<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFinancialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deleteFinancialDocument') ?? false;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
            'strategie' => ['required', 'in:individuelle,cascade'],
            'confirmation_comptable' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de suppression est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 10 caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 1000 caractères.',
            'confirmation_comptable.accepted' => 'Vous devez confirmer avoir vérifié les conséquences comptables.',
        ];
    }
}
