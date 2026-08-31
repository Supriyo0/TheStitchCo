<?php
/**
 * Admin AJAX Action Handlers
 * Payment Approval, Order Status Transitions, Shipping Tracking
 * The Stitch Co.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/order_functions.php';

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';
$admin = current_user();
$db = get_db();

// 1. Payment Verification (Approve / Reject)
if ($action === 'verify_payment') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $status = $_POST['status'] ?? ''; // 'Approved' or 'Rejected'
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if ($paymentId <= 0 || !in_array($status, ['Approved', 'Rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT p.*, o.order_number, o.customer_id FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Payment record not found.']);
            exit;
        }

        $now = date('Y-m-d H:i:s');
        $upStmt = $db->prepare("
            UPDATE payments 
            SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = ? 
            WHERE id = ?
        ");
        $upStmt->execute([$status, $adminNotes, $admin['id'], $now, $paymentId]);

        // If approved, update order payment status to Paid and advance workflow
        if ($status === 'Approved') {
            $db->prepare("UPDATE orders SET payment_status = 'Paid', status = 'Confirmed' WHERE id = ?")->execute([$payment['order_id']]);
            log_order_status_transition($payment['order_id'], 'Order Placed', 'Confirmed', 'Payment verified and approved by admin (' . $admin['fullname'] . ')', $admin['fullname']);
            create_notification($payment['customer_id'], 'Payment Verified', 'Your payment for Order #' . $payment['order_number'] . ' has been approved!', 'payment', 'account.php?tab=orders');
        } else {
            $db->prepare("UPDATE orders SET payment_status = 'Failed' WHERE id = ?")->execute([$payment['order_id']]);
            log_order_status_transition($payment['order_id'], 'Order Placed', 'Order Placed', 'Payment rejected by admin: ' . $adminNotes, $admin['fullname']);
            create_notification($payment['customer_id'], 'Payment Rejected', 'Your payment for Order #' . $payment['order_number'] . ' could not be verified. Please check notes or resubmit.', 'payment', 'account.php?tab=orders');
        }

        log_admin_activity($admin['id'], $admin['fullname'], 'verify_payment', 'Payment #' . $paymentId . ' marked as ' . $status);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Payment ' . strtolower($status) . ' successfully!']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. Order Status Update
if ($action === 'update_order_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    $validStatuses = ['Order Placed', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
    if ($orderId <= 0 || !in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $prevStatus = $order['status'];
    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
    log_order_status_transition($orderId, $prevStatus, $newStatus, $comment, $admin['fullname']);
    create_notification($order['customer_id'], 'Order Update: ' . $newStatus, 'Order #' . $order['order_number'] . ' status changed to ' . $newStatus, 'order', 'track-order.php?order_number=' . $order['order_number']);

    // Send Email via Google SMTP
    require_once __DIR__ . '/../includes/mailer.php';
    try {
        if ($newStatus === 'Cancelled') {
            send_order_cancellation_email($order, $comment);
        } else {
            send_order_status_email($order, $prevStatus, $newStatus, $comment);
        }
    } catch (Exception $mailEx) {
        error_log("Order status email error: " . $mailEx->getMessage());
    }

    log_admin_activity($admin['id'], $admin['fullname'], 'update_order_status', 'Order #' . $order['order_number'] . ' updated from ' . $prevStatus . ' to ' . $newStatus);

    echo json_encode(['success' => true, 'message' => 'Order status updated to ' . $newStatus]);
    exit;
}

// 3. Update Shipping Details
if ($action === 'update_shipping') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $courier = trim($_POST['courier_name'] ?? 'Delhivery');
    $trackingNumber = trim($_POST['tracking_number'] ?? '');
    $trackingUrl = trim($_POST['tracking_url'] ?? '');

    if ($orderId <= 0 || empty($trackingNumber)) {
        echo json_encode(['success' => false, 'message' => 'Tracking number is required.']);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO shipping_details (order_id, courier_name, tracking_number, tracking_url, shipped_date)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            courier_name = VALUES(courier_name),
            tracking_number = VALUES(tracking_number),
            tracking_url = VALUES(tracking_url),
            shipped_date = NOW()
    ");
    $stmt->execute([$orderId, $courier, $trackingNumber, $trackingUrl]);

    // Advance order status to Shipped if not already
    $db->prepare("UPDATE orders SET status = 'Shipped' WHERE id = ? AND status IN ('Confirmed', 'Processing', 'Packed')")->execute([$orderId]);

    echo json_encode(['success' => true, 'message' => 'Shipping information updated successfully.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown admin action.']);
