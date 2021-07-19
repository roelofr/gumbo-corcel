<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\FormLayoutContract;
use App\Helpers\Str;
use App\Models\States\Enrollment\Cancelled as CancelledState;
use App\Models\States\Enrollment\Refunded as RefundedState;
use App\Models\Traits\HasEditorJsContent;
use App\Models\Traits\HasSimplePaperclippedMedia;
use App\Nova\Flexible\Presets\ActivityForm;
use App\Traits\HasPaperclip;
use Czim\Paperclip\Contracts\AttachableInterface;
use Czim\Paperclip\Model\PaperclipTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;
use Whitecube\NovaFlexibleContent\Concerns\HasFlexible;

/**
 * A hosted activity.
 *
 * @property-read AttachmentInterface $image
 * @property int $id
 * @property \Illuminate\Support\Date $created_at
 * @property \Illuminate\Support\Date $updated_at
 * @property null|\Illuminate\Support\Date $deleted_at
 * @property null|\Illuminate\Support\Date $published_at
 * @property null|\Illuminate\Support\Date $cancelled_at
 * @property string $name
 * @property string $slug
 * @property null|string $tagline
 * @property null|string $poster
 * @property null|array $description
 * @property null|string $statement
 * @property null|string $location
 * @property null|string $location_address
 * @property string $location_type
 * @property \Illuminate\Support\Date $start_date Start date and time
 * @property \Illuminate\Support\Date $end_date End date and time
 * @property null|int $seats
 * @property bool $is_public
 * @property null|int $member_discount
 * @property null|int $discount_count
 * @property null|string $stripe_coupon_id
 * @property null|int $price
 * @property null|\Illuminate\Support\Date $enrollment_start
 * @property null|\Illuminate\Support\Date $enrollment_end
 * @property null|string $payment_type
 * @property null|string $cancelled_reason
 * @property null|\Illuminate\Support\Date $rescheduled_from
 * @property null|string $rescheduled_reason
 * @property null|\Illuminate\Support\Date $postponed_at
 * @property null|string $postponed_reason
 * @property null|mixed $enrollment_questions
 * @property null|int $role_id
 * @property null|string $image_file_name image name
 * @property null|int $image_file_size image size (in bytes)
 * @property null|string $image_content_type image content type
 * @property null|string $image_updated_at image update timestamp
 * @property null|mixed $image_variants image variants (json)
 * @property array $features
 * @property-read \Illuminate\Support\Collection $expanded_features
 * @property-read \Illuminate\Database\Eloquent\Collection<Enrollment> $enrollments
 * @property-read int $available_seats
 * @property-read null|string $description_html
 * @property-read null|int $discount_price
 * @property-read null|int $discounts_available
 * @property-read bool $enrollment_open
 * @property-read \Whitecube\NovaFlexibleContent\Layouts\Collection $flexible_content
 * @property-read string $full_statement
 * @property-read bool $is_cancelled
 * @property-read bool $is_free
 * @property-read bool $is_free_for_member
 * @property-read bool $is_postponed
 * @property-read bool $is_published
 * @property-read bool $is_rescheduled
 * @property-read null|string $location_url
 * @property-read null|string $organiser
 * @property-read string $price_label
 * @property-read null|int $total_discount_price
 * @property-read null|int $total_price
 * @property-read \Illuminate\Database\Eloquent\Collection<Payment> $payments
 * @property-read null|Role $role
 * @property-read null|array<FormLayout> $form
 * @property-read null|bool $form_is_medical True if the form contains field that are not to be exported
 */
class Activity extends SluggableModel implements AttachableInterface
{
    use HasEditorJsContent;
    use HasFlexible;
    use HasPaperclip;
    use HasSimplePaperclippedMedia;
    use PaperclipTrait;

    public const PAYMENT_TYPE_INTENT = 'intent';

    public const PAYMENT_TYPE_BILLING = 'billing';

    public const LOCATION_OFFLINE = 'offline';

    public const LOCATION_ONLINE = 'online';

