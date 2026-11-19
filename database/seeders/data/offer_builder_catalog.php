<?php

/*
|--------------------------------------------------------------------------
| AI Offer Builder catalog: 4 categories, 16 strategies
|--------------------------------------------------------------------------
| Seeded into offer_categories / offer_strategies by OfferBuilderCatalogSeeder
| (idempotent by slug). Wording is original; the strategies are inspired by
| Alex Hormozi's published frameworks. No affiliation or endorsement implied.
|
| Strategy fields: each field has a key (stored in builder_offers.business_inputs),
| a token (shown in the prompt as [Token]), a label, an input type, required flag,
| an example and optional help. Tokens are unique within a strategy and never
| collide with the global business fields (OfferPromptBuilderService::GLOBAL_FIELDS).
*/

return [
    'categories' => [
        [
            'slug' => 'attraction',
            'name' => 'Attraction Offers',
            'icon' => 'fa-magnet',
            'purpose' => 'Attract new potential customers by making the initial purchase more appealing and reducing the barriers to buying.',
            'description' => 'Turn strangers into first-time buyers with an entry offer that is easy to say yes to.',
            'use_cases' => [
                'When launching a new product, service, or business.',
                'When website visitors are not converting into paying customers.',
                'When you want to turn cold prospects into first-time buyers.',
                'When competitors are attracting more customers with better entry offers.',
                'When you need to increase customer acquisition while controlling acquisition costs.',
            ],
        ],
        [
            'slug' => 'upsell',
            'name' => 'Upsell Offers',
            'icon' => 'fa-arrow-trend-up',
            'purpose' => 'Increase revenue from existing customers by presenting relevant upgrades, complementary services, or higher-value packages.',
            'description' => 'Help buyers get more of what they already want, at the moment they want it.',
            'use_cases' => [
                'When a customer is purchasing or has already purchased a product.',
                'When customers need additional features or premium services.',
                'When you want to increase average order value.',
                'When existing customers are ready for advanced packages.',
                'When you want to improve customer profitability without acquiring entirely new customers.',
            ],
        ],
        [
            'slug' => 'downsell',
            'name' => 'Downsell Offers',
            'icon' => 'fa-arrow-trend-down',
            'purpose' => 'Recover potential sales by providing suitable alternatives when customers decline the original offer.',
            'description' => 'A "no" to the main offer does not have to end the conversation.',
            'use_cases' => [
                'When a customer says the price is too expensive.',
                'When the full package exceeds the customer\'s budget.',
                'When customers prefer installment payments.',
                'When prospects want to test a product before committing.',
                'When customers need only a limited number of features or services.',
            ],
        ],
        [
            'slug' => 'continuity',
            'name' => 'Continuity Offers',
            'icon' => 'fa-arrows-rotate',
            'purpose' => 'Increase customer lifetime value and build more predictable revenue by encouraging recurring purchases and ongoing relationships.',
            'description' => 'Turn one-time buyers into members, subscribers and long-term clients.',
            'use_cases' => [
                'When selling monthly or yearly subscriptions.',
                'When offering ongoing support or maintenance.',
                'When converting one-time buyers into repeat customers.',
                'When encouraging longer subscription commitments.',
                'When building stable recurring revenue.',
            ],
        ],
    ],

    'strategies' => [
        // ───────────────────────────── Attraction ─────────────────────────────
        [
            'category' => 'attraction',
            'slug' => 'win-your-money-back',
            'name' => 'Win Your Money Back',
            'purpose' => 'Encourage initial purchases by giving customers a clearly defined way to recover their purchase amount or earn an eligible reward.',
            'objective' => 'Make the first purchase feel low-risk by tying a refund or reward to a measurable action the customer completes.',
            'description' => 'The customer pays the normal price, then earns all or part of it back (as cash, credit or a reward) by completing specific, verifiable actions that also help them succeed with the product.',
            'when_to_use' => [
                'Customers hesitate because they doubt they will follow through.',
                'Results depend on the customer taking part (attendance, usage, homework).',
                'You can verify completion objectively.',
            ],
            'business_types' => ['Fitness & coaching', 'Courses & training', 'Agencies with onboarding tasks', 'Software with activation milestones'],
            'example' => 'Buy [Product Name] for [Price and Currency] and qualify for [Refund or Reward] after completing [Qualifying Actions].',
            'examples' => [
                "Fitness studio – 8-Week Body Reset: \$299.\nAttend all 24 classes and send your weekly check-in photo.\nDo that and the full \$299 comes back as credit for your next program.\nYou either get fit, or you train the next 8 weeks free.",
                "Online marketing course: \$199 to join.\nFinish all 6 modules and launch your first ad within 30 days.\nShow us your live ad and we refund 100% of your fee.\nThe only way to lose money is to not take action.",
                "Web agency – website kick-off: \$500.\nSend us your texts and photos within 14 days.\nDo that and the \$500 is taken off your final invoice.\nFast content means a faster website, and you pay less.",
            ],
            'fields' => [
                ['key' => 'reward', 'token' => 'Refund or Reward', 'label' => 'Refund or reward offered', 'type' => 'text', 'required' => true, 'example' => 'Full refund as account credit'],
                ['key' => 'qualifying_actions', 'token' => 'Qualifying Actions', 'label' => 'Actions the customer must complete', 'type' => 'textarea', 'required' => true, 'example' => 'Attend 12 of 12 sessions and submit weekly check-ins'],
                ['key' => 'deadline', 'token' => 'Completion Deadline', 'label' => 'Deadline to complete them', 'type' => 'text', 'required' => false, 'example' => 'Within 60 days of purchase'],
                ['key' => 'verification', 'token' => 'Verification Method', 'label' => 'How completion is verified', 'type' => 'text', 'required' => false, 'example' => 'Attendance log in our booking system'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: WIN YOUR MONEY BACK

Design a "win your money back" offer for [Product Name] at [Price and Currency].

Refund or Reward: [Refund or Reward]
Qualifying Actions: [Qualifying Actions]
Completion Deadline: [Completion Deadline]
Verification Method: [Verification Method]

Define the measurable customer actions, the eligibility conditions and how completion is verified.
Make sure the qualifying actions also move the customer toward [Desired Outcome].
Check the economics: estimate how many customers are likely to qualify and whether the offer stays profitable given [Estimated Delivery Cost]. Flag any risk.
Write the full terms in plain language so nothing is hidden.
TXT,
        ],
        [
            'category' => 'attraction',
            'slug' => 'free-giveaways',
            'name' => 'Free Giveaways',
            'purpose' => 'Attract prospects with a valuable free resource or service that naturally introduces the paid product.',
            'objective' => 'Deliver real value for free so the right prospects raise their hand and see why the paid offer is the next step.',
            'description' => 'Give away something genuinely useful (a guide, audit, template, sample or short service) in exchange for contact details or attention, then connect it to the paid offer.',
            'when_to_use' => [
                'You need more leads or a bigger audience.',
                'Prospects do not yet trust you enough to pay.',
                'You can show your expertise in a small, low-cost format.',
            ],
            'business_types' => ['Agencies & consultants', 'Local services', 'SaaS', 'Coaches & educators'],
            'example' => 'Get a free [Giveaway Name] designed to help [Target Audience] achieve [Giveaway Benefit].',
            'examples' => [
                "Free 10-point Google Maps audit for local businesses.\nAnswer 3 quick questions and get your report in 48 hours.\nIt shows exactly why competitors appear above you, and how to fix it.\nNo cost, no obligation.",
                "Free \"Year-End Tax Checklist\" for small business owners.\nDownload it today and avoid the 7 most common filing mistakes.\nWant help? Book a free 15-minute call to go through it together.",
                "Bakery – free cupcake box for our first 50 new WhatsApp subscribers.\nSend \"SWEET\" to our number to claim yours.\nPick it up any day this week, no purchase needed.",
            ],
            'fields' => [
                ['key' => 'giveaway_name', 'token' => 'Giveaway Name', 'label' => 'Free resource or service', 'type' => 'text', 'required' => true, 'example' => 'Free local SEO audit'],
                ['key' => 'giveaway_benefit', 'token' => 'Giveaway Benefit', 'label' => 'What it helps them achieve', 'type' => 'text', 'required' => true, 'example' => 'Find the 5 fixes that bring more calls from Google Maps'],
                ['key' => 'optin_process', 'token' => 'Opt-in Process', 'label' => 'How people claim it', 'type' => 'text', 'required' => false, 'example' => 'Fill in a 3-question form on the website'],
                ['key' => 'giveaway_cost', 'token' => 'Giveaway Cost', 'label' => 'Your cost to deliver one', 'type' => 'text', 'required' => false, 'example' => '30 minutes of staff time'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: FREE GIVEAWAYS

Create a free giveaway that attracts [Target Audience] and leads naturally to [Product Name].

Giveaway: [Giveaway Name]
Main Benefit: [Giveaway Benefit]
Opt-in Process: [Opt-in Process]
Cost to Deliver: [Giveaway Cost]

Explain exactly who the giveaway helps and why it is worth their time.
Define the opt-in process and what the prospect receives.
Show the bridge from the free resource to the paid offer without bait-and-switch.
Write a follow-up sequence outline (3 steps) that presents [Product Name] to people who claimed the giveaway.
TXT,
        ],
        [
            'category' => 'attraction',
            'slug' => 'decoy-offers',
            'name' => 'Decoy Offers',
            'purpose' => 'Help customers compare package value and make a more informed purchasing decision.',
            'objective' => 'Present genuinely available packages side by side so the best-value option is easy to recognise.',
            'description' => 'Offer two or three real packages with clear, honest differences. The comparison helps buyers see the value of the recommended package. Every package must be available and deliverable.',
            'when_to_use' => [
                'Buyers struggle to judge what a fair price is.',
                'You already sell (or can sell) more than one package.',
                'Most buyers pick the cheapest option by default.',
            ],
            'business_types' => ['Agencies', 'SaaS plans', 'Service packages', 'Restaurants & hospitality'],
            'example' => 'Compare [Basic Package], [Recommended Package] and [Premium Package].',
            'examples' => [
                "Website packages:\nStarter – \$299: 5 pages, mobile-friendly.\nGrowth – \$599: 10 pages + local SEO setup (most popular).\nPro – \$1,199: 20 pages + 3 months of SEO.\nMost businesses choose Growth: twice the pages and SEO for \$300 more.",
                "Car wash menu:\nBasic – \$15: outside wash.\nPremium – \$25: wash, wax and interior clean (best value).\nUltimate – \$45: full detail.\nPremium gives you a wax and a clean interior for just \$10 more.",
                "Social media plans:\n8 posts – \$200 per month.\n16 posts + stories – \$350 per month (recommended).\n30 posts + ads – \$700 per month.\nThe middle plan keeps you posting 4 times a week without running ads.",
            ],
            'fields' => [
                ['key' => 'basic_package', 'token' => 'Basic Package', 'label' => 'Basic package (name, price, what is included)', 'type' => 'textarea', 'required' => true, 'example' => 'Starter: $299, 5-page website'],
                ['key' => 'recommended_package', 'token' => 'Recommended Package', 'label' => 'Recommended package', 'type' => 'textarea', 'required' => true, 'example' => 'Growth: $599, 10 pages + local SEO setup'],
                ['key' => 'premium_package', 'token' => 'Premium Package', 'label' => 'Premium package (optional)', 'type' => 'textarea', 'required' => false, 'example' => 'Pro: $1,199, 20 pages + 3 months SEO'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: DECOY OFFERS (PACKAGE COMPARISON)

Build an honest package comparison for [Product Name].

Basic Package: [Basic Package]
Recommended Package: [Recommended Package]
Premium Package: [Premium Package]

Every package must be genuinely available and deliverable. Do not create fake or misleading options.
Show transparent differences in features, pricing and value in a comparison table.
Explain why the recommended package is the best value for most of [Target Audience].
Suggest package names and short descriptions that make the differences easy to understand.
TXT,
        ],
        [
            'category' => 'attraction',
            'slug' => 'buy-x-get-y',
            'name' => 'Buy X Get Y',
            'purpose' => 'Increase transaction value by giving a relevant bonus when customers buy a qualifying quantity or package.',
            'objective' => 'Reward a slightly bigger first purchase with a bonus that costs you little but matters to the customer.',
            'description' => 'When the customer buys a defined quantity or package (X), they receive a related bonus product or service (Y). The bonus should be relevant, valued by the customer and affordable for you.',
            'when_to_use' => [
                'Your products are bought in quantities or bundles.',
                'You have a low-cost item or service customers value highly.',
                'You want a higher first order without discounting.',
            ],
            'business_types' => ['Retail & e-commerce', 'Restaurants', 'Salons & spas', 'Agencies selling bundles'],
            'example' => 'Buy [Qualifying Purchase] and receive [Bonus Item].',
            'examples' => [
                "Book 3 months of social media management and get a free brand photo shoot.\nThe shoot is worth \$250 and gives you 30 fresh photos for your posts.\nWe schedule it in your first month.\nAvailable for the first 10 clients this month.",
                "Order 2 family meals and get a dessert platter free.\nPerfect for weekend family dinners.\nValid for dine-in, takeaway and delivery.\nEvery Friday to Sunday this month.",
                "Print 1,000 business cards and get 100 matching flyers free.\nSame design, same paper quality, ready together in 3 working days.\nPerfect for your next event or shop opening.",
            ],
            'fields' => [
                ['key' => 'qualifying_purchase', 'token' => 'Qualifying Purchase', 'label' => 'What the customer must buy (X)', 'type' => 'text', 'required' => true, 'example' => '3 months of social media management'],
                ['key' => 'bonus_item', 'token' => 'Bonus Item', 'label' => 'What they get (Y)', 'type' => 'text', 'required' => true, 'example' => 'A free brand photo shoot'],
                ['key' => 'bonus_value', 'token' => 'Bonus Value', 'label' => 'Bonus value / your cost', 'type' => 'text', 'required' => false, 'example' => 'Worth $250, costs us $60'],
                ['key' => 'bonus_conditions', 'token' => 'Bonus Conditions', 'label' => 'Delivery conditions', 'type' => 'text', 'required' => false, 'example' => 'Delivered in month 1'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: BUY X GET Y

Create a "buy X get Y" offer for [Product Name].

Qualifying Purchase (X): [Qualifying Purchase]
Bonus (Y): [Bonus Item]
Bonus Value and Cost: [Bonus Value]
Delivery Conditions: [Bonus Conditions]

Explain why the bonus is relevant to the qualifying purchase.
Check profitability: compare the extra revenue from the qualifying purchase with the cost of the bonus.
State the delivery conditions clearly.
TXT,
        ],
        [
            'category' => 'attraction',
            'slug' => 'pay-less-now',
            'name' => 'Pay Less Now',
            'purpose' => 'Reduce the upfront purchase barrier through a transparent introductory payment or limited initial discount.',
            'objective' => 'Lower the cost of getting started while clearly disclosing what the customer pays later.',
            'description' => 'The customer pays a smaller amount to start and the rest later (or after a trial period). The full cost and every later charge must be disclosed up front.',
            'when_to_use' => [
                'The full price scares buyers away at the first step.',
                'Customers see the value only after they start.',
                'You can afford to collect revenue later.',
            ],
            'business_types' => ['Coaching & courses', 'SaaS', 'Agencies with retainers', 'Memberships'],
            'example' => 'Get started with [Product Name] for [Initial Payment], followed by [Remaining Payment Terms].',
            'examples' => [
                "Start the 12-week coaching program for just \$99 today.\nThe remaining \$400 is charged after 30 days.\nTotal price: \$499, with nothing hidden.\nYou get to see results before you pay the rest.",
                "Get your new website with a \$150 deposit.\nPay the remaining \$650 only when the site goes live.\nTotal: \$800.\nNo full payment until you are happy with the result.",
                "Try our booking software for \$9 in your first month.\nFrom month 2 it is \$49 per month.\nCancel anytime from your account, no contract.",
            ],
            'fields' => [
                ['key' => 'initial_payment', 'token' => 'Initial Payment', 'label' => 'Amount paid to start', 'type' => 'text', 'required' => true, 'example' => '$99 today'],
                ['key' => 'remaining_terms', 'token' => 'Remaining Payment Terms', 'label' => 'What is paid later, and when', 'type' => 'text', 'required' => true, 'example' => '$400 after 30 days'],
                ['key' => 'eligibility', 'token' => 'Eligibility', 'label' => 'Who qualifies', 'type' => 'text', 'required' => false, 'example' => 'New customers only'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: PAY LESS NOW

Create a "pay less now" offer for [Product Name] (full price [Price and Currency]).

Initial Payment: [Initial Payment]
Remaining Payment Terms: [Remaining Payment Terms]
Eligibility: [Eligibility]

Specify the initial payment, the remaining payment, when each is charged and the customer's total cost.
Show that the total cost is clearly disclosed in every piece of copy.
Explain how the lower start reduces the barrier to buying, and how to reduce the risk of non-payment.
TXT,
        ],
        [
            'category' => 'attraction',
            'slug' => 'free-with-consumption',
            'name' => 'Free with Consumption',
            'purpose' => 'Let customers access a product or service at no initial charge while paying for clearly defined usage.',
            'objective' => 'Remove the entry price and earn from actual usage, with clear allowances and cost protection.',
            'description' => 'Access is free; the customer pays per unit of usage beyond a free allowance (or for every unit). Billing rules and caps must be clear so customers are never surprised.',
            'when_to_use' => [
                'Your costs grow with usage.',
                'Customers want to start small and grow.',
                'Usage can be measured reliably.',
            ],
            'business_types' => ['SaaS & APIs', 'Printing & production', 'Marketplaces', 'Telecom & utilities'],
            'example' => 'Access [Product Name] for free and pay [Price per Unit] per [Usage Unit].',
            'examples' => [
                "Create your lead-finder account for free.\nYour first 200 leads every month cost nothing.\nAfter that it is \$5 per 100 leads.\nSet a monthly spending cap so there are never surprises.",
                "Print-on-demand with no setup fee and no minimum order.\nUpload your design for free.\nPay only \$3 for each item we print and ship.",
                "Join our SMS service for free.\nSend messages for \$0.02 each, billed monthly.\nNo monthly fee, no contract, and you see every cost in your dashboard.",
            ],
            'fields' => [
                ['key' => 'usage_unit', 'token' => 'Usage Unit', 'label' => 'Usage unit', 'type' => 'text', 'required' => true, 'example' => 'per 100 leads exported'],
                ['key' => 'unit_price', 'token' => 'Price per Unit', 'label' => 'Price per unit', 'type' => 'text', 'required' => true, 'example' => '$5'],
                ['key' => 'free_allowance', 'token' => 'Free Allowance', 'label' => 'Free allowance', 'type' => 'text', 'required' => false, 'example' => 'First 200 leads per month free'],
                ['key' => 'billing_rules', 'token' => 'Billing Rules', 'label' => 'Billing rules and caps', 'type' => 'text', 'required' => false, 'example' => 'Billed monthly, spending cap set by the customer'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: FREE WITH CONSUMPTION

Design usage-based pricing for [Product Name] where access is free.

Usage Unit: [Usage Unit]
Price per Unit: [Price per Unit]
Free Allowance: [Free Allowance]
Billing Rules: [Billing Rules]

Define the free allowance, paid usage units and billing rules.
Add cost protection for the customer (caps, alerts) and for the business (minimums, limits).
Show two or three example bills at different usage levels.
TXT,
        ],

        // ───────────────────────────── Upsell ─────────────────────────────
        [
            'category' => 'upsell',
            'slug' => 'classic-upsell',
            'name' => 'Classic Upsell',
            'purpose' => 'Offer an additional product, service, or upgrade that complements a customer\'s existing purchase.',
            'objective' => 'Present the natural next purchase right when the customer is already buying.',
            'description' => 'After (or during) the main purchase, offer one relevant upgrade or add-on that makes the original purchase work better or faster.',
            'when_to_use' => [
                'There is an obvious "next thing" customers need.',
                'Customers ask for more help after buying.',
                'You want a higher average order value.',
            ],
            'business_types' => ['E-commerce', 'Agencies', 'SaaS', 'Local services'],
            'example' => 'Add [Upsell Product] to [Main Product] for [Upsell Price].',
            'examples' => [
                "Your new website is almost ready.\nAdd a Google Business Profile setup for \$149.\nYou will appear on Google Maps from day one and get calls straight from search.\nMost of our clients add it.",
                "Thanks for choosing this laptop.\nAdd a 2-year protection plan for \$79.\nIt covers repairs, battery issues and accidental damage.\nOne repair usually costs more than the plan.",
                "Your wedding photo package is booked.\nAdd a printed premium album for \$250.\nYour 60 favourite photos, printed and bound to last a lifetime.",
            ],
            'fields' => [
                ['key' => 'main_product', 'token' => 'Main Product', 'label' => 'Original product', 'type' => 'text', 'required' => false, 'example' => '5-page website'],
                ['key' => 'upsell_product', 'token' => 'Upsell Product', 'label' => 'Upgrade or additional service', 'type' => 'text', 'required' => true, 'example' => 'Google Business Profile setup'],
                ['key' => 'upsell_price', 'token' => 'Upsell Price', 'label' => 'Additional price', 'type' => 'text', 'required' => true, 'example' => '$149'],
                ['key' => 'upsell_benefit', 'token' => 'Additional Benefit', 'label' => 'Additional value', 'type' => 'text', 'required' => false, 'example' => 'Appear in local map results from day one'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: CLASSIC UPSELL

Create a relevant upgrade offer for customers who purchase [Main Product].

Original Product: [Main Product]
Original Price: [Price and Currency]
Upsell Product: [Upsell Product]
Upsell Price: [Upsell Price]
Additional Benefit: [Additional Benefit]

Explain why the upgrade complements the original purchase.
Recommend the best moment to present the upsell.
Generate checkout copy, a follow-up message, and an upgrade CTA.
TXT,
        ],
        [
            'category' => 'upsell',
            'slug' => 'menu-upsell',
            'name' => 'Menu Upsell',
            'purpose' => 'Present customers with a small, organised selection of relevant optional upgrades.',
            'objective' => 'Let customers customise their purchase from a short menu instead of one take-it-or-leave-it add-on.',
            'description' => 'Offer two to four optional add-ons, each with a clear price and benefit, so customers pick what fits them. Fewer, well-chosen options convert better than long lists.',
            'when_to_use' => [
                'Different customers need different extras.',
                'You have several small add-ons.',
                'A single upsell gets ignored.',
            ],
            'business_types' => ['Restaurants & cafes', 'Agencies', 'Events', 'Software add-ons'],
            'example' => 'Customise [Product Name] with [Add-on A], [Add-on B] or [Add-on C].',
            'examples' => [
                "Make your website package complete. Add any of these:\n• Copywriting for all pages – \$200\n• Logo refresh – \$150\n• Monthly maintenance and updates – \$49/month\nChoose one, two or all three.",
                "Make your coffee your way:\n• Extra espresso shot – \$1\n• Oat or almond milk – \$0.50\n• Fresh cookie on the side – \$2\nTell the barista what you would like.",
                "Make your move easier. Add:\n• Full packing service – \$120\n• Furniture assembly – \$80\n• One month of storage – \$60\nAdd them when you book or up to 2 days before your move.",
            ],
            'fields' => [
                ['key' => 'addon_a', 'token' => 'Add-on A', 'label' => 'Add-on A (name, price)', 'type' => 'text', 'required' => true, 'example' => 'Copywriting, $200'],
                ['key' => 'addon_b', 'token' => 'Add-on B', 'label' => 'Add-on B (name, price)', 'type' => 'text', 'required' => true, 'example' => 'Logo refresh, $150'],
                ['key' => 'addon_c', 'token' => 'Add-on C', 'label' => 'Add-on C (optional)', 'type' => 'text', 'required' => false, 'example' => 'Monthly maintenance, $49/month'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: MENU UPSELL

Create a short menu of optional upgrades for [Product Name].

Add-on A: [Add-on A]
Add-on B: [Add-on B]
Add-on C: [Add-on C]

Present each add-on with its price, benefit and who it is for.
Recommend the order to show them in and which one to highlight.
Keep the menu short and easy to choose from; suggest a simple bundle price only as a clearly marked proposal.
TXT,
        ],
        [
            'category' => 'upsell',
            'slug' => 'anchor-upsell',
            'name' => 'Anchor Upsell',
            'purpose' => 'Use a transparent comparison of real package prices and benefits to help customers understand the value of different options.',
            'objective' => 'Show a real premium option first so the standard option (or the upgrade) is understood in context.',
            'description' => 'Present a genuine higher-priced package next to the standard one. Both must be real, and the price difference must reflect a real difference in value.',
            'when_to_use' => [
                'You have a real premium tier that some customers buy.',
                'Customers think the standard price is high.',
                'You want more customers to consider the premium tier.',
            ],
            'business_types' => ['Agencies', 'Consultants', 'SaaS', 'Hospitality'],
            'example' => 'Compare [Standard Package] at [Standard Price] with [Premium Package] at [Premium Price].',
            'examples' => [
                "Most clients choose our Growth plan: SEO + Google Ads management for \$1,200 per month.\nIt brings both organic and paid leads every month.\nPrefer to start smaller? SEO only is \$500 per month.\nYou can upgrade at any time.",
                "Personal training plan: \$300 per month with a coach, a custom plan and nutrition support.\nGroup classes only: \$60 per month.\nNot sure? Start with classes and upgrade when you are ready.",
                "Sea-view suite: \$280 per night, with balcony and breakfast included.\nStandard room: \$150 per night.\nCelebrating something? The suite makes it unforgettable.",
            ],
            'fields' => [
                ['key' => 'standard_package', 'token' => 'Standard Package', 'label' => 'Standard package', 'type' => 'text', 'required' => false, 'example' => 'Monthly SEO'],
                ['key' => 'standard_price', 'token' => 'Standard Price', 'label' => 'Standard price', 'type' => 'text', 'required' => false, 'example' => '$500/month'],
                ['key' => 'premium_package', 'token' => 'Premium Package Name', 'label' => 'Premium package', 'type' => 'text', 'required' => true, 'example' => 'SEO + Ads management'],
                ['key' => 'premium_price', 'token' => 'Premium Price', 'label' => 'Premium price', 'type' => 'text', 'required' => true, 'example' => '$1,200/month'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: ANCHOR UPSELL

Create an honest anchor comparison for [Product Name].

Standard Package: [Standard Package] at [Standard Price]
Premium Package: [Premium Package Name] at [Premium Price]

Both packages must be real and available. Do not inflate prices or invent a fake "original" price.
Explain what the price difference buys in concrete terms.
Recommend how to present the premium option first and when to offer the upgrade.
TXT,
        ],
        [
            'category' => 'upsell',
            'slug' => 'rollover-upsell',
            'name' => 'Rollover Upsell',
            'purpose' => 'Encourage an upgrade by applying eligible previous spending toward a higher-value purchase.',
            'objective' => 'Make upgrading feel fair by crediting what the customer already spent.',
            'description' => 'Customers who bought a smaller product can apply all or part of what they paid as credit toward a bigger package, within a set time window.',
            'when_to_use' => [
                'Customers buy a starter product first.',
                'You have a clear higher-value next step.',
                'Customers hesitate to "pay twice".',
            ],
            'business_types' => ['Courses & coaching', 'Agencies', 'SaaS', 'Equipment & retail'],
            'example' => 'Upgrade to [Upgrade Package] and receive [Eligible Credit] from your previous [Previous Purchase].',
            'examples' => [
                "Already bought our \$199 website audit?\nUpgrade to a full redesign within 30 days and the \$199 is credited.\nYour redesign costs \$1,300 instead of \$1,499.\nWe start with the fixes from your audit.",
                "Finished our \$49 mini-course?\nThe full \$299 program is \$250 for you this month.\nEverything you paid counts toward the next step.",
                "Moving from the Basic to the Pro plan?\nYour unused Basic months are credited to Pro.\nYou never pay twice for the same time.",
            ],
            'fields' => [
                ['key' => 'previous_purchase', 'token' => 'Previous Purchase', 'label' => 'What they bought before', 'type' => 'text', 'required' => true, 'example' => 'Website audit ($199)'],
                ['key' => 'upgrade_package', 'token' => 'Upgrade Package', 'label' => 'Upgrade package and price', 'type' => 'text', 'required' => true, 'example' => 'Full website redesign ($1,499)'],
                ['key' => 'credit', 'token' => 'Eligible Credit', 'label' => 'Credit applied', 'type' => 'text', 'required' => false, 'example' => 'The full $199'],
                ['key' => 'credit_window', 'token' => 'Credit Window', 'label' => 'Time limit for the credit', 'type' => 'text', 'required' => false, 'example' => 'Within 30 days of the audit'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: ROLLOVER UPSELL

Create a rollover upgrade offer.

Previous Purchase: [Previous Purchase]
Upgrade Package: [Upgrade Package]
Eligible Credit: [Eligible Credit]
Credit Window: [Credit Window]

Calculate what the customer pays after the credit.
Explain why crediting previous spending makes the upgrade fair and easy to accept.
State eligibility and the time window clearly.
TXT,
        ],

        // ───────────────────────────── Downsell ─────────────────────────────
        [
            'category' => 'downsell',
            'slug' => 'payment-plans',
            'name' => 'Payment Plans',
            'purpose' => 'Make a purchase more manageable by spreading the price across multiple payments.',
            'objective' => 'Keep the sale when the full price at once is the only obstacle, with full disclosure of the total.',
            'description' => 'Split the price into instalments. The total payable, schedule and any fees must be disclosed in every message.',
            'when_to_use' => [
                'Customers want the product but cannot pay at once.',
                'Your price is high relative to monthly budgets.',
                'You can manage collection risk.',
            ],
            'business_types' => ['Coaching & courses', 'Agencies', 'Healthcare & dental', 'Equipment'],
            'example' => 'Get [Product Name] with [Installment Count] instalments of [Installment Amount].',
            'examples' => [
                "Get your clinic website for \$1,200.\nPay in 3 monthly payments of \$400.\nTotal: \$1,200, no interest and no extra fees.\nYour site goes live after the first payment.",
                "Dental treatment: \$2,400.\nPay \$400 per month over 6 months, 0% interest.\nStart your treatment this month and spread the cost.",
                "Online course: \$600.\nPay 4 payments of \$150, every 2 weeks.\nFull access to every lesson from day one.",
            ],
            'fields' => [
                ['key' => 'installment_count', 'token' => 'Installment Count', 'label' => 'Number of payments', 'type' => 'number', 'required' => true, 'example' => '3'],
                ['key' => 'installment_amount', 'token' => 'Installment Amount', 'label' => 'Amount per payment (if known)', 'type' => 'text', 'required' => false, 'example' => '$350'],
                ['key' => 'initial_payment_plan', 'token' => 'Initial Payment', 'label' => 'Initial payment', 'type' => 'text', 'required' => false, 'example' => '$350 today'],
                ['key' => 'payment_frequency', 'token' => 'Payment Frequency', 'label' => 'Payment frequency', 'type' => 'text', 'required' => false, 'example' => 'Monthly'],
                ['key' => 'plan_fees', 'token' => 'Any Applicable Fees', 'label' => 'Additional fees', 'type' => 'text', 'required' => false, 'example' => 'None'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: PAYMENT PLANS

Create an affordable payment structure for [Product Name].

Original Price: [Price and Currency]
Number of Payments: [Installment Count]
Amount per Payment: [Installment Amount]
Initial Payment: [Initial Payment]
Payment Frequency: [Payment Frequency]
Additional Fees: [Any Applicable Fees]

Calculate the payment breakdown.
Show the total payable amount.
Explain how installments reduce the upfront purchase barrier.
Generate persuasive marketing copy without concealing the total price or payment conditions.
TXT,
        ],
        [
            'category' => 'downsell',
            'slug' => 'free-trials',
            'name' => 'Free Trials',
            'purpose' => 'Let potential customers experience the product\'s value before purchasing.',
            'objective' => 'Lower the risk of buying by letting prospects try first, then convert them with a clear path to paying.',
            'description' => 'Give time-limited free access with defined features. Billing, cancellation and limits must be disclosed before the trial starts.',
            'when_to_use' => [
                'Prospects want to test before committing.',
                'The value is easy to see after a short time.',
                'You can onboard trial users efficiently.',
            ],
            'business_types' => ['SaaS', 'Memberships & gyms', 'Subscriptions', 'Online services'],
            'example' => 'Try [Product Name] free for [Number of Days] with [Included Features].',
            'examples' => [
                "Try our CRM free for 14 days.\nAll features, no credit card needed.\nImport your contacts and send your first follow-ups in minutes.\nWhen the trial ends, your account simply pauses until you choose a plan.",
                "Your first 7 days at our gym are free.\nJoin any class, use all equipment and meet the trainers.\nNo payment and no contract, just come in and try it.",
                "Use Pro free for 30 days.\nAfter the trial you move to the free plan automatically,\nunless you choose to upgrade. Nothing is charged without your OK.",
            ],
            'fields' => [
                ['key' => 'trial_days', 'token' => 'Number of Days', 'label' => 'Trial duration', 'type' => 'text', 'required' => true, 'example' => '14 days'],
                ['key' => 'trial_included', 'token' => 'Included Features', 'label' => 'Features included', 'type' => 'textarea', 'required' => true, 'example' => 'All features, up to 100 leads'],
                ['key' => 'trial_excluded', 'token' => 'Excluded Features', 'label' => 'Features excluded', 'type' => 'text', 'required' => false, 'example' => 'Export and team seats'],
                ['key' => 'trial_card', 'token' => 'Payment Method Required', 'label' => 'Card required to start?', 'type' => 'select', 'options' => ['No', 'Yes'], 'required' => false, 'example' => 'No'],
                ['key' => 'trial_after', 'token' => 'What Happens After Trial', 'label' => 'What happens when the trial ends', 'type' => 'text', 'required' => false, 'example' => 'Account pauses until a plan is chosen'],
                ['key' => 'trial_cancel', 'token' => 'Cancellation Conditions', 'label' => 'Cancellation rules', 'type' => 'text', 'required' => false, 'example' => 'Cancel anytime from settings'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: FREE TRIALS

Create a compelling free trial offer for [Product Name].

Trial Duration: [Number of Days]
Available Features: [Included Features]
Restricted Features: [Excluded Features]
Payment Method Required: [Payment Method Required]
Trial Expiration Behavior: [What Happens After Trial]
Cancellation Rules: [Cancellation Conditions]

Explain why customers should start the trial.
Show the benefits customers can experience during the trial.
Generate onboarding messages and conversion CTAs.
Clearly disclose any automatic billing.
TXT,
        ],
        [
            'category' => 'downsell',
            'slug' => 'feature-downsells',
            'name' => 'Feature Downsells',
            'purpose' => 'Create a lower-priced version of an offer by reducing features, deliverables, or scope.',
            'objective' => 'Keep the customer by offering less for less, instead of lowering the price of the full offer.',
            'description' => 'Remove features, deliverables or support from the full offer to create a smaller package at a lower price. The value of the full offer stays intact.',
            'when_to_use' => [
                'Customers need only part of the full package.',
                'The budget is fixed and lower than your price.',
                'You do not want to discount the full offer.',
            ],
            'business_types' => ['Agencies', 'Consultants', 'Software', 'Local services'],
            'example' => 'Get the essential [Selected Features] package for [Reduced Price].',
            'examples' => [
                "Is the full \$1,200 website more than you need right now?\nGet our Essential site: 3 pages, professional design, mobile-friendly.\nJust \$499.\nAdd more pages any time later.",
                "Monthly SEO at \$800 too much for now?\nStart with Local SEO only: Google Maps optimisation and local listings.\n\$350 per month, no long contract.",
                "A full-day photo shoot is \$1,500.\nNeed less? Book a 2-hour mini session with 20 edited photos for \$400.
Same photographer, same editing quality, just a shorter shoot.",
            ],
            'fields' => [
                ['key' => 'kept_features', 'token' => 'Selected Features', 'label' => 'What the smaller package keeps', 'type' => 'textarea', 'required' => true, 'example' => 'Design + 3 pages, no copywriting'],
                ['key' => 'removed_features', 'token' => 'Removed Features', 'label' => 'What is removed', 'type' => 'textarea', 'required' => false, 'example' => 'Copywriting, SEO setup, 2 revision rounds'],
                ['key' => 'reduced_price', 'token' => 'Reduced Price', 'label' => 'Reduced price', 'type' => 'text', 'required' => true, 'example' => '$249'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: FEATURE DOWNSELLS

Create a lower-priced version of [Product Name] (full price [Price and Currency]).

Kept in the smaller package: [Selected Features]
Removed: [Removed Features]
Reduced Price: [Reduced Price]

Explain clearly what is and is not included, so the full offer keeps its value.
Write the script for presenting this downsell after a customer declines the full offer.
Suggest how a customer can upgrade later.
TXT,
        ],

        // ───────────────────────────── Continuity ─────────────────────────────
        [
            'category' => 'continuity',
            'slug' => 'continuity-bonus-offers',
            'name' => 'Continuity Bonus Offers',
            'purpose' => 'Encourage ongoing subscriptions by offering relevant benefits to customers who keep an eligible recurring plan.',
            'objective' => 'Give subscribers a valuable reason to join now and stay longer.',
            'description' => 'Customers who start or keep a recurring plan receive a bonus (often delivered over time) that is only available to active subscribers.',
            'when_to_use' => [
                'You sell a subscription or retainer.',
                'Customers cancel after a few months.',
                'You have a bonus that is valuable but cheap to deliver.',
            ],
            'business_types' => ['Memberships', 'SaaS', 'Agencies with retainers', 'Subscription boxes'],
            'example' => 'Subscribe to [Subscription Plan] and receive [Recurring Customer Bonus].',
            'examples' => [
                "Join our SEO retainer at \$500 per month.\nEvery quarter you also get a free 1-hour strategy call with our lead expert.\nWe review your results and plan your next 3 months together.\nAvailable only to active clients.",
                "Subscribe to our coffee box.\nEvery 3rd box includes a free bag of a limited-edition roast.\nThe longer you stay, the more you discover.",
                "IT support plan members get a free yearly security check.\nWe test your devices, backups and passwords\nand fix any risk we find.",
            ],
            'fields' => [
                ['key' => 'subscription_plan', 'token' => 'Subscription Plan', 'label' => 'Subscription plan and price', 'type' => 'text', 'required' => true, 'example' => 'Monthly SEO retainer, $500/month'],
                ['key' => 'continuity_bonus', 'token' => 'Recurring Customer Bonus', 'label' => 'Bonus for subscribers', 'type' => 'text', 'required' => true, 'example' => 'Free quarterly strategy call'],
                ['key' => 'bonus_eligibility', 'token' => 'Bonus Eligibility', 'label' => 'Who qualifies and when', 'type' => 'text', 'required' => false, 'example' => 'Active subscribers after month 3'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: CONTINUITY BONUS OFFERS

Create a continuity bonus offer for [Subscription Plan].

Subscriber Bonus: [Recurring Customer Bonus]
Eligibility: [Bonus Eligibility]

Explain why the bonus is valuable to ongoing subscribers.
Define exactly when and how the bonus is delivered, and what happens if the customer cancels.
Write retention messages that remind subscribers of the bonus.
TXT,
        ],
        [
            'category' => 'continuity',
            'slug' => 'continuity-discounts',
            'name' => 'Continuity Discounts',
            'purpose' => 'Encourage longer commitments with transparent subscription savings.',
            'objective' => 'Reward a longer commitment with a real, calculated saving.',
            'description' => 'Offer a lower effective price for a longer commitment (for example annual instead of monthly). The saving must be calculated from real prices.',
            'when_to_use' => [
                'You offer monthly and longer plans.',
                'Cash up front helps your business.',
                'You want lower churn.',
            ],
            'business_types' => ['SaaS', 'Memberships', 'Agencies', 'Insurance & services'],
            'example' => 'Choose [Long-term Plan] instead of [Monthly Plan] and save [Saving].',
            'examples' => [
                "Our software is \$49 per month.\nChoose yearly and pay \$470: you save \$118, that is 2 months free.\nSame features, one simple payment, refundable within 14 days.",
                "Gym membership: \$60 per month.\nPay 12 months upfront for \$600 and save \$120.\nYour price is locked for the whole year.",
                "Home cleaning: \$120 per visit.\nChoose a 6-month weekly plan and pay \$100 per visit.\nSame cleaner every week, and you save \$480.",
            ],
            'fields' => [
                ['key' => 'monthly_plan', 'token' => 'Monthly Plan', 'label' => 'Monthly plan and price', 'type' => 'text', 'required' => true, 'example' => '$49/month'],
                ['key' => 'longterm_plan', 'token' => 'Long-term Plan', 'label' => 'Longer plan and price', 'type' => 'text', 'required' => true, 'example' => '$470/year'],
                ['key' => 'commitment_terms', 'token' => 'Commitment Terms', 'label' => 'Commitment and refund terms', 'type' => 'text', 'required' => false, 'example' => 'Paid yearly, refundable within 14 days'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: CONTINUITY DISCOUNTS

Create a continuity discount offer for [Product Name].

Monthly Plan: [Monthly Plan]
Long-term Plan: [Long-term Plan]
Commitment Terms: [Commitment Terms]

Calculate the actual saving (amount and percentage) from the prices given. Do not invent prices.
Explain the trade-off honestly (commitment vs saving).
Write copy that presents the longer plan as the smart choice without pressure tactics.
TXT,
        ],
        [
            'category' => 'continuity',
            'slug' => 'waived-fee',
            'name' => 'Waived Fee',
            'purpose' => 'Remove a legitimate one-time fee for customers who choose an eligible ongoing subscription or service arrangement.',
            'objective' => 'Make starting a recurring plan easier by waiving a real setup or joining fee.',
            'description' => 'You charge a genuine one-time fee (setup, onboarding, joining). Customers who commit to a recurring plan for a defined term have that fee waived.',
            'when_to_use' => [
                'You have a real setup or onboarding cost.',
                'You want customers to choose a longer commitment.',
                'The setup fee blocks people from starting.',
            ],
            'business_types' => ['Gyms & memberships', 'Agencies', 'SaaS with onboarding', 'Telecom & utilities'],
            'example' => 'Get the [Setup Fee] waived when subscribing to [Plan] under [Fee Commitment Terms].',
            'examples' => [
                "Our \$300 onboarding fee is waived when you choose a 6-month retainer.\nOnboarding covers the setup of your accounts, tracking and reports.\nIf you cancel before 6 months, the fee is charged.",
                "Join our gym for 12 months and pay no \$99 joining fee.\nStart training this week and spend that money on new gear instead.
If you leave before 12 months, the joining fee applies.",
                "Choose our 1-year internet plan and the \$50 installation fee is waived.\nA technician sets everything up for you at no extra cost.
The fee is only charged if you end the plan in the first year.",
            ],
            'fields' => [
                ['key' => 'setup_fee', 'token' => 'Setup Fee', 'label' => 'Fee that is waived (name, amount)', 'type' => 'text', 'required' => true, 'example' => 'Onboarding fee, $300'],
                ['key' => 'waiver_plan', 'token' => 'Plan', 'label' => 'Plan that qualifies', 'type' => 'text', 'required' => true, 'example' => '6-month retainer, $500/month'],
                ['key' => 'fee_terms', 'token' => 'Fee Commitment Terms', 'label' => 'Commitment terms', 'type' => 'text', 'required' => false, 'example' => 'Minimum 6 months; fee charged if cancelled earlier'],
            ],
            'prompt_extension' => <<<'TXT'
SPECIFIC STRATEGY: WAIVED FEE

Create a waived-fee offer.

Fee Waived: [Setup Fee]
Qualifying Plan: [Plan]
Commitment Terms: [Fee Commitment Terms]

The fee must be a real fee that is normally charged. Do not invent a fee to waive.
Explain what the fee covers and why waiving it is valuable.
State the commitment terms and what happens if the customer leaves early.
TXT,
        ],
    ],
];
