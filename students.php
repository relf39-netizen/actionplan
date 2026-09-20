<?php
$pageTitle = 'ข้อมูลนักเรียนและการคำนวณเงินรายหัว';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';
$errorMsg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_students') {
        $gradeLevels = $_POST['grade_level'] ?? [];
        $stages = $_POST['stage'] ?? [];
        $males = $_POST['male_count'] ?? [];
        $females = $_POST['female_count'] ?? [];

        $newStudents = [];
        for ($i = 0; $i < count($gradeLevels); $i++) {
            $gl = trim($gradeLevels[$i] ?? '');
            if (empty($gl)) continue;

            $m = max(0, intval($males[$i] ?? 0));
            $f = max(0, intval($females[$i] ?? 0));
            $stg = trim($stages[$i] ?? 'ประถม');

            $newStudents[] = [
                'id' => $i + 1,
                'school_id' => $currentSchoolId,
                'grade_level' => $gl,
                'stage' => $stg,
                'male_count' => $m,
                'female_count' => $f,
                'total_count' => $m + $f,
            ];
        }

        if (!empty($newStudents)) {
            saveStudentsData($currentSchoolId, $newStudents);
            syncRevenuesFromStudentsAndRates($currentSchoolId);
            $successMsg = 'บันทึกข้อมูลจำนวนนักเรียนเรียบร้อยแล้ว ยอดรวมนักเรียน เงินอุดหนุนรายหัว และประมาณการรายรับถูกคำนวณใหม่อัตโนมัติ';
        } else {
            $errorMsg = 'กรุณาระบุข้อมูลนักเรียนอย่างน้อย 1 ระดับชั้น';
        }
    } elseif ($action === 'reset_standard') {
        $defaultStandard = [
            ['id' => 1, 'school_id' => $currentSchoolId, 'grade_level' => 'อนุบาล 1', 'stage' => 'อนุบาล', 'male_count' => 12, 'female_count' => 14, 'total_count' => 26],
            ['id' => 2, 'school_id' => $currentSchoolId, 'grade_level' => 'อนุบาล 2', 'stage' => 'อนุบาล', 'male_count' => 15, 'female_count' => 16, 'total_count' => 31],
            ['id' => 3, 'school_id' => $currentSchoolId, 'grade_level' => 'อนุบาล 3', 'stage' => 'อนุบาล', 'male_count' => 14, 'female_count' => 15, 'total_count' => 29],
            ['id' => 4, 'school_id' => $currentSchoolId, 'grade_level' => 'ประถมศึกษาปีที่ 1', 'stage' => 'ประถม', 'male_count' => 20, 'female_count' => 18, 'total_count' => 38],
            ['id' => 5, 'school_id' => $currentSchoolId, 'grade_level' => 'ประถมศึกษาปีที่ 2', 'stage' => 'ประถม', 'male_count' => 19, 'female_count' => 17, 'total_count' => 36],
            ['id' => 6, 'school_id' => $currentSchoolId, 'grade_level' => 'ประถมศึกษาปีที่ 3', 'stage' => 'ประถม', 'male_count' => 21, 'female_count' => 19, 'total_count' => 40],
            ['id' => 7, 'school_id' => $currentSchoolId, 'grade_level' => 'ประถมศึกษาปีที่ 4', 'stage' => 'ประถม', 'male_count' => 18, 'female_count' => 20, 'total_count' => 38],
            ['id' => 8, 'school_id' => $currentSchoolId, 'grade_level' => 'ประถมศึกษาปีที่ 5', 'stage' => 'ประถม', 'male_count' => 20, 'female_count' => 18, 'total_count' => 38],
            ['id' => 9, 'school_id' => $currentSchoolId, 'grade_level' => 'ประถมศึกษาปีที่ 6', 'stage' => 'ประถม', 'male_count' => 19, 'female_count' => 17, 'total_count' => 36],
        ];
        saveStudentsData($currentSchoolId, $defaultStandard);
        syncRevenuesFromStudentsAndRates($currentSchoolId);
        $successMsg = 'รีเซ็ตข้อมูลนักเรียนเป็นระดับชั้นมาตรฐาน สพฐ. เรียบร้อยแล้ว พร้อมคำนวณยอดประมาณการรายรับใหม่';
    }
}

