<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

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
        // 1. ตรวจสอบความถูกต้องของฟิลด์ที่ส่งมา
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        // 2. ตรวจสอบสิทธิ์การเข้าใช้งานผ่าน Auth::attempt
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // สร้าง Session ID ใหม่เพื่อความปลอดภัย (Session Fixation Protection)
            $request->session()->regenerate();
            $role = Auth::user()->role;
            
            // ส่งไปยังหน้าหลักของ Role นั้นๆ
            return $this->redirectByRole($role, 'เข้าสู่ระบบสำเร็จ');
        }

        // หากอีเมลหรือรหัสผ่านไม่ถูกต้อง ให้ส่ง Error กลับไปที่ฟอร์ม
        return back()->withErrors([
            'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง',
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
        Auth::logout();

        // ทำลายข้อมูลในเซสชันเดิมและสร้าง Token ใหม่
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }

    /**
     * แสดงหน้าฟอร์มสมัครสมาชิกใหม่ (Register Page)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

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
        // 1. ตรวจสอบความถูกต้องของข้อมูล
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.unique' => 'อีเมลนี้ถูกใช้งานในระบบแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        // 2. สร้าง User ใหม่ในฐานข้อมูล พร้อมเข้ารหัสผ่านด้วย Hash::make
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user', // สิทธิ์เริ่มต้นคือ user เสมอ
            'phone' => $validated['phone'] ?? null,
            'department' => $validated['department'] ?? null,
        ]);

        // 3. ทำการล็อกอินให้อัตโนมัติ
        Auth::login($user);
        $request->session()->regenerate();

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
        $redirect = match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'technician' => redirect()->route('tech.tasks'),
            default => redirect()->route('tickets.index'),
        };

        if ($message) {
            $redirect->with('success', $message);
        }

        return $redirect;
    }
}
