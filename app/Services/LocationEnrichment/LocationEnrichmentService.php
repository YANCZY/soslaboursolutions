<?php

namespace App\Services\LocationEnrichment;

class LocationEnrichmentService
{
    /**
     * @param array{id: string, city: string, suburb: string} $payload
     * @return array{id: string, city: string, suburb: string}
     */
    public function handle(array $payload): array
    {
        /*
         * Geoapify lookup and Zoho CRM update logic will be added here
         * during the next implementation stages.
         */
        return $payload;
    }
}
