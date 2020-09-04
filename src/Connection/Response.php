<?php

namespace o2o\FluentFM\Connection;

use o2o\FluentFM\Exception\DataApiException;
use o2o\FluentFM\Exception\ExceptionMessages;
use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Exception\TokenException;
use o2o\FluentFM\Utils\PaginatedCollection;
use Psr\Http\Message\ResponseInterface;

use function json_decode;

class Response
{
    /**
     * Get response body contents.
     *
     * @return mixed
     */
    public static function body(ResponseInterface $response)
    {
        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), false);
    }

    /**
     * Get response returned records.
     *
     * @return array<int|string,mixed|array>
     */
    public static function records(ResponseInterface $response, bool $with_portals = false): array
    {
        $records = [];

        if (isset(static::body($response)->response->data)) {
            foreach (static::body($response)->response->data as $record) {
                $records[$record->recordId] = $with_portals ? (array) $record : self::generateResponse($record);
            }
        }

        return $records;
    }

    public static function paginatedRecords(
        ResponseInterface $response,
        int $page,
        int $perPage,
        bool $with_portals = false
    ): PaginatedCollection {
        if (
            isset(static::body($response)->messages) &&
            static::body($response)->messages[0]->code === '401'
        ) {
            return new PaginatedCollection(
                static::records($response, $with_portals),
                0,
                $perPage,
                $page
            );
        }

        return new PaginatedCollection(
            static::records($response, $with_portals),
            (int) static::body($response)->response->dataInfo->foundCount,
            $perPage,
            $page
        );
    }

    /**
     * @param mixed $record
     * @return array<mixed,mixed|array>
     */
    public static function generateResponse($record): array
    {
        $fieldData = (array) $record->fieldData;

        if (! isset($fieldData['recordId'])) {
            $fieldData['recordId'] = (int) $record->recordId;
        }

        return $fieldData;
    }

    /**
     * @param mixed[] $records
     * @return array<mixed,mixed|array>
     */
    public static function recordsWithPortals(array $records): array
    {
        $resultSet = [];
        foreach ($records as $record) {
            $resultSet[] = self::generateResponseWithPortals($record);
        }

        return $resultSet;
    }

    /**
     * @param mixed $record
     * @return array<mixed,mixed|array>
     */
    public static function generateResponseWithPortals($record): array
    {
        $record = (object) $record;

        $fieldData = (array) $record->fieldData;

        if (! isset($fieldData['recordId'])) {
            $fieldData['recordId'] = (int) $record->recordId;
        }
        if (! isset($fieldData['portals'])) {
            $fieldData['portals'] = json_decode(
                json_encode($record->portalData, JSON_THROW_ON_ERROR),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return $fieldData;
    }

    /**
     * Get response returned message.
     *
     * @return mixed|null
     */
    public static function message(ResponseInterface $response)
    {
        $message = static::body($response)->messages[0];

        if ($message->code === '0') {
            return;
        }

        return $message;
    }

    public static function fields(ResponseInterface $response): array
    {
        $fields = static::body($response)->response->fieldMetaData;

        $result = [];
        foreach ($fields as $field) {
            $result[] = $field->name;
        }

        return $result;
    }

    /**
     * @param array<string|int,mixed|array> $query
     *
     * @throws FilemakerException
     */
    public static function check(ResponseInterface $response, array $query): void
    {
        $body = static::body($response);

        if ($response->getStatusCode() === 503) {
            throw DataApiException::serviceUnavailable();
        }

        if (! isset($body->messages)) {
            return;
        }

        $message = $body->messages[0];

        switch ($message->code) {
            case 0:
            case 401:
                return;
            case 3:
                throw DataApiException::connectionRefused();
            case 102:
                throw new FilemakerException(
                    ExceptionMessages::fieldMissing($message, $query),
                    $message->code
                );
            case 509:
                throw new FilemakerException(
                    ExceptionMessages::fieldInvalid($message, $query),
                    $message->code
                );
            case 952:
                throw TokenException::invalid();
            default:
                throw new FilemakerException(
                    ExceptionMessages::generic($message, $query),
                    $message->code
                );
        }
    }
}
