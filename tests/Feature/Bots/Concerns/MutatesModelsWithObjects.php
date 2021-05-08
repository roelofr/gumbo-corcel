<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Concerns;

use App\Models\User;

trait MutatesModelsWithObjects
{
    /**
     * Registers this user as an existing Telegram user
     */
    protected function registerTelegramUser(User $user, ?string $id = null): string
    {
        $id ??= (string) $this->faker->randomNumber;

        $user->telegram_id = $id;
        $user->save(['telegram_id']);

        return $id;
    }
}
