<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Concerns;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

trait CapturesHttpTraffic
{
    protected MockHandler $telegramMock;
    protected $telegramHistory = [];

    /**
     * Preps the Telegram SDK to use a mock client instead of
     * the real deal.
     *
     * @before
     */
    public function bindHttpMockerBeforeTests(): void
    {
        // Prep mock and allow users to add mocks
        $this->telegramMock = new MockHandler(
            array_fill(0, 20, new Response(HttpResponse::HTTP_NO_CONTENT))
        );

        // Prep stack with history
        $telegramStack = HandlerStack::create($this->telegramMock);
        $telegramStack->push(Middleware::history($this->telegramHistory));

        // Prep client
        $guzzleClient = new Client([
            'handler' => $telegramStack,
        ]);

        // Prep SDK client wrapper
        $telegramBotApiClient = new GuzzleHttpClient($guzzleClient);

        // Bind it
        $this->afterApplicationCreated(static function () use ($telegramBotApiClient) {
            Config::set('telegram.http_client_handler', $telegramBotApiClient);
        });
    }

    /**
     * @return Generator<array<Request,Response>>
     */
    protected function getTelegramHttpHistory(): Generator
    {
        foreach ($this->telegramHistory as $historyRow) {
            assert(is_array($historyRow));
            assert($historyRow['request'] instanceof Request);
            assert($historyRow['response'] instanceof Response);

            yield $historyRow;
        }
    }

    abstract protected function afterApplicationCreated(callable $closure);
}
