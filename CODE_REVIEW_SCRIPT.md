# 🎬 สคริปต์อัดคลิปวิดีโอโค้ดรีวิว (Code Review Video Script - 2 คน)
## โครงงาน: ระบบแจ้งซ่อมบำรุงและจัดการอุปกรณ์ภายในองค์กร (Maintenance Repair System)
> **เทคโนโลยี:** Laravel 12 • Blade Template Engine • Tailwind CSS • SQLite / MySQL  
> **รูปแบบการนำเสนอ:** Pair Code Review (คู่หูนักพัฒนารีวิวโค้ดร่วมกัน ผลัดกันถาม-ตอบ เจาะลึก Logic และ Security)  
> **ความยาวโดยประมาณ:** 12 - 15 นาที

---

### 👥 สรุปการแบ่งบทบาทหน้าที่ (Roles & Responsibilities)

| ผู้บรรยาย | บทบาทหลักในการรีวิว | ส่วนของโค้ดและไฟล์ที่รับผิดชอบ |
| :--- | :--- | :--- |
| **คนที่ 1 (Speaker A)** | **Lead Developer & Backend Core**<br>เน้นเปิดคลิป, ภาพรวมสถาปัตยกรรม MVC, เส้นทาง Routing & Middleware, ความปลอดภัยระบบ Auth และ Logic การสร้างใบแจ้งซ่อม | • ภาพรวมระบบ & Architecture<br>• [routes/web.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/routes/web.php)<br>• [AuthController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/AuthController.php)<br>• [TicketController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/TicketController.php) (index, create, store, show, cancel, review) |
| **คนที่ 2 (Speaker B)** | **Technical Reviewer & Operations Logic**<br>เน้นยิงคำถามเจาะลึก, อธิบายระบบ Dashboard KPI, Logic การค้นหา/Multi-filter, การจ่ายงานให้ช่าง และส่วนงานของ Technician | • แดชบอร์ดและสถิติ KPI<br>• [AdminController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/AdminController.php) (dashboard, tickets, assign)<br>• [TechnicianController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/TechnicianController.php) (tasks, updateStatus, Before/After image)<br>• สรุปจุดแข็งด้าน Security & Best Practices |

---

### ⏱️ กำหนดการและตารางเวลาของคลิป (Timeline Breakdown)

```
00:00 - 01:30 (1.5 นาที) ── Scene 1: เปิดคลิป & ภาพรวมโจทย์ของระบบแจ้งซ่อม
01:30 - 03:30 (2.0 นาที) ── Scene 2: สถาปัตยกรรม Routing & Role-Based Access Control
03:30 - 05:30 (2.0 นาที) ── Scene 3: ระบบยืนยันตัวตนและการกระจายสิทธิ์ (AuthController)
05:30 - 08:30 (3.0 นาที) ── Scene 4: วงจรชีวิตคำขอแจ้งซ่อมและฟังก์ชัน User (TicketController)
08:30 - 11:00 (2.5 นาที) ── Scene 5: แดชบอร์ด KPI และการจ่ายงานแอดมิน (AdminController)
11:00 - 13:00 (2.0 นาที) ── Scene 6: โมดูลงานช่างซ่อมและหลักฐานปิดงาน (TechnicianController)
13:00 - 14:30 (1.5 นาที) ── Scene 7: สรุป Security, Clean Code และปิดคลิป
```

---

## 📽️ บทพูดและขั้นตอนการอัดคลิปฉบับเต็ม (Full Dialogue & Screen Cues)

---

### Scene 1: เปิดคลิป และภาพรวมโจทย์ของระบบ (00:00 - 01:30)
**[Visual Cue / จอภาพ]:** แสดงหน้าเว็บจริงของระบบแจ้งซ่อมบำรุง หรือเปิดไดอะแกรมจาก [SYSTEM_DOCUMENTATION.md](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/SYSTEM_DOCUMENTATION.md) แล้วสลับมาที่ VS Code

