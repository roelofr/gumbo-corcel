<?php

declare(strict_types=1);

namespace Tests\Feature\Bots;

use App\Models\MemberReferral;
use App\Models\User;

class LeaderboardCommandTest extends BotTestCase
{
    public function ensureMigrations()
    {
        $this->afterApplicationCreated(static fn () => MemberReferral::query()->delete());
    }

    public function testCommandAsUnknown(): void
    {
        $this->issueCommand(null);

        $this->assertChatMessageSent('Je moet ingelogd zijn om dit commando te gebruiken.');
    }

    public function testCommandAsGuest(): void
    {
        $this->issueCommand($this->getGuestUser());

        $this->assertChatMessageSent('Dit commando is alleen voor leden.');
    }

    public function testEmptyList(): void
    {
        $this->issueCommand($this->getMemberUser());

        $this->assertChatMessageSent('Het leaderboard is momenteel leeg');
    }

    public function testValidList(): void
    {
        $users = factory(User::class, 4)->create();

        factory(MemberReferral::class, 10)->create([
            'user_id' => $users[0]->id,
        ]);
        factory(MemberReferral::class, 15)->create([
            'user_id' => $users[1]->id,
        ]);
        factory(MemberReferral::class, 5)->create([
            'user_id' => $users[2]->id,
        ]);
        factory(MemberReferral::class, 1)->create([
            'user_id' => $users[3]->id,
        ]);

        $this->assertEquals(10 + 15 + 5 + 1, MemberReferral::query()->count());

        $this->issueCommand($this->getMemberUser());

        $this->assertChatMessageSent(trans_choice(
            ':user with :count member|:user with :count members',
            15,
            ['user' => e($users[1]->leaderboard_name)]
        ));
        $this->assertChatMessageSent(trans_choice(
            ':user with :count member|:user with :count members',
            10,
            ['user' => e($users[0]->leaderboard_name)]
        ));
        $this->assertChatMessageSent(trans_choice(
            ':user with :count member|:user with :count members',
            5,
            ['user' => e($users[2]->leaderboard_name)]
        ));
        $this->assertChatMessageSent(trans_choice(
            ':user with :count member|:user with :count members',
            1,
            ['user' => e($users[3]->leaderboard_name)]
        ));
    }

    /**
     * Trigger the command, optionaly registering the user $user first.
     *
     * @param User|null $user User to register with Telegram. Call will originate from this user.
     * @return void
     */
    private function issueCommand(?User $user): void
    {
        if ($user) {
            $this->registerTelegramUser($user);
        }

        $this->sendPrivateMessage('/leaderboard', $user);
    }
}
