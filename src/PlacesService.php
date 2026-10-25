<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * $client->places: the /places/v1 endpoints.
 *
 * @phpstan-import-type Places from Client
 * @phpstan-import-type PlaceResult from Client
 */
final class PlacesService
{
    public function __construct(private \Closure $get)
    {
    }

    /**
     * List places around a coordinate, optionally by category.
     *
     * @param string|list<string>|null $category
     * @return Places
     */
    public function nearby(float $lat, float $lon, string|array|null $category = null, ?int $radius = null, ?int $limit = null, ?string $lang = null): array
    {
        return ($this->get)('/places/v1/nearby', ['lat' => $lat, 'lon' => $lon, 'category' => $category, 'radius' => $radius, 'limit' => $limit, 'lang' => $lang]);
    }

    /**
     * Look up one place by its Mapricot ID.
     *
     * @return PlaceResult
     */
    public function get(string $id, ?string $lang = null): array
    {
        return ($this->get)('/places/v1/' . rawurlencode($id), ['lang' => $lang]);
    }
}
