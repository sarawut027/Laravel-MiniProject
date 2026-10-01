@extends('layouts.app')

@section('title', 'สมัครสมาชิก')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">สมัครสมาชิกใหม่</h1>
            <p class="text-xs text-slate-500 mt-1">สำหรับพนักงาน / ผู้ใช้งานทั่วไปเพื่อแจ้งซ่อมอุปกรณ์</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="นายสมชาย ใจดี">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">อีเมล <span class="text-rose-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="somchai@example.com">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">เบอร์โทรศัพท์ติดต่อ</label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="08X-XXX-XXXX">
            </div>

            <div>
                <label for="department" class="block text-sm font-medium text-slate-700 mb-1">แผนก / สังกัด / ฝ่าย</label>
                <input type="text" name="department" id="department" value="{{ old('department') }}"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="ฝ่ายบุคคลและการเงิน">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน <span class="text-rose-500">*</span></label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="อย่างน้อย 8 ตัวอักษร">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">ยืนยันรหัสผ่าน <span class="text-rose-500">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="กรอกรหัสผ่านซ้ำอีกครั้ง">
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                ยืนยันการสมัครสมาชิก
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-600">
            มีบัญชีผู้ใช้อยู่แล้ว?
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">เข้าสู่ระบบ</a>
        </div>
    </div>
</div>
@endsection
