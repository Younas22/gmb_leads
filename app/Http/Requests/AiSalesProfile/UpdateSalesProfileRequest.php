<?php

namespace App\Http\Requests\AiSalesProfile;

use App\Support\SafeUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSalesProfileRequest extends FormRequest
{
    protected $errorBag = 'profile';

    public const MAX_PORTFOLIO_URLS = 10;
    public const MAX_LIST_ITEMS = 20;

    public function authorize(): bool
    {
        return Gate::allows('manage-sales-profile');
    }

    /** Textareas (one value per line, or comma-separated for short lists) → arrays. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'portfolio_urls' => $this->splitLines($this->input('portfolio_urls'), commas: false),
            'target_industries' => $this->splitLines($this->input('target_industries')),
            'target_locations' => $this->splitLines($this->input('target_locations')),
        ]);
    }

    public function rules(): array
    {
        $httpUrl = function (string $attribute, mixed $value, Closure $fail) {
            if (is_string($value) && $value !== '' && SafeUrl::http($value) === null) {
                $fail('Please enter a valid web address starting with http:// or https://.');
            }
        };

        return [
            'company_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:500', $httpUrl],
            'portfolio_urls' => ['nullable', 'array', 'max:' . self::MAX_PORTFOLIO_URLS],
            'portfolio_urls.*' => ['string', 'max:500', $httpUrl],
            'target_industries' => ['nullable', 'array', 'max:' . self::MAX_LIST_ITEMS],
            'target_industries.*' => ['string', 'max:100'],
            'target_locations' => ['nullable', 'array', 'max:' . self::MAX_LIST_ITEMS],
            'target_locations.*' => ['string', 'max:100'],
            'language' => ['required', 'string', Rule::in(array_keys(config('ai.languages')))],
            'tone' => ['required', 'string', Rule::in(array_keys(config('ai.tones')))],
            'signature' => ['nullable', 'string', 'max:1000'],
            'sender_name' => ['nullable', 'string', 'max:120'],
            'sender_postal_address' => ['nullable', 'string', 'max:500'],
            'ai_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'portfolio_urls' => 'portfolio links',
            'portfolio_urls.*' => 'portfolio link',
            'target_industries.*' => 'industry',
            'target_locations.*' => 'location',
            'ai_instructions' => 'AI instructions',
            'sender_postal_address' => 'postal address',
        ];
    }

    public function messages(): array
    {
        return [
            'portfolio_urls.max' => 'You can add up to ' . self::MAX_PORTFOLIO_URLS . ' portfolio links.',
            'target_industries.max' => 'You can add up to ' . self::MAX_LIST_ITEMS . ' industries.',
            'target_locations.max' => 'You can add up to ' . self::MAX_LIST_ITEMS . ' locations.',
        ];
    }

    /** Validated data with URLs normalized (http(s) only). */
    public function profileData(): array
    {
        $data = $this->validated();

        $data['website'] = SafeUrl::http($data['website'] ?? null);
        $data['portfolio_urls'] = array_values(array_filter(array_map(
            fn ($u) => SafeUrl::http($u),
            $data['portfolio_urls'] ?? []
        )));
        $data['target_industries'] = array_values($data['target_industries'] ?? []);
        $data['target_locations'] = array_values($data['target_locations'] ?? []);

        return $data;
    }

    private function splitLines(mixed $value, bool $commas = true): ?array
    {
        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value)) {
            $items = preg_split($commas ? '/[\r\n,]+/' : '/[\r\n]+/', $value) ?: [];
        } else {
            return null;
        }

        $items = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $items);
        $items = array_filter($items, fn ($v) => $v !== '' && $v !== null);

        // Case-insensitive de-duplication, original order kept
        $seen = [];
        $out = [];
        foreach ($items as $item) {
            $key = is_string($item) ? mb_strtolower($item) : serialize($item);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $item;
            }
        }

        return $out;
    }
}
