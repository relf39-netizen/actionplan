<?php
$pageTitle = 'การเบิกจ่ายงบประมาณ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$projects = getProjectsData();

// Mock disbursements
if (!isset($_SESSION['disbursements'])) {
    $_SESSION['disbursements'] = [
        [
            'id' => 1,
            'doc_no' => 'ขจ.001/2568',
            'date' => '2024-11-15',
            'project_name' => 'โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)',
            'payee' => 'นายสมควร สอนดี (วิทยากร)',
            'category' => 'ค่าตอบแทน',
            'amount' => 9000.00,
            'status' => 'paid',
            'note' => 'ค่าสมนาคุณวิทยากรติวเข้มรอบที่ 1'
        ],
        [
            'id' => 2,
            'doc_no' => 'ขจ.002/2568',
            'date' => '2024-11-20',
            'project_name' => 'โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)',
            'payee' => 'ร้านครัวคุณแม่',
            'category' => 'ค่าใช้สอย',
            'amount' => 11400.00,
            'status' => 'paid',
            'note' => 'ค่าอาหารกลางวันและอาหารว่างนักเรียนเข้าค่าย'
        ],
        [
            'id' => 3,
            'doc_no' => 'ขจ.003/2568',
            'date' => '2024-12-05',
            'project_name' => 'โครงการปรับปรุงซ่อมแซมอาคารสถานที่และพัฒนาสิ่งแวดล้อมเพื่อความปลอดภัย (Safety School)',
            'payee' => 'หจก.ขอนแก่นการช่าง',
            'category' => 'ค่าวัสดุ',
            'amount' => 50000.00,
            'status' => 'paid',
            'note' => 'ค่าวัสดุปรับปรุงซ่อมแซมระบบไฟฟ้าและสีอาคาร'
        ],
    ];
}

$disbursements = $_SESSION['disbursements'];
$totalDisbursed = array_sum(array_column($disbursements, 'amount'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">การเบิกจ่าย / การใช้เงินตามโครงการ</h2>
            <p class="text-xs text-slate-500">บันทึกฎีกาเบิกเงิน ใบสำคัญรับเงิน และติดตามสถานะการตัดจ่ายงบประมาณ</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 px-4 py-2 rounded-xl text-right">
            <span class="text-[11px] font-bold text-amber-800 uppercase block">ยอดเบิกจ่ายสะสมทั้งสิ้น</span>
            <span class="text-xl font-bold font-mono text-amber-900"><?= number_format($totalDisbursed, 2) ?> บาท</span>
        </div>
    </div>

    <!-- Disbursements Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">ประวัติการเบิกจ่ายเงินงบประมาณ</h3>
            <span class="text-xs text-slate-500">จำนวน <?= count($disbursements) ?> รายการ</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-2.5 px-4 font-semibold">เลขที่เอกสาร</th>
                        <th class="py-2.5 px-4 font-semibold">วันที่เบิกจ่าย</th>
                        <th class="py-2.5 px-4 font-semibold">โครงการที่ขอเบิก</th>
                        <th class="py-2.5 px-4 font-semibold">ผู้รับเงิน / ร้านค้า</th>
                        <th class="py-2.5 px-4 font-semibold">หมวดรายจ่าย</th>
                        <th class="py-2.5 px-4 font-semibold text-right">จำนวนเงิน (บาท)</th>
                        <th class="py-2.5 px-4 font-semibold text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($disbursements as $d): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($d['doc_no']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= formatThaiDate($d['date']) ?></td>
                            <td class="py-3 px-4 font-medium text-slate-900 max-w-xs truncate"><?= htmlspecialchars($d['project_name']) ?></td>
                            <td class="py-3 px-4 text-slate-700"><?= htmlspecialchars($d['payee']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($d['category']) ?></td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900"><?= number_format($d['amount'], 2) ?></td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    จ่ายแล้ว
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
