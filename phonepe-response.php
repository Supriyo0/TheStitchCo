<?php
/**
 * PhonePe Payment Gateway Return Response Handler
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';
require_once __DIR__ . '/includes/phonepe.php';
require_once __DIR__ . '/includes/mailer.php';

$db = get_db();

// 1. Extract Merchant Transaction ID from POST, GET, or Decoded Payload
$merchantTxnId = '';

if (!empty($_POST['response'])) {
    $decoded = json_decode(base64_decode($_POST['response']), true);
    if (!empty($decoded['data']['merchantTransactionId'])) {
        $merchantTxnId = $decoded['data']['merchantTransactionId'];
    }
}

if (empty($merchantTxnId) && !empty($_POST['merchantTransactionId'])) {
    $merchantTxnId = trim($_POST['merchantTransactionId']);
}

if (empty($merchantTxnId) && !empty($_POST['transactionId'])) {
    $merchantTxnId = trim($_POST['transactionId']);
}

if (empty($merchantTxnId) && !empty($_GET['txn_id'])) {
    $merchantTxnId = trim($_GET['txn_id']);
}

if (empty($merchantTxnId) && !empty($_SESSION['phonepe_merchant_txn_id'])) {
    $merchantTxnId = $_SESSION['phonepe_merchant_txn_id'];
}

if (empty($merchantTxnId)) {
    $_SESSION['checkout_error'] = 'Invalid payment session or no transaction ID received.';
    header("Location: payment.php");
    exit;
}

// 2. Identify the Order in Database
$orderId = 0;
$order = null;

// Find by payment record with this merchant transaction ID / UTR
$payStmt = $db->prepare("SELECT * FROM payments WHERE utr_number = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$merchantTxnId]);
$paymentRecord = $payStmt->fetch();

if ($paymentRecord) {
    $orderId = (int)$paymentRecord['order_id'];
} elseif (!empty($_SESSION['phonepe_order_id'])) {
    $orderId = (int)$_SESSION['phonepe_order_id'];
} elseif (preg_match('/^TSC_(\d+)_/', $merchantTxnId, $matches)) {
    $orderId = (int)$matches[1];
}

if ($orderId > 0) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
}

if (!$order) {
    $_SESSION['checkout_error'] = 'Could not locate order associated with this transaction.';
    header("Location: cart.php");
    exit;
}

// 3. Query PhonePe Status API to Securely Verify Payment
$statusCheck = phonepe_check_status($merchantTxnId);
$statusCode = $statusCheck['code'] ?? 'UNKNOWN';
$providerTxnId = !empty($statusCheck['transaction_id']) ? $statusCheck['transaction_id'] : $merchantTxnId;

if ($statusCheck['success'] === true || $statusCode === 'PAYMENT_SUCCESS') {
    // === PAYMENT SUCCESSFUL ===
    try {
        $db->beginTransaction();

        // 1. Update Order Status
        $upOrder = $db->prepare("
            UPDATE orders 
            SET payment_status = 'Paid', 
                status = 'Order Placed', 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $upOrder->execute([$orderId]);

        // 2. Update Payment Record
        $upPay = $db->prepare("
            UPDATE payments 
            SET status = 'Approved', 
                utr_number = ?, 
                admin_notes = ?, 
                reviewed_at = NOW() 
            WHERE order_id = ?
        ");
        $upPay->execute([$providerTxnId, 'PhonePe Gateway Auto-Verified (Code: ' . $statusCode . ')', $orderId]);

        // 3. Log Status Timeline
        log_order_status_transition(
            $orderId, 
            'Pending', 
            'Order Placed', 
            'Payment successfully verified via PhonePe Gateway (Ref: ' . $providerTxnId . ')', 
            'PhonePe Gateway'
        );

        // 4. Admin Notification
        create_notification(
            null, 
            'PhonePe Payment Received', 
            'Order #' . $order['order_number'] . ' paid successfully via PhonePe (' . format_price($order['total_price']) . ')', 
            'payment', 
            'orders.php'
        );

        $db->commit();

        // 5. Clear Cart, Shipping Session & Coupon
        clear_cart();
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['checkout_shipping']);
        unset($_SESSION['phonepe_order_id']);
        unset($_SESSION['phonepe_merchant_txn_id']);

        // 6. Send Order Confirmation Email
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
        } catch (Exception $mailEx) {
            error_log("Order confirmation email error: " . $mailEx->getMessage());
        }

        // Redirect to Order Success Page
        header("Location: order-success.php?order_number=" . urlencode($order['order_number']));
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error confirming PhonePe payment: " . $e->getMessage());
        $_SESSION['checkout_error'] = 'Payment was received, but an error occurred finalizing your order. Please contact support.';
        header("Location: order-success.php?order_number=" . urlencode($order['order_number']));
        exit;
    }

} elseif ($statusCode === 'PAYMENT_PENDING') {
    // === PAYMENT PENDING ===
    // PhonePe is awaiting authorization from customer's bank
    $upOrder = $db->prepare("UPDATE orders SET payment_status = 'Pending', updated_at = NOW() WHERE id = ?");
    $upOrder->execute([$orderId]);

    clear_cart();
    unset($_SESSION['applied_coupon']);
    unset($_SESSION['checkout_shipping']);
    unset($_SESSION['phonepe_order_id']);
    unset($_SESSION['phonepe_merchant_txn_id']);

    header("Location: order-success.php?order_number=" . urlencode($order['order_number']) . "&status=pending");
    exit;

} else {
    // === PAYMENT FAILED / CANCELLED / DECLINED ===
    $failureMsg = !empty($statusCheck['message']) ? $statusCheck['message'] : 'Payment was cancelled or could not be authorized.';
    
    // Mark order as failed
    $upOrder = $db->prepare("UPDATE orders SET payment_status = 'Failed', cancel_reason = ?, updated_at = NOW() WHERE id = ?");
    $upOrder->execute([$failureMsg, $orderId]);

    // Restore stock if needed
    $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();
    $stockStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    foreach ($items as $it) {
        $stockStmt->execute([$it['quantity'], $it['product_id']]);
    }

    log_order_status_transition($orderId, 'Order Placed', 'Payment Failed', 'PhonePe payment failed: ' . $failureMsg, 'PhonePe Gateway');

    $_SESSION['checkout_error'] = '⚠️ Payment Failed: ' . $failureMsg . '. You can retry payment below.';
    header("Location: payment.php");
    exit;
}
