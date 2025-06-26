<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Exception;

class PaymentService
{
    protected $orderService;
    
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        
        // Initialize Stripe
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create payment intent for order.
     *
     * @param Order $order
     * @param array $paymentData
     * @return array
     * @throws Exception
     */
    public function createPaymentIntent(Order $order, array $paymentData = []): array
    {
        try {
            $user = $order->user;
            $amount = $this->convertToStripeAmount($order->total);
            
            // Create or get Stripe customer
            $stripeCustomer = $this->getOrCreateStripeCustomer($user);
            
            $intentData = [
                'amount' => $amount,
                'currency' => config('app.currency', 'usd'),
                'customer' => $stripeCustomer->id,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $user->id,
                ],
                'description' => "Order #{$order->order_number}",
                'receipt_email' => $user->email,
                'setup_future_usage' => 'off_session', // For future payments
            ];
            
            // Add shipping address if available
            if ($order->shipping_address) {
                $shippingAddress = json_decode($order->shipping_address, true);
                $intentData['shipping'] = [
                    'name' => $shippingAddress['name'] ?? $user->name,
                    'address' => [
                        'line1' => $shippingAddress['address_line_1'] ?? '',
                        'line2' => $shippingAddress['address_line_2'] ?? null,
                        'city' => $shippingAddress['city'] ?? '',
                        'state' => $shippingAddress['state'] ?? '',
                        'postal_code' => $shippingAddress['postal_code'] ?? '',
                        'country' => $shippingAddress['country'] ?? 'US',
                    ],
                ];
            }
            
            $paymentIntent = PaymentIntent::create($intentData);
            
            // Store payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'payment_method' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => config('app.currency', 'usd'),
                'status' => 'pending',
                'metadata' => json_encode([
                    'stripe_customer_id' => $stripeCustomer->id,
                    'payment_intent' => $paymentIntent->toArray(),
                ]),
            ]);
            
            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
                'payment_id' => $payment->id,
            ];
            
        } catch (Exception $e) {
            Log::error('Payment intent creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Confirm payment and update order status.
     *
     * @param string $paymentIntentId
     * @return array
     * @throws Exception
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            // Find payment record
            $payment = Payment::where('payment_intent_id', $paymentIntentId)->first();
            
            if (!$payment) {
                throw new Exception('Payment record not found');
            }
            
            $order = $payment->order;
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            DB::beginTransaction();
            
            if ($paymentIntent->status === 'succeeded') {
                // Update payment status
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $paymentIntent->charges->data[0]->id ?? null,
                    'paid_at' => now(),
                    'metadata' => json_encode([
                        'stripe_payment_intent' => $paymentIntent->toArray(),
                    ]),
                ]);
                
                // Update order status
                $this->orderService->updateOrderStatus($order, 'paid');
                
                DB::commit();
                
                return [
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'order' => $order,
                    'payment' => $payment,
                ];
                
            } elseif ($paymentIntent->status === 'requires_action') {
                return [
                    'success' => false,
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret,
                ];
                
            } else {
                // Payment failed
                $payment->update([
                    'status' => 'failed',
                    'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                ]);
                
                $this->orderService->updateOrderStatus($order, 'payment_failed');
                
                DB::commit();
                
                return [
                    'success' => false,
                    'message' => 'Payment failed: ' . ($paymentIntent->last_payment_error->message ?? 'Unknown error'),
                ];
            }
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new Exception('Failed to confirm payment: ' . $e->getMessage());
        }
    }

    /**
     * Process refund for payment.
     *
     * @param Payment $payment
     * @param float|null $amount
     * @param string|null $reason
     * @return array
     * @throws Exception
     */
    public function processRefund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        try {
            if ($payment->status !== 'completed') {
                throw new Exception('Cannot refund a payment that is not completed');
            }
            
            $refundAmount = $amount ? $this->convertToStripeAmount($amount) : null;
            
            $refundData = [
                'payment_intent' => $payment->payment_intent_id,
            ];
            
            if ($refundAmount) {
                $refundData['amount'] = $refundAmount;
            }
            
            if ($reason) {
                $refundData['reason'] = $reason;
            }
            
            $refund = \Stripe\Refund::create($refundData);
            
            // Update payment record
            $payment->update([
                'status' => $refund->amount === $this->convertToStripeAmount($payment->amount) ? 'refunded' : 'partially_refunded',
                'refunded_amount' => $this->convertFromStripeAmount($refund->amount),
                'refunded_at' => now(),
                'metadata' => json_encode(array_merge(
                    json_decode($payment->metadata, true) ?? [],
                    ['refund' => $refund->toArray()]
                )),
            ]);
            
            // Update order status if fully refunded
            if ($refund->amount === $this->convertToStripeAmount($payment->amount)) {
                $this->orderService->updateOrderStatus($payment->order, 'refunded');
            }
            
            return [
                'success' => true,
                'refund' => $refund,
                'refunded_amount' => $this->convertFromStripeAmount($refund->amount),
            ];
            
        } catch (Exception $e) {
            Log::error('Refund processing failed', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw new Exception('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Get or create Stripe customer.
     *
     * @param User $user
     * @return Customer
     */
    protected function getOrCreateStripeCustomer(User $user): Customer
    {
        // Check if user already has a Stripe customer ID
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (Exception $e) {
                // Customer not found, create new one
                Log::warning('Stripe customer not found, creating new one', [
                    'user_id' => $user->id,
                    'stripe_customer_id' => $user->stripe_customer_id,
                ]);
            }
        }
        
        // Create new Stripe customer
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);
        
        // Save customer ID to user
        $user->update(['stripe_customer_id' => $customer->id]);
        
        return $customer;
    }

    /**
     * Save payment method for future use.
     *
     * @param User $user
     * @param string $paymentMethodId
     * @return PaymentMethod
     * @throws Exception
     */
    public function savePaymentMethod(User $user, string $paymentMethodId): PaymentMethod
    {
        try {
            $stripeCustomer = $this->getOrCreateStripeCustomer($user);
            
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomer->id]);
            
            return $paymentMethod;
            
        } catch (Exception $e) {
            Log::error('Failed to save payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            
            throw new Exception('Failed to save payment method: ' . $e->getMessage());
        }
    }

    /**
     * Get user's saved payment methods.
     *
     * @param User $user
     * @return array
     */
    public function getUserPaymentMethods(User $user): array
    {
        try {
            if (!$user->stripe_customer_id) {
                return [];
            }
            
            $paymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card',
            ]);
            
            return $paymentMethods->data;
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment methods', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Delete saved payment method.
     *
     * @param string $paymentMethodId
     * @return bool
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to delete payment method', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Handle Stripe webhook.
     *
     * @param array $payload
     * @param string $signature
     * @return array
     * @throws Exception
     */
    public function handleWebhook(array $payload, string $signature): array
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                json_encode($payload),
                $signature,
                config('services.stripe.webhook_secret')
            );
            
            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);
            
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentIntentSucceeded($event->data->object);
                    
                case 'payment_intent.payment_failed':
                    return $this->handlePaymentIntentFailed($event->data->object);
                    
                case 'charge.dispute.created':
                    return $this->handleChargeDispute($event->data->object);
                    
                default:
                    Log::info('Unhandled webhook event type', ['type' => $event->type]);
                    return ['status' => 'ignored'];
            }
            
        } catch (Exception $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle successful payment intent webhook.
     *
     * @param object $paymentIntent
     * @return array
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): array
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if (!$payment) {
            Log::warning('Payment not found for successful payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return ['status' => 'payment_not_found'];
        }
        
        if ($payment->status === 'completed') {
            return ['status' => 'already_processed'];
        }
        
        // Update payment and order status
        $this->confirmPayment($paymentIntent->id);
        
        return ['status' => 'processed'];
    }

    /**
     * Handle failed payment intent webhook.
     *
     * @param object $paymentIntent
     * @return array
     */
    protected function handlePaymentIntentFailed($paymentIntent): array
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if (!$payment) {
            return ['status' => 'payment_not_found'];
        }
        
        $payment->update([
            'status' => 'failed',
            'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
        ]);
        
        $this->orderService->updateOrderStatus($payment->order, 'payment_failed');
        
        return ['status' => 'processed'];
    }

    /**
     * Handle charge dispute webhook.
     *
     * @param object $dispute
     * @return array
     */
    protected function handleChargeDispute($dispute): array
    {
        // Find payment by charge ID
        $payment = Payment::where('transaction_id', $dispute->charge)->first();
        
        if (!$payment) {
            return ['status' => 'payment_not_found'];
        }
        
        // Update payment status
        $payment->update([
            'status' => 'disputed',
            'metadata' => json_encode(array_merge(
                json_decode($payment->metadata, true) ?? [],
                ['dispute' => $dispute]
            )),
        ]);
        
        // Update order status
        $this->orderService->updateOrderStatus($payment->order, 'disputed');
        
        return ['status' => 'processed'];
    }

    /**
     * Convert amount to Stripe format (cents).
     *
     * @param float $amount
     * @return int
     */
    protected function convertToStripeAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from Stripe format.
     *
     * @param int $amount
     * @return float
     */
    protected function convertFromStripeAmount(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Get payment statistics.
     *
     * @return array
     */
    public function getPaymentStatistics(): array
    {
        return [
            'total_payments' => Payment::count(),
            'completed_payments' => Payment::where('status', 'completed')->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
            'refunded_payments' => Payment::where('status', 'refunded')->count(),
            'disputed_payments' => Payment::where('status', 'disputed')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'total_refunded' => Payment::whereIn('status', ['refunded', 'partially_refunded'])->sum('refunded_amount'),
            'payments_this_month' => Payment::where('status', 'completed')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->count(),
            'revenue_this_month' => Payment::where('status', 'completed')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];
    }
}