<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain_ids' => 'required|array|min:1',
            'domain_ids.*' => 'exists:domains,id',
            'sales_person_id' => 'sometimes|nullable|exists:users,id',
            'manual_status' => 'sometimes|nullable|string|max:255',
            'branch_id' => 'sometimes|nullable|exists:branches,id',
            'next_followup_at' => 'sometimes|nullable|date',
            'remark' => 'sometimes|nullable|string',
        ];
    }
}
