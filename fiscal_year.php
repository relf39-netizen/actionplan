<?php
$pageTitle = 'ตั้งค่าปีงบประมาณและอัตราเงินอุดหนุน';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';
$errorMsg = '';

// Load current configuration
$config = getFiscalYearConfig($currentSchoolId);
$students = getStudentsData($currentSchoolId);
$totalStudents = array_sum(array_column($students, 'total_count'));

// Count per stage
$kCount = 0; $pCount = 0; $sCount = 0; $uCount = 0;
foreach ($students as $s) {
    $stg = $s['stage'] ?? 'ประถม';
    $cnt = (int)($s['total_count'] ?? 0);
    if ($stg === 'อนุบาล') $kCount += $cnt;
    elseif ($stg === 'มัธยมต้น') $sCount += $cnt;
    elseif ($stg === 'มัธยมปลาย') $uCount += $cnt;
    else $pCount += $cnt;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_fiscal_year_config') {
        $activeYear = intval($_POST['active_year'] ?? 2568);
        $syncRevenues = isset($_POST['sync_revenues']);

        // Rates
        $rates = [
            'kindergarten' => floatval(str_replace(',', '', $_POST['rate_kindergarten'] ?? '1854')),
            'primary' => floatval(str_replace(',', '', $_POST['rate_primary'] ?? '2122')),
            'secondary_lower' => floatval(str_replace(',', '', $_POST['rate_secondary_lower'] ?? '3608')),
            'secondary_upper' => floatval(str_replace(',', '', $_POST['rate_secondary_upper'] ?? '4018')),
            'topup' => floatval(str_replace(',', '', $_POST['rate_topup'] ?? '500')),
            'textbook' => floatval(str_replace(',', '', $_POST['rate_textbook'] ?? '650')),
            'uniform' => floatval(str_replace(',', '', $_POST['rate_uniform'] ?? '400')),
            'stationery' => floatval(str_replace(',', '', $_POST['rate_stationery'] ?? '440')),
            'activity' => floatval(str_replace(',', '', $_POST['rate_activity'] ?? '460')),
            'lunch_per_day' => floatval(str_replace(',', '', $_POST['rate_lunch_per_day'] ?? '24')),
            'lunch_days' => intval($_POST['rate_lunch_days'] ?? 200),
            'poor_fund' => floatval(str_replace(',', '', $_POST['rate_poor_fund'] ?? '1500')),
        ];

        // Fiscal years list
        $existingYears = $config['fiscal_years'] ?? [];
        $found = false;
        foreach ($existingYears as &$fy) {
            if ($fy['year'] === $activeYear) {
                $fy['is_active'] = true;
                $found = true;
            } else {
                $fy['is_active'] = false;
            }
        }
        if (!$found) {
            $existingYears[] = [
                'id' => count($existingYears) + 1,
                'year' => $activeYear,
                'is_active' => true,
                'start_date' => ($activeYear - 544) . '-10-01',
                'end_date' => ($activeYear - 543) . '-09-30',
            ];
        }

        // Proposal window
        $proposalWindow = [
            'is_open' => isset($_POST['proposal_is_open']),
            'open_date' => $_POST['proposal_open_date'] ?? (($activeYear - 544) . '-10-01'),
            'close_date' => $_POST['proposal_close_date'] ?? (($activeYear - 543) . '-01-31'),
            'notice' => trim($_POST['proposal_notice'] ?? "เปิดรับการเสนอโครงการตามแผนปฏิบัติการประจำปีงบประมาณ พ.ศ. {$activeYear}"),
        ];

        $newConfig = [
            'active_year' => $activeYear,
            'fiscal_years' => $existingYears,
            'rates' => $rates,
            'proposal_window' => $proposalWindow,
        ];

        saveFiscalYearConfig($currentSchoolId, $newConfig, $syncRevenues);
        $config = $newConfig;

        $successMsg = 'บันทึกการตั้งค่าปีงบประมาณและเกณฑ์อัตราเงินอุดหนุนเรียบร้อยแล้ว' . ($syncRevenues ? ' และได้ซิงค์คำนวณยอดไปยังหน้าประมาณการรายรับให้อัตโนมัติแล้ว' : '');
    } elseif ($action === 'add_fiscal_year') {
        $newYear = intval($_POST['new_year'] ?? 0);
        if ($newYear >= 2550 && $newYear <= 2600) {
            $existingYears = $config['fiscal_years'] ?? [];
            $already = false;
            foreach ($existingYears as $fy) {
                if ($fy['year'] === $newYear) { $already = true; break; }
            }
            if (!$already) {
                $existingYears[] = [
                    'id' => count($existingYears) + 1,
                    'year' => $newYear,
                    'is_active' => false,
                    'start_date' => ($newYear - 544) . '-10-01',
                    'end_date' => ($newYear - 543) . '-09-30',
                ];
                $config['fiscal_years'] = $existingYears;
                saveFiscalYearConfig($currentSchoolId, $config, false);
                $successMsg = "เพิ่มปีงบประมาณ พ.ศ. {$newYear} เข้าสู่ระบบเรียบร้อยแล้ว";
            } else {
                $errorMsg = "ปีงบประมาณ พ.ศ. {$newYear} มีอยู่ในระบบแล้ว";
            }
        }
    } elseif ($action === 'sync_revenues_now') {
        syncRevenuesFromStudentsAndRates($currentSchoolId);
        $successMsg = 'ซิงค์ข้อมูลจำนวนนักเรียนและอัตราเงินอุดหนุนไปยังประมาณการรายรับ 11 หมวดเรียบร้อยแล้ว';
    }
}

