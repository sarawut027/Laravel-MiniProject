<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }

        $userRole = auth()->user()->role;

        if (!in_array($userRole, $roles)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Unauthorized role: ' . $userRole . ')');
        }

        return $next($request);
    }
}
