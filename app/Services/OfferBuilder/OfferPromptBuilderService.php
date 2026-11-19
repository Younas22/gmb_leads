<?php

namespace App\Services\OfferBuilder;

use App\Models\OfferStrategy;
use App\Models\User;
use App\Services\Ai\SalesProfileContext;

/**
 * One prompt engine for all 16 strategies: universal template + the strategy's
 * own extension (from offer_strategies.prompt_extension), with [Token]
 * placeholders that the guided form fills in.
 *
 *  - template()     the prompt with every placeholder still in [brackets]
 *  - fill()         replace placeholders that have a value (unfilled ones stay visible)
 *  - missing()      required fields whose placeholder is still unresolved
 *  - forAi()        the text sent to the model: unresolved placeholders become
 *                   "(not provided)" so they are never mistaken for facts
 *  - profilePrefill() values from the AI Sales Profile for "Use My AI Sales Profile"
 */
class OfferPromptBuilderService
{
    /**
     * Business fields shared by every strategy. Tokens never repeat a strategy token.
     * type: text | textarea | select ; group: business | product | offer | style
     */
    public const GLOBAL_FIELDS = [
        ['key' => 'business_name', 'token' => 'Business Name', 'label' => 'Business name', 'type' => 'text', 'required' => false, 'group' => 'business', 'example' => 'Pixel Growth Studio'],
        ['key' => 'website', 'token' => 'Business Website', 'label' => 'Website', 'type' => 'text', 'required' => false, 'group' => 'business', 'example' => 'https://pixelgrowth.com'],
        ['key' => 'business_description', 'token' => 'Business Description', 'label' => 'What the business does', 'type' => 'textarea', 'required' => false, 'group' => 'business', 'example' => 'We build fast websites for local service businesses.'],
        ['key' => 'business_type', 'token' => 'Business Type', 'label' => 'Business type', 'type' => 'text', 'required' => false, 'group' => 'business', 'example' => 'Web design agency'],
        ['key' => 'product_name', 'token' => 'Product Name', 'label' => 'Product or service', 'type' => 'text', 'required' => true, 'group' => 'product', 'example' => 'Website redesign'],
        ['key' => 'target_audience', 'token' => 'Target Audience', 'label' => 'Who is it for?', 'type' => 'text', 'required' => true, 'group' => 'product', 'example' => 'Dental clinics in Dubai with outdated websites'],
        ['key' => 'customer_problem', 'token' => 'Customer Problem', 'label' => 'Their main problem', 'type' => 'text', 'required' => true, 'group' => 'product', 'example' => 'Few online bookings; the site is slow on phones'],
        ['key' => 'desired_outcome', 'token' => 'Desired Outcome', 'label' => 'Result they want', 'type' => 'text', 'required' => false, 'group' => 'product', 'example' => 'More booked appointments from Google'],
        ['key' => 'product_features', 'token' => 'Product Features', 'label' => 'Main features', 'type' => 'textarea', 'required' => false, 'group' => 'product', 'example' => 'Mobile-first design, online booking, 5 pages'],
        ['key' => 'product_benefits', 'token' => 'Product Benefits', 'label' => 'Benefits', 'type' => 'textarea', 'required' => false, 'group' => 'product', 'example' => 'Loads in under 2 seconds, patients book 24/7'],
        ['key' => 'price', 'token' => 'Price and Currency', 'label' => 'Price', 'type' => 'text', 'required' => true, 'group' => 'offer', 'example' => '$1,200'],
        ['key' => 'delivery_cost', 'token' => 'Estimated Delivery Cost', 'label' => 'Your cost to deliver', 'type' => 'text', 'required' => false, 'group' => 'offer', 'example' => 'About $300 in staff time'],
        ['key' => 'available_bonuses', 'token' => 'Available Bonuses', 'label' => 'Bonuses you can offer', 'type' => 'textarea', 'required' => false, 'group' => 'offer', 'example' => 'Free logo refresh, 1 month of hosting'],
        ['key' => 'usp', 'token' => 'Unique Selling Point', 'label' => 'Competitive advantage', 'type' => 'textarea', 'required' => false, 'group' => 'offer', 'example' => 'We only work with clinics'],
        ['key' => 'proof', 'token' => 'Genuine Testimonials or Results', 'label' => 'Real proof (testimonials, results)', 'type' => 'textarea', 'required' => false, 'group' => 'offer', 'example' => 'Smile Dental: bookings up 40% in 3 months'],
        ['key' => 'portfolio', 'token' => 'Portfolio Links', 'label' => 'Portfolio links', 'type' => 'textarea', 'required' => false, 'group' => 'offer', 'example' => 'https://pixelgrowth.com/work'],
        ['key' => 'offer_duration', 'token' => 'Offer Duration', 'label' => 'Offer duration / real deadline', 'type' => 'text', 'required' => false, 'group' => 'offer', 'example' => 'Until 31 December'],
        ['key' => 'sales_channel', 'token' => 'Sales Channel', 'label' => 'Marketing platform / sales channel', 'type' => 'text', 'required' => false, 'group' => 'style', 'example' => 'Cold email and WhatsApp'],
        ['key' => 'language', 'token' => 'Language', 'label' => 'Language', 'type' => 'text', 'required' => false, 'group' => 'style', 'example' => 'English'],
        ['key' => 'brand_voice', 'token' => 'Brand Tone', 'label' => 'Brand voice', 'type' => 'text', 'required' => false, 'group' => 'style', 'example' => 'Friendly and direct'],
    ];

