<?php

namespace App\Http\Requests\OfferBuilder;

use App\Models\OfferStrategy;
use App\Services\OfferBuilder\OfferPromptBuilderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Save an AI Offer Builder draft (create or update). Only the known fields of the
 * selected strategy are kept from "inputs"; everything is plain text with limits.
 */
class SaveBuilderOfferRequest extends FormRequest
{
    private ?OfferStrategy $strategyModel = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $limits = config('offer_builder.limits');

        return [
            'strategy' => ['required', 'string', Rule::exists('offer_strategies', 'slug')->where('is_active', true)],
            'title' => ['nullable', 'string', 'max:' . $limits['title']],
            'inputs' => ['nullable', 'array'],
            'inputs.*' => ['nullable', 'string', 'max:' . $limits['long_field']],
            'additional_instructions' => ['nullable', 'string', 'max:' . $limits['additional_instructions']],
            'custom_prompt' => ['nullable', 'string', 'max:' . $limits['custom_prompt']],
        ];
    }

    public function messages(): array
    {
        return [
            'strategy.*' => 'Please choose an offer strategy.',
            'inputs.*.max' => 'One of the fields is too long (max :max characters).',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->any()) {
                return;
            }
            $short = (int) config('offer_builder.limits.field');
            foreach ($this->fieldDefs() as $f) {
                $value = (string) ($this->input('inputs.' . $f['key']) ?? '');
                if (!in_array($f['type'], OfferPromptBuilderService::LONG_TYPES, true) && mb_strlen($value) > $short) {
                    $v->errors()->add('inputs.' . $f['key'], "\"{$f['label']}\" is too long (max {$short} characters).");
                }
                if (($f['type'] ?? '') === 'select' && $value !== '' && !in_array($value, $f['options'] ?? [], true)) {
                    $v->errors()->add('inputs.' . $f['key'], "Please choose a valid option for \"{$f['label']}\".");
                }
            }
        });
    }

    public function strategy(): OfferStrategy
    {
        return $this->strategyModel ??= OfferStrategy::with('category')->where('slug', $this->input('strategy'))->firstOrFail();
    }

    private function fieldDefs(): array
    {
        return app(OfferPromptBuilderService::class)->fields($this->strategy());
    }

    /** Known field values only, trimmed; empty values dropped. */
    public function inputs(): array
    {
        $raw = (array) $this->input('inputs', []);
        $clean = [];
        foreach ($this->fieldDefs() as $f) {
            $value = trim(str_replace("\r\n", "\n", (string) ($raw[$f['key']] ?? '')));
            if ($value !== '') {
                $clean[$f['key']] = $value;
            }
        }

        return $clean;
    }

    /** Custom prompt only when the user really edited it (identical to the generated one = not custom). */
    public function customPrompt(): ?string
    {
        $custom = trim(str_replace("\r\n", "\n", (string) $this->input('custom_prompt', '')));

        return $custom === '' ? null : $custom;
    }
}
