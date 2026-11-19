<?php

namespace App\Services\Gmail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Google OAuth 2.0 for Gmail sending (authorization code + PKCE).
 * Separate from "Sign in with Google" (Socialite).
 */
class GmailOAuth
{
    public const SEND_SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function isConfigured(): bool
    {
        return (bool) config('gmail.enabled') && filled(config('gmail.client_id')) && filled(config('gmail.client_secret'));
    }

    public function redirectUri(): string
    {
        return config('gmail.redirect_uri') ?: route('user.email-sending.callback');
    }

    public function authorizationUrl(string $state, string $codeVerifier, ?string $loginHint = null): string
    {
        $params = array_filter([
            'client_id' => config('gmail.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', config('gmail.scopes')),
            'access_type' => 'offline',
            // consent: always return a refresh token; select_account: pick the right mailbox
            'prompt' => 'consent select_account',
            'include_granted_scopes' => 'false',
            'state' => $state,
            'code_challenge' => self::challenge($codeVerifier),
            'code_challenge_method' => 'S256',
            'login_hint' => $loginHint,
        ]);

        return config('gmail.endpoints.authorize') . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public static function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Exchange the authorization code. Returns tokens and the verified account identity.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_in: int, scopes: list<string>, email: string, sub: string}
     */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        $data = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'redirect_uri' => $this->redirectUri(),
        ]);

        if (empty($data['access_token']) || empty($data['id_token'])) {
            throw new GmailException(GmailException::INVALID_GRANT_RESPONSE);
        }

        $claims = $this->idTokenClaims($data['id_token']);

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'scopes' => array_values(array_filter(explode(' ', (string) ($data['scope'] ?? '')))),
            'email' => mb_strtolower($claims['email']),
            'sub' => (string) $claims['sub'],
        ];
    }

    /** @return array{access_token: string, expires_in: int} */
    public function refresh(string $refreshToken): array
    {
        $data = $this->tokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);

        if (empty($data['access_token'])) {
            throw new GmailException(GmailException::INVALID_GRANT_RESPONSE);
        }

        return ['access_token' => $data['access_token'], 'expires_in' => (int) ($data['expires_in'] ?? 3600)];
    }

    /** Best effort: returns false when Google could not be reached or rejected it. */
    public function revoke(string $token): bool
    {
        try {
            return Http::asForm()->timeout((int) config('gmail.timeout'))->connectTimeout((int) config('gmail.connect_timeout'))
                ->post(config('gmail.endpoints.revoke'), ['token' => $token])->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    private function tokenRequest(array $params): array
    {
        if (!$this->isConfigured()) {
            throw new GmailException(GmailException::NOT_CONFIGURED);
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('gmail.timeout'))
                ->connectTimeout((int) config('gmail.connect_timeout'))
                ->post(config('gmail.endpoints.token'), $params + [
                    'client_id' => config('gmail.client_id'),
                    'client_secret' => config('gmail.client_secret'),
                ]);
        } catch (ConnectionException $e) {
            throw new GmailException(GmailException::CONNECT_FAILED, null, $e);
        }

        if ($response->status() === 400 && $response->json('error') === 'invalid_grant') {
            throw new GmailException(GmailException::AUTH_REVOKED, 400);
        }
        if (in_array($response->status(), [401, 403], true)) {
            throw new GmailException(GmailException::AUTH, $response->status());
        }
        if (!$response->successful()) {
            throw new GmailException($response->status() >= 500 ? GmailException::CONNECT_FAILED : GmailException::INVALID_GRANT_RESPONSE, $response->status());
        }

        return (array) $response->json();
    }

    /**
     * The ID token comes straight from Google's token endpoint over TLS, so per
     * OpenID Connect its signature need not be re-verified; we still check the
     * audience, issuer, expiry and that the email is verified.
     */
    private function idTokenClaims(string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new GmailException(GmailException::INVALID_GRANT_RESPONSE);
        }

        $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        $ok = is_array($claims)
            && ($claims['aud'] ?? null) === config('gmail.client_id')
            && in_array($claims['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true)
            && (int) ($claims['exp'] ?? 0) > time()
            && !empty($claims['email']) && filter_var($claims['email'], FILTER_VALIDATE_EMAIL)
            && ($claims['email_verified'] ?? false) === true
            && !empty($claims['sub']);

        if (!$ok) {
            throw new GmailException(GmailException::INVALID_GRANT_RESPONSE);
        }

        return $claims;
    }
}
