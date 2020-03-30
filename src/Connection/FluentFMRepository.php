<?php

namespace o2o\FluentFM\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use o2o\FluentFM\Contract\FluentFM;
use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Exception\NoResultException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_slice;
use function basename;
use function count;
use function date;
use function explode;
use function file_put_contents;
use function fopen;
use function is_array;
use function is_dir;
use function mkdir;
use function parse_url;
use function pathinfo;
use function sprintf;
use function strpos;

use const PATHINFO_EXTENSION;

class FluentFMRepository extends BaseConnection implements FluentFM
{
    use FluentQuery;

    protected $auto_id = false;

    public function __construct(array $config, ?Client $client = null)
    {
        parent::__construct($config, $client);

        $this->clearQuery();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getClientHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'cache-control' => 'no-cache',
            'read_timeout'  => 30000,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function record($layout, $id): FluentFM
    {
        $this->records($layout, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function records($layout, $id = null): FluentFM
    {
        $this->callback = function () use ($layout, $id) {
            $response = $this->client->get((new UrlGenerator($layout))->record($id), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'query'        => $this->queryString(),
            ]);

            return (new ResponseHandler($response, $this->queryString()))->getRecords($this->with_portals);
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $layout): FluentFM
    {
        $this->callback = function () use ($layout) {
            $response = $this->client->post((new UrlGenerator($layout))->find(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => array_filter($this->query),
            ]);

            return (new ResponseHandler($response, array_filter($this->query)))->getRecords($this->with_portals);
        };

        return $this;
    }

    public function findPaginated(string $layout, int $page = 1, int $perPage = 10): FluentFM
    {
        $this->callback = function () use ($layout, $page, $perPage) {
            $this->limit($perPage);
            $this->offset(($page - 1) * $perPage);

            $response = $this->client->post((new UrlGenerator($layout))->find(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => array_filter($this->query),
            ]);

            return (new ResponseHandler($response, array_filter($this->query)))->getPaginatedRecords($page, $perPage);
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $layout, array $fields = [], array $portals = []): int
    {
        if (! array_key_exists('id', $fields) && $this->auto_id) {
            $fields['id'] = Uuid::uuid4()->toString();
        }

        $this->callback = function () use ($layout, $fields, $portals) {
            $json = [ 'fieldData' => $fields ];
            if (count($portals) > 0) {
                $json['portalData'] = $portals;
            }
            $response = $this->client->post((new UrlGenerator($layout))->records(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => $json,
            ]);

            return (new ResponseHandler($response, [ 'fieldData' => $fields ]))->getRecordId();
        };

        return $this->exec();
    }

    public function broadcast(array $body): int
    {
        $layout         = 'API_request';
        $this->callback = function () use ($layout, $body) {
            $response = $this->client->post((new UrlGenerator($layout))->records(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => $body,
            ]);

            return (new ResponseHandler($response, [ 'fieldData' => array_filter($body) ]))->getRecordId();
        };

        return $this->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function globals(string $layout, array $fields = []): bool
    {
        $this->callback = function () use ($layout, $fields) {
            $globals = [];

            foreach ($fields as $key => $value) {
                $globals[$layout . '::' . $key] = $value;
            }

            $response = $this->client->patch((new UrlGenerator($layout))->globals(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => [ 'globalFields' => array_filter($globals) ],
            ]);

            ResponseHandler::checkResult($response, [ 'globalFields' => array_filter($globals) ]);

            return true;
        };

        return $this->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function update(
        string $layout,
        array $fields = [],
        ?int $recordId = null,
        array $portals = [],
        array $deleteRelated = []
    ): FluentFM {
        $this->callback = function () use ($layout, $fields, $recordId, $portals, $deleteRelated) {
            $recordIds = [ $recordId ];

            if (! $recordId) {
                if (! $records = $this->find($layout)->get()) {
                    return true;
                }

                $recordIds = array_keys($records);
            }

            foreach ($recordIds as $id) {
                $json = [ 'fieldData' => $fields ];
                if (count($portals) > 0) {
                    $json['portalData'] = $portals;
                }
                if (count($deleteRelated) > 0) {
                    $json['fieldData']['deleteRelated'] = $deleteRelated;
                }
                $response = $this->client->patch((new UrlGenerator($layout))->record($id), [
                    'Content-Type' => 'application/json',
                    'headers'      => $this->authHeader(),
                    'json'         => $json,
                ]);
                ResponseHandler::checkResult($response, [ 'fieldData' => $fields ]);
            }
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function upload(string $layout, string $field, string $filename, ?int $recordId = null): FluentFM
    {
        $this->callback = function () use ($layout, $field, $filename, $recordId) {
            $recordIds = $recordId ? [ $recordId ] : array_keys($this->find($layout)->get());

            foreach ($recordIds as $id) {
                $response = $this->client->post((new UrlGenerator($layout))->container($field, $id), [
                    'Content-Type' => 'multipart/form-data',
                    'headers'      => $this->authHeader(),
                    'multipart'    => [
                        [
                            'name'     => 'upload',
                            'contents' => fopen($filename, 'rb'),
                            'filename' => basename($filename),
                        ],
                    ],
                ]);

                ResponseHandler::checkResult($response, [
                    'multipart' => [
                        [
                            'name'     => 'upload',
                            'contents' => '...',
                            'filename' => basename($filename),
                        ],
                    ],
                ]);
            }

            return true;
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function download(string $layout, string $field, string $output_dir = './', ?int $recordId = null): FluentFM
    {
        // $this->callback = function () use ( $layout, $field, $output_dir, $recordId ) {
        if ($recordId) {
            $records = $this->record($layout, $recordId)->get();
        } else {
            $records = $this->find($layout)->get();
        }

        if (! is_dir($output_dir) && ! mkdir($output_dir, 0775, true) && ! is_dir($output_dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $output_dir));
        }

            $downloader = new Client([
                'verify'  => false,
                'headers' => $this->authHeader(),
                'cookies' => true,
            ]);

        foreach ($records as $record) {
            $ext = pathinfo(
                parse_url($record[$field])['path'],
                PATHINFO_EXTENSION
            );

            $filename = sprintf('%s/%s.%s', $output_dir, $recordId, $ext ? $ext : 'pdf');
            $response = $downloader->get($record[$field]);

            // Response::check( $response, $this->query );

            file_put_contents(
                $filename,
                $response->getBody()->getContents()
            );
        }

            $downloader = null;
        // };

        return $this;
    }

    public function downloadFromPath(string $path, string $filename, string $output_dir)
    {
        // $this->callback = function () use ( $path, $filename, $output_dir ) {
        if (! is_dir($output_dir) && ! mkdir($output_dir, 0775, true) && ! is_dir($output_dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $output_dir));
        }

            $downloader = new Client([
                'verify'  => false,
                'headers' => $this->authHeader(),
                'cookies' => true,
            ]);

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            $filename = sprintf('%s/%s.%s', $output_dir, $filename, $ext ? $ext : 'pdf');
        if (strpos($filename, '?')) {
            $filenameParts = explode('?', $filename);
            $filename      = $filenameParts[0];
        }
            $response = $downloader->get($path);

            file_put_contents(
                $filename,
                $response->getBody()->getContents()
            );

            $downloader = null;
        // };

        return $filename;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $layout, ?int $recordId = null): FluentFM
    {
        $this->callback = function () use ($layout, $recordId) {
            $recordIds = $recordId ? [ $recordId ] : array_keys($this->find($layout)->get());

            foreach ($recordIds as $id) {
                $response = $this->client->delete((new UrlGenerator($layout))->record($id), [
                    'Content-Type' => 'application/json',
                    'headers'      => $this->authHeader(),
                ]);

                ResponseHandler::checkResult($response, $this->query);
            }

            return true;
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function softDelete(string $layout, ?int $recordId = null): FluentFM
    {
        return $this->update(
            $layout,
            [ 'deleted_at' => date('m/d/Y H:i:s') ],
            $recordId
        )->whereEmpty('deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function undelete(string $layout, ?int $recordId = null): FluentFM
    {
        return $this->update(
            $layout,
            [ 'deleted_at' => '' ],
            $recordId
        )->withDeleted();
    }

    /**
     * {@inheritdoc}
     */
    public function fields(string $layout): array
    {
        if (isset($this->field_cache[$layout])) {
            return $this->field_cache[$layout];
        }

        $id          = $this->create($layout);
        $temp_record = $this->record($layout, $id)->first();
        $fields      = array_keys($temp_record);
        $this->delete($layout, $id)->exec();

        return $this->field_cache[$layout] = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
        if (! $this->token) {
            return;
        }

        $this->client->delete('sessions/' . $this->token, [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function exec()
    {
        return $this->get();
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $results = null;

        if (! isset($this->query['query'][0]) || ! is_array($this->query['query'][0])) {
            $this->has('id');
        }

        if ($this->with_deleted === false) {
            $this->whereEmpty('deleted_at');
        }

        try {
            $results = ( $this->callback )();
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                $this->getToken();
                $results = ( $this->callback )();
            } elseif ($e instanceof RequestException && $response = $e->getResponse()) {
                ResponseHandler::checkResult($response, $this->query);
            } else {
                throw $e;
            }
        }

        $this->clearQuery();

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function latest(string $layout, string $field = 'created_at')
    {
        return $this->records($layout)->sortDesc($field)->limit(1)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function lastUpdate(string $layout, string $field = 'updated_at')
    {
        return $this->records($layout)->sortDesc($field)->limit(1)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function oldest(string $layout, string $field = 'created_at')
    {
        return $this->records($layout)->sortAsc($field)->limit(1)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        $result = $this->getResultForCurrentQuery();
        return array_shift($result);
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        $result = $this->getResultForCurrentQuery();
        return array_pop($result);
    }

    private function getResultForCurrentQuery(): array
    {
        $query = $this->query;

        $result = $this->get();
        if (count($result) === 0) {
            throw NoResultException::noResultForQuery($query);
        }

        return $result;
    }
}
