<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmptyFinancialAuditRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phrase = mb_strtoupper(trim((string) $this->input('phrase_confirmation')));
        $phrase = preg_replace('/\s+/', ' ', str_replace(["'", '`', '‘'], '’', $phrase));

        $this->merge(['phrase_confirmation' => $phrase]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('viewFinancialAudit') ?? false;
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['accepted'],
            'phrase_confirmation' => ['required', 'in:VIDER LE JOURNAL D’AUDIT'],
        ];
    }

    public function messages(): array
    {
        return [
            'phrase_confirmation.in' => 'Veuillez saisir exactement : VIDER LE JOURNAL D’AUDIT.',
        ];
    }
}
