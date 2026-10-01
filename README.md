# 🛠️ ระบบแจ้งซ่อมบำรุงและจัดการอุปกรณ์ภายในองค์กร (Maintenance Repair System)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Tests](https://img.shields.io/badge/Tests-28%20Passed%20(100%25)-brightgreen?style=for-the-badge)](tests/Feature/RepairSystemContractTest.php)

เว็บแอปพลิเคชันระบบแจ้งซ่อมบำรุงและจัดการอุปกรณ์ พัฒนาด้วย **Laravel 12** และ **Blade Template Engine** ออกแบบตามสถาปัตยกรรม **MVC (Model-View-Controller)** และการควบคุมสิทธิ์ตามบทบาท **RBAC (Role-Based Access Control)** รองรับ 3 กลุ่มผู้ใช้งาน: ผู้ดูแลระบบ (Admin), ช่างซ่อม (Technician), และผู้ใช้งานทั่วไป (User)

---

## 📑 เอกสารประกอบการนำเสนอ (Project Documentation)
- 📐 **เอกสารระบบและไดอะแกรมฉบับเต็ม:** [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md)  
  *(Use Case, System Architecture, ERD, State Machine, Sequence Diagrams 4 ชุด, Data Dictionary)*
- 🚀 **คู่มือสถาปัตยกรรมและการทำงานของ Laravel Framework:** [LARAVEL_GUIDE.md](LARAVEL_GUIDE.md)  
  *(Request Lifecycle, MVC ในระบบ, Eloquent ORM, Middleware CheckRole, Security, Artisan CLI, แนวทางตอบคำถามอาจารย์)*

---

## 🚀 ฟังก์ชันการทำงานหลัก (Core Features)

### 👤 1. สำหรับผู้ใช้งานทั่วไป (User)
- แจ้งซ่อมใหม่ ระบุหมวดหมู่ สถานที่ อาการเสีย ระดับความเร่งด่วน และแนบรูปภาพก่อนซ่อม (Before Image)
- ตรวจสอบรายการแจ้งซ่อมของตนเอง และติดตามประวัติการดำเนินการ (Timeline Audit Log) แบบ Real-time
- กดยกเลิกคำขอแจ้งซ่อมได้ด้วยตนเอง (เฉพาะตอนสถานะรอดำเนินการ)
- ตรวจรับงานและประเมินความพึงพอใจด้วยคะแนนดาว (1-5 ดาว) พร้อมข้อคิดเห็นเพื่อปิดงาน

### 🔧 2. สำหรับช่างซ่อม (Technician)
- ดูรายการงานซ่อมที่ได้รับมอบหมาย (My Tasks) พร้อมตัวกรองสถานะด่วน
- กดรับงาน เปลี่ยนสถานะเป็น "กำลังดำเนินการ" (In Progress) หรือ "รออะไหล่" (Waiting for Parts)
- บันทึกรายละเอียดการซ่อม อะไหล่ที่เปลี่ยน และระบุค่าใช้จ่าย
- แนบรูปถ่ายหลังซ่อมเสร็จสิ้น (After Image) เพื่อเป็นหลักฐานตรวจรับงาน

### 👑 3. สำหรับผู้ดูแลระบบ (Admin)
- แดชบอร์ดสรุปสถิติตัวเลข KPI 5 รายการ (งานทั้งหมด, งานค้าง, กำลังซ่อม, ซ่อมเสร็จ, คะแนนเฉลี่ย)
- ชาร์ตสถิติจำแนกตามหมวดหมู่งานซ่อม และสัดส่วนสถานะงานในองค์กร
- ค้นหาและกรองงานซ่อมทั้งหมดแบบ Multi-filter (คำค้นหา, สถานะ, หมวดหมู่, ช่างผู้ดูแล)
- สั่งมอบหมายงาน (Dispatch/Assign) ให้ช่างซ่อมที่รับผิดชอบ พร้อมระบุคำสั่งการเพิ่มเติม

---

## 🔑 ข้อมูลบัญชีสำหรับทดสอบระบบ (Test Credentials)

ทุกบัญชีใช้รหัสผ่านเดียวกันคือ: `password`

