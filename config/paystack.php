<?php
// config/paystack.php
define('PAYSTACK_PUBLIC_KEY', $_ENV['PAYSTACK_PUBLIC_KEY'] ?? $_SERVER['PAYSTACK_PUBLIC_KEY'] ?? getenv('PAYSTACK_PUBLIC_KEY') ?: '');
define('PAYSTACK_SECRET_KEY', $_ENV['PAYSTACK_SECRET_KEY'] ?? $_SERVER['PAYSTACK_SECRET_KEY'] ?? getenv('PAYSTACK_SECRET_KEY') ?: '');
define('PAYSTACK_BASE_URL', 'https://api.paystack.co');

function paystack_verify(string $reference): array {
    $ch = curl_init(PAYSTACK_BASE_URL . '/transaction/verify/' . urlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // TEMPORARY: Fix for XAMPP SSL issues on Windows
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $rawResponse = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // LOGGING FOR DEBUGGING
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'reference' => $reference,
        'error' => $error,
        'response' => json_decode($rawResponse, true)
    ];
    file_put_contents(__DIR__ . '/../paystack_debug.log', json_encode($log) . "\n", FILE_APPEND);

    return json_decode($rawResponse, true) ?: [];
}

function paystack_refund(string $reference, ?float $amount_ghs = null): array {
    $data = ['transaction' => $reference];
    
    // If amount is provided, convert to Pesewas (smallest unit)
    if ($amount_ghs !== null) {
        $data['amount'] = (int)($amount_ghs * 100);
    }
    
    $payload = json_encode($data);

    $ch = curl_init(PAYSTACK_BASE_URL . '/refund');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // TEMPORARY: Fix for XAMPP SSL issues on Windows
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload
    ]);
    
    $rawResponse = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // LOGGING FOR AUDIT
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'reference' => $reference,
        'amount_ghs' => $amount_ghs ?: 'FULL_REFUND',
        'action'    => 'REFUND_REQUEST',
        'error'     => $error,
        'response'  => json_decode($rawResponse, true)
    ];
    file_put_contents(__DIR__ . '/../paystack_refunds.log', json_encode($log) . "\n", FILE_APPEND);

    return json_decode($rawResponse, true) ?: [];
}