// Reload students after save
$students = getStudentsData($currentSchoolId);
$config = getFiscalYearConfig($currentSchoolId);
$activeYear = (int)($config['active_year'] ?? 2568);
$rates = getFiscalYearRates($currentSchoolId);
$kRate = (float)($rates['kindergarten'] ?? 1854);
$pRate = (float)($rates['primary'] ?? 2122);
$sRate = (float)($rates['secondary_lower'] ?? 3608);
$uRate = (float)($rates['secondary_upper'] ?? 4018);

$totalMale = array_sum(array_column($students, 'male_count'));
$totalFemale = array_sum(array_column($students, 'female_count'));
$totalStudents = array_sum(array_column($students, 'total_count'));

$kCount = 0;
$pCount = 0;
$sCount = 0;
$uCount = 0;
$totalSubsidy = 0;

foreach ($students as $s) {
    $stg = $s['stage'] ?? 'ประถม';
    $tot = (int)($s['total_count'] ?? 0);
    if ($stg === 'อนุบาล') {
        $kCount += $tot;
        $totalSubsidy += ($tot * $kRate);
    } elseif ($stg === 'มัธยมต้น') {
        $sCount += $tot;
        $totalSubsidy += ($tot * $sRate);
    } elseif ($stg === 'มัธยมปลาย') {
        $uCount += $tot;
        $totalSubsidy += ($tot * $uRate);
    } else {
        $pCount += $tot;
        $totalSubsidy += ($tot * $pRate);
    }
}

