<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CheckRole
 * มิดเดิลแวร์สำหรับตรวจสอบสิทธิ์การเข้าถึง Route ตามบทบาทผู้ใช้งาน (Role-Based Access Control - RBAC)
 * การใช้งานใน Route:
 * - middleware('role:admin') -> อนุญาตเฉพาะแอดมิน
 * - middleware('role:technician') -> อนุญาตเฉพาะช่างซ่อม
 * - middleware('role:user') -> อนุญาตเฉพาะผู้ใช้งานทั่วไป
 * - middleware('role:user,admin') -> อนุญาตทั้งผู้ใช้ทั่วไปและแอดมิน
 */
class CheckRole
{
    /**
     * ดักจับและตรวจสอบสิทธิ์คำขอ (Request) ก่อนส่งต่อให้ Controller
     *
     * @param Request $request คำขอ HTTP ที่ส่งเข้ามา
     * @param Closure $next ฟังก์ชันส่งต่อคำขอไปยังขั้นตอนถัดไป
     * @param string ...$roles รายการบทบาทที่ได้รับอนุญาตให้ผ่านได้
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง หากยัง ให้พาไปหน้า Login
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }

        // 2. ดึงบทบาท (Role) ของผู้ใช้งานปัจจุบัน
        $userRole = auth()->user()->role;

        // 3. ตรวจสอบว่า Role ของผู้ใช้ตรงกับหนึ่งในสิทธิ์ที่ Route กำหนดไว้หรือไม่
        if (!in_array($userRole, $roles)) {
            // หากไม่มีสิทธิ์ ให้ตอบกลับด้วย HTTP 403 Forbidden
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Unauthorized role: ' . $userRole . ')');
        }

        // หากผ่านเงื่อนไข อนุญาตให้ประมวลผลคำขอต่อไป
        return $next($request);
    }
}
