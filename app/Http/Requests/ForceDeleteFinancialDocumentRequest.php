<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForceDeleteFinancialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('forceDeleteFinancialDocument') ?? false;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
            'confirmation_comptable' => ['accepted'],
            'phrase_confirmation' => ['required', 'in:SUPPRIMER DÉFINITIVEMENT'],
        ];
    }
}
