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
            'file' => 'required_without:domains|file|mimes:xlsx,xls,csv,txt',
            'domains' => 'required_without:file|array|min:1',
            'domains.*.name' => 'required_with:domains|string|distinct',
            'domains.*.id' => 'nullable|integer',
            'domains.*.company_name' => 'nullable|string|max:255',
            'domains.*.booking_date' => 'nullable|date',
            'domains.*.expiry_date' => 'nullable|date',
            'domains.*.total_amount' => 'nullable|numeric|min:0',
            'domains.*.sales_person_id' => 'nullable|exists:users,id',
            'domains.*.branch_id' => 'nullable|exists:branches,id',
            'domains.*.manual_status' => 'nullable|string|max:255',
            'domains.*.next_followup_at' => 'nullable|date',
            'domains.*.last_contacted_at' => 'nullable|date',
            'domains.*.renewal_date' => 'nullable|date',
            'domains.*.renewed_by' => 'nullable|exists:users,id',
            'domains.*.remark' => 'nullable|string',
            'override_payment_check' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without' => 'Please provide an Excel file (.xlsx, .xls, .csv) or a domains list.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.mimes' => 'The file must be an Excel or CSV file (.xlsx, .xls, .csv).',
            'domains.required_without' => 'Please provide a domains list or upload an Excel file.',
            'domains.*.name.distinct' => 'Duplicate domain name ":input" found in the submitted list.',
        ];
    }
}
