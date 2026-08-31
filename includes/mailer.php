<?php
/**
 * Google SMTP Mailer & Streetwear Branded Notification Templates
 * The Stitch Co. — A Fashion Brand by MJ Company
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Send Email via Google SMTP (TLS on Port 587)
 */
function send_smtp_mail(string $toEmail, string $subject, string $htmlBody, string $toName = ''): array {
    $smtpHost = get_setting('smtp_host', 'smtp.gmail.com');
    $smtpPort = (int)get_setting('smtp_port', 587);
    $smtpUser = get_setting('smtp_username', 'thestitchco.official@gmail.com');
    $smtpPass = get_setting('smtp_password', 'mbslyojqdzwbugjb');
    $fromEmail = get_setting('smtp_from_email', 'thestitchco.official@gmail.com');
    $fromName = get_setting('smtp_from_name', 'The Stitch Co.');

    if (empty($toEmail)) {
        return ['success' => false, 'message' => 'Recipient email is required.'];
    }

    $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 12);
    if (!$socket) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return ['success' => false, 'message' => "Could not connect to SMTP server: $errstr"];
    }

    $read = function() use ($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $response;
    };

    $write = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $res = $read();
    if (substr($res, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'message' => 'Initial response error: ' . $res];
    }

    $write("EHLO " . gethostname());
    $res = $read();

    $write("STARTTLS");
    $res = $read();
    if (substr($res, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'message' => 'STARTTLS negotiation failed: ' . $res];
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        return ['success' => false, 'message' => 'TLS crypto handshake failed'];
    }

    $write("EHLO " . gethostname());
    $res = $read();

    $write("AUTH LOGIN");
    $res = $read();
    if (substr($res, 0, 3) !== '334') {
        fclose($socket);
        return ['success' => false, 'message' => 'AUTH LOGIN failed: ' . $res];
    }

    $write(base64_encode($smtpUser));
    $res = $read();
    if (substr($res, 0, 3) !== '334') {
        fclose($socket);
        return ['success' => false, 'message' => 'Username failed: ' . $res];
    }

    $write(base64_encode(str_replace(' ', '', $smtpPass)));
    $res = $read();
    if (substr($res, 0, 3) !== '235') {
        fclose($socket);
        return ['success' => false, 'message' => 'Authentication failed: ' . $res];
    }

    $write("MAIL FROM: <$fromEmail>");
    $res = $read();
    if (substr($res, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => 'MAIL FROM error: ' . $res];
    }

    $write("RCPT TO: <$toEmail>");
    $res = $read();
    if (substr($res, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => 'RCPT TO error: ' . $res];
    }

    $write("DATA");
    $res = $read();
    if (substr($res, 0, 3) !== '354') {
        fclose($socket);
        return ['success' => false, 'message' => 'DATA command error: ' . $res];
    }

    $headers = [];
    $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>";
    $headers[] = "To: " . ($toName ? "=?UTF-8?B?" . base64_encode($toName) . "?= " : "") . "<$toEmail>";
    $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    $headers[] = "Content-Transfer-Encoding: base64";
    $headers[] = "Date: " . date('r');
    $headers[] = "X-Mailer: TheStitchCo-Engine/2.0";

    $rawContent = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";
    $write($rawContent);
    $res = $read();

    $write("QUIT");
    fclose($socket);

    if (substr($res, 0, 3) === '250') {
        return ['success' => true, 'message' => 'Email dispatched successfully!'];
    }

    return ['success' => false, 'message' => 'Delivery error: ' . $res];
}

/**
 * Base Email Wrapper with Modern Streetwear Aesthetic
 */
