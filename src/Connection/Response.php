<?php

namespace o2o\FluentFM\Connection;

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
     */
    public static function body(ResponseInterface $response)
    {
        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), false);
    }

    /**
     * Get response returned records.
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
        return new PaginatedCollection(
            static::records($response, $with_portals),
            (int) static::body($response)->response->dataInfo->foundCount,
            $perPage,
            $page
        );
    }

    public static function generateResponse($record): array
    {
        $fieldData = (array) $record->fieldData;

        if (! isset($fieldData['recordId'])) {
            $fieldData['recordId'] = (int) $record->recordId;
        }

        return $fieldData;
    }

    /**
     * Get response returned message.
     */
    public static function message(ResponseInterface $response)
    {
        $message = static::body($response)->messages[0];

        if ($message->code === '0') {
            return;
        }

        return $message;
    }

    /**
     * @param array $query
     *
     * @throws FilemakerException
     */
    public static function check(ResponseInterface $response, array $query): void
    {
        $body = static::body($response);

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
                break;
            default:
                throw new FilemakerException(
                    ExceptionMessages::generic($message, $query),
                    $message->code
                );
        }
    }
}
