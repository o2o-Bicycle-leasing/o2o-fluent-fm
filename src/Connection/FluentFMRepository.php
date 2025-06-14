<?php

namespace o2o\FluentFM\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
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

    /** @var bool */
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

    /** @return array<string,int|string> */
    public function getClientHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getTokenWithRetries(3),
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
            $response = $this->client->get(Url::records($layout, $id), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'query'        => $this->queryString(),
            ]);

            Response::check($response, $this->queryString());

            return Response::records($response, $this->with_portals);
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $layout): FluentFM
    {
        $this->callback = function () use ($layout) {
            $response = $this->client->post(Url::find($layout), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => array_filter($this->query),
            ]);

            Response::check($response, array_filter($this->query));

            return Response::records($response, $this->with_portals);
        };

        return $this;
    }

    public function findPaginated(string $layout, int $page = 1, int $perPage = 10): FluentFM
    {
        $this->callback = function () use ($layout, $page, $perPage) {
            $this->limit($perPage);
            $this->offset(($page - 1) * $perPage + 1);

            $response = $this->client->post(Url::find($layout), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => array_filter($this->query),
            ]);

            Response::check($response, array_filter($this->query));

            return Response::paginatedRecords($response, $page, $perPage, $this->with_portals);
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     * @param array<int|string, mixed> $portals
     */
    public function create(string $layout, array $fields = [], array $portals = [])
    {
        if (! array_key_exists('id', $fields) && $this->auto_id) {
            $fields['id'] = Uuid::uuid4()->toString();
        }

        $this->callback = function () use ($layout, $fields, $portals) {
            $json = [ 'fieldData' => $fields ];
            if (count($portals) > 0) {
                $json['portalData'] = $portals;
            }
            $response = $this->client->post(Url::records($layout), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => $json,
            ]);

            Response::check($response, [ 'fieldData' => $fields ]);

            return (int) Response::body($response)->response->recordId;
        };

        return $this->exec();
    }

    /**
     * Creates new filemaker record on table.
     *
     * @param array<int|string, array|mixed> $body
     *
     * @return int|mixed
     *
     * @throws FilemakerException
     */
    public function broadcast(array $body)
    {
        $layout         = 'API_request';
        $this->callback = function () use ($layout, $body) {
            $response = $this->client->post(Url::records($layout), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => $body,
            ]);

            Response::check($response, [ 'fieldData' => array_filter($body) ]);

            return (int) Response::body($response)->response->recordId;
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

            $response = $this->client->patch(Url::globals(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => [ 'globalFields' => array_filter($globals) ],
            ]);

            Response::check($response, [ 'globalFields' => array_filter($globals) ]);

            return true;
        };

        return $this->exec();
    }

    /**
     * {@inheritdoc}
     * @param array<int|string, mixed> $deleteRelated
     * @param array<int|string, mixed|array> $portals
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
                $response = $this->client->patch(Url::records($layout, $id), [
                    'Content-Type' => 'application/json',
                    'headers'      => $this->authHeader(),
                    'json'         => $json,
                ]);

                Response::check($response, [ 'fieldData' => $fields ]);
            }
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rawUpdate(string $layout, int $recordId, array $json): FluentFM
    {
        $this->callback = function () use ($layout, $recordId, $json) {
            $response = $this->client->patch(Url::records($layout, $recordId), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => $json,
            ]);

            Response::check($response, $json);
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
                $response = $this->client->post(Url::container($layout, $field, $id), [
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

                Response::check($response, [
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
    public function uploadStream(string $layout, string $field, $fileStream, string $filename, ?int $recordId = null): FluentFM
    {
        $this->callback = function () use ($layout, $field, $fileStream, $filename, $recordId) {
            $recordIds = $recordId ? [ $recordId ] : array_keys($this->find($layout)->get());

            foreach ($recordIds as $id) {
                $response = $this->client->post(Url::container($layout, $field, $id), [
                    'Content-Type' => 'multipart/form-data',
                    'headers'      => $this->authHeader(),
                    'multipart'    => [
                        [
                            'name'     => 'upload',
                            'contents' => $fileStream,
                            'filename' => $filename,
                        ],
                    ],
                ]);

                Response::check($response, [
                    'multipart' => [
                        [
                            'name'     => 'upload',
                            'contents' => '...',
                            'filename' => $filename,
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
            $url = parse_url($record[$field]);
            if ($url && isset($url['path'])) {
                $ext = pathinfo(
                    $url['path'],
                    PATHINFO_EXTENSION
                );

                $filename = sprintf('%s/%s.%s', $output_dir, $recordId, $ext ? $ext : 'pdf');
                $response = $downloader->get($record[$field]);

                file_put_contents(
                    $filename,
                    $response->getBody()->getContents()
                );
            }
        }

        $downloader = null;

        return $this;
    }

    public function downloadFromPath(string $path, string $filename, string $output_dir): string
    {
        // Ensure the output directory exists
        if (!is_dir($output_dir) && !mkdir($output_dir, 0775, true) && !is_dir($output_dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $output_dir));
        }

        // Initialize the downloader client
        $downloader = new Client([
            'verify'  => false,
            'headers' => $this->authHeader(),
            'cookies' => true,
        ]);

        // Determine the full filename with extension
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $filename = sprintf('%s/%s.%s', $output_dir, $filename, $ext ? $ext : 'pdf');
        } else {
            $filename = sprintf('%s/%s', $output_dir, $filename);
        }

        // Remove query parameters from the filename, if present
        if (strpos($filename, '?')) {
            $filenameParts = explode('?', $filename);
            $filename = $filenameParts[0];
        }

        // Perform the file download
        $response = $downloader->get($path);

        // Write the downloaded content to the file
        file_put_contents($filename, $response->getBody()->getContents());

        // Validate the file exists and is not empty
        if (!file_exists($filename) || filesize($filename) === 0) {
            throw new RuntimeException(sprintf('Failed to download file to "%s"', $filename));
        }

        $downloader = null;

        return $filename;
    }


    public function downloadFromPathToDisk(string $path, string $filename, string $disk, string $diskPath): string
    {
        $downloader = new Client([
            'verify'  => false,
            'headers' => $this->authHeader(),
            'cookies' => true,
        ]);

        if (strpos($filename, '?')) {
            $filenameParts = explode('?', $filename);
            $filename      = $filenameParts[0];
        }
        $response = $downloader->get($path);

        Storage::disk($disk)->put($diskPath . '/' . $filename, $response->getBody()->getContents());
        $downloader = null;

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
                $response = $this->client->delete(Url::records($layout, $id), [
                    'Content-Type' => 'application/json',
                    'headers'      => $this->authHeader(),
                ]);

                Response::check($response, $this->query);
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
    public function fields(string $layout): FluentFM
    {
        $this->callback = function () use ($layout) {
            $response = $this->client->get('layouts/' . $layout, [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => array_filter($this->query),
            ]);
            Response::check($response, array_filter($this->query));

            return Response::fields($response);
        };

        return $this;
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
                $this->replaceToken($this->token);
                $results = ( $this->callback )();
            } elseif ($e instanceof RequestException && $response = $e->getResponse()) {
                Response::check($response, $this->query);
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

    /**
     * @return array<int|string, array|mixed>
     */
    private function getResultForCurrentQuery(): array
    {
        $query = $this->query;

        $result = $this->get();
        if (count($result) === 0) {
            throw NoResultException::noResultForQuery($query);
        }

        return $result;
    }

    public static function escapeFindRequest(string $param): string
    {
        $characters = ['@', '!', '*', '"', '?', '#', '=', '~'];
        foreach ($characters as $character) {
            $param = str_replace($character, '\\' . $character, $param);
        }

        return $param;
    }
}
