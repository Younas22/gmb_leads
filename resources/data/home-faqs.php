<?php

/*
 * Homepage FAQ (visible accordion + FAQPage structured data are both built from this list).
 * Every answer describes behaviour that exists in the app today; plan-dependent features say so.
 * The "Starter vs Growth" answer is generated from the live plans, so it never goes stale.
 *
 * Returns a function: fn (array $ctx): array  — $ctx = ['site' => string, 'plans' => Collection, 'currency' => array]
 * Answers may contain simple HTML links; the structured data uses the text only.
 */

return function (array $ctx): array {
    $site = e($ctx['site']);
    $refund = route('refund.policy');

    // Plan comparison from the live packages (monthly, paid)
    $planLines = [];
    foreach ($ctx['plans'] as $plan) {
        if ((float) $plan->price <= 0) {
            continue;
        }
        $bits = [];
        foreach (\App\Support\PlanFeatureCatalog::cardLines($plan) as [$label, $included, $value]) {
            if ($included) {
                $bits[] = e($label) . ($value ? ': ' . e($value) : '');
            }
        }
        $price = $ctx['currency']['symbol'] . number_format(\App\Services\CurrencyHelper::convert((float) $plan->price, $ctx['currency']), 0);
        $planLines[] = '<strong class="text-[#111827]">' . e($plan->name) . '</strong> (' . e($price) . '/month): ' . implode(', ', array_slice($bits, 0, 8)) . '.';
    }
    $plansAnswer = $planLines
        ? implode('<br>', $planLines) . '<br>See the <a href="#pricing" class="text-[#EA580C] font-medium hover:underline">pricing table</a> for every limit side by side.'
        : 'Each plan has different lead, AI, sending and device allowances. See the <a href="#pricing" class="text-[#EA580C] font-medium hover:underline">pricing table</a> for every limit side by side.';

    return [
        ['What is ' . $site . '?',
         $site . ' is a lead generation and outreach platform. You find businesses on Google Maps, save and organise them as leads, then use AI to research each one and draft a personal email you can send from your own Gmail.'],

        ['How does ' . $site . ' find business leads?',
         'Install the ' . $site . ' Chrome extension, search Google Maps by business type and location, and save the businesses you want. Their public listing details go into My Leads, where you can filter, organise and export them.'],

        ['Does ' . $site . ' offer a free trial?',
         'Yes. Every new account gets a <strong class="text-[#111827]">3-day free trial</strong> that starts automatically when you sign up, so you can try the core features within the trial plan\'s limits. After 3 days, choose a paid plan to keep going.'],

        ['Do I need a credit card to start?',
         'No. Signing up and starting the free trial needs no credit card or payment. When you upgrade, you pay with one of the payment methods shown at checkout and upload your payment receipt; your plan is activated once our team has checked it.'],

        ['What information can I collect about businesses?',
         'Whatever the business lists publicly: business name, category, phone number, address, website, Google Maps link, star rating and review count, opening hours and social media links. Some fields, such as emails and social links, depend on your plan.'],

        ['Can ' . $site . ' find business email addresses?',
         'Yes, when a business publishes an email address on its listing or website, ' . $site . ' can pick it up (email extraction depends on your plan). Not every business lists an email, so one is not available for every lead.'],

        ['What is the AI Sales Profile?',
         (($ctx['aiSoon'] ?? false) ? '<strong class="text-[#111827]">Coming soon.</strong> ' : '') . 'Your AI Sales Profile tells the AI about your business: what you do, the services and offers you sell, the industries and locations you target, and the language, tone and signature you want. The AI uses it in every analysis, email and offer, so everything sounds like you.'],

        ['Can AI write personalised sales emails?',
         (($ctx['aiSoon'] ?? false) ? '<strong class="text-[#111827]">Coming soon.</strong> When it launches, for each lead' : 'Yes, on plans that include AI. For each lead') . ', the AI checks the business\'s website, lists its strengths and gaps, picks your best-fit offer and drafts a personal email. Every email stays a <strong class="text-[#111827]">draft</strong>: you review, edit or discard it. Nothing is sent without your approval.'],

        ['Can I send emails through Gmail?',
         (($ctx['emailSoon'] ?? false) ? '<strong class="text-[#111827]">Coming soon.</strong> ' : '') . 'Yes, on plans that include Gmail sending. Connect your Gmail once (we only ask for permission to send, never to read your inbox), then send approved drafts one at a time from your own address. Replies arrive in your normal inbox.'],

        ['Can I track outreach performance?',
         (($ctx['emailSoon'] ?? false) ? '<strong class="text-[#111827]">Coming soon.</strong> ' : '') . 'Email Analytics shows which emails Gmail accepted or failed, optional link-click tracking (shown as estimates, because email security tools can open links), replies you mark as received, and follow-ups due. If you send through Resend, it also shows the delivery, bounce, open and click events Resend reports. Gmail does not report opens or inbox delivery.'],

        ['Can I export my leads?',
         'Yes. Export saved leads to <strong class="text-[#111827]">CSV or Excel</strong> from My Leads. The number of exports you can make each month depends on your plan.'],

        ['Can I organise and manage my leads?',
         'Yes. Give each lead a status (Pending, Contacted, Responded, Follow Up, Converted or Closed), group leads into folders, filter by location, rating, reviews and lead type, and keep notes and follow-up dates on every lead.'],

        ['Does ' . $site . ' support follow-up reminders?',
         (($ctx['emailSoon'] ?? false) ? '<strong class="text-[#111827]">Coming soon.</strong> ' : '') . 'Yes, on plans that include follow-ups. Set a follow-up date on a lead or an email, and due and overdue follow-ups appear on your dashboard and the Follow-ups page. They are reminders only; nothing is sent automatically.'],

        ['Who is ' . $site . ' for?',
         'Anyone who sells to businesses: digital marketing and SEO agencies, freelancers, local SEO professionals, web designers, sales teams and service providers looking for B2B clients.'],

        ['Can I search businesses in different countries?',
         'Yes. Choose a country, state and city when you search. Results come from Google Maps, so coverage depends on what Google Maps lists in that area.'],

        ['Is there a daily lead limit?',
         'Each plan has its own daily lead limit, and some plans are unlimited. Your dashboard always shows how many leads you have left today. Compare the limits in the <a href="#pricing" class="text-[#EA580C] font-medium hover:underline">pricing table</a>.'],

        ['What is the difference between the plans?', $plansAnswer],

        ['Can I cancel my subscription?',
         'Yes. Go to Subscription → Plan Management → Cancel Plan. Cancelling takes effect immediately and premium features stop. For refunds, please read our <a href="' . e($refund) . '" class="text-[#EA580C] font-medium hover:underline">Refund Policy</a>.'],

        ['Is ' . $site . ' mobile-friendly?',
         'Your dashboard, leads, AI tools and email pages work in a mobile browser. Finding new leads from Google Maps uses the Chrome extension, so that part needs Chrome on a desktop or laptop.'],

        ['How can I contact support?',
         'Email <a href="mailto:info@customernearme.com" class="text-[#EA580C] font-medium hover:underline">info@customernearme.com</a> or message us on <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="text-[#EA580C] font-medium hover:underline">WhatsApp (+92 346 0820722)</a>. Signed-in users can also use "Add Your Feedback" in the dashboard sidebar.'],
    ];
};
