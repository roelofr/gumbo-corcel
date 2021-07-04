<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Payments\PayableModel;
use App\Models\States\Enrollment\Cancelled as CancelledState;
use App\Models\States\Enrollment\Confirmed as ConfirmedState;
use App\Models\States\Enrollment\Created as CreatedState;
use App\Models\States\Enrollment\Paid as PaidState;
use App\Models\States\Enrollment\Refunded as RefundedState;
use App\Models\States\Enrollment\Seeded as SeededState;
use App\Models\States\Enrollment\State as EnrollmentState;
use App\Models\Traits\IsPayable;
use App\Services\Payments\Order;
use App\Services\Payments\OrderLine;
use AustinHeap\Database\Encryption\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Spatie\ModelStates\HasStates;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A user enrollment for an activity. Optionally has payments.
 *
 * @property States\Enrollment\State $state
 * @property string $id
 * @property int $user_id
 * @property int $activity_id
 * @property \Illuminate\Support\Date $created_at
 * @property \Illuminate\Support\Date $updated_at
 * @property null|\Illuminate\Support\Date $deleted_at
 * @property null|string $deleted_reason
 * @property null|int $price
 * @property null|int $total_price
 * @property null|string $payment_intent
 * @property null|string $payment_invoice
 * @property null|string $payment_source
 * @property string $user_type
 * @property null|\Illuminate\Support\Date $expire
 * @property null|string $transfer_secret
 * @property null|\Illuminate\Support\Collection $data
 * @property-read Activity $activity
 * @property-read bool $is_discounted
 * @property-read bool $is_stable
 * @property-read bool $requires_payment
 * @property-read null|States\Enrollment\State $wanted_state
 * @property-read \Illuminate\Database\Eloquent\Collection<Payment> $payments
 * @property-read User $user
 * @property-read null|array<scalar> $form The form data ready for export
 * @property-read null|array<scalar> $form_data The form data to supply to the form builder
 * @property-read null|bool $is_form_exportable True if the form can be exported
 */
class Enrollment extends UuidModel implements PayableModel
{
    use HasEncryptedAttributes;
    use HasStates;
    use IsPayable;
    use SoftDeletes;

    public const USER_TYPE_MEMBER = 'member';

    public const USER_TYPE_GUEST = 'guest';

    /**
     * @inheritDoc
     */
    protected $encrypted = [
        'data',
    ];

    /**
     * @inheritDoc
     */
    protected $casts = [
        'data' => 'json',
        'paid' => 'bool',
    ];

    /**
     * @inheritDoc
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'expire',
    ];

    /**
     * Finds the active enrollment for this activity.
     */
    public static function findActive(User $user, Activity $activity): ?self
    {
        return self::query()
            ->withoutTrashed()
            ->whereUserId($user->id)
            ->whereActivityId($activity->id)
            ->whereNotState('state', [CancelledState::class, RefundedState::class])
            ->with(['activity'])
            ->first();
    }

    /**
     * Finds the active enrollment for this activity, or throws a 404 HTTP exception.
     *
     * @throws NotFoundHttpException if there is no enrollment present
     */
    public static function findActiveOrFail(User $user, Activity $activity): self
    {
        $result = self::findActive($user, $activity);
        if ($result) {
            return $result;
        }

        throw new NotFoundHttpException();
    }

    /**
     * An enrollment can have multiple payments (in case one failed, for example).
     *
     * @return HasMany
     */
    public function payments(): Relation
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The user this enrollment belongs to.
     *
     * @return BelongsTo
     */
    public function user(): Relation
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The activity this enrollment belongs to.
     *
     * @return BelongsTo
     */
    public function activity(): Relation
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Returns true if the state is stable and will not auto-delete.
     */
    public function getIsStableAttribute(): bool
    {
        return $this->state instanceof ConfirmedState;
    }

    /**
     * Returns if the enrollment is discounted.
     */
    public function getIsDiscountedAttribute(): bool
    {
        return $this->price === $this->activity->discount_price;
    }

    /**
     * Returns state we want to go to, depending on Enrollment's own attributes.
     * Returns null if it can't figure it out.
     *
     * @return null|App\Models\States\Enrollment\State
     */
    public function getWantedStateAttribute(): ?EnrollmentState
    {
        // First check for any transition
        $options = $this->state->transitionableStates();
        if (in_array(SeededState::$name, $options, true) && $this->activity->form) {
            return new SeededState($this);
        }

        if (in_array(PaidState::$name, $options, true) && $this->price) {
            return new PaidState($this);
        }

        if (in_array(ConfirmedState::$name, $options, true)) {
            return new ConfirmedState($this);
        }

        return null;
    }

