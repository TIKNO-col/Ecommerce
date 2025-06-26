<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\EmailService;

class NewsletterController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Subscribe to newsletter.
     */
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos.',
                    'errors' => $validator->errors()
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        try {
            $email = $request->input('email');
            $name = $request->input('name');

            // Check if user already exists
            $user = User::where('email', $email)->first();

            if ($user) {
                // Update existing user's newsletter preference
                $preferences = $user->notification_preferences ?? [];
                $preferences['email']['newsletter'] = true;
                $user->update(['notification_preferences' => $preferences]);
            } else {
                // Create new user for newsletter subscription
                $user = User::create([
                    'name' => $name ?? 'Suscriptor',
                    'email' => $email,
                    'password' => null, // Newsletter-only user
                    'notification_preferences' => [
                        'email' => [
                            'newsletter' => true,
                            'promotions' => true,
                            'order_updates' => false,
                        ]
                    ]
                ]);
            }

            // Send welcome email
            $this->sendWelcomeEmail($user);

            Log::info('Newsletter subscription successful', [
                'email' => $email,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Gracias por suscribirte! Te hemos enviado un email de confirmación.'
                ]);
            }

            return back()->with('success', '¡Gracias por suscribirte! Te hemos enviado un email de confirmación.');

        } catch (\Exception $e) {
            Log::error('Newsletter subscription failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar la suscripción. Inténtalo de nuevo.'
                ], 500);
            }

            return back()->with('error', 'Error al procesar la suscripción. Inténtalo de nuevo.');
        }
    }

    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe(Request $request, $token = null): RedirectResponse
    {
        try {
            $email = null;

            if ($token) {
                // Decode token to get email
                $email = base64_decode($token);
            } elseif ($request->has('email')) {
                $email = $request->input('email');
            }

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->route('home')->with('error', 'Token de desuscripción inválido.');
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                $preferences = $user->notification_preferences ?? [];
                $preferences['email']['newsletter'] = false;
                $user->update(['notification_preferences' => $preferences]);

                Log::info('Newsletter unsubscription successful', [
                    'email' => $email,
                    'user_id' => $user->id
                ]);

                return redirect()->route('home')->with('success', 'Te has desuscrito exitosamente del newsletter.');
            }

            return redirect()->route('home')->with('error', 'No se encontró una suscripción con ese email.');

        } catch (\Exception $e) {
            Log::error('Newsletter unsubscription failed', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('home')->with('error', 'Error al procesar la desuscripción.');
        }
    }

    /**
     * Show unsubscribe form.
     */
    public function showUnsubscribe(Request $request)
    {
        return view('newsletter.unsubscribe', [
            'email' => $request->input('email')
        ]);
    }

    /**
     * Send welcome email to new subscriber.
     */
    protected function sendWelcomeEmail(User $user)
    {
        try {
            $this->emailService->sendWelcomeEmail($user, [
                'subscription_date' => now()->format('Y-m-d H:i:s'),
                'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => base64_encode($user->email)])
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send newsletter welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get newsletter statistics (admin only).
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_subscribers' => User::whereJsonContains('notification_preferences->email->newsletter', true)->count(),
                'new_subscribers_today' => User::whereJsonContains('notification_preferences->email->newsletter', true)
                    ->whereDate('created_at', today())->count(),
                'new_subscribers_week' => User::whereJsonContains('notification_preferences->email->newsletter', true)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'new_subscribers_month' => User::whereJsonContains('notification_preferences->email->newsletter', true)
                    ->whereMonth('created_at', now()->month)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get newsletter statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas.'
            ], 500);
        }
    }
}