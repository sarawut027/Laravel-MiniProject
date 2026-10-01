<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $role = Auth::user()->role;
            return $this->redirectByRole($role, 'เข้าสู่ระบบสำเร็จ');
        }

        return back()->withErrors([
            'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'phone' => $validated['phone'] ?? null,
            'department' => $validated['department'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('tickets.index')->with('success', 'สมัครสมาชิกและเข้าสู่ระบบสำเร็จ ยินดีต้อนรับ!');
    }

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
