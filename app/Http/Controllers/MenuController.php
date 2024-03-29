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
  private function callIt ($response) {
    // Forward call to Jyrone Parker
    $response->say(env('MENU_ITEM_ONE_SELECTED'));
    $response->dial(env('BUSINESS_NUMBER'));
  }
  public function handleMenu(Request $request)
{
  $selectedOption = $request->input('Digits');
  $response = new VoiceResponse();

  switch ($selectedOption) {
      case 1:
          $this->callIt($response);
          break;
      case 2:
        $this->startPhone($response);
      break;
      default:
          // Handle invalid input
          $response->say('Invalid selection. Please try again.', ['voice' => 'alice']);
          $response->gather(['numDigits' => 1, 'action' => '/api/menu']);
          break;
  }

  echo $response;
}

public function startPhone($response) {
  $gather = $response->gather(['numDigits' => 10, 'action' => secure_url('api/send-money-phone-confirm')]);

  $gather->say('Welcome to J Comp Pay! J Computer Solutions peer-to-peer payment system');
  $gather->say('All you need is a cell number and your debit card!');



  $gather->say('To get started put in the 10 digit cell phone number that is receiving the funds!');

}

public function confirmPhone (Request $request) {
  $response = new VoiceResponse();
  $phone = $request->input('Digits');

  $gather = $response->gather(['numDigits' => 1, 'action' => secure_url('api/send-money-start?num='.$phone)]);

  $gather->say('I heard '.implode(' ',str_split($phone)).' is that correct? Press 1 for yes and 2 for no.');
  echo $response;

}
public function startSendMoney (Request $request) {
  $response = new VoiceResponse();
  $num = $request->input('Digits');


  if($num == 1) {
    $gather = $response->gather(['numDigits' => 6, 'action' => secure_url('api/send-money-start-confirm?num='.$request->input('num'))]);

    $gather->say('Input the desired amount to send. You can send up to $999.99.');
    $gather->say('Please input the amount in cents. For example to send $100 you would enter 10000');
    echo $response;
  } else {
    $this->startPhone($response);
  }


}
public function checkAccountExists($num) {
  $isExist = \App\Models\User::select("*")
                        ->where("phone_number", substr($num, 2))
                        ->exists();
  return $isExist;
}

public function confirmStartSendMoney (Request $request) {
  $response = new VoiceResponse();
  $num = $request->input('num');
  $amt = $request->input('Digits');
  $gather = $response->gather(['numDigits' => 1, 'action' => secure_url('api/send-money-get-funds?num='.$num.'&val='.$amt)]);
  $gather->say('Just to confirm. You want to sent $'.number_format(($amt /100), 2, '.', ' ').' to '.implode(' ',str_split($num)));
  // if($this->checkAccountExists($request->input('From'))) {
  //   $gather->say('We see you have a J Comp Pay account');
  //   $gather->say('If you have the funds in your account to cover the transaction their is no fee.');
  //   $gather->say('Otherwise...');
  // }
  $gather->say('A 8% + $0.50 processing fee is added to all payments sent.');
  $gather->say('This would bring the total charged amount to $'.number_format((($amt * 1.08 +50) /100), 2, '.', ' '));
  $gather->say('Press 1 for yes. 2 for no.');
  echo $response;
}

public function getCardInfo (Request $request) {
  $response = new VoiceResponse();
  $value = $request->input('val');
  $num = $request->input('num');
  $ans = $request->input('Digits');
  if($ans == 1) {
      $response->pay([
        'paymentConnector' => 'Stripe_Connector',
        'tokenType' => 'one-time',
        'chargeAmount' => number_format((($value*1.08 + 50) /100), 2, '.', ' '),
        'action' => secure_url('/api/twilio/incoming/payment/'.$num.'/value/'.$value)
      ]);

      echo $response;
  } else {
    $this->startSendMoney($request);
  }
}

public function generateMenuTwiml()
{
    $response = new VoiceResponse();
    $gather = $response->gather(['numDigits' => 1, 'action' => '/api/menu']);

    $gather->say(env('MENU_ITEM_ONE'));
    //$gather->say(env('MENU_ITEM_TWO'));

    echo $response;
}

public function pay(Request $request, $num, $value) {
  $response = new VoiceResponse();
  $response->say('Your payment has been taken, your confirmation code has been sent to your phone.');
  $response->say('Thank you for using J Comp Pay! Goodbye!');

  $this->sendMessageToRec($num, $request->input('From'), $value, $request['PaymentConfirmationCode']);
  $this->sendMessageToSend($request->input('From'), $value, $request['PaymentConfirmationCode']);

  echo $response;
  }

  public function payWithTransfer($from, $num, $value, $charge_id, $response) {
    $response->say('Your payment has been taken, your confirmation code has been sent to your phone.');
    $response->say('Thank you for using J Comp Pay! Goodbye!');

    $this->sendMessageToRec($num, $from, $value, $charge_id);
    $this->sendMessageToSend($from, $value, $charge_id);

    echo $response;
    }

  private function sendMessageToRec($num, $from, $value, $transaction_id) {
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
          'type' => 'custom',
          // 'settings' => [
          //   'payouts' => [
          //     'schedule' => [
          //       'interval' => 'manual'
          //       ]
          //     ]
          //   ],
          'capabilities' => [
            'transfers' => ['requested' => true],
            'card_payments' => ['requested' => true]
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
      $amount = floor($value * 1.08 + 50);
    $transaction = \App\Models\Transaction::Create([
      'from' => $from,
      'to' => $num,
      'amount' => $amount,
      'user_id' => $user->id,
      'stripe_transaction_id' => $transaction_id
    ]);
    $acct = $this->stripe->accounts->retrieve($account_id);
    if($acct->details_submitted) {
      $this->stripe->transfers->create([
          'amount' => $value ,
          'currency' => 'usd',
          'destination' => $account_id,
          'transfer_group' => 'TRANSACTION'.$transaction->id,
        ]);
        $transaction->is_complete = true;
        $transaction->save();
        $balances = $stripe->balance->retrieve([], ['stripe_account' => $account_id]);
        $body = $from.' SENT YOU $'.number_format(($value /100), 2, '.', ' ').'! Your balance is: '.$balance->available->amount;

    } else {
      $body = $from.' SENT YOU $'.number_format(($value /100), 2, '.', ' ').'! To claim it go to '.$links->url;
    }
    $twilio_number = env('TWILIO_ACCOUNT_NUMBER');
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
