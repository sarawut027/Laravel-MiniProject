# 🛠️ เอกสารสถาปัตยกรรมและแผนผังระบบแจ้งซ่อมบำรุง (Maintenance Repair System Documentation)

> **โครงงาน:** ระบบแจ้งซ่อมบำรุงและจัดการอุปกรณ์ภายในองค์กร (Maintenance Repair System)  
> **เทคโนโลยี:** Laravel 12 • Blade Template Engine • Tailwind CSS • SQLite / MySQL • PHP 8.2+  
> **วัตถุประสงค์:** ใช้ประกอบการนำเสนอโครงงาน (Presentation) ให้อาจารย์และคณะกรรมการตรวจประเมิน เพื่อแสดงสถาปัตยกรรมระบบ แผนผังฐานข้อมูล ลำดับการทำงาน (Workflows) และการทดสอบระบบอัตโนมัติ

---

## 📑 สารบัญ (Table of Contents)
1. [ภาพรวมของระบบและบทบาทผู้ใช้งาน (System Overview & Roles)](#1-ภาพรวมของระบบและบทบาทผู้ใช้งาน-system-overview--roles)
2. [แผนภาพ Use Case ของระบบ (Use Case Diagram)](#2-แผนภาพ-use-case-ของระบบ-use-case-diagram)
3. [สถาปัตยกรรมระบบ (System Architecture Diagram)](#3-สถาปัตยกรรมระบบ-system-architecture-diagram)
4. [แผนผังฐานข้อมูลและความสัมพันธ์ (Entity Relationship Diagram - ERD)](#4-แผนผังฐานข้อมูลและความสัมพันธ์-entity-relationship-diagram---erd)
5. [วงจรสถานะงานแจ้งซ่อม (Ticket Lifecycle & State Machine)](#5-วงจรสถานะงานแจ้งซ่อม-ticket-lifecycle--state-machine)
6. [ลำดับการทำงานของระบบ (Sequence Diagrams)](#6-ลำดับการทำงานของระบบ-sequence-diagrams)
7. [พจนานุกรมข้อมูล (Data Dictionary & Schema)](#7-พจนานุกรมข้อมูล-data-dictionary--schema)
8. [ตารางเส้นทางและการควบคุมสิทธิ์ (Route & Permission Matrix)](#8-ตารางเส้นทางและการควบคุมสิทธิ์-route--permission-matrix)
9. [การทดสอบระบบอัตโนมัติ (Automated Testing & Quality Assurance)](#9-การทดสอบระบบอัตโนมัติ-automated-testing--quality-assurance)
10. [คู่มือการติดตั้งและข้อมูลบัญชีทดสอบ (Setup & Test Credentials)](#10-คู่มือการติดตั้งและข้อมูลบัญชีทดสอบ-setup--test-credentials)

---

## 1. ภาพรวมของระบบและบทบาทผู้ใช้งาน (System Overview & Roles)

ระบบแจ้งซ่อมบำรุงถูกออกแบบตามหลักสถาปัตยกรรม **MVC (Model-View-Controller)** และการควบคุมสิทธิ์ตามบทบาท **RBAC (Role-Based Access Control)** โดยแบ่งผู้ใช้งานออกเป็น 3 กลุ่มหลัก:

| บทบาท (Role) | สัญลักษณ์ | หน้าที่และความรับผิดชอบหลักในระบบ |
| :--- | :---: | :--- |
| **1. ผู้ใช้งานทั่วไป (User)** | 👤 | • ส่งเรื่องแจ้งซ่อมใหม่ พร้อมระบุสถานที่ หมวดหมู่ ความเร่งด่วน และแนบรูปภาพก่อนซ่อม<br>• ติดตามสถานะงานและประวัติ Timeline แบบ Real-time<br>• ขอยกเลิกคำขอได้ด้วยตนเอง (เฉพาะตอนสถานะรอดำเนินการ)<br>• ตรวจรับงานและให้คะแนนความพึงพอใจ (1-5 ดาว) เพื่อปิดงาน |
| **2. ช่างซ่อม (Technician)** | 🔧 | • ดูรายการงานที่ได้รับมอบหมาย (My Tasks)<br>• อัปเดตสถานะงาน (กำลังดำเนินการ / รออะไหล่ / ซ่อมเสร็จสิ้น)<br>• บันทึกวิธีซ่อม อะไหล่ที่เปลี่ยน และระบุค่าใช้จ่าย<br>• แนบรูปถ่ายหลังซ่อมเสร็จ (Image After) เพื่อเป็นหลักฐานปิดงาน |
| **3. ผู้ดูแลระบบ (Admin)** | 👑 | • ตรวจสอบสถิติภาพรวมผ่าน Dashboard (ตัวเลข KPI 5 รายการ และชาร์ตแจกแจง)<br>• ค้นหาและกรองรายการงานทั้งหมดในองค์กรแบบ Multi-filter<br>• สั่งมอบหมายงาน (Dispatch/Assign) ให้ช่างที่เหมาะสมพร้อมบันทึกคำสั่งการ |

---

## 2. แผนภาพ Use Case ของระบบ (Use Case Diagram)

แผนภาพแสดงฟังก์ชันการทำงานที่แต่ละกลุ่มผู้ใช้งานสามารถกระทำได้ในระบบ:

```mermaid
flowchart LR
    subgraph Actors["กลุ่มผู้ใช้งาน (Actors)"]
        User["👤 ผู้ใช้งานทั่วไป (User)"]
        Tech["🔧 ช่างซ่อม (Technician)"]
        Admin["👑 ผู้ดูแลระบบ (Admin)"]
    end

    subgraph AuthModule["ระบบยืนยันตัวตน (Authentication)"]
        UC_Login(["เข้าสู่ระบบ (Login)"])
        UC_Register(["สมัครสมาชิก (Register)"])
        UC_Logout(["ออกจากระบบ (Logout)"])
    end

    subgraph UserModule["โมดูลผู้ใช้งาน (User Actions)"]
        UC_Create(["สร้างใบแจ้งซ่อม + แนบรูปภาพ"])
        UC_MyTickets(["ดูรายการแจ้งซ่อมของตนเอง"])
        UC_ViewDetail(["ดูรายละเอียด & Timeline"])
        UC_Cancel(["ขอยกเลิกคำขอ (สถานะ Pending)"])
        UC_Review(["ประเมินดาว & ตรวจรับงาน"])
    end

    subgraph TechModule["โมดูลช่างซ่อม (Technician Actions)"]
        UC_MyTasks(["ดูงานที่ได้รับมอบหมาย (My Tasks)"])
        UC_UpdateStatus(["อัปเดตสถานะงาน (กำลังซ่อม/รออะไหล่)"])
        UC_Complete(["ปิดงานซ่อม + แนบรูปหลังซ่อม + ค่าใช้จ่าย"])
    end

    subgraph AdminModule["โมดูลผู้ดูแลระบบ (Admin Actions)"]
        UC_Dashboard(["ดูแดชบอร์ดสถิติ & กราฟ KPI"])
        UC_ManageAll(["ค้นหา & กรองงานซ่อมทั้งหมด"])
        UC_Assign(["จ่ายงานและมอบหมายให้ช่าง"])
    end

    %% User Relations
    User --> UC_Login
    User --> UC_Register
    User --> UC_Logout
    User --> UC_Create
    User --> UC_MyTickets
    User --> UC_ViewDetail
    User --> UC_Cancel
    User --> UC_Review

    %% Technician Relations
    Tech --> UC_Login
    Tech --> UC_Logout
    Tech --> UC_MyTasks
    Tech --> UC_ViewDetail
    Tech --> UC_UpdateStatus
    Tech --> UC_Complete

    %% Admin Relations
    Admin --> UC_Login
    Admin --> UC_Logout
    Admin --> UC_Dashboard
    Admin --> UC_ManageAll
    Admin --> UC_ViewDetail
    Admin --> UC_Assign
```

---

## 3. สถาปัตยกรรมระบบ (System Architecture Diagram)

ระบบทำงานบนโครงสร้าง **Laravel MVC Architecture** ควบคู่กับ **CheckRole Middleware** ในการคัดกรองคำขอ:

```mermaid
graph TD
    Client["🌐 Client Browser (Chrome / Edge / Safari)"]
    
    subgraph WebServer["Laravel 12 Application Layer"]
        Routing["🛣️ Route Layer (routes/web.php)"]
        AuthMiddleware["🛡️ CheckRole Middleware (RBAC Guard)"]
        
        subgraph Controllers["🎮 Controller Layer"]
            AuthController["AuthController<br>(เข้าสู่ระบบ / สมัครสมาชิก)"]
            TicketController["TicketController<br>(สร้างงาน / ยกเลิก / รีวิว)"]
            TechController["TechnicianController<br>(รับงาน / อัปเดต / ปิดงาน)"]
            AdminController["AdminController<br>(แดชบอร์ด KPI / จ่ายงาน)"]
        end

        subgraph Models["📦 Eloquent ORM Model Layer"]
            M_User["User Model"]
            M_Ticket["RepairRequest Model"]
            M_Log["RepairLog Model"]
            M_Review["Review Model"]
            M_Category["Category Model"]
        end

        subgraph Views["🖼️ View Layer (Blade Templates & Tailwind CSS)"]
            Layout["Master Layout (app.blade.php)"]
            V_Tickets["Tickets Views (index, create, show)"]
            V_Tech["Technician View (tasks)"]
            V_Admin["Admin Views (dashboard, tickets)"]
        end
    end

    subgraph StorageLayer["💾 Storage & Database Layer"]
        DB[("🗄️ SQLite / MySQL Database")]
        Storage["📁 Public Storage Link<br>(storage/app/public/repairs)"]
    end

    Client -->|"HTTP Request (GET/POST)"| Routing
    Routing --> AuthMiddleware
    AuthMiddleware -->|"Verified Request"| Controllers
    
    Controllers -->|"Query / Insert / Update"| Models
    Models -->|"SQL Queries"| DB
    Controllers -->|"Upload Image"| Storage
    
    Controllers -->|"Pass Data (compact)"| Views
    Views -.-> Layout
    Views -->|"Rendered HTML + Tailwind CSS"| Client
```

---

## 4. แผนผังฐานข้อมูลและความสัมพันธ์ (Entity Relationship Diagram - ERD)

ฐานข้อมูลถูกออกแบบตามหลัก Normalization มีความสัมพันธ์ 1:N และ 1:1 ครอบคลุมการทำงานทุกส่วน:

```mermaid
erDiagram
    USERS ||--o{ REPAIR_REQUESTS : "แจ้งเรื่อง (user_id)"
    USERS ||--o{ REPAIR_REQUESTS : "ช่างที่ดูแล (assigned_to)"
    USERS ||--o{ REPAIR_LOGS : "ผู้บันทึก (user_id)"
    USERS ||--o{ REVIEWS : "ผู้ประเมิน (user_id)"

    CATEGORIES ||--o{ REPAIR_REQUESTS : "จัดหมวดหมู่ (category_id)"

    REPAIR_REQUESTS ||--o{ REPAIR_LOGS : "มีประวัติไทม์ไลน์ (repair_request_id)"
    REPAIR_REQUESTS ||--o| REVIEWS : "มีการประเมินผล (repair_request_id)"

    USERS {
        bigint id PK "รหัสผู้ใช้"
        string name "ชื่อ-นามสกุล"
        string email UK "อีเมลเข้าสู่ระบบ"
        string password "รหัสผ่านแฮช"
        string role "บทบาท: admin, technician, user"
        string phone "เบอร์โทรศัพท์"
        string department "แผนก/สังกัด"
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK "รหัสหมวดหมู่"
        string name "ชื่อหมวดหมู่"
        string icon "ไอคอนสัญลักษณ์"
        text description "คำอธิบายหมวดหมู่"
        timestamp created_at
        timestamp updated_at
    }

    REPAIR_REQUESTS {
        bigint id PK "รหัสคำขอ"
        string ticket_number UK "รหัสอ้างอิง REP-YYYYMM-XXXX"
        bigint user_id FK "ผู้แจ้งเรื่อง"
        bigint category_id FK "หมวดหมู่งาน"
        string title "หัวข้อปัญหา"
        string location "สถานที่/ห้อง"
        enum urgency "ระดับ: low, normal, high, urgent"
        text description "รายละเอียดอาการเสีย"
        enum status "สถานะ 7 ขั้นตอน"
        bigint assigned_to FK "ช่างที่รับผิดชอบ"
        datetime assigned_at "เวลาที่มอบหมาย"
        datetime completed_at "เวลาที่ซ่อมเสร็จ"
        text technician_notes "บันทึกจากช่าง"
        decimal repair_cost "ค่าใช้จ่าย/อะไหล่"
        string image_before "Path รูปก่อนซ่อม"
        string image_after "Path รูปหลังซ่อม"
        timestamp created_at
        timestamp updated_at
    }

    REPAIR_LOGS {
        bigint id PK "รหัสบันทึก"
        bigint repair_request_id FK "รหัสคำขอแจ้งซ่อม"
        bigint user_id FK "ผู้ดำเนินการ"
        string status_from "สถานะเดิม"
        string status_to "สถานะใหม่"
        text comment "ข้อความบันทึกเหตุการณ์"
        timestamp created_at
        timestamp updated_at
    }

    REVIEWS {
        bigint id PK "รหัสรีวิว"
        bigint repair_request_id FK "รหัสคำขอแจ้งซ่อม (Unique)"
        bigint user_id FK "ผู้ให้คะแนน"
        tinyint rating "คะแนนดาว (1 - 5)"
        text comment "ข้อเสนอแนะเพิ่มเติม"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 5. วงจรสถานะงานแจ้งซ่อม (Ticket Lifecycle & State Machine)

สถานะงานซ่อมมีทั้งหมด 7 สถานะ ดำเนินการตามลำดับขั้นตอน (Workflow Lifecycle) ดังนี้:

```mermaid
stateDiagram-v2
    [*] --> pending: 1. ผู้ใช้สร้างใบแจ้งซ่อมใหม่

    pending --> assigned: 2. แอดมินมอบหมายช่าง
    pending --> cancelled: ผู้ใช้กดยกเลิกเอง (ก่อนจ่ายงาน)

    assigned --> in_progress: 3. ช่างกดรับงาน & เริ่มซ่อม
    in_progress --> waiting_parts: 4. ช่างรออะไหล่ / อุปกรณ์
    waiting_parts --> in_progress: 5. อะไหล่มาถึง & ซ่อมต่อ

    in_progress --> completed: 6. ช่างซ่อมเสร็จ + แนบภาพหลังซ่อม
    completed --> closed: 7. ผู้ใช้ตรวจรับงาน + ให้คะแนนดาว

    cancelled --> [*]
    closed --> [*]
```

---

## 6. ลำดับการทำงานของระบบ (Sequence Diagrams)

### 6.1 ขั้นตอนที่ 1: ผู้ใช้งานส่งเรื่องแจ้งซ่อมใหม่ (Create Ticket)
```mermaid
sequenceDiagram
    autonumber
    actor User as 👤 ผู้ใช้งาน (User)
    participant Browser as 🌐 Web Browser
    participant TicketCtrl as 🎮 TicketController
    participant Storage as 📁 Storage (Disk Public)
    participant DB as 🗄️ Database

    User->>Browser: กรอกฟอร์มแจ้งซ่อม + แนบรูปภาพ
    Browser->>TicketCtrl: POST /tickets (พร้อม CSRF & Multipart File)
    TicketCtrl->>TicketCtrl: Validate ข้อมูล (หมวดหมู่, ความเร่งด่วน, รูปภาพ <= 5MB)
    TicketCtrl->>Storage: บันทึกรูปภาพลง storage/repairs
    Storage-->>TicketCtrl: คืนค่า imagePath
    TicketCtrl->>TicketCtrl: Generate Ticket Number (REP-YYYYMM-XXXX)
    TicketCtrl->>DB: บันทึกลง repair_requests (status: 'pending')
    TicketCtrl->>DB: บันทึก Timeline เริ่มต้นลง repair_logs
    TicketCtrl-->>Browser: Redirect ไปยัง /tickets/{id} พร้อม Flash Success Alert
    Browser-->>User: แสดงหน้ารายละเอียดงานซ่อม
```

### 6.2 ขั้นตอนที่ 2: แอดมินตรวจสอบและมอบหมายงานให้ช่าง (Assign Technician)
```mermaid
sequenceDiagram
    autonumber
    actor Admin as 👑 แอดมิน (Admin)
    participant AdminCtrl as 🎮 AdminController
    participant DB as 🗄️ Database

    Admin->>AdminCtrl: ดูรายการงานสถานะ Pending
    Admin->>AdminCtrl: เลือกช่างซ่อม + สั่งการ POST /admin/tickets/{id}/assign
    AdminCtrl->>AdminCtrl: ตรวจสอบว่าช่างมี Role: technician จริง
    AdminCtrl->>DB: อัปเดต repair_requests (assigned_to, assigned_at, status: 'assigned')
    AdminCtrl->>DB: บันทึก Timeline "แอดมินมอบหมายงานให้ช่าง..." ลง repair_logs
    AdminCtrl-->>Admin: แสดงข้อความแจ้งเตือน "มอบหมายงานสำเร็จ"
```

### 6.3 ขั้นตอนที่ 3: ช่างซ่อมดำเนินการและปิดงาน (Technician Work & Complete)
```mermaid
sequenceDiagram
    autonumber
    actor Tech as 🔧 ช่างซ่อม (Technician)
    participant TechCtrl as 🎮 TechnicianController
    participant Storage as 📁 Storage
    participant DB as 🗄️ Database

    Tech->>TechCtrl: ดู My Tasks และกดเริ่มงาน (status: 'in_progress')
    TechCtrl->>DB: อัปเดต status เป็น in_progress + บันทึก Timeline
    Note over Tech: ช่างเข้าดำเนินการซ่อมหน้างานจริง...
    Tech->>TechCtrl: ส่งรูปหลังซ่อม + ค่าใช้จ่าย + status: 'completed'
    TechCtrl->>Storage: อัปโหลดรูปภาพหลังซ่อม (image_after)
    TechCtrl->>DB: อัปเดต repair_requests (completed_at, technician_notes, repair_cost)
    TechCtrl->>DB: บันทึก Timeline ปิดงาน
    TechCtrl-->>Tech: แจ้งเตือนอัปเดตงานเสร็จสิ้น
```

### 6.4 ขั้นตอนที่ 4: ผู้ใช้ตรวจรับงานและประเมินความพึงพอใจ (Review & Close)
```mermaid
sequenceDiagram
    autonumber
    actor User as 👤 ผู้ใช้งาน (User)
    participant TicketCtrl as 🎮 TicketController
    participant DB as 🗄️ Database

    User->>TicketCtrl: เข้าดูงานที่สถานะเป็น 'completed'
    User->>TicketCtrl: ให้คะแนน 1-5 ดาว + คอมเมนต์ POST /tickets/{id}/review
    TicketCtrl->>DB: บันทึกคะแนนลงตาราง reviews
    TicketCtrl->>DB: อัปเดตสถานะงานเป็น 'closed' (ปิดงานสมบูรณ์)
    TicketCtrl->>DB: บันทึก Timeline ตรวจรับงาน
    TicketCtrl-->>User: แสดงการประเมินดาวสำเร็จ & ปิดงาน
```

---

## 7. พจนานุกรมข้อมูล (Data Dictionary & Schema)

### 7.1 ตาราง `repair_requests`
| ชื่อคอลัมน์ | ชนิดข้อมูล | คุณสมบัติ | คำอธิบาย |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | รหัสประจำรายการ |
| `ticket_number` | VARCHAR(50) | Unique, Not Null | รหัสอ้างอิง เช่น `REP-202610-0001` |
| `user_id` | BIGINT | FK (users.id), Cascade | รหัสผู้แจ้งซ่อม |
| `category_id` | BIGINT | FK (categories.id) | รหัสหมวดหมู่งานซ่อม |
| `title` | VARCHAR(255) | Not Null | หัวข้อปัญหา / ชื่ออุปกรณ์ |
| `location` | VARCHAR(255) | Not Null | สถานที่ / อาคาร / ชั้น / ห้อง |
| `urgency` | ENUM | Not Null, Default: 'normal' | ระดับความเร่งด่วน (`low`, `normal`, `high`, `urgent`) |
| `description` | TEXT | Not Null | รายละเอียดอาการชำรุด |
| `status` | ENUM | Not Null, Default: 'pending' | สถานะงาน 7 ขั้นตอน |
| `assigned_to` | BIGINT | FK (users.id), Nullable | ช่างผู้รับผิดชอบ |
| `assigned_at` | DATETIME | Nullable | วันเวลาที่จ่ายงานให้ช่าง |
| `completed_at` | DATETIME | Nullable | วันเวลาที่ช่างซ่อมเสร็จ |
| `technician_notes`| TEXT | Nullable | บันทึกสิ่งที่ซ่อม / อะไหล่ที่เปลี่ยน |
| `repair_cost` | DECIMAL(10,2)| Default: 0.00 | ค่าใช้จ่ายรวม / ค่าอะไหล่ |
| `image_before` | VARCHAR(255) | Nullable | พาธไฟล์ภาพถ่ายก่อนซ่อม |
| `image_after` | VARCHAR(255) | Nullable | พาธไฟล์ภาพถ่ายหลังซ่อม |
| `created_at` | TIMESTAMP | Nullable | วันเวลาที่ส่งคำขอ |
| `updated_at` | TIMESTAMP | Nullable | วันเวลาที่แก้ไขล่าสุด |

---

## 8. ตารางเส้นทางและการควบคุมสิทธิ์ (Route & Permission Matrix)

| Method | URI Path | Route Name | Controller Action | สิทธิ์ที่เข้าถึงได้ | หน้าที่การทำงาน |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **GET** | `/login` | `login` | `AuthController@showLogin` | Guest | แสดงฟอร์มเข้าสู่ระบบ |
| **POST** | `/login` | `login.submit` | `AuthController@login` | Guest | ยืนยันการเข้าสู่ระบบ |
| **POST** | `/logout` | `logout` | `AuthController@logout` | Authenticated | ออกจากระบบ |
| **GET** | `/register` | `register` | `AuthController@showRegister` | Guest | แสดงฟอร์มสมัครสมาชิก |
| **POST** | `/register` | - | `AuthController@register` | Guest | บันทึกการสมัครสมาชิกใหม่ |
| **GET** | `/dashboard`| `dashboard` | Inline Closure | Authenticated | กระจายหน้าแรกตาม Role |
| **GET** | `/tickets` | `tickets.index` | `TicketController@index` | `user`, `admin` | รายการคำขอของตนเอง |
| **GET** | `/tickets/create` | `tickets.create` | `TicketController@create` | `user`, `admin` | ฟอร์มสร้างใบแจ้งซ่อมใหม่ |
| **POST** | `/tickets` | `tickets.store` | `TicketController@store` | `user`, `admin` | บันทึกการแจ้งซ่อมใหม่ |
| **GET** | `/tickets/{ticket}` | `tickets.show` | `TicketController@show` | ทุก Role ที่เกี่ยวข้อง | ดูรายละเอียด & Timeline |
| **POST** | `/tickets/{ticket}/cancel` | `tickets.cancel` | `TicketController@cancel` | `user` | ขอยกเลิกงาน (ตอน Pending) |
| **POST** | `/tickets/{ticket}/review` | `tickets.review` | `TicketController@review` | `user` | ให้คะแนนประเมินดาวปิดงาน |
| **GET** | `/technician/tasks` | `tech.tasks` | `TechnicianController@index` | `technician` | รายการงานที่ช่างได้รับมอบหมาย |
| **POST** | `/technician/tasks/{ticket}/status` | `tech.updateStatus` | `TechnicianController@updateStatus` | `technician`, `admin` | อัปเดตสถานะงาน / ปิดงาน |
| **GET** | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | `admin` | แดชบอร์ดสรุปสถิติ & KPI |
| **GET** | `/admin/tickets` | `admin.tickets.index` | `AdminController@tickets` | `admin` | จัดการงานทั้งหมด & ค้นหา |
| **POST** | `/admin/tickets/{ticket}/assign` | `admin.tickets.assign` | `AdminController@assign` | `admin` | มอบหมายงานให้ช่าง |

---

## 9. การทดสอบระบบอัตโนมัติ (Automated Testing & Quality Assurance)

ระบบมาพร้อมชุดทดสอบอัตโนมัติ (Automated Feature Tests) ทั้งหมด **28 Tests (89 Assertions)** รันผ่าน 100%:

```bash
php artisan test
```

### รายการทดสอบหลักใน `RepairSystemContractTest.php`:
1. `test_login_redirects_user_to_tickets_index`: ตรวจสอบการนำทางของ User ไป `/tickets`
2. `test_login_redirects_technician_to_technician_tasks`: ตรวจสอบการนำทางของ ช่าง ไป `/technician/tasks`
3. `test_login_redirects_admin_to_admin_dashboard`: ตรวจสอบการนำทางของ แอดมิน ไป `/admin/dashboard`
4. `test_user_can_create_ticket_with_image`: ตรวจสอบการบันทึกงานซ่อมพร้อมอัปโหลดรูปภาพ
5. `test_user_can_cancel_pending_ticket`: ตรวจสอบการยกเลิกงานตอน Pending
6. `test_admin_can_assign_technician_to_ticket`: ตรวจสอบการจ่ายงานให้ช่าง
7. `test_technician_can_update_status_and_complete_job`: ตรวจสอบการเปลี่ยนสถานะและการแนบรูปหลังซ่อม
8. `test_user_can_review_completed_ticket_and_close_it`: ตรวจสอบการให้คะแนนดาว 1-5 และเปลี่ยนสถานะเป็น Closed
9. `test_role_authorization_middleware_protects_routes`: ป้องกันการข้ามสิทธิ์ตอบกลับ 403 Forbidden
10. `test_user_cannot_view_other_user_ticket`: ป้องกันไม่ให้ผู้ใช้แอบเปิดดูงานของผู้อื่น
11. `test_user_cannot_cancel_already_assigned_ticket`: ป้องกันไม่ให้ยกเลิกงานที่จ่ายงานไปแล้ว
12. `test_admin_can_view_tickets_and_filter`: ตรวจสอบฟังก์ชันการค้นหาและกรองงานของแอดมิน

---

## 10. คู่มือการติดตั้งและข้อมูลบัญชีทดสอบ (Setup & Test Credentials)

### การติดตั้งและเริ่มใช้งาน (Installation Steps)
```bash
# 1. คัดลอกไฟล์ Environment
cp .env.example .env

# 2. สร้าง Application Key
php artisan key:generate

# 3. เชื่อมโยง Storage Link สำหรับรูปภาพ
php artisan storage:link

# 4. รัน Database Migration และจำลองข้อมูลเริ่มต้น (Seeder)
php artisan migrate:fresh --seed

# 5. เริ่มต้นเซิร์ฟเวอร์
php artisan serve
```

### ข้อมูลผู้ใช้งานสำหรับทดสอบระบบ (รหัสผ่านคือ `password` ทุกบัญชี)
| บทบาท (Role) | อีเมล (Email) | รหัสผ่าน | สิทธิ์และการทดสอบ |
| :--- | :--- | :--- | :--- |
| **ผู้ดูแลระบบ (Admin)** | `admin@repair.test` | `password` | เข้าดูแดชบอร์ด KPI, จ่ายงานให้ช่าง, กรองค้นหางานทั้งหมด |
| **ช่างซ่อม (Technician)** | `tech@repair.test` | `password` | รับงานซ่อม, อัปเดตสถานะ, แนบรูปหลังซ่อม, ระบุค่าใช้จ่าย |
| **ผู้ใช้งานทั่วไป (User)** | `user@repair.test` | `password` | แจ้งซ่อมใหม่, ติดตามประวัติ, ตรวจรับงานและให้คะแนนดาว |
