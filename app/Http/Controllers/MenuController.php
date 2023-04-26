<?php
/*

TODO: Make the express onboarding functionality.
https://stripe.com/docs/connect/express-accounts
*/
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;
class MenuController extends Controller
{
  private $stripe = null;
  function __construct() {
      $this->stripe =  new \Stripe\StripeClient(env('STRIPE_SECRET'));
  }
  public function handleMenu(Request $request)
{
  $selectedOption = $request->input('Digits');
  $response = new VoiceResponse();

  switch ($selectedOption) {
      case 1:
          // Forward call to Jyrone Parker
          $response->say('You selected to speak to the IT department. Please wait while we transfer your call.');
          $response->dial('+18594024863');
          break;
      case 2:
        $gather = $response->gather(['numDigits' => 10, 'action' => secure_url('api/send-money-start')]);

        $gather->say('Welcome to J Comp Pay! J Computer Solutions peer-to-peer payment system');
        $gather->say('All you need is a cell number and your debit card!');



        $gather->say('To get started put in the 10 digit cell phone number that is receiving the funds!');

      break;
      default:
          // Handle invalid input
          $response->say('Invalid selection. Please try again.', ['voice' => 'alice']);
          $response->gather(['numDigits' => 1, 'action' => '/api/menu']);
          break;
  }

  echo $response;
}

public function startSendMoney (Request $request) {
  $response = new VoiceResponse();
  $num = $request->input('Digits');
  $gather = $response->gather(['numDigits' => 6, 'action' => secure_url('api/send-money-get-funds?num='.$num)]);

  $gather->say('Input the desired amount to send. You can send up to $1000.');
  $gather->say('Please input the amount in cents. For example to send $100 you would enter 10000');
  echo $response;
  }

public function getCardInfo (Request $request) {
  $response = new VoiceResponse();
  $value = $request->input('Digits');
  $num = $request->input('num');
  $response->pay([
    'paymentConnector' => 'Stripe_Connector_Test',
    'tokenType' => 'one-time',
    'chargeAmount' => number_format(($value /100), 2, '.', ' '),
    'action' => secure_url('/api/twilio/incoming/payment/'.$num.'/value/'.$value)
  ]);

  echo $response;
}

public function generateMenuTwiml()
{
    $response = new VoiceResponse();
    $gather = $response->gather(['numDigits' => 1, 'action' => '/api/menu']);

    $gather->say('Press 1 to get IT help.');
    $gather->say('Press 2 to send money using J Comp Pay!');

    echo $response;
}

public function pay(Request $request, $num, $value) {
  $response = new VoiceResponse();
  $response->say('Your payment has been taken, your confirmation code is: '. $request['PaymentConfirmationCode']);

  $this->sendMessageToRec($num, $request->input('From'), $value);
  $this->sendMessageToSend($request->input('From'), $value, $request['PaymentConfirmationCode']);

  echo $response;
  }

  private function sendMessageToRec($num, $from, $value) {
    $user = \App\Models\User::firstOrNew([
      'phone_number' => $num
    ], [
      'password' => bcrypt('abc123'),
      'name' => $num
    ]);
    $account = NULL;
    if($user->stripe_account_id === null) {
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
        $user->stripe_account_id = $account->id;
        $user->save();
    }
    $account_id = is_null($account)  ? $user->stripe_account_id : $account->id;
    $links = $this->stripe->accountLinks->create([
        'account' => $account_id,
        'refresh_url' => secure_url('/stripe/reauth?account_id='.$account_id),
        'return_url' => secure_url('/stripe/return?account_id='.$account_id),
        'type' => 'account_onboarding',
      ]);
    $transaction = \App\Models\Transaction::Create([
      'from' => $from,
      'to' => $num,
      'amount' => $value,
      'user_id' => $user->id,
      'stripe_transaction_id' =>
    ]);

    $twilio_number = env('TWILIO_ACCOUNT_NUMBER');
    $body = 'SOMEONE SENT YOU $'.number_format(($value /100), 2, '.', ' ').'! To claim it go to '.$links->url;
    $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
    $client->messages->create(
        // Where to send a text message (your cell phone?)
        $num,
        [
            'from' => $twilio_number,
            'body' => $body
        ]
    );
  }

  private function sendMessageToSend($num, $value, $transaction_id) {
    $twilio_number = env('TWILIO_ACCOUNT_NUMBER');
    $url = secure_url('/');
    $body = 'Thank you for sending $'.number_format(($value /100), 2, '.', ' ').' with J Comp Pay! Your transaction ID is '. $transaction_id . ' keep this for your records.';

    $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
    $client->messages->create(
        // Where to send a text message (your cell phone?)
        $num,
        [
            'from' => $twilio_number,
            'body' => $body
        ]
    );
  }
}
