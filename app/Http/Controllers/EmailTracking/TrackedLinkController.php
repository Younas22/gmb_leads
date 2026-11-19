<?php

namespace App\Http\Controllers\EmailTracking;

use App\Http\Controllers\Controller;
use App\Models\EmailTrackingLink;
use App\Services\EmailTracking\ClickRecorder;
use App\Services\EmailTracking\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Public redirect for tracked links: /r/{token}.
 *
 * The destination always comes from the stored link record, never from the
 * request, so this cannot be used as an open redirect. The page reveals nothing
 * about the lead, the sender or the account.
 */
class TrackedLinkController extends Controller
{
    private const HEADERS = [
        'Cache-Control' => 'no-store, private',
        'Referrer-Policy' => 'no-referrer',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    public function __invoke(Request $request, string $token, ClickRecorder $recorder)
    {
        $link = EmailTrackingLink::where('token_hash', EmailTrackingLink::hashToken($token))->with('email')->first();

        $url = $link && $link->is_active ? SafeUrl::check((string) $link->original_url) : null;
        if ($url === null) {
            return response()->view('email-link-unavailable', [], 404)->withHeaders(self::HEADERS);
        }

        try {
            $recorder->record($link, $request);
        } catch (Throwable $e) {
            // Recording must never stop the visitor from reaching the page
            Log::warning('Tracked link request not recorded', ['link_id' => $link->id, 'exception' => get_class($e)]);
        }

        return redirect()->away($url, 302)->withHeaders(self::HEADERS);
    }
}
