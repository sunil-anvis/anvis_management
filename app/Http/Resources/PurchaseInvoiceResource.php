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
        return [
            'id' => $this->id,
            'amount' => (float)$this->amount,
            'payment_date' => $this->payment_date,
            'status' => $this->status,
            'invoice_file_path' => $this->invoice_file_path,
            'invoice_file_url' => $this->invoice_file_path ? asset('storage/' . $this->invoice_file_path) : null,
            'company_name' => $this->company_name,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
