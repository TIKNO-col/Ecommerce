<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Newsletter;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Mail\WelcomeEmail;
use App\Mail\PasswordReset;
use App\Mail\ProductBackInStock;
use App\Mail\NewsletterEmail;
use App\Mail\AbandonedCartReminder;
use App\Mail\ProductRecommendation;
use App\Mail\PromotionalEmail;

class EmailService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send order confirmation email.
     *
     * @param Order $order
     * @param array $options
     * @return array
     */
    public function sendOrderConfirmation(Order $order, array $options = []): array
    {
        try {
            $user = $order->user;
            
            if (!$user || !$user->email) {
                throw new Exception('User email not found.');
            }
            
            // Check if user wants to receive order emails
            if (!$this->userWantsEmail($user, 'order_updates')) {
                return [
                    'success' => false,
                    'message' => 'User has opted out of order emails.',
                ];
            }
            
            // Get email template
            $template = $this->getEmailTemplate('order_confirmation');
            
            // Prepare email data
            $emailData = [
                'order' => $order,
                'user' => $user,
                'items' => $order->items()->with('product')->get(),
                'shipping_address' => $order->shippingAddress,
                'billing_address' => $order->billingAddress,
                'template' => $template,
            ];
            
            // Send email
            if ($options['queue'] ?? true) {
                Mail::to($user->email)->queue(new OrderConfirmation($emailData));
            } else {
                Mail::to($user->email)->send(new OrderConfirmation($emailData));
            }
            
            // Log email
            $this->logEmail([
                'user_id' => $user->id,
                'type' => 'order_confirmation',
                'subject' => "Order Confirmation #{$order->id}",
                'recipient' => $user->email,
                'order_id' => $order->id,
                'status' => 'sent',
            ]);
            
            return [
                'success' => true,
                'message' => 'Order confirmation email sent successfully.',
            ];
            
        } catch (Exception $e) {
            Log::error('Order confirmation email failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send order confirmation email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send order status update email.
     *
     * @param Order $order
     * @param string $newStatus
     * @param array $options
     * @return array
     */
    public function sendOrderStatusUpdate(Order $order, string $newStatus, array $options = []): array
    {
        try {
            $user = $order->user;
            
            if (!$user || !$user->email) {
                throw new Exception('User email not found.');
            }
            
            // Check if user wants to receive order emails
            if (!$this->userWantsEmail($user, 'order_updates')) {
                return [
                    'success' => false,
                    'message' => 'User has opted out of order emails.',
                ];
            }
            
            // Get email template
            $template = $this->getEmailTemplate('order_status_update');
            
            // Prepare email data
            $emailData = [
                'order' => $order,
                'user' => $user,
                'new_status' => $newStatus,
                'status_message' => $this->getStatusMessage($newStatus),
                'tracking_info' => $options['tracking_info'] ?? null,
                'template' => $template,
            ];
            
            // Send email
            if ($options['queue'] ?? true) {
                Mail::to($user->email)->queue(new OrderStatusUpdate($emailData));
            } else {
                Mail::to($user->email)->send(new OrderStatusUpdate($emailData));
            }
            
            // Log email
            $this->logEmail([
                'user_id' => $user->id,
                'type' => 'order_status_update',
                'subject' => "Order #{$order->id} Status Update",
                'recipient' => $user->email,
                'order_id' => $order->id,
                'status' => 'sent',
                'metadata' => json_encode(['new_status' => $newStatus]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Order status update email sent successfully.',
            ];
            
        } catch (Exception $e) {
            Log::error('Order status update email failed', [
                'order_id' => $order->id,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send order status update email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send welcome email to new user.
     *
     * @param User $user
     * @param array $options
     * @return array
     */
    public function sendWelcomeEmail(User $user, array $options = []): array
    {
        try {
            if (!$user->email) {
                throw new Exception('User email not found.');
            }
            
            // Get email template
            $template = $this->getEmailTemplate('welcome');
            
            // Get welcome offer/coupon if available
            $welcomeCoupon = $this->getWelcomeCoupon();
            
            // Prepare email data
            $emailData = [
                'user' => $user,
                'welcome_coupon' => $welcomeCoupon,
                'featured_products' => $this->getFeaturedProducts(4),
                'template' => $template,
            ];
            
            // Send email
            if ($options['queue'] ?? true) {
                Mail::to($user->email)->queue(new WelcomeEmail($emailData));
            } else {
                Mail::to($user->email)->send(new WelcomeEmail($emailData));
            }
            
            // Log email
            $this->logEmail([
                'user_id' => $user->id,
                'type' => 'welcome',
                'subject' => 'Welcome to Our Store!',
                'recipient' => $user->email,
                'status' => 'sent',
            ]);
            
            return [
                'success' => true,
                'message' => 'Welcome email sent successfully.',
            ];
            
        } catch (Exception $e) {
            Log::error('Welcome email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send welcome email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send password reset email.
     *
     * @param User $user
     * @param string $resetToken
     * @param array $options
     * @return array
     */
    public function sendPasswordResetEmail(User $user, string $resetToken, array $options = []): array
    {
        try {
            if (!$user->email) {
                throw new Exception('User email not found.');
            }
            
            // Get email template
            $template = $this->getEmailTemplate('password_reset');
            
            // Generate reset URL
            $resetUrl = url('/password/reset/' . $resetToken . '?email=' . urlencode($user->email));
            
            // Prepare email data
            $emailData = [
                'user' => $user,
                'reset_token' => $resetToken,
                'reset_url' => $resetUrl,
                'expires_at' => now()->addHours(24),
                'template' => $template,
            ];
            
            // Send email immediately (don't queue password resets)
            Mail::to($user->email)->send(new PasswordReset($emailData));
            
            // Log email
            $this->logEmail([
                'user_id' => $user->id,
                'type' => 'password_reset',
                'subject' => 'Password Reset Request',
                'recipient' => $user->email,
                'status' => 'sent',
            ]);
            
            return [
                'success' => true,
                'message' => 'Password reset email sent successfully.',
            ];
            
        } catch (Exception $e) {
            Log::error('Password reset email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send password reset email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send product back in stock notification.
     *
     * @param Product $product
     * @param array $users
     * @param array $options
     * @return array
     */
    public function sendProductBackInStockNotification(Product $product, array $users, array $options = []): array
    {
        try {
            $sentCount = 0;
            $failedCount = 0;
            
            // Get email template
            $template = $this->getEmailTemplate('product_back_in_stock');
            
            foreach ($users as $user) {
                try {
                    if (!$user->email || !$this->userWantsEmail($user, 'product_updates')) {
                        continue;
                    }
                    
                    // Prepare email data
                    $emailData = [
                        'user' => $user,
                        'product' => $product,
                        'related_products' => $this->getRelatedProducts($product, 3),
                        'template' => $template,
                    ];
                    
                    // Send email
                    if ($options['queue'] ?? true) {
                        Mail::to($user->email)->queue(new ProductBackInStock($emailData));
                    } else {
                        Mail::to($user->email)->send(new ProductBackInStock($emailData));
                    }
                    
                    // Log email
                    $this->logEmail([
                        'user_id' => $user->id,
                        'type' => 'product_back_in_stock',
                        'subject' => "{$product->name} is Back in Stock!",
                        'recipient' => $user->email,
                        'product_id' => $product->id,
                        'status' => 'sent',
                    ]);
                    
                    $sentCount++;
                    
                } catch (Exception $e) {
                    Log::error('Product back in stock email failed for user', [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $failedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => "Product back in stock notifications sent.",
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ];
            
        } catch (Exception $e) {
            Log::error('Product back in stock notification failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send product back in stock notifications.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send newsletter email.
     *
     * @param array $content
     * @param array $recipients
     * @param array $options
     * @return array
     */
    public function sendNewsletter(array $content, array $recipients = [], array $options = []): array
    {
        try {
            // If no recipients specified, get all newsletter subscribers
            if (empty($recipients)) {
                $recipients = $this->getNewsletterSubscribers();
            }
            
            $sentCount = 0;
            $failedCount = 0;
            
            // Get email template
            $template = $this->getEmailTemplate('newsletter');
            
            foreach ($recipients as $recipient) {
                try {
                    $user = $recipient instanceof User ? $recipient : User::find($recipient['user_id'] ?? null);
                    $email = $user ? $user->email : $recipient['email'];
                    
                    if (!$email) {
                        continue;
                    }
                    
                    // Check if user wants newsletters
                    if ($user && !$this->userWantsEmail($user, 'newsletter')) {
                        continue;
                    }
                    
                    // Generate unsubscribe token
                    $unsubscribeToken = $this->generateUnsubscribeToken($email);
                    
                    // Prepare email data
                    $emailData = [
                        'user' => $user,
                        'content' => $content,
                        'unsubscribe_url' => url('/newsletter/unsubscribe/' . $unsubscribeToken),
                        'template' => $template,
                    ];
                    
                    // Send email
                    if ($options['queue'] ?? true) {
                        Mail::to($email)->queue(new NewsletterEmail($emailData));
                    } else {
                        Mail::to($email)->send(new NewsletterEmail($emailData));
                    }
                    
                    // Log email
                    $this->logEmail([
                        'user_id' => $user ? $user->id : null,
                        'type' => 'newsletter',
                        'subject' => $content['subject'] ?? 'Newsletter',
                        'recipient' => $email,
                        'status' => 'sent',
                        'metadata' => json_encode(['newsletter_id' => $content['id'] ?? null]),
                    ]);
                    
                    $sentCount++;
                    
                } catch (Exception $e) {
                    Log::error('Newsletter email failed for recipient', [
                        'recipient' => $recipient,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $failedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Newsletter sent successfully.',
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ];
            
        } catch (Exception $e) {
            Log::error('Newsletter sending failed', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send newsletter.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send abandoned cart reminder.
     *
     * @param User $user
     * @param array $cartItems
     * @param array $options
     * @return array
     */
    public function sendAbandonedCartReminder(User $user, array $cartItems, array $options = []): array
    {
        try {
            if (!$user->email || !$this->userWantsEmail($user, 'marketing')) {
                return [
                    'success' => false,
                    'message' => 'User has opted out of marketing emails.',
                ];
            }
            
            // Get email template
            $template = $this->getEmailTemplate('abandoned_cart');
            
            // Calculate cart total
            $cartTotal = collect($cartItems)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });
            
            // Get discount coupon for abandoned cart
            $discountCoupon = $this->getAbandonedCartCoupon();
            
            // Prepare email data
            $emailData = [
                'user' => $user,
                'cart_items' => $cartItems,
                'cart_total' => $cartTotal,
                'discount_coupon' => $discountCoupon,
                'cart_url' => url('/cart'),
                'template' => $template,
            ];
            
            // Send email
            if ($options['queue'] ?? true) {
                Mail::to($user->email)->queue(new AbandonedCartReminder($emailData));
            } else {
                Mail::to($user->email)->send(new AbandonedCartReminder($emailData));
            }
            
            // Log email
            $this->logEmail([
                'user_id' => $user->id,
                'type' => 'abandoned_cart',
                'subject' => 'You left something in your cart',
                'recipient' => $user->email,
                'status' => 'sent',
                'metadata' => json_encode(['cart_total' => $cartTotal]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Abandoned cart reminder sent successfully.',
            ];
            
        } catch (Exception $e) {
            Log::error('Abandoned cart reminder failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send abandoned cart reminder.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send product recommendation email.
     *
     * @param User $user
     * @param array $products
     * @param array $options
     * @return array
     */
    public function sendProductRecommendation(User $user, array $products, array $options = []): array
    {
        try {
            if (!$user->email || !$this->userWantsEmail($user, 'marketing')) {
                return [
                    'success' => false,
                    'message' => 'User has opted out of marketing emails.',
                ];
            }
            
            // Get email template
            $template = $this->getEmailTemplate('product_recommendation');
            
            // Prepare email data
            $emailData = [
                'user' => $user,
                'recommended_products' => $products,
                'recommendation_reason' => $options['reason'] ?? 'Based on your browsing history',
                'template' => $template,
            ];
            
            // Send email
            if ($options['queue'] ?? true) {
                Mail::to($user->email)->queue(new ProductRecommendation($emailData));
            } else {
                Mail::to($user->email)->send(new ProductRecommendation($emailData));
            }
            
            // Log email
            $this->logEmail([
                'user_id' => $user->id,
                'type' => 'product_recommendation',
                'subject' => 'Products you might like',
                'recipient' => $user->email,
                'status' => 'sent',
                'metadata' => json_encode(['product_count' => count($products)]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Product recommendation email sent successfully.',
            ];
            
        } catch (Exception $e) {
            Log::error('Product recommendation email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send product recommendation email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send promotional email.
     *
     * @param array $promotion
     * @param array $recipients
     * @param array $options
     * @return array
     */
    public function sendPromotionalEmail(array $promotion, array $recipients = [], array $options = []): array
    {
        try {
            // If no recipients specified, get marketing subscribers
            if (empty($recipients)) {
                $recipients = $this->getMarketingSubscribers();
            }
            
            $sentCount = 0;
            $failedCount = 0;
            
            // Get email template
            $template = $this->getEmailTemplate('promotional');
            
            foreach ($recipients as $recipient) {
                try {
                    $user = $recipient instanceof User ? $recipient : User::find($recipient['user_id'] ?? null);
                    $email = $user ? $user->email : $recipient['email'];
                    
                    if (!$email) {
                        continue;
                    }
                    
                    // Check if user wants marketing emails
                    if ($user && !$this->userWantsEmail($user, 'marketing')) {
                        continue;
                    }
                    
                    // Generate unsubscribe token
                    $unsubscribeToken = $this->generateUnsubscribeToken($email);
                    
                    // Prepare email data
                    $emailData = [
                        'user' => $user,
                        'promotion' => $promotion,
                        'unsubscribe_url' => url('/marketing/unsubscribe/' . $unsubscribeToken),
                        'template' => $template,
                    ];
                    
                    // Send email
                    if ($options['queue'] ?? true) {
                        Mail::to($email)->queue(new PromotionalEmail($emailData));
                    } else {
                        Mail::to($email)->send(new PromotionalEmail($emailData));
                    }
                    
                    // Log email
                    $this->logEmail([
                        'user_id' => $user ? $user->id : null,
                        'type' => 'promotional',
                        'subject' => $promotion['subject'] ?? 'Special Offer',
                        'recipient' => $email,
                        'status' => 'sent',
                        'metadata' => json_encode(['promotion_id' => $promotion['id'] ?? null]),
                    ]);
                    
                    $sentCount++;
                    
                } catch (Exception $e) {
                    Log::error('Promotional email failed for recipient', [
                        'recipient' => $recipient,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $failedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Promotional email sent successfully.',
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ];
            
        } catch (Exception $e) {
            Log::error('Promotional email sending failed', [
                'promotion' => $promotion,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send promotional email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get email statistics.
     *
     * @param array $filters
     * @return array
     */
    public function getEmailStatistics(array $filters = []): array
    {
        $cacheKey = 'email_statistics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = EmailLog::query();
            
            // Apply date filters
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            
            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            
            // Apply type filter
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            $emails = $query->get();
            
            // Calculate statistics
            $totalEmails = $emails->count();
            $sentEmails = $emails->where('status', 'sent')->count();
            $failedEmails = $emails->where('status', 'failed')->count();
            $deliveryRate = $totalEmails > 0 ? ($sentEmails / $totalEmails) * 100 : 0;
            
            // Email types breakdown
            $emailTypes = $emails->groupBy('type')->map->count();
            
            // Daily email volume
            $dailyVolume = $emails->groupBy(function ($email) {
                return $email->created_at->format('Y-m-d');
            })->map->count();
            
            return [
                'total_emails' => $totalEmails,
                'sent_emails' => $sentEmails,
                'failed_emails' => $failedEmails,
                'delivery_rate' => round($deliveryRate, 2),
                'email_types' => $emailTypes,
                'daily_volume' => $dailyVolume,
            ];
        });
    }

    /**
     * Check if user wants to receive specific type of email.
     *
     * @param User $user
     * @param string $type
     * @return bool
     */
    protected function userWantsEmail(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences ?? [];
        
        return $preferences['email'][$type] ?? true;
    }

    /**
     * Get email template.
     *
     * @param string $type
     * @return EmailTemplate|null
     */
    protected function getEmailTemplate(string $type): ?EmailTemplate
    {
        return Cache::remember("email_template_{$type}", 3600, function () use ($type) {
            return EmailTemplate::where('type', $type)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Log email sending.
     *
     * @param array $data
     * @return EmailLog
     */
    protected function logEmail(array $data): EmailLog
    {
        return EmailLog::create(array_merge($data, [
            'sent_at' => now(),
        ]));
    }

    /**
     * Get status message for order status.
     *
     * @param string $status
     * @return string
     */
    protected function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Your order has been received and is being processed.',
            'confirmed' => 'Your order has been confirmed and will be prepared for shipping.',
            'processing' => 'Your order is currently being prepared.',
            'shipped' => 'Your order has been shipped and is on its way to you.',
            'delivered' => 'Your order has been delivered successfully.',
            'cancelled' => 'Your order has been cancelled.',
            'refunded' => 'Your order has been refunded.',
            default => 'Your order status has been updated.',
        };
    }

    /**
     * Get welcome coupon.
     *
     * @return array|null
     */
    protected function getWelcomeCoupon(): ?array
    {
        // This would typically fetch from a coupons table
        return [
            'code' => 'WELCOME10',
            'discount' => 10,
            'type' => 'percentage',
            'expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Get featured products.
     *
     * @param int $limit
     * @return Collection
     */
    protected function getFeaturedProducts(int $limit = 4)
    {
        return Cache::remember("featured_products_{$limit}", 3600, function () use ($limit) {
            return Product::where('is_featured', true)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get related products.
     *
     * @param Product $product
     * @param int $limit
     * @return Collection
     */
    protected function getRelatedProducts(Product $product, int $limit = 3)
    {
        return Cache::remember("related_products_{$product->id}_{$limit}", 3600, function () use ($product, $limit) {
            return Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get newsletter subscribers.
     *
     * @return Collection
     */
    protected function getNewsletterSubscribers()
    {
        return User::whereJsonContains('notification_preferences->email->newsletter', true)
            ->orWhereNull('notification_preferences')
            ->get();
    }

    /**
     * Get marketing subscribers.
     *
     * @return Collection
     */
    protected function getMarketingSubscribers()
    {
        return User::whereJsonContains('notification_preferences->email->marketing', true)
            ->orWhereNull('notification_preferences')
            ->get();
    }

    /**
     * Get abandoned cart coupon.
     *
     * @return array|null
     */
    protected function getAbandonedCartCoupon(): ?array
    {
        return [
            'code' => 'COMEBACK15',
            'discount' => 15,
            'type' => 'percentage',
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Generate unsubscribe token.
     *
     * @param string $email
     * @return string
     */
    protected function generateUnsubscribeToken(string $email): string
    {
        return hash('sha256', $email . config('app.key') . now()->timestamp);
    }

    /**
     * Clear email cache.
     *
     * @return void
     */
    public function clearEmailCache(): void
    {
        Cache::tags(['emails'])->flush();
    }
}