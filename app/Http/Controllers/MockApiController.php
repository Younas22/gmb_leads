<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MockApiController extends Controller
{
    /**
     * Mock search API for development mode
     * Returns test data based on plan type with support for filters and pagination
     */
    public function search(Request $request)
    {
        $plan = $request->input('plan', 'starter');
        $pageToken = $request->input('pagetoken');
        $reviewMax = (int) $request->input('review_max', 0);
        $latestReviewWithinDays = (int) $request->input('latest_review_within_days', 0);

        // Get base results for the plan
        $results = $this->getBaseResults($plan);

        // If page token is provided, return page 2 data
        if ($pageToken) {
            $results = $this->getPage2Results($plan);
            $nextPageToken = null; // No more pages after page 2
        } else {
            $nextPageToken = 'mock_dev_token_page2_' . time();
        }

        // Apply review_max filter
        if ($reviewMax > 0) {
            $results = array_filter($results, function ($item) use ($reviewMax) {
                return ($item['total_reviews'] ?? 0) <= $reviewMax;
            });
            $results = array_values($results);
        }

        // Apply latest_review_within_days filter
        if ($latestReviewWithinDays > 0 && in_array($plan, ['growth', 'pro'])) {
            $cutoffTimestamp = now()->subDays($latestReviewWithinDays)->timestamp;
            $results = array_filter($results, function ($item) use ($cutoffTimestamp) {
                return !empty($item['latest_review_date']) && $item['latest_review_date'] >= $cutoffTimestamp;
            });
            $results = array_values($results);
        }

        $totalResults = count($results);

        // Build meta based on plan
        $meta = [
            'api_calls' => [
                'place_details' => $plan === 'starter' ? 0 : $totalResults,
                'text_search' => 1,
                'total' => $plan === 'starter' ? 1 : $totalResults + 1,
            ],
            'filters' => [
                'latest_review_within_days' => $latestReviewWithinDays,
                'review_max' => $reviewMax,
            ],
            'places' => [
                'after_latest_review_filter' => $totalResults,
                'after_review_filter' => $totalResults,
                'final_returned' => $totalResults,
                'text_search_results' => $totalResults,
            ],
            'plan' => $plan,
        ];

        return response()->json([
            'api_version' => '3.0',
            'meta' => $meta,
            'next_page_token' => $nextPageToken,
            'results' => $results,
            'success' => true,
        ]);
    }

    /**
     * Get base results (page 1) for a plan
     */
    private function getBaseResults(string $plan): array
    {
        $starterData = [
            [
                'address' => '150 N College St, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Queen City Rides: Historical and Haunted City Tours',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJ63Go_ySgVogR2tZgq3Ll79c',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 778,
                'website' => null,
            ],
            [
                'address' => '380 S College St, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Charlotte City Tours',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJa_B-HwChVogRXOErtA3QQT0',
                'rating' => 4.8,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 52,
                'website' => null,
            ],
            [
                'address' => '227 W 4th St First Floor, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Queen City Culture Vultures',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJQcqSK7ih-UER-j9xitE4iLM',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 140,
                'website' => null,
            ],
            [
                'address' => '224 E 7th St, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Southern Charm Rides',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJw46XWwSqMGgR0q7-_23VNRo',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 133,
                'website' => null,
            ],
            [
                'address' => '2600 Park Rd, Charlotte, NC 28209, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Far Out Travel Tours',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJNd-G0e6fVogROiFEQD-YeeM',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 31,
                'website' => null,
            ],
            [
                'address' => '201 N Tryon St, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Carolina History & Haunts Charlotte Ghost Tour',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJE5k9GiWgVogRSgL0qWB-e3k',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 158,
                'website' => null,
            ],
            [
                'address' => '310 East Blvd, Charlotte, NC 28203, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => "Nada's Italy",
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJx_ehOryeVogRxbNEQTohjnI',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 159,
                'website' => null,
            ],
            [
                'address' => '101 S Tryon St #16, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Charlotte Segway Tours / Charlotte Rydables',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJVbP4NH-fVogRSBjA3DhMYn4',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 155,
                'website' => null,
            ],
            [
                'address' => '3729 Monique Ln, Charlotte, NC 28210, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'The Travel Byrds | Travel Agency',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJuzks5pViDEYRjrrZJgMywdA',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 341,
                'website' => null,
            ],
            [
                'address' => '2500 Dunavant St, Charlotte, NC 28203, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Trolley Pub Charlotte',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJ6c1ZjHSgVogR0glwdDuMHho',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 2893,
                'website' => null,
            ],
        ];

        if ($plan === 'starter') {
            return $starterData;
        }

        // Growth plan - add emails, phone, website, opening_hours, social_links, latest_review_date
        $growthData = [
            [
                'address' => '150 N College St, Charlotte, NC 28202, USA',
                'emails' => ['info@queencityrides.com'],
                'latest_review_date' => now()->subDays(5)->timestamp,
                'name' => 'Queen City Rides: Historical and Haunted City Tours',
                'opening_hours' => ['Monday: 8:00 AM - 8:00 PM', 'Tuesday: 8:00 AM - 8:00 PM', 'Wednesday: 8:00 AM - 8:00 PM', 'Thursday: 8:00 AM - 8:00 PM', 'Friday: 8:00 AM - 8:00 PM', 'Saturday: 8:00 AM - 8:00 PM', 'Sunday: 8:00 AM - 8:00 PM'],
                'phone' => '+1 833-868-7258',
                'profile' => 'https://maps.google.com/?cid=15559907518252635866',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 778,
                'website' => 'https://queencityrides.com/',
            ],
            [
                'address' => '380 S College St, Charlotte, NC 28202, USA',
                'emails' => ['management@takeguidedtours.com', 'waqasahmad9961@gmail.com'],
                'latest_review_date' => now()->subDays(15)->timestamp,
                'name' => 'Charlotte City Tours',
                'opening_hours' => ['Monday: 9:00 AM - 7:00 PM', 'Tuesday: 9:00 AM - 7:00 PM', 'Wednesday: 9:00 AM - 7:00 PM', 'Thursday: 9:00 AM - 7:00 PM', 'Friday: 9:00 AM - 7:00 PM', 'Saturday: 9:00 AM - 7:00 PM', 'Sunday: 9:00 AM - 7:00 PM'],
                'phone' => '+1 980-655-4641',
                'profile' => 'https://maps.google.com/?cid=4414037867075723612',
                'rating' => 4.8,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 52,
                'website' => 'https://www.takeguidedtours.com/charlotte/',
            ],
            [
                'address' => '227 W 4th St First Floor, Charlotte, NC 28202, USA',
                'emails' => ['info@queencityculturevultures.com'],
                'latest_review_date' => now()->subDays(30)->timestamp,
                'name' => 'Queen City Culture Vultures',
                'opening_hours' => ['Monday: 9:00 AM - 9:00 PM', 'Tuesday: 9:00 AM - 9:00 PM', 'Wednesday: 9:00 AM - 9:00 PM', 'Thursday: 9:00 AM - 9:00 PM', 'Friday: 9:00 AM - 9:00 PM', 'Saturday: 9:00 AM - 9:00 PM', 'Sunday: 9:00 AM - 9:00 PM'],
                'phone' => '+1 336-918-2022',
                'profile' => 'https://maps.google.com/?cid=12936652402243747834',
                'rating' => 5,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/queencityculturevultures/', 'https://www.facebook.com/queencityculturevultures/'],
                'total_reviews' => 140,
                'website' => 'https://queencityculturevultures.com/',
            ],
            [
                'address' => '224 E 7th St, Charlotte, NC 28202, USA',
                'emails' => ['southerncharmrides1@gmail.com'],
                'latest_review_date' => now()->subDays(45)->timestamp,
                'name' => 'Southern Charm Rides',
                'opening_hours' => ['Monday: 8:30 AM - 9:00 PM', 'Tuesday: 8:30 AM - 9:00 PM', 'Wednesday: 8:30 AM - 9:00 PM', 'Thursday: 8:30 AM - 9:00 PM', 'Friday: 8:30 AM - 9:00 PM', 'Saturday: 8:30 AM - 9:00 PM', 'Sunday: 8:30 AM - 9:00 PM'],
                'phone' => '+1 980-295-8041',
                'profile' => 'https://maps.google.com/?cid=1888650287174823634',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => ['https://www.facebook.com/SouthernCharmRides'],
                'total_reviews' => 133,
                'website' => 'https://www.southerncharmrides.com/',
            ],
            [
                'address' => '2600 Park Rd, Charlotte, NC 28209, USA',
                'emails' => ['seanhira125@gmail.com'],
                'latest_review_date' => now()->subDays(10)->timestamp,
                'name' => 'Far Out Travel Tours',
                'opening_hours' => null,
                'phone' => '+1 803-984-7563',
                'profile' => 'https://maps.google.com/?cid=16391299716220199226',
                'rating' => 5,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/farouttraveltours/'],
                'total_reviews' => 31,
                'website' => 'https://www.farouttraveling.com/',
            ],
            [
                'address' => '201 N Tryon St, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => now()->subDays(25)->timestamp,
                'name' => 'Carolina History & Haunts Charlotte Ghost Tour',
                'opening_hours' => ['Monday: 10:00 AM - 10:00 PM', 'Tuesday: 10:00 AM - 10:00 PM', 'Wednesday: Closed', 'Thursday: Closed', 'Friday: 10:00 AM - 10:00 PM', 'Saturday: 10:00 AM - 10:00 PM', 'Sunday: 10:00 AM - 10:00 PM'],
                'phone' => '+1 833-628-6277',
                'profile' => 'https://maps.google.com/?cid=8753729254357992010',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/carolina_history_and_haunts/', 'https://www.facebook.com/carolinahistoryandhaunts'],
                'total_reviews' => 158,
                'website' => 'http://www.carolinahistoryandhaunts.com/',
            ],
            [
                'address' => '310 East Blvd, Charlotte, NC 28203, USA',
                'emails' => ['info@nadasitaly.com'],
                'latest_review_date' => now()->subDays(20)->timestamp,
                'name' => "Nada's Italy",
                'opening_hours' => ['Monday: 9:00 AM - 5:00 PM', 'Tuesday: 9:00 AM - 5:00 PM', 'Wednesday: 9:00 AM - 5:00 PM', 'Thursday: 9:00 AM - 5:00 PM', 'Friday: 9:00 AM - 5:00 PM', 'Saturday: Closed', 'Sunday: Closed'],
                'phone' => '+1 877-959-8365',
                'profile' => 'https://maps.google.com/?cid=8254571701103539141',
                'rating' => 5,
                'reviews' => [],
                'social_links' => ['https://www.facebook.com/nadasitalytours', 'https://www.instagram.com/nadasitaly/'],
                'total_reviews' => 159,
                'website' => 'http://nadasitaly.com/',
            ],
            [
                'address' => '101 S Tryon St #16, Charlotte, NC 28202, USA',
                'emails' => ['info@charlottenctours.com'],
                'latest_review_date' => now()->subDays(3)->timestamp,
                'name' => 'Charlotte Segway Tours / Charlotte Rydables',
                'opening_hours' => ['Monday: 9:00 AM - 9:00 PM', 'Tuesday: 9:00 AM - 9:00 PM', 'Wednesday: 9:00 AM - 9:00 PM', 'Thursday: 9:00 AM - 9:00 PM', 'Friday: 9:00 AM - 9:00 PM', 'Saturday: 9:00 AM - 5:00 PM', 'Sunday: 9:00 AM - 5:00 PM'],
                'phone' => '+1 704-962-4548',
                'profile' => 'https://maps.google.com/?cid=9106925203602020424',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => ['https://www.facebook.com/CharlotteNcTours/', 'https://www.instagram.com/charlottenctours/'],
                'total_reviews' => 155,
                'website' => 'http://www.charlottenctours.com/',
            ],
            [
                'address' => '3729 Monique Ln, Charlotte, NC 28210, USA',
                'emails' => ['media@travelbyrds.com', 'sales@travelbyrds.com'],
                'latest_review_date' => now()->subDays(8)->timestamp,
                'name' => 'The Travel Byrds | Travel Agency',
                'opening_hours' => ['Monday: 9:00 AM - 6:00 PM', 'Tuesday: 9:00 AM - 6:00 PM', 'Wednesday: 9:00 AM - 6:00 PM', 'Thursday: 9:00 AM - 6:00 PM', 'Friday: 9:00 AM - 6:00 PM', 'Saturday: Closed', 'Sunday: Closed'],
                'phone' => '+1 800-976-0706',
                'profile' => 'https://maps.google.com/?cid=15042359219512261262',
                'rating' => 5,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/thetravelbyrds', 'https://www.facebook.com/thetravelbyrds'],
                'total_reviews' => 341,
                'website' => 'https://www.travelbyrds.com/',
            ],
            [
                'address' => '2500 Dunavant St, Charlotte, NC 28203, USA',
                'emails' => [],
                'latest_review_date' => now()->subDays(2)->timestamp,
                'name' => 'Trolley Pub Charlotte',
                'opening_hours' => ['Monday: 10:30 AM - 9:00 PM', 'Tuesday: 10:30 AM - 9:00 PM', 'Wednesday: 10:30 AM - 9:00 PM', 'Thursday: 10:30 AM - 9:00 PM', 'Friday: 10:30 AM - 9:00 PM', 'Saturday: 11:00 AM - 9:00 PM', 'Sunday: 11:00 AM - 9:00 PM'],
                'phone' => '+1 980-999-2198',
                'profile' => 'https://maps.google.com/?cid=1882095881271904722',
                'rating' => 5,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/trolleypub.charlotte/', 'https://www.facebook.com/partypedaler/'],
                'total_reviews' => 2893,
                'website' => 'https://trolleypub.com/charlotte/',
            ],
        ];

        if ($plan === 'growth') {
            return $growthData;
        }

        // Pro plan - same as growth but with reviews data
        $proData = $growthData;
        $sampleReviews = [
            [
                ['author_name' => 'John Smith', 'rating' => 5, 'text' => 'Amazing experience! The tour was incredibly informative and fun. Our guide was knowledgeable and entertaining. Highly recommend!', 'time' => now()->subDays(2)->timestamp, 'relative_time_description' => '2 days ago'],
                ['author_name' => 'Sarah Johnson', 'rating' => 5, 'text' => 'Best tour in Charlotte! We had such a great time learning about the city history. Will definitely come back.', 'time' => now()->subDays(5)->timestamp, 'relative_time_description' => '5 days ago'],
                ['author_name' => 'Mike Davis', 'rating' => 4, 'text' => 'Great tour overall. The guide was very passionate about Charlotte history. Only wish it was a bit longer.', 'time' => now()->subDays(10)->timestamp, 'relative_time_description' => '10 days ago'],
            ],
            [
                ['author_name' => 'Emily Brown', 'rating' => 5, 'text' => 'Wonderful city tour! Saw so many beautiful sights. The guide made everything come alive with stories.', 'time' => now()->subDays(15)->timestamp, 'relative_time_description' => '15 days ago'],
                ['author_name' => 'David Wilson', 'rating' => 4, 'text' => 'Good tour, friendly staff. Would recommend for first-time visitors to Charlotte.', 'time' => now()->subDays(20)->timestamp, 'relative_time_description' => '20 days ago'],
            ],
            [
                ['author_name' => 'Lisa Anderson', 'rating' => 5, 'text' => 'Absolutely loved this experience! The culture and history tour was top-notch. Our guide was exceptional.', 'time' => now()->subDays(3)->timestamp, 'relative_time_description' => '3 days ago'],
                ['author_name' => 'James Taylor', 'rating' => 5, 'text' => 'A must-do when visiting Charlotte. The walking tour covered all the major highlights.', 'time' => now()->subDays(8)->timestamp, 'relative_time_description' => '8 days ago'],
                ['author_name' => 'Amanda White', 'rating' => 4, 'text' => 'Really enjoyed the tour. Great way to spend an afternoon in the city.', 'time' => now()->subDays(12)->timestamp, 'relative_time_description' => '12 days ago'],
            ],
            [
                ['author_name' => 'Robert Martinez', 'rating' => 5, 'text' => 'Fantastic rides and great service! The party bus was perfect for our group outing.', 'time' => now()->subDays(7)->timestamp, 'relative_time_description' => '7 days ago'],
                ['author_name' => 'Jennifer Lee', 'rating' => 5, 'text' => 'Had an amazing time! The driver was so friendly and the vehicle was spotless.', 'time' => now()->subDays(14)->timestamp, 'relative_time_description' => '14 days ago'],
            ],
            [
                ['author_name' => 'Chris Thompson', 'rating' => 5, 'text' => 'Travel planning made easy! They helped us plan the perfect vacation. Everything was seamless.', 'time' => now()->subDays(10)->timestamp, 'relative_time_description' => '10 days ago'],
                ['author_name' => 'Patricia Garcia', 'rating' => 4, 'text' => 'Great travel agency with personalized service. Will use again for our next trip.', 'time' => now()->subDays(18)->timestamp, 'relative_time_description' => '18 days ago'],
            ],
            [
                ['author_name' => 'Daniel Robinson', 'rating' => 5, 'text' => 'Spooky and educational! The ghost tour was a highlight of our Charlotte trip. So much history!', 'time' => now()->subDays(6)->timestamp, 'relative_time_description' => '6 days ago'],
                ['author_name' => 'Michelle Clark', 'rating' => 5, 'text' => 'An unforgettable evening! The stories were captivating and our guide was amazing.', 'time' => now()->subDays(11)->timestamp, 'relative_time_description' => '11 days ago'],
                ['author_name' => 'Kevin Lewis', 'rating' => 4, 'text' => 'Very entertaining ghost tour. Perfect for a Friday night out with friends.', 'time' => now()->subDays(25)->timestamp, 'relative_time_description' => '25 days ago'],
            ],
            [
                ['author_name' => 'Sophia Walker', 'rating' => 5, 'text' => 'Italy trip of a lifetime! Nada planned everything perfectly. From the hotels to the food tours, it was all incredible.', 'time' => now()->subDays(20)->timestamp, 'relative_time_description' => '20 days ago'],
                ['author_name' => 'Andrew Hall', 'rating' => 5, 'text' => 'Best Italy travel agency hands down. They know all the hidden gems.', 'time' => now()->subDays(30)->timestamp, 'relative_time_description' => 'a month ago'],
            ],
            [
                ['author_name' => 'Rachel Allen', 'rating' => 5, 'text' => 'The Segway tour was so much fun! We covered so much ground and saw amazing parts of Charlotte.', 'time' => now()->subDays(3)->timestamp, 'relative_time_description' => '3 days ago'],
                ['author_name' => 'Thomas Young', 'rating' => 5, 'text' => 'Great experience for the whole family. The guides were patient and really helpful.', 'time' => now()->subDays(9)->timestamp, 'relative_time_description' => '9 days ago'],
                ['author_name' => 'Nicole King', 'rating' => 4, 'text' => 'Fun way to explore the city! Would recommend booking the longer tour option.', 'time' => now()->subDays(16)->timestamp, 'relative_time_description' => '16 days ago'],
            ],
            [
                ['author_name' => 'Brandon Scott', 'rating' => 5, 'text' => 'The Travel Byrds made our honeymoon perfect! Every detail was taken care of. Truly a luxury experience.', 'time' => now()->subDays(8)->timestamp, 'relative_time_description' => '8 days ago'],
                ['author_name' => 'Ashley Green', 'rating' => 5, 'text' => 'Incredible service from start to finish. They went above and beyond for our family vacation.', 'time' => now()->subDays(13)->timestamp, 'relative_time_description' => '13 days ago'],
            ],
            [
                ['author_name' => 'Tyler Adams', 'rating' => 5, 'text' => 'Best bar crawl experience in Charlotte! The Trolley Pub is an absolute blast. Perfect for birthdays!', 'time' => now()->subDays(2)->timestamp, 'relative_time_description' => '2 days ago'],
                ['author_name' => 'Megan Baker', 'rating' => 5, 'text' => 'So much fun! Our driver and guide were hilarious. Great music and great vibes all around.', 'time' => now()->subDays(4)->timestamp, 'relative_time_description' => '4 days ago'],
                ['author_name' => 'Jason Nelson', 'rating' => 5, 'text' => 'We had our company team building event here and it was amazing. Everyone had a great time!', 'time' => now()->subDays(7)->timestamp, 'relative_time_description' => '7 days ago'],
            ],
        ];

        foreach ($proData as $index => &$item) {
            $item['reviews'] = $sampleReviews[$index] ?? $sampleReviews[0];
        }

        return $proData;
    }

    /**
     * Get page 2 results (for load more / pagination)
     */
    private function getPage2Results(string $plan): array
    {
        $page2Starter = [
            [
                'address' => '235 N Tryon St, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Nightly Spirits - Ghost Tours & Pub Crawls',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJrcbjKr6hVogRlbGN9CYubv4',
                'rating' => 4.6,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 16,
                'website' => null,
            ],
            [
                'address' => '6145 Robley Tate Ct, Charlotte, NC 28270, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Travel Now with April - Dream Vacations',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJrznzmYQhVIgRZYqIlGy-_OI',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 85,
                'website' => null,
            ],
            [
                'address' => '119 Brevard Court, Charlotte, NC 28202, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Court Travel Ltd.',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJnV963C6gVogRoxCUSal4XYo',
                'rating' => 3.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 14,
                'website' => null,
            ],
            [
                'address' => '3040 Kirkwall Ln, Indian Land, SC 29707, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Mystical Dream Travel Luxury Family Vacations',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJ0dHnt8KBVogRToKH85eqpUE',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 45,
                'website' => null,
            ],
            [
                'address' => '301 Camp Rd, Charlotte, NC 28206, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'LaZoom Tours - Charlotte',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJpU-cwJcrOA4RcMEslWui6kI',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 69,
                'website' => null,
            ],
            [
                'address' => '511 W Summit Ave, Charlotte, NC 28203, USA',
                'emails' => [],
                'latest_review_date' => null,
                'name' => 'Pedal Pub Charlotte',
                'opening_hours' => null,
                'phone' => null,
                'profile' => 'https://www.google.com/maps/place/?q=place_id:ChIJnzbsWUufVogR9cilEejEDfo',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 130,
                'website' => null,
            ],
        ];

        if ($plan === 'starter') {
            return $page2Starter;
        }

        // Growth/Pro page 2
        $page2Growth = [
            [
                'address' => '235 N Tryon St, Charlotte, NC 28202, USA',
                'emails' => ['info@nightlyspirits.com'],
                'latest_review_date' => now()->subDays(60)->timestamp,
                'name' => 'Nightly Spirits - Ghost Tours & Pub Crawls',
                'opening_hours' => null,
                'phone' => '+1 844-678-8687',
                'profile' => 'https://maps.google.com/?cid=18333641877918429589',
                'rating' => 4.6,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/nightlyspirits/', 'https://www.facebook.com/NightlySpirits/'],
                'total_reviews' => 16,
                'website' => 'https://nightlyspirits.com/charlotte-ghost-tours/',
            ],
            [
                'address' => '6145 Robley Tate Ct, Charlotte, NC 28270, USA',
                'emails' => [],
                'latest_review_date' => now()->subDays(90)->timestamp,
                'name' => 'Travel Now with April - Dream Vacations',
                'opening_hours' => ['Monday: 7:00 AM - 10:30 PM', 'Tuesday: 7:00 AM - 10:30 PM', 'Wednesday: 7:00 AM - 10:30 PM', 'Thursday: 7:00 AM - 10:30 PM', 'Friday: Open 24 hours', 'Saturday: Open 24 hours', 'Sunday: 7:00 AM - 10:30 PM'],
                'phone' => '+1 704-968-0151',
                'profile' => 'https://maps.google.com/?cid=16356157320260520549',
                'rating' => 5,
                'reviews' => [],
                'social_links' => [],
                'total_reviews' => 85,
                'website' => 'https://travelnowwithapril.com/',
            ],
            [
                'address' => '119 Brevard Court, Charlotte, NC 28202, USA',
                'emails' => ['contact@courttravel.com'],
                'latest_review_date' => now()->subDays(4)->timestamp,
                'name' => 'Court Travel Ltd.',
                'opening_hours' => ['Monday: 10:00 AM - 6:00 PM', 'Tuesday: 10:00 AM - 6:00 PM', 'Wednesday: 10:00 AM - 6:00 PM', 'Thursday: 10:00 AM - 6:00 PM', 'Friday: 10:00 AM - 6:00 PM', 'Saturday: Closed', 'Sunday: Closed'],
                'phone' => '+1 704-372-4231',
                'profile' => 'https://maps.google.com/?cid=9970257818547392675',
                'rating' => 3.9,
                'reviews' => [],
                'social_links' => ['https://www.facebook.com/Court-Travel-Ltd/'],
                'total_reviews' => 14,
                'website' => 'http://www.courttravel.com/',
            ],
            [
                'address' => '3040 Kirkwall Ln, Indian Land, SC 29707, USA',
                'emails' => ['Colleen@MysticalDreamTravel.com'],
                'latest_review_date' => now()->subDays(2)->timestamp,
                'name' => 'Mystical Dream Travel Luxury Family Vacations',
                'opening_hours' => ['Monday: Open 24 hours', 'Tuesday: Open 24 hours', 'Wednesday: Open 24 hours', 'Thursday: Open 24 hours', 'Friday: Open 24 hours', 'Saturday: 9:00 AM - 2:00 PM', 'Sunday: Closed'],
                'phone' => '+1 803-203-5101',
                'profile' => 'https://maps.google.com/?cid=4730374553225101902',
                'rating' => 5,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/mystical_dream_travel/', 'https://www.facebook.com/mysticaldreamtravel'],
                'total_reviews' => 45,
                'website' => 'http://mysticaldreamtravel.com/colleen',
            ],
            [
                'address' => '301 Camp Rd, Charlotte, NC 28206, USA',
                'emails' => [],
                'latest_review_date' => now()->subDays(12)->timestamp,
                'name' => 'LaZoom Tours - Charlotte',
                'opening_hours' => ['Monday: 9:00 AM - 10:00 PM', 'Tuesday: 9:00 AM - 10:00 PM', 'Wednesday: 9:00 AM - 10:00 PM', 'Thursday: 9:00 AM - 10:00 PM', 'Friday: 9:00 AM - 10:00 PM', 'Saturday: 10:00 AM - 6:00 PM', 'Sunday: 9:00 AM - 10:00 PM'],
                'phone' => '+1 828-225-6932',
                'profile' => 'https://maps.google.com/?cid=4821844934001475952',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/lazoom/', 'https://www.facebook.com/LaZoomtours'],
                'total_reviews' => 69,
                'website' => 'https://lazoomtours.com/charlotte',
            ],
            [
                'address' => '511 W Summit Ave, Charlotte, NC 28203, USA',
                'emails' => ['charlotte@pedalpub.com'],
                'latest_review_date' => now()->subDays(22)->timestamp,
                'name' => 'Pedal Pub Charlotte',
                'opening_hours' => ['Monday: 10:00 AM - 10:00 PM', 'Tuesday: 10:00 AM - 10:00 PM', 'Wednesday: 10:00 AM - 10:00 PM', 'Thursday: 10:00 AM - 10:00 PM', 'Friday: 10:00 AM - 10:00 PM', 'Saturday: 10:00 AM - 10:00 PM', 'Sunday: 10:00 AM - 10:00 PM'],
                'phone' => '+1 704-877-8067',
                'profile' => 'https://maps.google.com/?cid=18018274185186756853',
                'rating' => 4.9,
                'reviews' => [],
                'social_links' => ['https://www.instagram.com/pedalpubcharlotte/', 'https://www.facebook.com/pedalpubcharlotte/'],
                'total_reviews' => 130,
                'website' => 'https://www.pedalpub.com/charlotte-nc/',
            ],
        ];

        if ($plan === 'growth') {
            return $page2Growth;
        }

        // Pro page 2 - with reviews
        $proPage2 = $page2Growth;
        $page2Reviews = [
            [
                ['author_name' => 'Mark Stevens', 'rating' => 5, 'text' => 'Incredible ghost tour! Really spooky and informative. The pub stops were a great bonus.', 'time' => now()->subDays(6)->timestamp, 'relative_time_description' => '6 days ago'],
                ['author_name' => 'Laura Collins', 'rating' => 4, 'text' => 'Fun evening out. The stories were great and the pubs we visited had awesome drinks.', 'time' => now()->subDays(14)->timestamp, 'relative_time_description' => '14 days ago'],
            ],
            [
                ['author_name' => 'Karen Phillips', 'rating' => 5, 'text' => 'April is the best travel planner! She made our dream vacation a reality. So grateful.', 'time' => now()->subDays(30)->timestamp, 'relative_time_description' => 'a month ago'],
                ['author_name' => 'Steven Turner', 'rating' => 5, 'text' => 'Excellent service and attention to detail. Our cruise was perfectly planned.', 'time' => now()->subDays(45)->timestamp, 'relative_time_description' => '45 days ago'],
            ],
            [
                ['author_name' => 'Diana Morgan', 'rating' => 4, 'text' => 'Professional travel service. They helped us with a complex multi-city itinerary.', 'time' => now()->subDays(4)->timestamp, 'relative_time_description' => '4 days ago'],
                ['author_name' => 'Paul Cooper', 'rating' => 3, 'text' => 'Decent service, but communication could be better. The trip itself was fine.', 'time' => now()->subDays(20)->timestamp, 'relative_time_description' => '20 days ago'],
            ],
            [
                ['author_name' => 'Rebecca Howard', 'rating' => 5, 'text' => 'Colleen planned the most magical Disney trip for our family! Every detail was perfect.', 'time' => now()->subDays(2)->timestamp, 'relative_time_description' => '2 days ago'],
                ['author_name' => 'William Ward', 'rating' => 5, 'text' => 'Luxury travel at its finest. Mystical Dream Travel exceeded all our expectations.', 'time' => now()->subDays(9)->timestamp, 'relative_time_description' => '9 days ago'],
                ['author_name' => 'Samantha Cox', 'rating' => 5, 'text' => 'We have used this agency three times now and each trip gets better!', 'time' => now()->subDays(15)->timestamp, 'relative_time_description' => '15 days ago'],
            ],
            [
                ['author_name' => 'Eric Richardson', 'rating' => 5, 'text' => 'LaZoom is an absolute must-do! The comedy tour had us laughing the entire time.', 'time' => now()->subDays(12)->timestamp, 'relative_time_description' => '12 days ago'],
                ['author_name' => 'Hannah Brooks', 'rating' => 5, 'text' => 'So much fun! Our guide was hilarious and we learned so much about Charlotte.', 'time' => now()->subDays(19)->timestamp, 'relative_time_description' => '19 days ago'],
            ],
            [
                ['author_name' => 'Ryan Foster', 'rating' => 5, 'text' => 'Pedal Pub was the highlight of our bachelor party! Everyone had an incredible time.', 'time' => now()->subDays(5)->timestamp, 'relative_time_description' => '5 days ago'],
                ['author_name' => 'Olivia Bennett', 'rating' => 5, 'text' => 'Such a unique and fun way to explore Charlotte! The staff made it extra special.', 'time' => now()->subDays(10)->timestamp, 'relative_time_description' => '10 days ago'],
                ['author_name' => 'Derek Hayes', 'rating' => 4, 'text' => 'Great experience overall. Book ahead because they fill up fast on weekends!', 'time' => now()->subDays(22)->timestamp, 'relative_time_description' => '22 days ago'],
            ],
        ];

        foreach ($proPage2 as $index => &$item) {
            $item['reviews'] = $page2Reviews[$index] ?? $page2Reviews[0];
        }

        return $proPage2;
    }
}
