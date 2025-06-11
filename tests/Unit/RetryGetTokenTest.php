<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\BaseConnectionStub;
use o2o\FluentFM\Exception\TokenException;
use PHPUnit\Framework\TestCase;

class RetryGetTokenTest extends TestCase
{
    public function testSucceeds(): void
    {
        $connection = new class extends BaseConnectionStub {
            public function getToken(bool $force = false): string
            {
                return 'token';
            }
        };

        $this->assertEquals('token', $connection->getTokenWithRetries());
    }


    public function testKeepsFailing(): void
    {
        $connection = new class extends BaseConnectionStub {
            public int $retries = -1; // First call doesn't count

            public function getToken(bool $force = false): string
            {
                $this->retries++;
                throw new TokenException('token error');
            }
        };

        try {
            $connection->getTokenWithRetries(5, 0, 0);
        } catch (\Exception $e) {
        }
        $this->assertEquals(5, $connection->retries);
    }

    public function testRetryTwice(): void
    {
        $connection = new class extends BaseConnectionStub {
            public int $retries = -2; //initial in constructor + first call doesn't count

            public function getToken(bool $force = false): string
            {
                $this->retries++;
                if ($this->retries === 2) {
                    return 'token';
                }

                throw new TokenException('token error');
            }
        };

        $token = $connection->getTokenWithRetries(5, 0, 0);
        $this->assertEquals(2, $connection->retries);
        $this->assertEquals('token', $token);
    }
}
