<?php

namespace App\Http\Requests\AiSalesProfile;

use App\Models\UserOffer;
use App\Support\SkillCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Create / update a skill, service or offer. */
class SaveUserOfferRequest extends FormRequest
{
    protected $errorBag = 'offer';

    public function authorize(): bool
    {
        return Gate::allows('manage-sales-profile');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'string', Rule::in(array_keys(UserOffer::TYPES))],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_hint' => ['nullable', 'string', 'max:100'],
            'ideal_for' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];

        // Skills are picked from the catalog: main skill → one of its sub skills (saved as the title)
        if ($this->input('type') === UserOffer::TYPE_SKILL) {
            $main = (string) $this->input('main_skill');
            $rules['main_skill'] = ['required', 'string', Rule::in(array_keys(SkillCatalog::all()))];
            $rules['title'] = ['required', 'string', Rule::in(SkillCatalog::all()[$main] ?? [])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'main_skill.*' => 'Please choose a main skill from the list.',
            'title.in' => 'Please choose a sub skill from the list.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->input('type') !== UserOffer::TYPE_SKILL || $v->errors()->any()) {
                return;
            }
            $exists = UserOffer::ownedBy($this->user()->getAccountOwner())
                ->where('type', UserOffer::TYPE_SKILL)
                ->where('title', $this->input('title'))
                ->when($this->route('offer'), fn ($q, $id) => $q->whereKeyNot((int) $id))
                ->exists();
            if ($exists) {
                $v->errors()->add('title', 'You have already added this skill.');
            }
        });
    }

    /** main_skill only drives validation; it is not stored (it is looked up from the title). */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if ($key === null) {
            unset($data['main_skill']);
        }

        return $data;
    }
}
