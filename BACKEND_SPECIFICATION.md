# 📑 เอกสารข้อกำหนดระบบ Backend (Backend API & Blade Contract Specification)
> **วัตถุประสงค์:** ใช้เป็นข้อตกลงกลาง (Single Source of Truth) ระหว่าง **คนที่ 1 (Backend)** และ **คนที่ 2 (Frontend)**  
> เพื่อให้ชื่อฟิลด์, ชื่อ Route, ค่าคงที่ของสถานะ (Status), และตัวแปรที่ส่งไปแสดงผลตรงกัน 100% ไม่เกิดข้อผิดพลาดตอนรวมโค้ด

---

## 1. ข้อมูลผู้ใช้ทดสอบในระบบ (Login Credentials for Testing)

ทุกคนใช้รหัสผ่านเดียวกันคือ `password` (เมื่อรัน `php artisan db:seed`):

| บทบาท (Role) | อีเมล (Email) | รหัสผ่าน | หน้าที่ในการทดสอบ |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@repair.test` | `password` | จัดการงานทั้งหมด, มอบหมายงานให้ช่าง, ดู Dashboard สถิติ |
| **Technician** | `tech@repair.test` | `password` | ดูงานที่ได้รับมอบหมาย, อัปเดตสถานะ, แนบรูปหลังซ่อม |
| **User** | `user@repair.test` | `password` | แจ้งซ่อมใหม่, ติดตามสถานะ, ตรวจรับและให้คะแนนดาว |

---

## 2. มาตรฐานค่าคงที่ (Constants, Roles & Statuses)
*(⚠️ สำคัญมาก: Frontend และ Backend ต้องใช้ข้อความภาษาอังกฤษตัวพิมพ์เล็กตรงกันเป๊ะตามตารางนี้)*

### 2.1 สิทธิ์ผู้ใช้งาน (`role`)
- `'admin'`
- `'technician'`
- `'user'`

### 2.2 ระดับความเร่งด่วน (`urgency`)
| ค่าในโค้ด | ป้ายภาษาไทย | สไตล์สีที่แนะนำใน Tailwind |
| :--- | :--- | :--- |
| `'low'` | ไม่เร่งด่วน | `bg-slate-100 text-slate-700` |
| `'normal'` | ปกติ | `bg-blue-100 text-blue-700` |
| `'high'` | ด่วน | `bg-amber-100 text-amber-700` |
| `'urgent'` | ด่วนที่สุด | `bg-rose-100 text-rose-700 font-bold` |

### 2.3 สถานะงานแจ้งซ่อม (`status`)
| ค่าในฐานข้อมูล | ข้อความแสดงผล | สี Tailwind Badge | ใครเป็นคนเปลี่ยนสถานะได้ |
| :--- | :--- | :--- | :--- |
| `'pending'` | รอดำเนินการ | `bg-yellow-100 text-yellow-800` | สถานะเริ่มต้นเมื่อผู้ใช้แจ้งเรื่อง |
| `'assigned'` | มอบหมายช่างแล้ว | `bg-indigo-100 text-indigo-800` | Admin มอบหมายงาน |
| `'in_progress'` | กำลังดำเนินการ | `bg-blue-100 text-blue-800` | ช่างกดรับงาน |
| `'waiting_parts'` | รออะไหล่ | `bg-orange-100 text-orange-800` | ช่างรอของ |
| `'completed'` | ซ่อมเสร็จสิ้น | `bg-emerald-100 text-emerald-800` | ช่างแนบรูปหลังซ่อมและปิดงาน |
| `'closed'` | ปิดงานเรียบร้อย | `bg-green-100 text-green-800` | ผู้ใช้ตรวจรับงานและให้คะแนนดาว |
| `'cancelled'` | ยกเลิกคำขอ | `bg-rose-100 text-rose-800` | ผู้ใช้ยกเลิก (ตอน pending) หรือ Admin ปฏิเสธ |

---

## 3. แผนผังเส้นทาง (Routes & Controllers Contract)

```mermaid
classDiagram
    direction LR
    class AuthRoutes {
        GET /login (login)
        POST /login
        POST /logout (logout)
        GET /register (register)
        POST /register
    }
    class UserRoutes {
        GET /tickets (tickets.index)
        GET /tickets/create (tickets.create)
        POST /tickets (tickets.store)
        GET /tickets/{id} (tickets.show)
        POST /tickets/{id}/review (tickets.review)
        POST /tickets/{id}/cancel (tickets.cancel)
    }
    class TechRoutes {
        GET /technician/tasks (tech.tasks)
        POST /technician/tasks/{id}/status (tech.updateStatus)
    }
    class AdminRoutes {
        GET /admin/dashboard (admin.dashboard)
        GET /admin/tickets (admin.tickets.index)
        POST /admin/tickets/{id}/assign (admin.tickets.assign)
    }
