<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreFinancialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restoreFinancialDocument') ?? false;
    }

    public function rules(): array
    {
        return ['cascade' => ['nullable', 'boolean']];
    }
}
