<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(): View
    {
        if (Auth::check()) {
            return view('user.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Show registration form.
     */
    public function showRegister(): View
    {
        if (Auth::check()) {
            return view('user.dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Transfer guest cart to user if exists
            $this->transferGuestCart($request);
            
            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            return redirect()->intended(route('home'))
                ->with('success', '¡Bienvenido de vuelta, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas son incorrectas.',
        ])->withInput($request->except('password'));
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => 'required|accepted',
            'phone' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date|before:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'email_verified_at' => null, // Will be verified via email
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Send email verification
            $user->sendEmailVerificationNotification();

            // Don't log the user in automatically - redirect to login instead
            return redirect()->route('login')
                ->with('success', '¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.');
                
        } catch (\Exception $e) {
            return back()->withErrors([
                'general' => 'Error al crear la cuenta. Por favor, inténtalo de nuevo.'
            ])->withInput();
        }
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Sesión cerrada exitosamente.');
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password request.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            
            // Generate password reset token
            $token = Str::random(64);
            
            // Store token in database (you might want to create a password_resets table)
            $user->update([
                'password_reset_token' => $token,
                'password_reset_expires_at' => now()->addHours(1),
            ]);

            // Send password reset email
            // Mail::to($user->email)->send(new PasswordResetMail($user, $token));

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un enlace de restablecimiento de contraseña a tu email.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el email de restablecimiento.'
            ], 500);
        }
    }

    /**
     * Show reset password form.
     */
    public function showResetPassword(Request $request, string $token): View
    {
        $user = User::where('password_reset_token', $token)
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (!$user) {
            abort(404, 'Token de restablecimiento inválido o expirado.');
        }

        return view('auth.reset-password', compact('token', 'user'));
    }

    /**
     * Handle reset password request.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::where('email', $request->email)
            ->where('password_reset_token', $request->token)
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token de restablecimiento inválido o expirado.',
                'errors' => [
                    'token' => ['Token inválido o expirado.']
                ]
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
                'password_reset_token' => null,
                'password_reset_expires_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente.',
                'redirect' => route('auth.login')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restablecer la contraseña.'
            ], 500);
        }
    }

    /**
     * Redirect to social provider.
     */
    public function redirectToProvider(string $provider)
    {
        if (!in_array($provider, ['google', 'facebook', 'github'])) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle social provider callback.
     */
    public function handleProviderCallback(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'facebook', 'github'])) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if user exists with this email
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if ($user) {
                // Update social provider info
                $user->update([
                    $provider . '_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar() ?: $user->avatar,
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    $provider . '_id' => $socialUser->getId(),
                    'email_verified_at' => now(), // Social accounts are considered verified
                    'password' => Hash::make(Str::random(32)), // Random password
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            // Transfer guest cart to user if exists
            $this->transferGuestCart($request);

            return response()->json([
                'success' => true,
                'message' => '¡Bienvenido, ' . $user->name . '!',
                'redirect' => route('user.dashboard'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al autenticar con ' . ucfirst($provider) . '.'
            ], 500);
        }
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Enlace de verificación inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('user.dashboard')->with('message', 'Email ya verificado.');
        }

        $user->markEmailAsVerified();

        return redirect()->route('user.dashboard')->with('message', '¡Email verificado exitosamente!');
    }

    /**
     * Resend email verification.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'El email ya está verificado.'
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Enlace de verificación enviado.'
        ]);
    }

    /**
     * Check authentication status.
     */
    public function status(): JsonResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'email_verified' => $user->hasVerifiedEmail(),
                ],
                'cart_count' => Cart::getItemsCount(),
            ]);
        }

        return response()->json([
            'authenticated' => false,
            'cart_count' => Cart::getItemsCount(),
        ]);
    }

    /**
     * Transfer guest cart to authenticated user.
     */
    private function transferGuestCart(Request $request): void
    {
        $guestId = $request->session()->get('guest_id');
        
        if ($guestId && Auth::check()) {
            Cart::transferGuestCart($guestId, Auth::id());
            $request->session()->forget('guest_id');
        }
    }

    /**
     * Get or create guest ID.
     */
    public function getGuestId(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json([
                'guest_id' => null,
                'user_id' => Auth::id()
            ]);
        }

        $guestId = $request->session()->get('guest_id');
        
        if (!$guestId) {
            $guestId = 'guest_' . Str::random(32);
            $request->session()->put('guest_id', $guestId);
        }

        return response()->json([
            'guest_id' => $guestId,
            'user_id' => null
        ]);
    }

    /**
     * Handle account linking (for users who registered with email but want to link social accounts).
     */
    public function linkSocialAccount(Request $request, string $provider): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes estar autenticado para vincular cuentas.'
            ], 401);
        }

        if (!in_array($provider, ['google', 'facebook', 'github'])) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no soportado.'
            ], 400);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            $user = Auth::user();

            // Check if this social account is already linked to another user
            $existingUser = User::where($provider . '_id', $socialUser->getId())
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cuenta de ' . ucfirst($provider) . ' ya está vinculada a otra cuenta.'
                ], 400);
            }

            // Link the social account
            $user->update([
                $provider . '_id' => $socialUser->getId(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cuenta de ' . ucfirst($provider) . ' vinculada exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vincular la cuenta de ' . ucfirst($provider) . '.'
            ], 500);
        }
    }

    /**
     * Unlink social account.
     */
    public function unlinkSocialAccount(Request $request, string $provider): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes estar autenticado.'
            ], 401);
        }

        if (!in_array($provider, ['google', 'facebook', 'github'])) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no soportado.'
            ], 400);
        }

        $user = Auth::user();

        // Check if user has a password (to ensure they can still log in)
        if (!$user->password && !$user->google_id && !$user->facebook_id && !$user->github_id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desvincular tu única forma de acceso. Configura una contraseña primero.'
            ], 400);
        }

        try {
            $user->update([
                $provider . '_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cuenta de ' . ucfirst($provider) . ' desvinculada exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desvincular la cuenta.'
            ], 500);
        }
    }
}