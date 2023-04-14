<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;

class SmsController extends Controller
{
    public function handleIncomingSms(Request $request)
    {
        $message = $request->input('Body');
        $fromNumber = $request->input('From');
        $toNumber = '+18594024863';

        $twilioClient = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
        $twilioClient->messages->create(
            $toNumber,
            array(
                'from' => $fromNumber,
                'body' => $message
            )
        );
    }
}
