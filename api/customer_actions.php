<?php
/**
 * Customer AJAX Actions (Cancellation Request, Address Updates, etc.)
 * The Stitch Co.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/order_functions.php';

$action = $_POST['action'] ?? '';
$db = get_db();

// Ensure columns exist on orders table
try {
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested TINYINT(1) DEFAULT 0");
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested_at TIMESTAMP NULL DEFAULT NULL");
} catch (Exception $e) {
    // Columns may already exist
}

// 1. Request Order Cancellation
if ($action === 'request_cancellation') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderNumber = trim($_POST['order_number'] ?? '');
    $reason = trim($_POST['cancel_reason'] ?? '');
    $additionalNotes = trim($_POST['additional_notes'] ?? '');

    if ($orderId <= 0 && empty($orderNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order reference.']);
        exit;
    }

    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Please select a reason for cancellation.']);
        exit;
    }

    $fullReason = $reason . (!empty($additionalNotes) ? " — " . $additionalNotes : '');

    // Fetch order
    if ($orderId > 0) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
    }
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Check user permission
    if (is_logged_in()) {
        $cUser = current_user();
        if ((int)$order['customer_id'] !== (int)$cUser['id'] && $order['customer_email'] !== $cUser['email'] && !is_admin()) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to cancel this order.']);
            exit;
        }
    }

    // Check if order is eligible for cancellation (Not Shipped, Delivered, or Cancelled)
    $nonCancellableStatuses = ['Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
    if (in_array($order['status'], $nonCancellableStatuses)) {
        echo json_encode([
            'success' => false, 
            'message' => 'This order cannot be cancelled because it is already ' . strtolower($order['status']) . '. Please contact customer support.'
        ]);
        exit;
    }

    if (!empty($order['cancel_requested']) && (int)$order['cancel_requested'] === 1) {
        echo json_encode([
            'success' => false, 
            'message' => 'A cancellation request has already been submitted for this order and is under review.'
        ]);
        exit;
    }

    try {
        $now = date('Y-m-d H:i:s');
        $upStmt = $db->prepare("
            UPDATE orders 
            SET cancel_requested = 1, 
                cancel_reason = ?, 
                cancel_requested_at = ? 
            WHERE id = ?
        ");
        $upStmt->execute([$fullReason, $now, $order['id']]);

        // Log in status history
        log_order_status_transition($order['id'], $order['status'], $order['status'], 'Customer submitted cancellation request: ' . $fullReason, 'Customer');

        // Create Admin Notification
        create_notification(
            null, 
            'Order Cancellation Requested', 
            'Customer ' . $order['customer_name'] . ' requested cancellation for Order #' . $order['order_number'] . ' (' . $fullReason . ')', 
            'order', 
            'admin/orders.php?status=cancel_requests'
        );

        echo json_encode([
            'success' => true, 
            'message' => 'Your cancellation request has been submitted. Our store admin will review and approve it shortly.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown customer action.']);