**คนที่ 1 (Speaker A):**  
"สวัสดีอาจารย์และเพื่อนๆ ทุกคนครับ วันนี้พวกเรา 2 คนจะมาทำ **Code Review Walkthrough** เจาะลึกโค้ดเบื้องหลังของ **'ระบบแจ้งซ่อมบำรุงและจัดการอุปกรณ์ภายในองค์กร'** พัฒนาด้วย Laravel 12 และ Blade ครับ ผม [ชื่อคนพูดที่ 1] รับหน้าที่เป็น Lead Developer ครับ"

**คนที่ 2 (Speaker B):**  
"และผม [ชื่อคนพูดที่ 2] รับหน้าที่เป็น Technical Reviewer ครับ วันนี้เราจะมารีวิวโค้ดกันแบบเจาะลึกบรรทัดต่อบรรทัด ดูว่าสถาปัตยกรรม MVC ของโปรเจกต์นี้ทำงานอย่างไร จัดการความปลอดภัย และแก้ปัญหาทางธุรกิจในองค์กรอย่างไรบ้างครับ"

**คนที่ 1 (Speaker A):**  
"ระบบนี้ออกแบบมาเพื่อแก้ปัญหาคลาสสิกขององค์กรครับ เช่น แจ้งซ่อมผ่านไลน์แล้วข้อความตกหล่น ช่างไม่รู้ว่าต้องไปซ่อมห้องไหน หรือหัวหน้างานไม่รู้ว่าตอนนี้มีงานค้างกี่งาน เราจึงแบ่งผู้ใช้งานออกเป็น 3 บทบาทหลักตามหลัก RBAC คือ:
1. **User (ผู้ใช้งานทั่วไป):** ส่งเรื่อง แนบรูป ติดตาม Timeline และให้คะแนนดาว
2. **Technician (ช่างซ่อม):** รับงาน อัปเดตสถานะ กำลังซ่อม/รออะไหล่ และแนบรูปหลังซ่อม
3. **Admin (ผู้ดูแลระบบ):** ดู Dashboard KPI ตัวเลขรวม และมอบหมายงานให้ช่างที่เหมาะสมครับ"

---

### Scene 2: สถาปัตยกรรม Routing & Role-Based Access Control (01:30 - 03:30)
**[Visual Cue / จอภาพ]:** เปิดไฟล์ [routes/web.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/routes/web.php) ใน VS Code และซูมโค้ดบรรทัดที่ 22 - 75

**คนที่ 2 (Speaker B):**  
*(ชี้ไปที่ routes/web.php)*  
"งั้นเรามาเริ่มต้นที่จุดเริ่มต้นของ Request ทั้งหมดกันเลยครับ คือไฟล์ `routes/web.php` ผมสังเกตว่าที่ Route `/` และ `/dashboard` มีการเขียนโค้ดที่น่าสนใจมาก โค้ดตรงนี้ทำงานอย่างไรครับคุณ A?"

**คนที่ 1 (Speaker A):**  
*(ไฮไลต์บรรทัดที่ 25 - 36)*  
"ตรงนี้เราสร้างเป็น **Smart Redirection** ครับ โดยเช็คว่าถ้าผู้ใช้ล็อกอินเข้ามาแล้ว (`Auth::check()`) เราจะใช้ `match expression` ของ PHP 8 ตรวจสอบ `Auth::user()->role`:
- ถ้าเป็น `admin` ระบบจะ Redirect ไปที่ `admin.dashboard` ทันที
- ถ้าเป็น `technician` จะเด้งไปที่หน้ารายการงานช่าง `tech.tasks`
- และถ้าเป็น `user` ทั่วไป จะส่งไปที่ `tickets.index` ครับ
ซึ่งตรงนี้ช่วยให้ผู้ใช้งานไม่ต้องจำ URL แยกกัน ทุกคนเข้าผ่านหน้าแรกหน้าเดียว แล้วระบบกระจายให้เองครับ"

