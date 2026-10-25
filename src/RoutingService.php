<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * $client->routing: the /routing/v1 endpoints.
 *
 * @phpstan-import-type Point from Client
 * @phpstan-import-type Routes from Client
 * @phpstan-import-type Isochrones from Client
 */
final class RoutingService
{
    public function __construct(private \Closure $post)
    {
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
        return ($this->post)('/routing/v1/route', ['waypoints' => $waypoints, 'mode' => $mode, 'geometries' => $geometries]);
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
        return ($this->post)('/routing/v1/isochrone', ['location' => $location, 'minutes' => $minutes, 'mode' => $mode]);
    }
}
