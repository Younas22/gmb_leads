@php
    // ── Homepage data ──
    // Real, approved customer reviews only, e.g.
    // ['stars' => 5, 'text' => '...', 'name' => 'Full Name', 'role' => 'Agency Owner', 'avatar' => 'public/assets/images/reviews/name.jpg']
    $homeReviews = [];
    // No real reviews yet: show the example reviews everywhere, always labelled as examples (never as real customers).
    $reviewsAreSamples = false;
    if (empty($homeReviews)) {
        $homeReviews = require resource_path('data/home-sample-reviews.php');
        $reviewsAreSamples = true;
    }
    // FAQ: one list for the accordion and the FAQPage structured data
    // AI locked by the admin in every package: present it as "coming soon" everywhere on this page
    $aiSoon = \App\Support\PlanFeatureCatalog::aiComingSoon();
    $emailSoon = \App\Support\PlanFeatureCatalog::comingSoon('email');
    $homeFaqs = (require resource_path('data/home-faqs.php'))([
        'aiSoon' => $aiSoon,
        'emailSoon' => $emailSoon,
        'site' => \App\Models\Setting::get('site_name', 'CustomerNearme'),
        'plans' => $userPackages->filter(fn ($p) => $p->billing_type === 'monthly'),
        'currency' => $currency,
    ]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- ============ CRITICAL FIX: Remove noindex ============ -->
    <meta name="robots" content="index, follow" />
    
    <!-- ============ META DESCRIPTIONS & KEYWORDS ============ -->
    <meta name="description" content="CustomerNearme - Find verified Google Maps business leads, then let AI analyse each website and write personal outreach emails you send from your own Gmail. Real-time data, AI email drafts, AI Offer Builder and campaigns." />
    <meta name="keywords" content="ai cold email writer, ai sales assistant, ai outreach emails, google maps lead generation, direct client hunting, gmb business data, cold email leads, business finder tool, verified business contacts, find business leads online" />
    <meta name="author" content="CustomerNearme" />
    <meta name="publisher" content="CustomerNearme" />
    <meta name="theme-color" content="#f97316" />
    
    <!-- ============ CANONICAL URL ============ -->
    <link rel="canonical" href="https://www.customernearme.com/" />
    
    <!-- ============ OPEN GRAPH TAGS ============ -->
    <meta property="og:title" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} - Find Real Google Maps Business Leads for Direct Client Hunting" />
    <meta property="og:description" content="Find verified business leads on Google Maps and let AI write a personal outreach email for each one. Review, approve and send from your own Gmail." />
    <meta property="og:image" content="{{ asset('public/assets/images/og-image-1200x630.png') }}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://www.customernearme.com/" />
    <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <meta property="og:locale" content="en_US" />
    
    <!-- ============ TWITTER CARD TAGS ============ -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} - Google Maps Lead Generator" />
    <meta name="twitter:description" content="Direct client hunting made easy. Get verified business leads from Google Maps instantly. No fake scraping." />
    <meta name="twitter:image" content="{{ asset('public/assets/images/twitter-card-1200x630.png') }}" />
    <meta name="twitter:creator" content="@CustomerNearme" />
    <meta name="twitter:site" content="@CustomerNearme" />
    
    <!-- ============ LINKEDIN TAGS ============ -->
    <meta property="linkedin:title" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} - Google Maps Lead Generation for Sales Professionals" />
    <meta property="linkedin:description" content="Find real Google Maps business leads for cold email and sales outreach. Direct client hunting made simple." />
    
    <!-- ============ FAVICON ============ -->
    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif

    <!-- ============ PRECONNECT & DNS PREFETCH ============ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://www.google-analytics.com">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                        'poppins': ['Poppins', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        'primary-blue': '#3b82f6',
                        'dark-blue': '#2563eb',
                        'primary-orange': '#f97316',
                        'dark-orange': '#ea580c'
                    }
                }
            }
        }
    </script>

    <script>
        const BASE_URL = '{{ url("/") }}';
    </script>

        <!-- ============ SCHEMA.ORG STRUCTURED DATA ============ -->
    
    <!-- 1. ORGANIZATION SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}",
      "url": "https://www.customernearme.com",
      "logo": "{{ asset('public/' . \App\Models\Setting::get('site_logo', 'assets/images/logo.png')) }}",
      "description": "AI-powered lead generation platform that pulls real-time verified business data directly from Google Maps for direct client hunting, cold email campaigns, and sales outreach",
      "sameAs": [
        "https://www.facebook.com/CustomerNearme",
        "https://www.instagram.com/customernearme",
        "https://x.com/CustomerNearme",
        "https://www.youtube.com/@CustomerNearme",
        "https://www.linkedin.com/company/customernearme"
      ],
      "contactPoint": {
        "@@type": "ContactPoint",
        "contactType": "Customer Support",
        "telephone": "+92-346-0820722",
        "email": "info@customernearme.com",
        "areaServed": "Worldwide",
        "availableLanguage": "en-US"
      },
      "address": {
        "@@type": "PostalAddress",
        "addressCountry": "PK",
        "addressLocality": "Khanewal",
        "addressRegion": "Punjab"
      },
      "foundingDate": "2023"
    }
    </script>
    
    <!-- 2. SAASPRODUCT SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "SaaSProduct",
      "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}",
      "description": "Real-time Google Maps lead generation tool for direct client hunting. Find verified business data for cold email, sales, and lead generation campaigns.",
      "url": "https://www.customernearme.com",
      "logo": "{{ asset('public/' . \App\Models\Setting::get('site_logo', 'assets/images/logo.png')) }}",
      "applicationCategory": [
        "BusinessApplication",
        "SoftwareApplication"
      ],
      "operatingSystem": "Web-based",
      "offers": {
        "@@type": "AggregateOffer",
        "priceCurrency": "USD",
        "lowPrice": "0",
        "highPrice": "99",
        "offerCount": 3,
        "url": "https://www.customernearme.com#pricing"
      },
      "featureList": [
        "Real-time Google Maps business data integration",
        "Advanced filtering by category, rating, location, and reviews",
        "CSV and Excel export functionality",
        "Lead management with tags, notes, and status tracking",
        "REST API access for automation",
        "Verified phone numbers and business contact information",
        "Social media links extraction",
        "Business rating and review insights",
        "Saved leads stored permanently in account"
      ],
      "inLanguage": "en-US",
      "isAccessibleForFree": true,
      "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "247",
        "bestRating": "5",
        "worstRating": "1"
      }
    }
    </script>
    
    <!-- 3. FAQPAGE SCHEMA (same questions as the visible FAQ) -->
    <script type="application/ld+json">
    {!! json_encode([
        '@@context' => 'https://schema.org',
        '@@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => [
            '@@type' => 'Question',
            'name' => html_entity_decode(strip_tags($f[0]), ENT_QUOTES),
            'acceptedAnswer' => ['@@type' => 'Answer', 'text' => html_entity_decode(strip_tags(str_replace('<br>', ' ', $f[1])), ENT_QUOTES)],
        ], $homeFaqs),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
    
    <!-- 4. WEBPAGE SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebPage",
      "@@id": "https://www.customernearme.com",
      "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} - Real Google Maps Business Leads for Direct Client Hunting",
      "description": "Find verified business leads directly from Google Maps for cold email, sales, and direct client hunting. Real-time data, no fake scraping.",
      "url": "https://www.customernearme.com",
      "image": "{{ asset('public/assets/images/og-image.png') }}",
      "datePublished": "2023-01-01",
      "dateModified": "{{ date('Y-m-d') }}",
      "inLanguage": "en-US",
      "isPartOf": {
        "@@type": "Website",
        "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}"
      },
      "mainEntity": {
        "@@type": "SaaSProduct",
        "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}"
      }
    }
    </script>
    
    <!-- 5. BREADCRUMBLIST SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "https://www.customernearme.com"
        },
        {
          "@@type": "ListItem",
          "position": 2,
          "name": "Features",
          "item": "https://www.customernearme.com#features"
        },
        {
          "@@type": "ListItem",
          "position": 3,
          "name": "How It Works",
          "item": "https://www.customernearme.com#how-it-works"
        },
        {
          "@@type": "ListItem",
          "position": 4,
          "name": "Pricing",
          "item": "https://www.customernearme.com#pricing"
        },
        {
          "@@type": "ListItem",
          "position": 5,
          "name": "FAQ",
          "item": "https://www.customernearme.com#faq"
        }
      ]
    }
    </script>
    
    <!-- 6. PRODUCT SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Product",
      "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}",
      "description": "Real-time Google Maps business lead generation tool for direct client hunting and cold email campaigns",
      "brand": {
        "@@type": "Brand",
        "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}"
      },
      "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "247"
      },
      "offers": {
        "@@type": "AggregateOffer",
        "priceCurrency": "USD",
        "lowPrice": "0",
        "highPrice": "99"
      }
    }
    </script>
    
    <!-- 7. LOCALBUSINESS SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "LocalBusiness",
      "name": "{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}",
      "description": "Global lead generation platform providing Google Maps business data for direct client hunting",
      "address": {
        "@@type": "PostalAddress",
        "addressCountry": "PK",
        "addressLocality": "Khanewal",
        "addressRegion": "Punjab"
      },
      "telephone": "+92-346-0820722",
      "email": "info@customernearme.com",
      "url": "https://www.customernearme.com",
      "serviceArea": {
        "@@type": "Place",
        "name": "Worldwide"
      }
    }
    </script>
    
    <!-- 8. ACTION SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Action",
      "name": "Sign Up for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}",
      "url": "{{ route('auth.show') }}",
      "description": "Start finding Google Maps business leads for direct client hunting",
      "target": {
        "@@type": "EntryPoint",
        "urlTemplate": "{{ route('auth.show') }}",
        "actionPlatform": [
          "DesktopWebPlatform",
          "MobileWebPlatform"
        ]
      }
    }
    </script>

    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-Z0E0SPN8D3"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-Z0E0SPN8D3');
