<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (เส้นทาง URL ของระบบแจ้งซ่อมบำรุง)
|--------------------------------------------------------------------------
| กำหนดเส้นทางทั้งหมดของระบบ แบ่งตามสิทธิ์ผู้ใช้งาน (RBAC):
| 1. ผู้เยี่ยมชมทั่วไป (Guest): หน้า Login, Register
| 2. ผู้ใช้ล็อกอินแล้ว (Auth): หน้า Dashboard Router, หน้าดูรายละเอียดงาน (Shared)
| 3. ผู้ใช้งานทั่วไป (User): แจ้งซ่อมใหม่, ติดตามงาน, ยกเลิกงาน, ประเมินดาว
| 4. ช่างซ่อม (Technician): รับงาน, อัปเดตสถานะ, แนบรูปซ่อมเสร็จ
| 5. ผู้ดูแลระบบ (Admin): สรุปสถิติ KPI, จัดการงานทั้งหมด, มอบหมายงานให้ช่าง
*/

// ==========================================
// 1. หน้าแรก (Root URL /) - Smart Redirection
// ==========================================
Route::get('/', function () {
    // หากล็อกอินแล้ว ให้กระจายไปยังหน้าแรกตาม Role ของตนเอง
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'technician' => redirect()->route('tech.tasks'),
            default => redirect()->route('tickets.index'),
        };
    }
    // หากยังไม่ล็อกอิน ให้พาไปหน้าล็อกอิน
    return redirect()->route('login');
})->name('home');

// ==========================================
// 2. ระบบยืนยันตัวตน (Authentication Routes)
// เข้าถึงได้เฉพาะคนที่ "ยังไม่ได้ล็อกอิน" (guest middleware)
// ==========================================
Route::middleware('guest')->group(function () {
    // หน้าฟอร์มล็อกอิน และประมวลผลการล็อกอิน
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    // หน้าฟอร์มสมัครสมาชิก และประมวลผลการสมัคร
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// ออกจากระบบ (ต้องล็อกอินอยู่แล้วเท่านั้น)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ==========================================
// 3. เส้นทางสำหรับผู้ใช้ที่ล็อกอินแล้ว (Authenticated Routes)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // จุดรวม Dashboard กลาง: ทำหน้าที่ Route Router กระจายผู้ใช้ตาม Role
    Route::get('/dashboard', function () {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'technician' => redirect()->route('tech.tasks'),
            default => redirect()->route('tickets.index'),
        };
    })->name('dashboard');

    // -------------------------------------------------------------
    // 3.1 สิทธิ์ผู้ใช้งานทั่วไป และผู้ดูแลระบบ (role:user,admin)
    // -------------------------------------------------------------
    Route::middleware(['role:user,admin'])->group(function () {
        // หน้ารายการคำขอแจ้งซ่อมของตนเอง
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        
        // หน้าแบบฟอร์มสร้างใบแจ้งซ่อมใหม่ (ต้องอยู่ก่อน /tickets/{ticket})
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        
        // บันทึกการแจ้งซ่อมใหม่ พร้อมรูปภาพ
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    });

    // -------------------------------------------------------------
    // 3.2 สิทธิ์เฉพาะเจ้าของเรื่อง (role:user)
    // -------------------------------------------------------------
    Route::middleware(['role:user'])->group(function () {
        // กดยกเลิกคำขอแจ้งซ่อม (ทำได้เฉพาะตอนสถานะ pending)
        Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])->name('tickets.cancel');
        
        // บันทึกคะแนนประเมินดาว (1-5 ดาว) และตรวจรับงานปิดงาน (สถานะ completed -> closed)
        Route::post('/tickets/{ticket}/review', [TicketController::class, 'review'])->name('tickets.review');
    });

    // -------------------------------------------------------------
    // 3.3 หน้ารายละเอียดงานซ่อม (Shared Detail & Timeline)
    // ใช้ร่วมกันระหว่าง User (เจ้าของเรื่อง), Technician (ช่างที่รับงาน), และ Admin
    // ใส่ whereNumber('ticket') เพื่อป้องกันการชนกับ path ข้อความอื่นๆ
    // -------------------------------------------------------------
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
        ->whereNumber('ticket')
        ->name('tickets.show');

    // -------------------------------------------------------------
    // 3.4 เส้นทางสำหรับช่างซ่อม (role:technician)
    // มีคำนำหน้า URL เป็น /technician/...
    // -------------------------------------------------------------
    Route::middleware(['role:technician'])->prefix('technician')->group(function () {
        // รายการงานซ่อมที่ตนเองได้รับมอบหมาย (My Tasks)
        Route::get('/tasks', [TechnicianController::class, 'index'])->name('tech.tasks');
        
        // อัปเดตสถานะงาน (กำลังซ่อม / รออะไหล่ / ซ่อมเสร็จ) พร้อมแนบรูปหลังซ่อมและระบุค่าใช้จ่าย
        Route::post('/tasks/{ticket}/status', [TechnicianController::class, 'updateStatus'])->name('tech.updateStatus');
    });

    // -------------------------------------------------------------
    // 3.5 เส้นทางสำหรับผู้ดูแลระบบ (role:admin)
    // มีคำนำหน้า URL เป็น /admin/...
    // -------------------------------------------------------------
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // แดชบอร์ดสรุปสถิติตัวเลข KPI 5 รายการ และข้อมูลชาร์ต
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        
        // รายการงานซ่อมทั้งหมดในระบบ เพื่อค้นหา กรอง และติดตามสถานะ
        Route::get('/tickets', [AdminController::class, 'tickets'])->name('admin.tickets.index');
        
        // สั่งมอบหมายงานและจ่ายงานให้ช่างซ่อม
        Route::post('/tickets/{ticket}/assign', [AdminController::class, 'assign'])->name('admin.tickets.assign');
    });
});

// โหลดเส้นทางการตั้งค่าโปรไฟล์และรหัสผ่าน
require __DIR__.'/settings.php';