**คนที่ 2 (Speaker B):**  
*(เลื่อนจอลงมาที่บรรทัด 58 - 128)*  
"ยอดเยี่ยมครับ แล้วการป้องกันไม่ให้ User แอบพิมพ์ URL ข้ามไปหน้าของ Admin หรือช่างทำอย่างไรครับ?"

**คนที่ 1 (Speaker A):**  
"เราใช้ **Role Middleware** แบบจัดกลุ่ม (Group Middleware) ครับ สังเกตที่บรรทัด 70 เราใช้ `middleware(['role:user,admin'])` และบรรทัด 116 สำหรับ `middleware(['role:admin'])->prefix('admin')`  
ถ้าผู้ใช้คนไหนไม่มีสิทธิ์ หรือพยายามพิมพ์ URL ข้ามบทบาท ตัว `CheckRole` Middleware จะดักและโยน HTTP 403 Forbidden ออกมาทันที ทำให้ระบบมีความปลอดภัยสูงสุดตั้งแต่ระดับ Route ครับ"

---

### Scene 3: ระบบยืนยันตัวตนและการกระจายสิทธิ์ (AuthController) (03:30 - 05:30)
**[Visual Cue / จอภาพ]:** สลับไปเปิดไฟล์ [app/Http/Controllers/AuthController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/AuthController.php)

**คนที่ 2 (Speaker B):**  
"ทีนี้เรามาดูตัว Controller แรกกันครับ `AuthController.php` ซึ่งเป็นประตูหลักในการเข้าใช้งานระบบ ผมสังเกตว่าในฟังก์ชัน `login()` ที่บรรทัด 46 มีการเขียนเรื่องความปลอดภัยไว้หลายจุด ช่วยอธิบายให้ฟังหน่อยครับ"

**คนที่ 1 (Speaker A):**  
*(ไฮไลต์บรรทัดที่ 48 - 72 ของ AuthController.php)*  
"ในฟังก์ชัน `login()` เราแบ่งเป็น 3 สเต็ปชัดเจนครับ:
1. **Validation:** เช็คว่ากรอกอีเมลและรหัสผ่านครบถ้วนและรูปแบบถูกต้อง พร้อม custom ข้อความแจ้งเตือนภาษาไทย
2. **Authentication:** ใช้ `Auth::attempt($credentials, $request->boolean('remember'))` ซึ่งคำสั่งนี้จะไปค้นหาผู้ใช้ และนำ password ที่ส่งเข้ามาไปเทียบกับค่า Bcrypt Hash ในฐานข้อมูลโดยอัตโนมัติ
3. **Session Security:** จุดสำคัญที่สุดคือบรรทัดที่ 60 `$request->session()->regenerate();` คำสั่งนี้จะออก Session ID ตัวใหม่ทันทีที่ล็อกอินผ่าน เพื่อป้องกันช่องโหว่ **Session Fixation Attack** ไม่ให้ผู้ไม่หวังดีขโมย Session เดิมไปสวมรอยครับ"

**คนที่ 2 (Speaker B):**  
*(ชี้ไปที่ฟังก์ชัน register บรรทัด 114 - 146)*  
"แล้วในฟังก์ชัน `register()` สังเกตว่าที่บรรทัด 136 เราฟิกค่า `'role' => 'user'` ไว้เลย ทำไมถึงไม่รับค่า role มาจากฟอร์มที่ส่งมาล่ะครับ?"

**คนที่ 1 (Speaker A):**  
"ตรงนี้เป็นเรื่อง **Mass Assignment Vulnerability** และ Business Logic ครับ เพราะหน้าสมัครสมาชิกทั่วไปต้องอนุญาตให้เป็นได้แค่ผู้ใช้งานธรรมดาเท่านั้น หากเรารับค่า `role` มาจาก Request ผู้ใช้อาจใช้เครื่องมืออย่าง Postman หรือ Inspect Element แอบส่ง `role = admin` เข้ามาได้ เราจึงฟิกค่าเริ่มต้นเป็น `user` เสมอ ส่วนช่างหรือแอดมินจะต้องถูกสร้างหรือแต่งตั้งโดยผู้ดูแลระบบเท่านั้นครับ"

