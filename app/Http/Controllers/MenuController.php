<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;
class MenuController extends Controller
{
  public function handleMenu(Request $request)
{
  $selectedOption = $request->input('Digits');
  $response = new VoiceResponse();

  switch ($selectedOption) {
      case 1:
          // Forward call to technical support
          $response->say('You selected technical support. Before we transfer you our services are billed at $50/hr with a minimum of 30 minutes. Please input your card information');
          $response->pay([
            'paymentConnector' => 'Stripe_Connector_Test',
            'tokenType' => 'reusable',
            'chargeAmount' => '0'
          ]);
          $response->dial('+18594024863');
          break;
      case 2:
          // Handle billing and account inquiries option
          $response->say('You selected software services.');
          $response->dial('+18594024863');
          break;
      case 3:
          // Forward call to sales department
          $response->say('You selected sales and product information. Please wait while we transfer your call.');
          $response->dial('+18594024863');
          break;
      case 4:
          // Forward call to Jyrone Parker
          $response->say('You selected to speak to the media department. Please wait while we transfer your call.');
          $response->dial('+18594024863');
          break;
      case 5:
      $response->pay([
        'paymentConnector' => 'Stripe_Connector_Test',
        'tokenType' => 'one-time',
        'chargeAmount' => '20.00',
        'action' => secure_url('/api/twilio/incoming/payment')
      ]);
      $response->say('Thank you');

      break;
      case 6:
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

    $gather->say('Press 1 for technical support including hardware repair and networking');
    $gather->say('Press 2 for software services including web and app development');
    $gather->say('Press 3 for sales and product information.');
    $gather->say('Press 4 to media.');
    $gather->say('Press 5 to manage your account.');
    $gather->say('Press 6 to send money using J Comp Pay!');

    echo $response;
}

public function pay(Request $request, $num, $value) {
  $response = new VoiceResponse();
  $response->say('Your payment has been taken, your confirmation code is: '. $request['PaymentConfirmationCode']);

  $this->sendMessageToRec($num, $value);
  $this->sendMessageToSend($request->input('From'), $value);
  echo $response;
  }

  private function sendMessageToRec($num, $value) {

    $twilio_number = env('TWILIO_ACCOUNT_NUMBER');
    $url = secure_url('/');
    $body = 'SOMEONE SENT YOU '.$value.'! To claim it go to '.$url;
    $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
    var_dump($body);
    $client->messages->create(
        // Where to send a text message (your cell phone?)
        $num,
        [
            'from' => $twilio_number,
            'body' => $body
        ]
    );
  }

  private function sendMessageToSend($num, $value) {
    dd([$num,$value]);
    $twilio_number = env('TWILIO_ACCOUNT_NUMBER');
    $url = secure_url('/');
    $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
    $client->messages->create(
        // Where to send a text message (your cell phone?)
        $num,
        array(
            'from' => $twilio_number,
            'body' => `Thank you for sending {$value} with J Comp Pay!`
        )
    );
  }
}
