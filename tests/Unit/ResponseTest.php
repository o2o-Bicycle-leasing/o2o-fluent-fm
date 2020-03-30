<?php


namespace Tests\Unit;


use GuzzleHttp\Psr7\Response as GuzzleResponse;
use o2o\FluentFM\Connection\ResponseHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ResponseTest extends TestCase
{
    /** @var ResponseInterface */
    private $response;

    public function setUp(): void
    {
        $this->response = new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/../Stubs/response.json'));
    }

    public function testRecords()
    {
        $this->assertCount(5, (new ResponseHandler($this->response))->getRecords());
    }

    public function testPaginatedRecords()
    {
        $response = (new ResponseHandler($this->response))->getPaginatedRecords(1, 5);

        $this->assertEquals(93, $response->getTotalItemCount());
        $this->assertEquals(1, $response->getCurrentPage());
        $this->assertEquals(5, $response->getItemsPerPage());
        $this->assertEquals(19, $response->getPageCount());
        $this->assertCount(5, $response->getData());
    }
}