    public const LOCATION_MIXED = 'mixed';

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'features' => '[]',
    ];

    /**
     * @inheritDoc
     */
    protected $dates = [
        // Management dates
        'created_at',
        'updated_at',
        'deleted_at',
        'published_at',
        'cancelled_at',

        // Start date
        'start_date',
        'end_date',

        // Enrollment date
        'enrollment_start',
        'enrollment_end',

        // Reschedule date
        'rescheduled_from',

        // Postpone date
        'postponed_at',
    ];

    /**
     * @inheritDoc
     */
    protected $casts = [
        // Description
        'description' => 'json',
        'enrollment_questions' => 'json',

        // Number of seats
        'seats' => 'int',
        'is_public' => 'bool',

        // Pricing
        'member_discount' => 'int',
        'discount_count' => 'int',
        'price' => 'int',

        // Features
        'features' => 'json',
    ];

    /**
     * Lists the next up activities.
     *
     * @throws InvalidArgumentException
     */
    public static function getNextActivities(?User $user): Builder
    {
        return self::query()
            ->where(static function (Builder $query) {
                $query
                    // Get non-ended activities...
                    ->where('end_date', '>', Date::now())
                    // ... or where the event is postponed, but only postponed
                    ->orWhere(static fn (Builder $query) => $query
                    ->whereNotNull('postponed_at')
                    ->whereNull('cancelled_at')
                    ->whereNull('rescheduled_from'), );
            })
            ->orderBy('start_date')
            ->whereAvailable($user);
    }

    /**
     * Generate the slug based on the title property.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'unique' => true,
            ],
        ];
    }

    /**
     * Returns the associated role, if any.
     *
     * @return BelongsTo
     */
    public function role(): Relation
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Returns all enrollments (both pending and active).
     *
     * @return HasMany
     */
    public function enrollments(): Relation
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Returns all made payments for this event.
     *
     * @return HasMany
     */
    public function payments(): Relation
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Returns the name of the organiser, either committee or user.
     */
    public function getOrganiserAttribute(): ?string
    {
        return optional($this->role)->title ?? optional($this->role)->name;
    }

    /**
     * Returns the number of remaining seats.
     */
    public function getAvailableSeatsAttribute(): int
    {
        // Only if there are actually places
        if ($this->seats === null) {
            return PHP_INT_MAX;
        }

        // Get enrollment count
        $occupied = $this->enrollments()
            ->whereNotState('state', [CancelledState::class, RefundedState::class])
            ->count();

        // Subtract active enrollments from active seats
        return (int) max(0, $this->seats - $occupied);
    }

    /**
     * Returns if the enrollment is still open.
     *
     * @return bool
     */
    public function getEnrollmentOpenAttribute(): ?bool
    {
        // Prevent if cancelled
        if ($this->is_cancelled) {
            return false;
        }

        // Don't re-create a timestamp every time
        $now = Date::now();

        // Cannot sell tickets after activity end
        if ($this->end_date < $now) {
            logger()->info(
                'Enrollments on {activity} closed:  Cannot sell tickets after activity end',
                ['activity' => $this],
            );

            return false;
        }

        // Cannot sell tickets after enrollment closure
        if ($this->enrollment_end !== null && $this->enrollment_end < $now) {
            logger()->info(
                'Enrollments on {activity} closed:  Cannot sell tickets after enrollment closure',
                ['activity' => $this],
            );

            return false;
        }

        // Cannot sell tickets before enrollment start
        if ($this->enrollment_start !== null && $this->enrollment_start > $now) {
            logger()->info(
                'Enrollments on {activity} closed:  Cannot sell tickets before enrollment start',
                ['activity' => $this],
            );

            return false;
        }

        // Enrollment start < now < (Enrollment end | Event end)
        return true;
    }

    /**
     * Converts contents to HTML.
     */
    public function getDescriptionHtmlAttribute(): ?string
    {
        return $this->convertToHtml($this->description);
    }

    /**
     * Enrollment form.
     *
     * @return Whitecube\NovaFlexibleContent\Layouts\Collection
     */
    public function getFlexibleContentAttribute()
    {
        // Return empty collection if Nova is disabled
        if (! Config::get('services.features.enable-nova')) {
            return new Collection();
        }

        // Map layouts with keys
        $keyedLayouts = collect(ActivityForm::LAYOUTS)
            ->mapWithKeys(static fn ($item) => [
                (new $item())->name() => $item,
            ])
            ->toArray();

        // Return flexible content
        return $this->flexible('enrollment_questions', $keyedLayouts);
    }

    /**
     * Returns the price for people with discounts.
     */
    public function getDiscountPriceAttribute(): ?int
    {
        // Return null if no discounts are available
        if (! $this->member_discount) {
            return null;
        }

        // Member price
        return max(0, $this->price - $this->member_discount);
    }

    /**
     * Returns member price with transfer costs.
     */
    public function getTotalDiscountPriceAttribute(): ?int
    {
        // Return null if no discounts are available
        if (! $this->member_discount) {
            return null;
        }

        // In case it's free
        if ($this->price - $this->member_discount <= 0) {
            return 0;
        }

        // Otherwise, use with transfer cost
        return max(0, $this->total_price - $this->member_discount);
    }

    /**
     * Returns the number of discounts available, if any.
     */
    public function getDiscountsAvailableAttribute(): ?int
    {
        // None if no discount is available
        if (! $this->member_discount || ! $this->discount_count) {
            return null;
        }

        // Count them
        $usedEnrollments = $this->enrollments()
            ->whereNotState('state', [CancelledState::class, RefundedState::class])
            ->where('user_type', 'member')
            ->count();

        // Return enrollments with discount that have been paid
        return (int) max(0, $this->discount_count - $usedEnrollments);
    }

    /**
     * Returns guest price with transfer cost.
     */
    public function getTotalPriceAttribute(): ?int
    {
        return $this->price ? $this->price + config('gumbo.transfer-fee', 0) : $this->price;
    }

    /**
     * Returns human-readable summary of the ticket price.
     */
    public function getPriceLabelAttribute(): string
    {
        if ($this->is_free || $this->total_price <= 0) {
            // If it's free, mention it
            return 'gratis';
        }

        // No discount
        if ($this->total_discount_price === null) {
            // Return total price as single price point
            return Str::price($this->total_price);
        }

        // Members might have free entry
        if ($this->total_discount_price === 0) {
            // Free for all members
            return 'gratis voor leden';
        }

        // Discounted for all members
        return sprintf('Vanaf %s', Str::price($this->total_discount_price ?? $this->total_price));
    }

    /**
     * Returns if members can go for free.
     */
    public function getIsFreeForMemberAttribute(): bool
    {
        return $this->is_free ||
            ($this->member_discount === $this->price && $this->discount_count === null);
    }

    /**
     * Returns true if the activity is free.
     */
    public function getIsFreeAttribute(): bool
    {
        return $this->total_price === null;
    }

    /**
     * Returns if the activity has been cancelled.
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Returns if the activity was rescheduled to a different date.
     */
    public function getIsRescheduledAttribute(): bool
    {
        return $this->rescheduled_from !== null;
    }

    /**
     * Returns if the activity was postponed to an as-of-yet unknown date.
     */
    public function getIsPostponedAttribute(): bool
    {
        return $this->postponed_at !== null;
    }

    /**
     * Returns if the activity is published.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->published_at === null || $this->published_at < \now();
    }

    /**
     * Returns collection of applied icons, with label and icon.
     */
    public function getExpandedFeaturesAttribute(): Collection
    {
        $featureIcons = collect();

        foreach (collect($this->features)->filter()->keys() as $feature) {
            $featureIcon = Config::get("gumbo.activity-features.{$feature}.icon");
            if (! file_exists(storage_path("app/font-awesome/{$featureIcon}.svg"))) {
                continue;
            }

            $featureIcons->push((object) [
                'icon' => $featureIcon,
                'title' => Config::get("gumbo.activity-features.{$feature}.title"),
            ]);
        }

        return $featureIcons;
    }

    /**
     * Only return activities available to this user.
     *
     * @param User $user
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAvailable(Builder $query, ?User $user = null): Builder
    {
        $user ??= request()->user();
        \assert($user === null || $user instanceof User);

        // Add public-only when not a member
        if (! $user || ! $user->is_member) {
            $query = $query->whereIsPublic(true);
        }

        // Only return published
        return $query->wherePublished();
    }

    /**
     * Only return published activities.
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublished(Builder $query): Builder
    {
        return $query->where(static fn (Builder $query) => $query
            ->whereNull('published_at')
            ->orWhere('published_at', '<', \now()), );
    }

    /**
     * Returns url to map provider for the given address.
     */
    public function getLocationUrlAttribute(): ?string
    {
        // Skip if empty
        if (empty($this->location_address)) {
            return null;
        }

        // Build HERE maps link
        return sprintf(
            'https://www.qwant.com/maps/?%s',
            \http_build_query(['q' => $this->location_address]),
        );
    }

    /**
     * Returns a complete statement, up to 22 characters long.
     */
    public function getFullStatementAttribute(): string
    {
        if (! empty($this->statement)) {
            return Str::limit(Str::ascii("Gumbo {$this->statement}", 'nl'), 22, '');
        }

        return 'Gumbo Millennium';
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ActivityMessage::class);
    }

    /**
     * Returns the form fields interpreted as a form field.
     *
     * @return null|array<FormLayout>
     */
    public function getFormAttribute(): ?array
    {
        $fields = [];

        foreach ($this->flexible_content ?? [] as $field) {
            if (! $field instanceof FormLayoutContract) {
                continue;
            }

            $fields[] = $field->toFormField();
        }

        return $fields;
    }

    /**
     * If the form contains medical data, we can't export the data.
     * This method should check that somehow.
     */
    public function getFormIsMedicalAttribute(): ?bool
    {
        if ($this->form === null) {
            return false;
        }

        $medicalNames = Config::get('gumbo.activity.medical-titles');

        foreach ($this->form as $field) {
            $fieldLabel = Arr::get($field->getOptions(), 'label');

            if (! $fieldLabel || $field->getType() === 'static') {
                continue;
            }

            $fieldLabel = Str::lower($fieldLabel);
            if (Str::contains($fieldLabel, $medicalNames)) {
                return true;
            }
        }

        return false;
    }

    public function scopeWhereHasFeature(Builder $query, string $feature): Builder
    {
        return $query->where("features.{$feature}", '=', true);
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) Arr::get($this->features ?? [], $feature, false);
    }

    /**
     * Binds paperclip files.
     */
    protected function bindPaperclip(): void
    {
        // Sizes
        $this->createSimplePaperclip('image', [
            'cover' => [768, 256, true],
        ]);
    }
}
