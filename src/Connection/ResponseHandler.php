<?php


namespace o2o\FluentFM\Connection;


use o2o\FluentFM\Exception\ExceptionMessages;
use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Exception\TokenException;
use o2o\FluentFM\Utils\Collection;
use o2o\FluentFM\Utils\PaginatedCollection;
use Psr\Http\Message\ResponseInterface;

class ResponseHandler
{
    /** @var ResponseInterface */
    private $response;

    /** @var array */
    private $query;

    public function __construct(ResponseInterface $response, array $query = [])
    {
        $this->response = $response;
        $this->query = $query;

        $this->checkResponse();
    }

    public static function checkResult(ResponseInterface $response, array $query = []): void
    {
        new self($response, $query);
    }

    private function checkResponse(): void
    {
        $body = $this->getBody();

        if (! isset($body->messages)) {
            return;
        }

        $message = $body->messages[0];

        switch ($message->code) {
            case 0:
            case 401:
                return;
            case 102:
                throw new FilemakerException(
                    ExceptionMessages::fieldMissing($message, $this->query),
                    $message->code
                );
            case 509:
                throw new FilemakerException(
                    ExceptionMessages::fieldInvalid($message, $this->query),
                    $message->code
                );
            case 952:
                throw TokenException::invalid();
                break;
            default:
                throw new FilemakerException(
                    ExceptionMessages::generic($message, $this->query),
                    $message->code
                );
        }
    }

    private function getBody(): \stdClass
    {
        $this->response->getBody()->rewind();
        return json_decode($this->response->getBody()->getContents(), false);
    }

    public function getRecordId(): int
    {
        return (int)$this->getBody()->response->recordId;
    }

    public function getRecords(bool $withPortals = false): \o2o\FluentFM\Contract\Collection
    {
        $records = [];

        $responseBody = $this->getBody();
        if (isset($responseBody->response->data)) {
            foreach ($responseBody->response->data as $record) {
                $records[$record->recordId] = $withPortals ? (array) $record : $this->generateResponse($record);
            }
        }

        return new Collection(
            $records,
            (int) $responseBody->response->dataInfo->foundCount
        );
    }

    public function getPaginatedRecords(
        int $page,
        int $perPage,
        bool $with_portals = false
    ): \o2o\FluentFM\Contract\PaginatedCollection {
        return new PaginatedCollection(
            $this->getRecords($with_portals),
            $perPage,
            $page
        );
    }

    private function generateResponse($record): array
    {
        $fieldData = (array) $record->fieldData;

        if (! isset($fieldData['recordId'])) {
            $fieldData['recordId'] = (int) $record->recordId;
        }

        return $fieldData;
    }
}
