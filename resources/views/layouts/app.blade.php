<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Token สำหรับป้องกันการโจมตีแบบ CSRF (Cross-Site Request Forgery) ในฟอร์มและ AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'ระบบแจ้งซ่อมบำรุง') - Maintenance Repair System</title>
    
    <!-- Google Fonts (Prompt & Inter) สำหรับภาษาไทยที่อ่านง่ายและสวยงาม -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- โหลด Tailwind CSS CDN สำหรับตกแต่งสไตล์ UI -->>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    {{-- ======================================================== --}}
    {{-- ส่วนแถบเมนูด้านบน (Top Navigation Bar) --}}
    {{-- แสดงเมนูแตกต่างกันตามบทบาทของผู้ใช้งานที่ล็อกอิน (Role-Based Menu) --}}
    {{-- ======================================================== --}}
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- โลโก้และชื่อระบบ -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2">
                        <span class="text-2xl">🛠️</span>
                        <div>
                            <span class="font-bold text-lg text-slate-900 tracking-tight">ระบบแจ้งซ่อม</span>
                            <span class="hidden sm:inline-block text-xs text-slate-500 ml-1">Repair System</span>
                        </div>
                    </a>

                    <!-- เมนูนำทางตามสิทธิ์ผู้ใช้งาน (Auth Role Navigation) -->
                    @auth
                        <div class="hidden md:flex items-center space-x-1 ml-6">
                            {{-- 1. เมนูสำหรับผู้ใช้งานทั่วไป (User) --}}
                            @if(auth()->user()->role === 'user')
                                <a href="{{ route('tickets.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('tickets.index') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    📋 รายการแจ้งซ่อมของฉัน
                                </a>
                                <a href="{{ route('tickets.create') }}" class="px-3 py-2 rounded-md text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                                    ➕ แจ้งซ่อมใหม่
                                </a>
                            {{-- 2. เมนูสำหรับช่างซ่อม (Technician) --}}
                            @elseif(auth()->user()->role === 'technician')
                                <a href="{{ route('tech.tasks') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('tech.tasks') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    🔧 งานที่ได้รับมอบหมาย (My Tasks)
                                </a>
                            {{-- 3. เมนูสำหรับผู้ดูแลระบบ (Admin) --}}
                            @elseif(auth()->user()->role === 'admin')
                                <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    📊 แดชบอร์ดสรุปสถิติ
                                </a>
                                <a href="{{ route('admin.tickets.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.tickets.index') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    📑 จัดการงานซ่อมทั้งหมด
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>

                <!-- เมนูด้านขวา: ป้ายแสดงบทบาท, ข้อมูลผู้ใช้, และปุ่มล็อกเอาต์ -->
                <div class="flex items-center space-x-3">
                    @auth
                        <div class="flex items-center space-x-2">
                            <!-- ป้ายแสดงบทบาท (Role Badge) พร้อมสีประจำบทบาท -->
                            @if(auth()->user()->role === 'admin')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                    👑 ผู้ดูแลระบบ
                                </span>
                            @elseif(auth()->user()->role === 'technician')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                    🔧 ช่างซ่อม
                                </span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                    👤 ผู้ใช้งานทั่วไป
                                </span>
                            @endif

                            <span class="text-sm font-medium text-slate-700 hidden sm:inline-block">
                                {{ auth()->user()->name }}
                            </span>
                        </div>

                        <!-- ฟอร์มออกจากระบบ (Logout Form ป้องกัน CSRF) -->
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-md border border-slate-200 transition">
                                ออกจากระบบ
                            </button>
                        </form>
                    @else
                        <!-- เมนูกรณีผู้เยี่ยมชมยังไม่ได้ล็อกอิน -->
                        <a href="{{ route('login') }}" class="px-3 py-1.5 text-sm font-medium text-slate-700 hover:text-indigo-600">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm">
                            สมัครสมาชิก
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- ======================================================== --}}
    {{-- ส่วนแสดงผลเนื้อหาหลัก (Main Content Container) --}}
    {{-- ======================================================== --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- แสดงข้อความแจ้งเตือนความสำเร็จ (Success Flash Alert) --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 p-4 text-emerald-800 border border-emerald-200 shadow-sm flex items-start space-x-3">
                <span class="text-emerald-500 text-xl font-bold">✓</span>
                <div class="flex-1 text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        {{-- แสดงข้อความแจ้งเตือนข้อผิดพลาด (Error Flash Alert) --}}
        @if (session('error'))
            <div class="mb-6 rounded-lg bg-rose-50 p-4 text-rose-800 border border-rose-200 shadow-sm flex items-start space-x-3">
                <span class="text-rose-500 text-xl font-bold">✕</span>
                <div class="flex-1 text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        {{-- แสดงรายการ Error จากการตรวจสอบฟอร์ม (Validation Error List) --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-rose-50 p-4 text-rose-800 border border-rose-200 shadow-sm">
                <div class="font-semibold text-sm mb-1">เกิดข้อผิดพลาด กรุณาตรวจสอบข้อมูล:</div>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- จุดแทรกเนื้อหาของแต่ละหน้า View --}}
        @yield('content')
    </main>

    <!-- ส่วนท้ายหน้าเว็บ (Footer) -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <p>ระบบแจ้งซ่อมบำรุง (Maintenance Repair System) • Laravel 12 Backend & Blade Architecture</p>
    </footer>

    @stack('scripts')
</body>
</html>
