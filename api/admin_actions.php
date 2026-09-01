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

// 4. Approve Order Cancellation (Restock inventory + refund note)
if ($action === 'approve_cancellation') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? 'Order cancellation approved by store support.');

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        // Restock inventory for all order items
        $itemStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll();

        $restockStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        foreach ($items as $it) {
            $restockStmt->execute([$it['quantity'], $it['product_id']]);
        }

        // Determine payment status
        $newPaymentStatus = ($order['payment_status'] === 'Paid') ? 'Refunded' : $order['payment_status'];

        $upStmt = $db->prepare("
            UPDATE orders 
            SET status = 'Cancelled', 
                cancel_requested = 0,
                admin_note = ?,
                payment_status = ?
            WHERE id = ?
        ");
        $upStmt->execute([$adminNote, $newPaymentStatus, $orderId]);

        log_order_status_transition($orderId, $order['status'], 'Cancelled', 'Cancellation approved by admin (' . $admin['fullname'] . '). Note: ' . $adminNote, $admin['fullname']);

        create_notification(
            $order['customer_id'], 
            'Order Cancelled #' . $order['order_number'], 
            'Your order has been cancelled as requested. ' . $adminNote, 
            'order', 
            'account.php?tab=orders'
        );

        // Send Email
        require_once __DIR__ . '/../includes/mailer.php';
        try {
            send_order_cancellation_email($order, $adminNote);
        } catch (Exception $e) {
            error_log("Cancellation email error: " . $e->getMessage());
        }

        log_admin_activity($admin['id'], $admin['fullname'], 'approve_cancellation', 'Cancelled Order #' . $order['order_number'] . ' & restocked items.');

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully and inventory restored to catalog!']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 5. Reject Order Cancellation (Send cancellation explanation note to customer)
if ($action === 'reject_cancellation') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }

    if (empty($adminNote)) {
        echo json_encode(['success' => false, 'message' => 'Please provide an explanation note for the customer.']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        $upStmt = $db->prepare("
            UPDATE orders 
            SET cancel_requested = 0,
                admin_note = ?
            WHERE id = ?
        ");
        $upStmt->execute([$adminNote, $orderId]);

        log_order_status_transition($orderId, $order['status'], $order['status'], 'Admin rejected cancellation request. Note: ' . $adminNote, $admin['fullname']);

        create_notification(
            $order['customer_id'], 
            'Cancellation Update for #' . $order['order_number'], 
            'Your cancellation request could not be processed: ' . $adminNote, 
            'order', 
            'account.php?tab=orders'
        );

        log_admin_activity($admin['id'], $admin['fullname'], 'reject_cancellation', 'Rejected cancellation for Order #' . $order['order_number'] . ' with note: ' . $adminNote);

        echo json_encode(['success' => true, 'message' => 'Cancellation request dismissed and note sent to customer.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 6. Approve Return & Schedule Pickup (Today / Tomorrow)
if ($action === 'approve_return_pickup') {
    $returnId = (int)($_POST['return_id'] ?? 0);
    $pickupDate = trim($_POST['pickup_date'] ?? date('Y-m-d'));
    $courierName = trim($_POST['courier_name'] ?? 'Delhivery Reverse Pickup');
    $adminNote = trim($_POST['admin_note'] ?? 'Return approved! Our courier executive will visit your address for product pickup today.');

    if ($returnId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid return ID.']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM order_returns WHERE id = ?");
        $stmt->execute([$returnId]);
        $return = $stmt->fetch();

        if (!$return) {
            echo json_encode(['success' => false, 'message' => 'Return record not found.']);
            exit;
        }

        $upStmt = $db->prepare("
            UPDATE order_returns 
            SET status = 'Approved - Pickup Scheduled',
                pickup_date = ?,
                courier_name = ?,
                admin_note = ?
            WHERE id = ?
        ");
        $upStmt->execute([$pickupDate, $courierName, $adminNote, $returnId]);

        create_notification(
            $return['customer_id'],
            'Return Approved #' . $return['order_number'],
            'Your return request is approved! Pickup scheduled for ' . date('d M Y', strtotime($pickupDate)) . ' via ' . $courierName . '. ' . $adminNote,
            'order',
            'account.php?tab=orders'
        );

        log_admin_activity($admin['id'], $admin['fullname'], 'approve_return', 'Approved return #' . $returnId . ' for Order #' . $return['order_number'] . ' with pickup on ' . $pickupDate);

        echo json_encode(['success' => true, 'message' => 'Return approved and pickup scheduled for ' . $pickupDate . '!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 7. Mark Return Pickup Completed
if ($action === 'complete_return_pickup') {
    $returnId = (int)($_POST['return_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? 'Item received and verified at store warehouse.');

    if ($returnId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid return ID.']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM order_returns WHERE id = ?");
        $stmt->execute([$returnId]);
        $return = $stmt->fetch();

        if (!$return) {
            echo json_encode(['success' => false, 'message' => 'Return record not found.']);
            exit;
        }

        $upStmt = $db->prepare("
            UPDATE order_returns 
            SET status = 'Pickup Completed',
                admin_note = ?
            WHERE id = ?
        ");
        $upStmt->execute([$adminNote, $returnId]);

        create_notification(
            $return['customer_id'],
            'Return Pickup Completed #' . $return['order_number'],
            'Your returned item has been successfully picked up and is now undergoing refund processing.',
            'order',
            'account.php?tab=orders'
        );

        log_admin_activity($admin['id'], $admin['fullname'], 'complete_pickup', 'Marked pickup completed for Return #' . $returnId);

        echo json_encode(['success' => true, 'message' => 'Return pickup marked as completed!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 8. Process & Issue Refund to Customer UPI
if ($action === 'process_return_refund') {
    $returnId = (int)($_POST['return_id'] ?? 0);
    $refundRef = trim($_POST['refund_ref'] ?? '');
    $adminNote = trim($_POST['admin_note'] ?? 'Refund processed and credited to your UPI ID.');

    if ($returnId <= 0 || empty($refundRef)) {
        echo json_encode(['success' => false, 'message' => 'Please provide the UPI Transaction / Payout Reference Number.']);
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM order_returns WHERE id = ?");
        $stmt->execute([$returnId]);
        $return = $stmt->fetch();

        if (!$return) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Return record not found.']);
            exit;
        }

        // Restock inventory for items in this order
        $itemStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$return['order_id']]);
        $items = $itemStmt->fetchAll();

        $restockStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        foreach ($items as $it) {
            $restockStmt->execute([$it['quantity'], $it['product_id']]);
        }

        // Update return record
        $upStmt = $db->prepare("
            UPDATE order_returns 
            SET status = 'Refund Processed',
                refund_ref = ?,
                admin_note = ?
            WHERE id = ?
        ");
        $upStmt->execute([$refundRef, $adminNote, $returnId]);

        // Update order payment status
        $db->prepare("UPDATE orders SET payment_status = 'Refunded', status = 'Cancelled' WHERE id = ?")->execute([$return['order_id']]);

        log_order_status_transition($return['order_id'], 'Delivered', 'Cancelled', 'Return completed & refund of ' . format_price($return['refund_amount']) . ' sent to UPI ' . $return['upi_id'] . ' (Ref: ' . $refundRef . ')', $admin['fullname']);

        create_notification(
            $return['customer_id'],
            'Refund Disbursed #' . $return['order_number'],
            'Refund of ' . format_price($return['refund_amount']) . ' has been credited to your UPI: ' . $return['upi_id'] . ' (Ref: ' . $refundRef . ')',
            'payment',
            'account.php?tab=orders'
        );

        log_admin_activity($admin['id'], $admin['fullname'], 'process_refund', 'Disbursed refund of ' . $return['refund_amount'] . ' to ' . $return['upi_id'] . ' for Return #' . $returnId);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Refund processed successfully and inventory restored!']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 9. Reject Return Request
if ($action === 'reject_return') {
    $returnId = (int)($_POST['return_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($returnId <= 0 || empty($adminNote)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a rejection reason note for the customer.']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM order_returns WHERE id = ?");
        $stmt->execute([$returnId]);
        $return = $stmt->fetch();

        if (!$return) {
            echo json_encode(['success' => false, 'message' => 'Return record not found.']);
            exit;
        }

        $upStmt = $db->prepare("
            UPDATE order_returns 
            SET status = 'Rejected',
                admin_note = ?
            WHERE id = ?
        ");
        $upStmt->execute([$adminNote, $returnId]);

        create_notification(
            $return['customer_id'],
            'Return Request Update #' . $return['order_number'],
            'Your return request could not be approved: ' . $adminNote,
            'order',
            'account.php?tab=orders'
        );

        log_admin_activity($admin['id'], $admin['fullname'], 'reject_return', 'Rejected Return #' . $returnId . ' with note: ' . $adminNote);

        echo json_encode(['success' => true, 'message' => 'Return request rejected and explanation sent to customer.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 9. Maintenance Mode Toggle
if ($action === 'toggle_maintenance') {
    $mode = (int)($_POST['mode'] ?? 0);
    $customMsg = trim($_POST['message'] ?? '');
    
    update_setting('maintenance_mode', (string)$mode);
    if (!empty($customMsg)) {
        update_setting('maintenance_message', $customMsg);
    }
    
    log_admin_activity($admin['id'], $admin['fullname'], 'toggle_maintenance', ($mode ? 'Enabled' : 'Disabled') . ' Maintenance Mode');
    
    echo json_encode([
        'success' => true,
        'maintenance_mode' => $mode,
        'message' => $mode ? 'Maintenance Mode is now ENABLED! Public visitors will see the offline screen.' : 'Store is now LIVE! Maintenance mode has been turned off.'
    ]);
    exit;
}

// 10. Set Active Festive & Seasonal Theme
if ($action === 'set_active_theme') {
    $theme = trim($_POST['theme'] ?? 'default');
    $validThemes = ['default', 'winter', 'summer', 'durga_puja', 'freedom', 'diwali', 'christmas'];

    if (!in_array($theme, $validThemes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid theme selected.']);
        exit;
    }

    update_setting('active_theme', $theme);
    log_admin_activity($admin['id'], $admin['fullname'], 'set_active_theme', 'Switched storefront theme to ' . strtoupper($theme));

    echo json_encode([
        'success' => true,
        'active_theme' => $theme,
        'message' => 'Theme updated to ' . ucfirst($theme) . ' successfully!'
    ]);
    exit;
}

// 11. Toggle Theme Particle Animations
if ($action === 'toggle_theme_particles') {
    $enabled = ($_POST['enabled'] ?? '1') === '1' ? '1' : '0';
    update_setting('theme_particles_enabled', $enabled);

    echo json_encode([
        'success' => true,
        'particles_enabled' => $enabled === '1',
        'message' => 'Particle effects updated.'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown admin action.']);