$activeYear = (int)($config['active_year'] ?? 2568);
$rates = $config['rates'] ?? getDefaultSubsidyRates($activeYear);
$fiscalYears = $config['fiscal_years'] ?? [];
$proposalWindow = $config['proposal_window'] ?? [];

// Calculate total estimated general subsidy based on active rates
$kRate = $rates['kindergarten'] ?? 1854;
$pRate = $rates['primary'] ?? 2122;
$sRate = $rates['secondary_lower'] ?? 3608;
$uRate = $rates['secondary_upper'] ?? 4018;

$estK = $kCount * $kRate;
$estP = $pCount * $pRate;
$estS = $sCount * $sRate;
$estU = $uCount * $uRate;
$estTotalSubsidy = $estK + $estP + $estS + $estU;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <a href="settings.php" class="hover:text-blue-900 transition-colors">ตั้งค่า</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">ปีงบประมาณ & อัตราเงินอุดหนุน</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="calendar" class="w-6 h-6 text-blue-900"></i>
                <span>ตั้งค่าปีงบประมาณและเกณฑ์อัตราเงินอุดหนุน</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                กำหนดปีงบประมาณปัจจุบัน อัตราเงินอุดหนุนรายหัว (มติ ครม. ปรับอัตราใหม่) และซิงค์ยอดไปยังประมาณการรายรับอัตโนมัติ
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="sync_revenues_now">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>ซิงค์ไปยังประมาณการรายรับทันที</span>
                </button>
            </form>
            <a href="revenue.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl border border-slate-300 transition-colors">
                <i data-lucide="wallet" class="w-4 h-4"></i>
                <span>ดูประมาณการรายรับ</span>
            </a>
            <a href="students.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl border border-slate-300 transition-colors">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>ดูข้อมูลนักเรียน</span>
            </a>
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

    <!-- Preset Rate Buttons Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white rounded-2xl p-5 mb-6 shadow-md">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold text-blue-200 mb-2">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-300"></i>
                    <span>อัตราเงินอุดหนุนตามมติคณะรัฐมนตรี (ปรับเพิ่มแบบขั้นบันได 4 ปี)</span>
                </span>
                <h3 class="text-base sm:text-lg font-bold">เลือกชุดอัตราเงินอุดหนุนมาตรฐาน (Presets)</h3>
                <p class="text-xs text-slate-300 mt-0.5">
                    คลิกเพื่อนำเข้าอัตราที่รัฐบาลปรับเพิ่ม หรือท่านสามารถแก้ไขระบุตัวเลขเองได้ตามต้องการ
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="applyPreset('step2')" class="px-3.5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold rounded-xl shadow-xs transition-all">
                    มติ ครม. ขั้นที่ 2 (ปี 2568-2569) ⭐
                </button>
                <button type="button" onclick="applyPreset('step1')" class="px-3.5 py-2 bg-white/15 hover:bg-white/25 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all">
                    มติ ครม. ขั้นที่ 1 (ปี 2566-2567)
                </button>
                <button type="button" onclick="applyPreset('legacy')" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-slate-300 text-xs font-semibold rounded-xl transition-all">
                    เกณฑ์เดิม (ก่อนปี 2566)
                </button>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <form method="POST" id="fiscalYearForm" class="space-y-6">
        <input type="hidden" name="action" value="save_fiscal_year_config">

        <!-- Section 1: Active Fiscal Year Selection -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-900 flex items-center justify-center font-bold">
                        1
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">กำหนดปีงบประมาณปัจจุบัน (Active Fiscal Year)</h3>
                        <p class="text-xs text-slate-500">เลือกว่าระบบกำลังวางแผนและเบิกจ่ายงบประมาณของปี พ.ศ. ใด</p>
                    </div>
                </div>
                <div class="text-xs text-slate-500">
                    รอบปีงบประมาณ: 1 ตุลาคม - 30 กันยายน
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-xs font-bold text-slate-700">คลิกเลือกปีงบประมาณที่ต้องการเปิดใช้งาน:</label>
                <div class="flex flex-wrap gap-2.5">
                    <?php foreach ($fiscalYears as $fy): ?>
                        <?php $isActive = ($fy['year'] === $activeYear); ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="active_year" value="<?= $fy['year'] ?>" <?= $isActive ? 'checked' : '' ?> class="sr-only peer" onchange="onYearChanged(this.value)">
                            <div class="flex items-center gap-2 px-5 py-2.5 rounded-xl border text-xs font-bold transition-all peer-checked:bg-blue-900 peer-checked:text-amber-300 peer-checked:border-blue-900 peer-checked:shadow-sm bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100">
                                <span>ปีงบประมาณ พ.ศ. <?= $fy['year'] ?></span>
                                <span class="w-2 h-2 rounded-full <?= $isActive ? 'bg-amber-300' : 'bg-slate-300' ?>"></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Section 2: Per-Student Subsidy Rates (เงินอุดหนุนรายหัวตามช่วงชั้น) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-900 flex items-center justify-center font-bold">
                    2
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">ตั้งค่าอัตราเงินอุดหนุนรายหัว (บาท/คน/ปี)</h3>
                    <p class="text-xs text-slate-500">อัตราตามมติ ครม. ที่ปรับเปลี่ยนตามแต่ละปีงบประมาณ สามารถปรับแก้ตัวเลขได้ตามจริง</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Kindergarten -->
                <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-purple-900">ก่อนประถม (อนุบาล 1-3)</span>
                        <span class="text-[10px] bg-purple-100 text-purple-800 font-bold px-2 py-0.5 rounded-full">อ.1 - อ.3</span>
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-600 block mb-1">อัตราเงินอุดหนุนรายหัว (บาท/คน/ปี):</label>
                        <input type="number" step="1" name="rate_kindergarten" id="rate_kindergarten" value="<?= $rates['kindergarten'] ?? 1854 ?>" oninput="recalcPreview()" required
                               class="w-full text-base font-bold font-mono px-3 py-1.5 border border-purple-300 rounded-lg bg-white text-purple-950 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                    <div class="text-[11px] text-slate-500 pt-1 border-t border-purple-100 flex justify-between">
                        <span>นักเรียนปัจจุบัน: <strong><?= $kCount ?></strong> คน</span>
                        <span>รวม: <strong id="prev_sub_k" class="text-purple-900 font-mono"><?= number_format($estK) ?></strong> บ.</span>
                    </div>
                </div>

                <!-- Primary -->
                <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-900">ประถมศึกษา (ป.1 - ป.6)</span>
                        <span class="text-[10px] bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded-full">ป.1 - ป.6</span>
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-600 block mb-1">อัตราเงินอุดหนุนรายหัว (บาท/คน/ปี):</label>
                        <input type="number" step="1" name="rate_primary" id="rate_primary" value="<?= $rates['primary'] ?? 2122 ?>" oninput="recalcPreview()" required
                               class="w-full text-base font-bold font-mono px-3 py-1.5 border border-blue-300 rounded-lg bg-white text-blue-950 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="text-[11px] text-slate-500 pt-1 border-t border-blue-100 flex justify-between">
                        <span>นักเรียนปัจจุบัน: <strong><?= $pCount ?></strong> คน</span>
                        <span>รวม: <strong id="prev_sub_p" class="text-blue-900 font-mono"><?= number_format($estP) ?></strong> บ.</span>
                    </div>
                </div>

                <!-- Secondary Lower -->
                <div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-900">มัธยมศึกษาตอนต้น (ม.1 - ม.3)</span>
                        <span class="text-[10px] bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-full">ม.1 - ม.3</span>
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-600 block mb-1">อัตราเงินอุดหนุนรายหัว (บาท/คน/ปี):</label>
                        <input type="number" step="1" name="rate_secondary_lower" id="rate_secondary_lower" value="<?= $rates['secondary_lower'] ?? 3608 ?>" oninput="recalcPreview()"
                               class="w-full text-base font-bold font-mono px-3 py-1.5 border border-indigo-300 rounded-lg bg-white text-indigo-950 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="text-[11px] text-slate-500 pt-1 border-t border-indigo-100 flex justify-between">
                        <span>นักเรียนปัจจุบัน: <strong><?= $sCount ?></strong> คน</span>
                        <span>รวม: <strong id="prev_sub_s" class="text-indigo-900 font-mono"><?= number_format($estS) ?></strong> บ.</span>
                    </div>
                </div>

                <!-- Secondary Upper -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">มัธยมศึกษาตอนปลาย (ม.4 - ม.6)</span>
                        <span class="text-[10px] bg-slate-200 text-slate-800 font-bold px-2 py-0.5 rounded-full">ม.4 - ม.6</span>
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-600 block mb-1">อัตราเงินอุดหนุนรายหัว (บาท/คน/ปี):</label>
                        <input type="number" step="1" name="rate_secondary_upper" id="rate_secondary_upper" value="<?= $rates['secondary_upper'] ?? 4018 ?>" oninput="recalcPreview()"
                               class="w-full text-base font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-200 flex justify-between">
                        <span>นักเรียนปัจจุบัน: <strong><?= $uCount ?></strong> คน</span>
                        <span>รวม: <strong id="prev_sub_u" class="text-slate-900 font-mono"><?= number_format($estU) ?></strong> บ.</span>
                    </div>
                </div>
            </div>

            <!-- Top Up rate -->
            <div class="pt-2">
                <label class="text-xs font-semibold text-slate-700 block mb-1">
                    เงินอุดหนุนรายหัวส่วนเพิ่ม (Top Up) โรงเรียนคุณภาพ / สนับสนุนพิเศษ (บาท/คน/ปี):
                </label>
                <div class="max-w-xs flex items-center gap-2">
                    <input type="number" step="1" name="rate_topup" id="rate_topup" value="<?= $rates['topup'] ?? 500 ?>" oninput="recalcPreview()"
                           class="w-full text-sm font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span class="text-xs text-slate-500 shrink-0">บาท/คน/ปี</span>
                </div>
            </div>
        </div>

        <!-- Section 3: 5 Welfare & Lunch Rates (เกณฑ์เรียนฟรี 15 ปี & อาหารกลางวัน) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-900 flex items-center justify-center font-bold">
                    3
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">เกณฑ์อัตราโครงการเรียนฟรี 15 ปี และโครงการอาหารกลางวัน</h3>
                    <p class="text-xs text-slate-500">อัตราเฉลี่ยที่ใช้ในการคำนวณประมาณการรายรับหมวดที่ 3, 4, 5, 6, 8</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Textbook -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                    <label class="text-xs font-bold text-slate-800 block">3. ค่าหนังสือเรียนเฉลี่ย (บาท/คน/ปี):</label>
                    <input type="number" name="rate_textbook" id="rate_textbook" value="<?= $rates['textbook'] ?? 650 ?>" oninput="recalcPreview()"
                           class="w-full text-sm font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg bg-white">
                    <span class="text-[10px] text-slate-500">เกณฑ์จัดสรรหนังสือเรียนตามระดับการศึกษา สพฐ.</span>
                </div>

                <!-- Uniform -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                    <label class="text-xs font-bold text-slate-800 block">4. ค่าเครื่องแบบนักเรียน (2 ชุด/คน/ปี):</label>
                    <input type="number" name="rate_uniform" id="rate_uniform" value="<?= $rates['uniform'] ?? 400 ?>" oninput="recalcPreview()"
                           class="w-full text-sm font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg bg-white">
                    <span class="text-[10px] text-slate-500">อนุบาล 325 บ., ประถม 400 บ., ม.ต้น 500 บ.</span>
                </div>

                <!-- Stationery -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                    <label class="text-xs font-bold text-slate-800 block">5. ค่าอุปกรณ์การเรียน (บาท/คน/ปี):</label>
                    <input type="number" name="rate_stationery" id="rate_stationery" value="<?= $rates['stationery'] ?? 440 ?>" oninput="recalcPreview()"
                           class="w-full text-sm font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg bg-white">
                    <span class="text-[10px] text-slate-500">จัดสรร 2 ภาคเรียน (สมุด ดินสอ ยางลบ สี)</span>
                </div>

                <!-- Learner Activity -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                    <label class="text-xs font-bold text-slate-800 block">6. ค่ากิจกรรมพัฒนาผู้เรียน (บาท/คน/ปี):</label>
                    <input type="number" name="rate_activity" id="rate_activity" value="<?= $rates['activity'] ?? 460 ?>" oninput="recalcPreview()"
                           class="w-full text-sm font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg bg-white">
                    <span class="text-[10px] text-slate-500">4 กิจกรรมหลัก (วิชาการ, คุณธรรม, ทัศนศึกษา, ICT)</span>
                </div>

                <!-- Lunch -->
                <div class="p-3.5 bg-amber-50/50 rounded-xl border border-amber-200 space-y-1.5">
                    <label class="text-xs font-bold text-amber-950 block">8. ค่าอาหารกลางวัน (อปท. จัดสรร):</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] text-amber-900 block">บาท/คน/วัน:</span>
                            <input type="number" name="rate_lunch_per_day" id="rate_lunch_per_day" value="<?= $rates['lunch_per_day'] ?? 24 ?>" oninput="recalcPreview()"
                                   class="w-full text-sm font-bold font-mono px-2 py-1.5 border border-amber-300 rounded-lg bg-white">
                        </div>
                        <div>
                            <span class="text-[10px] text-amber-900 block">วันทำการ:</span>
                            <input type="number" name="rate_lunch_days" id="rate_lunch_days" value="<?= $rates['lunch_days'] ?? 200 ?>" oninput="recalcPreview()"
                                   class="w-full text-sm font-bold font-mono px-2 py-1.5 border border-amber-300 rounded-lg bg-white">
                        </div>
                    </div>
                    <span class="text-[10px] text-amber-800 block">= <strong id="prev_lunch_rate" class="font-mono">4,800</strong> บาท/คน/ปี</span>
                </div>

                <!-- Poor fund -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                    <label class="text-xs font-bold text-slate-800 block">7. ปัจจัยพื้นฐานนักเรียนยากจน (กสศ.):</label>
                    <input type="number" name="rate_poor_fund" id="rate_poor_fund" value="<?= $rates['poor_fund'] ?? 1500 ?>" oninput="recalcPreview()"
                           class="w-full text-sm font-bold font-mono px-3 py-1.5 border border-slate-300 rounded-lg bg-white">
                    <span class="text-[10px] text-slate-500">จัดสรรเฉพาะนักเรียนที่ผ่านเกณฑ์คัดกรอง</span>
                </div>
            </div>
        </div>

        <!-- Section 4: Live Preview & Sync to Revenue option -->
        <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-white rounded-2xl border border-blue-200 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-blue-200 pb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="calculator" class="w-5 h-5 text-blue-900"></i>
                    <div>
                        <h3 class="text-sm font-bold text-blue-950">คำนวณและเชื่อมโยงไปยังประมาณการรายรับ (Live Preview)</h3>
                        <p class="text-xs text-slate-600">ตรวจสอบยอดประมาณการคำนวณอัตโนมัติตามจำนวนนักเรียนปัจจุบัน (<?= $totalStudents ?> คน)</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-slate-500 block uppercase">เงินอุดหนุนรายหัวรวม</span>
                    <span id="prev_total_subsidy" class="text-xl font-bold font-mono text-blue-900"><?= number_format($estTotalSubsidy, 2) ?> ฿</span>
                </div>
            </div>

            <!-- Checkbox for Auto-sync -->
            <div class="p-3.5 bg-white rounded-xl border border-blue-300 shadow-xs flex items-center justify-between gap-3">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="sync_revenues" value="1" checked class="w-4 h-4 rounded text-blue-900 mt-0.5 focus:ring-blue-900">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">
                            ซิงค์และคำนวณยอดเงินในเมนู "ประมาณการรายรับ 11 หมวด" ให้สอดคล้องกันทันที
                        </span>
                        <span class="text-[11px] text-slate-500">
                            ระบบจะนำจำนวนนักเรียนจากเมนูข้อมูลนักเรียน มาคูณกับอัตราปีงบประมาณที่กำหนดด้านบน แล้วอัปเดตลงในตารางรายรับอัตโนมัติ
                        </span>
                    </div>
                </label>
                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-lg shrink-0">
                    แนะนำให้เปิดไว้
                </span>
            </div>
        </div>

        <!-- Section 5: Project Proposal Window (เปิด/ปิดรับการเสนอโครงการ) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="clock" class="w-5 h-5 text-indigo-700"></i>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">ตั้งค่าเปิด/ปิดรับการเสนอโครงการจากคุณครู</h3>
                        <p class="text-xs text-slate-500">กำหนดช่วงเวลาให้คุณครูสามารถเข้ามาเสนอโครงการในแผนปฏิบัติการประจำปี</p>
                    </div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="proposal_is_open" value="1" <?= (!empty($proposalWindow['is_open'])) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600 relative"></div>
                    <span class="text-xs font-bold text-slate-800">เปิดรับข้อเสนอ</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">วันที่เริ่มเปิดรับข้อเสนอ:</label>
                    <input type="date" name="proposal_open_date" value="<?= htmlspecialchars($proposalWindow['open_date'] ?? (($activeYear - 544) . '-10-01')) ?>"
                           class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">วันที่สิ้นสุด/ปิดรับข้อเสนอ:</label>
                    <input type="date" name="proposal_close_date" value="<?= htmlspecialchars($proposalWindow['close_date'] ?? (($activeYear - 543) . '-01-31')) ?>"
                           class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ประกาศคำชี้แจงสำหรับคุณครู:</label>
                <textarea name="proposal_notice" rows="2" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg"><?= htmlspecialchars($proposalWindow['notice'] ?? "เปิดรับการเสนอโครงการตามแผนปฏิบัติการประจำปีงบประมาณ พ.ศ. {$activeYear}") ?></textarea>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <div class="text-xs text-slate-500">
                * ข้อมูลที่บันทึกจะถูกจัดเก็บแยกตามโรงเรียน และอัปเดตลงฐานข้อมูลถาวร
            </div>
            <div class="flex items-center gap-3">
                <a href="revenue.php" class="px-5 py-2.5 border border-slate-300 hover:bg-slate-100 rounded-xl text-xs font-bold text-slate-700 transition-colors">
                    ยกเลิก
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-8 py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-md transition-all">
                    <i data-lucide="save" class="w-4 h-4 text-amber-300"></i>
                    <span>บันทึกการตั้งค่าปีงบประมาณ</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Section: Add New Fiscal Year Modal/Form -->
    <div class="mt-8 pt-6 border-t border-slate-200">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <h4 class="text-sm font-bold text-slate-900 mb-1">เปิดปีงบประมาณใหม่เพิ่มเติม</h4>
            <p class="text-xs text-slate-500 mb-3">หากต้องการวางแผนหรือเตรียมข้อมูลสำหรับปีงบประมาณถัดไป (เช่น พ.ศ. 2569, 2570) สามารถเพิ่มได้ที่นี่</p>
            <form method="POST" class="flex items-center gap-3">
                <input type="hidden" name="action" value="add_fiscal_year">
                <input type="number" name="new_year" value="<?= max(array_column($fiscalYears, 'year')) + 1 ?>" min="2550" max="2600" class="w-36 text-sm font-bold font-mono px-3 py-2 border border-slate-300 rounded-xl">
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition-colors">
                    + เพิ่มปีงบประมาณ
                </button>
            </form>
        </div>
    </div>
