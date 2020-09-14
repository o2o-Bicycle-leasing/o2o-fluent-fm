<?php

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use o2o\FluentFM\Connection\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ResponseTest extends TestCase
{
    /** @var ResponseInterface */
    private $response;

    public function setUp(): void
    {
        $this->response = new GuzzleResponse(
            200,
            [],
            (string)file_get_contents(__DIR__ . '/../Stubs/response.json')
        );
    }

    public function testRecords(): void
    {
        $this->assertCount(5, Response::records($this->response));
    }

    public function testPaginatedRecords(): void
    {
        $response = Response::paginatedRecords($this->response, 1, 5);

        $this->assertEquals(93, $response->getTotalItemCount());
        $this->assertEquals(1, $response->getCurrentPage());
        $this->assertEquals(5, $response->getItemsPerPage());
        $this->assertEquals(19, $response->getPageCount());
        $this->assertCount(5, $response->getData());
    }

    public function testFields(): void
    {
        $response = Response::fields(
            new GuzzleResponse(
                200,
                [],
                (string) file_get_contents(__DIR__ . '/../Stubs/responseFields.json')
            )
        );

        $this->assertCount(32, $response['fields']);
        $this->assertCount(1, $response['portals']);
        $this->assertCount(7, $response['portals']['Order || Persoon']);
    }
}
