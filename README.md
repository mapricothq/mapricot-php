# Mapricot for PHP

The official PHP client for the [Mapricot API](https://mapricot.com/): address and place search, reverse geocoding, nearby places, routing, and IP geolocation, with 250,000 free requests every month.

```sh
composer require mapricot/mapricot
```

It needs PHP 8.1 or later with the `curl` extension, and has no other dependencies.

## Quick start

Create a server key in the [dashboard](https://app.mapricot.com/) and pass it to the client. Keep it out of your source code, for example by reading it from an environment variable as here.

```php
<?php

require 'vendor/autoload.php';

$client = new Mapricot\Client(getenv('MAPRICOT_KEY'));

$data = $client->search('128 SW 3rd Ave, Portland', limit: 1);
$place = $data['results'][0];
echo $place['label'], ' ', $place['lat'], ' ', $place['lon'], "\n";
```

## Methods

Each method returns the API's JSON as an array, with the same field names as the [API reference](https://mapricot.com/api-reference/). The array shapes are declared for PHPStan and Psalm.

| Method | Endpoint |
| --- | --- |
| `$client->search($q, focus:, country:, limit:, lang:)` | [`GET /v1/search`](https://mapricot.com/docs/geocoding/#find-an-address) |
| `$client->autocomplete($q, focus:, country:, limit:, lang:)` | [`GET /v1/autocomplete`](https://mapricot.com/docs/places/#suggest-as-people-type) |
| `$client->reverse($lat, $lon, limit:, lang:)` | [`GET /v1/reverse`](https://mapricot.com/docs/geocoding/#find-the-address-at-a-point) |
| `$client->nearby($lat, $lon, category:, radius:, limit:, lang:)` | [`GET /v1/nearby`](https://mapricot.com/docs/places/#list-places-around-a-point) |
| `$client->place($id, lang:)` | [`GET /v1/places/{id}`](https://mapricot.com/docs/places/#look-up-a-saved-place) |
| `$client->route($waypoints, mode:, geometries:)` | [`POST /v1/route`](https://mapricot.com/docs/routing/#get-a-route) |
| `$client->ip($address)` | [`GET /v1/ip/{ip}`](https://mapricot.com/docs/ip-geolocation/#look-up-an-address) |
| `$client->account()` | `GET /v1/account`, which checks a key and is never billed |

Every named argument is optional. `focus` and each waypoint are `['lat' => …, 'lon' => …]`. `country` and `category` take a list or a comma-separated string.

```php
$nearby = $client->nearby(45.52, -122.68, category: ['food:coffee_shop'], radius: 1000);
$route = $client->route([['lat' => 45.5189, 'lon' => -122.6793], ['lat' => 45.5214, 'lon' => -122.6828]], mode: 'walk');
echo $route['routes'][0]['durationSeconds'], "\n";
```

A route's path comes as an encoded `polyline` with five decimal places, which polyline decoders assume. Pass `geometries: 'geojson'` to get it as a GeoJSON `LineString` in `geometry` instead, which maps draw without decoding, or `geometries: 'polyline6'` for six decimal places.

If your app shows results, show the credits in each response's `attribution`.

## Errors and retries

An error response throws `Mapricot\MapricotException`, with the HTTP `status`, the API's `errorCode`, the message, and the `requestId`:

```php
try {
    $client->search('');
} catch (Mapricot\MapricotException $error) {
    echo $error->status, ' ', $error->errorCode, ' ', $error->getMessage(), "\n"; // 400 invalid_request The service rejected the request.
}
```

A network failure throws `RuntimeException`. The client retries `503` responses and `429 concurrency_exceeded`, up to twice, waiting longer each time. Errors aren't billed. Pass `maxRetries:` and `timeout:` (seconds) to `new Mapricot\Client(...)` to change this. The [API reference](https://mapricot.com/api-reference/#errors) lists every status.

## Links

- [Mapricot](https://mapricot.com/)
- [PHP docs](https://mapricot.com/docs/php/)
- [API reference](https://mapricot.com/api-reference/)
- [Pricing](https://mapricot.com/pricing/)
