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
        $response = new Response(200, [], '{"response": {"recordId": "1", "dataInfo": {"foundCount": 0}}}');
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
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

    public function testFind()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->find('layout')->where('param', 'value')->get();

        $request = $this->popLastRequest();
        $this->assertPost($request, 'layouts/layout/_find', '{"query":[{"param":"=value"}]}');
    }

    public function testFindPaginated()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->findPaginated('layout')->where('param', 'value')->get();

        $request = $this->popLastRequest();
        $this->assertPost($request, 'layouts/layout/_find', '{"limit":10,"query":[{"param":"=value"}]}');
    }

    public function testFindPaginatedNextPage()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->findPaginated('layout', 2)->where('param', 'value')->get();

        $request = $this->popLastRequest();
        $this->assertPost($request, 'layouts/layout/_find', '{"limit":10,"offset":10,"query":[{"param":"=value"}]}');
    }

    public function testCreate()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->create('layout', ['field' => 'value'], ['portal' => 'value']);

        $request = $this->popLastRequest();
        $this->assertPost(
            $request,
            'layouts/layout/records',
            '{"fieldData":{"field":"value"},"portalData":{"portal":"value"}}'
        );
    }

    public function testBroadcast()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->broadcast(['data' => 'content']);
        $request = $this->popLastRequest();
        $this->assertPost($request, 'layouts/API_request/records', '{"data":"content"}');
    }

    public function testGlobals()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->globals('layout', ['field' => 'value']);
        $request = $this->popLastRequest();
        $this->assertPost(
            $request,
            'globals',
            '{"globalFields":{"layout::field":"value"}}',
            'PATCH'
        );
    }

    public function testUpdate()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->update('layout', ['field' => 'value'], 1, ['portals' => 'test'], ['delete'])->get();
        $request = $this->popLastRequest();
        $this->assertPost(
            $request,
            'layouts/layout/records/1',
            '{"fieldData":{"field":"value","deleteRelated":["delete"]},"portalData":{"portals":"test"}}',
            'PATCH'
        );
    }

    public function testUpload()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->upload('layout', 'field', __DIR__ . '/../Stubs/file.txt', 1)->get();
        $request = $this->popLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('layouts/layout/records/1/containers/field/1', $request->getUri()->getPath());
        $this->assertStringContainsString(
            'multipart/form-data; boundary=',
            $request->getHeaderLine('Content-Type')
        );
        $this->assertStringContainsString(
            'Content-Disposition: form-data; name="upload"; filename="file.txt"',
            $request->getBody()->getContents()
        );
    }

    /* TODO: should probably check the download */
    public function testDownload()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->download('layout', 'field', './', 1);
        $request = $this->popLastRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('layouts/layout/records/1', $request->getUri()->getPath());
    }

    public function testDelete()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->delete('layout', 1)->get();
        $request = $this->popLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('layouts/layout/records/1', $request->getUri()->getPath());
    }

    public function testSoftDelete()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->softDelete('layout', 1)->get();
        $request = $this->popLastRequest();
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals('layouts/layout/records/1', $request->getUri()->getPath());
        $this->assertStringContainsString(
            '{"fieldData":{"deleted_at":',
            $request->getBody()->getContents()
        );
    }
    public function testUndelete()
    {
        $repo = new FluentFmRepositoryStub([], $this->httpClient);
        $repo->undelete('layout', 1)->get();
        $request = $this->popLastRequest();
        $this->assertPost(
            $request,
            'layouts/layout/records/1',
            '{"fieldData":{"deleted_at":""}}',
            'PATCH'
        );
        $this->assertTrue($repo->getWithDeleted());
    }

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

    private function assertPost(Request $request, string $expectedUrl, string $expectedBody, $method = 'POST')
    {
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($expectedUrl, $request->getUri()->getPath());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals($expectedBody, $request->getBody()->getContents());
    }
}
