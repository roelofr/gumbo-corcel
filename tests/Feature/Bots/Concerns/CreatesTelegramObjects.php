<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Concerns;

use App\Models\User;
use Telegram\Bot\Objects\User as TelegramUser;

trait CreatesTelegramObjects
{
    /**
     * Returns a telegram user for the User, or a random one if unset.
     *
     * @param User|null $user
     * @return TelegramUser
     */
    protected function getTelegramUser(?User $user = null): TelegramUser
    {
        return new TelegramUser([
            'id' => optional($user)->telegram_id ?? $this->faker->randomNumber,
            'isBot' => false,
            'firstName' => $this->faker->firstName,
        ]);
    }
}