    public function getRequiresPaymentAttribute(): bool
    {
        return $this->exists &&
            $this->total_price &&
            ! ($this->state instanceof CancelledState);
    }

    /**
     * Stores the enrollment data on this user.
     */
    public function setFormData(array $values): void
    {
        // Transform data into something persistable
        $formValues = [];
        $formLabels = [];
        $formExportable = [];

        foreach ($this->activity->form as $field) {
            $rawValue = Arr::get($values, $field->getName());
            $fieldLabel = Arr::get($field->getOptions(), 'label', $field->getName());
            $fieldType = $field->getType();

            if ($fieldType === 'checkbox') {
                $rawValue = (bool) $rawValue;
            }

            $formValues[$field->getName()] = $rawValue;
            $formLabels[$field->getName()] = $fieldLabel;
            $formExportable[$fieldLabel] = $rawValue;
        }

        // Store data
        $data = $this->data;

        // Assign data
        Arr::set($data, 'form.fields', $formValues);
        Arr::set($data, 'form.labels', $formLabels);
        Arr::set($data, 'form.exportable', $formExportable);
        Arr::set($data, 'form.filled', true);
        Arr::set($data, 'form.medical', (bool) $this->activity->form_is_medical);

        // Re-apply
        $this->data = $data;
    }

    /**
     * Returns the filled in form.
     */
    public function getFormAttribute(): ?array
    {
        return Arr::get($this->data, 'form.exportable');
    }

    /**
     * Returns the data for this form, as it's presented to the form builder.
     */
    public function getFormDataAttribute()
    {
        return Arr::get($this->data, 'form.fields');
    }

    /**
     * Returns if the form can be exported.
     */
    public function getIsFormExportableAttribute(): ?bool
    {
        if (! $this->form) {
            return null;
        }

        return Arr::get($this->data, 'form.medical', false) !== true;
    }

    /**
     * Returns a Mollie order from this model.
     */
    public function toMollieOrder(): Order
    {
        $order = Order::make($this->total_price, "inschrijving-{$this->id}");

        $address = $this->getPaymentAddressForUser($this->user);
        $order
            ->billingAddress($address)
            ->shippingAddress($address);

        $order->addLine(
            OrderLine::make(
                $this->activity->name,
                1,
                $this->price,
            )->productUrl(URL::route('activity.show', $this->activity)),
        );

        $order->addLine(
            OrderLine::make(
                'Transactiekosten',
                1,
                (int) ($this->total_price - $this->price),
                'surcharge',
            ),
        );

        $order
            ->redirectUrl(URL::route('enroll.pay-return', $this));

        $isLocal = in_array(parse_url(URL::to('/'), PHP_URL_HOST), [
            'localhost',
            '127.0.0.1',
            '[::1]',
        ], true);

        if (! $isLocal) {
            $order
                ->webhookUrl(URL::route('api.webhooks.activities'));
        }

        $order
            ->method('ideal')
            ->locale('nl_NL');

        $order
            ->expiresAt($this->expires_at->addDays(5));

        return $order;
    }

    /**
     * Register the states an enrollment can have.
     */
    protected function registerStates(): void
    {
        // Register enrollment state
        $this
            ->addState('state', EnrollmentState::class)

            // Default to Created
            ->default(CreatedState::class)

            // Create → Seeded
            ->allowTransition(CreatedState::class, SeededState::class)

            // Created, Seeded → Confirmed
            ->allowTransition([CreatedState::class, SeededState::class], ConfirmedState::class)

            // Created, Seeded, Confirmed → Paid
            ->allowTransition([CreatedState::class, SeededState::class, ConfirmedState::class], PaidState::class)

            // Created, Seeded, Confirmed, Paid → Cancelled
            ->allowTransition(
                [CreatedState::class, SeededState::class, ConfirmedState::class, PaidState::class],
                CancelledState::class,
            )

            // Paid, Cancelled → Refunded
            ->allowTransition(
                [PaidState::class, CancelledState::class],
                RefundedState::class,
            );
    }
}
