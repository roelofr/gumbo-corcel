<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsItem;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class NewsItemTest extends TestCase
{
    public function test_empty_index(): void
    {
        // Get news index
        $this->get(route('news.index'))
            ->assertOk();
    }

    public function test_valid_news_items_are_shown(): void
    {
        $item = $this->getTestNewsItem();

        // Get news index
        $this->get(route('news.index'))
            ->assertOk()
            ->assertSeeText($item->title);

        // Get news display
        $this
            ->get(route('news.show', $item))
            ->assertOk()
            ->assertSeeText($item->title);
    }

    public function test_item_deletion(): void
    {
        $item = $this->getTestNewsItem();
        $item->delete();

        // Get news index
        $this->get(route('news.index'))
            ->assertOk()
            ->assertDontSeeText($item->title);

        // Get news display
        $this
            ->get(route('news.show', $item))
            ->assertNotFound();
    }

    public function test_index_without_deleted_item(): void
    {
        $item = $this->getTestNewsItem();
        $item->delete();

        // Get news index
        $response = $this->get(route('news.index'));

        // Ensure it loads
        $response->assertOk();

        // Check if we cannot see our article
        $response->assertDontSeeText($item->title);
    }

    public function test_publication_dates(): void
    {
        $published = $this->getTestNewsItem([
            'published_at' => Date::now()->subHour(),
        ]);
        $unpublished = $this->getTestNewsItem([
            'published_at' => Date::now()->addHour(),
        ]);

        // Get news index
        $this->get(route('news.index'))
            ->assertOk()
            ->assertSeeText($published->title)
            ->assertDontSeeText($unpublished->title);

        // Get published item
        $this->get(route('news.show', $published))
            ->assertOk()
            ->assertSeeText($published->title);

        // Get unpublished item
        $this->get(route('news.show', $unpublished))
            ->assertNotFound();
    }

    private function getTestNewsItem(array $attributes = []): NewsItem
    {
        return factory(NewsItem::class)->create($attributes);
    }
}
