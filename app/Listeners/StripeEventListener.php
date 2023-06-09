<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{


    /**
     * Handle received Stripe webhooks.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] === 'account.updated') {
            // Handle the incoming event...
            $account_id = $event->payload['data']['object']['id'];
            $user = \App\Models\User::where('stripe_account_id', $account_id)->first();
            $transactions = $user->transactions()->where('is_complete', false)->get();
            $transactions->each(function($transaction) use ($user){
              try {
                if($transaction->amount > 99){
                  $stripe =  new \Stripe\StripeClient(env('STRIPE_SECRET'));
                  $stripe->transfers->create([
                      'amount' => floor($transaction->amount * 0.92),
                      'currency' => 'usd',
                      'destination' => $user->stripe_account_id,
                      'transfer_group' => 'TRANSACTION'.$transaction->id,
                    ]);
                  $transaction->is_complete = true;
                  $transaction->save();
                }
              } catch (\Exception $e) {
              var_dump($transaction);
              }

            });
        }
    }
}
