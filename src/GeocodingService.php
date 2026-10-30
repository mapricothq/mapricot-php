<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * $client->geocoding: the /geocoding/v1 endpoints.
 *
 * @phpstan-import-type Point from Client
 * @phpstan-import-type Places from Client
 * @phpstan-import-type SearchQuery from Client
 * @phpstan-import-type SearchBatch from Client
 */
final class GeocodingService
{
    public function __construct(private \Closure $get, private \Closure $post, private \Closure $query)
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
     * Run up to 100 searches in one request, each an array of search's parameters, such as ['q' => '…', 'limit' => 1].
     * Each completed search is billed as one request. 'responses' answers them in order, each with 'results' and
     * 'attribution', or an 'error' with the code and message the search would get on its own, which isn't thrown.
     *
     * @param list<SearchQuery> $queries
     * @return SearchBatch
     */
    public function searchBatch(array $queries): array
    {
        return ($this->post)('/geocoding/v1/search/batch', ['queries' => array_map($this->query, $queries)]);
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
