@extends('layouts.app')

@section('title', 'จัดการงานซ่อมทั้งหมด (Admin)')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">📑 จัดการงานแจ้งซ่อมทั้งหมด</h1>
            <p class="text-sm text-slate-500 mt-1">ตรวจสอบ มอบหมายงานให้ช่าง และติดตามสถานะงานซ่อมทั้งหมดในระบบ</p>
        </div>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition">
                ← กลับหน้าแดชบอร์ด
            </a>
        </div>
    </div>

    <!-- Advanced Filter & Search Box -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <form action="{{ route('admin.tickets.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <!-- Search Text -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-xs font-medium text-slate-600 mb-1">ค้นหา (รหัส, หัวข้อ, สถานที่, ผู้แจ้ง)</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="พิมพ์คำค้นหา...">
            </div>

            <!-- Filter Status -->
            <div>
                <label for="status" class="block text-xs font-medium text-slate-600 mb-1">สถานะงาน</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>มอบหมายช่างแล้ว</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="waiting_parts" {{ request('status') === 'waiting_parts' ? 'selected' : '' }}>รออะไหล่</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>ซ่อมเสร็จสิ้น</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>ปิดงานเรียบร้อย</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิกคำขอ</option>
                </select>
            </div>

            <!-- Filter Category -->
            <div>
                <label for="category_id" class="block text-xs font-medium text-slate-600 mb-1">หมวดหมู่งาน</label>
                <select name="category_id" id="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- ทุกหมวดหมู่ --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Technician -->
            <div>
                <label for="assigned_to" class="block text-xs font-medium text-slate-600 mb-1">ช่างผู้รับผิดชอบ</label>
                <select name="assigned_to" id="assigned_to" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- ช่างทุกคน --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ request('assigned_to') == $tech->id ? 'selected' : '' }}>
                            {{ $tech->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Button -->
            <div class="lg:col-span-5 flex justify-end space-x-2 pt-2 border-t border-slate-100">
                @if(request()->hasAny(['search', 'status', 'category_id', 'urgency', 'assigned_to']))
                    <a href="{{ route('admin.tickets.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition">
                        ล้างตัวกรอง
                    </a>
                @endif
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition shadow-sm">
                    🔍 ค้นหาข้อมูล
                </button>
            </div>

        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($tickets->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                            <th class="py-3 px-4">รหัสคำขอ</th>
                            <th class="py-3 px-4">หัวข้อปัญหา</th>
                            <th class="py-3 px-4">หมวดหมู่</th>
                            <th class="py-3 px-4">สถานที่</th>
                            <th class="py-3 px-4">ผู้แจ้ง</th>
                            <th class="py-3 px-4">ความเร่งด่วน</th>
                            <th class="py-3 px-4">ช่างผู้รับผิดชอบ</th>
                            <th class="py-3 px-4">สถานะ</th>
                            <th class="py-3 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-medium text-indigo-600 whitespace-nowrap">
                                    {{ $ticket->ticket_number }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-900 max-w-xs truncate">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="hover:text-indigo-600">
                                        {{ $ticket->title }}
                                    </a>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        แจ้งเมื่อ {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $ticket->category->name }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $ticket->location }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    <div>{{ $ticket->user->name }}</div>
                                    @if($ticket->user->phone)
                                        <div class="text-[10px] text-slate-400">📞 {{ $ticket->user->phone }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full {{ $ticket->urgency_badge_class }}">
                                        {{ $ticket->urgency_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($ticket->technician)
                                        <span class="text-slate-800 font-medium">🔧 {{ $ticket->technician->name }}</span>
                                    @else
                                        <span class="text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded">⏳ ยังไม่มอบหมาย</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full border font-semibold text-[10px] {{ $ticket->status_badge_class }}">
                                        {{ $ticket->status_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap space-x-1">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded font-medium transition">
                                        ดู / สั่งการ
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-slate-200">
                {{ $tickets->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <span class="text-4xl">🔍</span>
                <p class="text-slate-600 font-medium mt-3">ไม่พบรายการงานซ่อมตามเงื่อนไขที่ค้นหา</p>
                <div class="mt-3">
                    <a href="{{ route('admin.tickets.index') }}" class="text-xs text-indigo-600 hover:underline">
                        ล้างตัวกรองทั้งหมด
                    </a>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
