<?php
$pageTitle = 'จัดสรรงบประมาณตาม 4 ฝ่าย';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';
$errorMsg = '';

// Handle POST request to update allocations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_allocations') {
        $deptNames = $_POST['dept_name'] ?? [];
        $percentages = $_POST['percentage'] ?? [];
        $allocatedAmounts = $_POST['allocated_amount'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $colorHexes = $_POST['color_hex'] ?? [];
        
        $newAllocations = [];
        for ($i = 0; $i < count($deptNames); $i++) {
            $allocated = floatval(str_replace(',', '', $allocatedAmounts[$i] ?? '0'));
            $pct = floatval($percentages[$i] ?? 0);
            $newAllocations[] = [
                'id' => $i + 1,
                'department_name' => trim($deptNames[$i] ?? ''),
                'percentage' => $pct,
                'allocated_amount' => $allocated,
                'spent_amount' => floatval(str_replace(',', '', $_POST['spent_amount'][$i] ?? '0')),
                'remaining_amount' => max(0, $allocated - floatval(str_replace(',', '', $_POST['spent_amount'][$i] ?? '0'))),
                'color_hex' => trim($colorHexes[$i] ?? '#1E3A8A'),
                'description' => trim($descriptions[$i] ?? ''),
            ];
        }

        if (!empty($newAllocations)) {
            saveBudgetAllocations($currentSchoolId, $newAllocations);
            $successMsg = 'บันทึกการจัดสรรงบประมาณตามฝ่ายเรียบร้อยแล้ว';
        }
    }
}

$allocations = getBudgetAllocations($currentSchoolId);
$totalAllocated = array_sum(array_column($allocations, 'allocated_amount'));
$totalSpent = array_sum(array_column($allocations, 'spent_amount'));
$totalRemaining = $totalAllocated - $totalSpent;
$totalPercentage = array_sum(array_column($allocations, 'percentage'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb and Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">การจัดสรรงบประมาณ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="pie-chart" class="w-6 h-6 text-blue-900"></i>
                <span>การจัดสรรงบประมาณตาม 4 ฝ่ายบริหาร</span>
            </h2>
            <p class="text-xs text-slate-500">กำหนดสัดส่วนร้อยละ และงบประมาณจัดสรรตามเกณฑ์ สพฐ. เพื่อการควบคุมค่าใช้จ่ายที่มีประสิทธิภาพ</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openBudgetModal()" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="sliders" class="w-4 h-4"></i>
                <span>ปรับสัดส่วนงบประมาณ</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($successMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-3 shadow-xs">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase flex items-center justify-between">
                <span>งบประมาณจัดสรรรวม</span>
                <span class="font-mono text-blue-900 font-bold"><?= $totalPercentage ?>%</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-1"><?= number_format($totalAllocated, 2) ?> บาท</div>
            <div class="text-xs text-slate-500 mt-1">กระจายตาม 4 ฝ่าย + งบกลางสำรอง</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">เบิกจ่ายแล้วสะสม</div>
            <div class="text-2xl font-bold font-mono text-amber-800 mt-1"><?= number_format($totalSpent, 2) ?> บาท</div>
            <div class="text-xs text-slate-500 mt-1">คิดเป็น <?= $totalAllocated > 0 ? round(($totalSpent / $totalAllocated) * 100, 1) : 0 ?>% ของงบจัดสรร</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">งบประมาณคงเหลือพร้อมใช้</div>
            <div class="text-2xl font-bold font-mono text-emerald-700 mt-1"><?= number_format($totalRemaining, 2) ?> บาท</div>
            <div class="text-xs text-slate-500 mt-1">พร้อมดำเนินโครงการตลอดปีงบประมาณ</div>
        </div>
    </div>

    <!-- Department Allocations List -->
    <div class="space-y-4 mb-6">
        <?php foreach ($allocations as $a): ?>
            <?php 
                $spentPct = $a['allocated_amount'] > 0 ? round(($a['spent_amount'] / $a['allocated_amount']) * 100, 1) : 0;
            ?>
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs hover:border-slate-300 transition-colors">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="w-3.5 h-3.5 rounded-full mt-1 shrink-0 shadow-2xs" style="background-color: <?= $a['color_hex'] ?>"></span>
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
                        <span class="font-mono font-bold <?= $spentPct > 90 ? 'text-rose-600' : 'text-slate-700' ?>"><?= $spentPct ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-300" style="width: <?= min($spentPct, 100) ?>%; background-color: <?= $a['color_hex'] ?>;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modal: ปรับสัดส่วนและงบประมาณจัดสรร -->
<div id="budgetModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="sliders" class="w-5 h-5 text-blue-900"></i>
                    <span>กำหนดสัดส่วนและงบประมาณจัดสรร</span>
                </h3>
                <p class="text-xs text-slate-500">ปรับเปลี่ยนอัตราร้อยละ หรืองบประมาณจัดสรรของแต่ละฝ่าย</p>
            </div>
            <button type="button" onclick="closeBudgetModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="save_allocations">

            <div class="space-y-3">
                <?php foreach ($allocations as $idx => $a): ?>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-4">
                                <label class="block font-bold text-slate-700 mb-1">ชื่อฝ่าย</label>
                                <input type="text" name="dept_name[]" value="<?= htmlspecialchars($a['department_name']) ?>" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-semibold focus:outline-none focus:ring-1 focus:ring-blue-900">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 mb-1">สัดส่วน (%)</label>
                                <input type="number" step="0.1" min="0" max="100" name="percentage[]" value="<?= $a['percentage'] ?>" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-center font-bold text-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block font-bold text-slate-700 mb-1">งบจัดสรร (บาท)</label>
                                <input type="number" step="0.01" min="0" name="allocated_amount[]" value="<?= $a['allocated_amount'] ?>" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block font-bold text-slate-700 mb-1">เบิกจ่ายแล้ว (บาท)</label>
                                <input type="number" step="0.01" min="0" name="spent_amount[]" value="<?= $a['spent_amount'] ?>" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right font-semibold text-amber-800 focus:outline-none focus:ring-1 focus:ring-blue-900">
                            </div>
                        </div>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-10">
                                <input type="text" name="description[]" value="<?= htmlspecialchars($a['description']) ?>" placeholder="คำอธิบายรายละเอียด..." 
                                       class="w-full px-3 py-1 bg-white border border-slate-200 rounded-lg text-[11px] text-slate-600 focus:outline-none">
                            </div>
                            <div class="sm:col-span-2 flex items-center gap-1.5">
                                <span class="text-[11px] text-slate-500">สีแท็ก:</span>
                                <input type="color" name="color_hex[]" value="<?= htmlspecialchars($a['color_hex']) ?>" class="w-7 h-7 rounded border border-slate-300 cursor-pointer p-0 bg-transparent">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>บันทึกการจัดสรรงบประมาณ</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openBudgetModal() {
    document.getElementById('budgetModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeBudgetModal() {
    document.getElementById('budgetModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

