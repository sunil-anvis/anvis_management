<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'username'            => $this->username,
            'first_name'          => $this->first_name,
            'last_name'           => $this->last_name,
            'email'               => $this->email,
            'status'              => $this->status,
            'role'                => $this->whenLoaded('role', fn() => [
                'id'   => $this->role->id,
                'name' => $this->role->name,
            ]),
            'branch'              => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'department'          => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'primary_lead_id'     => $this->primary_lead_id,
            'secondary_lead_id'   => $this->secondary_lead_id,
        ];
    }
}
