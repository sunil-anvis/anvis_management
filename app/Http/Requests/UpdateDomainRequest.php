<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDomainRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'booking_date' => 'sometimes|required|date',
            'expiry_date' => 'sometimes|required|date|after_or_equal:booking_date',
            'total_amount' => 'sometimes|numeric|min:0',
            'sales_person_id' => 'sometimes|nullable|exists:users,id',
            'manual_status' => 'sometimes|nullable|string|max:255',
            'last_contacted_at' => 'sometimes|nullable|date',
            'next_followup_at' => 'sometimes|nullable|date',
            'renewal_date' => 'sometimes|nullable|date',
            'renewed_by' => 'sometimes|nullable|exists:users,id',
            'remark' => 'sometimes|nullable|string',
            'branch_id' => 'sometimes|nullable|exists:branches,id',
        ];
    }
}
