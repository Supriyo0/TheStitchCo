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

// Ensure tables and columns exist
try {
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested TINYINT(1) DEFAULT 0");
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested_at TIMESTAMP NULL DEFAULT NULL");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `order_returns` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `order_number` VARCHAR(50) NOT NULL,
            `customer_id` INT NOT NULL,
            `customer_name` VARCHAR(255) NOT NULL,
            `customer_phone` VARCHAR(50) NOT NULL,
            `customer_email` VARCHAR(255) NOT NULL,
            `reason` VARCHAR(255) NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `img_front` VARCHAR(255) NOT NULL,
            `img_back` VARCHAR(255) NOT NULL,
            `img_tag` VARCHAR(255) NOT NULL,
            `upi_id` VARCHAR(100) DEFAULT NULL,
            `return_type` ENUM('refund', 'exchange') DEFAULT 'refund',
            `status` ENUM('Pending Review', 'Approved - Pickup Scheduled', 'Pickup Completed', 'Refund Processed', 'Rejected') DEFAULT 'Pending Review',
            `admin_note` TEXT DEFAULT NULL,
            `pickup_date` DATE DEFAULT NULL,
            `courier_name` VARCHAR(100) DEFAULT 'Delhivery Reverse Pickup',
            `tracking_number` VARCHAR(100) DEFAULT NULL,
            `refund_amount` DECIMAL(10,2) NOT NULL,
            `refund_ref` VARCHAR(100) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`order_id`),
            INDEX (`customer_id`),
            INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    // Already created
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
            'message' => 'This order cannot be cancelled because it is already ' . strtolower($order['status']) . '. Please use Return/Refund after delivery.'
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

// 2. Request Post-Delivery Return & Refund with 3 Product Photos & UPI ID
if ($action === 'request_return') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $upiId = trim($_POST['upi_id'] ?? '');
    $returnType = trim($_POST['return_type'] ?? 'refund');

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }

    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Please select a reason for your return.']);
        exit;
    }

    if (empty($upiId)) {
        echo json_encode(['success' => false, 'message' => 'Please provide your UPI ID for the refund disbursement.']);
        exit;
    }

    // Fetch order
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Must be Delivered to return
    if ($order['status'] !== 'Delivered') {
        echo json_encode(['success' => false, 'message' => 'Returns can only be initiated after your order has been delivered. Current status is: ' . $order['status']]);
        exit;
    }

    // Check if return request already exists
    $chkStmt = $db->prepare("SELECT id, status FROM order_returns WHERE order_id = ? LIMIT 1");
    $chkStmt->execute([$orderId]);
    $existingReturn = $chkStmt->fetch();

    if ($existingReturn && $existingReturn['status'] !== 'Rejected') {
        echo json_encode(['success' => false, 'message' => 'A return request for this order is already active (Status: ' . $existingReturn['status'] . ').']);
        exit;
    }

    // Check 3 Required Image Uploads
    if (empty($_FILES['img_front']['name']) || empty($_FILES['img_back']['name']) || empty($_FILES['img_tag']['name'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Please upload all 3 required photos: Front View, Back View, and Brand/Price Tag.'
        ]);
        exit;
    }

    // Upload Front Photo
    $upFront = handle_image_upload($_FILES['img_front'], 'returns', 'ret_front_' . $orderId);
    if (!$upFront['success']) {
        echo json_encode(['success' => false, 'message' => 'Front Photo upload failed: ' . ($upFront['message'] ?? '')]);
        exit;
    }

    // Upload Back Photo
    $upBack = handle_image_upload($_FILES['img_back'], 'returns', 'ret_back_' . $orderId);
    if (!$upBack['success']) {
        echo json_encode(['success' => false, 'message' => 'Back Photo upload failed: ' . ($upBack['message'] ?? '')]);
        exit;
    }

    // Upload Tag Photo
    $upTag = handle_image_upload($_FILES['img_tag'], 'returns', 'ret_tag_' . $orderId);
    if (!$upTag['success']) {
        echo json_encode(['success' => false, 'message' => 'Brand Tag Photo upload failed: ' . ($upTag['message'] ?? '')]);
        exit;
    }

    try {
        $insReturn = $db->prepare("
            INSERT INTO order_returns (
                order_id, order_number, customer_id, customer_name, customer_phone,
                customer_email, reason, notes, img_front, img_back, img_tag,
                upi_id, return_type, status, refund_amount
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, 'Pending Review', ?
            )
            ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                notes = VALUES(notes),
                img_front = VALUES(img_front),
                img_back = VALUES(img_back),
                img_tag = VALUES(img_tag),
                upi_id = VALUES(upi_id),
                return_type = VALUES(return_type),
                status = 'Pending Review',
                refund_amount = VALUES(refund_amount)
        ");

        $insReturn->execute([
            $order['id'],
            $order['order_number'],
            $order['customer_id'],
            $order['customer_name'],
            $order['customer_phone'],
            $order['customer_email'],
            $reason,
            $notes,
            $upFront['url'],
            $upBack['url'],
            $upTag['url'],
            $upiId,
            $returnType,
            $order['total_price']
        ]);

        log_order_status_transition($order['id'], 'Delivered', 'Delivered', 'Customer submitted 7-Day Return / Refund request with 3 product verification photos and UPI ID (' . $upiId . ')', 'Customer');

        // Admin Notification
        create_notification(
            null,
            'New Return / Refund Request',
            'Order #' . $order['order_number'] . ' return requested by ' . $order['customer_name'] . ' (Reason: ' . $reason . ')',
            'order',
            'admin/returns.php'
        );

        echo json_encode([
            'success' => true,
            'message' => 'Return request submitted successfully! Our team will inspect your product photos and schedule pickup within 24 hours.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown customer action.']);
