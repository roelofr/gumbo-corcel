<?php

declare(strict_types=1);

namespace App\Services\Traits;

use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Source;

trait HandlesStripeSources
{
    /**
     * Returns a single source for the given enrollment, as long as it has the
     * same bank.
     * @param Enrollment $enrollment
     * @param null|string $bank
     * @return App\Contracts\Source
     */
    public function getSource(Enrollment $enrollment, ?string $bank): Source
    {
        if ($enrollment->payment_source) {
            try {
                // Get active source
                $source = Source::retrieve($enrollment->payment_source);

                // Validation disabled, return most-recent source
                if (!$bank) {
                    return $source;
                }

                // Validation enabled, make sure we can use this source.
                if (
                    $bank &&
                    object_get($source, 'ideal.bank') === $bank &&
                    $source->status == Source::STATUS_PENDING
                ) {
                    return $source;
                }
            } catch (ApiErrorException $exception) {
                // Bubble any non-404 errors
                $this->handleError($exception, 404);
            }
        }

        // Don't return a new source on blank requests
        if (!$bank) {
            throw new RuntimeException('Not found', 404);
        }

        try {
            // Create customer
            $source = Source::create([
                'type' => 'ideal',
                'amount' => $enrollment->total_price,
                'currency' => 'eur',
                'flow' => 'redirect',
                'metadata' => [
                    'activity-id' => $enrollment->activity->id,
                    'enrollment-id' => $enrollment->id,
                    'user-id' => $enrollment->user->id,
                ],
                'redirect' => [
                    'return_url' => route('enroll.pay-return', ['activity' => $enrollment->activity])
                ],
                'ideal' => [
                    'bank' => $bank
                ],
                'statement_descriptor' => $enrollment->activity->full_statement
            ]);

            // Bind to customer
            // Associate new source with user
            $source = Customer::createSource($enrollment->user->stripe_customer_id, [
                'source' => $source->id,
            ]);

            // Update user
            $enrollment->payment_source = $source->id;
            $enrollment->save(['payment_source']);

            // Return customer
            return $source;
        } catch (ApiErrorException $exception) {
            // Bubble all
            $this->handleError($exception);
        }
    }

    /**
     * Builds a redirect to fulfill the Source's payment, if applicable.
     *
     * @param Source $source
     * @return null|RedirectResponse
     */
    public function getSourceRedirect(Source $source): ?RedirectResponse
    {
        $redirectStatus = \object_get($source, 'redirect.status');
        $redirectUrl = \object_get($source, 'redirect.url');

        // Redirect to payment page
        if ($redirectStatus === Source::STATUS_PENDING && $redirectUrl) {
            // Redirect
            return response()
                ->redirectTo($redirectUrl)
                ->setPrivate();
        }

        // Can't redirect yet
        return null;
    }
}