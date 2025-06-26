<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Mail\WelcomeEmail;
use App\Mail\PasswordReset;
use App\Mail\ProductBackInStock;
use App\Mail\NewsletterEmail;
use Exception;

class NotificationService
{
    /**
     * Send order confirmation email.
     *
     * @param Order $order
     * @return bool
     */
    public function sendOrderConfirmation(Order $order): bool
    {
        try {
            Mail::to($order->user->email)
                ->queue(new OrderConfirmation($order));
            
            $this->createNotification([
                'user_id' => $order->user_id,
                'type' => 'order_confirmation',
                'title' => 'Order Confirmed',
                'message' => "Your order #{$order->order_number} has been confirmed.",
                'data' => json_encode(['order_id' => $order->id]),
            ]);
            
            Log::info('Order confirmation email queued', [
                'order_id' => $order->id,
                'user_email' => $order->user->email,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send order status update notification.
     *
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    public function sendOrderStatusUpdate(Order $order, string $oldStatus, string $newStatus): bool
    {
        try {
            // Send email notification
            Mail::to($order->user->email)
                ->queue(new OrderStatusUpdate($order, $oldStatus, $newStatus));
            
            // Create in-app notification
            $statusMessages = [
                'processing' => 'Your order is being processed.',
                'shipped' => 'Your order has been shipped.',
                'delivered' => 'Your order has been delivered.',
                'cancelled' => 'Your order has been cancelled.',
                'refunded' => 'Your order has been refunded.',
            ];
            
            $message = $statusMessages[$newStatus] ?? "Your order status has been updated to {$newStatus}.";
            
            $this->createNotification([
                'user_id' => $order->user_id,
                'type' => 'order_status_update',
                'title' => 'Order Status Updated',
                'message' => $message,
                'data' => json_encode([
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]),
            ]);
            
            // Send push notification if enabled
            $this->sendPushNotification($order->user, [
                'title' => 'Order Update',
                'body' => $message,
                'data' => ['order_id' => $order->id],
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send order status update', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send welcome email to new user.
     *
     * @param User $user
     * @return bool
     */
    public function sendWelcomeEmail(User $user): bool
    {
        try {
            Mail::to($user->email)
                ->queue(new WelcomeEmail($user));
            
            $this->createNotification([
                'user_id' => $user->id,
                'type' => 'welcome',
                'title' => 'Welcome to Our Store!',
                'message' => 'Thank you for joining us. Start exploring our amazing products.',
                'data' => json_encode(['user_id' => $user->id]),
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send password reset email.
     *
     * @param User $user
     * @param string $token
     * @return bool
     */
    public function sendPasswordResetEmail(User $user, string $token): bool
    {
        try {
            Mail::to($user->email)
                ->queue(new PasswordReset($user, $token));
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send product back in stock notification.
     *
     * @param Product $product
     * @param Collection $users
     * @return bool
     */
    public function sendProductBackInStockNotification(Product $product, Collection $users): bool
    {
        try {
            foreach ($users as $user) {
                // Send email
                Mail::to($user->email)
                    ->queue(new ProductBackInStock($product, $user));
                
                // Create in-app notification
                $this->createNotification([
                    'user_id' => $user->id,
                    'type' => 'product_back_in_stock',
                    'title' => 'Product Back in Stock',
                    'message' => "{$product->name} is now available again!",
                    'data' => json_encode(['product_id' => $product->id]),
                ]);
                
                // Send push notification
                $this->sendPushNotification($user, [
                    'title' => 'Back in Stock!',
                    'body' => "{$product->name} is available again",
                    'data' => ['product_id' => $product->id],
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send back in stock notifications', [
                'product_id' => $product->id,
                'user_count' => $users->count(),
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send newsletter email.
     *
     * @param Collection $users
     * @param array $newsletterData
     * @return bool
     */
    public function sendNewsletter(Collection $users, array $newsletterData): bool
    {
        try {
            foreach ($users as $user) {
                Mail::to($user->email)
                    ->queue(new NewsletterEmail($user, $newsletterData));
            }
            
            Log::info('Newsletter emails queued', [
                'recipient_count' => $users->count(),
                'subject' => $newsletterData['subject'] ?? 'Newsletter',
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send newsletter', [
                'user_count' => $users->count(),
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send push notification.
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function sendPushNotification(User $user, array $data): bool
    {
        try {
            // Check if user has push notifications enabled
            if (!$user->push_notifications_enabled || !$user->fcm_token) {
                return false;
            }
            
            // Here you would integrate with Firebase Cloud Messaging (FCM)
            // or another push notification service
            
            // For now, we'll just log the notification
            Log::info('Push notification would be sent', [
                'user_id' => $user->id,
                'fcm_token' => $user->fcm_token,
                'data' => $data,
            ]);
            
            // TODO: Implement actual FCM integration
            // $this->sendFCMNotification($user->fcm_token, $data);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send SMS notification.
     *
     * @param User $user
     * @param string $message
     * @return bool
     */
    public function sendSMSNotification(User $user, string $message): bool
    {
        try {
            // Check if user has SMS notifications enabled and phone number
            if (!$user->sms_notifications_enabled || !$user->phone) {
                return false;
            }
            
            // Here you would integrate with Twilio, AWS SNS, or another SMS service
            
            // For now, we'll just log the SMS
            Log::info('SMS notification would be sent', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'message' => $message,
            ]);
            
            // TODO: Implement actual SMS integration
            // $this->sendTwilioSMS($user->phone, $message);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send SMS notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Create in-app notification.
     *
     * @param array $data
     * @return Notification|null
     */
    public function createNotification(array $data): ?Notification
    {
        try {
            $notification = Notification::create([
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'data' => $data['data'] ?? null,
                'read_at' => null,
            ]);
            
            // Clear user's notification cache
            Cache::forget("user_notifications_{$data['user_id']}");
            Cache::forget("user_unread_notifications_count_{$data['user_id']}");
            
            return $notification;
            
        } catch (Exception $e) {
            Log::error('Failed to create notification', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Get user notifications.
     *
     * @param User $user
     * @param int $limit
     * @param bool $unreadOnly
     * @return Collection
     */
    public function getUserNotifications(User $user, int $limit = 20, bool $unreadOnly = false): Collection
    {
        $cacheKey = "user_notifications_{$user->id}_{$limit}_" . ($unreadOnly ? 'unread' : 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($user, $limit, $unreadOnly) {
            $query = $user->notifications()->orderBy('created_at', 'desc');
            
            if ($unreadOnly) {
                $query->whereNull('read_at');
            }
            
            return $query->limit($limit)->get();
        });
    }

    /**
     * Mark notification as read.
     *
     * @param Notification $notification
     * @return bool
     */
    public function markAsRead(Notification $notification): bool
    {
        try {
            if ($notification->read_at) {
                return true; // Already read
            }
            
            $notification->update(['read_at' => now()]);
            
            // Clear user's notification cache
            Cache::forget("user_notifications_{$notification->user_id}");
            Cache::forget("user_unread_notifications_count_{$notification->user_id}");
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Mark all user notifications as read.
     *
     * @param User $user
     * @return bool
     */
    public function markAllAsRead(User $user): bool
    {
        try {
            $user->notifications()
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            
            // Clear user's notification cache
            Cache::forget("user_notifications_{$user->id}");
            Cache::forget("user_unread_notifications_count_{$user->id}");
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get unread notifications count.
     *
     * @param User $user
     * @return int
     */
    public function getUnreadCount(User $user): int
    {
        $cacheKey = "user_unread_notifications_count_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->notifications()->whereNull('read_at')->count();
        });
    }

    /**
     * Delete old notifications.
     *
     * @param int $daysOld
     * @return int
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        try {
            $deletedCount = Notification::where('created_at', '<', now()->subDays($daysOld))
                ->delete();
            
            Log::info('Old notifications deleted', [
                'days_old' => $daysOld,
                'deleted_count' => $deletedCount,
            ]);
            
            return $deletedCount;
            
        } catch (Exception $e) {
            Log::error('Failed to delete old notifications', [
                'days_old' => $daysOld,
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    /**
     * Send bulk notifications.
     *
     * @param Collection $users
     * @param array $notificationData
     * @return bool
     */
    public function sendBulkNotifications(Collection $users, array $notificationData): bool
    {
        try {
            $notifications = [];
            $now = now();
            
            foreach ($users as $user) {
                $notifications[] = [
                    'user_id' => $user->id,
                    'type' => $notificationData['type'],
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'data' => $notificationData['data'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            // Bulk insert notifications
            Notification::insert($notifications);
            
            // Clear cache for all users
            foreach ($users as $user) {
                Cache::forget("user_notifications_{$user->id}");
                Cache::forget("user_unread_notifications_count_{$user->id}");
            }
            
            Log::info('Bulk notifications sent', [
                'user_count' => $users->count(),
                'type' => $notificationData['type'],
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send bulk notifications', [
                'user_count' => $users->count(),
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get notification statistics.
     *
     * @return array
     */
    public function getNotificationStatistics(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::whereNull('read_at')->count(),
            'notifications_today' => Notification::whereDate('created_at', today())->count(),
            'notifications_this_week' => Notification::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'notifications_this_month' => Notification::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'notification_types' => Notification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Update user notification preferences.
     *
     * @param User $user
     * @param array $preferences
     * @return bool
     */
    public function updateNotificationPreferences(User $user, array $preferences): bool
    {
        try {
            $user->update([
                'email_notifications_enabled' => $preferences['email'] ?? true,
                'push_notifications_enabled' => $preferences['push'] ?? true,
                'sms_notifications_enabled' => $preferences['sms'] ?? false,
                'marketing_emails_enabled' => $preferences['marketing'] ?? false,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to update notification preferences', [
                'user_id' => $user->id,
                'preferences' => $preferences,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}