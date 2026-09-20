<?php
$pageTitle = 'รายละเอียดงบโครงการ (4 หมวด สพฐ.)';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$projects = getProjectsData();
$selectedId = intval($_GET['id'] ?? ($projects[0]['id'] ?? 1));
$currentProj = null;
foreach ($projects as $p) {
    if ($p['id'] === $selectedId) {
        $currentProj = $p;
        break;
    }
}
if (!$currentProj) $currentProj = $projects[0];

// Standard 4-category items
$expenseItems = [
    ['category' => 'ค่าตอบแทน', 'item' => 'ค่าสมนาคุณวิทยากรบรรยายและฝึกปฏิบัติการ', 'qty' => 15, 'unit' => 'ชั่วโมง', 'price' => 600, 'total' => 9000],
    ['category' => 'ค่าใช้สอย', 'item' => 'ค่าอาหารกลางวันและอาหารว่างสำหรับผู้เข้าร่วมกิจกรรม', 'qty' => 76, 'unit' => 'คน', 'price' => 150, 'total' => 11400],
    ['category' => 'ค่าวัสดุ', 'item' => 'ค่าวัสดุ อุปกรณ์ เอกสารประกอบการฝึกอบรม และข้อสอบ', 'qty' => 76, 'unit' => 'ชุด', 'price' => 323.68, 'total' => 24600],
];
$totalExpense = array_sum(array_column($expenseItems, 'total'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">รายละเอียดงบประมาณโครงการ 4 หมวด สพฐ.</h2>
            <p class="text-xs text-slate-500">จำแนกตาม ค่าตอบแทน, ค่าใช้สอย, ค่าวัสดุ, ค่าครุภัณฑ์ ตามระเบียบการเบิกจ่ายกระทรวงการคลัง</p>
        </div>
        
        <!-- Project Selector Dropdown -->
        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-700">เลือกโครงการ:</label>
            <select name="id" onchange="this.form.submit()" class="text-xs px-3 py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800">
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] === $currentProj['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['project_code']) ?>: <?= htmlspecialchars($p['project_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Active Project Banner -->
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="text-xs font-mono font-bold text-blue-800"><?= htmlspecialchars($currentProj['project_code']) ?></span>
                <h3 class="text-base font-bold text-slate-900"><?= htmlspecialchars($currentProj['project_name']) ?></h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?= htmlspecialchars($currentProj['department']) ?> • ผู้รับผิดชอบ: <?= htmlspecialchars($currentProj['responsible_person']) ?>
                </p>
            </div>
            <div class="text-right">
                <span class="text-[11px] text-slate-500 uppercase font-semibold block">งบประมาณที่ได้รับอนุมัติ</span>
                <span class="text-2xl font-bold font-mono text-blue-900"><?= number_format($currentProj['allocated_budget'], 2) ?> บาท</span>
            </div>
        </div>
    </div>

    <!-- 4-Category Breakdown Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-sm font-bold text-slate-900">ตารางจำแนกรายจ่าย 4 หมวด</h4>
            <span class="text-xs text-slate-500 font-mono">รวมยอดงบ: <?= number_format($totalExpense, 2) ?> บ.</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-2.5 px-4 font-semibold">หมวดรายจ่าย</th>
                        <th class="py-2.5 px-4 font-semibold">รายการค่าใช้จ่าย</th>
                        <th class="py-2.5 px-4 font-semibold text-center w-20">จำนวน</th>
                        <th class="py-2.5 px-4 font-semibold text-center w-20">หน่วย</th>
                        <th class="py-2.5 px-4 font-semibold text-right w-28">ราคา/หน่วย (บาท)</th>
                        <th class="py-2.5 px-4 font-semibold text-right w-32">รวมเงิน (บาท)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($expenseItems as $item): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-bold text-slate-800"><?= htmlspecialchars($item['category']) ?></td>
                            <td class="py-3 px-4 text-slate-700"><?= htmlspecialchars($item['item']) ?></td>
                            <td class="py-3 px-4 text-center font-mono"><?= $item['qty'] ?></td>
                            <td class="py-3 px-4 text-center text-slate-500"><?= htmlspecialchars($item['unit']) ?></td>
                            <td class="py-3 px-4 text-right font-mono text-slate-600"><?= number_format($item['price'], 2) ?></td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-blue-900"><?= number_format($item['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-blue-50 font-bold text-slate-900 border-t border-blue-200">
                        <td colspan="5" class="py-3 px-4 text-right">รวมเงินทั้งสิ้น</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-900 text-sm"><?= number_format($totalExpense, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
