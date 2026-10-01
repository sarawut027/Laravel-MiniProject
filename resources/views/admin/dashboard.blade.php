{{-- สืบทอดโครงร่างหลักมาจาก layouts/app.blade.php --}}
@extends('layouts.app')

@section('title', 'แดชบอร์ดสรุปสถิติ (Admin Dashboard)')

@section('content')
<div class="space-y-6">

    <!-- ส่วนหัวแดชบอร์ด -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">📊 แดชบอร์ดสรุปสถิติงานซ่อมบำรุง</h1>
            <p class="text-sm text-slate-500 mt-1">ภาพรวมของงานแจ้งซ่อม สถานะปัจจุบัน และความพึงพอใจของผู้ใช้งาน</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
                📑 จัดการงานซ่อมทั้งหมด
            </a>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- การ์ดสรุปตัวเลขสถิติ 5 ตัวชี้วัด (5 KPI Stat Cards) --}}
    {{-- ======================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- การ์ด 1: งานทั้งหมด (Total Tickets) -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 block uppercase">งานทั้งหมด</span>
                <span class="text-2xl font-bold text-slate-900 mt-1 block">{{ $stats['total'] }}</span>
                <span class="text-[10px] text-slate-400">รายการแจ้งทั้งหมด</span>
            </div>
            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-2xl">
                📑
            </div>
        </div>

        <!-- การ์ด 2: รอดำเนินการ (Pending) -->
        <div class="bg-white p-5 rounded-xl border border-yellow-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-yellow-700 block uppercase">รอดำเนินการ</span>
                <span class="text-2xl font-bold text-yellow-600 mt-1 block">{{ $stats['pending'] }}</span>
                <span class="text-[10px] text-yellow-600">รอมอบหมายช่าง</span>
            </div>
            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center text-2xl">
                ⏳
            </div>
        </div>

        <!-- การ์ด 3: กำลังดำเนินการ (In Progress) -->
        <div class="bg-white p-5 rounded-xl border border-blue-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-blue-700 block uppercase">กำลังดำเนินการ</span>
                <span class="text-2xl font-bold text-blue-600 mt-1 block">{{ $stats['in_progress'] }}</span>
                <span class="text-[10px] text-blue-500">ช่างกำลังเข้าซ่อม</span>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-2xl">
                🔧
            </div>
        </div>

        <!-- การ์ด 4: ซ่อมเสร็จสิ้น (Completed) -->
        <div class="bg-white p-5 rounded-xl border border-emerald-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-emerald-700 block uppercase">ซ่อมเสร็จสิ้น</span>
                <span class="text-2xl font-bold text-emerald-600 mt-1 block">{{ $stats['completed'] }}</span>
                <span class="text-[10px] text-emerald-500">ปิดงานเรียบร้อย</span>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-2xl">
                ✅
            </div>
        </div>

        <!-- การ์ด 5: คะแนนความพึงพอใจเฉลี่ย (Average Rating) -->
        <div class="bg-white p-5 rounded-xl border border-amber-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-amber-700 block uppercase">คะแนนเฉลี่ย</span>
                <div class="flex items-baseline mt-1 space-x-1">
                    <span class="text-2xl font-bold text-amber-500">{{ $stats['average_rating'] }}</span>
                    <span class="text-xs text-slate-400">/ 5.0</span>
                </div>
                <span class="text-[10px] text-amber-600">ความพึงพอใจ</span>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-2xl">
                ⭐
            </div>
        </div>

    </div>

    {{-- ======================================================== --}}
    {{-- กราฟและการกระจายตัวของข้อมูล (Charts & Breakdown) --}}
    {{-- ======================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ชาร์ต 1: สถิติตามหมวดหมู่ (CSS Progress Bar) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">
                📁 สถิติงานซ่อมแยกตามหมวดหมู่
            </h2>
            <div class="space-y-3">
                @foreach($chartData['category_labels'] as $idx => $label)
                    @php
                        $count = $chartData['category_counts'][$idx] ?? 0;
                        $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700">{{ $label }}</span>
                            <span class="text-slate-500">{{ $count }} รายการ ({{ $percent }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ชาร์ต 2: สัดส่วนสถานะงานในระบบ (Status Breakdown Cards) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">
                🔄 สัดส่วนสถานะงานในระบบ
            </h2>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-lg bg-yellow-50 border border-yellow-200">
                    <span class="text-yellow-700 block font-medium">รอดำเนินการ</span>
                    <span class="text-xl font-bold text-yellow-800">{{ $chartData['status_counts']['pending'] }}</span>
                </div>
                <div class="p-3 rounded-lg bg-indigo-50 border border-indigo-200">
                    <span class="text-indigo-700 block font-medium">มอบหมายแล้ว</span>
                    <span class="text-xl font-bold text-indigo-800">{{ $chartData['status_counts']['assigned'] }}</span>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 border border-blue-200">
                    <span class="text-blue-700 block font-medium">กำลังดำเนินการ</span>
                    <span class="text-xl font-bold text-blue-800">{{ $chartData['status_counts']['in_progress'] }}</span>
                </div>
                <div class="p-3 rounded-lg bg-orange-50 border border-orange-200">
                    <span class="text-orange-700 block font-medium">รออะไหล่</span>
                    <span class="text-xl font-bold text-orange-800">{{ $chartData['status_counts']['waiting_parts'] }}</span>
                </div>
                <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                    <span class="text-emerald-700 block font-medium">ซ่อมเสร็จสิ้น</span>
                    <span class="text-xl font-bold text-emerald-800">{{ $chartData['status_counts']['completed'] }}</span>
                </div>
                <div class="p-3 rounded-lg bg-green-50 border border-green-200">
                    <span class="text-green-700 block font-medium">ปิดงาน (ประเมินแล้ว)</span>
                    <span class="text-xl font-bold text-green-800">{{ $chartData['status_counts']['closed'] }}</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ======================================================== --}}
    {{-- ตารางงานแจ้งซ่อมล่าสุด 5 รายการ (Recent 5 Tickets) --}}
    {{-- ======================================================== --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">
                🕒 งานแจ้งซ่อมล่าสุด 5 รายการ
            </h2>
            <a href="{{ route('admin.tickets.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                ดูงานทั้งหมด →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <th class="py-3 px-4">รหัสคำขอ</th>
                        <th class="py-3 px-4">หัวข้อปัญหา</th>
                        <th class="py-3 px-4">หมวดหมู่</th>
                        <th class="py-3 px-4">ผู้แจ้ง</th>
                        <th class="py-3 px-4">ช่างผู้ดูแล</th>
                        <th class="py-3 px-4">สถานะ</th>
                        <th class="py-3 px-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {{-- วนลูปแสดงงานล่าสุด 5 รายการ --}}
                    @forelse($recentTickets as $ticket)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-medium text-indigo-600 whitespace-nowrap">
                                {{ $ticket->ticket_number }}
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-900 max-w-xs truncate">
                                <a href="{{ route('tickets.show', $ticket) }}" class="hover:text-indigo-600">
                                    {{ $ticket->title }}
                                </a>
                                <div class="text-[10px] text-slate-400">
                                    {{ $ticket->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                {{ $ticket->category->name }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                {{ $ticket->user->name }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                {{ $ticket->technician ? $ticket->technician->name : '⏳ ยังไม่มอบหมาย' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full border font-semibold text-[10px] {{ $ticket->status_badge_class }}">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('tickets.show', $ticket) }}" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded transition font-medium">
                                    ดู / มอบหมาย
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">ยังไม่มีงานแจ้งซ่อมในระบบ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
