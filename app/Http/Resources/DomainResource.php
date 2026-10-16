<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'booking_date' => $this->booking_date,
            'expiry_date' => $this->expiry_date,
            'total_amount' => (float)$this->total_amount,
            'system_status' => $this->system_status,
            'manual_status' => $this->manual_status,
            'last_contacted_at' => $this->last_contacted_at,
            'next_followup_at' => $this->next_followup_at,
            'renewal_date' => $this->renewal_date,
            'remark' => $this->remark,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'sales_person' => new UserResource($this->whenLoaded('salesPerson')),
            'renewed_by' => new UserResource($this->whenLoaded('renewedBy')),
            'payment_summary' => $this->payment_summary,
            'days_to_expiry' => $this->days_to_expiry,
            'priority' => $this->priority,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
