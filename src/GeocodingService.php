<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * $client->geocoding: the /geocoding/v1 endpoints.
 *
 * @phpstan-import-type Point from Client
 * @phpstan-import-type Places from Client
 */
final class GeocodingService
{
    public function __construct(private \Closure $get)
    {
    }

    /**
     * Find addresses and places matching free text.
     *
     * @param Point|null $focus
     * @param string|list<string>|null $country
     * @return Places
     */
    public function search(string $q, ?array $focus = null, string|array|null $country = null, ?int $limit = null, ?string $lang = null): array
    {
        return ($this->get)('/geocoding/v1/search', ['q' => $q, 'focus' => $focus, 'country' => $country, 'limit' => $limit, 'lang' => $lang]);
    }

    /**
     * Suggest addresses and places as someone types.
     *
     * @param Point|null $focus
     * @param string|list<string>|null $country
     * @return Places
     */
    public function autocomplete(string $q, ?array $focus = null, string|array|null $country = null, ?int $limit = null, ?string $lang = null): array
    {
        return ($this->get)('/geocoding/v1/autocomplete', ['q' => $q, 'focus' => $focus, 'country' => $country, 'limit' => $limit, 'lang' => $lang]);
    }

    /**
     * Find the address nearest a coordinate, or the area around it.
     *
     * @return Places
     */
    public function reverse(float $lat, float $lon, ?int $limit = null, ?string $lang = null): array
    {
        return ($this->get)('/geocoding/v1/reverse', ['lat' => $lat, 'lon' => $lon, 'limit' => $limit, 'lang' => $lang]);
    }
}
