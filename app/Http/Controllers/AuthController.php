<?php

// กำหนด namespace ของคลาสให้อยู่ในกลุ่ม App\Http\Controllers ตามมาตรฐาน PSR-4 ของ Laravel
namespace App\Http\Controllers;

// นำเข้า Eloquent Model ของ User เพื่อใช้จัดการข้อมูลผู้ใช้งานในฐานข้อมูล (สร้าง ค้นหา ตรวจสอบ)
use App\Models\User;
// นำเข้า Illuminate\Http\Request เพื่อใช้รับข้อมูล HTTP Request (GET, POST, Header, Session ฯลฯ)
use Illuminate\Http\Request;
// นำเข้า Auth Facade เพื่อใช้ระบบยืนยันตัวตนในตัวของ Laravel เช่น ตรวจสอบการล็อกอิน, ดึง User ปัจจุบัน
use Illuminate\Support\Facades\Auth;
// นำเข้า Hash Facade เพื่อใช้ฟังก์ชันเข้ารหัสแบบทางเดียวที่ปลอดภัย (Bcrypt / Argon2) สำหรับรหัสผ่าน
use Illuminate\Support\Facades\Hash;
// นำเข้ากฎการตรวจสอบรหัสผ่านขั้นสูงจาก Validation Rule เพื่อกำหนดเกณฑ์ความปลอดภัยของรหัสผ่าน
use Illuminate\Validation\Rules\Password;

/**
 * Class AuthController
 * คอนโทรลเลอร์สำหรับจัดการระบบยืนยันตัวตน (Authentication)
 * หน้าที่หลัก:
 * 1. แสดงหน้าเข้าสู่ระบบ (showLogin)
 * 2. ตรวจสอบข้อมูลเข้าสู่ระบบและนำทางตาม Role (login)
 * 3. ออกจากระบบ (logout)
 * 4. สมัครสมาชิกผู้ใช้งานทั่วไปใหม่ (showRegister & register)
 * 5. ฟังก์ชันช่วยนำทางผู้ใช้งานตามบทบาท (redirectByRole)
 */
