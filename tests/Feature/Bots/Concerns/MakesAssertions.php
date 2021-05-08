<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Concerns;

use Generator;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * @mixin Tests\TestCase
 */
trait MakesAssertions
{
    protected function assertMessagesSent(?int $count): void
    {
        TestCase::assertNotEmpty($this->getSentMessages());

        if ($count === null) {
            return;
        }

        TestCase::assertCount($count, $this->getSentMessages());
    }

    protected function assertNoMessagesSent(): void
    {
        TestCase::assertEmpty($this->getSentMessages());
    }

    protected function assertChatMessageSent(string $partialBody): void
    {
        $messages = collect($this->getSentMessages());

        TestCase::assertNotNull(
            $messages->first(static fn ($row) => Str::contains($row, $partialBody)),
            sprintf(
                'Cannot find message containing [%s] in [%s]',
                $partialBody,
                $messages->join('], ['),
            )
        );
    }

    protected function assertChatMessageNotSent(string $partialBody): void
    {
        $messages = collect($this->getSentMessages());

        TestCase::assertNull(
            $messages->first(static fn ($row) => Str::contains($row, $partialBody)),
            sprintf(
                'Found message containing [%s] in [%s]',
                $partialBody,
                $messages->join('], ['),
            )
        );
    }

    /**
     * Returns messages
     *
     * @return Generator<string>
     * @throws InvalidArgumentException
     */
    private function getSentMessages(): Generator
    {
        foreach ($this->getTelegramHttpHistory() as $row) {
            $request = $row['request'];
            assert($request instanceof Request);

            if (!Str::endsWith($request->getUri()->getPath(), '/sendMessage')) {
                continue;
            }

            $body = [];
            parse_str((string) $request->getBody(), $body);

            if (!Arr::has($body, 'text')) {
                continue;
            }

            yield $body['text'];
        }
    }

    /**
     * @return Generator<array<Request,Response>>
     */
    abstract protected function getTelegramHttpHistory(): Generator;
}
