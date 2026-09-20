<?php
$pageTitle = 'โครงการตามแผนปฏิบัติการ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';
$errorMsg = '';

// จัดการคำขอเพิ่ม / แก้ไข / ลบ / อนุมัติ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_project') {
        $pId = intval($_POST['project_id'] ?? 0);
        $allocated = floatval(str_replace(',', '', $_POST['allocated_budget'] ?? '0'));
        $spent = floatval(str_replace(',', '', $_POST['spent_budget'] ?? '0'));
        
        $projData = [
            'id' => $pId,
            'project_code' => trim($_POST['project_code'] ?? ''),
            'project_name' => trim($_POST['project_name'] ?? ''),
            'department' => trim($_POST['department'] ?? 'ฝ่ายบริหารงานวิชาการ'),
            'responsible_person' => trim($_POST['responsible_person'] ?? ''),
            'allocated_budget' => $allocated,
            'spent_budget' => $spent,
            'remaining_budget' => max(0, $allocated - $spent),
            'status' => trim($_POST['status'] ?? 'not_started'),
            'approval_status' => trim($_POST['approval_status'] ?? 'pending'),
            'duration' => trim($_POST['duration'] ?? ''),
            'rationale' => trim($_POST['rationale'] ?? ''),
            'target_group' => trim($_POST['target_group'] ?? ''),
        ];

        if (!empty($projData['project_name'])) {
            saveProject($currentSchoolId, $projData);
            $successMsg = $pId > 0 ? 'แก้ไขข้อมูลโครงการเรียบร้อยแล้ว' : 'เพิ่มโครงการใหม่ลงในแผนปฏิบัติการเรียบร้อยแล้ว';
        } else {
            $errorMsg = 'กรุณาระบุชื่อโครงการ';
        }
    } elseif ($action === 'delete_project') {
        $pId = intval($_POST['project_id'] ?? 0);
        if ($pId > 0) {
            deleteProject($currentSchoolId, $pId);
            $successMsg = 'ลบโครงการเรียบร้อยแล้ว';
        }
    } elseif ($action === 'toggle_approval') {
        $pId = intval($_POST['project_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'approved';
        $projects = getProjectsData($currentSchoolId);
        foreach ($projects as $p) {
            if ((int)$p['id'] === $pId) {
                $p['approval_status'] = $newStatus;
                saveProject($currentSchoolId, $p);
                $successMsg = 'ปรับสถานะการอนุมัติโครงการเรียบร้อยแล้ว';
                break;
            }
        }
    }
}

$projects = getProjectsData($currentSchoolId);
$fiscalYear = getFiscalYearData();
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">โครงการตามแผนปฏิบัติการ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="folder-kanban" class="w-6 h-6 text-blue-900"></i>
                <span>โครงการตามแผนปฏิบัติการประจำปี</span>
            </h2>
            <p class="text-xs text-slate-500">จัดการโครงการ บันทึกงบประมาณ อนุมัติ และติดตามความก้าวหน้าโครงการของ 4 ฝ่ายบริหาร</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="ai_project_writer.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="bot" class="w-4 h-4 text-amber-300"></i>
                <span>ร่างด้วย AI</span>
            </a>
            <button type="button" onclick="openProjectModal()" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>เพิ่มโครงการใหม่</span>
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

    <?php if ($errorMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center gap-3 shadow-xs">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">โครงการทั้งหมด</span>
            <div class="text-xl font-bold font-mono text-slate-900 mt-1"><?= count($projects) ?> <span class="text-xs font-normal text-slate-500">โครงการ</span></div>
        </div>
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">อนุมัติแล้ว</span>
            <div class="text-xl font-bold font-mono text-emerald-600 mt-1">
                <?= count(array_filter($projects, fn($p) => ($p['approval_status'] ?? '') === 'approved')) ?> <span class="text-xs font-normal text-slate-500">โครงการ</span>
            </div>
        </div>
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">กำลังดำเนินการ</span>
            <div class="text-xl font-bold font-mono text-blue-600 mt-1">
                <?= count(array_filter($projects, fn($p) => ($p['status'] ?? '') === 'in_progress')) ?> <span class="text-xs font-normal text-slate-500">โครงการ</span>
            </div>
        </div>
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">งบประมาณโครงการรวม</span>
            <div class="text-xl font-bold font-mono text-blue-900 mt-1">
                <?= number_format(array_sum(array_column($projects, 'allocated_budget'))) ?> <span class="text-xs font-normal text-slate-500">บาท</span>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i data-lucide="list-filter" class="w-4 h-4 text-blue-900"></i>
                <h3 class="text-sm font-bold text-slate-900">รายการโครงการประจำปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?></h3>
            </div>
            <div class="text-xs text-slate-500">
                พบทั้งหมด <span class="font-bold text-blue-900 font-mono"><?= count($projects) ?></span> โครงการ
            </div>
        </div>

        <?php if (empty($projects)): ?>
            <div class="p-12 text-center text-slate-400">
                <i data-lucide="folder-plus" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                <h4 class="text-sm font-bold text-slate-700">ยังไม่มีโครงการในแผนปฏิบัติการ</h4>
                <p class="text-xs text-slate-500 mt-1 mb-4">คลิกปุ่มด้านล่างเพื่อเริ่มสร้างโครงการของโรงเรียนคุณ</p>
                <button type="button" onclick="openProjectModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-900 text-white text-xs font-bold rounded-xl shadow-xs">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>สร้างโครงการแรก</span>
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="py-3 px-4 font-semibold w-24">รหัส</th>
                            <th class="py-3 px-4 font-semibold">ชื่อโครงการ / หลักการและเหตุผล</th>
                            <th class="py-3 px-4 font-semibold w-40">ฝ่ายรับผิดชอบ</th>
                            <th class="py-3 px-4 font-semibold w-36">ผู้รับผิดชอบ</th>
                            <th class="py-3 px-4 font-semibold text-right w-28">งบจัดสรร</th>
                            <th class="py-3 px-4 font-semibold text-right w-28">ใช้ไป</th>
                            <th class="py-3 px-4 font-semibold text-center w-28">สถานะอนุมัติ</th>
                            <th class="py-3 px-4 font-semibold text-center w-28">การดำเนินงาน</th>
                            <th class="py-3 px-4 font-semibold text-center w-36">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($projects as $proj): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-blue-900">
                                    <?= htmlspecialchars($proj['project_code'] ?: 'กค.-') ?>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900 max-w-sm">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($proj['project_name']) ?></div>
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($proj['rationale'] ?? '-') ?></div>
                                    <?php if (!empty($proj['duration'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">⏱ <?= htmlspecialchars($proj['duration']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($proj['department']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($proj['responsible_person'] ?: '-') ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-right text-slate-900"><?= number_format($proj['allocated_budget']) ?></td>
                                <td class="py-3 px-4 font-mono text-right text-amber-700 font-semibold"><?= number_format($proj['spent_budget']) ?></td>
                                
                                <!-- Approval Status -->
                                <td class="py-3 px-4 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_approval">
                                        <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                                        <?php if (($proj['approval_status'] ?? '') === 'approved'): ?>
                                            <input type="hidden" name="new_status" value="pending">
                                            <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200 transition-colors" title="คลิกเพื่อยกเลิกอนุมัติ">
                                                ✓ อนุมัติแล้ว
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="approved">
                                            <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-200 transition-colors" title="คลิกเพื่ออนุมัติโครงการ">
                                                รออนุมัติ
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>

                                <!-- Execution Status -->
                                <td class="py-3 px-4 text-center">
                                    <?php if ($proj['status'] === 'completed'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">เสร็จสิ้น</span>
                                    <?php elseif ($proj['status'] === 'in_progress'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">กำลังทำ</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">ยังไม่เริ่ม</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Edit Button -->
                                        <button type="button" onclick='editProject(<?= json_encode($proj, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' 
                                                class="p-1.5 text-blue-700 hover:bg-blue-50 rounded transition-colors" title="แก้ไขโครงการ">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        
                                        <!-- View Expenses Button -->
                                        <a href="expenses.php?id=<?= $proj['id'] ?>" class="p-1.5 text-emerald-700 hover:bg-emerald-50 rounded transition-colors" title="หมวดค่าใช้จ่าย 4 หมวด">
                                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                        </a>

                                        <!-- Delete Button -->
                                        <form method="POST" onsubmit="return confirm('ยืนยันการลบโครงการ <?= htmlspecialchars($proj['project_name']) ?> หรือไม่?');" class="inline">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition-colors" title="ลบโครงการ">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal: เพิ่ม / แก้ไขโครงการ -->
<div id="projectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <h3 id="modalTitle" class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="folder-plus" class="w-5 h-5 text-blue-900"></i>
                <span>เพิ่มโครงการใหม่</span>
            </h3>
            <button type="button" onclick="closeProjectModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="save_project">
            <input type="hidden" name="project_id" id="modalProjectId" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">รหัสโครงการ</label>
                    <input type="text" name="project_code" id="modalProjectCode" placeholder="เช่น กค.01/2568" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">ชื่อโครงการ <span class="text-rose-500">*</span></label>
                    <input type="text" name="project_name" id="modalProjectName" required placeholder="เช่น โครงการพัฒนาทักษะการเรียนรู้เชิงรุก" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ฝ่ายรับผิดชอบ</label>
                    <select name="department" id="modalDepartment" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                        <option value="ฝ่ายบริหารงานวิชาการ">ฝ่ายบริหารงานวิชาการ</option>
                        <option value="ฝ่ายบริหารงานงบประมาณ">ฝ่ายบริหารงานงบประมาณ</option>
                        <option value="ฝ่ายบริหารงานบุคคล">ฝ่ายบริหารงานบุคคล</option>
                        <option value="ฝ่ายบริหารงานทั่วไป">ฝ่ายบริหารงานทั่วไป</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ผู้รับผิดชอบโครงการ</label>
                    <input type="text" name="responsible_person" id="modalResponsible" placeholder="ชื่อ-สกุล ครูผู้รับผิดชอบ" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">งบประมาณที่จัดสรร (บาท) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="allocated_budget" id="modalAllocated" required placeholder="0.00" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">งบประมาณที่ใช้ไป (บาท)</label>
                    <input type="number" step="0.01" min="0" name="spent_budget" id="modalSpent" value="0" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-amber-800 font-bold focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ระยะเวลาดำเนินงาน</label>
                    <input type="text" name="duration" id="modalDuration" placeholder="เช่น ตลอดปีการศึกษา 2568" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">สถานะการดำเนินงาน</label>
                    <select name="status" id="modalStatus" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                        <option value="not_started">ยังไม่เริ่ม</option>
                        <option value="in_progress">กำลังดำเนินการ</option>
                        <option value="completed">เสร็จสิ้น</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">สถานะการอนุมัติ</label>
                    <select name="approval_status" id="modalApproval" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                        <option value="pending">รออนุมัติ</option>
                        <option value="approved">อนุมัติแล้ว</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">หลักการและเหตุผล</label>
                <textarea name="rationale" id="modalRationale" rows="3" placeholder="ระบุความเป็นมา ความสำคัญ หรือปัญหาที่ต้องแก้..." 
                          class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeProjectModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>บันทึกโครงการ</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProjectModal() {
    document.getElementById('modalTitle').innerHTML = '<i data-lucide="folder-plus" class="w-5 h-5 text-blue-900"></i><span>เพิ่มโครงการใหม่</span>';
    document.getElementById('modalProjectId').value = '0';
    document.getElementById('modalProjectCode').value = 'กค.' + (Math.floor(Math.random() * 90) + 10) + '/2568';
    document.getElementById('modalProjectName').value = '';
    document.getElementById('modalDepartment').value = 'ฝ่ายบริหารงานวิชาการ';
    document.getElementById('modalResponsible').value = '';
    document.getElementById('modalAllocated').value = '';
    document.getElementById('modalSpent').value = '0';
    document.getElementById('modalDuration').value = 'ตลอดปีการศึกษา 2568';
    document.getElementById('modalStatus').value = 'not_started';
    document.getElementById('modalApproval').value = 'approved';
    document.getElementById('modalRationale').value = '';
    document.getElementById('projectModal').classList.remove('hidden');
    lucide.createIcons();
}

function editProject(proj) {
    document.getElementById('modalTitle').innerHTML = '<i data-lucide="edit-3" class="w-5 h-5 text-blue-900"></i><span>แก้ไขข้อมูลโครงการ</span>';
    document.getElementById('modalProjectId').value = proj.id;
    document.getElementById('modalProjectCode').value = proj.project_code || '';
    document.getElementById('modalProjectName').value = proj.project_name || '';
    document.getElementById('modalDepartment').value = proj.department || 'ฝ่ายบริหารงานวิชาการ';
    document.getElementById('modalResponsible').value = proj.responsible_person || '';
    document.getElementById('modalAllocated').value = proj.allocated_budget || 0;
    document.getElementById('modalSpent').value = proj.spent_budget || 0;
    document.getElementById('modalDuration').value = proj.duration || '';
    document.getElementById('modalStatus').value = proj.status || 'not_started';
    document.getElementById('modalApproval').value = proj.approval_status || 'approved';
    document.getElementById('modalRationale').value = proj.rationale || '';
    document.getElementById('projectModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeProjectModal() {
    document.getElementById('projectModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

