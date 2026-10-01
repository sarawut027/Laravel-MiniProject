@extends('layouts.app')

@section('title', 'รายละเอียดใบแจ้งซ่อม #' . $ticket->ticket_number)

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Top Navigation & Status Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center space-x-3">
            <a href="{{ auth()->user()->role === 'admin' ? route('admin.tickets.index') : (auth()->user()->role === 'technician' ? route('tech.tasks') : route('tickets.index')) }}"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition">
                ← กลับหน้ารายการ
            </a>
            <div>
                <span class="font-mono text-xs text-slate-500">รหัสคำขอ:</span>
                <span class="font-mono font-bold text-sm text-indigo-700 ml-1">{{ $ticket->ticket_number }}</span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 text-xs rounded-full {{ $ticket->urgency_badge_class }}">
                ความเร่งด่วน: {{ $ticket->urgency_label }}
            </span>
            <span class="px-3 py-1 text-xs font-bold rounded-full border {{ $ticket->status_badge_class }}">
                สถานะ: {{ $ticket->status_label }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Details & Images (2 cols) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card 1: Ticket Info -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <h1 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-3">
                    {{ $ticket->title }}
                </h1>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">หมวดหมู่งานซ่อม</span>
                        <span class="text-slate-800 font-medium text-sm">{{ $ticket->category->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">สถานที่เกิดเหตุ</span>
                        <span class="text-slate-800 font-medium text-sm">{{ $ticket->location }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">ผู้แจ้งเรื่อง</span>
                        <span class="text-slate-800 font-medium">{{ $ticket->user->name }}</span>
                        @if($ticket->user->phone)
                            <span class="text-slate-500 block">📞 {{ $ticket->user->phone }}</span>
                        @endif
                        @if($ticket->user->department)
                            <span class="text-slate-500 block">🏢 {{ $ticket->user->department }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">ช่างผู้รับผิดชอบ</span>
                        @if($ticket->technician)
                            <span class="text-indigo-700 font-medium">🔧 {{ $ticket->technician->name }}</span>
                            @if($ticket->technician->phone)
                                <span class="text-slate-500 block">📞 {{ $ticket->technician->phone }}</span>
                            @endif
                        @else
                            <span class="text-amber-600 font-medium">⏳ ยังไม่ได้มอบหมายช่าง</span>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-slate-400 text-xs block mb-1">รายละเอียดปัญหาและอาการ:</span>
                    <div class="bg-slate-50 p-4 rounded-lg text-sm text-slate-700 whitespace-pre-line border border-slate-100">
                        {{ $ticket->description }}
                    </div>
                </div>
            </div>

            <!-- Card 2: Before & After Images -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">
                    📷 รูปภาพประกอบงานซ่อม (Before & After)
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Image Before -->
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50 text-center">
                        <span class="text-xs font-semibold text-slate-600 block mb-2">📸 รูปภาพก่อนซ่อม (ชำรุด)</span>
                        @if($ticket->image_before)
                            <a href="{{ asset('storage/' . $ticket->image_before) }}" target="_blank" class="block">
                                <img src="{{ asset('storage/' . $ticket->image_before) }}" alt="Before Repair"
                                    class="rounded-lg max-h-52 w-full object-cover mx-auto hover:opacity-90 transition border border-slate-200">
                            </a>
                            <span class="text-[10px] text-slate-400 mt-1 block">คลิกที่รูปเพื่อดูขนาดใหญ่</span>
                        @else
                            <div class="h-40 flex items-center justify-center text-xs text-slate-400">
                                ไม่มีรูปภาพก่อนซ่อม
                            </div>
                        @endif
                    </div>

                    <!-- Image After -->
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50 text-center">
                        <span class="text-xs font-semibold text-emerald-700 block mb-2">✨ รูปภาพหลังซ่อมเสร็จสิ้น</span>
                        @if($ticket->image_after)
                            <a href="{{ asset('storage/' . $ticket->image_after) }}" target="_blank" class="block">
                                <img src="{{ asset('storage/' . $ticket->image_after) }}" alt="After Repair"
                                    class="rounded-lg max-h-52 w-full object-cover mx-auto hover:opacity-90 transition border border-slate-200">
                            </a>
                            <span class="text-[10px] text-slate-400 mt-1 block">คลิกที่รูปเพื่อดูขนาดใหญ่</span>
                        @else
                            <div class="h-40 flex items-center justify-center text-xs text-slate-400">
                                ยังไม่มีรูปภาพหลังซ่อม
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Technician Notes & Cost if completed -->
                @if($ticket->technician_notes || $ticket->repair_cost > 0)
                    <div class="mt-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-xs">
                        <span class="font-bold text-emerald-900 block mb-1">🛠️ ข้อมูลบันทึกจากช่างซ่อม:</span>
                        @if($ticket->technician_notes)
                            <p class="text-emerald-800 mb-1">{{ $ticket->technician_notes }}</p>
                        @endif
                        @if($ticket->repair_cost > 0)
                            <p class="font-semibold text-emerald-900">
                                ค่าใช้จ่าย/อะไหล่: {{ number_format($ticket->repair_cost, 2) }} บาท
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Card 3: Review Section -->
            @if($ticket->review)
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-3 flex items-center justify-between">
                        <span>⭐ ผลการประเมินความพึงพอใจ</span>
                        <span class="text-xs font-normal text-slate-400">ประเมินเมื่อ {{ $ticket->review->created_at->format('d/m/Y H:i') }}</span>
                    </h2>
                    <div class="flex items-center space-x-2 text-amber-400 text-xl mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= $ticket->review->rating ? '★' : '☆' }}</span>
                        @endfor
                        <span class="text-sm font-bold text-slate-700 ml-2">({{ $ticket->review->rating }} / 5 ดาว)</span>
                    </div>
                    @if($ticket->review->comment)
                        <div class="bg-slate-50 p-3 rounded-lg text-xs text-slate-700 border border-slate-100 italic">
                            "{{ $ticket->review->comment }}"
                        </div>
                    @endif
                </div>
            @endif

        </div>

        <!-- Right Column: Actions & Timeline (1 col) -->
        <div class="space-y-6">

            <!-- ACTION PANEL (Based on Role & Status) -->

            <!-- 1. User Cancel Action (Only when pending) -->
            @if(auth()->user()->role === 'user' && $ticket->user_id === auth()->id() && $ticket->status === 'pending')
                <div class="bg-white p-5 rounded-xl border border-rose-200 shadow-sm">
                    <h3 class="text-sm font-bold text-rose-800 mb-2">ขอยกเลิกคำขอแจ้งซ่อม</h3>
                    <p class="text-xs text-slate-500 mb-3">คุณสามารถยกเลิกได้หากต้องการแจ้งใหม่ หรืออาการหายเป็นปกติแล้ว</p>
                    <form action="{{ route('tickets.cancel', $ticket) }}" method="POST" onsubmit="return confirm('ยืนยันที่จะยกเลิกคำขอแจ้งซ่อมนี้ใช่หรือไม่?');">
                        @csrf
                        <button type="submit" class="w-full py-2 px-3 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-medium transition">
                            ขอยกเลิกคำขอนี้
                        </button>
                    </form>
                </div>
            @endif

            <!-- 2. User Review Action (When completed and no review yet) -->
            @if(auth()->user()->role === 'user' && $ticket->user_id === auth()->id() && $ticket->status === 'completed' && !$ticket->review)
                <div class="bg-white p-5 rounded-xl border border-amber-200 shadow-sm">
                    <h3 class="text-sm font-bold text-amber-900 mb-2">⭐ ประเมินความพึงพอใจและปิดงาน</h3>
                    <p class="text-xs text-slate-600 mb-3">ช่างซ่อมดำเนินการเสร็จสิ้นแล้ว กรุณาตรวจรับงานและให้คะแนน</p>
                    
                    <form action="{{ route('tickets.review', $ticket) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">คะแนนดาว (1 - 5) <span class="text-rose-500">*</span></label>
                            <div class="flex items-center space-x-3 text-sm">
                                @for($star = 5; $star >= 1; $star--)
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $star }}" {{ $star == 5 ? 'checked' : '' }} class="text-amber-500 focus:ring-amber-400">
                                        <span>{{ $star }}★</span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <div>
                            <label for="review_comment" class="block text-xs font-medium text-slate-700 mb-1">ความคิดเห็นเพิ่มเติม</label>
                            <textarea name="comment" id="review_comment" rows="2" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="เช่น ซ่อมเรียบร้อย ช่างบริการดี"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2 px-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition shadow-sm">
                            บันทึกการประเมิน & ตรวจรับงาน
                        </button>
                    </form>
                </div>
            @endif

            <!-- 3. Admin Assign Action -->
            @if(auth()->user()->role === 'admin')
                <div class="bg-white p-5 rounded-xl border border-indigo-200 shadow-sm">
                    <h3 class="text-sm font-bold text-indigo-900 mb-2">👑 มอบหมายงานให้ช่าง (Admin)</h3>
                    <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label for="assigned_to" class="block text-xs font-medium text-slate-700 mb-1">เลือกช่างซ่อม <span class="text-rose-500">*</span></label>
                            <select name="assigned_to" id="assigned_to" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="">-- กรุณาเลือกช่าง --</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ $ticket->assigned_to == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }} ({{ $tech->phone ?? 'ไม่มีเบอร์' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assign_comment" class="block text-xs font-medium text-slate-700 mb-1">หมายเหตุสั่งการ (ไม่บังคับ)</label>
                            <input type="text" name="comment" id="assign_comment" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="เช่น ด่วนมาก เข้าบ่ายนี้">
                        </div>

                        <button type="submit" class="w-full py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                            {{ $ticket->assigned_to ? 'เปลี่ยนช่างผู้รับผิดชอบ' : 'ยืนยันมอบหมายงาน' }}
                        </button>
                    </form>
                </div>
            @endif

            <!-- 4. Technician Status Update Action -->
            @if((auth()->user()->role === 'technician' && $ticket->assigned_to === auth()->id()) || auth()->user()->role === 'admin')
                <div class="bg-white p-5 rounded-xl border border-blue-200 shadow-sm">
                    <h3 class="text-sm font-bold text-blue-900 mb-2">🔧 อัปเดตสถานะงานซ่อม</h3>
                    <form action="{{ route('tech.updateStatus', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label for="update_status" class="block text-xs font-medium text-slate-700 mb-1">เปลี่ยนสถานะเป็น <span class="text-rose-500">*</span></label>
                            <select name="status" id="update_status" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ (In Progress)</option>
                                <option value="waiting_parts" {{ $ticket->status === 'waiting_parts' ? 'selected' : '' }}>รออะไหล่ (Waiting for Parts)</option>
                                <option value="completed" {{ $ticket->status === 'completed' ? 'selected' : '' }}>ซ่อมเสร็จสิ้น (Completed)</option>
                            </select>
                        </div>

                        <div>
                            <label for="technician_notes" class="block text-xs font-medium text-slate-700 mb-1">บันทึกงาน / อะไหล่ที่เปลี่ยน</label>
                            <textarea name="technician_notes" id="technician_notes" rows="2" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="บันทึกสิ่งที่ตรวจพบ หรือวิธีซ่อม">{{ $ticket->technician_notes }}</textarea>
                        </div>

                        <div>
                            <label for="repair_cost" class="block text-xs font-medium text-slate-700 mb-1">ค่าใช้จ่าย/ค่าอะไหล่ (บาท)</label>
                            <input type="number" step="0.01" min="0" name="repair_cost" id="repair_cost" value="{{ $ticket->repair_cost }}" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="0.00">
                        </div>

                        <div>
                            <label for="image_after" class="block text-xs font-medium text-slate-700 mb-1">รูปถ่ายหลังซ่อมเสร็จ (กรณีปิดงาน)</label>
                            <input type="file" name="image_after" id="image_after" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-blue-50 file:text-blue-700 border border-slate-200 rounded-lg">
                        </div>

                        <button type="submit" class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                            บันทึกอัปเดตสถานะ
                        </button>
                    </form>
                </div>
            @endif

            <!-- Card: Timeline / Audit Logs -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">
                    ⏱️ ประวัติการดำเนินการ (Timeline)
                </h3>

                <div class="relative border-l-2 border-slate-200 ml-3 space-y-5 text-xs">
                    @forelse($ticket->logs as $log)
                        <div class="relative pl-5">
                            <!-- Bullet Icon -->
                            <div class="absolute -left-[9px] top-0.5 w-4 h-4 rounded-full bg-white border-2 border-indigo-600"></div>

                            <div>
                                <span class="font-semibold text-slate-800">
                                    {{ $log->user ? $log->user->name : 'ระบบอัตโนมัติ' }}
                                </span>
                                <span class="text-slate-400 text-[10px] block">
                                    {{ $log->created_at->format('d/m/Y H:i น.') }}
                                </span>
                            </div>

                            @if($log->status_to)
                                <div class="mt-1">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        สถานะ: {{ $log->status_to }}
                                    </span>
                                </div>
                            @endif

                            @if($log->comment)
                                <div class="mt-1 text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                                    {{ $log->comment }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-slate-400 text-xs pl-4">ยังไม่มีประวัติการบันทึก</div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