class AuthController extends Controller
{
    /**
     * แสดงหน้าฟอร์มเข้าสู่ระบบ (Login Page)
     * หากล็อกอินอยู่แล้ว ให้ Redirect ไปยังหน้าที่สอดคล้องกับ Role ของตนเองทันที
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLogin()
    {
        // ตรวจสอบสถานะว่าผู้ใช้งานปัจจุบันได้เข้าสู่ระบบไว้แล้วหรือไม่
        if (Auth::check()) {
            // ดึงข้อมูล User ปัจจุบัน แล้วนำ role ส่งไปยังเมธอด redirectByRole เพื่อส่งไปยังหน้า Dashboard ที่ถูกต้อง
            return $this->redirectByRole(Auth::user()->role);
        }

        // หากยังไม่ได้เข้าสู่ระบบ ให้เรนเดอร์ไฟล์ Blade View ที่ resources/views/auth/login.blade.php
        return view('auth.login');
    }

    /**
     * ประมวลผลการเข้าสู่ระบบ (Login Submission)
     * ตรวจสอบความถูกต้องของ Email และ Password
     * ป้องกัน Session Fixation ด้วย session()->regenerate()
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // 1. ตรวจสอบความถูกต้องของฟิลด์ที่ส่งมาจาก Form เข้าสู่ระบบ
        $credentials = $request->validate([
            // ฟิลด์ email ต้องมีค่า (required) และต้องมีรูปแบบเป็นอีเมลที่ถูกต้อง (email)
            'email' => ['required', 'email'],
            // ฟิลด์ password ต้องมีค่า (required) และเป็นสตริงตัวอักษร (string)
            'password' => ['required', 'string'],
        ], [
            // กำหนดข้อความภาษาไทยเมื่อไม่ผ่านเงื่อนไข required ของ email
            'email.required' => 'กรุณากรอกอีเมล',
            // กำหนดข้อความภาษาไทยเมื่อรูปแบบอีเมลไม่ถูกต้อง
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            // กำหนดข้อความภาษาไทยเมื่อไม่ผ่านเงื่อนไข required ของ password
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        // 2. ตรวจสอบสิทธิ์การเข้าใช้งานด้วย Auth::attempt โดยจับคู่ email และ hash ของ password ในฐานข้อมูล
        // พารามิเตอร์ตัวที่ 2 เป็นบูลีน boolean('remember') สำหรับจำการเข้าสู่ระบบ (Remember Me Cookie)
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // สร้าง Session ID ใหม่เพื่อป้องกันการโจมตีแบบ Session Fixation
            $request->session()->regenerate();
            // ดึงบทบาท (Role) ของผู้ใช้งานที่เพิ่งล็อกอินสำเร็จ เช่น admin, technician, user
            $role = Auth::user()->role;
            
            // ส่ง Redirect ไปยังหน้า Dashboard ที่สอดคล้องกับสิทธิ์ พร้อมแนบ Flash Message แจ้งเตือนสำเร็จ
            return $this->redirectByRole($role, 'เข้าสู่ระบบสำเร็จ');
        }

        // หากตรวจสอบอีเมลหรือรหัสผ่านไม่ผ่าน ให้ Redirect กลับไปหน้าเดิม (back) พร้อมส่ง Error Message
        return back()->withErrors([
            // แนบข้อความเตือนความผิดพลาดของอีเมลหรือรหัสผ่าน
            'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง',
        // ส่งคืนค่า email เดิมกลับไปด้วยเพื่อความสะดวกของผู้ใช้ ไม่ต้องพิมพ์ใหม่ แต่ไม่ส่งรหัสผ่านเพื่อความปลอดภัย
        ])->onlyInput('email');
    }

    /**
     * ออกจากระบบ (Logout)
     * ล้าง Session และสร้าง CSRF Token ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // เรียกใช้คำสั่งเคลียร์สถานะ Authentication ของผู้ใช้งานใน Guard ปัจจุบัน
        Auth::logout();

        // สั่งทำลายข้อมูลทั้งหมดที่เก็บอยู่ใน Session ของผู้ใช้รายนี้ (Invalidate Session)
        $request->session()->invalidate();
        // สั่งสร้าง CSRF Token ใหม่ ป้องกันการนำ Token เก่ามาใช้ซ้ำ (Session Hijacking / CSRF)
        $request->session()->regenerateToken();

        // ส่งผู้ใช้กลับไปยังหน้าล็อกอิน พร้อมส่ง Flash Message 'success' แจ้งว่าออกจากระบบแล้ว
        return redirect()->route('login')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }

    /**
     * แสดงหน้าฟอร์มสมัครสมาชิกใหม่ (Register Page)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRegister()
    {
        // หากผู้ใช้คนนี้ล็อกอินอยู่แล้ว ไม่ต้องให้สมัครซ้ำ
        if (Auth::check()) {
            // ส่งผู้ใช้กลับไปยังหน้าตามบทบาทของตนเองทันที
            return $this->redirectByRole(Auth::user()->role);
        }

        // แสดงหน้าแบบฟอร์มสมัครสมาชิกจากไฟล์ resources/views/auth/register.blade.php
        return view('auth.register');
    }

    /**
     * บันทึกการสมัครสมาชิกใหม่ลงฐานข้อมูล
     * กำหนด Role เริ่มต้นเป็น 'user' (ผู้ใช้งานทั่วไป) เสมอ
     * และทำการล็อกอินให้อัตโนมัติหลังสมัครเสร็จ
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        // 1. ตรวจสอบความถูกต้องของข้อมูลการสมัครสมาชิก
        $validated = $request->validate([
            // ชื่อ-นามสกุล ต้องมีค่า เป็นสตริง ความยาวไม่เกิน 255 ตัวอักษร
            'name' => ['required', 'string', 'max:255'],
            // อีเมล ต้องมีค่า รูปแบบถูกต้อง ความยาวไม่เกิน 255 และต้องไม่ซ้ำกับตาราง users ในคอลัมน์ email
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // รหัสผ่าน ต้องมีค่า ต้องตรงกับช่องยืนยันรหัสผ่าน (password_confirmation) และผ่านเกณฑ์ค่าเริ่มต้น
            'password' => ['required', 'confirmed', Password::defaults()],
            // เบอร์โทรศัพท์ อนุญาตให้เป็นค่าว่างได้ (nullable) หากกรอกต้องเป็นสตริง ไม่เกิน 50 ตัวอักษร
            'phone' => ['nullable', 'string', 'max:50'],
            // แผนก/ฝ่าย อนุญาตให้ว่างได้ หากกรอกต้องเป็นสตริง ไม่เกิน 100 ตัวอักษร
            'department' => ['nullable', 'string', 'max:100'],
        ], [
            // กำหนดข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้กรอกชื่อ
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            // กำหนดข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้กรอกอีเมล
            'email.required' => 'กรุณากรอกอีเมล',
            // กำหนดข้อความแจ้งเตือนภาษาไทยเมื่ออีเมลซ้ำกับในระบบ
            'email.unique' => 'อีเมลนี้ถูกใช้งานในระบบแล้ว',
            // กำหนดข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้กรอกรหัสผ่าน
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            // กำหนดข้อความแจ้งเตือนภาษาไทยเมื่อรหัสผ่านและช่องยืนยันไม่ตรงกัน
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        // 2. สร้างเรคอร์ดผู้ใช้ใหม่ลงในตาราง users ด้วย Eloquent Model
        $user = User::create([
            // บันทึกชื่อผู้ใช้งาน
            'name' => $validated['name'],
            // บันทึกอีเมล
            'email' => $validated['email'],
            // เข้ารหัสผ่านด้วยฟังก์ชัน Hash::make ก่อนบันทึกลงฐานข้อมูล ห้ามเก็บแบบ Plain text
            'password' => Hash::make($validated['password']),
            // กำหนด Role ให้เป็น 'user' โดยอัตโนมัติ เพื่อความปลอดภัย ป้องกันผู้ใช้แอบส่ง role=admin เข้ามา
            'role' => 'user',
            // บันทึกเบอร์โทรศัพท์ หากไม่มีให้บันทึกเป็น null
            'phone' => $validated['phone'] ?? null,
            // บันทึกแผนก หากไม่มีให้บันทึกเป็น null
            'department' => $validated['department'] ?? null,
        ]);

        // 3. ทำการล็อกอินผู้ใช้งานใหม่เข้าระบบทันทีด้วยคำสั่ง Auth::login
        Auth::login($user);
        // สร้าง Session ID ใหม่เพื่อความปลอดภัย
        $request->session()->regenerate();

        // ส่งผู้ใช้ไปหน้าแรกของงานแจ้งซ่อม (tickets.index) พร้อมแนบ Flash Message ต้อนรับ
        return redirect()->route('tickets.index')->with('success', 'สมัครสมาชิกและเข้าสู่ระบบสำเร็จ ยินดีต้อนรับ!');
    }

    /**
     * Helper Function สำหรับนำทาง (Redirect) ผู้ใช้ไปยังหน้าหลักตามบทบาท (Role)
     * - Admin -> แดชบอร์ดผู้ดูแลระบบ
     * - Technician -> หน้ารายการงานของช่าง
     * - User -> หน้ารายการแจ้งซ่อมของตนเอง
     *
     * @param string $role
     * @param string|null $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectByRole(string $role, string $message = null)
    {
        // ใช้คำสั่ง match expression ของ PHP 8 ในการเลือกปลายทาง URL ตามค่า Role
        $redirect = match ($role) {
            // หากบทบาทเป็น 'admin' ให้ส่งไปยัง Route 'admin.dashboard'
            'admin' => redirect()->route('admin.dashboard'),
            // หากบทบาทเป็น 'technician' ให้ส่งไปยัง Route 'tech.tasks'
            'technician' => redirect()->route('tech.tasks'),
            // บทบาทอื่นๆ หรือ 'user' ให้ส่งไปยัง Route 'tickets.index'
            default => redirect()->route('tickets.index'),
        };

        // ตรวจสอบว่ามีการส่งข้อความ Flash Message เข้ามาด้วยหรือไม่
        if ($message) {
            // หากมีข้อความ ให้พ่วง Flash Session key 'success' ไปกับ Redirect Response
            $redirect->with('success', $message);
        }

        // ส่งผลลัพธ์การ Redirect กลับไปยังเบราว์เซอร์
        return $redirect;
    }
}
