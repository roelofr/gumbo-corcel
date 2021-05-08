<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Concerns;

use App\Models\User;
use Generator;
use Illuminate\Support\Facades\Date;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\EditedMessage;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

trait SendsTelegramMessages
{
    /**
     * Sends a private message to the bot command
     *
     * @param string $message
     * @param User|null $user User issuing the message, random if unspecified
     * @return Update
     */
    protected function sendPrivateMessage(string $message, ?User $user = null): Update
    {
        $message = new Message([
            'messageId' => $this->faker->randomNumber,
            'from' => $tgUser = $this->getTelegramUser($user),
            'date' => Date::now()->getTimestamp(),
            'text' => $message,
            'chat' => new Chat([
                'id' => $tgUser->id,
                'type' => 'private',
                'username' => $tgUser->firstName,
                'firstName' => $tgUser->firstName,
            ]),
            'entities' => [...$this->parseEntities($message)],
        ]);

        return $this->sendUpdate($message, null);
    }


    /**
     * Sends a group chat message to the bot command
     *
     * @param string $message
     * @param User|null $user User issuing the message, random if unspecified
     * @return Update
     */
    protected function sendChatMessage(string $message, ?User $user = null): Update
    {
        $message = new Message([
            'messageId' => $this->faker->randomNumber,
            'from' => $this->getTelegramUser($user),
            'date' => Date::now()->getTimestamp(),
            'text' => $message,
            'chat' => new Chat([
                'id' => $this->faker->randomNumber,
                'type' => $this->faker->randomElement(['group', 'supergroup']),
                'title' => $this->faker->sentence,
            ]),
            'entities' => [...$this->parseEntities($message)],
        ]);

        return $this->sendUpdate($message, null);
    }

    /**
     * Sends the given message or message update to the bot handler.
     *
     * @param Message|null $message
     * @param EditedMessage|null $editedMessage
     * @return Update
     */
    protected function sendUpdate(?Message $message = null): Update
    {
        $update = new Update(array_filter([
            'updateId' => $this->faker->randomNumber,
            'message' => $message,
        ]));

        Telegram::processCommand($update);

        return $update;
    }
    /**
     * Converts a message to entities
     *
     * @param string $message
     * @return Generator
     */
    private function parseEntities(string $message): Generator
    {
        if (!preg_match_all('/(?<cmd>\/[a-z0-9_-]+(?:\@[a-z0-9_-]+)?)/', $message, $matches, PREG_OFFSET_CAPTURE)) {
            $matches = ['cmd' => []];
        }

        foreach ($matches['cmd'] as $match) {
            yield [
                'type' => 'bot_command',
                'offset' => $match[1],
                'length' => strlen($match[0]),
            ];
        }
    }
}