    public const ADDITIONAL_TOKEN = 'Additional Instructions';

    /** Shown up front in the short form (plus the strategy's required fields); the rest is optional. */
    public const ESSENTIAL = ['product_name', 'price', 'target_audience', 'customer_problem'];

    /** Ready-made choices for the dropdowns (the user can always pick "Custom" and type). */
    public const SUGGESTIONS = [
        // General choices that fit most businesses (profile values are listed first)
        'product_name' => [
            'Website design', 'SEO service', 'Social media management', 'Online ads management', 'Software subscription',
            'Online course', 'Consulting session', 'Monthly maintenance plan', 'Cleaning service', 'Repair service',
            'Beauty treatment', 'Fitness membership', 'Restaurant meal', 'Product bundle',
        ],
        'price' => ['Free', '$49', '$99', '$199', '$499', '$999', '$49/month', '$99/month', '$299/month', '$500/month'],
        'target_audience' => [
            'Small local businesses', 'Restaurants and cafes', 'Dentists and clinics', 'Real estate agents',
            'Salons and spas', 'Gyms and fitness studios', 'Contractors and home services', 'Law firms',
            'E-commerce stores', 'Startups', 'Coaches and consultants', 'Hotels and travel businesses', 'Car dealers and garages',
        ],
        'business_name' => [],
        'available_bonuses' => ['Free consultation', 'Free setup', 'Extra month free', 'Priority support', 'Free training session'],
        'usp' => ['Fast delivery', 'Specialists in one industry', 'Money-back guarantee', 'Local and available 24/7', 'Lower price than big agencies'],
        'delivery_cost' => ['Low (mostly time)', 'About 20% of the price', 'About 50% of the price'],
        'customer_problem' => [
            'Not enough new customers', 'Website is outdated or slow', 'Hard to find on Google / Maps',
            'Too few online bookings or enquiries', 'Leads do not turn into sales', 'Price feels too high for them',
            'No time to handle marketing', 'Customers buy once and do not come back',
        ],
        'desired_outcome' => [
            'More customers every month', 'More calls and bookings', 'Higher sales per customer',
            'Steady monthly income', 'Save time on marketing', 'Rank higher on Google',
        ],
        'offer_duration' => ['No deadline', '7 days', '14 days', '30 days', 'Until the end of this month'],
        'sales_channel' => ['Cold email', 'WhatsApp', 'Facebook / Instagram', 'Website landing page', 'Phone call', 'In person'],
        'business_type' => ['Agency', 'Freelancer', 'Local service business', 'SaaS / software', 'E-commerce store', 'Consultant / coach'],
        // strategy fields
        'installment_count' => ['2', '3', '4', '6', '12'],
        'payment_frequency' => ['Weekly', 'Every 2 weeks', 'Monthly'],
        'plan_fees' => ['None'],
        'trial_days' => ['7 days', '14 days', '30 days'],
        'trial_after' => ['Account pauses until a plan is chosen', 'Paid plan starts automatically (customer is told first)'],
        'trial_cancel' => ['Cancel anytime, no charge during the trial'],
        'deadline' => ['Within 30 days', 'Within 60 days', 'Within 90 days'],
        'reward' => ['Full refund', 'Refund as account credit', '50% back'],
        'credit_window' => ['Within 14 days', 'Within 30 days', 'Within 60 days'],
        'eligibility' => ['New customers only', 'All customers'],
        'bonus_eligibility' => ['All active subscribers', 'Subscribers after month 3'],
        'commitment_terms' => ['Paid yearly, refundable within 14 days', 'Minimum 6 months'],
        'free_allowance' => ['First 100 units free each month', 'None'],
        'verification' => ['Booking / attendance records', 'Screenshots sent by the customer', 'Usage data in our system'],
        'qualifying_actions' => ['Attend every session', 'Complete all modules', 'Send the required content on time', 'Use the product every week'],
        'giveaway_name' => ['Free audit', 'Free guide / checklist (PDF)', 'Free consultation call', 'Free sample', 'Free template'],
        'giveaway_benefit' => ['Find quick wins to get more customers', 'Save time and money', 'See exactly what to fix first'],
        'optin_process' => ['Short form on the website', 'Send a WhatsApp message', 'Reply to the email'],
        'giveaway_cost' => ['Free (digital)', '15–30 minutes of staff time', 'Small material cost'],
        'qualifying_purchase' => ['3 months of service', '2 or more items', 'The yearly plan', 'The full package'],
        'bonus_item' => ['Free extra service', 'Free product', 'Free consultation', 'Extra month free'],
        'bonus_value' => ['Worth $50', 'Worth $100', 'Worth $250'],
        'bonus_conditions' => ['Delivered in the first month', 'While stock lasts', 'For new customers only'],
        'initial_payment' => ['$49 today', '$99 today', '25% today'],
        'remaining_terms' => ['Rest after 30 days', 'Rest when the work is delivered', 'Then the normal monthly price'],
        'usage_unit' => ['per item', 'per hour', 'per 100 units', 'per message', 'per user'],
        'unit_price' => ['$1', '$5', '$10'],
        'billing_rules' => ['Billed monthly with a spending cap', 'Prepaid credits'],
        'main_product' => [],
        'upsell_product' => ['Extra service', 'Premium version', 'Monthly maintenance', 'Express delivery', 'Extended warranty'],
        'upsell_price' => ['$29', '$49', '$99', '$149', '$29/month'],
        'upsell_benefit' => ['Faster results', 'Less work for the customer', 'Peace of mind'],
        'addon_a' => ['Express delivery', 'Extra revisions', 'Priority support'],
        'addon_b' => ['Extended warranty', 'Training session', 'Premium materials'],
        'addon_c' => ['Monthly maintenance', 'Gift wrapping', 'Extra month of support'],
        'premium_package' => ['Premium package', 'All-in-one package', 'VIP package'],
        'premium_price' => ['$999', '$1,499', '$299/month'],
        'standard_package' => ['Standard package'],
        'standard_price' => ['$499', '$99/month'],
        'previous_purchase' => ['Starter package', 'Audit / consultation', 'Trial month'],
        'upgrade_package' => ['Full package', 'Premium plan', 'Yearly plan'],
        'credit' => ['The full amount paid', '50% of the amount paid'],
        'installment_amount' => [],
        'initial_payment_plan' => ['First payment today', 'No payment today'],
        'trial_included' => ['All features', 'Main features with usage limits'],
        'trial_excluded' => ['None', 'Exports and team features'],
        'kept_features' => ['Core service only', 'Fewer pages / items', 'Basic support only'],
        'removed_features' => ['Extras and add-ons', 'Priority support', 'Custom work'],
        'reduced_price' => ['50% of the full price', '$199', '$49/month'],
        'subscription_plan' => ['Monthly plan', 'Yearly plan', 'Monthly retainer'],
        'continuity_bonus' => ['Free monthly check-up', 'Free strategy call every quarter', 'Member-only discount'],
        'monthly_plan' => ['$29/month', '$49/month', '$99/month'],
        'longterm_plan' => ['Yearly plan, 2 months free', '6-month plan, 10% off'],
        'setup_fee' => ['Setup fee', 'Joining fee', 'Onboarding fee'],
        'waiver_plan' => ['6-month plan', '12-month plan', 'Yearly plan paid upfront'],
        'fee_terms' => ['Fee charged if cancelled early', 'Minimum 6 months'],
    ];

