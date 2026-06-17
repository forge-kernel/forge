<?php

namespace App\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
class LocationService
{
    /** @return array<int, array{value:string,label:string}> */
    public function countries(): array
    {
        return [
            ['value' => 'US', 'label' => 'United States'],
            ['value' => 'CA', 'label' => 'Canada'],
        ];
    }

    /** @return array<int, array{value:string,label:string}> */
    public function states(string $country): array
    {
        return match ($country) {
            'US' => [
                ['value' => 'CA', 'label' => 'California'],
                ['value' => 'NY', 'label' => 'New York'],
            ],
            'CA' => [
                ['value' => 'ON', 'label' => 'Ontario'],
                ['value' => 'BC', 'label' => 'British Columbia'],
            ],
            default => [],
        };
    }

    /** @return array<int, array{value:string,label:string}> */
    public function cities(string $country, string $state): array
    {
        return match ([$country, $state]) {
            ['US','CA'] => [
                ['value' => 'sf', 'label' => 'San Francisco'],
                ['value' => 'la', 'label' => 'Los Angeles'],
            ],
            ['US','NY'] => [
                ['value' => 'nyc', 'label' => 'New York City'],
                ['value' => 'buf', 'label' => 'Buffalo'],
            ],
            ['CA','ON'] => [
                ['value' => 'tor', 'label' => 'Toronto'],
                ['value' => 'ott', 'label' => 'Ottawa'],
            ],
            ['CA','BC'] => [
                ['value' => 'van', 'label' => 'Vancouver'],
                ['value' => 'vic', 'label' => 'Victoria'],
            ],
            default => [],
        };
    }
}
