<?php
/**
 * PhonePe Server-to-Server Webhook Listener
 * The Stitch Co.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/order_functions.php';
require_once __DIR__ . '/../includes/phonepe.php';
require_once __DIR__ . '/../includes/mailer.php';

$rawBody = file_get_contents('php://input');
$xVerifyHeader = $_SERVER['HTTP_X_VERIFY'] ?? '';

if (empty($rawBody)) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'Empty webhook payload']);
    exit;
}

$input = json_decode($rawBody, true);
$rawResponse = $input['response'] ?? '';

if (empty($rawResponse)) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'Missing response field']);
    exit;
}

// Verify Webhook Signature if header is present
if (!empty($xVerifyHeader)) {
    $isValid = phonepe_verify_webhook_signature($rawResponse, $xVerifyHeader);
    if (!$isValid) {
        http_response_code(401);
        echo json_encode(['status' => 'ERROR', 'message' => 'Invalid signature']);
        exit;
    }
}

// Decode Base64 Payload
$decoded = json_decode(base64_decode($rawResponse), true);
if (!$decoded || empty($decoded['data'])) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'Invalid base64 payload']);
    exit;
}

$code = $decoded['code'] ?? '';
$data = $decoded['data'] ?? [];
$merchantTxnId = $data['merchantTransactionId'] ?? '';
$providerTxnId = $data['transactionId'] ?? ($data['providerReferenceId'] ?? $merchantTxnId);
$amount = isset($data['amount']) ? ((float)$data['amount'] / 100) : 0.00;

if (empty($merchantTxnId)) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'Missing merchantTransactionId']);
    exit;
}

$db = get_db();

// Locate order
$payStmt = $db->prepare("SELECT * FROM payments WHERE utr_number = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$merchantTxnId]);
$paymentRecord = $payStmt->fetch();

$orderId = 0;
if ($paymentRecord) {
    $orderId = (int)$paymentRecord['order_id'];
} elseif (preg_match('/^TSC_(\d+)_/', $merchantTxnId, $matches)) {
    $orderId = (int)$matches[1];
}

if ($orderId <= 0) {
    http_response_code(404);
    echo json_encode(['status' => 'ERROR', 'message' => 'Order not found']);
    exit;
}

$orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['status' => 'ERROR', 'message' => 'Order not found']);
    exit;
}

// If payment already recorded as Paid, acknowledge immediately
if ($order['payment_status'] === 'Paid') {
    echo json_encode(['status' => 'SUCCESS', 'message' => 'Order already marked as paid']);
    exit;
}

if ($code === 'PAYMENT_SUCCESS') {
    try {
        $db->beginTransaction();

        $upOrder = $db->prepare("UPDATE orders SET payment_status = 'Paid', status = 'Order Placed', updated_at = NOW() WHERE id = ?");
        $upOrder->execute([$orderId]);

        $upPay = $db->prepare("UPDATE payments SET status = 'Approved', utr_number = ?, admin_notes = ?, reviewed_at = NOW() WHERE order_id = ?");
        $upPay->execute([$providerTxnId, 'Webhook Auto-Verified (Code: ' . $code . ')', $orderId]);

        log_order_status_transition($orderId, $order['status'], 'Order Placed', 'Payment confirmed via PhonePe Webhook (Ref: ' . $providerTxnId . ')', 'PhonePe Webhook');

        create_notification(null, 'PhonePe Webhook Payment Confirmed', 'Payment confirmed via webhook for Order #' . $order['order_number'], 'payment', 'orders.php');

        $db->commit();

        // Send Email confirmation
        try {
            $itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$orderId]);
            $orderItems = $itemsStmt->fetchAll();

            $orderData = [
                'order_number'     => $order['order_number'],
                'customer_name'    => $order['customer_name'],
                'customer_email'   => $order['customer_email'],
                'total_price'      => $order['total_price'],
                'subtotal'         => $order['subtotal'],
                'shipping_fee'     => $order['shipping_fee'],
                'discount_amount'  => $order['discount_amount'],
                'payment_method'   => $order['payment_method'],
                'shipping_address' => $order['shipping_address']
            ];
            send_order_confirmation_email($orderData, $orderItems);
        } catch (Exception $e) {
            error_log("Webhook confirmation email error: " . $e->getMessage());
        }

        echo json_encode(['status' => 'SUCCESS', 'message' => 'Payment processed successfully']);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
        exit;
    }
} else {
    // Payment failure reported by webhook
    $upOrder = $db->prepare("UPDATE orders SET payment_status = 'Failed', cancel_reason = ?, updated_at = NOW() WHERE id = ?");
    $upOrder->execute(['Webhook Reported Failure: ' . $code, $orderId]);

    echo json_encode(['status' => 'SUCCESS', 'message' => 'Failure recorded']);
    exit;
}
