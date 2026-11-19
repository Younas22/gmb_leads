<?php

namespace App\Http\Controllers\OfferBuilder;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferBuilder\SaveBuilderOfferRequest;
use App\Jobs\GenerateBuilderOffer;
use App\Models\BuilderOffer;
use App\Models\BuilderOfferGeneration;
use App\Models\OfferStrategy;
use App\Models\UserOffer;
use App\Services\Ai\AiService;
use App\Services\Ai\Outreach\AnalysisLauncher;
use App\Services\OfferBuilder\OfferPromptBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Offers inside the AI Sales Profile: pick an offer type → short pre-filled form →
 * "Generate with AI" (queued; the result is added to the profile Offers) or
 * "Save without AI". Every offer record is private to its user (BuilderOfferPolicy).
 */
class BuilderOfferController extends Controller
{
    /** Short form for one offer type (HTML, loaded into the Offers section). */
    public function quickForm(Request $request, OfferPromptBuilderService $prompts, AiService $ai)
    {
        $strategy = $this->strategy((string) $request->query('strategy'));
        $user = $request->user();
        $feature = (string) config('offer_builder.ai_feature', 'offer_builder');

        // The account's own Services for "Product or service" (title + price)
        $owner = $user->getAccountOwner();
        $services = UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_SERVICE)->active()->ordered()->get()
            ->map(fn (UserOffer $s) => ['title' => $s->title, 'price' => (string) $s->price_hint, 'description' => (string) $s->description])
            ->values()->all();
        $canAddService = Gate::allows('manage-sales-profile')
            && UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_SERVICE)->count() < UserOffer::limits(UserOffer::TYPE_SERVICE)['max'];

        return view('user.offer-builder._quick', [
            'services' => $services,
            'canAddService' => $canAddService,
            'strategy' => $strategy,
            'fields' => $prompts->fields($strategy),
            'essential' => $prompts->essentialKeys($strategy),
            'suggestions' => $prompts->suggestions($user, $strategy),
            'values' => $prompts->profilePrefill($user)['values'],
            'canGenerate' => $ai->isAvailable() && $ai->quota()->remaining($user, $feature) !== 0,
        ]);
    }

    /** The full prompt for the current answers ([placeholders] left for empty fields), to read or copy. */
    public function prompt(SaveBuilderOfferRequest $request, OfferPromptBuilderService $prompts): JsonResponse
    {
        $strategy = $request->strategy();

        return response()->json([
            'ok' => true,
            'prompt' => $prompts->resolve($strategy, $request->inputs(), trim((string) $request->input('additional_instructions')) ?: null, null),
            'missing' => $prompts->missing($strategy, $request->inputs(), null, null),
        ]);
    }

    /** Save the answers as an offer record (needed before generating). */
    public function store(SaveBuilderOfferRequest $request): JsonResponse
    {
        $user = $request->user();
        $max = (int) config('offer_builder.max_offers_per_user', 300);
        if (BuilderOffer::ownedBy($user)->count() >= $max) {
            return $this->fail("You can keep up to {$max} offers. Remove some old ones first.");
        }

        $offer = new BuilderOffer();
        $offer->user_id = $user->id;            // never from input
        $offer->status = BuilderOffer::STATUS_DRAFT;
        $this->fill($offer, $request);
        $offer->save();

        $prompts = app(OfferPromptBuilderService::class);

        return response()->json([
            'ok' => true,
            'id' => $offer->id,
            'generate_url' => route('user.offer-builder.offers.generate', $offer->id),
            'missing' => $prompts->missing($request->strategy(), (array) $offer->business_inputs, $offer->additional_instructions, null),
        ]);
    }

    /** Queue one AI generation. A finished generation is added to the profile Offers by the job. */
    public function generate(Request $request, int $offer, OfferPromptBuilderService $prompts, AiService $ai): JsonResponse
    {
        $item = $this->owned($offer, ['strategy.category']);
        $user = $request->user();
        $feature = (string) config('offer_builder.ai_feature', 'offer_builder');

        if (!$ai->isAvailable()) {
            return $this->fail('AI is not configured on this server yet. Use "Save without AI" for now.');
        }
        if ($ai->quota()->remaining($user, $feature) === 0) {
            return $this->fail($ai->quota()->limit($user, $feature) === 0
                ? 'Your current plan does not include AI offer generation. Use "Save without AI" instead.'
                : 'You have used all AI offer generations for this month.');
        }

        $missing = $prompts->missing($item->strategy, (array) $item->business_inputs, $item->additional_instructions, $item->custom_prompt);
        if ($missing !== []) {
            return response()->json(['ok' => false, 'missing' => $missing,
                'message' => 'Please fill in: ' . implode(', ', $missing) . '.'], 422);
        }

        $maxOpen = (int) config('offer_builder.max_open_generations_per_user', 3);
        $result = DB::transaction(function () use ($item, $user, $prompts, $maxOpen) {
            BuilderOffer::whereKey($item->id)->lockForUpdate()->first(); // a double click cannot queue two runs
            if ($item->generations()->whereIn('status', BuilderOfferGeneration::OPEN)->get()->reject->isStale()->isNotEmpty()) {
                return 'This offer is already being generated. Please wait for it to finish.';
            }
            $open = BuilderOfferGeneration::where('user_id', $user->id)->whereIn('status', BuilderOfferGeneration::OPEN)->get()->reject->isStale()->count();
            if ($open >= $maxOpen) {
                return "You already have {$open} offers generating. Stop one under \"AI offers in progress\" or wait for it to finish.";
            }

            $brief = $prompts->forAi($item->strategy, (array) $item->business_inputs, $item->additional_instructions, $item->custom_prompt);
            $item->forceFill([
                'generated_prompt' => $brief,
                'generation_metadata' => array_merge((array) $item->generation_metadata, [
                    'language' => $prompts->language((array) $item->business_inputs),
                ]),
            ])->save();

            $gen = new BuilderOfferGeneration();
            $gen->forceFill([
                'builder_offer_id' => $item->id,
                'user_id' => $user->id,
                'status' => BuilderOfferGeneration::STATUS_QUEUED,
                'prompt' => $brief,
            ])->save();

            return $gen;
        });

        if (is_string($result)) {
            return $this->fail($result, 429);
        }

        AnalysisLauncher::queue(new GenerateBuilderOffer($result->id));

        return response()->json([
            'ok' => true,
            'generation_id' => $result->id,
            'status_url' => route('user.offer-builder.offers.generation', [$item->id, $result->id]),
        ]);
    }

    /** Poll a generation. */
    public function generation(Request $request, int $offer, int $generation): JsonResponse
    {
        $item = $this->owned($offer);
        $gen = $item->generations()->whereKey($generation)->firstOrFail();

        if ($gen->isStale()) {
            $gen->markFailed('The AI worker did not finish this request. Please try again in a moment.');
            $gen->refresh();
        }

        return response()->json(['status' => $gen->status, 'message' => $gen->error_message]);
    }

    /**
     * "AI offers in progress": this user's queued/running generations (plus recent failures),
     * with why each one is waiting, so a stuck one can be found and stopped.
     */
    public function generations(Request $request): JsonResponse
    {
        $user = $request->user();

        // Expire runs the worker can no longer finish, so they stop counting toward the limit
        BuilderOfferGeneration::where('user_id', $user->id)->whereIn('status', BuilderOfferGeneration::OPEN)->get()
            ->filter->isStale()
            ->each->markFailed('The AI worker did not finish this request. Please try again in a moment.');

        $open = BuilderOfferGeneration::with('offer.strategy')->where('user_id', $user->id)
            ->whereIn('status', BuilderOfferGeneration::OPEN)->oldest('id')->get();
        $failed = BuilderOfferGeneration::with('offer.strategy')->where('user_id', $user->id)
            ->where('status', BuilderOfferGeneration::STATUS_FAILED)
            ->where('updated_at', '>=', now()->subDay())->latest('id')->limit(5)->get();

        $staleAfter = (int) config('offer_builder.stale_after_minutes', 15);

        return response()->json([
            'ok' => true,
            'max_open' => (int) config('offer_builder.max_open_generations_per_user', 3),
            'open' => $open->map(fn (BuilderOfferGeneration $g) => $this->generationRow($g, $staleAfter))->values(),
            'failed' => $failed->map(fn (BuilderOfferGeneration $g) => $this->generationRow($g, $staleAfter))->values(),
        ]);
    }

    /** Stop one queued/running generation (only the user's own). The worker skips or discards it. */
    public function stopGeneration(Request $request, int $generation): JsonResponse
    {
        $gen = BuilderOfferGeneration::where('user_id', $request->user()->id)->whereKey($generation)->firstOrFail();

        if (!$gen->isOpen()) {
            return $this->fail('This generation has already finished.');
        }
        $gen->markFailed(BuilderOfferGeneration::STOPPED_MESSAGE);

        return response()->json(['ok' => true, 'message' => 'Generation stopped.']);
    }

    /** Stop all of this user's queued/running generations. */
    public function stopAllGenerations(Request $request): JsonResponse
    {
        $open = BuilderOfferGeneration::where('user_id', $request->user()->id)->whereIn('status', BuilderOfferGeneration::OPEN)->get();
        $open->each->markFailed(BuilderOfferGeneration::STOPPED_MESSAGE);

        return response()->json(['ok' => true, 'stopped' => $open->count(),
            'message' => $open->isEmpty() ? 'Nothing was generating.' : "Stopped {$open->count()} generation(s)."]);
    }

    /** "Save without AI": the strategy example filled with the answers becomes a profile offer. */
    public function quickSave(SaveBuilderOfferRequest $request, OfferPromptBuilderService $prompts): JsonResponse
    {
        if (!Gate::allows('manage-sales-profile')) {
            return $this->fail('Only the account owner can change the AI Sales Profile.', 403);
        }
        $strategy = $request->strategy();
        $inputs = $request->inputs();
        if (($inputs['product_name'] ?? '') === '') {
            return $this->fail('Please choose your product or service first.');
        }

        $owner = $request->user()->getAccountOwner();
        $max = UserOffer::limits(UserOffer::TYPE_OFFER)['max'];
        if (UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_OFFER)->count() >= $max) {
            return $this->fail("You can have up to {$max} offers. Remove one first.");
        }

        $title = mb_substr(trim((string) $request->input('title')) ?: $strategy->name . ': ' . $inputs['product_name'], 0, 150);
        if (UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_OFFER)->where('title', $title)->exists()) {
            return $this->fail('You already have an offer with this name. Change the name and try again.');
        }

        $profileOffer = DB::transaction(function () use ($request, $owner, $strategy, $inputs, $title, $prompts) {
            $profileOffer = new UserOffer([
                'type' => UserOffer::TYPE_OFFER,
                'title' => $title,
                'description' => mb_substr($prompts->filledExample($strategy, $inputs), 0, 1000) ?: null,
                'price_hint' => isset($inputs['price']) ? mb_substr($inputs['price'], 0, 100) : null,
                'ideal_for' => isset($inputs['target_audience']) ? mb_substr($inputs['target_audience'], 0, 255) : null,
                'is_active' => true,
            ]);
            $profileOffer->owner_user_id = $owner->id;
            $profileOffer->sort_order = (int) UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_OFFER)->max('sort_order') + 1;
            $profileOffer->save();

            // Keep the answers, so the profile can show (and colour) what the user entered
            $record = new BuilderOffer();
            $record->user_id = $request->user()->id;
            $record->status = BuilderOffer::STATUS_DRAFT;
            $this->fill($record, $request);
            $record->title = $title;
            $record->generation_metadata = ['profile_offer_id' => $profileOffer->id, 'mode' => 'manual'];
            $record->save();

            return $profileOffer;
        });

        return response()->json(['ok' => true, 'message' => 'Offer saved. AI Outreach can now suggest it to leads.', 'id' => $profileOffer->id]);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function strategy(string $slug): OfferStrategy
    {
        return OfferStrategy::with('category')->where('is_active', true)->where('slug', $slug)->firstOrFail();
    }

    private function owned(int $id, array $with = []): BuilderOffer
    {
        $item = BuilderOffer::with($with)->findOrFail($id);
        Gate::authorize('update', $item);

        return $item;
    }

    private function fill(BuilderOffer $offer, SaveBuilderOfferRequest $request): void
    {
        $strategy = $request->strategy();
        $inputs = $request->inputs();
        $title = trim((string) $request->input('title'));
        if ($title === '') {
            $title = trim(($inputs['product_name'] ?? 'Untitled') . ' – ' . $strategy->name);
        }

        $offer->fill([
            'offer_strategy_id' => $strategy->id,
            'title' => mb_substr($title, 0, (int) config('offer_builder.limits.title')),
            'business_inputs' => $inputs,
            'additional_instructions' => trim((string) $request->input('additional_instructions')) ?: null,
            'custom_prompt' => null,
        ]);
    }

    private function generationRow(BuilderOfferGeneration $g, int $staleAfter): array
    {
        $since = $g->started_at ?? $g->created_at;
        $waitingMinutes = $g->created_at ? (int) $g->created_at->diffInMinutes(now()) : 0;

        $reason = match ($g->status) {
            BuilderOfferGeneration::STATUS_QUEUED => $waitingMinutes >= 2
                ? 'Waiting in the AI queue. The AI worker is busy or not running right now; it is stopped automatically after ' . $staleAfter . ' minutes.'
                : 'Waiting in the AI queue for its turn.',
            BuilderOfferGeneration::STATUS_RUNNING => 'The AI is writing this offer now.',
            default => (string) $g->error_message,
        };

        return [
            'id' => $g->id,
            'title' => $g->offer?->title ?: 'Offer #' . $g->builder_offer_id,
            'type' => $g->offer?->strategy?->name,
            'status' => $g->status,
            'reason' => $reason,
            'since' => $since?->diffForHumans(),
            'stopped' => $g->error_message === BuilderOfferGeneration::STOPPED_MESSAGE,
            'stop_url' => $g->isOpen() ? route('user.offer-builder.generations.stop', $g->id) : null,
        ];
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }
}
