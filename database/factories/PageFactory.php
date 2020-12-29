<?php

declare(strict_types=1);

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Helpers\Str;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(Page::class, static fn (Faker $faker) => [
        'title' => Str::title($faker->words($faker->numberBetween(2, 8), true)),
        'contents' => $faker->randomHtml(),
        'author_id' => optional(User::inRandomOrder()->first())->id,
        'owner_id' => $faker->optional(0.8)->passthrough(optional(Role::inRandomOrder()->first())->id),
    ]);