---

### Scene 4: วงจรชีวิตของใบแจ้งซ่อมและฟังก์ชัน User (TicketController) (05:30 - 08:30)
**[Visual Cue / จอภาพ]:** สลับไปเปิดไฟล์ [app/Http/Controllers/TicketController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/TicketController.php)

**คนที่ 2 (Speaker B):**  
"มาถึงหัวใจหลักของระบบครับ `TicketController.php` ซึ่งจัดการฝั่งผู้ใช้งานทั่วไป เริ่มจากฟังก์ชัน `index()` ที่บรรทัด 34 คุณ A ช่วยเล่าเทคนิคการ Query ข้อมูลตรงนี้หน่อยครับ"

**คนที่ 1 (Speaker A):**  
*(ไฮไลต์บรรทัด 38 - 57)*  
"ใน `index()` เราใช้เทคนิค 2 อย่างครับ:
1. **Data Isolation:** ใช้ `where('user_id', Auth::id())` เพื่อมั่นใจ 100% ว่าผู้ใช้จะเห็นเฉพาะงานที่ตัวเองแจ้งเท่านั้น
2. **Prevent N+1 Query:** ใช้ `with(['category', 'technician'])` ซึ่งเป็นการทำ **Eager Loading** โหลดข้อมูลหมวดหมู่และช่างมาใน Query เดียว ทำให้ประสิทธิภาพการโหลดหน้าเว็บเร็วมาก แม้จะมีข้อมูลเป็นพันรายการ
3. และต่อท้ายด้วย `paginate(10)->withQueryString()` เพื่อแบ่งหน้าละ 10 รายการ และคงค่าตัวกรองค้นหาไว้ ไม่หลุดหายเวลาคลิกไปหน้า 2 ครับ"

**คนที่ 2 (Speaker B):**  
*(เลื่อนมาที่ฟังก์ชัน store บรรทัด 80 - 145)*  
"ทีนี้มาดูจุดที่คนมักเจอปัญหากันบ่อย คือการสร้าง Ticket ใหม่ในฟังก์ชัน `store()` โค้ดตรงนี้มีทั้งการ Validate, อัปโหลดรูปภาพ และสร้างเลขที่ใบแจ้งซ่อม ช่วยอธิบายโฟลว์ตรงนี้ให้ฟังหน่อยครับ"

**คนที่ 1 (Speaker A):**  
*(ไฮไลต์บรรทัด 100 - 140)*  
"โฟลว์การทำงานใน `store()` มี 4 ขั้นตอนสำคัญครับ:
- **1. File Upload:** บรรทัด 104 ตรวจสอบว่าถ้ามีรูปภาพ จะบันทึกผ่าน `$request->file('image_before')->store('repairs', 'public')` เก็บไว้ใน Storage ปลอดภัย
- **2. Ticket Generator:** บรรทัด 108 - 118 เราออกแบบฟอร์แมตรหัสแบบ `REP-YYYYMM-XXXX` (เช่น REP-202610-0001) โดยนับจำนวนงานของเดือนนั้น แล้วใช้คำสั่ง `sprintf('%04d', $count)` เพื่อเติม 0 ข้างหน้าให้ครบ 4 หลัก และมีลูป `while` ตรวจสอบความซ้ำซ้อนในฐานข้อมูลอีกชั้นหนึ่ง
- **3. Insert Data:** สร้างใบแจ้งซ่อมด้วยสถานะเริ่มต้นเป็น `pending`
- **4. Audit Trail (RepairLog):** ที่บรรทัด 134 ทันทีที่ตั๋วถูกสร้าง เราสั่ง `RepairLog::create()` เพื่อบันทึกประวัติ Timeline ก้าวแรกของระบบทันทีครับ"

