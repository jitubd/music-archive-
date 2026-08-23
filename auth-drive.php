<?php
/**
 * Standalone Google Drive authorization script.
 * Run: F:\Xampp\php\php.exe auth-drive.php
 * Then open the URL it prints in your browser.
 */

// Load from .env
$env = [];
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
    }
}

$clientId     = $env['GOOGLE_DRIVE_CLIENT_ID'] ?? getenv('GOOGLE_DRIVE_CLIENT_ID');
$clientSecret = $env['GOOGLE_DRIVE_CLIENT_SECRET'] ?? getenv('GOOGLE_DRIVE_CLIENT_SECRET');
$redirectUri  = 'http://127.0.0.1:9999/callback';
$tokenPath = __DIR__ . '/storage/app/google-drive-token.json';

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'https://www.googleapis.com/auth/drive.readonly',
    'access_type'   => 'offline',
    'prompt'        => 'consent',
]);

echo "=== Google Drive Authorization ===\n\n";
echo "1. Open this URL in your browser:\n\n";
echo $authUrl . "\n\n";
echo "2. Sign in and approve access.\n";
echo "3. The code will be captured automatically here.\n\n";

// Start a temporary server on port 9999 to catch the redirect
$server = stream_socket_server('tcp://127.0.0.1:9999', $errno, $errstr);
if (!$server) {
    die("ERROR: Could not start server on port 9999: $errstr\n");
}

echo "Waiting for authorization...\n";

$client = stream_socket_accept($server, 300);
if (!$client) {
    die("ERROR: Timed out waiting for callback (300s).\n");
}

$request = '';
while ($line = fgets($client)) {
    $request .= $line;
    if ($line === "\r\n") break;
}

// Extract the authorization code from the callback URL
preg_match('/GET \/callback\?code=([^&\s]+)/', $request, $matches);
if (empty($matches[1])) {
    fwrite($client, "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<h2>Authorization failed - no code received. Close this tab and try again.</h2>");
    fclose($client);
    fclose($server);
    die("ERROR: No authorization code received.\n");
}

$code = urldecode($matches[1]);
fclose($client);
fclose($server);

echo "Got code! Exchanging for token...\n";

// Exchange code for token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'code'          => $code,
        'grant_type'    => 'authorization_code',
    ]),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token = json_decode($response, true);

if (isset($token['error'])) {
    die("ERROR: " . ($token['error_description'] ?? $token['error']) . "\n");
}

// Save token
$token['expires_at'] = time() + ($token['expires_in'] ?? 3600);
file_put_contents($tokenPath, json_encode($token));

// Show success page to user
$successClient = stream_socket_accept($server = stream_socket_server('tcp://127.0.0.1:9999', $errno2, $errstr2) ?: null, 5);

echo "\n=== SUCCESS ===\n";
echo "Token saved to: $tokenPath\n";
echo "You can close this terminal and run: php artisan drive:import 1Lp12tPEogQYr3fuhcAKUZ5Mw_lISP1Fi\n";