</main>

<script>
const PRESETS = {
    step2: {
        kindergarten: 1854,
        primary: 2122,
        secondary_lower: 3608,
        secondary_upper: 4018,
        topup: 500,
        textbook: 650,
        uniform: 400,
        stationery: 440,
        activity: 460,
        lunch_per_day: 24,
        lunch_days: 200,
        poor_fund: 1500
    },
    step1: {
        kindergarten: 1800,
        primary: 2050,
        secondary_lower: 3500,
        secondary_upper: 3800,
        topup: 500,
        textbook: 650,
        uniform: 380,
        stationery: 400,
        activity: 460,
        lunch_per_day: 24,
        lunch_days: 200,
        poor_fund: 1500
    },
    legacy: {
        kindergarten: 1700,
        primary: 1900,
        secondary_lower: 3000,
        secondary_upper: 3500,
        topup: 500,
        textbook: 600,
        uniform: 360,
        stationery: 390,
        activity: 460,
        lunch_per_day: 22,
        lunch_days: 200,
        poor_fund: 1500
    }
};

const studentCounts = {
    k: <?= $kCount ?>,
    p: <?= $pCount ?>,
    s: <?= $sCount ?>,
    u: <?= $uCount ?>
};

function applyPreset(presetKey) {
    const data = PRESETS[presetKey];
    if (!data) return;

    for (const [key, val] of Object.entries(data)) {
        const input = document.getElementById('rate_' + key);
        if (input) {
            input.value = val;
        }
    }
    recalcPreview();
}

