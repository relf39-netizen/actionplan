<?php
$pageTitle = 'ประมาณการรายรับสถานศึกษา';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$revenues = getRevenuesData();
$totalRevenue = array_sum(array_column($revenues, 'calculated_amount'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">ประมาณการรายรับสถานศึกษา 11 หมวด</h2>
            <p class="text-xs text-slate-500">เงินอุดหนุนทั่วไป, เงินเรียนฟรี 15 ปี, กสศ., อาหารกลางวัน, เงินระดมทรัพยากร และเงินรายได้สถานศึกษา</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 px-4 py-2 rounded-xl text-right">
            <span class="text-[11px] font-bold text-slate-500 uppercase block">ยอดประมาณการรายรับรวม</span>
            <span class="text-xl font-bold font-mono text-blue-900"><?= number_format($totalRevenue, 2) ?> บาท</span>
        </div>
    </div>

    <!-- Revenues Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-3 px-4 font-semibold w-12 text-center">ลำดับ</th>
                        <th class="py-3 px-4 font-semibold">รายการรายรับตามระเบียบ สพฐ.</th>
                        <th class="py-3 px-4 font-semibold text-right w-32">อัตรา/หน่วย (บาท)</th>
                        <th class="py-3 px-4 font-semibold text-right w-28">จำนวนเป้าหมาย</th>
                        <th class="py-3 px-4 font-semibold text-right w-36">ประมาณการยอดเงิน (บาท)</th>
                        <th class="py-3 px-4 font-semibold">หมายเหตุ / เกณฑ์การจัดสรร</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($revenues as $idx => $r): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 text-center font-bold text-slate-400"><?= $idx + 1 ?></td>
                            <td class="py-3 px-4 font-semibold text-slate-800"><?= htmlspecialchars($r['item_name']) ?></td>
                            <td class="py-3 px-4 text-right font-mono text-slate-600">
                                <?= $r['rate_per_head'] > 0 ? number_format($r['rate_per_head']) : '-' ?>
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-700">
                                <?= number_format($r['eligible_count']) ?> <?= $r['rate_per_head'] > 0 ? 'คน' : 'รายการ' ?>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-blue-950">
                                <?= number_format($r['calculated_amount'], 2) ?>
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]"><?= htmlspecialchars($r['note'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-blue-50 font-bold text-slate-900 border-t border-blue-200 text-sm">
                        <td colspan="4" class="py-3.5 px-4 text-right">รวมประมาณการรายรับทั้งสิ้น (11 หมวด)</td>
                        <td class="py-3.5 px-4 text-right font-mono text-blue-900"><?= number_format($totalRevenue, 2) ?></td>
                        <td class="py-3.5 px-4 text-xs font-normal text-slate-600">(<?= bahtText($totalRevenue) ?>)</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
