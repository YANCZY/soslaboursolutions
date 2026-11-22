<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'company_type' => $this->company_type,
            'trade' => $this->trade,
            'industry' => $this->industry,
            'industry_description' => $this->industry_description,
            'phone' => $this->phone,
            'website' => $this->website,
            'company_address' => $this->company_address,
            'company_address_2' => $this->company_address_2,
            'company_address_city' => $this->company_address_city,
            'company_address_state' => $this->company_address_state,
            'company_address_zip' => $this->company_address_zip,
            'company_address_country' => $this->company_address_country,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
