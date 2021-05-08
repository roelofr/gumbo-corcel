<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Concerns;

use Illuminate\Support\Facades\Config;
use Telegram\Bot\Commands\CommandBus;

trait ConfiguresTelegramSdk
{
    /**
     * Configure dummy client
     *
     * @before
     */
    public function setRandomTelegramTokenAfterCreation(): void
    {
        // Bind it
        $this->afterApplicationCreated(function () {
            foreach (Config::get('telegram.bots') as $key => $_) {
                Config::set("telegram.bots.{$key}.token", $this->faker->uuid);
            }
        });
    }

    /**
     * @after
     * @return void
     */
    public function clearCommandBusSingletonAfterCommand(): void
    {
        CommandBus::destroy();
    }

    abstract protected function afterApplicationCreated(callable $closure);
}