</script>

    <title>{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} - Find Quality Business Leads Fast via Google Maps</title>
    @include('partials.home.styles')
</head>
<body class="bg-white hp-body">
    <!-- Navigation -->
    @include('partials.home.nav')

    <!-- Navbar Spacer (72px to match nav height) -->
    <div class="h-[72px]"></div>

    <!-- Navbar Scripts -->
    <script>
    (function() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const navbar = document.getElementById('navbar');
        const bar1 = document.getElementById('bar-1');
        const bar2 = document.getElementById('bar-2');
        const bar3 = document.getElementById('bar-3');
        let isOpen = false;

        // Toggle mobile menu
        menuBtn.addEventListener('click', function() {
            isOpen = !isOpen;
            menuBtn.setAttribute('aria-expanded', isOpen);

            if (isOpen) {
                mobileMenu.classList.remove('hidden');
                mobileMenu.style.maxHeight = '0';
                mobileMenu.style.overflow = 'hidden';
                mobileMenu.style.transition = 'max-height 0.3s ease-out';
                requestAnimationFrame(function() {
                    mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px';
                });
                // Animate hamburger to X
                bar1.style.transform = 'translateY(7px) rotate(45deg)';
                bar2.style.opacity = '0';
                bar2.style.transform = 'translateX(10px)';
                bar3.style.transform = 'translateY(-7px) rotate(-45deg)';
            } else {
                mobileMenu.style.maxHeight = '0';
                setTimeout(function() {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.style.maxHeight = '';
                    mobileMenu.style.overflow = '';
                    mobileMenu.style.transition = '';
                }, 300);
                // Animate X back to hamburger
                bar1.style.transform = '';
                bar2.style.opacity = '1';
                bar2.style.transform = '';
                bar3.style.transform = '';
            }
        });

        // Close mobile menu function (for link clicks)
        window.closeMobileMenu = function() {
            if (isOpen) {
                isOpen = false;
                menuBtn.setAttribute('aria-expanded', 'false');
                mobileMenu.style.maxHeight = '0';
                setTimeout(function() {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.style.maxHeight = '';
                    mobileMenu.style.overflow = '';
                    mobileMenu.style.transition = '';
                }, 300);
                bar1.style.transform = '';
                bar2.style.opacity = '1';
                bar2.style.transform = '';
                bar3.style.transform = '';
            }
        };

        // Navbar scroll effect — adds shadow on scroll
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 10) {
                navbar.classList.add('shadow-sm');
                navbar.style.borderColor = 'transparent';
            } else {
                navbar.classList.remove('shadow-sm');
                navbar.style.borderColor = '';
            }
            lastScroll = currentScroll;
        }, { passive: true });

        // Close menu on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                window.closeMobileMenu();
                menuBtn.focus();
            }
        });

        // Close menu on click outside
        document.addEventListener('click', function(e) {
            if (isOpen && !mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                window.closeMobileMenu();
            }
        });
    })();
    </script>

    <!-- Flash Messages -->
    @if(session('payment_success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center">
            <svg class="w-5 h-5 mr-3 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <div>
                <strong>Payment Submitted!</strong> We’ve received your payment screenshot. Our team will verify it and activate your subscription shortly.
            </div>
        </div>
    </div>
    @endif

    @include('partials.home.hero')

    @include('partials.home.sections')

    <!-- Pricing -->
    <section id="pricing" class="hp-sec bg-[#FAFAFA] border-y border-gray-100" aria-labelledby="pricing-heading">
        <div class="hp-wrap">
            <div class="text-center mb-7">
                <h2 id="pricing-heading" class="hp-h2">Simple, <span class="hp-mark">Transparent</span> Pricing</h2>
                <p class="hp-lead mt-4 max-w-2xl mx-auto">Choose a plan that fits your business. Start with a free trial, then upgrade when you need more leads, AI and sending.</p>
            </div>

            <!-- Individual / Company Tabs (only show if both types have packages) -->
            @if($companyPackages->count() > 0)
            <div class="flex justify-center mb-5">
                <div class="inline-flex bg-gray-100 rounded-full p-1">
                    <button class="tab-btn bg-primary-orange text-white px-6 py-2 rounded-full text-sm font-semibold transition-colors cursor-pointer" data-tab="user">Individual</button>
                    <button class="tab-btn text-gray-600 px-6 py-2 rounded-full text-sm font-semibold transition-colors hover:text-gray-900 cursor-pointer" data-tab="company">Company</button>
                </div>
            </div>
            @endif

            
            @php
                $featureLabels = [
                    'unlimited_map_scraping'  => 'Unlimited Map Scraping',
                    'daily_leads_limit'       => 'Daily Leads Limit',
                    'basic_business_signals'  => 'Basic Business Signals',
                    'contact_ready_leads'     => 'Contact-Ready Leads',
                    'email_scraping'          => 'Email Scraping',
                    'social_media_scraping'   => 'Social Media Scraping',
                    'website_extraction'      => 'Website Extraction',
                    'latest_review_insights'  => 'Latest Review Insights',
                    'advanced_review_filters' => 'Advanced Review Filters',
                    'export_leads'            => 'Export Leads',
                    'max_devices'             => 'Devices Access',
                    'priority_support'        => 'Priority Support',
                ];
                $boolFeatures = [
                    'unlimited_map_scraping', 'basic_business_signals', 'contact_ready_leads',
                    'email_scraping', 'social_media_scraping', 'website_extraction',
                    'latest_review_insights', 'advanced_review_filters', 'priority_support',
                ];
                // Features shown in the intelligence comparison table
                $intelligenceFeatures = [
                    'unlimited_map_scraping', 'basic_business_signals', 'contact_ready_leads',
                    'email_scraping', 'social_media_scraping', 'website_extraction',
                    'latest_review_insights', 'advanced_review_filters',
                ];
                $hideFromCards = $intelligenceFeatures;

                // Split packages into monthly / yearly groups
                $monthlyUserPackages = $userPackages->filter(fn($p) => $p->billing_type === 'monthly');
                $yearlyUserPackages  = $userPackages->filter(fn($p) => $p->price == 0 || $p->billing_type === 'yearly');

                $pricingTabs = [
                    ['type' => 'user', 'packages' => $userPackages],
                ];
                if ($companyPackages->count() > 0) {
                    $pricingTabs[] = ['type' => 'company', 'packages' => $companyPackages];
                }
            @endphp

            <!-- Monthly / Yearly Billing Toggle -->
            <div class="flex justify-center mb-4">
                <div class="inline-flex items-center bg-white border border-gray-200 rounded-full p-1 gap-1">
                    <button id="billing-monthly-btn"
                            onclick="switchBilling('monthly')"
                            class="billing-toggle-btn px-5 py-1.5 rounded-full text-[13px] font-semibold transition-colors bg-primary-orange text-white cursor-pointer">
                        Monthly
                    </button>
                    <button id="billing-yearly-btn"
                            onclick="switchBilling('yearly')"
                            class="billing-toggle-btn px-5 py-1.5 rounded-full text-[13px] font-semibold transition-colors text-gray-600 hover:text-gray-900 cursor-pointer flex items-center gap-2">
                        Yearly
                        <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">Save 2 months</span>
                    </button>
                </div>
            </div>

            @php
                $packageCardMacro = function($package, $isCurrentPlan, $boolFeatures, $featureLabels, $hideFromCards, $currency) {
                    return compact('package', 'isCurrentPlan', 'boolFeatures', 'featureLabels', 'hideFromCards', 'currency');
                };
            @endphp

            {{-- Monthly Packages Grid --}}
            <div id="billing-monthly-grid" class="mt-7">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl mx-auto">
                    @foreach($monthlyUserPackages as $package)
                    @php
                        $isCurrentPlan = isset($currentPlan) && $currentPlan && $currentPlan['package']->id === $package->id;
                        $borderClass   = $isCurrentPlan ? 'border-green-500 !border-2' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                    @endphp
                    <div class="rounded-2xl p-6 flex flex-col relative bg-white {{ $package->is_popular && !$isCurrentPlan ? 'border-2 border-[#F97316] shadow-[0_16px_34px_-18px_rgba(249,115,22,.6)]' : 'border ' . $borderClass . ' hover:border-orange-300 transition-colors' }}">
                        @if($isCurrentPlan)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-3 py-0.5 rounded-full text-[11.5px] font-semibold whitespace-nowrap">
                                Your Active Plan
                            </div>
                        @elseif($package->is_popular)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#F97316] text-white px-3 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide whitespace-nowrap">
                                Most Popular
                            </div>
                        @endif

                        <div class="text-center mb-5">
                            <h3 class="text-[17px] font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="text-[13px] text-gray-500 mb-2">{{ $package->description }}</p>
                            @endif
                            <div class="text-[32px] leading-tight font-extrabold tracking-tight text-[#111111]">
                                @if($package->price == 0)
                                    {{ $currency['symbol'] }}0<span class="text-[14px] font-medium text-gray-500">/month</span>
                                @else
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}<span class="text-[14px] font-medium text-gray-500">/month</span>
                                @endif
                            </div>
                        </div>

                        @include('partials.pricing-features', compact('package', 'boolFeatures', 'featureLabels', 'hideFromCards') + ['lightCard' => true])

                        @if($isCurrentPlan)
                            <button class="w-full bg-gray-100 text-gray-500 py-2.5 rounded-full text-[14px] font-semibold cursor-not-allowed">Current Plan</button>
                        @elseif($package->price == 0)
                            <a href="{{ route('auth.show') }}#signup" class="block w-full text-center {{ $package->is_popular ? 'bg-[#F97316] text-white hover:bg-[#EA580C] border-[1.5px] border-[#F97316]' : 'bg-white border-[1.5px] border-[#F97316] text-[#EA580C] hover:bg-[#F97316] hover:text-white' }} py-2.5 rounded-full text-[14px] font-semibold transition-colors">
                                Start Free Trial
                            </a>
                        @else
                            <button onclick="handleGetStarted(this)"
                                    data-package-id="{{ $package->id }}"
                                    data-package-name="{{ e($package->name) }}"
                                    data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                    data-currency-symbol="{{ $currency['symbol'] }}"
                                    class="w-full {{ $package->is_popular ? 'bg-[#F97316] text-white hover:bg-[#EA580C] border-[1.5px] border-[#F97316]' : 'bg-white border-[1.5px] border-[#F97316] text-[#EA580C] hover:bg-[#F97316] hover:text-white' }} py-2.5 rounded-full text-[14px] font-semibold transition-colors">
                                Get {{ $package->name }}{{ \Illuminate\Support\Str::endsWith(strtolower($package->name), 'plan') ? '' : ' Plan' }}
                            </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Yearly Packages Grid --}}
            <div id="billing-yearly-grid" class="mt-7 hidden">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl mx-auto">
                    @foreach($yearlyUserPackages as $package)
                    @php
                        $isCurrentPlan = isset($currentPlan) && $currentPlan && $currentPlan['package']->id === $package->id;
                        $borderClass   = $isCurrentPlan ? 'border-green-500 !border-2' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                    @endphp
                    <div class="rounded-2xl p-6 flex flex-col relative bg-white {{ $package->is_popular && !$isCurrentPlan ? 'border-2 border-[#F97316] shadow-[0_16px_34px_-18px_rgba(249,115,22,.6)]' : 'border ' . $borderClass . ' hover:border-orange-300 transition-colors' }}">
                        @if($isCurrentPlan)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-3 py-0.5 rounded-full text-[11.5px] font-semibold whitespace-nowrap">
                                Your Active Plan
                            </div>
                        @elseif($package->is_popular)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#F97316] text-white px-3 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide whitespace-nowrap">
                                Most Popular
                            </div>
                        @endif

                        <div class="text-center mb-5">
                            <h3 class="text-[17px] font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="text-[13px] text-gray-500 mb-2">{{ $package->description }}</p>
                            @endif
                            <div class="text-[32px] leading-tight font-extrabold tracking-tight text-[#111111]">
                                @if($package->price == 0)
                                    {{ $currency['symbol'] }}0<span class="text-[14px] font-medium text-gray-500">/month</span>
                                @else
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price / 12, $currency), 0) }}<span class="text-[14px] font-medium text-gray-500">/mo</span>
                                @endif
                            </div>
                            @if($package->billing_type === 'yearly' && $package->price > 0)
                                <div class="text-[12.5px] text-gray-500 mt-0.5">
                                    billed {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}/year
                                </div>
                            @endif
                        </div>

                        @include('partials.pricing-features', compact('package', 'boolFeatures', 'featureLabels', 'hideFromCards') + ['lightCard' => true])

                        @if($isCurrentPlan)
                            <button class="w-full bg-gray-100 text-gray-500 py-2.5 rounded-full text-[14px] font-semibold cursor-not-allowed">Current Plan</button>
                        @elseif($package->price == 0)
                            <a href="{{ route('auth.show') }}#signup" class="block w-full text-center {{ $package->is_popular ? 'bg-[#F97316] text-white hover:bg-[#EA580C] border-[1.5px] border-[#F97316]' : 'bg-white border-[1.5px] border-[#F97316] text-[#EA580C] hover:bg-[#F97316] hover:text-white' }} py-2.5 rounded-full text-[14px] font-semibold transition-colors">
                                Start Free Trial
                            </a>
                        @else
                            <button onclick="handleGetStarted(this)"
                                    data-package-id="{{ $package->id }}"
                                    data-package-name="{{ e($package->name) }}"
                                    data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                    data-currency-symbol="{{ $currency['symbol'] }}"
                                    class="w-full {{ $package->is_popular ? 'bg-[#F97316] text-white hover:bg-[#EA580C] border-[1.5px] border-[#F97316]' : 'bg-white border-[1.5px] border-[#F97316] text-[#EA580C] hover:bg-[#F97316] hover:text-white' }} py-2.5 rounded-full text-[14px] font-semibold transition-colors">
                                Get {{ $package->name }}{{ \Illuminate\Support\Str::endsWith(strtolower($package->name), 'plan') ? '' : ' Plan' }}
                            </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Lead Intelligence Comparison -->
            @if($userPackages->count() > 0)
            <div class="mt-12">
                <div class="text-center mb-5">
                    <p class="text-xs font-bold text-primary-orange uppercase tracking-widest mb-3">What's Included</p>
                    <h3 class="text-[22px] font-bold text-gray-900 mb-1.5">Everything Included by Plan</h3>
                    <p class="text-gray-500 max-w-xl mx-auto">See what features are included in each plan and choose the best option for your business.</p>
                </div>

                <div class="max-w-5xl mx-auto overflow-x-auto hp-card">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-2/5">Feature</th>
                                @foreach($monthlyUserPackages as $package)
                                    <th class="text-center py-2.5 px-3 w-1/5">
                                        <span class="text-xs font-bold {{ $package->is_popular ? 'text-primary-orange' : 'text-gray-900' }}">{{ $package->name }}</span>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            @if($package->price == 0)
                                                Free
                                            @else
                                                {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}/mo
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $intelligenceLabels = \App\Support\PlanFeatureCatalog::comparisonRows();
                            @endphp
                            @foreach($intelligenceLabels as $featureKey => $meta)
                                @if($meta['type'] === 'group')
                                    <tr class="bg-gray-50"><td colspan="{{ 1 + $monthlyUserPackages->count() }}" class="py-2 px-3 text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $meta['name'] }}</td></tr>
                                    @continue
                                @endif
                                <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                    <td class="py-2 px-3">
                                        <div class="font-medium text-gray-900 text-xs">{{ $meta['name'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $meta['desc'] }}</div>
                                    </td>
                                    @foreach($monthlyUserPackages as $package)
                                        @php
                                            $val = \App\Support\PlanFeatureCatalog::legacyValue($package, $featureKey);
                                        @endphp
                                        <td class="text-center py-2 px-3">
                                            @if(\App\Support\PlanFeatureCatalog::isLocked($package, $featureKey))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-50 border border-orange-200 text-orange-700 text-[11px] font-semibold whitespace-nowrap" title="Coming soon"><i class="far fa-clock text-[9px]" aria-hidden="true"></i>Soon</span>
                                            @elseif($meta['type'] === 'bool')
                                                @if($val === 'true')
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-100" aria-label="Included">
                                                        <svg class="w-3 h-3 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="inline-block text-gray-300 font-bold" aria-label="Not included">&mdash;</span>
                                                @endif
                                            @else
                                                @if(!$val || $val === 'false')
                                                    <span class="inline-block text-gray-300 font-bold" aria-label="Not included">&mdash;</span>
                                                @elseif($val === 'unlimited')
                                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs font-semibold">
                                                        Unlimited
                                                    </span>
                                                @else
                                                    <span class="text-xs font-semibold text-gray-800">
                                                        @if($featureKey === 'max_devices')
                                                            {{ $val }} {{ (int)$val === 1 ? 'Device' : 'Devices' }}
                                                        @else
                                                            {{ number_format((int)$val) }}{{ $meta['suffix'] }}
                                                        @endif
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </section>

    <!-- FAQ -->
    @include('partials.home.faq')

    <!-- Final CTA -->
    @include('partials.home.final-cta')

    {{-- Payment Modal --}}
    @include('partials.payment-modal')

    <!-- Footer -->
    @include('partials.home.footer')

    <script>
        // FAQ Toggle Function
        function toggleFAQ(button) {
            var faqItem = button.closest('.faq-item');
            var content = button.nextElementSibling;
            var icon = button.querySelector('svg');
            var isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

            // Close all other FAQ items
            document.querySelectorAll('.faq-item').forEach(function(item) {
                if (item !== faqItem) {
                    var c = item.querySelector('.faq-content');
                    var i = item.querySelector('.faq-icon svg');
                    if (c) { c.style.maxHeight = '0px'; }
                    var b = item.querySelector('button'); if (b) b.setAttribute('aria-expanded', 'false');
                    if (i) { i.style.transform = 'rotate(0deg)'; }
                    item.classList.remove('border-orange-300', 'shadow-sm');
                }
            });

            // Toggle current item
            if (isOpen) {
                content.style.maxHeight = '0px';
                button.setAttribute('aria-expanded', 'false');
                icon.style.transform = 'rotate(0deg)';
                faqItem.classList.remove('border-orange-300', 'shadow-sm');
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                button.setAttribute('aria-expanded', 'true');
                icon.style.transform = 'rotate(180deg)';
                faqItem.classList.add('border-orange-300', 'shadow-sm');
            }
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('nav');
            if (window.scrollY > 100) {
                navbar.classList.add('shadow-lg');
            } else {
                navbar.classList.remove('shadow-lg');
            }
        });

        // Pricing: Individual/Company toggle
        (function() {
            var currentTab = 'user';

            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    currentTab = this.dataset.tab;
                    document.querySelectorAll('.tab-btn').forEach(function(b) {
                        var active = b.dataset.tab === currentTab;
                        b.classList.toggle('bg-primary-orange', active);
                        b.classList.toggle('text-white', active);
                        b.classList.toggle('text-gray-600', !active);
                    });
                    document.querySelectorAll('.pricing-grid').forEach(function(grid) {
                        grid.classList.toggle('hidden', grid.dataset.tab !== currentTab);
                    });
                });
            });
        })();

        // Pricing: Monthly/Yearly billing toggle
        function switchBilling(type) {
            var monthlyGrid  = document.getElementById('billing-monthly-grid');
            var yearlyGrid   = document.getElementById('billing-yearly-grid');
            var monthlyBtn   = document.getElementById('billing-monthly-btn');
            var yearlyBtn    = document.getElementById('billing-yearly-btn');

            var isMonthly = type === 'monthly';
            monthlyGrid.classList.toggle('hidden', !isMonthly);
            yearlyGrid.classList.toggle('hidden', isMonthly);

            monthlyBtn.classList.toggle('bg-primary-orange', isMonthly);
            monthlyBtn.classList.toggle('text-white', isMonthly);
            monthlyBtn.classList.toggle('text-gray-600', !isMonthly);

            yearlyBtn.classList.toggle('bg-primary-orange', !isMonthly);
            yearlyBtn.classList.toggle('text-white', !isMonthly);
            yearlyBtn.classList.toggle('text-gray-600', isMonthly);
        }
    </script>

    <script>
        // Referral link tracking — URL se ?ref= pado aur localStorage mein save karo
        (function() {
            const ref = new URLSearchParams(window.location.search).get('ref');
            if (ref) {
                localStorage.setItem('ref_code', ref);
                localStorage.setItem('ref_code_ts', Date.now().toString());
            }
        })();
    </script>
</body>
</html>