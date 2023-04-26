<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

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
          $response->pay(['paymentConnector' => 'Stripe_Connector_Test']);
          $response->say('Now transferring you to tech support!')
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
      default:
          // Handle invalid input
          $response->say('Invalid selection. Please try again.', ['voice' => 'alice']);
          $response->gather(['numDigits' => 1, 'action' => '/api/menu']);
          break;
  }

  return response($response)->header('Content-Type', 'text/xml');
}

public function generateMenuTwiml()
{
    $response = new VoiceResponse();
    $gather = $response->gather(['numDigits' => 1, 'action' => '/api/menu']);

    $gather->say('Press 1 for technical support including hardware repair and networking');
    $gather->say('Press 2 for software services including web and app development');
    $gather->say('Press 3 for sales and product information.');
    $gather->say('Press 4 to media.');

    return response($response)->header('Content-Type', 'text/xml');
}

}
