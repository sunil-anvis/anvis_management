<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLower = strtolower(trim($this->status ?? ''));
        $isCompleted = in_array($statusLower, ['completed', 'processed', 'approved']);

        $hasFile = false;
        if ($isCompleted && $this->invoice_file_path) {
            $hasFile = file_exists(storage_path('app/public/' . $this->invoice_file_path))
                    || file_exists(storage_path('app/public_storage/' . $this->invoice_file_path));
        }

        return [
            'id' => $this->id,
            'amount' => (float)$this->amount,
            'payment_date' => $this->payment_date,
            'status' => $this->status,
            'invoice_file_path' => ($isCompleted && $hasFile) ? $this->invoice_file_path : null,
            'invoice_file_url' => ($isCompleted && $hasFile) ? asset('public_storage/' . $this->invoice_file_path) : null,
            'company_name' => $this->company_name,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
