<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeConnectController extends Controller
{
    //
    private $stripe = null;
    function __construct() {
        $this->stripe =  new \Stripe\StripeClient(env('STRIPE_SECRET'));
    }

    public function createExpressAccount (Request $request) {
      $account = $this->stripe->accounts->create([
          'country' => 'US',
          'type' => 'express',
          'capabilities' => [
            'card_payments' => ['requested' => true],
            'transfers' => ['requested' => true],
          ],
          'business_type' => 'individual',
          'business_profile' => ['url' => 'https://calls.jcompsolu.com'],
        ]);

        $links = $this->stripe->accountLinks->create([
            'account' => $account->id,
            'refresh_url' => secure_url('/stripe/reauth?account_id='.$account->id),
            'return_url' => secure_url('/stripe/return?account_id='.$account->id),
            'type' => 'account_onboarding',
          ]);
        return redirect($links->url);
    }

    public function finishOnboarding (Request $request) {
      echo 'You may close this window!';
      dd($request->all());
      // TODO: grab user account and transfer funds
    }
}
