{{-- สืบทอดโครงร่างหลักมาจาก layouts/app.blade.php --}}
@extends('layouts.app')

@section('title', 'รายการแจ้งซ่อมของฉัน')

@section('content')
<div class="space-y-6">

    <!-- ส่วนหัวและปุ่มสร้างงานใหม่ -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">รายการแจ้งซ่อมของฉัน</h1>
            <p class="text-sm text-slate-500 mt-1">ติดตามสถานะงานซ่อม ประวัติการแจ้ง และประเมินความพึงพอใจ</p>
        </div>
        <div>
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                <span class="mr-1.5 text-lg">➕</span> แจ้งซ่อมใหม่
            </a>
        </div>
    </div>

    <!-- แผงค้นหาและตัวกรอง (Filter Box) ส่งค่าผ่าน GET Method -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form action="{{ route('tickets.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- ตัวกรองตามสถานะงาน --}}
            <div>
                <label for="status" class="block text-xs font-medium text-slate-600 mb-1">กรองตามสถานะ</label>
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

            {{-- ตัวกรองตามหมวดหมู่งาน --}}
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

            {{-- ปุ่มกดค้นหาและปุ่มล้างค่าตัวกรอง --}}
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-medium transition">
                    ค้นหา
                </button>
                @if(request()->hasAny(['status', 'category_id']))
                    <a href="{{ route('tickets.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition">
                        ล้างค่า
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- ตารางแสดงรายการคำขอแจ้งซ่อม (Tickets Table) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($tickets->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase">
                            <th class="py-3 px-4">รหัสคำขอ</th>
                            <th class="py-3 px-4">หัวข้อปัญหา</th>
                            <th class="py-3 px-4">หมวดหมู่</th>
                            <th class="py-3 px-4">สถานที่</th>
                            <th class="py-3 px-4">ความเร่งด่วน</th>
                            <th class="py-3 px-4">สถานะ</th>
                            <th class="py-3 px-4">ช่างผู้รับผิดชอบ</th>
                            <th class="py-3 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- วนลูปแสดงข้อมูลคำขอแจ้งซ่อมแต่ละรายการ --}}
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-medium text-indigo-600 whitespace-nowrap">
                                    {{ $ticket->ticket_number }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-900 max-w-xs truncate">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="hover:text-indigo-600">
                                        {{ $ticket->title }}
                                    </a>
                                    <div class="text-xs text-slate-400 font-normal mt-0.5">
                                        แจ้งเมื่อ {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $ticket->category->name }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $ticket->location }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $ticket->urgency_badge_class }}">
                                        {{ $ticket->urgency_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $ticket->status_badge_class }}">
                                        {{ $ticket->status_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $ticket->technician ? $ticket->technician->name : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap space-x-2">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-md transition">
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- การแบ่งหน้า (Pagination Links) -->
            <div class="p-4 border-t border-slate-200">
                {{ $tickets->links() }}
            </div>
        @else
            <!-- กรณีไม่มีข้อมูลแจ้งซ่อม -->
            <div class="py-12 text-center">
                <span class="text-4xl">📭</span>
                <p class="text-slate-600 font-medium mt-3">ยังไม่มีรายการแจ้งซ่อม</p>
                <p class="text-xs text-slate-400 mt-1">คุณสามารถสร้างรายการแจ้งซ่อมใหม่ได้ทันที</p>
                <div class="mt-4">
                    <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg shadow-sm transition">
                        สร้างใบแจ้งซ่อมใหม่
                    </a>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
