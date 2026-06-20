<?php

namespace App\Services;

use Stripe\StripeClient;
use App\Models\ComputeInstance;
use App\Models\User;

class StripeBillingService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services_stripe.api_key'));
    }

    /**
     * Record usage for a compute instance.
     * Expects user's `stripe_subscription_item_id` to be present and the price to be metered.
     * Quantity is reported in minutes (integer).
     */
    public function recordUsage(User $user, ComputeInstance $instance)
    {
        if (! config('services_stripe.enabled')) {
            return null;
        }

        $subscriptionItem = $user->stripe_subscription_item_id ?? null;
        if (! $subscriptionItem) {
            return null;
        }

        $minutes = intval(round(($instance->usage_hours ?? 0) * 60));
        if ($minutes <= 0) {
            return null;
        }

        // Create usage record (reported as total quantity since timestamp)
        try {
            $record = $this->stripe->usageRecords->create($subscriptionItem, [
                'quantity' => $minutes,
                'timestamp' => time(),
                'action' => 'increment',
            ]);
            return $record;
        } catch (\Exception $e) {
            // Log but don't break billing flow
            logger()->error('Stripe usage record error: ' . $e->getMessage());
            return null;
        }
    }
}
