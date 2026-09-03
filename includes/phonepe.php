<?php
/**
 * PhonePe Payment Gateway Integration Helper
 * The Stitch Co.
 *
 * Supports PhonePe Standard Checkout API (PG V1 / Hermes)
 * Both Sandbox / UAT and Production Environments.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get PhonePe Gateway Configuration
 */
function phonepe_get_config(): array {
    $mode = get_setting('phonepe_mode', 'sandbox'); // 'sandbox' or 'production'
    $enabled = (int)get_setting('phonepe_enabled', '1');

    if ($mode === 'production') {
        $merchantId = get_setting('phonepe_merchant_id', 'SU2508281240185820112176');
        $saltKey = get_setting('phonepe_salt_key', 'a987a9bc-cf7e-417b-a627-21105e2de2d7');
        $saltIndex = get_setting('phonepe_salt_index', '1');
        $baseUrl = 'https://api.phonepe.com/apis/hermes';
    } else {
        // UAT / Sandbox Defaults (PhonePe Active Standard Simulator Credentials)
        $merchantId = get_setting('phonepe_merchant_id', 'PGTESTPAYUAT86');
        $saltKey = get_setting('phonepe_salt_key', '96434309-7796-489d-8924-ab56988a6076');
        $saltIndex = get_setting('phonepe_salt_index', '1');
        $baseUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    return [
        'enabled'      => $enabled,
        'mode'         => $mode,
        'merchant_id'  => trim($merchantId),
        'salt_key'     => trim($saltKey),
        'salt_index'   => trim($saltIndex),
        'base_url'     => $baseUrl,
        'pay_url'      => $baseUrl . '/pg/v1/pay',
        'status_url'   => $baseUrl . '/pg/v1/status'
    ];
}

/**
 * Initiate Payment via PhonePe Gateway
 *
 * @param array $orderData [
 *   'order_id'        => int,
 *   'order_number'    => string,
 *   'amount'          => float,
 *   'customer_id'     => int,
 *   'customer_name'   => string,
 *   'customer_phone'  => string,
 *   'customer_email'  => string
 * ]
 * @param string $redirectUrl
 * @param string $callbackUrl
 * @return array ['success' => bool, 'redirect_url' => string, 'merchant_txn_id' => string, 'message' => string]
 */
function phonepe_initiate_payment(array $orderData, string $redirectUrl, string $callbackUrl): array {
    $config = phonepe_get_config();

    if (empty($config['merchant_id']) || empty($config['salt_key'])) {
        return [
            'success' => false,
            'message' => 'PhonePe Merchant ID or Salt Key is not configured.'
        ];
    }

    // PhonePe requires amount in Paise (e.g. ₹100.50 -> 10050)
    $amountInPaise = (int)round(((float)$orderData['amount']) * 100);
    if ($amountInPaise <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid order amount for payment initiation.'
        ];
    }

    // Generate unique Merchant Transaction ID (Max 38 chars: alphanumeric, underscore, hyphen)
    $merchantTxnId = 'TSC_' . $orderData['order_id'] . '_' . time() . '_' . substr(bin2hex(random_bytes(3)), 0, 4);

    // Format clean 10-digit mobile number
    $cleanMobile = preg_replace('/[^0-9]/', '', (string)$orderData['customer_phone']);
    if (strlen($cleanMobile) > 10) {
        $cleanMobile = substr($cleanMobile, -10);
    }
    if (strlen($cleanMobile) < 10) {
        $cleanMobile = '9999999999'; // fallback for test environments if missing
    }

    $merchantUserId = 'CUST_' . (!empty($orderData['customer_id']) ? $orderData['customer_id'] : 'GUEST_' . substr(md5($cleanMobile), 0, 8));

    // Prepare JSON Request Payload
    $payload = [
        'merchantId'            => $config['merchant_id'],
        'merchantTransactionId' => $merchantTxnId,
        'merchantUserId'        => $merchantUserId,
        'amount'                => $amountInPaise,
        'redirectUrl'           => $redirectUrl,
        'redirectMode'          => 'POST',
        'callbackUrl'           => $callbackUrl,
        'mobileNumber'          => $cleanMobile,
        'paymentInstrument'     => [
            'type' => 'PAY_PAGE'
        ]
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $base64Payload = base64_encode($jsonPayload);

    // SHA256 Signature calculation
    // SHA256(Base64_Payload + "/pg/v1/pay" + Salt_Key) + "###" + Salt_Index
    $signString = $base64Payload . '/pg/v1/pay' . $config['salt_key'];
    $sha256 = hash('sha256', $signString);
    $xVerifyHeader = $sha256 . '###' . $config['salt_index'];

    $requestBody = json_encode(['request' => $base64Payload]);

    // Send cURL POST Request
    $ch = curl_init($config['pay_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $requestBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-VERIFY: ' . $xVerifyHeader,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'message' => 'Network error connecting to PhonePe: ' . $curlError
        ];
    }

    $resData = json_decode($response, true);

    if (
        $resData &&
        isset($resData['success']) &&
        $resData['success'] === true &&
        !empty($resData['data']['instrumentResponse']['redirectInfo']['url'])
    ) {
        return [
            'success'         => true,
            'redirect_url'    => $resData['data']['instrumentResponse']['redirectInfo']['url'],
            'merchant_txn_id' => $merchantTxnId,
            'message'         => $resData['message'] ?? 'Payment initialized successfully.'
        ];
    }

    $errMsg = $resData['message'] ?? ('PhonePe initiation failed with HTTP code ' . $httpCode);
    return [
        'success'         => false,
        'merchant_txn_id' => $merchantTxnId,
        'message'         => $errMsg,
        'raw_response'    => $resData
    ];
}

/**
 * Check Transaction Status with PhonePe Server API
 *
 * @param string $merchantTxnId
 * @return array ['success' => bool, 'code' => string, 'status' => string, 'transaction_id' => string, 'amount' => float, 'message' => string, 'raw' => array]
 */
function phonepe_check_status(string $merchantTxnId): array {
    $config = phonepe_get_config();

    if (empty($config['merchant_id']) || empty($config['salt_key'])) {
        return [
            'success' => false,
            'status'  => 'CONFIG_ERROR',
            'message' => 'PhonePe credentials are not configured.'
        ];
    }

    // Endpoint: /pg/v1/status/{merchantId}/{merchantTransactionId}
    $statusEndpoint = '/pg/v1/status/' . $config['merchant_id'] . '/' . $merchantTxnId;
    $url = $config['status_url'] . '/' . $config['merchant_id'] . '/' . $merchantTxnId;

    // Signature: SHA256("/pg/v1/status/{merchantId}/{merchantTransactionId}" + Salt_Key) + "###" + Salt_Index
    $signString = $statusEndpoint . $config['salt_key'];
    $sha256 = hash('sha256', $signString);
    $xVerifyHeader = $sha256 . '###' . $config['salt_index'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET        => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-VERIFY: ' . $xVerifyHeader,
            'X-MERCHANT-ID: ' . $config['merchant_id'],
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'status'  => 'NETWORK_ERROR',
            'message' => 'Unable to verify status: ' . $curlError
        ];
    }

    $resData = json_decode($response, true);

    $code = $resData['code'] ?? 'UNKNOWN';
    $isSuccess = ($resData && isset($resData['success']) && $resData['success'] === true && $code === 'PAYMENT_SUCCESS');
    $providerTxnId = $resData['data']['transactionId'] ?? ($resData['data']['providerReferenceId'] ?? $merchantTxnId);
    $amount = isset($resData['data']['amount']) ? ((float)$resData['data']['amount'] / 100) : 0.00;

    return [
        'success'        => $isSuccess,
        'code'           => $code,
        'status'         => $code, // e.g. PAYMENT_SUCCESS, PAYMENT_PENDING, PAYMENT_ERROR, PAYMENT_DECLINED
        'transaction_id' => $providerTxnId,
        'amount'         => $amount,
        'message'        => $resData['message'] ?? 'Transaction status retrieved.',
        'raw'            => $resData
    ];
}

/**
 * Verify Webhook Signature from PhonePe S2S Callback
 *
 * @param string $rawBase64Response
 * @param string $xVerifyHeader
 * @return bool
 */
function phonepe_verify_webhook_signature(string $rawBase64Response, string $xVerifyHeader): bool {
    $config = phonepe_get_config();

    if (empty($xVerifyHeader)) {
        return false;
    }

    $parts = explode('###', $xVerifyHeader);
    if (count($parts) !== 2) {
        return false;
    }

    $expectedHash = hash('sha256', $rawBase64Response . $config['salt_key']);
    return hash_equals($expectedHash, $parts[0]);
}