    /** Fields whose value may be long */
    public const LONG_TYPES = ['textarea'];

    public function __construct(private readonly SalesProfileContext $profiles)
    {
    }

    /**
     * Dropdown choices per field for this user: their AI Sales Profile first (services,
     * prices, industries, tone, language), then the ready-made list, then the example.
     *
     * @return array<string, list<string>>
     */
    public function suggestions(User $user, OfferStrategy $strategy): array
    {
        // Lists are general (any business). The only profile value in a list is the one the
        // form is pre-filled with, so it shows as the selected choice.
        $prefill = $this->profilePrefill($user)['values'];
        $industries = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $this->profiles->forUser($user)['target_industries'] ?? []), fn ($v) => $v !== ''));
        $general = [
            'language' => array_values(config('ai.languages', [])),
            'brand_voice' => array_values(config('ai.tones', [])),
        ];

        $out = [];
        foreach ($this->fields($strategy) as $f) {
            if ($f['key'] === 'product_name') {
                continue; // the product dropdown lists the user's own Services (see _product-field)
            }
            if ($f['key'] === 'target_audience' && $industries !== []) {
                $out['target_audience'] = array_slice(array_values(array_unique($industries)), 0, 20); // only the profile's Target industries
                continue;
            }
            $ready = array_merge(self::SUGGESTIONS[$f['key']] ?? [], $general[$f['key']] ?? [], $f['options'] ?? []);
            if ($ready === [] && ($f['group'] ?? '') === 'strategy' && !empty($f['example'])) {
                $ready[] = $f['example']; // a strategy field without a ready-made list: its example is a fair choice
            }
            if ($ready === []) {
                continue; // free text (e.g. business name, website): no dropdown
            }
            $list = array_merge(isset($prefill[$f['key']]) ? [$prefill[$f['key']]] : [], $ready);
            $list = array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $list), fn ($v) => $v !== '')));
            if ($list !== []) {
                $out[$f['key']] = array_slice($list, 0, 20);
            }
        }

        return $out;
    }

    /** Keys shown up front for a strategy: the essentials + the strategy's required fields. */
    public function essentialKeys(OfferStrategy $strategy): array
    {
        $keys = self::ESSENTIAL;
        foreach ($strategy->fields() as $f) {
            if (!empty($f['required'])) {
                $keys[] = $f['key'];
            }
        }

        return $keys;
    }

    /**
     * The strategy example with the user's values filled in, for "Save without AI".
     * Unfilled placeholders are dropped instead of being shown in brackets.
     */
    public function filledExample(OfferStrategy $strategy, array $inputs): string
    {
        $text = $this->fill($strategy, (string) $strategy->example, $inputs);
        $text = preg_replace('/\s*\[[^\]]{1,60}\]/', '', $text) ?? $text;

        return trim(preg_replace('/\s{2,}/', ' ', $text) ?? $text);
    }

    /** Global + strategy fields, in form order. */
    public function fields(OfferStrategy $strategy): array
    {
        $strategyFields = array_map(fn ($f) => $f + ['group' => 'strategy'], $strategy->fields());

        return array_merge(self::GLOBAL_FIELDS, $strategyFields);
    }

    /** The full prompt with every placeholder in [brackets]. */
    public function template(OfferStrategy $strategy): string
    {
        $strategy->loadMissing('category');
        $b = fn (string $token) => '[' . $token . ']';

        return trim(<<<TXT
You are an expert direct-response marketing strategist specializing in value-driven offers inspired by Alex Hormozi's \$100M Offers and \$100M Money Models.

YOUR OBJECTIVE

Create a compelling, realistic, customer-focused marketing offer using the selected strategy.

OFFER STRATEGY

Main Category: {$strategy->category->name}
Specific Strategy: {$strategy->name}
Strategy Objective: {$strategy->objective}
Strategy Mechanism: {$strategy->description}

MY BUSINESS INFORMATION

Business Name: {$b('Business Name')}
Website: {$b('Business Website')}
About the Business: {$b('Business Description')}
Product or Service: {$b('Product Name')}
Business Type: {$b('Business Type')}
Target Audience: {$b('Target Audience')}
Customer Problem: {$b('Customer Problem')}
Desired Customer Outcome: {$b('Desired Outcome')}
Main Product Features: {$b('Product Features')}
Product Benefits: {$b('Product Benefits')}
Current Selling Price: {$b('Price and Currency')}
Delivery Cost: {$b('Estimated Delivery Cost')}
Available Bonuses: {$b('Available Bonuses')}
Competitive Advantage: {$b('Unique Selling Point')}
Available Proof: {$b('Genuine Testimonials or Results')}
Portfolio: {$b('Portfolio Links')}
Offer Duration: {$b('Offer Duration')}
Marketing Platform: {$b('Sales Channel')}
Preferred Language: {$b('Language')}
Brand Voice: {$b('Brand Tone')}

{$strategy->prompt_extension}

ADDITIONAL INSTRUCTIONS

{$b(self::ADDITIONAL_TOKEN)}

GENERATE THE FOLLOWING

1. Three offer concepts, each with a unique name.
2. Explain the value proposition of each concept.
3. Recommend the strongest offer and explain why.
4. Create a compelling headline and marketing hook.
5. Create a complete offer stack.
6. Suggest practical bonuses using available capabilities.
7. Recommend sustainable pricing and payment terms.
8. Suggest appropriate risk-reduction mechanisms.
9. Add urgency only when there is a genuine deadline.
10. Write a strong call to action.
11. Generate a social media advertisement.
12. Generate landing page hero copy.
13. Explain how and when to present this offer.
14. Provide an implementation checklist.

IMPORTANT RULES

Use the exact selected offer strategy.
Do not invent product features or customer results.
Do not fabricate testimonials, guarantees, discounts, deadlines, or scarcity.
Use only the actual business information supplied.
For any missing critical information, identify the missing fields explicitly.
Any suggested new features, bonuses, or pricing must be clearly marked as proposals.
Keep the offer easy to understand, valuable, credible, and commercially sustainable.
Do not imply official endorsement by Alex Hormozi.
TXT);
    }

    /**
     * Replace placeholders that have a value. Unfilled ones stay as [Token].
     *
     * @param array<string, string|null> $inputs field key => value
     */
    public function fill(OfferStrategy $strategy, string $prompt, array $inputs, ?string $additional = null): string
    {
        $map = [];
        foreach ($this->fields($strategy) as $f) {
            $value = trim((string) ($inputs[$f['key']] ?? ''));
            if ($value !== '') {
                $map['[' . $f['token'] . ']'] = $value;
            }
        }
        if (trim((string) $additional) !== '') {
            $map['[' . self::ADDITIONAL_TOKEN . ']'] = trim((string) $additional);
        }

        // One pass: a value that itself contains "[Something]" is never re-processed
        return strtr($prompt, $map);
    }

    /** The prompt the user sees: their edited text (Advanced mode) or the template, filled. */
    public function resolve(OfferStrategy $strategy, array $inputs, ?string $additional, ?string $customPrompt): string
    {
        $base = filled($customPrompt) ? (string) $customPrompt : $this->template($strategy);

        return $this->fill($strategy, $base, $inputs, $additional);
    }

    /**
     * Required fields still unresolved in the prompt that would be sent.
     *
     * @return list<string> labels
     */
    public function missing(OfferStrategy $strategy, array $inputs, ?string $additional, ?string $customPrompt): array
    {
        $resolved = $this->resolve($strategy, $inputs, $additional, $customPrompt);
        $missing = [];
        foreach ($this->fields($strategy) as $f) {
            if (!empty($f['required']) && str_contains($resolved, '[' . $f['token'] . ']')) {
                $missing[] = $f['label'];
            }
        }

        return $missing;
    }

    /**
     * Text for the model. Unresolved known placeholders become "(not provided)"; the
     * whole text is user data, so it is cleaned (no tags, no control characters).
     */
    public function forAi(OfferStrategy $strategy, array $inputs, ?string $additional, ?string $customPrompt): string
    {
        $text = $this->resolve($strategy, $inputs, $additional, $customPrompt);

        $tokens = array_map(fn ($f) => '[' . $f['token'] . ']', $this->fields($strategy));
        $tokens[] = '[' . self::ADDITIONAL_TOKEN . ']';
        $text = str_replace($tokens, '(not provided)', $text);

        return self::clean($text, (int) config('offer_builder.limits.custom_prompt', 20000) + 8000);
    }

    /** Language the answer should be written in (free text field, default English). */
    public function language(array $inputs): string
    {
        $lang = self::clean((string) ($inputs['language'] ?? ''), 40);

        return $lang !== '' ? $lang : 'English';
    }

    /**
     * Values from the account's AI Sales Profile for the guided form. Only fields the
     * profile really has; everything else stays empty. Nothing is written back.
     *
     * @return array{has_profile: bool, values: array<string, string>}
     */
    public function profilePrefill(User $user): array
    {
        $ctx = $this->profiles->forUser($user);
        $services = $ctx['services'] ?? [];
        $first = $services[0] ?? null;

        $values = array_filter([
            'business_name' => (string) $ctx['company_name'],
            'website' => (string) $ctx['website'],
            'business_description' => (string) $ctx['description'],
            // "Who is it for?" = one of the profile's Target industries (the first one by default)
            'target_audience' => (string) ($ctx['target_industries'][0] ?? ''),
            'portfolio' => implode("\n", array_slice($ctx['portfolio_urls'], 0, 5)),
            // The first active service fills product + price (the user can pick another or type)
            'product_name' => (string) ($first['title'] ?? ''),
            'price' => (string) ($first['price_hint'] ?? ''),
            'brand_voice' => $ctx['has_profile'] ? (string) $ctx['tone']['name'] : '',
            'language' => $ctx['has_profile'] ? (string) $ctx['language']['name'] : '',
        ], fn ($v) => trim($v) !== '');

        return [
            'has_profile' => (bool) $ctx['has_profile'] && $values !== [],
            'values' => $values,
            'services' => array_values(array_map(fn ($s) => array_filter([
                'title' => (string) ($s['title'] ?? ''),
                'price' => (string) ($s['price_hint'] ?? ''),
                'description' => (string) ($s['description'] ?? ''),
            ]), $services)),
        ];
    }

    /** Remove control characters and tag brackets, keep line breaks, cap the length. */
    public static function clean(string $text, int $max): string
    {
        $text = mb_scrub($text, 'UTF-8');
        $text = str_replace("\r\n", "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? '';
        $text = str_replace(['<', '>', '`'], ['‹', '›', "'"], $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? '';

        return trim(mb_substr($text, 0, $max));
    }
}
