<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\RepairLog;
use App\Models\RepairRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Class DatabaseSeeder
 * คลาสสำหรับจำลองข้อมูลเริ่มต้นลงฐานข้อมูล (Database Seeder)
 * ข้อมูลที่จำลองประกอบด้วย:
 * 1. ผู้ใช้งานทดสอบครบ 3 กลุ่มบทบาท (Admin, Technician 2 คน, User) รหัสผ่าน 'password'
 * 2. หมวดหมู่งานซ่อมบำรุง 5 หมวดหมู่หลัก
 * 3. ตัวอย่างงานแจ้งซ่อมครบทุกสถานะ Lifecycle (Pending, Assigned, In Progress, Completed, Closed)
 *    เพื่อให้หน้ารายงาน Dashboard สถิติ และการทดสอบระบบสมบูรณ์ตั้งแต่วินาทีแรกที่ติดตั้ง
 */
class DatabaseSeeder extends Seeder
{
    /**
     * ดำเนินการสร้างข้อมูลจำลองลงฐานข้อมูล
     */
    public function run(): void
    {
        // ==========================================
        // 1. สร้างบัญชีผู้ใช้งานทดสอบ (Default Users)
        // ==========================================

        // 1.1 บัญชีผู้ดูแลระบบ (Admin)
        $admin = User::firstOrCreate(
            ['email' => 'admin@repair.test'],
            [
                'name' => 'ผู้ดูแลระบบ (Admin)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '081-111-2222',
                'department' => 'ฝ่ายบริหารและส่วนกลาง',
            ]
        );

        // 1.2 บัญชีช่างซ่อมคนที่ 1 (Technician 1)
        $tech1 = User::firstOrCreate(
            ['email' => 'tech@repair.test'],
            [
                'name' => 'ช่างสมชาย (Technician)',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'phone' => '082-333-4444',
                'department' => 'ฝ่ายซ่อมบำรุง',
            ]
        );

        // 1.3 บัญชีช่างซ่อมคนที่ 2 (Technician 2)
        $tech2 = User::firstOrCreate(
            ['email' => 'tech2@repair.test'],
            [
                'name' => 'ช่างวิชัย (Technician 2)',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'phone' => '083-555-6666',
                'department' => 'ฝ่ายไอทีและระบบเครือข่าย',
            ]
        );

        // 1.4 บัญชีผู้ใช้งานทั่วไป (User)
        $user = User::firstOrCreate(
            ['email' => 'user@repair.test'],
            [
                'name' => 'คุณพนักงานทดสอบ (User)',
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone' => '089-777-8888',
                'department' => 'ฝ่ายบุคคลและการเงิน',
            ]
        );

        // ==========================================
        // 2. สร้างหมวดหมู่งานซ่อมบำรุง (Categories)
        // ==========================================
        $categoriesData = [
            [
                'name' => 'ระบบไฟฟ้าและแสงสว่าง',
                'icon' => 'bolt',
                'description' => 'หลอดไฟ ปลั๊กไฟ สายไฟ ไฟช็อต สวิตช์ไฟ',
            ],
            [
                'name' => 'ระบบประปาและสุขาภิบาล',
                'icon' => 'faucet',
                'description' => 'ก๊อกน้ำ ท่อน้ำตัน น้ำรั่วซึม สุขภัณฑ์ชำรุด',
            ],
            [
                'name' => 'เครื่องปรับอากาศและความเย็น',
                'icon' => 'snowflake',
                'description' => 'แอร์ไม่เย็น น้ำแอร์หยด เสียงดัง มีกลิ่นอับ',
            ],
            [
                'name' => 'อุปกรณ์คอมพิวเตอร์และระบบเครือข่าย',
                'icon' => 'desktop',
                'description' => 'คอมพิวเตอร์เปิดไม่ติด จอฟ้า อินเทอร์เน็ตใช้งานไม่ได้ ปริ้นเตอร์',
            ],
            [
                'name' => 'อาคาร สถานที่ และเฟอร์นิเจอร์',
                'icon' => 'building',
                'description' => 'ประตู หน้าต่าง เก้าอี้ โต๊ะ ผนัง ฝ้าเพดานชำรุด',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $categories[] = Category::firstOrCreate(['name' => $catData['name']], $catData);
        }

        // ==========================================
        // 3. จำลองงานแจ้งซ่อมครบทุกสถานะ (Ticket Lifecycle States)
        // ==========================================

        // เคสที่ 1: สถานะ "รอดำเนินการ" (Pending) - รอแอดมินมอบหมายช่าง
        $t1 = RepairRequest::firstOrCreate(
            ['ticket_number' => 'REP-202610-0001'],
            [
                'user_id' => $user->id,
                'category_id' => $categories[0]->id,
                'title' => 'หลอดไฟเพดานกะพริบไม่ติด',
                'location' => 'อาคาร 2 ชั้น 3 ห้อง 305',
                'urgency' => 'normal',
                'description' => 'หลอดไฟยาวตรงกลางห้องกะพริบถี่ๆ แล้วดับไป ใช้งานทำงานลำบากมากครับ',
                'status' => 'pending',
            ]
        );
        RepairLog::firstOrCreate(
            ['repair_request_id' => $t1->id, 'status_to' => 'pending'],
            [
                'user_id' => $user->id,
                'status_from' => null,
                'comment' => 'ผู้ใช้งานส่งเรื่องแจ้งซ่อมเข้ามาในระบบ',
            ]
        );

        // เคสที่ 2: สถานะ "มอบหมายช่างแล้ว" (Assigned) - แอดมินจ่ายงานให้ช่างสมชาย
        $t2 = RepairRequest::firstOrCreate(
            ['ticket_number' => 'REP-202610-0002'],
            [
                'user_id' => $user->id,
                'category_id' => $categories[1]->id,
                'title' => 'ก๊อกน้ำอ่างล้างมือรั่วซึม',
                'location' => 'อาคาร 1 ชั้น 2 ห้องน้ำชาย',
                'urgency' => 'high',
                'description' => 'น้ำหยดตลอดเวลา ปิดสนิทแล้วก็ยังไหล นองเต็มพื้น',
                'status' => 'assigned',
                'assigned_to' => $tech1->id,
                'assigned_at' => now()->subHours(5),
            ]
        );
        RepairLog::firstOrCreate(
            ['repair_request_id' => $t2->id, 'status_to' => 'pending'],
            ['user_id' => $user->id, 'status_from' => null, 'comment' => 'แจ้งเรื่องแจ้งซ่อม']
        );
        RepairLog::firstOrCreate(
            ['repair_request_id' => $t2->id, 'status_to' => 'assigned'],
            ['user_id' => $admin->id, 'status_from' => 'pending', 'comment' => 'แอดมินมอบหมายงานให้ช่างสมชายเข้าตรวจสอบ']
        );

        // เคสที่ 3: สถานะ "กำลังดำเนินการ" (In Progress) - ช่างกดรับงานและเข้าตรวจสอบหน้างาน
        $t3 = RepairRequest::firstOrCreate(
            ['ticket_number' => 'REP-202610-0003'],
            [
                'user_id' => $user->id,
                'category_id' => $categories[2]->id,
                'title' => 'เครื่องปรับอากาศมีแต่น้ำแข็งเกาะ ไม่เย็น',
                'location' => 'อาคาร 1 ชั้น 4 ห้องประชุมใหญ่',
                'urgency' => 'urgent',
                'description' => 'แอร์เปิด 20 องศาแต่มีลมร้อนออกมา มีน้ำแข็งเกาะที่คอยล์เย็น มีประชุมบ่ายนี้',
                'status' => 'in_progress',
                'assigned_to' => $tech1->id,
                'assigned_at' => now()->subHours(4),
            ]
        );
        RepairLog::firstOrCreate(
            ['repair_request_id' => $t3->id, 'status_to' => 'in_progress'],
            ['user_id' => $tech1->id, 'status_from' => 'assigned', 'comment' => 'ช่างเข้าตรวจสอบหน้างาน วัดระดับน้ำยาแอร์']
        );

        // เคสที่ 4: สถานะ "ซ่อมเสร็จสิ้น" (Completed) - ช่างบันทึกอะไหล่และค่าใช้จ่าย รอผู้ใช้ประเมินดาว
        $t4 = RepairRequest::firstOrCreate(
            ['ticket_number' => 'REP-202610-0004'],
            [
                'user_id' => $user->id,
                'category_id' => $categories[3]->id,
                'title' => 'เครื่องปริ้นเตอร์กระดาษติดและดึงไม่เข้า',
                'location' => 'อาคาร 2 ชั้น 1 แผนกธุรการ',
                'urgency' => 'normal',
                'description' => 'ลูกกลิ้งดึงกระดาษเบี้ยว ดึงกระดาษเข้าไปติดในฟิวเซอร์',
                'status' => 'completed',
                'assigned_to' => $tech2->id,
                'assigned_at' => now()->subDays(1),
                'completed_at' => now()->subHours(2),
                'technician_notes' => 'แกะทำความสะอาดลูกยางดึงกระดาษ (Pick-up roller) และนำเศษกระดาษที่ติดค้างออก ทดสอบพิมพ์ 20 แผ่นผ่านปกติ',
                'repair_cost' => 150.00,
            ]
        );
        RepairLog::firstOrCreate(
            ['repair_request_id' => $t4->id, 'status_to' => 'completed'],
            ['user_id' => $tech2->id, 'status_from' => 'in_progress', 'comment' => 'ซ่อมเสร็จสิ้น ทดสอบการใช้งานเรียบร้อย']
        );

        // เคสที่ 5: สถานะ "ปิดงานเรียบร้อย" (Closed) - ผู้ใช้ตรวจรับงานและประเมินความพึงพอใจ 5 ดาว
        $t5 = RepairRequest::firstOrCreate(
            ['ticket_number' => 'REP-202610-0005'],
            [
                'user_id' => $user->id,
                'category_id' => $categories[4]->id,
                'title' => 'บานพับประตูห้องทำงานหลุด ปิดไม่ได้',
                'location' => 'อาคาร 1 ชั้น 3 ห้อง 301',
                'urgency' => 'high',
                'description' => 'บานพับตัวบนน็อตหลุด ทำให้ประตูดิ่งเอียง ปิดไม่สนิท',
                'status' => 'closed',
                'assigned_to' => $tech1->id,
                'assigned_at' => now()->subDays(3),
                'completed_at' => now()->subDays(2),
                'technician_notes' => 'เปลี่ยนน็อตยึดเกลียวปล่อยใหม่และปรับระดับบานพับให้สมดุล',
                'repair_cost' => 80.00,
            ]
        );
        RepairLog::firstOrCreate(
            ['repair_request_id' => $t5->id, 'status_to' => 'closed'],
            ['user_id' => $user->id, 'status_from' => 'completed', 'comment' => 'ผู้ใช้ตรวจรับงานและประเมินความพึงพอใจ']
        );
        Review::firstOrCreate(
            ['repair_request_id' => $t5->id],
            [
                'user_id' => $user->id,
                'rating' => 5,
                'comment' => 'ช่างมาไวมาก สุภาพ และซ่อมได้เรียบร้อย ประตูกลับมาใช้งานได้ดีเหมือนเดิมครับ',
            ]
        );
    }
}
