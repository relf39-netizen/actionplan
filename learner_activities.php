<?php
$pageTitle = 'กิจกรรมพัฒนาผู้เรียน (4 กิจกรรมหลัก)';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';

$students = getStudentsData();
$totalStudents = array_sum(array_column($students, 'total_count'));

// Handle POST to update learner activities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_activities') {
    $names = $_POST['activity_name'] ?? [];
    $percentages = $_POST['percentage'] ?? [];
    $rates = $_POST['rate_per_head'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    $allocates = $_POST['allocated'] ?? [];

    $updatedActivities = [];
    for ($i = 0; $i < count($names); $i++) {
        $name = trim($names[$i] ?? '');
        if (empty($name)) continue;

        $pct = floatval($percentages[$i] ?? 0);
        $rate = floatval($rates[$i] ?? 0);
        $alloc = floatval(str_replace(',', '', $allocates[$i] ?? 0));

        $updatedActivities[] = [
            'id' => $i + 1,
            'name' => $name,
            'percentage' => $pct,
            'rate_per_head' => $rate,
            'description' => trim($descriptions[$i] ?? ''),
            'allocated' => $alloc,
        ];
    }

    saveLearnerActivitiesData($currentSchoolId, $updatedActivities);
    $successMsg = 'บันทึกการจัดสรรงบกิจกรรมพัฒนาผู้เรียนเรียบร้อยแล้ว';
}

$activities = getLearnerActivitiesData($currentSchoolId);
$totalActivityBudget = array_sum(array_column($activities, 'allocated'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">กิจกรรมพัฒนาผู้เรียน</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="sparkles" class="w-6 h-6 text-purple-700"></i>
                <span>งบกิจกรรมพัฒนาผู้เรียน 4 กิจกรรมหลัก สพฐ.</span>
            </h2>
            <p class="text-xs text-slate-500">จัดสรรตามเกณฑ์ 460 บาท/คน/ปี สำหรับนักเรียน <?= number_format($totalStudents) ?> คน</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openActivitiesModal()" class="inline-flex items-center gap-2 bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="sliders" class="w-4 h-4"></i>
                <span>ปรับสัดส่วนกิจกรรม</span>
            </button>
            <div class="bg-purple-50 border border-purple-200 px-4 py-1.5 rounded-xl text-right">
                <span class="text-[10px] font-bold text-purple-700 uppercase block">งบกิจกรรมพัฒนาผู้เรียนรวม</span>
                <span class="text-lg font-bold font-mono text-purple-900"><?= number_format($totalActivityBudget, 2) ?> บาท</span>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($successMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-3 shadow-xs">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4 Activities Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($activities as $act): ?>
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-purple-200 transition-colors">
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

<!-- Modal: ปรับสัดส่วนกิจกรรม -->
<div id="activitiesModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="sliders" class="w-5 h-5 text-purple-700"></i>
                    <span>ปรับสัดส่วนงบกิจกรรมพัฒนาผู้เรียน</span>
                </h3>
                <p class="text-xs text-slate-500">กำหนดสัดส่วนร้อยละหรือยอดงบประมาณที่จัดสรรในแต่ละกิจกรรม</p>
            </div>
            <button type="button" onclick="closeActivitiesModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="save_activities">

            <div class="space-y-3">
                <?php foreach ($activities as $idx => $act): ?>
                    <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100 space-y-2">
                        <div class="font-bold text-slate-800 text-xs">
                            <input type="text" name="activity_name[]" value="<?= htmlspecialchars($act['name']) ?>" required
                                   class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">สัดส่วน (%)</label>
                                <input type="number" step="0.1" name="percentage[]" value="<?= $act['percentage'] ?>" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-slate-800">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">อัตรา/คน (บ.)</label>
                                <input type="number" step="0.01" name="rate_per_head[]" value="<?= $act['rate_per_head'] ?>" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-slate-800">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">ยอดจัดสรร (บ.)</label>
                                <input type="number" step="1" name="allocated[]" value="<?= $act['allocated'] ?>" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono font-bold text-purple-900">
                            </div>
                        </div>
                        <div>
                            <input type="text" name="description[]" value="<?= htmlspecialchars($act['description']) ?>" placeholder="คำอธิบายกิจกรรม..."
                                   class="w-full px-3 py-1 bg-white border border-slate-200 rounded-lg text-[11px] text-slate-600">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeActivitiesModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>บันทึกการจัดสรร</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openActivitiesModal() {
    document.getElementById('activitiesModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeActivitiesModal() {
    document.getElementById('activitiesModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