```

### ตาราง Routes ทั้งหมด

| Method | URI Path | Route Name | Controller Action | สิทธิ์ที่เข้าถึงได้ | คำอธิบาย |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **GET** | `/login` | `login` | `AuthController@showLogin` | ทุกคน | แสดงฟอร์มเข้าสู่ระบบ |
| **POST** | `/login` | `login.submit` | `AuthController@login` | ทุกคน | ยืนยันการเข้าสู่ระบบ |
| **POST** | `/logout` | `logout` | `AuthController@logout` | ล็อกอินแล้ว | ออกจากระบบ |
| **GET** | `/tickets` | `tickets.index` | `TicketController@index` | `user` | รายการแจ้งซ่อมของตนเอง |
| **GET** | `/tickets/create` | `tickets.create` | `TicketController@create` | `user` | หน้าฟอร์มสร้างใบแจ้งซ่อมใหม่ |
| **POST** | `/tickets` | `tickets.store` | `TicketController@store` | `user` | บันทึกการแจ้งซ่อม (มีรูปภาพ) |
| **GET** | `/tickets/{ticket}` | `tickets.show` | `TicketController@show` | ทุก Role ที่เกี่ยวข้อง | ดูรายละเอียดงานและ Timeline |
| **POST** | `/tickets/{ticket}/cancel` | `tickets.cancel` | `TicketController@cancel` | `user` | กดยกเลิกงาน (เฉพาะ Pending) |
| **POST** | `/tickets/{ticket}/review` | `tickets.review` | `TicketController@review` | `user` | บันทึกคะแนนประเมินดาว |
| **GET** | `/technician/tasks` | `tech.tasks` | `TechnicianController@index` | `technician` | รายการงานที่ช่างได้รับมอบหมาย |
| **POST** | `/technician/tasks/{ticket}/status` | `tech.updateStatus` | `TechnicianController@updateStatus` | `technician` | อัปเดตสถานะ + บันทึกอะไหล่/รูป |
| **GET** | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | `admin` | แดชบอร์ดสรุปตัวเลขและกราฟ |
| **GET** | `/admin/tickets` | `admin.tickets.index` | `AdminController@tickets` | `admin` | รายการงานทั้งหมดเพื่อค้นหา/กรอง |
| **POST** | `/admin/tickets/{ticket}/assign` | `admin.tickets.assign` | `AdminController@assign` | `admin` | สั่งจ่ายงานให้ช่าง |

---

## 4. ข้อกำหนดชื่อฟิลด์ในฟอร์ม HTML (HTML Form Input Names)
*(Frontend ต้องตั้ง attribute `name="..."` ให้ตรงกับชื่อด้านล่างนี้เป๊ะๆ)*

### 4.1 ฟอร์มแจ้งซ่อมใหม่ (`tickets.create` $\rightarrow$ `POST /tickets`)
⚠️ **ต้องมี:** `<form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">` และ `@csrf`

| ชื่อ Input (`name`) | ชนิด Element | ข้อกำหนด (Validation) | ตัวอย่าง |
| :--- | :--- | :--- | :--- |
| `category_id` | `<select>` | `required\|exists:categories,id` | ตัวเลือกหมวดหมู่งานซ่อม |
| `title` | `<input type="text">` | `required\|string\|max:255` | "หลอดไฟเพดานกะพริบไม่ติด" |
| `location` | `<input type="text">` | `required\|string\|max:255` | "อาคาร 2 ชั้น 3 ห้อง 305" |
| `urgency` | `<select>` | `required\|in:low,normal,high,urgent` | "high" |
| `description` | `<textarea>` | `required\|string` | รายละเอียดอาการที่พบ |
| `image_before` | `<input type="file">` | `nullable\|image\|mimes:jpeg,png,jpg,webp\|max:5120` | รูปภาพอุปกรณ์ที่ชำรุด (ไม่เกิน 5MB) |

---

### 4.2 ฟอร์มช่างอัปเดตสถานะและปิดงาน (`POST /technician/tasks/{ticket}/status`)
⚠️ **ต้องมี:** `enctype="multipart/form-data"` และ `@csrf`

| ชื่อ Input (`name`) | ชนิด Element | ตัวเลือก/ข้อกำหนด | คำอธิบาย |
| :--- | :--- | :--- | :--- |
| `status` | `<select>` | `in:in_progress,waiting_parts,completed` | เลือกสถานะใหม่ |
| `technician_notes`| `<textarea>` | `nullable\|string` | บันทึกสิ่งที่ซ่อม, อะไหล่ที่เปลี่ยน |
| `repair_cost` | `<input type="number" step="0.01">` | `nullable\|numeric\|min:0` | ค่าใช้จ่าย (ถ้ามี) |
| `image_after` | `<input type="file">` | `nullable\|image\|max:5120` | รูปถ่ายหลังซ่อมเสร็จ (กรณี completed) |