**คนที่ 2 (Speaker B):**  
*(ชี้ไปที่ฟังก์ชัน cancel และ review บรรทัด 179 และ 214)*  
"ผมชอบตรงฟังก์ชัน `cancel()` และ `review()` มาก มีการเช็ค State ชัดเจน เช่น กดยกเลิกได้เฉพาะสถานะ `pending` เท่านั้น และจะให้คะแนนดาวได้ก็ต่อเมื่อสถานะเป็น `completed` เพื่อเปลี่ยนเป็น `closed` เป็นการนำ **State Machine Pattern** มาใช้อย่างถูกต้องเลยครับ"

---

### Scene 5: แดชบอร์ด KPI และการจ่ายงานของแอดมิน (AdminController) (08:30 - 11:00)
**[Visual Cue / จอภาพ]:** สลับไปเปิดไฟล์ [app/Http/Controllers/AdminController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/AdminController.php)

**คนที่ 1 (Speaker A):**  
"ต่อไปขอส่งไม้ต่อให้คุณ B มารีวิวโค้ดในฝั่งผู้ดูแลระบบ `AdminController.php` ครับ เชิญคุณ B อธิบายตัวแดชบอร์ดในบรรทัดที่ 30 ได้เลยครับ"

**คนที่ 2 (Speaker B):**  
*(ไฮไลต์บรรทัดที่ 32 - 69 ของ AdminController.php)*  
"ขอบคุณครับ ในหน้า `dashboard()` ของ Admin เราต้องแสดงผลสถิติอย่างรวดเร็วและแม่นยำ:
- เราคำนวณ KPI Cards 5 ตัวด้วย Eloquent Aggregates เช่น `count()` นับงานทั้งหมด, งานค้าง, งานกำลังทำ และงานเสร็จสิ้น
- ส่วนคะแนนความพึงพอใจ เราใช้ `Review::avg('rating')` และปัดเศษทศนิยม 1 ตำแหน่งด้วย `round()`
- ที่น่าสนใจคือบรรทัดที่ 53 เราเตรียมชุดข้อมูล `$chartData` ดึงหมวดหมู่พร้อมนับจำนวนด้วย `withCount('tickets')` เพื่อนำ `pluck('name')` และ `pluck('tickets_count')` ส่งเป็น Array ให้ JavaScript นำไปวาดกราฟแสดงสัดส่วนงานซ่อมแต่ละประเภทได้อย่างสวยงามครับ"

**คนที่ 1 (Speaker A):**  
*(ชี้ไปที่ฟังก์ชัน tickets บรรทัด 79 - 124)*  
"แล้วในหน้ารายการงานซ่อมของแอดมิน ฟังก์ชัน `tickets()` มีการรองรับการค้นหาและฟิลเตอร์เยอะมาก โค้ดส่วนนี้เขียนอย่างไรให้รองรับหลายเงื่อนไขพร้อมกันครับ?"

**คนที่ 2 (Speaker B):**  
*(ไฮไลต์บรรทัด 84 - 115)*  
"เราใช้เทคนิค **Dynamic Query Building** ครับ เริ่มต้นสร้าง `$query = RepairRequest::with(...)` จากนั้นตรวจสอบ Request ทีละฟิลด์ด้วย `$request->filled()`:
- ถ้าพิมพ์ค้นหา `search` เราใช้ `whereHas('user')` เพื่อค้นหาข้ามตารางไปยังชื่อผู้แจ้งได้ด้วย นอกเหนือจากรหัสตั๋วและสถานที่
- และถ้ามีการเลือก dropdown กรองตาม `status`, `category_id`, `urgency` หรือช่างผู้รับผิดชอบ `assigned_to` ก็จะค่อยๆ ต่อเติมเงื่อนไขเข้าไปใน Query ทีละตัว ทำให้ค้นหาแบบผสมผสานได้อิสระครับ"

