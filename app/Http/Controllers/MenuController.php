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
          $response->say('You selected technical support. Please wait while we transfer your call.');
          $response->dial('+18594024863');
          break;
      case 2:
          // Handle billing and account inquiries option
          $response->say('You selected billing and account inquiries.');
          break;
      case 3:
          // Forward call to sales department
          $response->say('You selected sales and product information. Please wait while we transfer your call.');
          $response->dial('+18594024863');
          break;
      case 4:
          // Forward call to Jyrone Parker
          $response->say('You selected to speak to Jyrone Parker. Please wait while we transfer your call.');
          $response->dial('+18594024863');
          break;
      default:
          // Handle invalid input
          $response->say('Invalid selection. Please try again.', ['voice' => 'alice']);
          $response->gather(['numDigits' => 1, 'action' => '/menu']);
          break;
  }

  return response($response)->header('Content-Type', 'text/xml');
}

public function generateMenuTwiml()
{
    $response = new VoiceResponse();
    $gather = $response->gather(['numDigits' => 1, 'action' => '/api/menu']);

    $gather->say('Press 1 for technical support.');
    $gather->say('Press 2 for billing and account inquiries.');
    $gather->say('Press 3 for sales and product information.');
    $gather->say('Press 4 to speak to Jyrone Parker.');

    return response($response)->header('Content-Type', 'text/xml');
}

}