---

### 4.3 ฟอร์มแอดมินมอบหมายงาน (`POST /admin/tickets/{ticket}/assign`)
| ชื่อ Input (`name`) | ชนิด Element | ข้อกำหนด |
| :--- | :--- | :--- |
| `assigned_to` | `<select>` | `required\|exists:users,id` (ดึงเฉพาะ user ที่ role = technician) |
| `comment` | `<textarea>` หรือ `<input>` | `nullable\|string` (ข้อความสั่งการเพิ่มเติม) |

---

### 4.4 ฟอร์มประเมินความพึงพอใจ (`POST /tickets/{ticket}/review`)
| ชื่อ Input (`name`) | ชนิด Element | ตัวเลือก |
| :--- | :--- | :--- |
| `rating` | `<input type="radio">` หรือ Number | `required\|integer\|between:1,5` (1 ถึง 5 ดาว) |
| `comment` | `<textarea>` | `nullable\|string` (ความคิดเห็นของผู้ใช้) |

---

## 5. ตัวแปรที่ Controller ส่งให้ Blade View (View Variable Contract)

Frontend สามารถเรียกใช้ตัวแปรเหล่านี้ในไฟล์ Blade ได้ทันที:

### 1) หน้า `tickets.index`
- `$tickets`: รายการคำขอแจ้งซ่อม (Paginate 10 รายการ)
- `$categories`: หมวดหมู่ทั้งหมด สำหรับทำตัวกรอง Filter

### 2) หน้า `tickets.create`
- `$categories`: Collection ของ `Category::all()` สำหรับใส่ใน `<select>`

### 3) หน้า `tickets.show` (หน้ารายละเอียด & Timeline)
- `$ticket`: ข้อมูล Ticket ใบนั้น พร้อม Relations:
  - `$ticket->user` (ผู้แจ้ง: `$ticket->user->name`, `$ticket->user->phone`)
  - `$ticket->category` (หมวดหมู่: `$ticket->category->name`)
  - `$ticket->technician` (ช่างที่รับผิดชอบ: `$ticket->technician?->name`)
  - `$ticket->logs` (ประวัติ Timeline การเปลี่ยนสถานะ เรียงจากเก่าไปใหม่)
  - `$ticket->review` (ข้อมูลรีวิวถ้ามี: `$ticket->review?->rating`)
- `$technicians`: รายชื่อช่างซ่อมทั้งหมด (สำหรับ Admin เอาไว้แสดงใน Dropdown มอบหมายงาน)

### 4) หน้า `admin.dashboard`
- `$stats`: ข้อมูลสรุปตัวเลข:
  - `$stats['total']`: งานทั้งหมด
  - `$stats['pending']`: รอดำเนินการ
  - `$stats['in_progress']`: กำลังซ่อม
  - `$stats['completed']`: ซ่อมเสร็จแล้ว
  - `$stats['average_rating']`: คะแนนรีวิวเฉลี่ย (เช่น 4.8 / 5.0)
- `$recentTickets`: งานล่าสุด 5 รายการ
- `$chartData`: ข้อมูล Array สำหรับใส่ใน Chart.js แยกตามหมวดหมู่และสถานะ

---

## 6. การแจ้งเตือนข้อความสถานะ (Flash Session Alerts)
ในทุกหน้า Master Layout ให้ใส่โค้ดรับข้อความแจ้งเตือนนี้ไว้:

```blade
@if (session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-800 border border-emerald-200">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg bg-rose-50 p-4 text-rose-800 border border-rose-200">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-50 p-4 text-rose-800 border border-rose-200">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

---

## 7. โค้ดตัวอย่างการเรียกรูปภาพใน Blade
```blade
{{-- แสดงรูปก่อนซ่อม --}}
@if($ticket->image_before)
    <img src="{{ asset('storage/' . $ticket->image_before) }}" alt="Before" class="rounded-lg max-h-60 object-cover">
@else
    <span class="text-gray-400">ไม่มีรูปภาพประกอบ</span>
@endif

{{-- แสดงรูปหลังซ่อม --}}
@if($ticket->image_after)
    <img src="{{ asset('storage/' . $ticket->image_after) }}" alt="After" class="rounded-lg max-h-60 object-cover">
@endif
```
*(⚠️ อย่าลืมรัน `php artisan storage:link` เพื่อให้รูปภาพแสดงผลได้)*
