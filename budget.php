<?php
$pageTitle = 'จัดสรรงบประมาณตาม 4 ฝ่าย';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$allocations = getBudgetAllocations();
$totalAllocated = array_sum(array_column($allocations, 'allocated_amount'));
$totalSpent = array_sum(array_column($allocations, 'spent_amount'));
$totalRemaining = $totalAllocated - $totalSpent;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">การจัดสรรงบประมาณตาม 4 ฝ่ายบริหาร</h2>
            <p class="text-xs text-slate-500">ตามสัดส่วนเกณฑ์ สพฐ.: วิชาการ 60%, งบประมาณ 5%, บุคคล 12%, ทั่วไป 8%, งบกลาง 15%</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200">
                สัดส่วนรวม 100% ครบถ้วน
            </span>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">งบประมาณจัดสรรรวม</div>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-1"><?= number_format($totalAllocated, 2) ?> บาท</div>
            <div class="text-xs text-slate-500 mt-1">กระจายตาม 4 ฝ่าย + งบกลาง</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">เบิกจ่ายแล้วสะสม</div>
            <div class="text-2xl font-bold font-mono text-amber-800 mt-1"><?= number_format($totalSpent, 2) ?> บาท</div>
            <div class="text-xs text-slate-500 mt-1">คิดเป็น <?= round(($totalSpent / $totalAllocated) * 100, 1) ?>% ของงบจัดสรร</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">งบประมาณคงเหลือพร้อมใช้</div>
            <div class="text-2xl font-bold font-mono text-emerald-700 mt-1"><?= number_format($totalRemaining, 2) ?> บาท</div>
            <div class="text-xs text-slate-500 mt-1">พร้อมดำเนินโครงการในรอบปี</div>
        </div>
    </div>

    <!-- Department Allocations List -->
    <div class="space-y-4 mb-6">
        <?php foreach ($allocations as $a): ?>
            <?php 
                $spentPct = $a['allocated_amount'] > 0 ? round(($a['spent_amount'] / $a['allocated_amount']) * 100, 1) : 0;
            ?>
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="w-3.5 h-3.5 rounded-full mt-1 shrink-0" style="background-color: <?= $a['color_hex'] ?>"></span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($a['department_name']) ?></h3>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                    สัดส่วน <?= $a['percentage'] ?>%
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($a['description']) ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 text-xs font-mono">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-sans">จัดสรร</span>
                            <span class="font-bold text-slate-900 text-sm"><?= number_format($a['allocated_amount']) ?> บ.</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-sans">ใช้ไป</span>
                            <span class="font-bold text-amber-800 text-sm"><?= number_format($a['spent_amount']) ?> บ.</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-sans">คงเหลือ</span>
                            <span class="font-bold text-emerald-700 text-sm"><?= number_format($a['remaining_amount']) ?> บ.</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                        <span>ความก้าวหน้าการเบิกจ่าย</span>
                        <span><?= $spentPct ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full" style="width: <?= min($spentPct, 100) ?>%; background-color: <?= $a['color_hex'] ?>;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
