<?php
$pageTitle = 'ข้อมูลนักเรียนและการคำนวณเงินรายหัว';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$students = getStudentsData();

$totalMale = array_sum(array_column($students, 'male_count'));
$totalFemale = array_sum(array_column($students, 'female_count'));
$totalStudents = array_sum(array_column($students, 'total_count'));

// OBEC Rates
// Kindergarten: 1,800 baht/year
// Primary: 2,050 baht/year
$kCount = 0;
$pCount = 0;
foreach ($students as $s) {
    if ($s['stage'] === 'อนุบาล') {
        $kCount += $s['total_count'];
    } else {
        $pCount += $s['total_count'];
    }
}

$kRate = 1800;
$pRate = 2050;
$kSubsidy = $kCount * $kRate;
$pSubsidy = $pCount * $pRate;
$totalSubsidy = $kSubsidy + $pSubsidy;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">ข้อมูลจำนวนนักเรียนและเงินอุดหนุนรายหัว</h2>
            <p class="text-xs text-slate-500">ข้อมูลนักเรียนรายชั้น ณ วันที่ 10 มิถุนายน ตามฐานข้อมูล DMC สพฐ. เพื่อคำนวณเงินอุดหนุนรายหัว</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-blue-50 text-blue-800 text-xs font-bold rounded-lg border border-blue-200">
                ข้อมูล DMC ยืนยันแล้ว
            </span>
        </div>
    </div>

    <!-- 3 Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">นักเรียนทั้งหมด</div>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-1"><?= number_format($totalStudents) ?> คน</div>
            <div class="text-xs text-slate-500 mt-1">ชาย <?= $totalMale ?> คน / หญิง <?= $totalFemale ?> คน</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">ระดับก่อนประถมศึกษา (อนุบาล 1-3)</div>
            <div class="text-2xl font-bold font-mono text-purple-700 mt-1"><?= number_format($kCount) ?> คน</div>
            <div class="text-xs text-slate-500 mt-1">อัตรา 1,800 บ./คน = <?= number_format($kSubsidy) ?> บาท</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase">ระดับประถมศึกษา (ป.1 - ป.6)</div>
            <div class="text-2xl font-bold font-mono text-blue-700 mt-1"><?= number_format($pCount) ?> คน</div>
            <div class="text-xs text-slate-500 mt-1">อัตรา 2,050 บ./คน = <?= number_format($pSubsidy) ?> บาท</div>
        </div>
    </div>

    <!-- Student Details Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">จำนวนนักเรียนจำแนกตามระดับชั้น</h3>
            <span class="text-xs text-slate-500">ปีการศึกษา <?= $fiscalYear['year'] ?></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-2.5 px-4 font-semibold">ระดับชั้น</th>
                        <th class="py-2.5 px-4 font-semibold">ช่วงชั้น</th>
                        <th class="py-2.5 px-4 font-semibold text-right">นักเรียนชาย</th>
                        <th class="py-2.5 px-4 font-semibold text-right">นักเรียนหญิง</th>
                        <th class="py-2.5 px-4 font-semibold text-right">รวม (คน)</th>
                        <th class="py-2.5 px-4 font-semibold text-right">อัตรา/หัว (บาท)</th>
                        <th class="py-2.5 px-4 font-semibold text-right">เงินอุดหนุนรายหัวรวม (บาท)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($students as $row): ?>
                        <?php 
                            $rate = $row['stage'] === 'อนุบาล' ? $kRate : $pRate;
                            $amount = $row['total_count'] * $rate;
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-2.5 px-4 font-bold text-slate-800"><?= htmlspecialchars($row['grade_level']) ?></td>
                            <td class="py-2.5 px-4 text-slate-600"><?= htmlspecialchars($row['stage']) ?></td>
                            <td class="py-2.5 px-4 text-right font-mono text-slate-600"><?= $row['male_count'] ?></td>
                            <td class="py-2.5 px-4 text-right font-mono text-slate-600"><?= $row['female_count'] ?></td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900"><?= $row['total_count'] ?></td>
                            <td class="py-2.5 px-4 text-right font-mono text-slate-600"><?= number_format($rate) ?></td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-blue-900"><?= number_format($amount) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-blue-50 font-bold text-slate-900 border-t border-blue-200">
                        <td colspan="2" class="py-3 px-4">รวมทั้งสิ้น</td>
                        <td class="py-3 px-4 text-right font-mono"><?= $totalMale ?></td>
                        <td class="py-3 px-4 text-right font-mono"><?= $totalFemale ?></td>
                        <td class="py-3 px-4 text-right font-mono text-blue-900"><?= $totalStudents ?> คน</td>
                        <td class="py-3 px-4 text-right">-</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-900 text-sm"><?= number_format($totalSubsidy, 2) ?> บาท</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
