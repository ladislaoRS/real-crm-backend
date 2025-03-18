<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'status' => $this->status,
            'status_notes' => $this->status_notes,
            'status_updated_at' => $this->status_updated_at ? $this->status_updated_at->toIso8601String() : null,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
