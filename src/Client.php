<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * The official client for the Mapricot API: address and place search, reverse geocoding, nearby places, routing,
 * and IP geolocation. Methods are grouped by product, as in $client->geocoding->search(). Each returns the API's JSON
 * as an array, as in https://mapricot.com/api-reference/.
 *
 * @phpstan-type Point array{lat: float, lon: float}
 * @phpstan-type Attribution array{source: string, text: string, url: string}
 * @phpstan-type Address array{houseNumber?: string, street?: string, postalCode?: string, locality?: string, region?: string, regionCode?: string, country?: string, countryCode?: string}
 * @phpstan-type Place array{id: string, type: string, name: string, label: string, lat: float, lon: float, address?: Address, categories?: list<string>, phones?: list<string>, websites?: list<string>, socials?: list<string>, brand?: string, openingHours?: string, distanceMeters?: int, source: string}
 * @phpstan-type Places array{results: list<Place>, attribution: list<Attribution>}
 * @phpstan-type PlaceResult array{result: Place, attribution: list<Attribution>}
 * @phpstan-type SearchQuery array{q: string, focus?: Point, country?: string|list<string>, limit?: int, lang?: string}
 * @phpstan-type SearchResponse array{results?: list<Place>, attribution?: list<Attribution>, error?: array{code: string, message: string}}
 * @phpstan-type SearchBatch array{responses: list<SearchResponse>}
 * @phpstan-type Step array{instruction: string, distanceMeters: int, durationSeconds: int, lat: float, lon: float}
 * @phpstan-type Leg array{distanceMeters: int, durationSeconds: int, steps?: list<Step>}
 * @phpstan-type LineString array{type: 'LineString', coordinates: list<array{float, float}>}
 * @phpstan-type Route array{waypointOrder?: list<int>, distanceMeters: int, durationSeconds: int, polyline?: string, geometry?: LineString, legs: list<Leg>}
 * @phpstan-type Routes array{routes: list<Route>, attribution?: list<Attribution>}
 * @phpstan-type Isochrone array{type: 'Feature', properties: array{minutes: int}, geometry: array{type: 'Polygon'|'MultiPolygon', coordinates: list<mixed>}}
 * @phpstan-type Isochrones array{type: 'FeatureCollection', features: list<Isochrone>, attribution?: list<Attribution>}
 * @phpstan-type IpLocation array{ip: string, found: bool, continentCode?: string, countryCode?: string, countryName?: string, regionCode?: string, regionName?: string, city?: string, location?: Point, source: string, attributionUrl: string, licenseUrl: string, datasetRelease: string}
 * @phpstan-type Account array{id: string, name: string, status: string, createdAt: string}
 */
final class Client
{
    public const VERSION = '0.10.0';

    public readonly GeocodingService $geocoding;
    public readonly PlacesService $places;
    public readonly RoutingService $routing;
    public readonly IpGeolocationService $ipGeolocation;

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
        $this->geocoding = new GeocodingService($this->get(...), $this->post(...), self::query(...));
        $this->places = new PlacesService($this->get(...));
        $this->routing = new RoutingService($this->post(...));
        $this->ipGeolocation = new IpGeolocationService($this->get(...));
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
        $query = self::query($params);
        $url = $this->baseUrl . $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
        return $this->request('GET', $url);
    }

    /**
     * Parameters as the API takes them in a query string, without the ones that are null.
     *
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private static function query(array $params): array
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
        return $query;
    }

    /** @param array<string, mixed> $body */
    private function post(string $path, array $body): array
    {
        $body = array_filter($body, fn ($value) => $value !== null);
        return $this->request('POST', $this->baseUrl . $path, json_encode($body, JSON_THROW_ON_ERROR));
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
