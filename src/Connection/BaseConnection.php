<?php

namespace o2o\FluentFM\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Exception\TokenException;

/**
 * Class BaseConnection.
 */
abstract class BaseConnection
{

    /** @var Client */
    protected $client;

    /** @var callable */
    protected $callback;

    /** @var array */
    protected $config;

    /** @var string */
    protected $token;

    /** @var array */
    protected $field_cache = [];

    public function __construct(array $config, Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Client([
                'base_uri'        => sprintf(
                    'https://%s/fmi/data/v1/databases/%s/',
                    $this->config('host'),
                    $this->config('file')
                ),
                'verify'          => false,
                'http_errors'     => false,
                'connect_timeout' => 10,
                'timeout'         => 60,
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

    protected function authHeader() : array
    {
        if (!$this->token) {
            $this->getToken();
        }

        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }

    public function getToken(bool $force = false) : string
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
            ])->getHeader('X-FM-Data-Access-Token');

            if (count($token) === 0) {
                throw TokenException::noTokenReturned();
            }

            $token = $token[0];
            Cache::put('fm_token', $token, 60 * 14);

            return $this->token = $token;
        } catch (ClientException $e) {
            throw TokenException::unauthorized();
        }
    }
}