$kSubsidy = $kCount * $kRate;
$pSubsidy = $pCount * $pRate;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">ข้อมูลนักเรียน</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="users" class="w-6 h-6 text-blue-900"></i>
                <span>ข้อมูลจำนวนนักเรียนและเงินอุดหนุนรายหัว</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                สามารถกำหนดและแก้ไขจำนวนนักเรียน ชาย/หญิง ได้เองโดยตรง ไม่ต้องดึงจาก DMC เพื่อความคล่องตัวในการวางแผนงบประมาณ
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="fiscal_year.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-900 text-xs font-bold rounded-xl border border-blue-200 transition-colors shadow-xs">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                <span>อัตราปีงบประมาณ พ.ศ. <?= $activeYear ?></span>
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200 shadow-xs">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                <span>สถานะ: กำหนดข้อมูลเองได้</span>
            </span>
            <form method="POST" onsubmit="return confirm('ต้องการรีเซ็ตจำนวนนักเรียนเป็นระดับชั้นมาตรฐาน สพฐ. (อนุบาล 1 - ป.6) หรือไม่?');">
                <input type="hidden" name="action" value="reset_standard">
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl border border-slate-300 transition-colors">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>คืนค่ามาตรฐาน</span>
                </button>
            </form>
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
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Information Banner -->
    <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-slate-50 border border-blue-200 rounded-2xl p-4 mb-6 text-xs text-blue-900 flex items-start gap-3 shadow-xs">
        <i data-lucide="info" class="w-5 h-5 text-blue-700 shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            <div class="font-bold text-sm text-blue-950">
                เกณฑ์คำนวณเงินอุดหนุนรายหัวประจำปีงบประมาณ พ.ศ. <?= $activeYear ?> (มติ ครม. ล่าสุด):
            </div>
            <p class="text-slate-700 leading-relaxed">
                อนุบาล <strong><?= number_format($kRate) ?></strong> บาท/คน | ประถม <strong><?= number_format($pRate) ?></strong> บาท/คน | มัธยมต้น <strong><?= number_format($sRate) ?></strong> บาท/คน | มัธยมปลาย <strong><?= number_format($uRate) ?></strong> บาท/คน 
                (ท่านสามารถแก้ไขอัตราหรือเปลี่ยนปีงบประมาณได้ที่เมนู <a href="fiscal_year.php" class="underline font-bold text-blue-900 hover:text-blue-700">"ตั้งค่าปีงบประมาณ & อัตราเงินอุดหนุน"</a>)
            </p>
        </div>
    </div>

    <!-- 3 Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">นักเรียนทั้งหมด</span>
                <span class="p-1.5 bg-blue-50 text-blue-800 rounded-lg"><i data-lucide="users" class="w-4 h-4"></i></span>
            </div>
            <div id="stat-total-students" class="text-2xl font-bold font-mono text-slate-900 mt-2"><?= number_format($totalStudents) ?> คน</div>
            <div class="text-xs text-slate-500 mt-1">ชาย <span id="stat-total-male" class="font-bold text-slate-700"><?= $totalMale ?></span> คน / หญิง <span id="stat-total-female" class="font-bold text-slate-700"><?= $totalFemale ?></span> คน</div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-purple-700 uppercase">ก่อนประถม (อนุบาล)</span>
                <span class="p-1.5 bg-purple-50 text-purple-700 rounded-lg"><i data-lucide="baby" class="w-4 h-4"></i></span>
            </div>
            <div id="stat-k-count" class="text-2xl font-bold font-mono text-purple-700 mt-2"><?= number_format($kCount) ?> คน</div>
            <div class="text-xs text-slate-500 mt-1">อัตรา 1,800 บ./คน = <span id="stat-k-subsidy" class="font-bold text-purple-900"><?= number_format($kSubsidy) ?></span> บ.</div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-700 uppercase">ประถมศึกษา (ป.1 - ป.6)</span>
                <span class="p-1.5 bg-blue-50 text-blue-700 rounded-lg"><i data-lucide="graduation-cap" class="w-4 h-4"></i></span>
            </div>
            <div id="stat-p-count" class="text-2xl font-bold font-mono text-blue-700 mt-2"><?= number_format($pCount) ?> คน</div>
            <div class="text-xs text-slate-500 mt-1">อัตรา 2,050 บ./คน = <span id="stat-p-subsidy" class="font-bold text-blue-900"><?= number_format($pSubsidy) ?></span> บ.</div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-blue-200 bg-gradient-to-br from-blue-900 to-indigo-900 text-white shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-200 uppercase">เงินอุดหนุนรายหัวรวม</span>
                <span class="p-1.5 bg-white/10 text-amber-300 rounded-lg"><i data-lucide="coins" class="w-4 h-4"></i></span>
            </div>
            <div id="stat-total-subsidy" class="text-2xl font-bold font-mono text-amber-300 mt-2"><?= number_format($totalSubsidy, 2) ?> ฿</div>
            <div class="text-xs text-blue-200 mt-1">ตามเกณฑ์การจัดสรรประจำปี <?= $fiscalYear['year'] ?></div>
        </div>
    </div>

    <!-- Student Edit Form & Table -->
    <form method="POST" id="studentForm" class="space-y-4">
        <input type="hidden" name="action" value="save_students">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/60">
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="edit-3" class="w-4 h-4 text-blue-900"></i>
                        <span>ตารางกรอกและแก้ไขจำนวนนักเรียนจำแนกตามระดับชั้น</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">แก้ไขตัวเลขชาย/หญิง แล้วกด "บันทึกข้อมูลจำนวนนักเรียน" เพื่ออัปเดตระบบ</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="addNewGradeRow()" class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-300 hover:bg-slate-100 rounded-xl text-xs font-bold text-slate-700 transition-colors shadow-xs">
                        <i data-lucide="plus" class="w-4 h-4 text-blue-900"></i>
                        <span>เพิ่มระดับชั้น</span>
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                        <i data-lucide="save" class="w-4 h-4 text-amber-300"></i>
                        <span>บันทึกข้อมูลจำนวนนักเรียน</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="py-3 px-4 font-semibold w-12 text-center">#</th>
                            <th class="py-3 px-4 font-semibold min-w-[160px]">ระดับชั้น</th>
                            <th class="py-3 px-4 font-semibold w-36">ช่วงชั้น / เกณฑ์</th>
                            <th class="py-3 px-4 font-semibold text-center w-28">นักเรียนชาย (คน)</th>
                            <th class="py-3 px-4 font-semibold text-center w-28">นักเรียนหญิง (คน)</th>
                            <th class="py-3 px-4 font-semibold text-center w-24">รวม (คน)</th>
                            <th class="py-3 px-4 font-semibold text-right w-28">อัตรา/หัว (บาท)</th>
                            <th class="py-3 px-4 font-semibold text-right w-36">เงินอุดหนุนรวม (บาท)</th>
                            <th class="py-3 px-4 font-semibold text-center w-16">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody" class="divide-y divide-slate-100">
                        <?php foreach ($students as $idx => $row): ?>
                            <?php 
                                $stage = $row['stage'] ?? 'ประถม';
                                $rate = $pRate;
                                if ($stage === 'อนุบาล') $rate = $kRate;
                                elseif ($stage === 'มัธยมต้น') $rate = $sRate;
                                elseif ($stage === 'มัธยมปลาย') $rate = $uRate;
                                $amount = (int)$row['total_count'] * $rate;
                            ?>
                            <tr class="student-row hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 px-4 text-center font-mono text-slate-400 row-index"><?= $idx + 1 ?></td>
                                <td class="py-2.5 px-4">
                                    <input type="text" name="grade_level[]" value="<?= htmlspecialchars($row['grade_level']) ?>" required
                                           class="w-full px-2.5 py-1.5 bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-900 font-bold focus:outline-none focus:ring-1 focus:ring-blue-900/20">
                                </td>
                                <td class="py-2.5 px-4">
                                    <select name="stage[]" onchange="recalcAll()"
                                            class="stage-select w-full px-2.5 py-1.5 bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-700 font-semibold focus:outline-none">
                                        <option value="อนุบาล" <?= $stage === 'อนุบาล' ? 'selected' : '' ?>>อนุบาล (<?= number_format($kRate) ?> บ.)</option>
                                        <option value="ประถม" <?= $stage === 'ประถม' ? 'selected' : '' ?>>ประถม (<?= number_format($pRate) ?> บ.)</option>
                                        <option value="มัธยมต้น" <?= $stage === 'มัธยมต้น' ? 'selected' : '' ?>>มัธยมต้น (<?= number_format($sRate) ?> บ.)</option>
                                        <option value="มัธยมปลาย" <?= $stage === 'มัธยมปลาย' ? 'selected' : '' ?>>มัธยมปลาย (<?= number_format($uRate) ?> บ.)</option>
                                    </select>
                                </td>
                                <td class="py-2.5 px-4">
                                    <input type="number" name="male_count[]" value="<?= (int)$row['male_count'] ?>" min="0" oninput="recalcRow(this)"
                                           class="male-input w-full px-2 py-1.5 text-center font-mono font-bold bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900/20">
                                </td>
                                <td class="py-2.5 px-4">
                                    <input type="number" name="female_count[]" value="<?= (int)$row['female_count'] ?>" min="0" oninput="recalcRow(this)"
                                           class="female-input w-full px-2 py-1.5 text-center font-mono font-bold bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900/20">
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="row-total font-mono font-bold text-slate-900 text-sm"><?= (int)$row['total_count'] ?></span>
                                </td>
                                <td class="py-2.5 px-4 text-right">
                                    <span class="row-rate font-mono text-slate-600"><?= number_format($rate) ?></span>
                                </td>
                                <td class="py-2.5 px-4 text-right">
                                    <span class="row-subsidy font-mono font-bold text-blue-900 text-sm"><?= number_format($amount) ?></span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <button type="button" onclick="removeGradeRow(this)" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="ลบระดับชั้นนี้">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-blue-50/80 font-bold text-slate-900 border-t-2 border-blue-200">
                            <td colspan="3" class="py-3.5 px-4 text-right text-slate-700">ยอดรวมทั้งสิ้น</td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-900" id="foot-total-male"><?= $totalMale ?></td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-900" id="foot-total-female"><?= $totalFemale ?></td>
                            <td class="py-3.5 px-4 text-center font-mono text-blue-900 text-sm" id="foot-total-students"><?= $totalStudents ?> คน</td>
                            <td class="py-3.5 px-4 text-right text-slate-500">-</td>
                            <td class="py-3.5 px-4 text-right font-mono text-blue-900 text-sm" id="foot-total-subsidy"><?= number_format($totalSubsidy, 2) ?> บ.</td>
                            <td class="py-3.5 px-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Bottom action bar -->
            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/40">
                <div class="text-xs text-slate-500">
                    * เมื่อกรอกข้อมูลนักเรียนแล้ว ให้คลิกปุ่ม <strong>"บันทึกข้อมูลจำนวนนักเรียน"</strong> เพื่ออัปเดตระบบ
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="addNewGradeRow()" class="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-300 hover:bg-slate-100 rounded-xl text-xs font-bold text-slate-700 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>เพิ่มระดับชั้นใหม่</span>
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                        <i data-lucide="save" class="w-4 h-4 text-amber-300"></i>
                        <span>บันทึกข้อมูลจำนวนนักเรียน</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
