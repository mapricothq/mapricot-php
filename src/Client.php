<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * The official client for the Mapricot API: address and place search, reverse geocoding, nearby places, routing,
 * and IP geolocation. Each method returns the API's JSON as an array, as in https://mapricot.com/api-reference/.
 *
 * @phpstan-type Point array{lat: float, lon: float}
 * @phpstan-type Attribution array{source: string, text: string, url: string}
 * @phpstan-type Address array{houseNumber?: string, street?: string, postalCode?: string, locality?: string, region?: string, regionCode?: string, country?: string, countryCode?: string}
 * @phpstan-type Place array{id: string, type: string, name: string, label: string, lat: float, lon: float, address?: Address, categories?: list<string>, distanceMeters?: int, source: string}
 * @phpstan-type Places array{results: list<Place>, attribution: list<Attribution>}
 * @phpstan-type PlaceResult array{result: Place, attribution: list<Attribution>}
 * @phpstan-type Leg array{distanceMeters: int, durationSeconds: int}
 * @phpstan-type LineString array{type: 'LineString', coordinates: list<array{float, float}>}
 * @phpstan-type Route array{distanceMeters: int, durationSeconds: int, polyline?: string, geometry?: LineString, legs: list<Leg>}
 * @phpstan-type Routes array{routes: list<Route>, attribution?: list<Attribution>}
 * @phpstan-type Isochrone array{type: 'Feature', properties: array{minutes: int}, geometry: array{type: 'Polygon'|'MultiPolygon', coordinates: list<mixed>}}
 * @phpstan-type Isochrones array{type: 'FeatureCollection', features: list<Isochrone>, attribution?: list<Attribution>}
 * @phpstan-type IpLocation array{ip: string, found: bool, continentCode?: string, countryCode?: string, countryName?: string, regionCode?: string, regionName?: string, city?: string, location?: Point, source: string, attributionUrl: string, licenseUrl: string, datasetRelease: string}
 * @phpstan-type Account array{id: string, name: string, status: string, createdAt: string}
 */
final class Client
{
    public const VERSION = '0.6.0';

    /**
     * @param int $timeout Seconds per attempt.
     * @param int $maxRetries Retries for 503 and for 429 with too many requests in progress.
     */
    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://api.mapricot.com',
        private int $timeout = 30,
        private int $maxRetries = 2,
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('Pass $apiKey, a Mapricot key.');
        }
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
        return $this->get('/geocoding/v1/search', ['q' => $q, 'focus' => $focus, 'country' => $country, 'limit' => $limit, 'lang' => $lang]);
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
        return $this->get('/geocoding/v1/autocomplete', ['q' => $q, 'focus' => $focus, 'country' => $country, 'limit' => $limit, 'lang' => $lang]);
    }

    /**
     * Find the address nearest a coordinate, or the area around it.
     *
     * @return Places
     */
    public function reverse(float $lat, float $lon, ?int $limit = null, ?string $lang = null): array
    {
        return $this->get('/geocoding/v1/reverse', ['lat' => $lat, 'lon' => $lon, 'limit' => $limit, 'lang' => $lang]);
    }

    /**
     * List places around a coordinate, optionally by category.
     *
     * @param string|list<string>|null $category
     * @return Places
     */
    public function nearby(float $lat, float $lon, string|array|null $category = null, ?int $radius = null, ?int $limit = null, ?string $lang = null): array
    {
        return $this->get('/places/v1/nearby', ['lat' => $lat, 'lon' => $lon, 'category' => $category, 'radius' => $radius, 'limit' => $limit, 'lang' => $lang]);
    }

    /**
     * Look up one place by its Mapricot ID.
     *
     * @return PlaceResult
     */
    public function place(string $id, ?string $lang = null): array
    {
        return $this->get('/places/v1/' . rawurlencode($id), ['lang' => $lang]);
    }

    /**
     * Find a route through 2 to 20 waypoints.
     *
     * @param list<Point> $waypoints
     * @param 'drive'|'walk'|'bicycle'|null $mode
     * @param 'polyline'|'polyline6'|'geojson'|null $geometries
     * @return Routes
     */
    public function route(array $waypoints, ?string $mode = null, ?string $geometries = null): array
    {
        $body = array_filter(['waypoints' => $waypoints, 'mode' => $mode, 'geometries' => $geometries], fn ($value) => $value !== null);
        return $this->request('POST', $this->baseUrl . '/routing/v1/route', json_encode($body, JSON_THROW_ON_ERROR));
    }

    /**
     * The areas reachable from a location within 1 to 4 times, each 1 to 60 minutes, one polygon per time, largest first.
     *
     * @param Point $location
     * @param list<int> $minutes
     * @param 'drive'|'walk'|'bicycle'|null $mode
     * @return Isochrones
     */
    public function isochrone(array $location, array $minutes, ?string $mode = null): array
    {
        $body = array_filter(['location' => $location, 'minutes' => $minutes, 'mode' => $mode], fn ($value) => $value !== null);
        return $this->request('POST', $this->baseUrl . '/routing/v1/isochrone', json_encode($body, JSON_THROW_ON_ERROR));
    }

    /**
     * Approximate location of a public IP address.
     *
     * @return IpLocation
     */
    public function ip(string $address): array
    {
        return $this->get('/ip-geolocation/v1/' . rawurlencode($address));
    }

    /**
     * The key's account. Not billed, so it checks a key for free.
     *
     * @return Account
     */
    public function account(): array
    {
        return $this->get('/account');
    }

    /** @param array<string, mixed> $params */
    private function get(string $path, array $params = []): array
    {
        $query = [];
        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }
            $query[$name] = match (true) {
                is_array($value) && array_is_list($value) => implode(',', $value),
                is_array($value) => $value['lat'] . ',' . $value['lon'],
                default => (string) $value,
            };
        }
        $url = $this->baseUrl . $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
        return $this->request('GET', $url);
    }

    private function request(string $method, string $url, ?string $body = null): array
    {
        $headers = ['Authorization: Bearer ' . $this->apiKey, 'User-Agent: mapricot-php/' . self::VERSION];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        for ($attempt = 0; ; $attempt++) {
            $requestId = null;
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HEADERFUNCTION => function ($curl, string $header) use (&$requestId): int {
                    if (stripos($header, 'X-Request-Id:') === 0) {
                        $requestId = trim(substr($header, 13));
                    }
                    return strlen($header);
                },
            ]);
            if ($body !== null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            }
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($curl);
            if ($response === false) {
                throw new \RuntimeException('Mapricot request failed: ' . $curlError);
            }
            if ($status === 200) {
                return json_decode($response, true, flags: JSON_THROW_ON_ERROR);
            }
            $error = MapricotException::fromResponse($status, $response, $requestId);
            if ($attempt >= $this->maxRetries || !$error->retryable()) {
                throw $error;
            }
            usleep((int) (500_000 * 2 ** $attempt));
        }
    }
}
