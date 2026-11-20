<?php

declare(strict_types=1);

namespace Mapricot\Tests;

use Mapricot\Client;
use PHPUnit\Framework\TestCase;

final class PlacesTest extends TestCase
{
    public function testGetRefusesAnEmptyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Client('k'))->places->get('');
    }
}
