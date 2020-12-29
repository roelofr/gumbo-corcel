<?php

declare(strict_types=1);

use App\Models\Page;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run(Generator $faker)
    {
        foreach (Page::getRequiredPages() as $slug => $title) {
            Page::updateOrCreate(
                ['slug' => $slug, 'group' => null],
                ['title' => $title, 'type' => Page::TYPE_REQUIRED]
            );
        }

        if (App::environment(['local'])) {
            $categories = array_merge([null], $faker->words(3));
            foreach ($categories as $category) {
                factory(Page::class, $faker->numberBetween(1, 20))->create([
                    'group' => $category
                ]);
            }
        }
    }
}
