<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('auth.login')
                ->with('error', 'Debes iniciar sesión para acceder al panel de administración.');
        }

        // Check if user is admin
        $user = auth()->user();
        
        // Check if user has admin role or is_admin flag
        if (!$user->is_admin && !$user->hasRole('admin')) {
            abort(403, 'No tienes permisos para acceder al panel de administración.');
        }

        return $next($request);
    }

    /**
     * Check if user has admin privileges.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function isAdmin($user): bool
    {
        // Check multiple ways a user can be admin
        return $user->is_admin === true || 
               $user->role === 'admin' || 
               $user->hasRole('admin') ||
               in_array($user->email, config('app.admin_emails', []));
    }
}