function wrap_email_template(string $title, string $contentHtml): string {
    $storeName = STORE_NAME;
    $supportEmail = 'thestitchco.official@gmail.com';
    $supportPhone = '+91 7063179581';
    
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
<style>
  body { margin: 0; padding: 0; background-color: #0A0A0C; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0F172A; }
  table { border-collapse: collapse; }
  a { color: #2563EB; text-decoration: none; }
</style>
</head>
<body style="margin: 0; padding: 30px 15px; background-color: #0A0A0C;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
    <tr>
      <td align="center">
        <!-- Main Card Container -->
        <table role="presentation" width="100%" style="max-width: 600px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5);" cellspacing="0" cellpadding="0" border="0">
          
          <!-- Header Banner -->
          <tr>
            <td style="background-color: #000000; padding: 28px 24px; text-align: center; border-bottom: 2px solid #2563EB;">
              <h1 style="color: #FFFFFF; margin: 0; font-size: 24px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;">
                THE <span style="color: #3B82F6;">STITCH</span> CO.
              </h1>
              <p style="color: #94A3B8; margin: 4px 0 0; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">
                Wear Your Vibe • Heavyweight Streetwear
              </p>
            </td>
          </tr>

          <!-- Main Body Content -->
          <tr>
            <td style="padding: 32px 28px; background-color: #FFFFFF;">
              {$contentHtml}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #09090B; padding: 24px 24px; text-align: center; color: #94A3B8; font-size: 12px; line-height: 1.6; border-top: 1px solid #1E293B;">
              <p style="margin: 0 0 8px; color: #FFFFFF; font-weight: 700;">THE STITCH CO. — A Fashion Brand by MJ Company</p>
              <p style="margin: 0 0 8px;">Sisir Building, Jetty Ghat Bus Stopage, Fraserganj, South 24 Parganas, WB - 743357</p>
              <p style="margin: 0; font-size: 11px;">
                Need help? Contact us at <a href="mailto:{$supportEmail}" style="color: #60A5FA;">{$supportEmail}</a> or <a href="tel:{$supportPhone}" style="color: #60A5FA;">{$supportPhone}</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * 1. Order Confirmation Email
 */
function send_order_confirmation_email(array $order, array $items = []): array {
    $orderNumber = $order['order_number'] ?? 'TSC-ORDER';
    $customerName = $order['customer_name'] ?? 'Valued Customer';
    $customerEmail = $order['customer_email'] ?? '';
    $totalFormatted = format_price($order['total_price'] ?? 0);
    $subtotalFormatted = format_price($order['subtotal'] ?? 0);
    $shippingFormatted = ((float)($order['shipping_fee'] ?? 0) == 0) ? 'FREE' : format_price($order['shipping_fee']);
    $discountFormatted = ((float)($order['discount_amount'] ?? 0) > 0) ? '- ' . format_price($order['discount_amount']) : '₹0.00';
    $paymentMethod = $order['payment_method'] ?? 'UPI';
    $shippingAddress = nl2br(e($order['shipping_address'] ?? ''));
    $trackUrl = BASE_URL . 'track-order.php?order_number=' . urlencode($orderNumber);

    $itemsRowsHtml = '';
    foreach ($items as $it) {
        $name = e($it['name'] ?? 'Product');
        $qty = (int)($it['quantity'] ?? 1);
        $size = e($it['size'] ?? 'M');
        $color = e($it['color'] ?? 'Black');
        $price = format_price($it['price'] ?? 0);
        $total = format_price(($it['price'] ?? 0) * $qty);

        $itemsRowsHtml .= <<<HTML
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 8px; font-size: 14px; color: #0F172A;">
            <strong>{$name}</strong><br>
            <span style="font-size: 12px; color: #64748B;">Size: {$size} | Color: {$color}</span>
          </td>
          <td style="padding: 12px 8px; text-align: center; font-size: 14px; color: #0F172A; font-weight: 700;">{$qty}</td>
          <td style="padding: 12px 8px; text-align: right; font-size: 14px; color: #0F172A; font-weight: 800;">{$total}</td>
        </tr>
HTML;
    }

    $content = <<<HTML
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="display: inline-block; background-color: #ECFDF5; color: #10B981; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 9999px; letter-spacing: 1px; text-transform: uppercase;">
        Order Confirmed ✓
      </div>
      <h2 style="margin: 12px 0 6px; font-size: 22px; font-weight: 900; color: #0F172A;">THANK YOU FOR YOUR ORDER!</h2>
      <p style="margin: 0; color: #64748B; font-size: 14px;">Hi {$customerName}, we've received your order and are prepping your streetwear drop.</p>
    </div>

    <!-- Order Meta Box -->
    <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
      <table width="100%">
        <tr>
          <td style="font-size: 13px; color: #64748B;">Order Number:</td>
          <td style="font-size: 14px; font-weight: 900; color: #0F172A; text-align: right;">#{$orderNumber}</td>
        </tr>
        <tr>
          <td style="font-size: 13px; color: #64748B;">Order Date:</td>
          <td style="font-size: 13px; font-weight: 700; color: #0F172A; text-align: right;">today</td>
        </tr>
        <tr>
          <td style="font-size: 13px; color: #64748B;">Payment Method:</td>
          <td style="font-size: 13px; font-weight: 700; color: #2563EB; text-align: right;">{$paymentMethod}</td>
        </tr>
      </table>
    </div>

    <!-- Order Items -->
    <h3 style="font-size: 15px; font-weight: 800; text-transform: uppercase; margin: 0 0 10px; color: #0F172A;">Order Items</h3>
    <table width="100%" style="margin-bottom: 20px;">
      <thead>
        <tr style="border-bottom: 2px solid #0F172A; background-color: #F1F5F9;">
          <th style="padding: 8px; text-align: left; font-size: 12px; font-weight: 800; color: #0F172A;">ITEM</th>
          <th style="padding: 8px; text-align: center; font-size: 12px; font-weight: 800; color: #0F172A;">QTY</th>
          <th style="padding: 8px; text-align: right; font-size: 12px; font-weight: 800; color: #0F172A;">PRICE</th>
        </tr>
      </thead>
      <tbody>
        {$itemsRowsHtml}
      </tbody>
    </table>

    <!-- Totals Breakdown -->
    <table width="100%" style="margin-bottom: 24px;">
      <tr>
        <td style="padding: 4px 0; font-size: 13px; color: #64748B;">Subtotal:</td>
        <td style="padding: 4px 0; font-size: 13px; font-weight: 700; color: #0F172A; text-align: right;">{$subtotalFormatted}</td>
      </tr>
      <tr>
        <td style="padding: 4px 0; font-size: 13px; color: #64748B;">Shipping:</td>
        <td style="padding: 4px 0; font-size: 13px; font-weight: 700; color: #10B981; text-align: right;">{$shippingFormatted}</td>
      </tr>
      <tr>
        <td style="padding: 8px 0; font-size: 16px; font-weight: 900; color: #0F172A; border-top: 2px solid #E2E8F0;">Total Amount:</td>
        <td style="padding: 8px 0; font-size: 18px; font-weight: 900; color: #2563EB; text-align: right; border-top: 2px solid #E2E8F0;">{$totalFormatted}</td>
      </tr>
    </table>

    <!-- Shipping Address -->
    <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
      <h4 style="margin: 0 0 6px; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0F172A;">Shipping Address:</h4>
      <p style="margin: 0; font-size: 13px; color: #334155; line-height: 1.5;">{$shippingAddress}</p>
    </div>

    <!-- CTA Button -->
    <div style="text-align: center; margin-top: 28px;">
      <a href="{$trackUrl}" style="display: inline-block; background-color: #000000; color: #FFFFFF; padding: 14px 28px; border-radius: 8px; font-weight: 800; font-size: 14px; text-decoration: none; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(0,0,0,0.25);">
        TRACK YOUR ORDER →
      </a>
    </div>
HTML;

    $html = wrap_email_template("Order Confirmation #{$orderNumber}", $content);
    return send_smtp_mail($customerEmail, "Order Confirmed: #{$orderNumber} - The Stitch Co.", $html, $customerName);
}

/**
 * 2. Order Status Update / Tracking Email
 */
function send_order_status_email(array $order, string $prevStatus, string $newStatus, string $comment = ''): array {
    $orderNumber = $order['order_number'] ?? 'TSC-ORDER';
    $customerName = $order['customer_name'] ?? 'Valued Customer';
    $customerEmail = $order['customer_email'] ?? '';
    $trackUrl = BASE_URL . 'track-order.php?order_number=' . urlencode($orderNumber);
    $commentHtml = $comment ? "<p style=\"margin: 12px 0 0; padding: 10px; background: #F1F5F9; border-radius: 6px; font-size: 13px; color: #334155;\"><strong>Note:</strong> " . e($comment) . "</p>" : '';

    $statusColors = [
        'Confirmed' => '#10B981',
        'Processing' => '#3B82F6',
        'Packed' => '#6366F1',
        'Shipped' => '#2563EB',
        'Out for Delivery' => '#F59E0B',
        'Delivered' => '#10B981',
        'Cancelled' => '#EF4444'
    ];
    $statusColor = $statusColors[$newStatus] ?? '#2563EB';

    $content = <<<HTML
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="display: inline-block; background-color: {$statusColor}15; color: {$statusColor}; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 9999px; letter-spacing: 1px; text-transform: uppercase; border: 1px solid {$statusColor}40;">
        Status Update: {$newStatus}
      </div>
      <h2 style="margin: 12px 0 6px; font-size: 22px; font-weight: 900; color: #0F172A;">YOUR ORDER IS {$newStatus}!</h2>
      <p style="margin: 0; color: #64748B; font-size: 14px;">Hi {$customerName}, your order <strong>#{$orderNumber}</strong> has been updated to <strong>{$newStatus}</strong>.</p>
      {$commentHtml}
    </div>

    <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; margin-bottom: 24px; text-align: center;">
      <p style="margin: 0 0 10px; font-size: 13px; color: #64748B;">Want real-time status and delivery updates?</p>
      <a href="{$trackUrl}" style="display: inline-block; background-color: #000000; color: #FFFFFF; padding: 12px 24px; border-radius: 8px; font-weight: 800; font-size: 13px; text-decoration: none;">
        LIVE ORDER TRACKING →
      </a>
    </div>
HTML;

    $html = wrap_email_template("Order Status Update #{$orderNumber}", $content);
    return send_smtp_mail($customerEmail, "Order #{$orderNumber} Status: {$newStatus} - The Stitch Co.", $html, $customerName);
}

/**
 * 3. Order Cancellation Email
 */
function send_order_cancellation_email(array $order, string $reason = ''): array {
    $orderNumber = $order['order_number'] ?? 'TSC-ORDER';
    $customerName = $order['customer_name'] ?? 'Valued Customer';
    $customerEmail = $order['customer_email'] ?? '';
    $reasonHtml = $reason ? "<p style=\"margin: 12px 0 0; padding: 12px; background: #FEF2F2; border-radius: 6px; font-size: 13px; color: #991B1B;\"><strong>Reason:</strong> " . e($reason) . "</p>" : '';

    $content = <<<HTML
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="display: inline-block; background-color: #FEE2E2; color: #EF4444; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 9999px; letter-spacing: 1px; text-transform: uppercase;">
        Order Cancelled
      </div>
      <h2 style="margin: 12px 0 6px; font-size: 22px; font-weight: 900; color: #0F172A;">ORDER CANCELLATION NOTICE</h2>
      <p style="margin: 0; color: #64748B; font-size: 14px;">Hi {$customerName}, your order <strong>#{$orderNumber}</strong> has been cancelled.</p>
      {$reasonHtml}
    </div>

    <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 13px; color: #475569; line-height: 1.5;">
      <p style="margin: 0 0 8px;">If you already completed payment via UPI or Online Banking, our billing team will initiate your refund back to the original payment source within 3-5 working days.</p>
      <p style="margin: 0;">If you have any questions, reply directly to this email or reach out to our WhatsApp customer support.</p>
    </div>

    <div style="text-align: center;">
      <a href="{BASE_URL}shop.php" style="display: inline-block; background-color: #000000; color: #FFFFFF; padding: 12px 24px; border-radius: 8px; font-weight: 800; font-size: 13px; text-decoration: none;">
        CONTINUE SHOPPING →
      </a>
    </div>
HTML;

    $html = wrap_email_template("Order Cancellation #{$orderNumber}", $content);
    return send_smtp_mail($customerEmail, "Order Cancelled: #{$orderNumber} - The Stitch Co.", $html, $customerName);
}

/**
 * 4. Welcome / Registration Email
 */
function send_welcome_email(array $user): array {
    $fullname = $user['fullname'] ?? 'Streetwear Enthusiast';
    $email = $user['email'] ?? '';

    $content = <<<HTML
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="display: inline-block; background-color: #EFF6FF; color: #2563EB; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 9999px; letter-spacing: 1px; text-transform: uppercase;">
        Welcome to The Tribe 🔥
      </div>
      <h2 style="margin: 12px 0 6px; font-size: 22px; font-weight: 900; color: #0F172A;">WELCOME TO THE STITCH CO.</h2>
      <p style="margin: 0; color: #64748B; font-size: 14px;">Hi {$fullname}, your account has been created successfully!</p>
    </div>

    <!-- Special Promo Code Box -->
    <div style="background-color: #000000; border-radius: 12px; padding: 20px; text-align: center; color: #FFFFFF; margin-bottom: 24px;">
      <span style="font-size: 11px; font-weight: 800; color: #60A5FA; letter-spacing: 2px;">SPECIAL MEMBER DISCOUNT</span>
      <h3 style="margin: 6px 0 10px; font-size: 20px; font-weight: 900;">GET 10% OFF ON YOUR FIRST DROP</h3>
      <div style="display: inline-block; background: rgba(255,255,255,0.15); border: 1.5px dashed #3B82F6; padding: 8px 18px; border-radius: 8px; font-weight: 900; font-size: 16px; letter-spacing: 2px; color: #93C5FD;">
        WELCOME10
      </div>
    </div>

    <div style="text-align: center;">
      <a href="{BASE_URL}shop.php" style="display: inline-block; background-color: #2563EB; color: #FFFFFF; padding: 14px 28px; border-radius: 8px; font-weight: 800; font-size: 14px; text-decoration: none;">
        EXPLORE THE COLLECTION →
      </a>
    </div>
HTML;

    $html = wrap_email_template("Welcome to The Stitch Co.", $content);
    return send_smtp_mail($email, "Welcome to The Stitch Co. | Wear Your Vibe", $html, $fullname);
}

/**
 * 4. Password Reset OTP Verification Email
 */
function send_password_reset_otp_email(string $email, string $otp, string $fullname = 'Valued Customer'): array {
    $fullname = !empty($fullname) ? $fullname : 'Valued Customer';

    $content = <<<HTML
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="display: inline-block; background-color: #FEF2F2; color: #DC2626; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 9999px; letter-spacing: 1px; text-transform: uppercase;">
        Password Reset Request 🔐
      </div>
      <h2 style="margin: 12px 0 6px; font-size: 22px; font-weight: 900; color: #0F172A;">YOUR VERIFICATION CODE</h2>
      <p style="margin: 0; color: #64748B; font-size: 14px;">Hi {$fullname}, use the 6-digit OTP below to reset your account password.</p>
    </div>

    <!-- OTP Code Highlight Box -->
    <div style="background-color: #0F172A; border-radius: 16px; padding: 24px; text-align: center; color: #FFFFFF; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);">
      <span style="font-size: 11px; font-weight: 800; color: #94A3B8; letter-spacing: 2px; text-transform: uppercase;">ONE-TIME PASSWORD (OTP)</span>
      <div style="font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: 900; letter-spacing: 8px; color: #38BDF8; margin: 12px 0; padding: 10px 0; background: rgba(255,255,255,0.06); border-radius: 8px; border: 1px dashed rgba(56, 189, 248, 0.4);">
        {$otp}
      </div>
      <div style="font-size: 12px; color: #94A3B8; margin-top: 8px;">
        ⏳ This OTP is valid for <strong>10 minutes</strong>. Do not share it with anyone.
      </div>
    </div>

    <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 14px; font-size: 13px; color: #475569; margin-bottom: 20px; line-height: 1.5;">
      💡 If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.
    </div>
HTML;

    $html = wrap_email_template("Password Reset OTP - The Stitch Co.", $content);
    return send_smtp_mail($email, "Your Password Reset OTP is {$otp} - The Stitch Co.", $html, $fullname);
}

