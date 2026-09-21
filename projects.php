<?php
$pageTitle = 'แบบเสนอโครงการและบริหารโครงการ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$schoolInfo = getSchoolInfoData($currentSchoolId);
$schoolName = $schoolInfo['school_name'] ?? 'โรงเรียนเด็กเรียนดี';
$directorName = $schoolInfo['director_name'] ?? 'ดร.สมศักดิ์ พัฒนศึกษา (ผู้อำนวยการโรงเรียน)';

$successMsg = '';
$errorMsg = '';

// ตรวจสอบแท็บปัจจุบัน (?tab=all|pending|approved|completed)
$currentTab = trim($_GET['tab'] ?? 'all');
if (!in_array($currentTab, ['all', 'pending', 'approved', 'completed'])) {
    $currentTab = 'all';
}

// จัดการคำขอเพิ่ม / แก้ไข / ลบ / อนุมัติ / ปรับงบ / ปิดโครงการ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_project') {
        $pId = intval($_POST['project_id'] ?? 0);
        $allocated = floatval(str_replace(',', '', $_POST['allocated_budget'] ?? '0'));
        $spent = floatval(str_replace(',', '', $_POST['spent_budget'] ?? '0'));
        $idCard = trim($_POST['proposer_id_card'] ?? '');
        
        $projData = [
            'id' => $pId,
            'project_code' => trim($_POST['project_code'] ?? ''),
            'project_name' => trim($_POST['project_name'] ?? ''),
            'department' => trim($_POST['department'] ?? 'ฝ่ายบริหารงานวิชาการ'),
            'responsible_person' => trim($_POST['responsible_person'] ?? ''),
            'proposer_position' => trim($_POST['proposer_position'] ?? 'ครูผู้รับผิดชอบโครงการ'),
            'endorser_name' => trim($_POST['endorser_name'] ?? 'คุณครูสอนดี นามสกุลเก่งมาก'),
            'endorser_position' => trim($_POST['endorser_position'] ?? 'หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้างานแผนงาน'),
            'approver_name' => trim($_POST['approver_name'] ?? 'ดร.สมศักดิ์ พัฒนศึกษา'),
            'approver_position' => trim($_POST['approver_position'] ?? 'ผู้อำนวยการโรงเรียน'),
            'proposer_id_card' => $idCard,
            'allocated_budget' => $allocated,
            'spent_budget' => $spent,
            'remaining_budget' => max(0, $allocated - $spent),
            'status' => trim($_POST['status'] ?? 'not_started'),
            'approval_status' => trim($_POST['approval_status'] ?? 'pending'),
            'duration' => trim($_POST['duration'] ?? ''),
            'rationale' => trim($_POST['rationale'] ?? ''),
            'target_group' => trim($_POST['target_group'] ?? ''),
        ];

        if ($pId === 0) {
            $projData['original_budget'] = $allocated;
        }

        if (!empty($projData['project_name'])) {
            saveProject($currentSchoolId, $projData);
            $successMsg = $pId > 0 ? 'แก้ไขข้อมูลโครงการเรียบร้อยแล้ว' : 'เพิ่มโครงการใหม่ลงในแผนปฏิบัติการเรียบร้อยแล้ว';
        } else {
            $errorMsg = 'กรุณาระบุชื่อโครงการ';
        }
    } elseif ($action === 'adjust_budget') {
        $pId = intval($_POST['project_id'] ?? 0);
        $newBudget = floatval(str_replace(',', '', $_POST['new_budget'] ?? '0'));
        $reason = trim($_POST['adjustment_reason'] ?? '');
        $adjuster = trim($_POST['adjuster_name'] ?? ($currentUser['name'] ?? 'เจ้าหน้าที่แผนงาน'));

        if ($pId > 0 && $newBudget >= 0) {
            $projects = getProjectsData($currentSchoolId);
            foreach ($projects as $p) {
                if ((int)$p['id'] === $pId) {
                    $orig = isset($p['original_budget']) && floatval($p['original_budget']) > 0 ? floatval($p['original_budget']) : floatval($p['allocated_budget']);
                    $p['original_budget'] = $orig;
                    $p['allocated_budget'] = $newBudget;
                    $p['remaining_budget'] = max(0, $newBudget - floatval($p['spent_budget'] ?? 0));
                    $p['budget_adjusted_at'] = date('Y-m-d H:i:s');
                    $p['budget_adjusted_by'] = $adjuster;
                    $p['budget_adjustment_reason'] = $reason;
                    saveProject($currentSchoolId, $p);
                    $successMsg = 'ปรับเปลี่ยนวงเงินงบประมาณโครงการ ' . htmlspecialchars($p['project_name']) . ' เป็น ' . number_format($newBudget) . ' บาท เรียบร้อยแล้ว';
                    break;
                }
            }
        } else {
            $errorMsg = 'จำนวนเงินงบประมาณไม่ถูกต้อง';
        }
    } elseif ($action === 'close_project') {
        $pId = intval($_POST['project_id'] ?? 0);
        $notes = trim($_POST['closure_notes'] ?? '');
        $closedBy = trim($_POST['closed_by'] ?? ($currentUser['name'] ?? 'เจ้าหน้าที่แผนงาน'));

        if ($pId > 0) {
            $projects = getProjectsData($currentSchoolId);
            foreach ($projects as $p) {
                if ((int)$p['id'] === $pId) {
                    $p['status'] = 'completed';
                    $p['closed_at'] = date('Y-m-d H:i:s');
                    $p['closed_by'] = $closedBy;
                    $p['closure_notes'] = $notes;
                    saveProject($currentSchoolId, $p);
                    $successMsg = 'ดำเนินการปิดโครงการ ' . htmlspecialchars($p['project_name']) . ' เรียบร้อยแล้ว';
                    break;
                }
            }
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
                if ($newStatus === 'approved' && empty($p['approved_at'])) {
                    $p['approved_at'] = date('Y-m-d H:i:s');
                    $p['approver_name'] = $directorName;
                }
                saveProject($currentSchoolId, $p);
                $successMsg = 'ปรับสถานะการอนุมัติโครงการเรียบร้อยแล้ว';
                break;
            }
        }
    }
}

