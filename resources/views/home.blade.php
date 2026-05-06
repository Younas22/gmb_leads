<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- ============ CRITICAL FIX: Remove noindex ============ -->
    <meta name="robots" content="index, follow" />
    
    <!-- ============ META DESCRIPTIONS & KEYWORDS ============ -->
    <meta name="description" content="CustomerNearme - Find verified Google Maps business leads instantly for direct client hunting, cold email campaigns, and lead generation. Real-time data directly from Google Maps. No fake scraping." />
    <meta name="keywords" content="google maps lead generation, direct client hunting, gmb business data, cold email leads, business finder tool, verified business contacts, find business leads online" />
    <meta name="author" content="CustomerNearme" />
    <meta name="publisher" content="CustomerNearme" />
    <meta name="theme-color" content="#f97316" />
    
    <!-- ============ CANONICAL URL ============ -->
    <link rel="canonical" href="https://www.customernearme.com/" />
    
    <!-- ============ OPEN GRAPH TAGS ============ -->
    <meta property="og:title" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} - Find Real Google Maps Business Leads for Direct Client Hunting" />
    <meta property="og:description" content="Find verified business leads directly from Google Maps for cold email, sales outreach, and direct client hunting. Real-time data, no fake scraping." />
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

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
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
    
    <!-- 3. FAQPAGE SCHEMA -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "FAQPage",
      "mainEntity": [
        {
          "@@type": "Question",
          "name": "How fresh and up-to-date is the data in CustomerNearme?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "The data is pulled in real-time from Google Maps every time you run a search. Unlike static lead databases that go stale within weeks, our results reflect the current state of Google Maps at the moment of your query. This ensures you never waste time on closed or outdated listings when doing direct client hunting."
          }
        },
        {
          "@@type": "Question",
          "name": "What business information do I get for each lead?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Each lead includes business name, verified phone number, full address, Google Maps link, website URL, social media links, star rating, total review count, and business category. This gives you everything you need to qualify and reach out to prospects without additional research."
          }
        },
        {
          "@@type": "Question",
          "name": "Can I export leads from CustomerNearme?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Yes, you can export your saved leads in CSV and Excel formats with a single click. The files are clean and ready to import directly into your CRM, email marketing platform, or cold email tools."
          }
        },
        {
          "@@type": "Question",
          "name": "Are my saved leads stored permanently?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Yes. Every lead you save is permanently stored in your account. You can revisit, filter, tag, and export them whenever you need — your lead data is always there for you."
          }
        },
        {
          "@@type": "Question",
          "name": "Who should use CustomerNearme?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "CustomerNearme is built for anyone who needs reliable business leads. This includes digital marketers, SEO agencies, freelancers, cold emailers, sales teams, real estate agents, and local service providers."
          }
        }
      ]
    }
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
</head>
</head>
<body class="bg-white font-inter">
    <!-- Navigation -->
    <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100/80 transition-all duration-300" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-[72px]">

                <!-- Logo -->
                <a href="https://www.customernearme.com" class="flex items-center gap-2.5 flex-shrink-0 group" aria-label="CustomerNearme Home">
                    @php
                        $siteLogo = \App\Models\Setting::get('site_logo');
                        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
                    @endphp
                    @if($siteLogo)
                        <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-orange to-orange-600 flex items-center justify-center shadow-md shadow-orange-200/50">
                                <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-[1.15rem] font-bold text-gray-900 tracking-tight">Customer<span class="text-primary-orange">Nearme</span></span>
                        </div>
                    @endif
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden lg:flex items-center gap-1">
                    <a href="#sample-data" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        Leads Sample
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="#how-it-works" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        How It Works
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="#pricing" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        Pricing
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="#faq" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        FAQ
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-4 py-2 text-[0.9rem] font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-all duration-200">
                        <i class="fab fa-whatsapp text-base"></i>
                        Community
                    </a>
                </div>

                <!-- Desktop Right Side Actions -->
                <div class="hidden lg:flex items-center gap-3">
                    @guest
                        <a href="{{ route('auth.show') }}" class="px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200">
                            Login
                        </a>
                        <a href="{{ route('auth.show') }}" class="group relative inline-flex items-center gap-2 px-5 py-2.5 text-[0.9rem] font-semibold text-white bg-primary-orange rounded-full shadow-lg shadow-orange-200/50 hover:shadow-orange-300/60 hover:bg-dark-orange transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary-orange focus:ring-offset-2">
                            Start Free Trial
                            <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-[0.9rem] font-semibold text-white bg-primary-orange rounded-full shadow-lg shadow-orange-200/50 hover:shadow-orange-300/60 hover:bg-dark-orange transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary-orange focus:ring-offset-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Dashboard
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" type="button" class="lg:hidden relative w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-orange/50" aria-expanded="false" aria-controls="mobile-menu" aria-label="Toggle navigation menu">
                    <span class="sr-only">Open menu</span>
                    <!-- Hamburger Icon -->
                    <div class="w-5 h-4 flex flex-col justify-between" id="hamburger-icon">
                        <span class="block h-0.5 w-5 bg-gray-700 rounded-full transition-all duration-300 origin-center" id="bar-1"></span>
                        <span class="block h-0.5 w-3.5 bg-gray-700 rounded-full transition-all duration-300 ml-auto" id="bar-2"></span>
                        <span class="block h-0.5 w-5 bg-gray-700 rounded-full transition-all duration-300 origin-center" id="bar-3"></span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div id="mobile-menu" class="lg:hidden hidden" role="menu">
            <div class="bg-white border-t border-gray-100 shadow-xl shadow-gray-200/20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-1">
                    <a href="#sample-data" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        Leads Sample
                    </a>
                    <a href="#how-it-works" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
                        How It Works
                    </a>
                    <a href="#pricing" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                        Pricing
                    </a>
                    <a href="#faq" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                        FAQ
                    </a>

                    <!-- Mobile Divider -->
                    <div class="my-3 border-t border-gray-100"></div>

                    <!-- Mobile Action Buttons -->
                    @guest
                        <a href="{{ route('auth.show') }}" class="flex items-center justify-center gap-2 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                            Login
                        </a>
                        <a href="{{ route('auth.show') }}" class="flex items-center justify-center gap-2 px-4 py-3.5 text-[0.95rem] font-semibold text-white bg-primary-orange hover:bg-dark-orange rounded-xl shadow-lg shadow-orange-200/40 transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                            Start Free Trial
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="flex items-center justify-center gap-2 px-4 py-3.5 text-[0.95rem] font-semibold text-white bg-primary-orange hover:bg-dark-orange rounded-xl shadow-lg shadow-orange-200/40 transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

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

    @include('components.modern-hero')
    <!-- Hero Section -->

    <!-- Sample Sheet Data Section -->
    <section class="py-20 bg-white" id="sample-data">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-10">
                <p class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-3">Real Output Preview</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">See What Data You Get</h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">This is actual sample data exported from our Chrome Extension — the exact format you receive as an Excel file.</p>
            </div>

            <!-- Sheet Table -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <!-- Table Toolbar -->
                <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        <span class="ml-3 text-sm font-medium text-gray-600">
                            <i class="fas fa-file-excel text-green-600 mr-1"></i> sheet.xlsx
                        </span>
                    </div>
                    <a href="{{ asset('public/sheet/sheet.xlsx') }}" download
                       class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors">
                        <i class="fas fa-download mr-1.5"></i> Download Sample
                    </a>
                </div>

                <!-- Loading State -->
                <div id="sheetLoading" class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-3xl text-orange-400 mb-3"></i>
                        <p class="text-gray-500 text-sm">Loading sheet data...</p>
                    </div>
                </div>

                <!-- Table Container -->
                <div id="sheetTableWrap" class="hidden overflow-x-auto" style="max-height: 420px; overflow-y: auto;">
                    <table id="sheetTable" class="w-full text-sm border-collapse"></table>
                </div>

                <!-- Error State -->
                <div id="sheetError" class="hidden flex items-center justify-center py-16">
                    <div class="text-center">
                        <i class="fas fa-exclamation-circle text-3xl text-red-400 mb-3"></i>
                        <p class="text-gray-500 text-sm">Could not load sheet. Please try downloading it.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        #sheetTable thead tr { background: #f97316; color: white; position: sticky; top: 0; z-index: 1; }
        #sheetTable thead th { padding: 10px 14px; text-align: left; font-weight: 600; font-size: 12px; white-space: nowrap; border-right: 1px solid rgba(255,255,255,0.2); }
        #sheetTable tbody tr:nth-child(even) { background: #fafafa; }
        #sheetTable tbody tr:hover { background: #fff7ed; }
        #sheetTable tbody td { padding: 9px 14px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0; white-space: nowrap; color: #374151; }
    </style>

    <script>
    (function() {
        const headers = [
            'Search Query','Company Name','Category','Phone Number','Email','Website',
            'Address','City','State','Country','Rating','Total Reviews',
            'Latest Review Date','GMB Profile URL','Facebook','Instagram','Twitter',
            'LinkedIn','YouTube','Pinterest','Contact Status','Notes','Date Added'
        ];

        const rows = [
            ['pizza shop in New York','Joe\'s Pizza','Pizza Restaurant','+1 212-366-1182','contact@joespizzanyc.com','https://joespizzanyc.com','7 Carmine St, New York, NY 10014','New York','New York','United States','4.7','6842','2026-04-10','https://maps.google.com/?cid=1234567890','https://facebook.com/joespizzanyc','https://instagram.com/joespizzanyc','https://twitter.com/joespizzanyc','https://linkedin.com/company/joespizzanyc','https://youtube.com/@joespizzanyc','https://pinterest.com/joespizzanyc','Not contacted','Top-rated pizza spot in Manhattan','2026-04-11'],
            ['dentist in Los Angeles','Bright Smile Dental','Dental Clinic','+1 310-555-0192','info@brightsmileLA.com','https://brightsmileLA.com','450 N Roxbury Dr, Beverly Hills, CA 90210','Los Angeles','California','United States','4.9','3210','2026-04-09','https://maps.google.com/?cid=2345678901','https://facebook.com/brightsmileLA','https://instagram.com/brightsmileLA','https://twitter.com/brightsmileLA','https://linkedin.com/company/brightsmileLA','https://youtube.com/@brightsmileLA','https://pinterest.com/brightsmileLA','Not contacted','Accepts new patients, open weekends','2026-04-11'],
            ['plumber in Chicago','QuickFix Plumbing','Plumber','+1 773-555-0384','hello@quickfixplumbing.com','https://quickfixplumbing.com','1820 N Clark St, Chicago, IL 60614','Chicago','Illinois','United States','4.6','1875','2026-04-08','https://maps.google.com/?cid=3456789012','https://facebook.com/quickfixplumbing','https://instagram.com/quickfixplumbing','https://twitter.com/quickfixplumbing','https://linkedin.com/company/quickfixplumbing','https://youtube.com/@quickfixplumbing','https://pinterest.com/quickfixplumbing','Not contacted','24/7 emergency service available','2026-04-11'],
            ['gym in Houston','Iron Peak Fitness','Gym & Fitness Center','+1 713-555-0267','members@ironpeakfitness.com','https://ironpeakfitness.com','3900 Westheimer Rd, Houston, TX 77027','Houston','Texas','United States','4.8','4520','2026-04-07','https://maps.google.com/?cid=4567890123','https://facebook.com/ironpeakfitness','https://instagram.com/ironpeakfitness','https://twitter.com/ironpeakfitness','https://linkedin.com/company/ironpeakfitness','https://youtube.com/@ironpeakfitness','https://pinterest.com/ironpeakfitness','Not contacted','Offers free trial membership','2026-04-11'],
            ['coffee shop in Seattle','Bean & Brew Coffee','Coffee Shop','+1 206-555-0148','hello@beanandbrew.com','https://beanandbrew.com','1912 Pike Pl, Seattle, WA 98101','Seattle','Washington','United States','4.8','2993','2026-04-06','https://maps.google.com/?cid=5678901234','https://facebook.com/beanandbrew','https://instagram.com/beanandbrew','https://twitter.com/beanandbrew','https://linkedin.com/company/beanandbrew','https://youtube.com/@beanandbrew','https://pinterest.com/beanandbrew','Not contacted','Near Pike Place Market, high foot traffic','2026-04-11'],
            ['electrician in Phoenix','Volt Pro Electric','Electrician','+1 602-555-0391','service@voltproelectric.com','https://voltproelectric.com','2150 E Highland Ave, Phoenix, AZ 85016','Phoenix','Arizona','United States','4.7','987','2026-04-05','https://maps.google.com/?cid=6789012345','https://facebook.com/voltproelectric','https://instagram.com/voltproelectric','https://twitter.com/voltproelectric','https://linkedin.com/company/voltproelectric','https://youtube.com/@voltproelectric','https://pinterest.com/voltproelectric','Not contacted','Licensed & insured, same day service','2026-04-11'],
            ['restaurant in Miami','Seaside Grill Miami','Seafood Restaurant','+1 305-555-0275','reservations@seasidegrillmiami.com','https://seasidegrillmiami.com','800 Ocean Dr, Miami Beach, FL 33139','Miami','Florida','United States','4.6','5631','2026-04-04','https://maps.google.com/?cid=7890123456','https://facebook.com/seasidegrillmiami','https://instagram.com/seasidegrillmiami','https://twitter.com/seasidegrillmiami','https://linkedin.com/company/seasidegrillmiami','https://youtube.com/@seasidegrillmiami','https://pinterest.com/seasidegrillmiami','Not contacted','Oceanfront dining, live music on weekends','2026-04-11'],
            ['lawyer in Dallas','Justice Law Group','Law Firm','+1 214-555-0463','contact@justicelawgroup.com','https://justicelawgroup.com','1700 Pacific Ave Suite 2400, Dallas, TX 75201','Dallas','Texas','United States','4.9','1204','2026-04-03','https://maps.google.com/?cid=8901234567','https://facebook.com/justicelawgroup','https://instagram.com/justicelawgroup','https://twitter.com/justicelawgroup','https://linkedin.com/company/justicelawgroup','https://youtube.com/@justicelawgroup','https://pinterest.com/justicelawgroup','Not contacted','Specializes in personal injury & business law','2026-04-11'],
            ['hair salon in Atlanta','Glam Studio ATL','Hair Salon','+1 404-555-0319','book@glamstudioatl.com','https://glamstudioatl.com','675 Ponce De Leon Ave NE, Atlanta, GA 30308','Atlanta','Georgia','United States','4.8','2788','2026-04-02','https://maps.google.com/?cid=9012345678','https://facebook.com/glamstudioatl','https://instagram.com/glamstudioatl','https://twitter.com/glamstudioatl','https://linkedin.com/company/glamstudioatl','https://youtube.com/@glamstudioatl','https://pinterest.com/glamstudioatl','Not contacted','Walk-ins welcome, bridal packages available','2026-04-11'],
            ['mechanic in Denver','AutoCare Denver','Auto Repair Shop','+1 720-555-0582','repairs@autocaredenver.com','https://autocaredenver.com','2550 W Colfax Ave, Denver, CO 80204','Denver','Colorado','United States','4.7','1649','2026-04-01','https://maps.google.com/?cid=0123456789','https://facebook.com/autocaredenver','https://instagram.com/autocaredenver','https://twitter.com/autocaredenver','https://linkedin.com/company/autocaredenver','https://youtube.com/@autocaredenver','https://pinterest.com/autocaredenver','Not contacted','ASE-certified technicians, free diagnostics','2026-04-11'],
        ];

        const table = document.getElementById('sheetTable');
        const thead = document.createElement('thead');
        const hRow = document.createElement('tr');
        headers.forEach(h => {
            const th = document.createElement('th');
            th.textContent = h;
            hRow.appendChild(th);
        });
        thead.appendChild(hRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        rows.forEach(row => {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        document.getElementById('sheetLoading').classList.add('hidden');
        document.getElementById('sheetTableWrap').classList.remove('hidden');
    })();
    </script>

    <!-- Who Is This For -->
    <section class="py-12 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <div class="text-center mb-8">
                <p class="text-xs font-bold text-primary-orange uppercase tracking-widest mb-2">Target Audience</p>
                <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">Built for People Who Need Clients</h2>
                <p class="text-base text-gray-500 max-w-2xl mx-auto">{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} is perfect for:</p>
            </div>

            <!-- Audience Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">

    <!-- Digital Marketers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Digital Marketers</h3>
    </div>

    <!-- SEO Agencies -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">SEO Agencies</h3>
    </div>

    <!-- Freelancers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Freelancers</h3>
    </div>

    <!-- Cold Emailers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Cold Emailers</h3>
    </div>

    <!-- Sales Teams -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Sales Teams</h3>
    </div>

    <!-- Real Estate & Insurance -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Real Estate & Insurance</h3>
    </div>

    <!-- Local Service Providers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Local Service Providers</h3>
    </div>

    <!-- Startups & Entrepreneurs -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Startups & Entrepreneurs</h3>
    </div>

    <!-- Marketing Consultants / Agencies -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v18h14V3H5z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6v2H9V7zM9 11h6v2H9v-2z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Marketing Consultants</h3>
    </div>

    <!-- Social Media Managers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12c0 4.98 3.65 9.12 8.44 9.88v-6.99H7.9v-2.89h2.54V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.55v1.88h2.78l-.44 2.89h-2.34v6.99C18.35 21.12 22 16.98 22 12c0-5.52-4.48-10-10-10z"/>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Social Media Managers</h3>
    </div>

    <!-- Content Creators / Copywriters -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 19h16M4 5h16M4 12h16"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Content Creators</h3>
    </div>

    <!-- Web Developers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4h16v16H4z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Web Developers</h3>
    </div>

    <!-- Mobile App Developers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4h10v16H7z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Mobile App Developers</h3>
    </div>

    <!-- Graphic Designers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2l9 7-9 7-9-7 9-7z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Graphic Designers</h3>
    </div>

    <!-- Email Marketing Specialists -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l9 6 9-6v10H3V8z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Email Marketing Specialists</h3>
    </div>

    <!-- Small Business Owners -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18v18H3V3z"></path>
            </svg>
        </div>
        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Small Business Owners</h3>
    </div>

</div>


            <!-- Closing Line -->
            <div class="text-center">
                <p class="text-lg sm:text-xl font-bold text-gray-900">
                    If your income depends on clients — <span class="text-primary-orange">this tool is for you.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <div class="text-center mb-16">
                <p class="text-sm font-bold text-primary-orange uppercase tracking-widest mb-3">Simple 4-Step Process</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">
                    How {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} Finds Clients for You
                </h2>
            </div>

            <!-- Steps Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 relative">

                <!-- Connector line (desktop only) -->
                <div class="hidden lg:block absolute top-10 left-[12.5%] right-[12.5%] h-0.5 bg-gradient-to-r from-primary-orange via-primary-blue to-primary-orange opacity-20"></div>

                <!-- Step 1 — Add Extension -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-orange-50 border-2 border-primary-orange rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-orange text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 1</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Add Extension</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Install the Chrome Extension to start scraping Google Maps businesses.</p>
                </div>

                <!-- Step 2 — Find Leads -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-blue-50 border-2 border-primary-blue rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-blue text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 2</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Find Leads</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Search businesses by keyword & location using our Chrome Extension.</p>
                </div>

                <!-- Step 3 — Save & Organize -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-orange-50 border-2 border-primary-orange rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-orange text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 3</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Save & Organize</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Save leads with contact details, ratings & business info to your list.</p>
                </div>

                <!-- Step 4 — Export & Contact -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-blue-50 border-2 border-primary-blue rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-blue text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 4</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Export & Contact</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Download leads & track status — Contacted, Responded, Converted.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Data Trust Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="text-sm font-bold text-green-600 uppercase tracking-widest mb-3">Data You Can Trust</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">100% Real & Reliable Data</h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-5 max-w-2xl mx-auto">
                <!-- Trust Item 1 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Data directly from Google Maps</span>
                </div>

                <!-- Trust Item 2 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">No fake scraping</span>
                </div>

                <!-- Trust Item 3 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Active businesses only</span>
                </div>

                <!-- Trust Item 4 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Saved permanently in your account</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="text-xs font-bold text-primary-orange uppercase tracking-widest mb-3">Pricing</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Simple, Transparent Pricing</h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">Start free. Upgrade when you need deeper lead intelligence and more exports.</p>
            </div>

            <!-- Individual / Company Tabs (only show if both types have packages) -->
            @if($companyPackages->count() > 0)
            <div class="flex justify-center mb-14">
                <div class="inline-flex bg-gray-100 rounded-full p-1">
                    <button class="tab-btn bg-primary-blue text-white px-6 py-2 rounded-full text-sm font-semibold transition-colors cursor-pointer" data-tab="user">Individual</button>
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
                <div class="inline-flex items-center bg-gray-100 rounded-full p-1 gap-1">
                    <button id="billing-monthly-btn"
                            onclick="switchBilling('monthly')"
                            class="billing-toggle-btn px-5 py-2 rounded-full text-sm font-semibold transition-colors bg-primary-blue text-white cursor-pointer">
                        Monthly
                    </button>
                    <button id="billing-yearly-btn"
                            onclick="switchBilling('yearly')"
                            class="billing-toggle-btn px-5 py-2 rounded-full text-sm font-semibold transition-colors text-gray-600 hover:text-gray-900 cursor-pointer flex items-center gap-2">
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
            <div id="billing-monthly-grid" class="mt-10">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    @foreach($monthlyUserPackages as $package)
                    @php
                        $isCurrentPlan = isset($currentPlan) && $currentPlan && $currentPlan['package']->id === $package->id;
                        $borderClass   = $isCurrentPlan ? 'border-green-500 border-4' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                    @endphp
                    <div class="rounded-xl p-8 flex flex-col relative {{ $package->is_popular ? 'bg-primary-blue text-white transform scale-105' : 'bg-white border-2 ' . $borderClass . ' hover:border-primary-blue transition-colors' }}">
                        @if($isCurrentPlan)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Your Active Plan
                            </div>
                        @elseif($package->is_popular)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-primary-orange text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Popular
                            </div>
                        @endif

                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="text-sm {{ $package->is_popular ? 'opacity-75' : 'text-gray-500' }} mb-3">{{ $package->description }}</p>
                            @endif
                            <div class="text-4xl font-bold {{ $package->is_popular ? '' : 'text-gray-900' }}">
                                @if($package->price == 0)
                                    {{ $currency['symbol'] }}0<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @else
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @endif
                            </div>
                        </div>

                        @include('partials.pricing-features', compact('package', 'boolFeatures', 'featureLabels', 'hideFromCards'))

                        @if($isCurrentPlan)
                            <button class="w-full bg-gray-200 text-gray-500 py-3 rounded-lg font-semibold cursor-not-allowed">Current Plan</button>
                        @elseif($package->price == 0)
                            <a href="{{ route('auth.show') }}" class="block w-full text-center {{ $package->is_popular ? 'bg-white text-primary-blue hover:bg-gray-100' : 'border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                Start Free
                            </a>
                        @else
                            <button onclick="handleGetStarted(this)"
                                    data-package-id="{{ $package->id }}"
                                    data-package-name="{{ e($package->name) }}"
                                    data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                    data-currency-symbol="{{ $currency['symbol'] }}"
                                    class="w-full {{ $package->is_popular ? 'bg-white text-primary-blue hover:bg-gray-100' : 'border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                Get Started
                            </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Yearly Packages Grid --}}
            <div id="billing-yearly-grid" class="mt-10 hidden">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    @foreach($yearlyUserPackages as $package)
                    @php
                        $isCurrentPlan = isset($currentPlan) && $currentPlan && $currentPlan['package']->id === $package->id;
                        $borderClass   = $isCurrentPlan ? 'border-green-500 border-4' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                    @endphp
                    <div class="rounded-xl p-8 flex flex-col relative {{ $package->is_popular ? 'bg-primary-blue text-white transform scale-105' : 'bg-white border-2 ' . $borderClass . ' hover:border-primary-blue transition-colors' }}">
                        @if($isCurrentPlan)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Your Active Plan
                            </div>
                        @elseif($package->is_popular)
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-primary-orange text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Popular
                            </div>
                        @endif

                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="text-sm {{ $package->is_popular ? 'opacity-75' : 'text-gray-500' }} mb-3">{{ $package->description }}</p>
                            @endif
                            <div class="text-4xl font-bold {{ $package->is_popular ? '' : 'text-gray-900' }}">
                                @if($package->price == 0)
                                    {{ $currency['symbol'] }}0<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @else
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price / 12, $currency), 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/mo</span>
                                @endif
                            </div>
                            @if($package->billing_type === 'yearly' && $package->price > 0)
                                <div class="text-sm {{ $package->is_popular ? 'opacity-70' : 'text-gray-500' }} mt-1">
                                    billed {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}/year
                                </div>
                            @endif
                        </div>

                        @include('partials.pricing-features', compact('package', 'boolFeatures', 'featureLabels', 'hideFromCards'))

                        @if($isCurrentPlan)
                            <button class="w-full bg-gray-200 text-gray-500 py-3 rounded-lg font-semibold cursor-not-allowed">Current Plan</button>
                        @elseif($package->price == 0)
                            <a href="{{ route('auth.show') }}" class="block w-full text-center {{ $package->is_popular ? 'bg-white text-primary-blue hover:bg-gray-100' : 'border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                Start Free
                            </a>
                        @else
                            <button onclick="handleGetStarted(this)"
                                    data-package-id="{{ $package->id }}"
                                    data-package-name="{{ e($package->name) }}"
                                    data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                    data-currency-symbol="{{ $currency['symbol'] }}"
                                    class="w-full {{ $package->is_popular ? 'bg-white text-primary-blue hover:bg-gray-100' : 'border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                Get Started
                            </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Lead Intelligence Comparison -->
            @if($userPackages->count() > 0)
            <div class="mt-20">
                <div class="text-center mb-10">
                    <p class="text-xs font-bold text-primary-orange uppercase tracking-widest mb-3">What's Included</p>
                    <h3 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">Lead Intelligence by Plan</h3>
                    <p class="text-gray-500 max-w-xl mx-auto">Each plan unlocks deeper business data. See exactly what intelligence you get at every tier.</p>
                </div>

                <div class="max-w-5xl mx-auto overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-2/5">Feature</th>
                                @foreach($monthlyUserPackages as $package)
                                    <th class="text-center py-2.5 px-3 w-1/5">
                                        <span class="text-xs font-bold {{ $package->is_popular ? 'text-primary-blue' : 'text-gray-900' }}">{{ $package->name }}</span>
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
                                $intelligenceLabels = [
                                    // Boolean features
                                    'unlimited_map_scraping'  => ['name' => 'Unlimited Map Scraping',   'desc' => 'Scrape as many Google Maps results as you need',   'type' => 'bool'],
                                    'basic_business_signals'  => ['name' => 'Basic Business Signals',   'desc' => 'Name, address, category, rating, total reviews',   'type' => 'bool'],
                                    'contact_ready_leads'     => ['name' => 'Contact-Ready Leads',      'desc' => 'Verified phone & website data included',           'type' => 'bool'],
                                    'email_scraping'          => ['name' => 'Email Scraping',           'desc' => 'Extract emails from business websites',            'type' => 'bool'],
                                    'social_media_scraping'   => ['name' => 'Social Media Scraping',    'desc' => 'Facebook, Instagram, Twitter & more',              'type' => 'bool'],
                                    'website_extraction'      => ['name' => 'Website Extraction',       'desc' => 'Pull full website URL from listings',              'type' => 'bool'],
                                    'latest_review_insights'  => ['name' => 'Latest Review Insights',  'desc' => 'Recent review text & sentiment signals',           'type' => 'bool'],
                                    'advanced_review_filters' => ['name' => 'Advanced Review Filters', 'desc' => 'Filter by rating, recency & keywords',             'type' => 'bool'],
                                    // Value-based features
                                    'daily_leads_limit'       => ['name' => 'Daily Leads Limit',        'desc' => 'Max leads you can collect per day',                'type' => 'value', 'suffix' => '/day'],
                                    'export_leads'            => ['name' => 'Export Leads',             'desc' => 'Download leads as CSV / Excel',                    'type' => 'value', 'suffix' => ''],
                                    'max_devices'             => ['name' => 'Devices Access',           'desc' => 'Number of devices that can access your account',   'type' => 'value', 'suffix' => ''],
                                    'priority_support'        => ['name' => 'Priority Support',         'desc' => 'Fast-track support response',                      'type' => 'bool'],
                                ];
                            @endphp
                            @foreach($intelligenceLabels as $featureKey => $meta)
                                <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                    <td class="py-2 px-3">
                                        <div class="font-medium text-gray-900 text-xs">{{ $meta['name'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $meta['desc'] }}</div>
                                    </td>
                                    @foreach($monthlyUserPackages as $package)
                                        @php
                                            $feat = $package->features->firstWhere('feature_key', $featureKey);
                                            $val  = $feat ? $feat->feature_value : null;
                                        @endphp
                                        <td class="text-center py-2 px-3">
                                            @if($meta['type'] === 'bool')
                                                @if($val === 'true')
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100">
                                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100">
                                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </span>
                                                @endif
                                            @else
                                                @if(!$val || $val === 'false')
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100">
                                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </span>
                                                @elseif($val === 'unlimited')
                                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
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

    <!-- Social Proof -->
    <section class="py-20 bg-gray-50" style="display: none;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Trusted by Growing Businesses</h2>
                <div class="grid md:grid-cols-3 gap-8 mb-16">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-blue mb-2">50,000+</div>
                        <div class="text-gray-600">Businesses Found</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-orange mb-2">25,000+</div>
                        <div class="text-gray-600">Leads Generated</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-blue mb-2">1,200+</div>
                        <div class="text-gray-600">Happy Users</div>
                    </div>
                </div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-primary-blue rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">JS</span>
                        </div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">John Smith</div>
                            <div class="text-sm text-gray-600">Sales Director</div>
                        </div>
                    </div>
                    <p class="text-gray-700">"BusinessFinder helped us identify 500+ potential clients in our target market. The Google Maps data integration is incredibly accurate."</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-primary-orange rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">MJ</span>
                        </div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">Maria Johnson</div>
                            <div class="text-sm text-gray-600">Marketing Manager</div>
                        </div>
                    </div>
                    <p class="text-gray-700">"The export feature saved us hours of manual work. We can now focus on reaching out to qualified prospects instead of data collection."</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-primary-blue rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">DL</span>
                        </div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">David Lee</div>
                            <div class="text-sm text-gray-600">Business Owner</div>
                        </div>
                    </div>
                    <p class="text-gray-700">"Simple, fast, and reliable. BusinessFinder has become an essential tool for our lead generation process."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 bg-white" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-14">
                <p class="text-sm font-bold uppercase tracking-widest mb-3" style="color: rgb(249, 115, 22);">Support</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-lg text-gray-500 max-w-xl mx-auto">Everything you need to know about {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} before getting started.</p>
            </div>

            <!-- FAQ Accordion -->
            <div class="space-y-3" id="faq-accordion">


                <!-- FAQ 3 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">How fresh and up-to-date is the data?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            The data is pulled in <strong class="text-gray-800">real-time</strong> from Google every time you run a search. Unlike static lead databases that go stale within weeks, our results reflect the current state of Google Maps at the moment of your query. If a business is active on Google Maps right now, it will appear in your results. This ensures you never waste time on closed or outdated listings.
                        </div>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">What information do I get for each business?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Each lead includes the <strong class="text-gray-800">business name, phone number, full address, Google Maps link, website URL, social media links (Facebook, Instagram, Twitter, etc.), star rating, total review count, and business category</strong>. Depending on the business listing, you may also see operating hours and additional contact details. This gives you everything you need to qualify and reach out to prospects without any additional research.
                        </div>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Can I export my leads?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Yes. You can export your saved leads in <strong class="text-gray-800">CSV and Excel</strong> formats with a single click. Exported files are clean, organized, and ready to import directly into your CRM, email marketing platform, or outreach tools. Export functionality is available across all paid plans, making it easy to integrate {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} into your existing workflow.
                        </div>
                    </div>
                </div>

                <!-- FAQ 8 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Are my saved leads stored permanently?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Yes. Every lead you save is <strong class="text-gray-800">permanently stored</strong> in your account. Unlike tools that delete data after a session or limit your history, {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} keeps all your saved leads accessible at any time. You can revisit, filter, tag, and export them whenever you need — your data is always there for you.
                        </div>
                    </div>
                </div>

                <!-- FAQ 9 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Who is this tool designed for?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} is built for anyone who needs a reliable pipeline of business leads. This includes <strong class="text-gray-800">digital marketers, SEO agencies, freelancers, cold emailers, sales teams, real estate agents, insurance agents, and local service providers</strong>. If your income depends on reaching out to businesses, this tool will save you hours of manual prospecting and deliver higher-quality contacts.
                        </div>
                    </div>
                </div>

                <!-- FAQ 10 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">What are the different pricing tiers?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            We offer three plans: <strong class="text-gray-800">Free Trial ($0)</strong> — get started with 20 leads/day, 1 device access, and all core features except unlimited map scraping. <strong class="text-gray-800">Starter ($7.99/mo or $79.90/yr)</strong> — unlimited map scraping, unlimited daily leads, email scraping, social media & website extraction, unlimited exports, and 2 device access. <strong class="text-gray-800">Growth ($15.99/mo or $159.90/yr)</strong> — everything in Starter plus 5 device access and priority support. All plans include contact-ready leads, ratings, review insights, and advanced filters. View our <a href="#pricing" class="font-semibold hover:underline" style="color: rgb(249, 115, 22);">pricing section</a> for a complete feature comparison.
                        </div>
                    </div>
                </div>

                <!-- FAQ 12 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">How do I get started?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Getting started takes less than a minute. <strong class="text-gray-800">Create a free account</strong>, enter a location and business category, and run your first search. You will immediately see real, verified business leads from Google Maps. No setup, no API keys, no technical knowledge required — just sign up and start finding clients.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom CTA -->
            <div class="text-center mt-12 pt-8 border-t border-gray-100">
                <p class="text-gray-500 mb-4">Still have questions?</p>
                <a href="#contact" class="inline-flex items-center gap-2 font-semibold transition-colors duration-200 hover:underline" style="color: rgb(249, 115, 22);">
                    Contact our support team
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-24 bg-gradient-to-br from-gray-900 via-gray-900 to-gray-800 relative overflow-hidden">
        <!-- Background accent -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-primary-orange/10 rounded-full blur-3xl"></div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white mb-5 leading-tight">
                Stop Chasing Dead Leads.<br class="hidden sm:block">
                <span class="text-primary-orange">Start Closing Real Clients.</span>
            </h2>
            <p class="text-lg text-gray-400 mb-10 max-w-xl mx-auto">
                Launch your lead generation the smart way with {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}.
            </p>
            <a href="{{ route('auth.show') }}" class="inline-block bg-primary-orange hover:bg-dark-orange text-white px-10 py-4 rounded-xl text-lg font-bold transition-all hover:scale-105 shadow-lg shadow-orange-500/20">
                Get Started Now
            </a>
            <p class="text-sm text-gray-500 mt-4">No credit card required. Cancel anytime.</p>
        </div>
    </section>

    {{-- Payment Modal --}}
    @include('partials.payment-modal')

    <!-- Footer -->
    <footer id="contact" class="relative bg-white overflow-hidden">

        <!-- ===== Oversized watermark logo in background ===== -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden" aria-hidden="true">
            <span class="text-[12rem] sm:text-[16rem] lg:text-[22rem] font-black tracking-tighter text-gray-900/[0.018] whitespace-nowrap leading-none">CustomerNearme</span>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- ===== 1. Floating logo badge + gradient divider ===== -->
            <div class="relative flex items-center justify-center py-2" style="margin-top: 50px;">
                <!-- gradient line left -->
                <div class="hidden sm:block flex-1 h-px bg-gradient-to-r from-transparent via-orange-300 to-orange-400"></div>
                <!-- logo badge -->
                <div class="relative mx-6">
                    <div class="absolute -inset-3 rounded-3xl bg-gradient-to-br from-orange-400/20 via-blue-400/10 to-transparent blur-xl"></div>
                    <a href="https://www.customernearme.com" class="relative flex items-center gap-3 bg-white rounded-2xl px-6 py-3.5 shadow-lg shadow-gray-200/60 ring-1 ring-gray-100 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                        @php
                            $footerLogo = \App\Models\Setting::get('site_logo');
                            $footerSiteName = \App\Models\Setting::get('site_name', config('app.name'));
                        @endphp
                        @if($footerLogo)
                            <img src="{{ asset('public/' . $footerLogo) }}" alt="{{ $footerSiteName }}" class="h-12 w-auto object-contain">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md shadow-orange-200/60">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-lg font-extrabold text-gray-900 tracking-tight">Customer<span class="text-orange-500">Nearme</span></span>
                        @endif
                    </a>
                </div>
                <!-- gradient line right -->
                <div class="hidden sm:block flex-1 h-px bg-gradient-to-l from-transparent via-blue-300 to-blue-500"></div>
            </div>


            <!-- ===== 2. Main content — bento-style layout ===== -->
            <div class="pt-12 pb-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                <!-- CARD A: Navigate -->
                <div class="rounded-3xl bg-gradient-to-br from-gray-50 to-white border border-gray-100 p-7 sm:p-8 flex flex-col">
                    <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-500 mb-5">Navigate</h4>
                    <div class="flex flex-wrap gap-2 mb-7">
                        <a href="#sample-data" class="px-4 py-2 text-[13px] font-medium text-gray-600 bg-white rounded-full border border-gray-200 shadow-sm hover:border-orange-300 hover:text-orange-600 hover:shadow-md hover:shadow-orange-100/50 hover:-translate-y-px transition-all duration-200">Leads Sample</a>
                        <a href="#how-it-works" class="px-4 py-2 text-[13px] font-medium text-gray-600 bg-white rounded-full border border-gray-200 shadow-sm hover:border-orange-300 hover:text-orange-600 hover:shadow-md hover:shadow-orange-100/50 hover:-translate-y-px transition-all duration-200">How It Works</a>
                        <a href="#pricing" class="px-4 py-2 text-[13px] font-medium text-gray-600 bg-white rounded-full border border-gray-200 shadow-sm hover:border-orange-300 hover:text-orange-600 hover:shadow-md hover:shadow-orange-100/50 hover:-translate-y-px transition-all duration-200">Pricing</a>
                        <a href="#faq" class="px-4 py-2 text-[13px] font-medium text-gray-600 bg-white rounded-full border border-gray-200 shadow-sm hover:border-orange-300 hover:text-orange-600 hover:shadow-md hover:shadow-orange-100/50 hover:-translate-y-px transition-all duration-200">FAQ</a>
                        <a href="{{ route('auth.show') }}" class="px-4 py-2 text-[13px] font-medium text-gray-600 bg-white rounded-full border border-gray-200 shadow-sm hover:border-blue-300 hover:text-blue-600 hover:shadow-md hover:shadow-blue-100/50 hover:-translate-y-px transition-all duration-200">Login</a>
                    </div>
                    <div class="mt-auto">
                        <a href="{{ route('auth.show') }}" class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white text-sm font-bold shadow-lg shadow-orange-200/50 hover:shadow-xl hover:shadow-orange-300/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
                            Start Free Trial
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- CARD B: Get in Touch -->
                <div class="rounded-3xl bg-gradient-to-br from-gray-50 to-white border border-gray-100 p-7 sm:p-8 flex flex-col">
                    <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] text-blue-600 mb-5">Get in Touch</h4>

                    <!-- Email -->
                    <a href="mailto:info@customernearme.com" class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:border-orange-200 hover:shadow-md hover:shadow-orange-100/40 transition-all duration-200 mb-3">
                        <span class="flex-shrink-0 w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center group-hover:bg-orange-100 transition-colors duration-200">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider mb-0.5 font-medium">Email us</p>
                            <p class="text-sm text-gray-700 group-hover:text-orange-600 transition-colors duration-200 truncate font-medium">info@customernearme.com</p>
                        </div>
                    </a>

                    <!-- WhatsApp -->
                    <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:border-green-200 hover:shadow-md hover:shadow-green-100/40 transition-all duration-200">
                        <span class="flex-shrink-0 w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider mb-0.5 font-medium">Chat on WhatsApp</p>
                            <p class="text-sm text-gray-700 group-hover:text-green-600 transition-colors duration-200 font-medium">+92 346 0820722</p>
                        </div>
                        <span class="flex-shrink-0 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-2.5 w-2.5 rounded-full bg-green-400 opacity-50"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                        </span>
                    </a>
                </div>

                <!-- CARD C: Follow Us -->
                <div class="rounded-3xl bg-gradient-to-br from-gray-50 to-white border border-gray-100 p-7 sm:p-8 flex flex-col md:col-span-2 lg:col-span-1">
                    <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-900 mb-5">Follow Us</h4>

                    <div class="grid grid-cols-6 gap-2.5 mb-7">
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/CustomerNearme" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="group aspect-square rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <!-- LinkedIn -->
                        <a href="https://www.linkedin.com/company/customernearme" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="group aspect-square rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center hover:border-blue-500 hover:shadow-lg hover:shadow-blue-100/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-700 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <!-- X -->
                        <a href="https://x.com/CustomerNearme" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)" class="group aspect-square rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center hover:border-gray-400 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-[17px] h-[17px] text-gray-400 group-hover:text-gray-900 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/customernearme" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="group aspect-square rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center hover:border-pink-300 hover:shadow-lg hover:shadow-pink-100/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-pink-500 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                        </a>
                        <!-- YouTube -->
                        <a href="https://www.youtube.com/@CustomerNearme" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="group aspect-square rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center hover:border-red-300 hover:shadow-lg hover:shadow-red-100/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        <!-- WhatsApp -->
                        <a href="https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="group aspect-square rounded-2xl bg-white border border-green-100 shadow-sm flex items-center justify-center hover:border-green-300 hover:shadow-lg hover:shadow-green-100/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 text-green-500 group-hover:text-green-600 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Trust badge -->
                    <div class="mt-auto flex items-center gap-2.5 px-4 py-3 rounded-xl bg-white border border-gray-100 shadow-sm">
                        <div class="flex -space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400 ring-2 ring-white"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-400 ring-2 ring-white"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400 ring-2 ring-white"></span>
                        </div>
                        <span class="text-[12px] text-gray-500 font-medium">Built to find clients that actually respond</span>
                    </div>
                </div>

            </div>

            <!-- ===== 3. Bottom bar ===== -->
            <div class="border-t border-gray-100 py-7 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center flex-wrap justify-center gap-x-5 gap-y-1">
                    <a href="{{ route('terms') }}" class="text-xs text-gray-400 hover:text-orange-500 transition-colors duration-200">Terms of Service</a>
                    <a href="{{ route('privacy.policy') }}" class="text-xs text-gray-400 hover:text-orange-500 transition-colors duration-200">Privacy Policy</a>
                    <a href="{{ route('refund.policy') }}" class="text-xs text-gray-400 hover:text-orange-500 transition-colors duration-200">Refund Policy</a>
                </div>
                <p class="text-xs text-gray-400">
                    &copy; <script>document.write(new Date().getFullYear())</script> CustomerNearme. All rights reserved.
                </p>
            </div>

        </div>
    </footer>

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
                    if (i) { i.style.transform = 'rotate(0deg)'; }
                    item.classList.remove('border-orange-300', 'shadow-sm');
                }
            });

            // Toggle current item
            if (isOpen) {
                content.style.maxHeight = '0px';
                icon.style.transform = 'rotate(0deg)';
                faqItem.classList.remove('border-orange-300', 'shadow-sm');
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
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
                        b.classList.toggle('bg-primary-blue', active);
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

            monthlyBtn.classList.toggle('bg-primary-blue', isMonthly);
            monthlyBtn.classList.toggle('text-white', isMonthly);
            monthlyBtn.classList.toggle('text-gray-600', !isMonthly);

            yearlyBtn.classList.toggle('bg-primary-blue', !isMonthly);
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