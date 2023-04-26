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

    public function createExpressAccount () {
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
            'refresh_url' => secure_url('/stripe/reauth'),
            'return_url' => secure_url('/stripe/return'),
            'type' => 'account_onboarding',
          ]);

        return redirect(secure_url('/stripe/return'));
    }

    public function finishOnboarding (Request $request) {
      dd($request->all());
    }
}