$allProjects = getProjectsData($currentSchoolId);
$fiscalYear = getFiscalYearData();

// นับสถิติแต่ละหมวด
$countAll = count($allProjects);
$countPending = count(array_filter($allProjects, fn($p) => ($p['approval_status'] ?? '') !== 'approved'));
$countApproved = count(array_filter($allProjects, fn($p) => ($p['approval_status'] ?? '') === 'approved' && ($p['status'] ?? '') !== 'completed'));
$countCompleted = count(array_filter($allProjects, fn($p) => ($p['status'] ?? '') === 'completed'));

// กรองโครงการตามแท็บ
$filteredProjects = match ($currentTab) {
    'pending' => array_filter($allProjects, fn($p) => ($p['approval_status'] ?? '') !== 'approved'),
    'approved' => array_filter($allProjects, fn($p) => ($p['approval_status'] ?? '') === 'approved' && ($p['status'] ?? '') !== 'completed'),
    'completed' => array_filter($allProjects, fn($p) => ($p['status'] ?? '') === 'completed'),
    default => $allProjects,
};
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">โครงการตามแผนปฏิบัติการ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="folder-kanban" class="w-6 h-6 text-blue-900"></i>
                <span>โครงการตามแผนปฏิบัติการประจำปี</span>
                <?php if ($currentTab === 'approved'): ?>
                    <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2.5 py-0.5 rounded-full border border-emerald-200">
                        เฉพาะโครงการที่อนุมัติแล้ว
                    </span>
                <?php endif; ?>
            </h2>
            <p class="text-xs text-slate-500">จัดการโครงการ บันทึกงบประมาณ อนุมัติ ปรับเปลี่ยนงบประมาณ และติดตามการดำเนินงาน</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="ai_project_writer.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="bot" class="w-4 h-4 text-amber-300"></i>
                <span>เขียนโครงการด้วย AI</span>
            </a>
            <button type="button" onclick="openProjectModal()" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>เพิ่มโครงการใหม่</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($successMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-3 shadow-xs no-print">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center gap-3 shadow-xs no-print">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Sub-Tabs Navigation (Mirroring React system) -->
    <div class="flex items-center gap-2 border-b border-slate-200 mb-6 overflow-x-auto pb-1 no-print">
        <a href="projects.php?tab=all" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $currentTab === 'all' ? 'border-blue-900 text-blue-900 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
            <i data-lucide="folder" class="w-4 h-4"></i>
            <span>แฟ้มโครงการทั้งหมด</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $currentTab === 'all' ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-700' ?>"><?= $countAll ?></span>
        </a>
        <a href="projects.php?tab=pending" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $currentTab === 'pending' ? 'border-amber-600 text-amber-900 bg-amber-50/50' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
            <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
            <span>รอพิจารณาอนุมัติ</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $currentTab === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-700' ?>"><?= $countPending ?></span>
        </a>
        <a href="projects.php?tab=approved" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $currentTab === 'approved' ? 'border-emerald-600 text-emerald-900 bg-emerald-50/50' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
            <span>โครงการที่อนุมัติแล้ว</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $currentTab === 'approved' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700' ?>"><?= $countApproved ?></span>
        </a>
        <a href="projects.php?tab=completed" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $currentTab === 'completed' ? 'border-slate-700 text-slate-900 bg-slate-100' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
            <i data-lucide="flag" class="w-4 h-4 text-slate-600"></i>
            <span>เสร็จสิ้น / ปิดโครงการแล้ว</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $currentTab === 'completed' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-700' ?>"><?= $countCompleted ?></span>
        </a>
    </div>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 no-print">
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">โครงการในมุมมองนี้</span>
            <div class="text-xl font-bold font-mono text-slate-900 mt-1"><?= count($filteredProjects) ?> <span class="text-xs font-normal text-slate-500">โครงการ</span></div>
        </div>
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">อนุมัติแล้ว</span>
            <div class="text-xl font-bold font-mono text-emerald-600 mt-1">
                <?= $countApproved ?> <span class="text-xs font-normal text-slate-500">โครงการ</span>
            </div>
        </div>
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">ปิดโครงการแล้ว</span>
            <div class="text-xl font-bold font-mono text-slate-700 mt-1">
                <?= $countCompleted ?> <span class="text-xs font-normal text-slate-500">โครงการ</span>
            </div>
        </div>
        <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] text-slate-500 font-semibold block">งบประมาณรวมในมุมมองนี้</span>
            <div class="text-xl font-bold font-mono text-blue-900 mt-1">
                <?= number_format(array_sum(array_column($filteredProjects, 'allocated_budget'))) ?> <span class="text-xs font-normal text-slate-500">บาท</span>
            </div>
        </div>
    </div>

    <!-- Projects Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50 no-print">
            <div class="flex items-center gap-2">
                <i data-lucide="list-filter" class="w-4 h-4 text-blue-900"></i>
                <h3 class="text-sm font-bold text-slate-900">
                    <?php if ($currentTab === 'approved'): ?>
                        รายการโครงการที่ได้รับอนุมัติแล้ว (พร้อมดำเนินกิจกรรมและเบิกจ่าย)
                    <?php elseif ($currentTab === 'pending'): ?>
                        รายการโครงการที่รอผู้มีอำนาจลงนามอนุมัติ
                    <?php elseif ($currentTab === 'completed'): ?>
                        ทำเนียบโครงการที่ดำเนินการเสร็จสิ้นและปิดโครงการแล้ว
                    <?php else: ?>
                        รายการโครงการประจำปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="text-xs text-slate-500">
                พบ <span class="font-bold text-blue-900 font-mono"><?= count($filteredProjects) ?></span> โครงการ
            </div>
        </div>

        <?php if (empty($filteredProjects)): ?>
            <div class="p-12 text-center text-slate-400">
                <i data-lucide="folder-check" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                <h4 class="text-sm font-bold text-slate-700">ไม่พบโครงการในหมวดหมู่นี้</h4>
                <p class="text-xs text-slate-500 mt-1 mb-4">
                    <?php if ($currentTab === 'approved'): ?>
                        ยังไม่มีโครงการที่ได้รับการอนุมัติ กรุณาตรวจสอบแท็บ "รอพิจารณาอนุมัติ" เพื่อทำการอนุมัติโครงการ
                    <?php else: ?>
                        คลิกปุ่มด้านล่างเพื่อเพิ่มโครงการใหม่
                    <?php endif; ?>
                </p>
                <button type="button" onclick="openProjectModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-900 text-white text-xs font-bold rounded-xl shadow-xs">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>สร้างโครงการใหม่</span>
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="py-3 px-4 font-semibold w-24">รหัส</th>
                            <th class="py-3 px-4 font-semibold min-w-[240px]">ชื่อโครงการ / ผู้เสนอ</th>
                            <th class="py-3 px-4 font-semibold w-36">ฝ่ายงาน</th>
                            <th class="py-3 px-4 font-semibold text-right w-28">งบประมาณ</th>
                            <th class="py-3 px-4 font-semibold text-right w-24">ใช้ไป</th>
                            <th class="py-3 px-4 font-semibold text-center w-28">สถานะอนุมัติ</th>
                            <th class="py-3 px-4 font-semibold text-center w-28">การดำเนินงาน</th>
                            <th class="py-3 px-4 font-semibold text-center min-w-[200px]">การจัดการ / รายงาน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($filteredProjects as $proj): 
                            $isApproved = (($proj['approval_status'] ?? '') === 'approved');
                            $isClosed = (($proj['status'] ?? '') === 'completed');
                            $hasAdjusted = !empty($proj['budget_adjusted_at']) || (isset($proj['original_budget']) && floatval($proj['original_budget']) != floatval($proj['allocated_budget']) && floatval($proj['original_budget']) > 0);
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Code -->
                                <td class="py-3 px-4 font-mono font-bold text-blue-900 align-top">
                                    <?= htmlspecialchars($proj['project_code'] ?: 'กค.-') ?>
                                </td>

                                <!-- Title & Details -->
                                <td class="py-3 px-4 font-medium text-slate-900 max-w-sm align-top">
                                    <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($proj['project_name']) ?></div>
                                    
                                    <!-- Proposer info & Citizen ID -->
                                    <div class="text-[11px] text-slate-600 mt-1 flex flex-wrap items-center gap-1.5">
                                        <span>👤 ผู้เสนอ: <strong><?= htmlspecialchars($proj['responsible_person'] ?: '-') ?></strong></span>
                                        <?php if (!empty($proj['proposer_id_card'])): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[10px] border border-slate-200">
                                                ID: <?= htmlspecialchars($proj['proposer_id_card']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($proj['duration'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">⏱ <?= htmlspecialchars($proj['duration']) ?></div>
                                    <?php endif; ?>

                                    <!-- Budget adjustment flag -->
                                    <?php if ($hasAdjusted): ?>
                                        <div class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded bg-amber-50 border border-amber-200 text-amber-900 text-[10px] font-bold">
                                            <span>⚡ ปรับงบแล้ว</span>
                                            <?php if (!empty($proj['original_budget'])): ?>
                                                <span class="text-slate-500 font-normal">(เดิม: <?= number_format($proj['original_budget']) ?> บ.)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Closed flag -->
                                    <?php if ($isClosed): ?>
                                        <div class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-bold">
                                            <span>✓ ปิดโครงการแล้ว</span>
                                            <?php if (!empty($proj['closed_at'])): ?>
                                                <span class="text-slate-500 font-normal">(<?= date('d/m/Y', strtotime($proj['closed_at'])) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Department -->
                                <td class="py-3 px-4 text-slate-600 align-top">
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($proj['department']) ?>
                                    </span>
                                </td>

                                <!-- Allocated Budget -->
                                <td class="py-3 px-4 font-mono font-bold text-right text-slate-900 align-top">
                                    <div><?= number_format($proj['allocated_budget']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-normal">บาท</div>
                                </td>

                                <!-- Spent Budget -->
                                <td class="py-3 px-4 font-mono text-right text-amber-800 font-semibold align-top">
                                    <div><?= number_format($proj['spent_budget']) ?></div>
                                </td>
                                
                                <!-- Approval Status -->
                                <td class="py-3 px-4 text-center align-top">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_approval">
                                        <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                                        <?php if ($isApproved): ?>
                                            <input type="hidden" name="new_status" value="pending">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200 transition-colors" title="คลิกเพื่อยกเลิกอนุมัติ">
                                                <i data-lucide="check" class="w-3 h-3"></i>
                                                <span>อนุมัติแล้ว</span>
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="approved">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-200 transition-colors" title="คลิกเพื่ออนุมัติโครงการ">
                                                <i data-lucide="clock" class="w-3 h-3"></i>
                                                <span>รออนุมัติ</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>

                                <!-- Execution Status -->
                                <td class="py-3 px-4 text-center align-top">
                                    <?php if ($isClosed): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-white">ปิดโครงการแล้ว</span>
                                    <?php elseif (($proj['status'] ?? '') === 'in_progress'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">กำลังทำ</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">ยังไม่เริ่ม</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-center align-top">
                                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                                        <!-- ปุ่มปรับงบประมาณ -->
                                        <button type="button" 
                                                onclick='openAdjustBudgetModal(<?= json_encode($proj, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' 
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-md text-[11px] font-bold transition-colors" 
                                                title="ปรับเปลี่ยนวงเงินงบประมาณ">
                                            <i data-lucide="calculator" class="w-3.5 h-3.5 text-amber-700"></i>
                                            <span>ปรับงบ</span>
                                        </button>

                                        <!-- ปุ่มพิมพ์บันทึกค่าใช้จ่าย (เฉพาะโครงการที่อนุมัติแล้ว) -->
                                        <?php if ($isApproved): ?>
                                            <button type="button" 
                                                    onclick='openExpenseMemoModal(<?= json_encode($proj, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' 
                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-300 rounded-md text-[11px] font-bold transition-colors" 
                                                    title="พิมพ์บันทึกข้อความรายการค่าใช้จ่าย">
                                                <i data-lucide="printer" class="w-3.5 h-3.5 text-indigo-700"></i>
                                                <span>พิมพ์บันทึกค่าใช้จ่าย</span>
                                            </button>
                                        <?php endif; ?>

                                        <!-- ปุ่มปิดโครงการ (เฉพาะที่อนุมัติและยังไม่ปิด) -->
                                        <?php if ($isApproved && !$isClosed): ?>
                                            <button type="button" 
                                                    onclick='openCloseProjectModal(<?= json_encode($proj, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' 
                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md text-[11px] font-bold transition-colors" 
                                                    title="ปิดโครงการเมื่อดำเนินงานเสร็จสิ้น">
                                                <i data-lucide="check-square" class="w-3.5 h-3.5 text-emerald-700"></i>
                                                <span>ปิดโครงการ</span>
                                            </button>
                                        <?php endif; ?>

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

<!-- Modal 1: เพิ่ม / แก้ไขโครงการ -->
<div id="projectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden no-print">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100">
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                    <input type="text" name="responsible_person" id="modalResponsible" placeholder="เช่น คุณครูมุ่งมั่น นามสกุลตั้งใจสอน" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">เลขประจำตัวประชาชน 13 หลัก</label>
                    <input type="text" name="proposer_id_card" id="modalIdCard" maxlength="13" placeholder="ระบุเลข 13 หลัก" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ผู้เห็นชอบโครงการ</label>
                    <input type="text" name="endorser_name" id="modalEndorser" placeholder="เช่น คุณครูสอนดี นามสกุลเก่งมาก" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ตำแหน่งผู้เห็นชอบโครงการ</label>
                    <input type="text" name="endorser_position" id="modalEndorserPos" placeholder="เช่น หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้างานแผนงาน" 
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
                        <option value="completed">เสร็จสิ้น (ปิดโครงการ)</option>
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

<!-- Modal 2: ปรับเปลี่ยนวงเงินงบประมาณ (Budget Adjustment) -->
<div id="adjustBudgetModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden no-print">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="calculator" class="w-5 h-5 text-amber-600"></i>
                <span>ปรับเปลี่ยนวงเงินงบประมาณโครงการ</span>
            </h3>
            <button type="button" onclick="closeAdjustBudgetModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="adjust_budget">
            <input type="hidden" name="project_id" id="adjProjectId" value="0">

            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-1">
                <div class="font-bold text-slate-900 text-sm" id="adjProjectName">-</div>
                <div class="text-slate-500 text-[11px]" id="adjProjectCode">-</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-100 p-3 rounded-xl">
                    <label class="block text-slate-500 font-semibold mb-1">งบประมาณปัจจุบัน</label>
                    <div class="font-mono text-base font-bold text-slate-800" id="adjCurrentBudget">0.00 บาท</div>
                </div>
                <div class="bg-blue-50 p-3 rounded-xl border border-blue-200">
                    <label class="block text-blue-900 font-semibold mb-1">ส่วนต่างที่ปรับเปลี่ยน</label>
                    <div class="font-mono text-base font-bold text-blue-900" id="adjDiffBudget">0.00 บาท</div>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">กำหนดวงเงินงบประมาณใหม่ (บาท) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="new_budget" id="adjNewBudget" required oninput="calculateBudgetDiff()" 
                       class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-base font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">เหตุผลและความจำเป็นในการปรับเปลี่ยนงบประมาณ <span class="text-rose-500">*</span></label>
                <textarea name="adjustment_reason" id="adjReason" rows="3" required placeholder="เช่น ปรับลดตามกรอบวงเงินงบประมาณจัดสรรจริงของโรงเรียน..." 
                          class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">ผู้มีอำนาจที่อนุมัติปรับงบ</label>
                <input type="text" name="adjuster_name" id="adjAdjuster" value="<?= htmlspecialchars($directorName) ?>" 
                       class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeAdjustBudgetModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>ยืนยันการปรับงบประมาณ</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: ยืนยันปิดโครงการ (Close Project Modal) -->
<div id="closeProjectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden no-print">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="check-square" class="w-5 h-5 text-emerald-600"></i>
                <span>ยืนยันการปิดโครงการ (เสร็จสิ้นการดำเนินงาน)</span>
            </h3>
            <button type="button" onclick="closeCloseProjectModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="close_project">
            <input type="hidden" name="project_id" id="closeProjectId" value="0">

            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div class="font-bold text-slate-900 text-sm" id="closeProjectName">-</div>
                <div class="text-slate-500 text-[11px]" id="closeProjectCode">-</div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-slate-100 p-2.5 rounded-xl">
                    <span class="text-[10px] text-slate-500 block">งบที่อนุมัติ</span>
                    <span class="font-mono font-bold text-slate-900" id="closeAllocated">0</span>
                </div>
                <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-200">
                    <span class="text-[10px] text-amber-800 block">ใช้จ่ายจริง</span>
                    <span class="font-mono font-bold text-amber-900" id="closeSpent">0</span>
                </div>
                <div class="bg-emerald-50 p-2.5 rounded-xl border border-emerald-200">
                    <span class="text-[10px] text-emerald-800 block">คงเหลือส่งคืน</span>
                    <span class="font-mono font-bold text-emerald-900" id="closeRemaining">0</span>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">สรุปผลสัมฤทธิ์และผลการดำเนินงานโครงการ</label>
                <textarea name="closure_notes" id="closeNotes" rows="3" placeholder="ระบุผลการดำเนินงาน บรรลุวัตถุประสงค์ หรือปัญหาอุปสรรค..." 
                          class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">ผู้รับผิดชอบปิดโครงการ</label>
                <input type="text" name="closed_by" id="closedBy" value="<?= htmlspecialchars($currentUser['name'] ?? 'เจ้าหน้าที่แผนงาน') ?>" 
                       class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeCloseProjectModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>ยืนยันปิดโครงการ</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: พิมพ์แบบบันทึกข้อความรายการค่าใช้จ่าย สพฐ. (Official Memorandum) -->
<div id="expenseMemoModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[95vh] overflow-y-auto shadow-2xl border border-slate-200">
        <!-- Action Toolbar -->
        <div class="p-4 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white z-10 no-print">
            <div class="flex items-center gap-2">
                <i data-lucide="printer" class="w-5 h-5 text-indigo-700"></i>
                <h3 class="text-sm font-bold text-slate-900">บันทึกข้อความรายการค่าใช้จ่ายโครงการ (สพฐ.)</h3>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>พิมพ์เอกสาร (Print)</span>
                </button>
                <form method="POST" action="export_doc.php" target="_blank" class="inline">
                    <input type="hidden" name="project_name" id="docExportName" value="">
                    <input type="hidden" name="html_content" id="docExportContent" value="">
                    <button type="submit" onclick="prepareDocExport()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>ดาวน์โหลด Word (.doc)</span>
                    </button>
                </form>
                <button type="button" onclick="closeExpenseMemoModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Official Printable Document Container -->
        <div id="printDocumentArea" class="p-8 sm:p-12 text-slate-900 bg-white font-['Sarabun',sans-serif] leading-relaxed text-sm">
            <!-- Header Garuda / Memo -->
            <div class="text-center mb-6">
                <div class="text-2xl font-bold text-slate-900 mb-1">บันทึกข้อความ</div>
            </div>

            <!-- Official Memo Header Details -->
            <div class="border-b border-slate-900 pb-3 mb-4 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <div><strong>ส่วนราชการ:</strong> <span id="memoSchoolName"><?= htmlspecialchars($schoolName) ?></span></div>
                    <div><strong>โทร:</strong> <?= htmlspecialchars($schoolInfo['phone'] ?? '02-000-0000') ?></div>
                </div>
                <div class="flex justify-between">
                    <div><strong>ที่:</strong> <span id="memoDocNo">กค.พิเศษ/2568</span></div>
                    <div><strong>วันที่:</strong> <?= date('j') . ' ' . ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'][(int)date('n')-1] . ' พ.ศ. ' . (date('Y')+543) ?></div>
                </div>
                <div>
                    <strong>เรื่อง:</strong> ขออนุมัติเบิกจ่ายงบประมาณและรายงานรายละเอียดค่าใช้จ่ายโครงการ
                </div>
            </div>

            <div class="text-xs mb-4">
                <strong>เรียน:</strong> ผู้อำนวยการโรงเรียน<?= htmlspecialchars($schoolName) ?>
            </div>

            <!-- Body Text -->
            <div class="text-xs space-y-2 indent-6 mb-4 text-justify">
                <p>
                    ตามที่สถานศึกษาได้อนุมัติให้ดำเนินงาน <strong id="memoProjectTitle">โครงการ...</strong> 
                    รหัสโครงการ <span id="memoProjectCode" class="font-mono font-bold"></span> 
                    ประจำปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?> 
                    โดยมี <strong id="memoResponsible"></strong> เป็นผู้รับผิดชอบโครงการ 
                    <span id="memoCitizenIdWrapper" class="hidden">(เลขประจำตัวประชาชน: <span id="memoCitizenId" class="font-mono"></span>)</span>
                    สังกัด <span id="memoDepartment"></span> 
                    วงเงินงบประมาณที่ได้รับอนุมัติจัดสรรทั้งสิ้น <strong id="memoAllocatedBudget">0</strong> บาท 
                    (<span id="memoAllocatedText"></span>) นั้น
                </p>
                <p>
                    บัดนี้ คณะทำงานโครงการได้ดำเนินการตามแผนงานและขั้นตอนที่กำหนดไว้เรียบร้อยแล้ว จึงขอรายงานรายละเอียดค่าใช้จ่ายตามหมวดงบประมาณ 4 หมวดของสำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.) ดังมีรายละเอียดต่อไปนี้:
                </p>
            </div>

            <!-- Expenses Breakdown Table -->
            <div class="mb-4">
                <table class="w-full text-left text-xs border border-slate-900 border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 border-b border-slate-900">
                            <th class="py-2 px-3 border-r border-slate-900 text-center w-12">ลำดับ</th>
                            <th class="py-2 px-3 border-r border-slate-900">รายการค่าใช้จ่าย</th>
                            <th class="py-2 px-3 border-r border-slate-900 text-center w-24">หมวดรายจ่าย</th>
                            <th class="py-2 px-3 border-r border-slate-900 text-center w-16">จำนวน</th>
                            <th class="py-2 px-3 border-r border-slate-900 text-center w-16">หน่วย</th>
                            <th class="py-2 px-3 border-r border-slate-900 text-right w-24">ราคา/หน่วย</th>
                            <th class="py-2 px-3 text-right w-28">จำนวนเงิน (บาท)</th>
                        </tr>
                    </thead>
                    <tbody id="memoExpenseRows" class="divide-y divide-slate-300">
                        <tr>
                            <td colspan="7" class="py-4 text-center text-slate-400">กำลังโหลดรายการค่าใช้จ่าย...</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold border-t border-slate-900">
                            <td colspan="6" class="py-2 px-3 text-right border-r border-slate-900">รวมงบประมาณที่ใช้จ่ายทั้งสิ้น</td>
                            <td class="py-2 px-3 text-right font-mono" id="memoTotalExpense">0.00</td>
                        </tr>
                        <tr class="bg-slate-100 font-bold border-t border-slate-300">
                            <td colspan="6" class="py-2 px-3 text-right border-r border-slate-900">ยอดงบประมาณคงเหลือสุทธิ</td>
                            <td class="py-2 px-3 text-right font-mono text-emerald-800" id="memoRemainingExpense">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="text-xs indent-6 mb-8 text-justify">
                <p>จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ</p>
            </div>

            <!-- Signatures Section (3 Signatories) -->
            <div class="grid grid-cols-3 gap-6 text-center text-xs pt-6">
                <!-- Signatory 1: Proposer -->
                <div class="space-y-1">
                    <div>(ลงชื่อ)..........................................................</div>
                    <div class="font-bold mt-1">(<span id="memoSignProposer"></span>)</div>
                    <div class="text-slate-600">ผู้รับผิดชอบโครงการ</div>
                    <div class="text-[10px] text-slate-500">วันที่ ......./......./.......</div>
                </div>

                <!-- Signatory 2: Endorser / Planning Officer -->
                <div class="space-y-1">
                    <div>(ลงชื่อ)..........................................................</div>
                    <div class="font-bold mt-1">(<span id="memoSignEndorser">คุณครูสอนดี นามสกุลเก่งมาก</span>)</div>
                    <div class="text-slate-600"><span id="memoSignEndorserPos">หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้างานแผนงาน</span></div>
                    <div class="text-[10px] text-slate-500">วันที่ ......./......./.......</div>
                </div>

                <!-- Signatory 3: Director -->
                <div class="space-y-1">
                    <div>(ลงชื่อ)..........................................................</div>
                    <div class="font-bold mt-1">(<span id="memoSignDirector"><?= htmlspecialchars($directorName) ?></span>)</div>
                    <div class="text-slate-600">ผู้อำนวยการโรงเรียน<?= htmlspecialchars($schoolName) ?></div>
                    <div class="text-[10px] text-slate-500">วันที่ ......./......./.......</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSelectedProject = null;

function openProjectModal() {
    document.getElementById('modalTitle').innerHTML = '<i data-lucide="folder-plus" class="w-5 h-5 text-blue-900"></i><span>เพิ่มโครงการใหม่</span>';
    document.getElementById('modalProjectId').value = '0';
    document.getElementById('modalProjectCode').value = 'กค.' + (Math.floor(Math.random() * 90) + 10) + '/2568';
    document.getElementById('modalProjectName').value = '';
    document.getElementById('modalDepartment').value = 'ฝ่ายบริหารงานวิชาการ';
    document.getElementById('modalResponsible').value = 'คุณครูมุ่งมั่น นามสกุลตั้งใจสอน';
    if (document.getElementById('modalEndorser')) document.getElementById('modalEndorser').value = 'คุณครูสอนดี นามสกุลเก่งมาก';
    if (document.getElementById('modalEndorserPos')) document.getElementById('modalEndorserPos').value = 'หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้างานแผนงาน';
    document.getElementById('modalIdCard').value = '';
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
    document.getElementById('modalResponsible').value = proj.responsible_person || 'คุณครูมุ่งมั่น นามสกุลตั้งใจสอน';
    if (document.getElementById('modalEndorser')) document.getElementById('modalEndorser').value = proj.endorser_name || 'คุณครูสอนดี นามสกุลเก่งมาก';
    if (document.getElementById('modalEndorserPos')) document.getElementById('modalEndorserPos').value = proj.endorser_position || 'หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้างานแผนงาน';
    document.getElementById('modalIdCard').value = proj.proposer_id_card || proj.proposer_citizen_id || '';
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

// Budget Adjustment Modal
let curBudgetVal = 0;
function openAdjustBudgetModal(proj) {
    currentSelectedProject = proj;
    curBudgetVal = parseFloat(proj.allocated_budget || 0);
    document.getElementById('adjProjectId').value = proj.id;
    document.getElementById('adjProjectName').textContent = proj.project_name || '';
    document.getElementById('adjProjectCode').textContent = 'รหัส: ' + (proj.project_code || '-');
    document.getElementById('adjCurrentBudget').textContent = Number(curBudgetVal).toLocaleString() + ' บาท';
    document.getElementById('adjNewBudget').value = curBudgetVal;
    document.getElementById('adjReason').value = proj.budget_adjustment_reason || '';
    calculateBudgetDiff();
    document.getElementById('adjustBudgetModal').classList.remove('hidden');
    lucide.createIcons();
}

function calculateBudgetDiff() {
    const newB = parseFloat(document.getElementById('adjNewBudget').value || 0);
    const diff = newB - curBudgetVal;
    const diffEl = document.getElementById('adjDiffBudget');
    if (diff > 0) {
        diffEl.textContent = '+' + Number(diff).toLocaleString() + ' บาท (เพิ่มขึ้น)';
        diffEl.className = 'font-mono text-base font-bold text-emerald-600';
    } else if (diff < 0) {
        diffEl.textContent = Number(diff).toLocaleString() + ' บาท (ลดลง)';
        diffEl.className = 'font-mono text-base font-bold text-rose-600';
    } else {
        diffEl.textContent = '0.00 บาท (เท่าเดิม)';
        diffEl.className = 'font-mono text-base font-bold text-slate-600';
    }
}

function closeAdjustBudgetModal() {
    document.getElementById('adjustBudgetModal').classList.add('hidden');
}

// Close Project Modal
function openCloseProjectModal(proj) {
    const allocated = parseFloat(proj.allocated_budget || 0);
    const spent = parseFloat(proj.spent_budget || 0);
    const remaining = Math.max(0, allocated - spent);

    document.getElementById('closeProjectId').value = proj.id;
    document.getElementById('closeProjectName').textContent = proj.project_name || '';
    document.getElementById('closeProjectCode').textContent = 'รหัส: ' + (proj.project_code || '-');
    document.getElementById('closeAllocated').textContent = Number(allocated).toLocaleString() + ' บ.';
    document.getElementById('closeSpent').textContent = Number(spent).toLocaleString() + ' บ.';
    document.getElementById('closeRemaining').textContent = Number(remaining).toLocaleString() + ' บ.';
    document.getElementById('closeNotes').value = proj.closure_notes || 'การดำเนินงานบรรลุตามวัตถุประสงค์และตัวชี้วัดความสำเร็จทุกประการ';
    document.getElementById('closeProjectModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeCloseProjectModal() {
    document.getElementById('closeProjectModal').classList.add('hidden');
}

// Official Memorandum Modal & Printing
function openExpenseMemoModal(proj) {
    currentSelectedProject = proj;
    const allocated = parseFloat(proj.allocated_budget || 0);
    const spent = parseFloat(proj.spent_budget || 0);
    const remaining = Math.max(0, allocated - spent);

    document.getElementById('memoProjectTitle').textContent = proj.project_name || '';
    document.getElementById('memoProjectCode').textContent = proj.project_code || 'กค.-';
    document.getElementById('memoResponsible').textContent = proj.responsible_person || 'ผู้รับผิดชอบโครงการ';
    document.getElementById('memoDepartment').textContent = proj.department || 'ฝ่ายงาน';
    document.getElementById('memoAllocatedBudget').textContent = Number(allocated).toLocaleString();
    document.getElementById('memoAllocatedText').textContent = bahtText(allocated);
    document.getElementById('memoSignProposer').textContent = proj.responsible_person || 'คุณครูมุ่งมั่น นามสกุลตั้งใจสอน';
    if (document.getElementById('memoSignEndorser')) {
        document.getElementById('memoSignEndorser').textContent = proj.endorser_name || 'คุณครูสอนดี นามสกุลเก่งมาก';
    }
    if (document.getElementById('memoSignEndorserPos')) {
        document.getElementById('memoSignEndorserPos').textContent = proj.endorser_position || 'หัวหน้ากลุ่มสาระการเรียนรู้ / หัวหน้างานแผนงาน';
    }
    if (document.getElementById('memoSignDirector')) {
        document.getElementById('memoSignDirector').textContent = proj.approver_name || '<?= htmlspecialchars($directorName) ?>';
    }

    const idCard = proj.proposer_id_card || proj.proposer_citizen_id;
    if (idCard) {
        document.getElementById('memoCitizenId').textContent = idCard;
        document.getElementById('memoCitizenIdWrapper').classList.remove('hidden');
    } else {
        document.getElementById('memoCitizenIdWrapper').classList.add('hidden');
    }

    // Load default expenses for table
    const remBudget = Math.round(allocated * 0.2);
    const operBudget = Math.round(allocated * 0.45);
    const matBudget = allocated - remBudget - operBudget;

    const sampleExpenses = [
        { cat: 'ค่าตอบแทน', name: 'ค่าตอบแทนวิทยากรผู้เชี่ยวชาญการฝึกอบรมเชิงปฏิบัติการ', qty: 1, unit: 'รายการ', price: remBudget, total: remBudget },
        { cat: 'ค่าใช้สอย', name: 'ค่าอาหารกลางวันและเครื่องดื่มสำหรับผู้เข้าร่วมกิจกรรม', qty: 1, unit: 'รายการ', price: operBudget, total: operBudget },
        { cat: 'ค่าวัสดุ', name: 'ค่าวัสดุ อุปกรณ์ สื่อการเรียนรู้ และเอกสารประกอบกิจกรรม', qty: 1, unit: 'ชุด', price: matBudget, total: matBudget },
    ];

    let rowsHtml = '';
    let totalSum = 0;
    sampleExpenses.forEach((it, idx) => {
        totalSum += it.total;
        rowsHtml += `
            <tr class="border-b border-slate-200">
                <td class="py-2 px-3 text-center border-r border-slate-200">${idx + 1}</td>
                <td class="py-2 px-3 border-r border-slate-200 font-medium">${it.name}</td>
                <td class="py-2 px-3 text-center border-r border-slate-200"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-semibold">${it.cat}</span></td>
                <td class="py-2 px-3 text-center border-r border-slate-200 font-mono">${it.qty}</td>
                <td class="py-2 px-3 text-center border-r border-slate-200">${it.unit}</td>
                <td class="py-2 px-3 text-right border-r border-slate-200 font-mono">${Number(it.price).toLocaleString()}</td>
                <td class="py-2 px-3 text-right font-mono font-bold">${Number(it.total).toLocaleString()}</td>
            </tr>
        `;
    });

    document.getElementById('memoExpenseRows').innerHTML = rowsHtml;
    document.getElementById('memoTotalExpense').textContent = Number(totalSum).toLocaleString() + ' บาท';
    document.getElementById('memoRemainingExpense').textContent = Number(Math.max(0, allocated - totalSum)).toLocaleString() + ' บาท';

    document.getElementById('expenseMemoModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeExpenseMemoModal() {
    document.getElementById('expenseMemoModal').classList.add('hidden');
}

function prepareDocExport() {
    if (currentSelectedProject) {
        document.getElementById('docExportName').value = currentSelectedProject.project_name || 'บันทึกรายการค่าใช้จ่าย';
        document.getElementById('docExportContent').value = document.getElementById('printDocumentArea').innerHTML;
    }
}

// Simple Thai Baht Text generator
function bahtText(num) {
    if (!num || isNaN(num) || num <= 0) return 'ศูนย์บาทถ้วน';
    return Number(num).toLocaleString() + ' บาทถ้วน';
}
</script>

<style>
@media print {
    .no-print, #sidebar, header, footer {
        display: none !important;
    }
    body, main {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #expenseMemoModal {
        position: static !important;
        background: white !important;
        padding: 0 !important;
        display: block !important;
    }
    #expenseMemoModal > div {
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    #printDocumentArea {
        padding: 0 !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
