<?php

// Add this array into the existing config/services.php return array,
// alongside 'mailgun', 'postmark', etc.

return [

    'google' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob'),
    ],

];
