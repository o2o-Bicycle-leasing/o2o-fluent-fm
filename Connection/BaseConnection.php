<?php

namespace o2o\FluentFM\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use o2o\FluentFM\Exception\FilemakerException;

/**
 * Class BaseConnection.
 */
abstract class BaseConnection
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var
     */
    protected $callback;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var array
     */
    protected $field_cache = [];

    /**
     * BaseConnection constructor.
     *
     * @param array       $config
     * @param Client|null $client
     *
     * @throws FilemakerException
     */
    public function __construct(array $config, Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Client([
                'base_uri'    => sprintf(
                    'https://%s/fmi/data/v1/databases/%s/',
                    $this->config('host'),
                    $this->config('file')
                ),
                'verify'      => false,
                'http_errors' => false,
            ]);

        $this->getToken();
    }

    /**
     * Get specified value from config, or if not specified
     * the entire config array.
     *
     * @param string|null $key
     *
     * @return array|mixed
     */
    protected function config(string $key = null)
    {
        return $key ? $this->config[ $key ] : $this->config;
    }

    /**
     * Generate authorization header.
     *
     * @throws FilemakerException
     *
     * @return array
     */
    protected function authHeader() : array
    {
        if (!$this->token) {
            $this->getToken();
        }

        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }

    /**
     * Request api access token from server.
     *
     * @throws FilemakerException
     *
     * @return string
     */
    public function getToken($force = false) : string
    {
        if (!$force && Cache::has('fm_token') && !is_null(Cache::get('fm_token'))) {
            return $this->token = Cache::get('fm_token');
        }

        try {
            $token = $this->client->post('sessions', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($this->config('user').':'.$this->config('pass')),
                ],
            ])->getHeader('X-FM-Data-Access-Token')[ 0 ];

            Cache::put('fm_token', $token, 60 * 14);

            return $this->token = $token;
        } catch (ClientException $e) {
            throw new FilemakerException('Filemaker access unauthorized - please check your credentials', 401);
        }
    }
}
