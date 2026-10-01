<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 * คลาสสำหรับกำหนดค่าเริ่มต้นของแอปพลิเคชัน (Service Provider)
 * ในระบบ Laravel:
 * - register(): ใช้สำหรับผูก Services หรือ Dependencies ต่างๆ ลง Service Container
 * - boot(): ใช้รันคำสั่งกำหนดค่าสากลของระบบ เช่น การตั้งค่า Model, Schema, หรือ Global Blade Directives
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * ลงทะเบียน Service ต่างๆ ของแอปพลิเคชัน
     */
    public function register(): void
    {
        //
    }

    /**
     * กำหนดค่าเริ่มต้นให้กับแอปพลิเคชันเมื่อระบบบูตเสร็จสมบูรณ์
     */
    public function boot(): void
    {
        // สามารถเพิ่มการตั้งค่า Pagination สากล หรือ Model Strict Mode ได้ที่นี่
    }
}
