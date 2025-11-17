<?php

namespace Modules\Orders\Domain\Services;

interface PaymentGatewayInterface
{
    /**
     * Process a payment
     * 
     * @param array $paymentData ['order_id', 'amount', 'currency', 'customer_email']
     * @return object {success: bool, payment_reference: string, failure_reason: string|null}
     */
    public function processPayment(array $paymentData): object;

    /**
     * Process a refund
     * 
     * @param array $refundData ['order_id', 'amount', 'payment_reference']
     * @return object {success: bool, refund_reference: string, failure_reason: string|null}
     */
    public function processRefund(array $refundData): object;
}

