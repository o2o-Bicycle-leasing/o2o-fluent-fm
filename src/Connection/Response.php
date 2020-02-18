<?php

namespace o2o\FluentFM\Connection;

use o2o\FluentFM\Exception\ExceptionMessages;
use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Exception\TokenException;
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

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Get response returned records.
     *
     * @return array
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

    public static function generateResponse($record)
    {
        $fieldData = (array) $record->fieldData;

        if (! isset($fieldData['recordId'])) {
            $fieldData['recordId'] = (int) $record->recordId;
        }

        return $fieldData;
    }

    /**
     * Get response returned message.
     *
     * @return mixed
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