const RATES = {
    'อนุบาล': <?= $kRate ?>,
    'ประถม': <?= $pRate ?>,
    'มัธยมต้น': <?= $sRate ?>,
    'มัธยมปลาย': <?= $uRate ?>
};

function recalcRow(inputEl) {
    const row = inputEl.closest('.student-row');
    const male = parseInt(row.querySelector('.male-input').value) || 0;
    const female = parseInt(row.querySelector('.female-input').value) || 0;
    const total = male + female;
    row.querySelector('.row-total').innerText = total;

    const stage = row.querySelector('.stage-select').value;
    const rate = RATES[stage] || <?= $pRate ?>;
    const subsidy = total * rate;

    row.querySelector('.row-rate').innerText = rate.toLocaleString('th-TH');
    row.querySelector('.row-subsidy').innerText = subsidy.toLocaleString('th-TH');

    recalcGrandTotals();
}

function recalcAll() {
    document.querySelectorAll('.student-row').forEach(row => {
        const male = parseInt(row.querySelector('.male-input').value) || 0;
        const female = parseInt(row.querySelector('.female-input').value) || 0;
        const total = male + female;
        row.querySelector('.row-total').innerText = total;

        const stage = row.querySelector('.stage-select').value;
        const rate = RATES[stage] || <?= $pRate ?>;
        const subsidy = total * rate;

        row.querySelector('.row-rate').innerText = rate.toLocaleString('th-TH');
        row.querySelector('.row-subsidy').innerText = subsidy.toLocaleString('th-TH');
    });
    recalcGrandTotals();
}