**คนที่ 1 (Speaker A):**  
*(ชี้ไปที่ฟังก์ชัน assign บรรทัด 134 - 178)*  
"และที่สำคัญคือฟังก์ชัน `assign()` มอบหมายงานให้ช่าง มีการป้องกันอะไรเป็นพิเศษไหมครับ?"

**คนที่ 2 (Speaker B):**  
*(ไฮไลต์บรรทัด 146 - 150)*  
"มีแน่นอนครับ! บรรทัดที่ 146 เราตรวจสอบว่า ID ที่แอดมินเลือก มี `role === 'technician'` จริงหรือไม่ ป้องกันไม่ให้เผลอจ่ายงานให้ User ทั่วไป และเมื่อจ่ายงานเสร็จ เราอัปเดตสถานะเป็น `assigned` พร้อมบันทึกข้อความสั่งการของแอดมินลงใน `RepairLog` เพื่อให้ทุกคนในระบบดู Timeline ย้อนหลังได้ว่าแอดมินคนไหนสั่งจ่ายงานให้ช่างคนไหน เมื่อไหร่ครับ"

---

### Scene 6: โมดูลงานช่างซ่อมและหลักฐานปิดงาน (TechnicianController) (11:00 - 13:00)
**[Visual Cue / จอภาพ]:** สลับไปเปิดไฟล์ [app/Http/Controllers/TechnicianController.php](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/app/Http/Controllers/TechnicianController.php)

**คนที่ 1 (Speaker A):**  
"มาถึงตัว Controller สุดท้าย `TechnicianController.php` ที่ใช้สำหรับช่างซ่อมหน้างานครับ คุณ B ช่วยอธิบายฟังก์ชัน `updateStatus()` ในบรรทัด 53 หน่อยครับ"

**คนที่ 2 (Speaker B):**  
*(ไฮไลต์บรรทัด 55 - 117 ของ TechnicianController.php)*  
"ในฟังก์ชัน `updateStatus()` มีจุดเด่น 3 จุดหลักครับ:
1. **Authorization Check (บรรทัด 56):** เราตรวจสอบว่า `$ticket->assigned_to !== Auth::id()` หรือไม่ ช่างซ่อมจะอัปเดตได้เฉพาะงานที่ตนเองได้รับมอบหมายเท่านั้น ช่างคนอื่นไม่สามารถมาก้าวก่ายงานข้ามกันได้ เว้นแต่เป็น Admin
2. **Before & After Evidence (บรรทัด 88):** เมื่อช่างซ่อมเสร็จ สามารถอัปโหลดรูปถ่ายหลังซ่อม `image_after` เพื่อเป็นหลักฐานเปรียบเทียบ Before & After ยืนยันว่าอุปกรณ์กลับมาใช้งานได้จริง
3. **Cost & Completed Timestamp (บรรทัด 82, 93):** สามารถระบุค่าใช้จ่ายอะไหล่ `repair_cost` และถ้าสถานะเปลี่ยนเป็น `completed` ระบบจะแสตมป์เวลา `completed_at = now()` เพื่อนำไปใช้วัด SLA และระยะเวลาเฉลี่ยในการซ่อม (Mean Time to Repair) ขององค์กรได้ด้วยครับ"

---

