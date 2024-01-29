<?php

namespace o2o\FluentFM\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use o2o\FluentFM\Exception\DataApiException;
use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Exception\TokenException;

use function base64_encode;
use function is_null;
use function sprintf;

abstract class BaseConnection
{
    /** @var Client */
    protected $client;

    /** @var callable */
    protected $callback;

    /** @var array<string, string|array> */
    protected $config;

    /** @var string */
    protected $token;

    /** @var array<string, array> */
    protected $field_cache = [];

    /**
     * @param array<string, string|array> $config
     *
     * @throws FilemakerException
     */
    public function __construct(array $config, ?Client $client = null)
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
    }

    /**
     * Get specified value from config, or if not specified
     * the entire config array.
     *
     * @return array|mixed
     */
    protected function config(?string $key = null)
    {
        return $key ? $this->config[$key] : $this->config;
    }

    /**
     * Generate authorization header.
     *
     * @return array<string, string>
     *
     * @throws FilemakerException
     */
    protected function authHeader(): array
    {
        if (!$this->token) {
            $this->getToken();
        }

        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    public function getCachedTokens(): array
    {
        $tokens = Cache::get('fm_token') ?: [];
        if (is_string($tokens)) {
            $tokens = [$tokens];
        }
        return $tokens;
    }

    public function replaceToken(string $token): bool
    {
        $tokens = $this->getCachedTokens();

        if (($key = array_search($token, $tokens)) !== false) {
            unset($tokens[$key]);
        }

        Cache::put('fm_token', array_values($tokens));

        if (count($tokens) < max($this->config('token_limit') - 2, 1)) {
            $this->token = $this->createToken();
        } else {
            $this->token = $this->getToken();
        }

        return true;
    }

    public function createToken(): string
    {
        $tokens = $this->getCachedTokens();

        $token = $this->client->post('sessions', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->config('user') . ':' . $this->config('pass')),
            ],
        ])->getHeader('X-FM-Data-Access-Token');

        if (count($token) === 0) {
            throw TokenException::noTokenReturned();
        }
        $tokens[] = $token[0];

        if (count($tokens) > $this->config('token_limit')) {
            array_shift($tokens);
        }

        Cache::put('fm_token', $tokens);

        return $token[0];
    }

    /**
     * Request api access token from server.
     *
     * @throws FilemakerException
     */
    public function getToken(bool $force = false): string
    {
        $tokens = $this->getCachedTokens();
        $index = rand(0, max(0, count($tokens) - 1));
        if (!isset($tokens[$index])) {
            $tokens[$index] = $this->createToken();
        }
        return $this->token = $tokens[$index];
    }

    public function getTokenWithRetries(int $maxRetries = 5, int $initialWait = 100, int $exponent = 2): string
    {
        try {
            $token = $this->retry(
                fn () => $this->getToken(),
                [TokenException::class, DataApiException::class],
                $maxRetries,
                $initialWait,
                $exponent
            );
        } catch (ClientException $e) {
            throw TokenException::retryFailed($maxRetries);
        }
        return $token;
    }

    /**
     * @param array<int, string> $expectedErrors
     * @return mixed
     */
    protected function retry(
        callable $callable,
        array $expectedErrors,
        int $maxRetries = 6,
        int $initialWait = 100,
        int $exponent = 2
    ) {
        try {
            return $callable();
        } catch (\Exception $e) {
            // get whole inheritance chain
            $errors = class_parents($e);
            array_push($errors, get_class($e));

            // if unexpected, re-throw
            if (!array_intersect($errors, $expectedErrors)) {
                throw $e;
            }

            // exponential backoff
            if ($maxRetries > 0) {
                usleep((int) ($initialWait * 1E3));
                return $this->retry($callable, $expectedErrors, $maxRetries - 1, $initialWait * $exponent, $exponent);
            }

            // max retries reached
            throw $e;
        }
    }
}