| บทบาท (Role) | อีเมล (Email) | รหัสผ่าน | หน้าเริ่มต้นและหน้าที่หลัก |
| :--- | :--- | :--- | :--- |
| 👑 **ผู้ดูแลระบบ (Admin)** | `admin@repair.test` | `password` | **แดชบอร์ดสรุปสถิติ & จ่ายงาน** (`/admin/dashboard`) |
| 🔧 **ช่างซ่อม (Technician)** | `tech@repair.test` | `password` | **งานที่ได้รับมอบหมาย & อัปเดตสถานะ** (`/technician/tasks`) |
| 👤 **ผู้ใช้งานทั่วไป (User)** | `user@repair.test` | `password` | **แจ้งซ่อมใหม่ & ติดตามสถานะ** (`/tickets`) |

*(💡 ที่หน้าล็อกอินมีปุ่มกดด่วนเพื่อกรอกอีเมล/รหัสผ่านให้อัตโนมัติ)*

---

## 💻 การติดตั้งและเปิดใช้งาน (Quick Start)

```bash
# 1. ติดตั้ง Dependencies ฝั่ง PHP
composer install

# 2. คัดลอกไฟล์ Environment และสร้าง Application Key
cp .env.example .env
php artisan key:generate

# 3. สร้าง Symlink สำหรับพื้นที่จัดเก็บรูปภาพ
php artisan storage:link

# 4. รัน Database Migration และสร้างข้อมูลทดสอบเริ่มต้น (Seeder)
php artisan migrate:fresh --seed

# 5. รันเซิร์ฟเวอร์
php artisan serve
```

เปิดเว็บเบราว์เซอร์เข้าใช้งานได้ที่: **http://127.0.0.1:8000**

---

## 🌐 การทดสอบเชื่อมต่อพร้อมกันหลายเครื่อง (Multi-Device / User & Admin Live Demo)

หากต้องการสาธิตการทำงานจริงแบบแยกเครื่อง เช่น **เครื่องคุณเป็น User** และ **เครื่องเพื่อน/อาจารย์เป็น Admin**:

1. **เปิดเซิร์ฟเวอร์แบบเปิดรับทุกการเชื่อมต่อในวง LAN:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. **หา IP Address ของเครื่องโฮสต์ (เครื่องที่รันเซิร์ฟเวอร์):**
   - รันคำสั่ง `ipconfig` ใน Terminal/PowerShell เพื่อดูหมายเลข IPv4 (เช่น `10.53.11.150` หรือ `192.168.1.X`)
3. **การเข้าใช้งานจากอุปกรณ์ต่าง ๆ (ใน Wi-Fi / Hotspot เดียวกัน):**
   - **เครื่องโฮสต์ (คุณ - User 👤):** เข้าผ่าน `http://127.0.0.1:8000` $\rightarrow$ ล็อกอินเป็น `user@repair.test`
   - **เครื่องเพื่อน (Admin 👑):** เข้าผ่าน `http://<IP_เครื่องโฮสต์>:8000` (เช่น `http://10.53.11.150:8000`) $\rightarrow$ ล็อกอินเป็น `admin@repair.test`
   - **เครื่องช่าง (Technician 🔧):** สามารถใช้มือถือเข้าผ่าน `http://<IP_เครื่องโฮสต์>:8000` $\rightarrow$ ล็อกอินเป็น `tech@repair.test`
4. **ลำดับการทดสอบแบบ Real-time:**
   - User สร้างใบแจ้งซ่อมใหม่พร้อมรูปถ่าย $\rightarrow$ Admin เห็นงานใหม่ทันทีใน Dashboard และกดมอบหมายงานให้ช่าง $\rightarrow$ ช่างรับงานและอัปเดตสถานะ $\rightarrow$ User ตรวจรับงานและให้คะแนนดาวเพื่อปิดงาน

---

## 🧪 การทดสอบระบบอัตโนมัติ (Automated Testing)

รันชุดการทดสอบคุณภาพระบบด้วยคำสั่ง:
```bash
php artisan test
```

ผลการทดสอบ: **ผ่านครบทั้ง 28 Tests (89 Assertions) 100%** ครอบคลุมการทำงานทุกโมดูลและการควบคุมสิทธิ์ Role-Based Authorization

