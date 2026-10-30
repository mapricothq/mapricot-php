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
     * @param ?bool $steps true to include each leg's turn-by-turn steps.
     * @param ?string $lang ISO 639-1 language of the steps' instructions, such as 'fr'. English by default.
     * @param ?array<string, float|int|bool|string> $costing Valhalla's costing options for the mode, such as ['use_tolls' => 0]:
     *     https://mapricot.com/docs/routing/#costing-options.
     * @return Routes
     */
    public function route(array $waypoints, ?string $mode = null, ?string $geometries = null, ?bool $steps = null, ?string $lang = null, ?array $costing = null): array
    {
        return ($this->post)('/routing/v1/route', ['waypoints' => $waypoints, 'mode' => $mode, 'geometries' => $geometries, 'steps' => $steps, 'lang' => $lang, 'costing' => $costing ?: null]);
    }

    /**
     * Find a fast route through 2 to 20 waypoints, keeping the first and last in place and reordering the rest.
     *
     * @param list<Point> $waypoints
     * @param 'drive'|'walk'|'bicycle'|null $mode
     * @param 'polyline'|'polyline6'|'geojson'|null $geometries
     * @param ?bool $steps true to include each leg's turn-by-turn steps.
     * @param ?string $lang ISO 639-1 language of the steps' instructions, such as 'fr'. English by default.
     * @param ?array<string, float|int|bool|string> $costing As for route.
     * @return Routes
     */
    public function optimize(array $waypoints, ?string $mode = null, ?string $geometries = null, ?bool $steps = null, ?string $lang = null, ?array $costing = null): array
    {
        return ($this->post)('/routing/v1/optimize', ['waypoints' => $waypoints, 'mode' => $mode, 'geometries' => $geometries, 'steps' => $steps, 'lang' => $lang, 'costing' => $costing ?: null]);
    }

    /**
     * The areas reachable from a location within 1 to 4 times, each 1 to 60 minutes, one polygon per time, largest first.
     *
     * @param Point $location
     * @param list<int> $minutes
     * @param 'drive'|'walk'|'bicycle'|null $mode
     * @param ?array<string, float|int|bool|string> $costing As for route.
     * @return Isochrones
     */
    public function isochrone(array $location, array $minutes, ?string $mode = null, ?array $costing = null): array
    {
        return ($this->post)('/routing/v1/isochrone', ['location' => $location, 'minutes' => $minutes, 'mode' => $mode, 'costing' => $costing ?: null]);
    }
}
