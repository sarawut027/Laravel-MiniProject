<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

/**
 * กำหนดค่าหลักของแอปพลิเคชัน Laravel 12 (Application Configuration)
 * 1. withRouting: กำหนดไฟล์เส้นทาง (Web Routes, Console Commands, Health Check)
 * 2. withMiddleware: กำหนด Middleware สากล และลงทะเบียน Alias
 *    - ลงทะเบียน 'role' => CheckRole::class เพื่อใช้ในการตรวจสอบสิทธิ์ตามบทบาทผู้ใช้
 * 3. withExceptions: จัดการข้อผิดพลาดและ Exception Handling ของระบบ
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ลงทะเบียน Middleware Alias 'role' เพื่อเรียกใช้ใน routes/web.php เช่น ->middleware('role:admin')
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // สามารถจัดการ Custom Exceptions เพิ่มเติมได้ที่นี่
    })->create();
