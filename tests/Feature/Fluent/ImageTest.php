<?php

declare(strict_types=1);

namespace Tests\Feature\Fluent;

use App\Fluent\Image;
use DomainException;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use OutOfRangeException;
use Tests\TestCase;

class ImageTest extends TestCase
{
    public function test_simple_make(): void
    {
        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test']),
            Image::make('test')->getUrl(),
        );
    }

    public function test_image_url_works(): void
    {
        Storage::fake('public');
        Storage::fake();

        $tempImageFile = new File(resource_path('test-assets/images/dog.jpg'));
        $imagePath = Storage::disk('public')->putFile('images', $tempImageFile);

        $image = Image::make($imagePath)->square(64)->png()->getUrl();

        $this->get($image)
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_template_conversion(): void
    {
        $expected = URL::signedRoute('image.render', ['path' => 'test', 'w' => 500]);
        $actual = Image::make('test')->width(500);

        $template = <<<'HTML'
        <img src="%s" alt="test" />
        HTML;

        $this->assertSame(
            sprintf($template, $expected),
            sprintf($template, $actual),
        );
    }

    public function test_helper_works_the_same(): void
    {
        $this->assertSame(
            (string) Image::make('test'),
            (string) image_asset('test'),
        );
    }

    public function test_with_height_and_width(): void
    {
        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test', 'w' => 500, 'h' => 250]),
            Image::make('test')
                ->width(500)
                ->height(250)
                ->getUrl(),
        );
    }

    public function test_square(): void
    {
        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test', 'w' => 500, 'h' => 500, 'fit' => 'crop']),
            Image::make('test')
                ->square(500)
                ->getUrl(),
        );
    }

    public function test_invalid_height(): void
    {
        $this->expectException(OutOfRangeException::class);

        Image::make('test')->height(-16);
    }

    public function test_invalid_width(): void
    {
        $this->expectException(OutOfRangeException::class);

        Image::make('test')->width(-16);
    }

    public function test_invalid_square(): void
    {
        $this->expectException(OutOfRangeException::class);

        Image::make('test')->square(-16);
    }

    public function test_quality(): void
    {
        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test', 'q' => 30]),
            Image::make('test')
                ->quality(30)
                ->getUrl(),
        );

        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test', 'q' => 100, 'fm' => 'jpg']),
            Image::make('test')
                ->jpg(100)
                ->getUrl(),
        );

        $this->expectException(OutOfRangeException::class);
        Image::make('test')->quality(150);
    }

    /**
     * @dataProvider seedFits
     */
    public function test_set_fit(string $fit, bool $valid): void
    {
        if (! $valid) {
            $this->expectException(DomainException::class);

            Image::make('test')->fit($fit);
        }

        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test', 'fit' => $fit]),
            Image::make('test')
                ->fit($fit)
                ->getUrl(),
        );
    }

    /**
     * @dataProvider seedFormats
     */
    public function test_set_format(string $format, bool $valid, ?string $method = null): void
    {
        if (! $valid) {
            $this->expectException(DomainException::class);

            Image::make('test')->format($format);
        }

        $this->assertSame(
            URL::signedRoute('image.render', ['path' => 'test', 'fm' => $format]),
            Image::make('test')
                ->format($format)
                ->getUrl(),
        );

        if (! $method) {
            return;
        }

        $this->assertStringContainsString(
            URL::route('image.render', ['path' => 'test', 'fm' => $format]),
            Image::make('test')
                ->{$method}()
                ->getUrl(),
        );
    }

    public function seedFits(): array
    {
        return [
            'invalid' => ['invalid-fit', false],
            'contain' => [Image::FIT_CONTAIN, true],
            'max' => [Image::FIT_MAX, true],
            'fill' => [Image::FIT_FILL, true],
            'stretch' => [Image::FIT_STRETCH, true],
            'crop' => [Image::FIT_CROP, true],
        ];
    }

    public function seedFormats(): array
    {
        return [
            'invalid' => ['tiff', false],
            'gif' => [Image::FORMAT_GIF, true],
            'jpg' => [Image::FORMAT_JPG, true, 'jpg'],
            'png' => [Image::FORMAT_PNG, true, 'png'],
            'webp' => [Image::FORMAT_WEBP, true, 'webp'],
        ];
    }
}
