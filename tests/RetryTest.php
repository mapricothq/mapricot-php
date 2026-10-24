<?php

declare(strict_types=1);

namespace Mapricot\Tests;

use Mapricot\Client;
use Mapricot\MapricotException;
use PHPUnit\Framework\TestCase;

final class RetryTest extends TestCase
{
    /** @var resource */
    private $server;
    private string $log;

    /** Starts PHP's built-in server, which answers with each failure in turn and then with 200. */
    private function serve(array $failures): Client
    {
        $this->log = tempnam(sys_get_temp_dir(), 'mapricot');
        $port = 18000 + random_int(0, 999);
        $env = ['FAILURES' => json_encode($failures), 'LOG' => $this->log];
        $this->server = proc_open(['php', '-S', "127.0.0.1:$port", __DIR__ . '/stub.php'], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, $env);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
            usleep(100_000);
        }
        return new Client('k', baseUrl: "http://127.0.0.1:$port");
    }

    protected function tearDown(): void
    {
        proc_terminate($this->server);
        unlink($this->log);
    }

    /** @return list<string> */
    private function seen(): array
    {
        return file($this->log, FILE_IGNORE_NEW_LINES);
    }

    public function testRetries503AndConcurrencyExceeded(): void
    {
        $client = $this->serve([[503, 'origin_overloaded'], [429, 'concurrency_exceeded']]);
        $this->assertSame(['ok' => true], $client->search('x', focus: ['lat' => 1, 'lon' => 2], country: ['US', 'CA']));
        $this->assertCount(3, $this->seen());
        $this->assertSame('/v1/search?q=x&focus=1%2C2&country=US%2CCA', $this->seen()[0]);
    }

    public function testDoesNotRetryCreditExhausted(): void
    {
        $client = $this->serve([[429, 'credit_exhausted']]);
        try {
            $client->account();
            $this->fail('expected MapricotException');
        } catch (MapricotException $error) {
            $this->assertSame([429, 'credit_exhausted', 'r1'], [$error->status, $error->errorCode, $error->requestId]);
        }
        $this->assertCount(1, $this->seen());
    }
}
