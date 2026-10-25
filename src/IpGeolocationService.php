<?php

declare(strict_types=1);

namespace Mapricot;

/**
 * $client->ipGeolocation: the /ip-geolocation/v1 endpoints.
 *
 * @phpstan-import-type IpLocation from Client
 */
final class IpGeolocationService
{
    public function __construct(private \Closure $get)
    {
    }

    /**
     * Approximate location of a public IP address.
     *
     * @return IpLocation
     */
    public function lookup(string $ip): array
    {
        return ($this->get)('/ip-geolocation/v1/' . rawurlencode($ip));
    }
}
