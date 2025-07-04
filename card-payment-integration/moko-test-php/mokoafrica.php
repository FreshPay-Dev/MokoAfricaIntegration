<?php
// Configuration
$apiKey = "328fab11c5cd494eb0f80c3f7aedb67f";
$apiSecret = "29015cda152d7dc7b4042b24b7b502d92d90879f311374e87cbf24b01a2de14c";
$apiUrl = "https://test.card.gofreshpay.com/api/v1/payment/orders";

// 1. Prepare request data
$timestamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');

$paymentData = [
    'amount' => 100.50,
    'currency' => 'USD',
    'merchant_reference' => 'CMD-' . time(), // Unique reference
    'bill_to_forename' => 'Jane',
    'bill_to_surname' => 'Doe',
    'bill_to_email' => 'jane.doe@example.com',
    'bill_to_phone' => '+12125551212',
    'bill_to_address_line1' => '2000 Broadway St',
    'bill_to_address_city' => 'New York',
    'bill_to_address_state' => 'NY',
    'bill_to_address_postal_code' => '10023',
    'bill_to_address_country' => 'US',
    'callback_url' => 'http://your-domain.com/callback' // Valid callback URL required
];

// 2. Generate HMAC signature
$payload = json_encode($paymentData);
$message = $payload . $timestamp;
$signature = hash_hmac('sha256', $message, $apiSecret);

// 3. Configure cURL request
$ch = curl_init();
$headers = [
    'X-API-Key: ' . $apiKey,
    'X-Timestamp: ' . $timestamp,
    'X-Signature: ' . $signature,
    'Content-Type: application/json'
];

curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true, // Important for production
    CURLOPT_FOLLOWLOCATION => true
]);

// 4. Execute the request
echo "=== Sending request to FreshPay API ===\n";
echo "Endpoint: $apiUrl\n";
echo "Payload: $payload\n";
echo "Signature: $signature\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 5. Handle response
if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

curl_close($ch);

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    $redirectUrl = $responseData['data']['links'];
    
    echo "=== Transaction Created ===\n";
    echo "Transaction ID: " . $responseData['data']['transaction_uuid'] . "\n";
    echo "Redirect URL: $redirectUrl\n";
    
    // Handle redirection based on context
    if (php_sapi_name() === 'cli') {
        // CLI context - try to open browser automatically
        echo "Attempting to open browser automatically...\n";
        
        $redirectCommand = match(true) {
            strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' => "start \"\" " . escapeshellarg($redirectUrl),
            strtoupper(PHP_OS) === 'DARWIN' => "open " . escapeshellarg($redirectUrl),
            default => "xdg-open " . escapeshellarg($redirectUrl) . " 2>/dev/null"
        };
        
        system($redirectCommand, $returnCode);
        
        if ($returnCode !== 0) {
            echo "\n[ERROR] Could not open browser automatically.\n";
            echo "Please manually open this URL in your browser:\n";
            echo $redirectUrl . "\n";
        }
    } else {
        // Web context - standard HTTP redirect
        header("Location: " . $redirectUrl);
        exit;
    }
} else {
    echo "=== API Error ===\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n\n";
    
    if ($httpCode === 403) {
        echo "Authentication failed. Please verify:\n";
        echo "- Your API credentials\n";
        echo "- Server clock synchronization\n";
        echo "- HMAC signature calculation\n";
        echo "Signed message: $message\n";
        echo "Generated signature: $signature\n";
    }
}
?>