function recalcGrandTotals() {
    let sumMale = 0;
    let sumFemale = 0;
    let sumTotal = 0;
    let sumSubsidy = 0;
    let kCount = 0;
    let pCount = 0;

    document.querySelectorAll('.student-row').forEach(row => {
        const male = parseInt(row.querySelector('.male-input').value) || 0;
        const female = parseInt(row.querySelector('.female-input').value) || 0;
        const total = male + female;
        const stage = row.querySelector('.stage-select').value;
        const rate = RATES[stage] || <?= $pRate ?>;

        sumMale += male;
        sumFemale += female;
        sumTotal += total;
        sumSubsidy += (total * rate);

        if (stage === 'อนุบาล') {
            kCount += total;
        } else {
            pCount += total;
        }
    });

    // Update Footers
    const footMale = document.getElementById('foot-total-male');
    if (footMale) footMale.innerText = sumMale;
    const footFemale = document.getElementById('foot-total-female');
    if (footFemale) footFemale.innerText = sumFemale;
    const footStudents = document.getElementById('foot-total-students');
    if (footStudents) footStudents.innerText = sumTotal + ' คน';
    const footSubsidy = document.getElementById('foot-total-subsidy');
    if (footSubsidy) footSubsidy.innerText = sumSubsidy.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บ.';

    // Update Top Stat Cards
    const statStudents = document.getElementById('stat-total-students');
    if (statStudents) statStudents.innerText = sumTotal.toLocaleString('th-TH') + ' คน';
    const statMale = document.getElementById('stat-total-male');
    if (statMale) statMale.innerText = sumMale;
    const statFemale = document.getElementById('stat-total-female');
    if (statFemale) statFemale.innerText = sumFemale;

    const statKCount = document.getElementById('stat-k-count');
    if (statKCount) statKCount.innerText = kCount.toLocaleString('th-TH') + ' คน';
    const statKSubsidy = document.getElementById('stat-k-subsidy');
    if (statKSubsidy) statKSubsidy.innerText = (kCount * <?= $kRate ?>).toLocaleString('th-TH');

    const statPCount = document.getElementById('stat-p-count');
    if (statPCount) statPCount.innerText = pCount.toLocaleString('th-TH') + ' คน';
    const statPSubsidy = document.getElementById('stat-p-subsidy');
    if (statPSubsidy) statPSubsidy.innerText = (pCount * <?= $pRate ?>).toLocaleString('th-TH');

    const statTotalSubsidy = document.getElementById('stat-total-subsidy');
    if (statTotalSubsidy) statTotalSubsidy.innerText = sumSubsidy.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';
}

