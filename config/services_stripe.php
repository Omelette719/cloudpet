<?php

return [
    'enabled' => env('STRIPE_ENABLED', false),
    'api_key' => env('STRIPE_API_KEY'),
    // Expect each user to have `stripe_customer_id` and `stripe_subscription_item_id` set
    // Subscription item must be a metered price configured in Stripe.
];
