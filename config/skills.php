<?php

/*
|--------------------------------------------------------------------------
| Skill catalog (AI Sales Profile → Skills)
|--------------------------------------------------------------------------
| Main skill => list of sub skills. Users pick a main skill, then one of its
| sub skills; the sub skill is saved as the skill title. Sub skill names must
| be unique across the whole catalog (the main skill is looked up from it).
*/

return [
    // How many skills an account must have / may have.
    'min' => 3,
    'max' => 10,

    'catalog' => [
        'Web Development' => [
            'Custom Website Development', 'WordPress Development', 'Laravel Development', 'PHP Development',
            'React Development', 'Vue.js Development', 'Angular Development', 'Next.js Development',
            'Node.js Development', 'Python / Django Development', 'Ruby on Rails Development', 'ASP.NET Development',
            'Full-Stack Development', 'Frontend Development', 'Backend & API Development', 'Landing Page Development',
            'Website Maintenance & Support', 'Website Speed Optimization', 'Website Migration', 'Bug Fixing & Troubleshooting',
            'Webflow Development', 'Wix Development', 'Squarespace Development', 'Progressive Web Apps (PWA)',
        ],
        'E-commerce' => [
            'Shopify Store Development', 'WooCommerce Development', 'Magento Development', 'BigCommerce Development',
            'Amazon Store Management', 'eBay Store Management', 'Etsy Shop Management', 'Product Listing & Catalog',
            'Payment Gateway Integration', 'Dropshipping Store Setup', 'E-commerce Conversion Optimization', 'Marketplace Development',
        ],
        'Mobile App Development' => [
            'iOS App Development', 'Android App Development', 'Flutter Development', 'React Native Development',
            'Cross-Platform App Development', 'App UI Design', 'App Maintenance & Updates', 'App Store Optimization (ASO)',
            'Mobile Game Development', 'Wearable App Development',
        ],
        'Software Development' => [
            'Custom Software Development', 'SaaS Development', 'Desktop Application Development', 'CRM Development',
            'ERP Development', 'API Integration', 'Automation Scripts', 'Chrome Extension Development',
            'Database Design & Management', 'Software Testing & QA', 'Legacy System Modernization', 'Microservices Architecture',
        ],
        'Design & Creative' => [
            'Logo Design', 'Brand Identity Design', 'UI/UX Design', 'Website Design',
            'Graphic Design', 'Social Media Graphics', 'Print Design', 'Packaging Design',
            'Illustration', 'Infographic Design', 'Presentation Design', 'Banner & Ad Design',
            'Figma Design', 'Product Design', 'Interior Design', 'Architecture & 3D Rendering',
        ],
        'Video & Animation' => [
            'Video Editing', 'Motion Graphics', '2D Animation', '3D Animation',
            'Explainer Videos', 'YouTube Video Editing', 'Short-Form Video (Reels / TikTok)', 'Corporate Video Production',
            'Whiteboard Animation', 'Video Ads', 'Color Grading', 'Drone Videography',
        ],
        'Photography' => [
            'Product Photography', 'Real Estate Photography', 'Event Photography', 'Portrait Photography',
            'Food Photography', 'Wedding Photography', 'Photo Editing & Retouching', 'Commercial Photography',
        ],
        'Audio & Music' => [
            'Voice Over', 'Podcast Editing', 'Music Production', 'Audio Mixing & Mastering',
            'Jingles & Sound Logos', 'Sound Design', 'Audio Transcription',
        ],
        'Digital Marketing' => [
            'Digital Marketing Strategy', 'Social Media Marketing', 'Social Media Management', 'Facebook & Instagram Ads',
            'Google Ads (PPC)', 'LinkedIn Ads', 'TikTok Ads', 'YouTube Ads',
            'Email Marketing', 'Influencer Marketing', 'Affiliate Marketing', 'Marketing Automation',
            'Growth Hacking', 'Conversion Rate Optimization (CRO)', 'Retargeting Campaigns', 'Lead Generation',
        ],
        'SEO' => [
            'Local SEO', 'On-Page SEO', 'Off-Page SEO & Link Building', 'Technical SEO',
            'SEO Audit', 'Keyword Research', 'E-commerce SEO', 'Google Business Profile Optimization',
            'Citation Building', 'International SEO', 'Content SEO', 'Reputation Management',
        ],
        'Writing & Content' => [
            'Copywriting', 'Website Content Writing', 'Blog Writing', 'SEO Content Writing',
            'Technical Writing', 'Product Descriptions', 'Ghostwriting', 'Script Writing',
            'Press Release Writing', 'Resume & Cover Letter Writing', 'Proofreading & Editing', 'Grant Writing',
            'Newsletter Writing', 'Case Study Writing',
        ],
        'Translation & Languages' => [
            'English Translation', 'Arabic Translation', 'Spanish Translation', 'French Translation',
            'German Translation', 'Urdu Translation', 'Chinese Translation', 'Website Localization',
            'Subtitles & Captions', 'Interpretation',
        ],
        'AI & Data' => [
            'AI Chatbot Development', 'AI Automation', 'Machine Learning', 'Prompt Engineering',
            'Data Analysis', 'Data Visualization', 'Power BI', 'Tableau',
            'Data Entry', 'Web Scraping', 'Data Science', 'Computer Vision',
            'Natural Language Processing (NLP)', 'Business Intelligence',
        ],
        'IT & Networking' => [
            'IT Support', 'Network Administration', 'Cloud Computing (AWS)', 'Microsoft Azure',
            'Google Cloud Platform', 'DevOps', 'Server Management', 'Linux Administration',
            'Microsoft 365 Setup', 'Google Workspace Setup', 'Website Hosting Management', 'Backup & Disaster Recovery',
        ],
        'Cybersecurity' => [
            'Penetration Testing', 'Security Audit', 'Malware Removal', 'Firewall Configuration',
            'Website Security', 'Compliance (GDPR / HIPAA)', 'Vulnerability Assessment', 'Security Awareness Training',
        ],
        'Business & Consulting' => [
            'Business Consulting', 'Business Plan Writing', 'Market Research', 'Project Management',
            'Product Management', 'Startup Consulting', 'Management Consulting', 'Operations Management',
            'Business Process Automation', 'HR Consulting', 'Recruitment', 'Training & Coaching',
        ],
        'Sales & Customer Support' => [
            'Sales Consulting', 'Cold Calling', 'Appointment Setting', 'B2B Sales',
            'Customer Service', 'Live Chat Support', 'CRM Management', 'Sales Funnel Building',
            'Telemarketing', 'Customer Success',
        ],
        'Virtual Assistance & Admin' => [
            'Virtual Assistant', 'Administrative Support', 'Executive Assistant', 'Calendar Management',
            'Email Management', 'Online Research', 'Bookkeeping Assistance', 'Order Processing',
        ],
        'Finance & Accounting' => [
            'Accounting', 'Bookkeeping', 'Tax Preparation', 'Financial Planning',
            'Payroll Services', 'Financial Modeling', 'Auditing', 'QuickBooks',
            'Xero', 'Invoicing & Billing', 'CFO Services', 'Investment Advisory',
        ],
        'Legal' => [
            'Legal Consulting', 'Contract Drafting', 'Business Registration', 'Trademark Registration',
            'Immigration Services', 'Corporate Law', 'Intellectual Property', 'Legal Document Review',
        ],
        'Real Estate' => [
            'Property Sales', 'Property Management', 'Real Estate Marketing', 'Real Estate Consulting',
            'Property Valuation', 'Rental Services', 'Real Estate Virtual Tours', 'Commercial Real Estate',
        ],
        'Construction & Home Services' => [
            'General Contracting', 'Home Renovation', 'Plumbing', 'Electrical Services',
            'HVAC Services', 'Roofing', 'Painting & Decorating', 'Carpentry',
            'Flooring', 'Landscaping', 'Cleaning Services', 'Pest Control',
            'Moving & Relocation', 'Handyman Services', 'Solar Installation', 'Security Systems Installation',
        ],
        'Health & Wellness' => [
            'Medical Consulting', 'Dental Services', 'Physiotherapy', 'Nutrition & Dietetics',
            'Personal Training', 'Yoga Instruction', 'Mental Health Counseling', 'Medical Billing',
            'Healthcare Marketing', 'Spa & Massage', 'Telemedicine', 'Home Healthcare',
        ],
        'Beauty & Fashion' => [
            'Hair Styling', 'Makeup Artistry', 'Nail Services', 'Skincare',
            'Fashion Design', 'Tailoring & Alterations', 'Personal Styling', 'Barber Services',
        ],
        'Education & Training' => [
            'Online Tutoring', 'Course Creation', 'E-learning Development', 'Language Teaching',
            'Test Preparation', 'Corporate Training', 'Curriculum Development', 'LMS Setup',
            'Quran Teaching', 'Music Lessons',
        ],
        'Events & Hospitality' => [
            'Event Planning', 'Wedding Planning', 'Catering', 'Restaurant Consulting',
            'Hotel Management', 'Travel Planning', 'Tour Guide Services', 'DJ Services',
            'Event Decoration', 'Venue Management',
        ],
        'Automotive & Transport' => [
            'Car Repair & Maintenance', 'Car Detailing', 'Car Rental', 'Auto Body & Paint',
            'Towing Services', 'Logistics & Freight', 'Courier & Delivery', 'Fleet Management',
            'Chauffeur Services',
        ],
        'Manufacturing & Engineering' => [
            'Mechanical Engineering', 'Electrical Engineering', 'Civil Engineering', 'CAD Design',
            'Product Prototyping', '3D Printing', 'Industrial Design', 'Quality Control',
            'Supply Chain Management', 'PCB Design',
        ],
        'Agriculture & Environment' => [
            'Farming Consulting', 'Gardening Services', 'Environmental Consulting', 'Waste Management',
            'Water Treatment', 'Renewable Energy Consulting',
        ],
        'Pets & Animals' => [
            'Pet Grooming', 'Pet Sitting', 'Dog Training', 'Veterinary Services', 'Pet Boarding',
        ],
    ],
];
