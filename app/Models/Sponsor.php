<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasEditorJsContent;
use App\Models\Traits\HasSimplePaperclippedMedia;
use App\Traits\HasPaperclip;
use Czim\Paperclip\Contracts\AttachableInterface;
use Czim\Paperclip\Model\PaperclipTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Gumbo Millennium sponsors.
 *
 * @property-read AttachmentInterface $backdrop
 * @property int $id
 * @property \Illuminate\Support\Date $created_at
 * @property \Illuminate\Support\Date $updated_at
 * @property null|\Illuminate\Support\Date $deleted_at
 * @property string $name Sponsor name
 * @property string $slug
 * @property string $url URL of sponsor landing page
 * @property null|\Illuminate\Support\Date $starts_at
 * @property null|\Illuminate\Support\Date $ends_at
 * @property null|int $has_page
 * @property int $view_count Number of showings
 * @property null|string $caption
 * @property null|string $logo_gray
 * @property null|string $logo_color
 * @property null|string $contents_title
 * @property null|mixed $contents
 * @property null|string $background_image
 * @property-read \Illuminate\Database\Eloquent\Collection<SponsorClick> $clicks
 * @property-read mixed $click_count
 * @property-read null|string $content_html
 * @property-read bool $is_active
 * @property-read bool $is_classic
 * @property-read null|string $logo_color_url
 * @property-read null|string $logo_gray_url
 */
class Sponsor extends SluggableModel implements AttachableInterface
{
    use HasEditorJsContent;
    use HasPaperclip;
    use HasSimplePaperclippedMedia;
    use PaperclipTrait;
    use SoftDeletes;

    public const LOGO_DISK = 'public';

    public const LOGO_PATH = 'sponsors/logos';

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'contents' => 'null',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'view_count' => 'int',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'url',
        'start_at',
        'ends_at',
        'caption',
        'logo_gray',
        'logo_color',
        'contents',
    ];

    /**
     * Generate the slug based on the display_title property.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'unique' => true,
                'onUpdate' => false,
            ],
        ];
    }

    /**
     * Returns sponsors that are available right now.
     */
    public function scopeWhereAvailable(Builder $builder): Builder
    {
        return $builder
            // Require logos
            ->whereNotNull('logo_color')
            ->whereNotNull('logo_gray')

            // Require an URL to be set
            ->whereNotNull('url')

            // Require to have started, and not ended yet
            ->where('starts_at', '<', now())
            ->where(static function ($query) {
                $query->where('ends_at', '>', now())
                    ->orWhereNull('ends_at');
            });
    }

    /**
     * Returns if this should be a classic view.
     */
    public function getIsClassicAttribute(): bool
    {
        return empty($this->background_image) || empty($this->caption);
    }

    /**
     * Returns if this sponsor is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->starts_at < now()
            && ($this->ends_at === null || $this->ends_at > now());
    }

    /**
     * Returns URL to the grayscale (currentColor) logo.
     *
     * @throws InvalidArgumentException
     */
    public function getLogoGrayUrlAttribute(): ?string
    {
        if (! $this->logo_gray) {
            return null;
        }

        return Storage::disk(self::LOGO_DISK)->url($this->logo_gray);
    }

    /**
     * Returns URL to the full color logo.
     *
     * @throws InvalidArgumentException
     */
    public function getLogoColorUrlAttribute(): ?string
    {
        if (! $this->logo_color) {
            return null;
        }

        return Storage::disk(self::LOGO_DISK)->url($this->logo_color);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(SponsorClick::class);
    }

    /**
     * Returns the number of clicks.
     */
    public function getClickCountAttribute()
    {
        return $this->clicks()->sum('count');
    }

    /**
     * Converts contents to HTML.
     */
    public function getContentHtmlAttribute(): ?string
    {
        return $this->convertToHtml($this->contents);
    }

    /**
     * Binds paperclip files.
     */
    protected function bindPaperclip(): void
    {
        // Sizes
        $this->createSimplePaperclip('backdrop', [
            'banner' => [1920, 960, true],
        ]);
    }
}
