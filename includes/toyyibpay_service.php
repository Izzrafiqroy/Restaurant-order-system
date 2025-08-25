<?php
class ToyyibPayService {
    public static function createBill($amount, $itemCount, $customerName, $customerEmail, $customerPhone, $order_id) {
        $secretKey = 'c2d4flla-z917-v4nu-0ttg-drktir3jawnt';
        $categoryCode = 'yixzchkl';
        $apiUrl = 'https://dev.toyyibpay.com/index.php/api/createBill';
        $paymentUrl = 'https://dev.toyyibpay.com/';
        $amountSen = intval($amount * 100);
        $referenceNo = 'ORDER_' . $order_id . '_' . time();

        $postData = [
            'userSecretKey' => $secretKey,
            'categoryCode' => $categoryCode,
            'billName' => 'Shopping Cart Payment',
            'billDescription' => "Payment for $itemCount items in cart",
            'billPriceSetting' => '1',
            'billPayorInfo' => '1',
            'billAmount' => $amountSen,
            // Change to your actual return URL:
            'billReturnUrl' => 'http://localhost/restaurant-order-system/online_order_success.php?oid=' . $order_id,
            'billCallbackUrl' => 'https://google.com', // Or your own callback handler
            'billExternalReferenceNo' => $referenceNo,
            'billTo' => $customerName,
            'billEmail' => $customerEmail,
            'billPhone' => $customerPhone,
            'billSplitPayment' => '0',
            'billSplitPaymentArgs' => '',
            'billDisplayMerchant' => '1'
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (is_array($data) && isset($data[0]['BillCode'])) {
            return [
                'billCode' => $data[0]['BillCode'],
                'paymentUrl' => $paymentUrl . $data[0]['BillCode'],
                'billExternalReferenceNo' => $data[0]['BillExternalReferenceNo'] ?? null,
                'billpaymentStatus' => $data[0]['BillpaymentStatus'] ?? null,
            ];
        }
        return null;
    }
}
?>