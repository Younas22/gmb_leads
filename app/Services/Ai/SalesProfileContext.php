<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Models\UserOffer;
use App\Models\UserSalesProfile;

/**
 * Read model of an account's AI Sales Profile for AI features (Phase 2:
 * website audits, offer matching, email drafts). Only ACTIVE skills, services
 * and offers are included. Always resolves the account owner, so team members
 * get their company's profile.
 */
class SalesProfileContext
{
    /**
     * @return array{
     *   has_profile: bool,
     *   company_name: ?string, description: ?string, website: ?string, portfolio_urls: list<string>,
     *   target_industries: list<string>, target_locations: list<string>,
     *   language: array{code: string, name: string}, tone: array{code: string, name: string},
     *   sender_name: ?string, sender_postal_address: ?string, signature: ?string, ai_instructions: ?string,
     *   skills: list<array>, services: list<array>, offers: list<array>
     * }
     */
    public function forUser(User $user): array
    {
        $owner = $user->getAccountOwner();
        $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first() ?? UserSalesProfile::forOwner($owner);

        $offers = UserOffer::ownedBy($owner)->active()->ordered()->get()
            ->groupBy('type')
            ->map(fn ($items) => $items->map(fn (UserOffer $o) => array_filter([
                'title' => $o->title,
                'description' => $o->description,
                'price_hint' => $o->price_hint,
                'ideal_for' => $o->ideal_for,
            ], fn ($v) => $v !== null && $v !== ''))->values()->all());

        $language = $profile->language ?: 'en';
        $tone = $profile->tone ?: 'professional';

        return [
            'has_profile' => $profile->exists,
            'company_name' => $profile->company_name,
            'description' => $profile->description,
            'website' => $profile->website,
            'portfolio_urls' => array_values($profile->portfolio_urls ?? []),
            'target_industries' => array_values($profile->target_industries ?? []),
            'target_locations' => array_values($profile->target_locations ?? []),
            'language' => ['code' => $language, 'name' => config("ai.languages.{$language}", $language)],
            'tone' => ['code' => $tone, 'name' => config("ai.tones.{$tone}", $tone)],
            'sender_name' => $profile->sender_name,
            'sender_postal_address' => $profile->sender_postal_address,
            'signature' => $profile->signature,
            'ai_instructions' => $profile->ai_instructions,
            'skills' => $offers->get(UserOffer::TYPE_SKILL, []),
            'services' => $offers->get(UserOffer::TYPE_SERVICE, []),
            'offers' => $offers->get(UserOffer::TYPE_OFFER, []),
        ];
    }

    /**
     * Render the context as a delimited block for a prompt. Values are the
     * account owner's own text; angle brackets are neutralised so a value
     * cannot close the block and pose as instructions.
     */
    public function toPromptBlock(array $context): string
    {
        $clean = fn (?string $v) => $v === null ? '' : trim(str_replace(['<', '>'], ['‹', '›'], $v));
        $list = fn (array $items) => $items === [] ? '(none)' : implode('; ', array_map($clean, $items));
        $offers = function (array $items) use ($clean) {
            if ($items === []) {
                return "  (none)\n";
            }
            $out = '';
            foreach ($items as $item) {
                $out .= '  - ' . $clean($item['title'] ?? '');
                foreach (['description' => 'details', 'price_hint' => 'price', 'ideal_for' => 'ideal for'] as $key => $label) {
                    if (!empty($item[$key])) {
                        $out .= " | {$label}: " . $clean($item[$key]);
                    }
                }
                $out .= "\n";
            }

            return $out;
        };

        return "<sales_profile>\n"
            . 'Business: ' . $clean($context['company_name']) . "\n"
            . 'About: ' . $clean($context['description']) . "\n"
            . 'Website: ' . $clean($context['website']) . "\n"
            . 'Portfolio: ' . $list($context['portfolio_urls']) . "\n"
            . 'Target industries: ' . $list($context['target_industries']) . "\n"
            . 'Target locations: ' . $list($context['target_locations']) . "\n"
            . 'Write in: ' . $clean($context['language']['name']) . "\n"
            . 'Tone: ' . $clean($context['tone']['name']) . "\n"
            . "Skills:\n" . $offers($context['skills'])
            . "Services:\n" . $offers($context['services'])
            . "Special offers:\n" . $offers($context['offers'])
            . 'Sender: ' . $clean($context['sender_name']) . "\n"
            . 'Postal address: ' . $clean($context['sender_postal_address']) . "\n"
            . 'Extra instructions from the account owner: ' . $clean($context['ai_instructions']) . "\n"
            . '</sales_profile>';
    }
}
