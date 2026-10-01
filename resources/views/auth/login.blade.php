@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">เข้าสู่ระบบ</h1>
            <p class="text-xs text-slate-500 mt-1">ระบบแจ้งซ่อมและบำรุงรักษาอุปกรณ์</p>
        </div>

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">อีเมล (Email)</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="name@example.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน (Password)</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 text-xs text-slate-600">จดจำฉันในระบบ</span>
                </label>
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                เข้าสู่ระบบ
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-600">
            ยังไม่มีบัญชีผู้ใช้?
            <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">สมัครสมาชิกที่นี่</a>
        </div>
    </div>

    <!-- Quick Demo Accounts Helper Box -->
    <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-900 shadow-sm">
        <div class="font-bold mb-2 flex items-center">
            <span>🔑 บัญชีทดสอบระบบ (ตามสัญญาข้อตกลง):</span>
        </div>
        <p class="mb-2 text-amber-800">รหัสผ่านทุกบัญชีคือ: <code class="bg-amber-100 px-1.5 py-0.5 rounded font-mono font-bold">password</code></p>
        <div class="grid grid-cols-1 gap-1.5 text-xs">
            <button type="button" onclick="fillLogin('admin@repair.test')" class="text-left py-1 px-2 rounded bg-white hover:bg-amber-100 border border-amber-200 transition">
                👑 <strong>Admin:</strong> admin@repair.test
            </button>
            <button type="button" onclick="fillLogin('tech@repair.test')" class="text-left py-1 px-2 rounded bg-white hover:bg-amber-100 border border-amber-200 transition">
                🔧 <strong>Technician:</strong> tech@repair.test
            </button>
            <button type="button" onclick="fillLogin('user@repair.test')" class="text-left py-1 px-2 rounded bg-white hover:bg-amber-100 border border-amber-200 transition">
                👤 <strong>User:</strong> user@repair.test
            </button>
        </div>
    </div>
</div>

<script>
function fillLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password';
}
</script>
@endsection