function recalcPreview() {
    const kRate = parseFloat(document.getElementById('rate_kindergarten').value) || 0;
    const pRate = parseFloat(document.getElementById('rate_primary').value) || 0;
    const sRate = parseFloat(document.getElementById('rate_secondary_lower').value) || 0;
    const uRate = parseFloat(document.getElementById('rate_secondary_upper').value) || 0;

    const estK = studentCounts.k * kRate;
    const estP = studentCounts.p * pRate;
    const estS = studentCounts.s * sRate;
    const estU = studentCounts.u * uRate;
    const total = estK + estP + estS + estU;

    document.getElementById('prev_sub_k').innerText = estK.toLocaleString('th-TH');
    document.getElementById('prev_sub_p').innerText = estP.toLocaleString('th-TH');
    document.getElementById('prev_sub_s').innerText = estS.toLocaleString('th-TH');
    document.getElementById('prev_sub_u').innerText = estU.toLocaleString('th-TH');
    document.getElementById('prev_total_subsidy').innerText = total.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';

    const lunchDay = parseFloat(document.getElementById('rate_lunch_per_day').value) || 0;
    const lunchDays = parseInt(document.getElementById('rate_lunch_days').value) || 0;
    document.getElementById('prev_lunch_rate').innerText = (lunchDay * lunchDays).toLocaleString('th-TH');
}

function onYearChanged(val) {
    const year = parseInt(val);
    if (year >= 2569) {
        applyPreset('step2');
    } else if (year >= 2568) {
        applyPreset('step2');
    } else {
        applyPreset('step1');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
