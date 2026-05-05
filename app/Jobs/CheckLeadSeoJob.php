<?php

namespace App\Jobs;

use App\Models\AdminApiKey;
use App\Models\SavedLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class CheckLeadSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(public readonly int $leadId) {}

    public function handle(): void
    {
        $lead = SavedLead::find($this->leadId);

        // Skip if deleted, no website, or already checked
        if (!$lead || empty($lead->website) || $lead->seo_score !== null) {
            return;
        }

        // Bypass extension_mode scope — that only blocks Places API, not PageSpeed
        $apiKey = 'AIzaSyCE39nGPyHxB37_vAWufbum_7UpxusS90Y';

        try {
            $response = Http::timeout(30)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url'      => $lead->website,
                'key'      => $apiKey,
                'strategy' => 'mobile',
                'category' => 'performance',
            ]);

            $data  = $response->json();
            $score = null;

            if (isset($data['lighthouseResult']['categories']['performance']['score'])) {
                $score = (int) round($data['lighthouseResult']['categories']['performance']['score'] * 100);
            }

            $lead->update(['seo_score' => $score ?? -1]);
        } catch (\Exception $e) {
            $lead->update(['seo_score' => -1]);
        }
    }
}
