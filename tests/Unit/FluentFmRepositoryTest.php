<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\FluentFmRepositoryStub;
use GuzzleHttp\Psr7\Response;

class FluentFmRepositoryTest extends TestCase
{
    /** @var array */
    private $container = [];

    /** @var Client */
    private $httpClient;

    public function setUp(): void
    {
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create(new MockHandler([new Response(200)]));
        $handlerStack->push($history);

        $this->httpClient = new Client(['handler' => $handlerStack]);
    }

    public function testInitialQueryIsCleared()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $this->assertEquals([
            'limit' => null,
            'offset' => null,
            'sort' => null,
            'query' => null,
            'script' => null,
            'script.param' => null,
            'script.prerequest' => null,
            'script.prerequest.param' => null,
            'script.presort' => null,
            'script.presort.param' => null
        ], $repo->getQuery());
    }

    public function testHeaders()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $this->assertEquals([
            'Authorization' => 'Bearer token',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'cache-control' => 'no-cache',
            'read_timeout' => 30000,
        ], $repo->getClientHeaders());
    }

    public function testRecords()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->record('layout', 5)->get();

        $request = $this->popLastRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('layouts/layout/records/5', $request->getUri()->getPath());
    }

    public function testAuthHeaders()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->record('layout', 5)->get();

        $request = $this->popLastRequest();
        $this->assertEquals('Bearer token', $request->getHeaderLine('Authorization'));
    }

    /*
    public function testFind()
    {

    }

    public function testCreate()
    {

    }

    public function testGlobals()
    {

    }

    public function testUpdate()
    {

    }

    public function testBroadcast()
    {

    }

    public function testUpload()
    {

    }

    public function testDownload()
    {

    }
    */

    public function testLogout()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->logout();

        $request = $this->popLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('sessions/token', $request->getUri()->getPath());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
    }

    private function popLastRequest(): Request
    {
        return array_pop($this->container)['request'];
    }
}
