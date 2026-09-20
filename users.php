<?php
$pageTitle = 'ผู้ใช้งานระบบ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$users = [
    [
        'id' => 1,
        'username' => 'admin',
        'full_name' => 'นายพิเชษฐ์ ปัญญาวงศ์',
        'role' => 'admin',
        'department' => 'ฝ่ายบริหารงานงบประมาณ',
        'position' => 'ครูชำนาญการพิเศษ / หัวหน้างานแผนงาน',
        'email' => 'pichet_admin@school.ac.th'
    ],
    [
        'id' => 2,
        'username' => 'director',
        'full_name' => 'ดร.สมศักดิ์ พัฒนศึกษา',
        'role' => 'director',
        'department' => 'ฝ่ายบริหารทั่วไป',
        'position' => 'ผู้อำนวยการเชี่ยวชาญ คศ.4',
        'email' => 'somsak_director@school.ac.th'
    ],
    [
        'id' => 3,
        'username' => 'teacher',
        'full_name' => 'นางสาวกนกพร ใจมั่น',
        'role' => 'teacher',
        'department' => 'ฝ่ายบริหารงานวิชาการ',
        'position' => 'ครูชำนาญการ / หัวหน้ากลุ่มสาระภาษาไทย',
        'email' => 'kanokporn_teacher@school.ac.th'
    ],
];
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">ผู้ใช้งานระบบและการกำหนดสิทธิ์ (RBAC)</h2>
            <p class="text-xs text-slate-500">จัดการบัญชีผู้ใช้งาน สิทธิ์ผู้อำนวยการ (อนุมัติโครงการ) หัวหน้าแผนงาน และครูผู้รับผิดชอบโครงการ</p>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-3 px-4 font-semibold">ชื่อผู้ใช้ (Username)</th>
                        <th class="py-3 px-4 font-semibold">ชื่อ - สกุล</th>
                        <th class="py-3 px-4 font-semibold">ตำแหน่ง / หน้าที่</th>
                        <th class="py-3 px-4 font-semibold">ฝ่ายบริหาร</th>
                        <th class="py-3 px-4 font-semibold text-center">สิทธิ์การใช้งาน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="py-3 px-4 font-semibold text-slate-900"><?= htmlspecialchars($u['full_name']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($u['position']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($u['department']) ?></td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">ผู้ดูแลระบบ / แผนงาน</span>
                                <?php elseif ($u['role'] === 'director'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">ผู้อำนวยการ (ผู้อนุมัติ)</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">ครูผู้เสนอโครงการ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
