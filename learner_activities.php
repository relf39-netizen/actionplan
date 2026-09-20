<?php
$pageTitle = 'กิจกรรมพัฒนาผู้เรียน (4 กิจกรรมหลัก)';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$students = getStudentsData();
$totalStudents = array_sum(array_column($students, 'total_count'));

$activities = [
    [
        'id' => 1,
        'name' => '1. กิจกรรมวิชาการ (ค่ายวิชาการ / ติวเข้ม / แข่งขันความสามารถ)',
        'percentage' => 30.0,
        'rate_per_head' => 138.0,
        'description' => 'ส่งเสริมความเป็นเลิศทางวิชาการ ค่ายภาษาไทย ภาษาอังกฤษ วิทยาศาสตร์และคณิตศาสตร์',
        'allocated' => round($totalStudents * 460 * 0.30)
    ],
    [
        'id' => 2,
        'name' => '2. กิจกรรมคุณธรรม จริยธรรม / ลูกเสือ เนตรนารี / ยุวกาชาด',
        'percentage' => 25.0,
        'rate_per_head' => 115.0,
        'description' => 'การเข้าค่ายพักแรมลูกเสือ-เนตรนารี กิจกรรมค่ายคุณธรรม และปฏิบัติธรรมวันสำคัญ',
        'allocated' => round($totalStudents * 460 * 0.25)
    ],
    [
        'id' => 3,
        'name' => '3. กิจกรรมทัศนศึกษาตามแหล่งเรียนรู้ (1 ครั้ง/ปีการศึกษา)',
        'percentage' => 30.0,
        'rate_per_head' => 138.0,
        'description' => 'ยานพาหนะ ค่าผ่านทาง ค่าประกันอุบัติเหตุ และค่าเข้าชมพิพิธภัณฑ์/แหล่งเรียนรู้',
        'allocated' => round($totalStudents * 460 * 0.30)
    ],
    [
        'id' => 4,
        'name' => '4. กิจกรรมการจัดการเรียนรู้เทคโนโลยีสารสนเทศ (ICT / AI Literacy)',
        'percentage' => 15.0,
        'rate_per_head' => 69.0,
        'description' => 'การพัฒนาทักษะคอมพิวเตอร์ การเขียนโปรแกรม Coding และทักษะดิจิทัลในศตวรรษที่ 21',
        'allocated' => round($totalStudents * 460 * 0.15)
    ],
];

$totalActivityBudget = $totalStudents * 460;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">งบกิจกรรมพัฒนาผู้เรียน 4 กิจกรรมหลัก สพฐ.</h2>
            <p class="text-xs text-slate-500">จัดสรรตามเกณฑ์ 460 บาท/คน/ปี สำหรับนักเรียน <?= number_format($totalStudents) ?> คน</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 px-4 py-2 rounded-xl text-right">
            <span class="text-[11px] font-bold text-purple-700 uppercase block">งบกิจกรรมพัฒนาผู้เรียนรวม</span>
            <span class="text-xl font-bold font-mono text-purple-900"><?= number_format($totalActivityBudget, 2) ?> บาท</span>
        </div>
    </div>

    <!-- 4 Activities Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($activities as $act): ?>
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                            สัดส่วน <?= $act['percentage'] ?>%
                        </span>
                        <span class="font-mono text-xs font-bold text-slate-500">
                            <?= number_format($act['rate_per_head'], 2) ?> บ./คน
                        </span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($act['name']) ?></h3>
                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($act['description']) ?></p>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">งบประมาณที่ได้รับจัดสรร:</span>
                    <span class="font-mono font-bold text-base text-purple-900"><?= number_format($act['allocated'], 2) ?> บาท</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
