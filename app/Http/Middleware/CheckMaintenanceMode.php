<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rute esensial yang selalu boleh diakses: admin panel, otentikasi, aset storage, health check
        if ($request->is('admin*', 'login*', 'logout*', 'up', 'storage*', 'sanctum*')) {
            return $next($request);
        }

        $settings = Setting::first();
        if (! $settings || $settings->store_status !== 'maintenance') {
            return $next($request);
        }

        // 1. Admin terautentikasi selalu bebas mengakses
        if ($request->user()?->is_admin) {
            return $next($request);
        }

        // 2. Secret Key bypass via URL query (?bypass=KEY) atau Cookie
        $secret = trim((string) $settings->maintenance_secret);
        if ($secret !== '') {
            if ($request->query('bypass') === $secret) {
                return redirect($request->url())
                    ->withCookie(cookie('maintenance_bypass', $secret, 1440, null, null, false, false));
            }

            if ($request->cookie('maintenance_bypass') === $secret) {
                return $next($request);
            }
        }

        // 3. Whitelist IP
        $clientIp = $request->ip();
        if ($clientIp && in_array($clientIp, $settings->whitelistIpsList(), true)) {
            return $next($request);
        }

        // 4. Pengunjung umum: sajikan halaman pemeliharaan 503 elegan
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'maintenance',
                'message' => $settings->closed_message ?: 'Toko sedang dalam pemeliharaan sistem.',
                'estimated_end' => $settings->maintenance_end_time,
            ], 503);
        }

        return response()->view('errors.503', [
            'settings' => $settings,
        ], 503);
    }
}
