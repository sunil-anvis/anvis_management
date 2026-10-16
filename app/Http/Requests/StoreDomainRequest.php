<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'booking_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:booking_date',
            'total_amount' => 'required|numeric|min:0',
            'sales_person_id' => 'nullable|exists:users,id',
            'manual_status' => 'nullable|string|max:255',
            'last_contacted_at' => 'nullable|date',
            'next_followup_at' => 'nullable|date',
            'renewal_date' => 'nullable|date',
            'renewed_by' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
            'remark' => 'nullable|string',
        ];
    }
}