function addNewGradeRow() {
    const tbody = document.getElementById('studentTableBody');
    const rowCount = tbody.querySelectorAll('.student-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'student-row hover:bg-slate-50/70 transition-colors animate-in fade-in duration-150';
    tr.innerHTML = `
        <td class="py-2.5 px-4 text-center font-mono text-slate-400 row-index">${rowCount}</td>
        <td class="py-2.5 px-4">
            <input type="text" name="grade_level[]" value="ระดับชั้นเพิ่มเติม ${rowCount}" required
                   class="w-full px-2.5 py-1.5 bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-900 font-bold focus:outline-none focus:ring-1 focus:ring-blue-900/20">
        </td>
        <td class="py-2.5 px-4">
            <select name="stage[]" onchange="recalcAll()"
                    class="stage-select w-full px-2.5 py-1.5 bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-700 font-semibold focus:outline-none">
                <option value="อนุบาล">อนุบาล (<?= number_format($kRate) ?> บ.)</option>
                <option value="ประถม" selected>ประถม (<?= number_format($pRate) ?> บ.)</option>
                <option value="มัธยมต้น">มัธยมต้น (<?= number_format($sRate) ?> บ.)</option>
                <option value="มัธยมปลาย">มัธยมปลาย (<?= number_format($uRate) ?> บ.)</option>
            </select>
        </td>
        <td class="py-2.5 px-4">
            <input type="number" name="male_count[]" value="0" min="0" oninput="recalcRow(this)"
                   class="male-input w-full px-2 py-1.5 text-center font-mono font-bold bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900/20">
        </td>
        <td class="py-2.5 px-4">
            <input type="number" name="female_count[]" value="0" min="0" oninput="recalcRow(this)"
                   class="female-input w-full px-2 py-1.5 text-center font-mono font-bold bg-white border border-slate-200 focus:border-blue-900 rounded-lg text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900/20">
        </td>
        <td class="py-2.5 px-4 text-center">
            <span class="row-total font-mono font-bold text-slate-900 text-sm">0</span>
        </td>
        <td class="py-2.5 px-4 text-right">
            <span class="row-rate font-mono text-slate-600"><?= number_format($pRate) ?></span>
        </td>
        <td class="py-2.5 px-4 text-right">
            <span class="row-subsidy font-mono font-bold text-blue-900 text-sm">0</span>
        </td>
        <td class="py-2.5 px-4 text-center">
            <button type="button" onclick="removeGradeRow(this)" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="ลบระดับชั้นนี้">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    lucide.createIcons();
    recalcGrandTotals();
}

function removeGradeRow(btn) {
    const tbody = document.getElementById('studentTableBody');
    if (tbody.querySelectorAll('.student-row').length <= 1) {
        alert('ต้องมีข้อมูลนักเรียนอย่างน้อย 1 ระดับชั้น');
        return;
    }
    if (confirm('ต้องการลบระดับชั้นนี้ใช่หรือไม่?')) {
        btn.closest('.student-row').remove();
        // reindex
        tbody.querySelectorAll('.student-row').forEach((r, i) => {
            r.querySelector('.row-index').innerText = i + 1;
        });
        recalcGrandTotals();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
