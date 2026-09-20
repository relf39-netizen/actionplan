<?php
$pageTitle = 'โครงการตามแผนปฏิบัติการ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$projects = getProjectsData();

// Approve / Change status action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];
    $pId = intval($_POST['project_id'] ?? 0);
    
    foreach ($_SESSION['projects'] as &$p) {
        if ($p['id'] === $pId) {
            if ($act === 'approve') {
                $p['approval_status'] = 'approved';
            } elseif ($act === 'start') {
                $p['status'] = 'in_progress';
            } elseif ($act === 'complete') {
                $p['status'] = 'completed';
            }
            break;
        }
    }
    $projects = $_SESSION['projects'];
}
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">โครงการตามแผนปฏิบัติการประจำปี</h2>
            <p class="text-xs text-slate-500">จัดการโครงการ อนุมัติงบประมาณ และติดตามสถานะการดำเนินงานของแต่ละฝ่าย</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="ai_project_writer.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="bot" class="w-4 h-4 text-amber-300"></i>
                <span>เขียนโครงการด้วย AI</span>
            </a>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl p-3 border border-slate-200">
            <span class="text-[11px] text-slate-500 font-semibold">โครงการทั้งหมด</span>
            <div class="text-xl font-bold font-mono text-slate-900"><?= count($projects) ?> โครงการ</div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-slate-200">
            <span class="text-[11px] text-slate-500 font-semibold">อนุมัติแล้ว</span>
            <div class="text-xl font-bold font-mono text-emerald-600">
                <?= count(array_filter($projects, fn($p) => ($p['approval_status'] ?? '') === 'approved')) ?> โครงการ
            </div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-slate-200">
            <span class="text-[11px] text-slate-500 font-semibold">กำลังดำเนินการ</span>
            <div class="text-xl font-bold font-mono text-blue-600">
                <?= count(array_filter($projects, fn($p) => ($p['status'] ?? '') === 'in_progress')) ?> โครงการ
            </div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-slate-200">
            <span class="text-[11px] text-slate-500 font-semibold">งบประมาณรวม</span>
            <div class="text-xl font-bold font-mono text-blue-900">
                <?= number_format(array_sum(array_column($projects, 'allocated_budget'))) ?> บ.
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">รายชื่อโครงการทั้งหมด</h3>
            <span class="text-xs text-slate-500 font-mono">ปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-3 px-4 font-semibold">รหัส</th>
                        <th class="py-3 px-4 font-semibold">ชื่อโครงการ</th>
                        <th class="py-3 px-4 font-semibold">ฝ่ายรับผิดชอบ</th>
                        <th class="py-3 px-4 font-semibold">ผู้รับผิดชอบ</th>
                        <th class="py-3 px-4 font-semibold text-right">งบประมาณ</th>
                        <th class="py-3 px-4 font-semibold text-right">ใช้ไป</th>
                        <th class="py-3 px-4 font-semibold text-center">สถานะอนุมัติ</th>
                        <th class="py-3 px-4 font-semibold text-center">การดำเนินการ</th>
                        <th class="py-3 px-4 font-semibold text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($projects as $proj): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($proj['project_code']) ?></td>
                            <td class="py-3 px-4 font-medium text-slate-900 max-w-sm">
                                <div class="font-bold"><?= htmlspecialchars($proj['project_name']) ?></div>
                                <div class="text-[11px] text-slate-500 line-clamp-1"><?= htmlspecialchars($proj['rationale'] ?? '') ?></div>
                            </td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($proj['department']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($proj['responsible_person']) ?></td>
                            <td class="py-3 px-4 font-mono font-bold text-right text-slate-900"><?= number_format($proj['allocated_budget']) ?></td>
                            <td class="py-3 px-4 font-mono text-right text-amber-800"><?= number_format($proj['spent_budget']) ?></td>
                            
                            <!-- Approval Status -->
                            <td class="py-3 px-4 text-center">
                                <?php if (($proj['approval_status'] ?? '') === 'approved'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        ✓ อนุมัติแล้ว
                                    </span>
                                <?php else: ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                                        <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-200 transition-colors">
                                            รออนุมัติ (คลิกเพื่ออนุมัติ)
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                            <!-- Execution Status -->
                            <td class="py-3 px-4 text-center">
                                <?php if ($proj['status'] === 'completed'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">เสร็จสิ้น</span>
                                <?php elseif ($proj['status'] === 'in_progress'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">กำลังดำเนินการ</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">ยังไม่เริ่ม</span>
                                <?php endif; ?>
                            </td>

                            <!-- Action buttons -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="expenses.php?id=<?= $proj['id'] ?>" class="p-1.5 text-blue-700 hover:bg-blue-50 rounded" title="ดูรายละเอียดงบ">
                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                    </a>
                                    <a href="disbursements.php?id=<?= $proj['id'] ?>" class="p-1.5 text-amber-700 hover:bg-amber-50 rounded" title="บันทึกเบิกจ่าย">
                                        <i data-lucide="receipt" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
