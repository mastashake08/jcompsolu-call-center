# Twilio Call Center and SMS Forwarding with Laravel
This project is a simple call center and SMS forwarding system built with the Laravel PHP framework and the Twilio API. It allows incoming calls and SMS messages to be forwarded to a single phone number for handling by a support team.

## Setup
To set up the project, you'll need to follow these steps:

Clone the repository to your local machine.
Run composer install to install the project dependencies.
Create a new .env file based on the example .env.example file, and fill in your Twilio API credentials and phone number.
Run php artisan migrate to set up the project's database tables.
Start the development server by running php artisan serve.
Once you've completed these steps, you should be able to access the project by navigating to http://localhost:8000 in your web browser.

## Usage
The project provides a call center menu with options for sales, support, and speaking to Jyrone Parker. When a user selects an option, the call is forwarded to the configured phone number using the Twilio API.

The project also provides SMS forwarding functionality. When an SMS message is received by the configured Twilio phone number, the message is forwarded to the configured phone number using the Twilio API.

## License
This project is licensed under the MIT License. See the LICENSE file for more information.