### Scene 7: สรุป Security, Clean Architecture และปิดคลิป (13:00 - 14:30)
**[Visual Cue / จอภาพ]:** สลับกลับมาที่หน้าเว็บสรุป Dashboard หรือเปิดเอกสาร [SYSTEM_DOCUMENTATION.md](file:///d:/WEBDD/Miniproject/MiniProject_RepairSystem-main/SYSTEM_DOCUMENTATION.md) แล้วสลับมาเห็นหน้าผู้บรรยายทั้ง 2 คน

**คนที่ 1 (Speaker A):**  
"จากการที่เราได้รีวิวโค้ด Controller ทั้ง 4 ตัวไป จะเห็นได้ว่าระบบนี้ยึดหลัก **Clean Architecture** และมาตรฐานความปลอดภัยอย่างเคร่งครัด:
- มีการใช้ **CSRF Protection** และ **Session Fixation Prevention**
- มีการป้องกัน **N+1 Problem** ผ่าน Eager Loading ทุกจุด
- ใช้ **Form Validation** พร้อมข้อความภาษาไทยที่เข้าใจง่าย
- และที่สำคัญที่สุดคือการบันทึก **Audit Trail (RepairLog)** ทุกการเคลื่อนไหว ทำให้ระบบโปร่งใสและตรวจสอบย้อนหลังได้ 100% ครับ"

**คนที่ 2 (Speaker B):**  
"ถูกต้องครับ โค้ดทั้งหมดในโปรเจกต์นี้ได้รับการคอมเมนต์อธิบายอย่างละเอียดทุกบรรทัด ทำให้ง่ายต่อการนำไปบำรุงรักษาและพัฒนาต่อยอดในอนาคต เช่น การเชื่อมต่อ Line Notify หรือการทำ Push Notification ครับ  
สำหรับคลิปการ Code Review ของพวกเราในวันนี้ก็จบลงเพียงเท่านี้ หากอาจารย์หรือเพื่อนๆ มีข้อสงสัยหรือข้อเสนอแนะ สามารถสอบถามได้เลยครับ ขอบคุณครับ!"

**คนที่ 1 (Speaker A):**  
"ขอบคุณมากครับ สวัสดีครับ!" *(ทั้งคู่ไหว้พร้อมกัน ปิดคลิป)*

---

## 💡 เทคนิคและคำแนะนำสำหรับการอัดคลิปให้คะแนนเต็ม (Pro Recording Tips)

1. **การตั้งค่าหน้าจอ VS Code:**
   - ปรับขนาดฟอนต์ใน VS Code ให้ใหญ่พอเหมาะ (`Ctrl` + `+` หรือ font size 18-20)
   - แนะนำให้ปิด Sidebar ฝั่งซ้ายชั่วคราว (`Ctrl` + `B`) ขณะอธิบายโค้ด เพื่อให้พื้นที่โค้ดกว้างและเห็นคอมเมนต์ทุกบรรทัดชัดเจน
2. **การส่งคิวรับ-ส่งบทสนทนา:**
   - อย่าท่องจำแบบหุ่นยนต์ ให้พูดเหมือนเรากำลังเล่าให้เพื่อนหรือทีมงานฟัง สบตาเลนส์กล้องหรือสบตากันเวลาผลัดกันถาม
   - คนที่ 2 สามารถใช้เคอร์เซอร์เมาส์หรือการลากแถบคลุมโค้ด (Highlight) ตามบรรทัดที่คนพูดกำลังอธิบาย เพื่อให้สายตาคนดูโฟกัสตามได้ทันที
3. **การเตรียมตัวตอบคำถามอาจารย์ (Potential Defense Q&A):**
   - **Q: ทำไมไม่ใช้ Soft Deletes ในตาราง repair_requests?**  
     *A: เพราะในระบบงานซ่อม เราต้องการเก็บ Audit Trail ไว้ตลอดเวลา จึงเลือกใช้การเปลี่ยนสถานะเป็น 'cancelled' แทนการลบข้อมูลจริง เพื่อให้ประวัติไม่สูญหาย*
   - **Q: ทำไมต้องแยก RepairLog ออกมาเป็นอีกตาราง?**  
     *A: เพื่อรองรับประวัติหลายขั้นตอนแบบ One-to-Many ทำให้แสดงผลเป็น Timeline ได้ว่าใครทำอะไร เมื่อไหร่ โดยไม่ต้องเขียนข้อมูลทับในแถวเดิมครับ*
