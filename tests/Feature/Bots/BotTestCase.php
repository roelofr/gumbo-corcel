<?php

declare(strict_types=1);

namespace Tests\Feature\Bots;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BotTestCase extends TestCase
{
    use WithFaker;
    use Concerns\SendsTelegramMessages;
    use Concerns\CreatesTelegramObjects;
    use Concerns\MutatesModelsWithObjects;
    use Concerns\CapturesHttpTraffic;
    use Concerns\ConfiguresTelegramSdk;
    use Concerns\MakesAssertions;
}
