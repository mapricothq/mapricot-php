<?php

declare(strict_types=1);

namespace Mapricot\Tests;

use Mapricot\Client;
use Mapricot\MapricotException;
use PHPUnit\Framework\TestCase;

final class LiveTest extends TestCase
{
    private function client(): Client
    {
        if (!getenv('MAPRICOT_KEY')) {
            $this->markTestSkipped('set MAPRICOT_KEY to run tests against the live API');
        }
        return new Client(getenv('MAPRICOT_KEY'));
    }

    public function testSearch(): void
    {
        $data = $this->client()->geocoding->search('128 SW 3rd Ave, Portland', country: ['US'], limit: 1);
        $this->assertSame('Portland', $data['results'][0]['address']['locality']);
        $this->assertNotEmpty($data['attribution']);
    }

    public function testSearchBatch(): void
    {
        $data = $this->client()->geocoding->searchBatch([['q' => '128 SW 3rd Ave, Portland', 'country' => ['US'], 'limit' => 1], ['q' => '']]);
        [$first, $second] = $data['responses'];
        $this->assertSame('Portland', $first['results'][0]['address']['locality']);
        $this->assertSame('invalid_request', $second['error']['code']);
    }

    public function testAutocomplete(): void
    {
        $data = $this->client()->geocoding->autocomplete('stumptown', focus: ['lat' => 45.52, 'lon' => -122.68], limit: 1);
        $this->assertStringContainsString('Stumptown', $data['results'][0]['name']);
    }

    public function testReverse(): void
    {
        $data = $this->client()->geocoding->reverse(45.5217, -122.6733, limit: 1);
        $this->assertIsInt($data['results'][0]['distanceMeters']);
    }

    public function testNearby(): void
    {
        $data = $this->client()->places->nearby(45.52, -122.68, category: ['food:coffee_shop'], radius: 1000, limit: 1);
        $this->assertContains('food:coffee_shop', $data['results'][0]['categories']);
    }

    public function testPlace(): void
    {
        $client = $this->client();
        $found = $client->geocoding->search('Pioneer Courthouse Square, Portland', limit: 1)['results'][0];
        $this->assertSame($found['id'], $client->places->get($found['id'])['result']['id']);
    }

    public function testRoute(): void
    {
        $data = $this->client()->routing->route([['lat' => 43.7396, 'lon' => 7.4275], ['lat' => 43.734, 'lon' => 7.4222]], mode: 'walk');
        $this->assertCount(1, $data['routes'][0]['legs']);
        $this->assertNotEmpty($data['routes'][0]['polyline']);
        $this->assertArrayNotHasKey('steps', $data['routes'][0]['legs'][0]);
    }

    public function testRouteStepsInFrench(): void
    {
        $data = $this->client()->routing->route([['lat' => 43.7396, 'lon' => 7.4275], ['lat' => 43.734, 'lon' => 7.4222]], steps: true, lang: 'fr');
        $this->assertStringContainsString('destination', end($data['routes'][0]['legs'][0]['steps'])['instruction']);
    }

    public function testIsochrone(): void
    {
        $data = $this->client()->routing->isochrone(['lat' => 43.7396, 'lon' => 7.4275], [5, 10], mode: 'walk');
        $this->assertSame([10, 5], array_map(fn ($feature) => $feature['properties']['minutes'], $data['features']));
        $this->assertSame('Polygon', $data['features'][0]['geometry']['type']);
        $this->assertNotEmpty($data['attribution']);
    }

    public function testRouteGeoJson(): void
    {
        $data = $this->client()->routing->route([['lat' => 43.7396, 'lon' => 7.4275], ['lat' => 43.734, 'lon' => 7.4222]], geometries: 'geojson');
        $this->assertSame('LineString', $data['routes'][0]['geometry']['type']);
        $this->assertGreaterThan(1, count($data['routes'][0]['geometry']['coordinates']));
    }

    public function testIp(): void
    {
        $data = $this->client()->ipGeolocation->lookup('8.8.8.8');
        $this->assertTrue($data['found']);
        $this->assertSame('US', $data['countryCode']);
    }

    public function testAccount(): void
    {
        $this->assertSame('active', $this->client()->account()['status']);
    }

    public function testInvalidRequest(): void
    {
        try {
            $this->client()->geocoding->search('');
            $this->fail('expected MapricotException');
        } catch (MapricotException $error) {
            $this->assertSame([400, 'invalid_request'], [$error->status, $error->errorCode]);
            $this->assertNotEmpty($error->requestId);
        }
    }

    public function testBadKey(): void
    {
        try {
            (new Client('mapricot_sk_bad'))->account();
            $this->fail('expected MapricotException');
        } catch (MapricotException $error) {
            $this->assertSame([401, 'unauthorized'], [$error->status, $error->errorCode]);
        }
    }
}
