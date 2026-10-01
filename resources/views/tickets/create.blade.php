{{-- สืบทอดโครงร่างหลักมาจาก layouts/app.blade.php --}}
@extends('layouts.app')

@section('title', 'สร้างใบแจ้งซ่อมใหม่')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- แถบนำทางย่อย (Breadcrumb) -->
    <nav class="mb-4 text-xs text-slate-500 flex items-center space-x-2">
        <a href="{{ route('tickets.index') }}" class="hover:text-indigo-600">รายการแจ้งซ่อม</a>
        <span>/</span>
        <span class="text-slate-800 font-medium">แจ้งซ่อมใหม่</span>
    </nav>

    <!-- การ์ดฟอร์มแจ้งซ่อม -->
    <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h1 class="text-2xl font-bold text-slate-900">แบบฟอร์มแจ้งซ่อมอุปกรณ์ / สถานที่</h1>
            <p class="text-xs text-slate-500 mt-1">กรุณาระบุรายละเอียดให้ชัดเจนเพื่อให้เจ้าหน้าที่เข้าดำเนินการได้อย่างรวดเร็ว</p>
        </div>

        {{-- 
            ฟอร์มส่งข้อมูลไปยัง tickets.store (POST /tickets)
            - method="POST": เมธอด HTTP สำหรับบันทึกข้อมูลใหม่
            - enctype="multipart/form-data": สำคัญมาก! ต้องใส่เพื่อให้ฟอร์มสามารถอัปโหลดไฟล์รูปภาพได้
            - @csrf: Directive สำหรับสร้าง Token ป้องกัน CSRF Attack
        --}}
        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <!-- 1. เลือกหมวดหมู่งานซ่อม (Category Dropdown) -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">
                    หมวดหมู่งานซ่อม <span class="text-rose-500">*</span>
                </label>
                <select name="category_id" id="category_id" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">-- กรุณาเลือกหมวดหมู่งาน --</option>
                    {{-- วนลูปแสดงหมวดหมู่ที่ส่งมาจาก Controller --}}
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }} ({{ $category->description }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. หัวข้อปัญหา / ชื่ออุปกรณ์ (Title) -->
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">
                    หัวข้อปัญหา / อุปกรณ์ที่ชำรุด <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="เช่น หลอดไฟเพดานกะพริบไม่ติด, ก๊อกน้ำอ่างล้างมือรั่ว">
            </div>

            <!-- 3. สถานที่ / อาคาร / ชั้น / ห้อง (Location) -->
            <div>
                <label for="location" class="block text-sm font-medium text-slate-700 mb-1">
                    สถานที่ / อาคาร / ชั้น / ห้อง <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="location" id="location" value="{{ old('location') }}" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="เช่น อาคาร 2 ชั้น 3 ห้อง 305 โต๊ะริมหน้าต่าง">
            </div>

            <!-- 4. ระดับความเร่งด่วน (Urgency Level) -->
            <div>
                <label for="urgency" class="block text-sm font-medium text-slate-700 mb-1">
                    ระดับความเร่งด่วน <span class="text-rose-500">*</span>
                </label>
                <select name="urgency" id="urgency" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="low" {{ old('urgency') === 'low' ? 'selected' : '' }}>ไม่เร่งด่วน (รอได้ตามคิวปกติ)</option>
                    <option value="normal" {{ old('urgency', 'normal') === 'normal' ? 'selected' : '' }}>ปกติ (ภายใน 1-2 วันทำการ)</option>
                    <option value="high" {{ old('urgency') === 'high' ? 'selected' : '' }}>ด่วน (มีผลกระทบต่อการทำงาน)</option>
                    <option value="urgent" {{ old('urgency') === 'urgent' ? 'selected' : '' }}>ด่วนที่สุด (อันตราย / งานหยุดชะงักทันที)</option>
                </select>
            </div>

            <!-- 5. รายละเอียดของอาการที่พบ (Description) -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 mb-1">
                    รายละเอียดของปัญหาและอาการที่พบ <span class="text-rose-500">*</span>
                </label>
                <textarea name="description" id="description" rows="4" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="ระบุอาการให้ละเอียด เช่น มีเสียงดังผิดปกติ มีน้ำหยด เปิดไม่ติดตั้งแต่เมื่อเช้า">{{ old('description') }}</textarea>
            </div>

            <!-- 6. แนบรูปถ่ายก่อนซ่อม (Image Before Upload) -->
            <div>
                <label for="image_before" class="block text-sm font-medium text-slate-700 mb-1">
                    รูปภาพประกอบอุปกรณ์ที่ชำรุด (ไม่บังคับ แต่ช่วยให้ช่างเตรียมเครื่องมือได้แม่นยำ)
                </label>
                <input type="file" name="image_before" id="image_before" accept="image/jpeg,image/png,image/jpg,image/webp"
                    class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-lg cursor-pointer">
                <p class="text-xs text-slate-400 mt-1">รองรับไฟล์ JPG, PNG, WEBP ขนาดไม่เกิน 5MB</p>
            </div>

            <!-- ปุ่มกดยกเลิกและยืนยันการส่งข้อมูล -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('tickets.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
                    ส่งเรื่องแจ้งซ่อม
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
