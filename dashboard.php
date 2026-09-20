<?php
$pageTitle = 'แดชบอร์ดภาพรวม';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$students = getStudentsData();
$revenues = getRevenuesData();
$allocations = getBudgetAllocations();
$projects = getProjectsData();

$totalStudents = array_sum(array_column($students, 'total_count'));
$totalRevenue = array_sum(array_column($revenues, 'calculated_amount'));
$totalAllocated = array_sum(array_column($allocations, 'allocated_amount'));
$totalSpent = array_sum(array_column($allocations, 'spent_amount'));
$totalRemaining = $totalAllocated - $totalSpent;
$spentPercent = $totalAllocated > 0 ? round(($totalSpent / $totalAllocated) * 100, 1) : 0;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white rounded-2xl p-6 shadow-md mb-6 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold text-blue-200 mb-2">
                    <i data-lucide="award" class="w-3.5 h-3.5"></i>
                    <span>ระบบบริหารแผนปฏิบัติการประจำปีตามมาตรฐาน สพฐ.</span>
                </span>
                <h2 class="text-xl sm:text-2xl font-bold">ยินดีต้อนรับสู่ระบบงานแผนงานและงบประมาณ</h2>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl">
                    <?= htmlspecialchars($school['name']) ?> • ปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="ai_project_writer.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-lg transition-all">
                    <i data-lucide="bot" class="w-4 h-4 text-amber-300"></i>
                    <span>เขียนโครงการด้วย AI</span>
                </a>
                <a href="projects.php" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-all border border-white/20">
                    <i data-lucide="folder-plus" class="w-4 h-4"></i>
                    <span>โครงการทั้งหมด</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Key Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Revenue -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">ประมาณการรายรับรวม</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                    <i data-lucide="calculator" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="text-2xl font-bold font-mono text-blue-900 mt-2">
                <?= number_format($totalRevenue, 2) ?>
            </div>
            <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                <span>จาก 11 หมวดรายรับ สพฐ.</span>
                <a href="revenue.php" class="text-blue-600 font-semibold hover:underline">ดูรายละเอียด &rarr;</a>
            </div>
        </div>

        <!-- Card 2: Allocated Budget -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">งบประมาณที่จัดสรร</span>
                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center">
                    <i data-lucide="pie-chart" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-2">
                <?= number_format($totalAllocated, 2) ?>
            </div>
            <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                <span>กระจาย 4 ฝ่ายบริหาร</span>
                <a href="budget.php" class="text-indigo-600 font-semibold hover:underline">ปรับสัดส่วน &rarr;</a>
            </div>
        </div>

        <!-- Card 3: Spent Budget -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">เบิกจ่ายแล้ว (ใช้จริง)</span>
                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="text-2xl font-bold font-mono text-amber-900 mt-2">
                <?= number_format($totalSpent, 2) ?>
            </div>
            <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                <span>เบิกจ่ายไปแล้ว <?= $spentPercent ?>%</span>
                <a href="disbursements.php" class="text-amber-600 font-semibold hover:underline">บันทึกเบิกจ่าย &rarr;</a>
            </div>
        </div>

        <!-- Card 4: Remaining Balance -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">งบประมาณคงเหลือสุทธิ</span>
                <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="text-2xl font-bold font-mono text-emerald-700 mt-2">
                <?= number_format($totalRemaining, 2) ?>
            </div>
            <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                <span>คงเหลือพร้อมดำเนินงาน</span>
                <span class="font-bold text-emerald-600"><?= round(100 - $spentPercent, 1) ?>%</span>
            </div>
        </div>
    </div>

    <!-- Charts & Breakdown Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Budget Allocation Chart -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs lg:col-span-1 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-blue-600"></i>
                    <span>สัดส่วนการจัดสรรงบประมาณ 4 ฝ่าย</span>
                </h3>
                <p class="text-xs text-slate-500 mb-4">เกณฑ์มาตรฐาน สพฐ. วิชาการ 60%, งบประมาณ 5%, บุคคล 12%, ทั่วไป 8%, งบกลาง 15%</p>
                <div class="relative h-48 flex items-center justify-center">
                    <canvas id="allocationChart"></canvas>
                </div>
            </div>
            <div class="space-y-1.5 mt-4 pt-3 border-t border-slate-100 text-xs">
                <?php foreach ($allocations as $alloc): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?= $alloc['color_hex'] ?>"></span>
                            <span class="text-slate-700"><?= htmlspecialchars($alloc['department_name']) ?></span>
                        </div>
                        <span class="font-mono font-bold text-slate-900"><?= $alloc['percentage'] ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Department Spending Progress & Details -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs lg:col-span-2 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="trending-up" class="w-4 h-4 text-indigo-600"></i>
                            <span>การใช้จ่ายงบประมาณแยกตามฝ่ายบริหาร</span>
                        </h3>
                        <p class="text-xs text-slate-500">ติดตามความก้าวหน้าการเบิกจ่ายเทียบกับงบประมาณที่จัดสรร</p>
                    </div>
                    <a href="budget.php" class="text-xs font-semibold text-blue-700 hover:underline">จัดการงบประมาณ &rarr;</a>
                </div>

                <div class="space-y-4">
                    <?php foreach ($allocations as $alloc): ?>
                        <?php 
                            $deptSpentPct = $alloc['allocated_amount'] > 0 ? round(($alloc['spent_amount'] / $alloc['allocated_amount']) * 100, 1) : 0;
                        ?>
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($alloc['department_name']) ?></span>
                                <span class="text-slate-500 font-mono">
                                    <span class="font-bold text-slate-900"><?= number_format($alloc['spent_amount']) ?></span> / <?= number_format($alloc['allocated_amount']) ?> บาท (<?= $deptSpentPct ?>%)
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full" style="width: <?= min($deptSpentPct, 100) ?>%; background-color: <?= $alloc['color_hex'] ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Student Summary Row -->
            <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-3 mt-6 flex items-center justify-between text-xs text-blue-950">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-blue-700"></i>
                    <span>จำนวนนักเรียนทั้งหมด <strong><?= number_format($totalStudents) ?> คน</strong> (อนุบาล 86 คน, ประถม 226 คน)</span>
                </div>
                <a href="students.php" class="text-blue-700 font-bold hover:underline">ดูข้อมูลรายชั้น &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Recent Projects Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="folder-git-2" class="w-4 h-4 text-blue-600"></i>
                <h3 class="text-sm font-bold text-slate-900">โครงการตามแผนปฏิบัติการประจำปี</h3>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-semibold">
                    <?= count($projects) ?> โครงการ
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="ai_project_writer.php" class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-3 py-1 rounded-lg font-bold hover:bg-purple-100 transition-colors flex items-center gap-1">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>ร่างโครงการด้วย AI</span>
                </a>
                <a href="projects.php" class="text-xs bg-blue-700 text-white px-3 py-1 rounded-lg font-bold hover:bg-blue-800 transition-colors">
                    ดูทั้งหมด
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-2.5 px-4 font-semibold">รหัส</th>
                        <th class="py-2.5 px-4 font-semibold">ชื่อโครงการ</th>
                        <th class="py-2.5 px-4 font-semibold">ฝ่ายรับผิดชอบ</th>
                        <th class="py-2.5 px-4 font-semibold">ผู้รับผิดชอบ</th>
                        <th class="py-2.5 px-4 font-semibold text-right">งบประมาณ</th>
                        <th class="py-2.5 px-4 font-semibold text-right">เบิกจ่าย</th>
                        <th class="py-2.5 px-4 font-semibold text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($projects as $proj): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($proj['project_code']) ?></td>
                            <td class="py-3 px-4 font-medium text-slate-900 max-w-xs truncate"><?= htmlspecialchars($proj['project_name']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($proj['department']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($proj['responsible_person']) ?></td>
                            <td class="py-3 px-4 font-mono font-bold text-right text-slate-900"><?= number_format($proj['allocated_budget']) ?></td>
                            <td class="py-3 px-4 font-mono text-right text-amber-800"><?= number_format($proj['spent_budget']) ?></td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($proj['status'] === 'completed'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">เสร็จสิ้น</span>
                                <?php elseif ($proj['status'] === 'in_progress'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">กำลังดำเนินการ</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">ยังไม่เริ่ม</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    // Initialize Chart.js Allocation Donut
    const ctx = document.getElementById('allocationChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($allocations, 'department_name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($allocations, 'percentage')) ?>,
                    backgroundColor: <?= json_encode(array_column($allocations, 'color_hex')) ?>,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
