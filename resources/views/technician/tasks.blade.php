{{-- สืบทอดโครงร่างหลักมาจาก layouts/app.blade.php --}}
@extends('layouts.app')

@section('title', 'งานซ่อมที่ได้รับมอบหมาย')

@section('content')
<div class="space-y-6">

    <!-- ส่วนหัวของหน้างานช่างซ่อม -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">🔧 งานซ่อมที่ได้รับมอบหมาย (My Tasks)</h1>
            <p class="text-sm text-slate-500 mt-1">รายการงานที่ได้รับมอบหมายจากผู้ดูแลระบบ ให้บริการซ่อมและอัปเดตสถานะ</p>
        </div>
    </div>

    <!-- แผงกรองสถานะงานแบบปุ่มด่วน (Quick Status Filter Pills) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form action="{{ route('tech.tasks') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold text-slate-600">กรองสถานะงาน:</span>
            <a href="{{ route('tech.tasks') }}" class="px-3 py-1.5 rounded-lg text-xs font-medium {{ !request('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                ทั้งหมด ({{ \App\Models\RepairRequest::where('assigned_to', auth()->id())->count() }})
            </a>
            <a href="{{ route('tech.tasks', ['status' => 'assigned']) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium {{ request('status') === 'assigned' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}">
                มอบหมายแล้ว
            </a>
            <a href="{{ route('tech.tasks', ['status' => 'in_progress']) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium {{ request('status') === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                กำลังซ่อม
            </a>
            <a href="{{ route('tech.tasks', ['status' => 'waiting_parts']) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium {{ request('status') === 'waiting_parts' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-700 hover:bg-orange-100' }}">
                รออะไหล่
            </a>
            <a href="{{ route('tech.tasks', ['status' => 'completed']) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium {{ request('status') === 'completed' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                ซ่อมเสร็จแล้ว
            </a>
        </form>
    </div>

    <!-- ตารางแสดงรายการงานที่ได้รับมอบหมาย (Tasks Table) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($tasks->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase">
                            <th class="py-3 px-4">รหัสคำขอ</th>
                            <th class="py-3 px-4">หัวข้อปัญหา</th>
                            <th class="py-3 px-4">หมวดหมู่</th>
                            <th class="py-3 px-4">สถานที่</th>
                            <th class="py-3 px-4">ผู้แจ้งเรื่อง</th>
                            <th class="py-3 px-4">ความเร่งด่วน</th>
                            <th class="py-3 px-4">สถานะ</th>
                            <th class="py-3 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- วนลูปแสดงงานของช่าง --}}
                        @foreach($tasks as $task)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-medium text-indigo-600 whitespace-nowrap">
                                    {{ $task->ticket_number }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-900 max-w-xs truncate">
                                    <a href="{{ route('tickets.show', $task) }}" class="hover:text-indigo-600">
                                        {{ $task->title }}
                                    </a>
                                    <div class="text-xs text-slate-400 font-normal mt-0.5">
                                        มอบหมายเมื่อ: {{ $task->assigned_at ? $task->assigned_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $task->category->name }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $task->location }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    <div>{{ $task->user->name }}</div>
                                    @if($task->user->phone)
                                        <div class="text-xs text-slate-400">📞 {{ $task->user->phone }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $task->urgency_badge_class }}">
                                        {{ $task->urgency_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $task->status_badge_class }}">
                                        {{ $task->status_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $task) }}" class="inline-flex items-center px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-medium rounded-lg transition">
                                        เข้าดู & อัปเดตงาน
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- การแบ่งหน้า (Pagination) -->
            <div class="p-4 border-t border-slate-200">
                {{ $tasks->links() }}
            </div>
        @else
            <!-- กรณีไม่มีงานในสถานะที่เลือก -->
            <div class="py-12 text-center">
                <span class="text-4xl">🎉</span>
                <p class="text-slate-600 font-medium mt-3">ไม่มีงานซ่อมค้างในสถานะนี้</p>
                <p class="text-xs text-slate-400 mt-1">เมื่อแอดมินมอบหมายงานให้คุณ รายการจะแสดงขึ้นที่นี่</p>
            </div>
        @endif
    </div>

</div>
@endsection
