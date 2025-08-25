<?php
class EmailService {
    public static function sendOrderConfirmation($order_details, $order_items, $subtotal, $delivery_fee, $total) {
        $customer_email = $order_details['customer_email'];
        $customer_name = $order_details['customer_name'];
        $order_id = $order_details['id'];
        
        $subject = "Order Confirmation #" . $order_id . " - Sup Tulang ZZ";
        
        // Create HTML email content
        $message = self::createEmailTemplate($order_details, $order_items, $subtotal, $delivery_fee, $total);
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sup Tulang ZZ <noreply@suptulangzz.com>" . "\r\n";
        $headers .= "Reply-To: contact@suptulangzz.com" . "\r\n";
        
        // Send email
        return mail($customer_email, $subject, $message, $headers);
    }
    
    private static function createEmailTemplate($order_details, $order_items, $subtotal, $delivery_fee, $total) {
        $order_id = $order_details['id'];
        $customer_name = $order_details['customer_name'];
        $order_time = date('d M Y, g:i A', strtotime($order_details['order_time']));
        $order_type = ucfirst($order_details['order_type']);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FF6B35; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 8px; }
                .item { padding: 10px 0; border-bottom: 1px solid #eee; }
                .total { font-weight: bold; font-size: 18px; color: #FF6B35; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Confirmation</h1>
                    <h2>Sup Tulang ZZ</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($customer_name) . ",</p>
                    <p>Thank you for your order! Here are your order details:</p>
                    
                    <div class='order-details'>
                        <h3>Order #" . $order_id . "</h3>
                        <p><strong>Order Time:</strong> " . $order_time . "</p>
                        <p><strong>Order Type:</strong> " . $order_type . "</p>
                        <p><strong>Status:</strong> " . htmlspecialchars($order_details['status']) . "</p>";
                        
        if ($order_details['order_type'] === 'delivery') {
            $html .= "<p><strong>Delivery Address:</strong><br>" . nl2br(htmlspecialchars($order_details['delivery_address'])) . "</p>";
        }
        
        $html .= "
                    </div>
                    
                    <div class='order-details'>
                        <h3>Order Items</h3>";
                        
        foreach ($order_items as $item) {
            if ($item['item_name'] !== 'Delivery Fee') {
                $html .= "
                        <div class='item'>
                            <strong>" . htmlspecialchars($item['item_name']) . "</strong><br>
                            Qty: " . $item['quantity'] . " × RM" . number_format($item['price'] / $item['quantity'], 2) . " = RM" . number_format($item['price'], 2) . "
                        </div>";
            }
        }
        
        $html .= "
                        <div style='margin-top: 15px; padding-top: 15px; border-top: 2px solid #FF6B35;'>
                            <p>Subtotal: RM" . number_format($subtotal, 2) . "</p>";
                            
        if ($delivery_fee > 0) {
            $html .= "<p>Delivery Fee: RM" . number_format($delivery_fee, 2) . "</p>";
        }
        
        $html .= "
                            <p class='total'>Total: RM" . number_format($total, 2) . "</p>
                        </div>
                    </div>
                    
                    <div class='order-details'>
                        <h3>What's Next?</h3>
                        <ul>
                            <li>Estimated preparation time: 30-45 minutes</li>";
                            
        if ($order_details['order_type'] === 'delivery') {
            $html .= "
                            <li>Our delivery team will contact you when ready</li>
                            <li>Expected delivery: 45-60 minutes</li>";
        } else {
            $html .= "
                            <li>We'll call you when ready for pickup</li>
                            <li>Pickup Location: Sup Tulang ZZ Restaurant</li>";
        }
        
        $html .= "
                        </ul>
                    </div>
                    
                    <p><strong>Questions?</strong> Contact us at +60 11-6956-6961</p>
                    <p>Please keep your order reference: #" . $order_id . "</p>
                </div>
                <div class='footer'>
                    <p>Thank you for choosing Sup Tulang ZZ!</p>
                    <p>Authentic Malaysian Flavors</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
}
?>