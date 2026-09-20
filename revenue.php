<?php
$pageTitle = 'ประมาณการรายรับสถานศึกษา';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';
$errorMsg = '';

// Handle POST request to update revenue items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_revenues') {
        $itemNames = $_POST['item_name'] ?? [];
        $rates = $_POST['rate_per_head'] ?? [];
        $counts = $_POST['eligible_count'] ?? [];
        $amounts = $_POST['calculated_amount'] ?? [];
        $notes = $_POST['note'] ?? [];
        
        $newRevenues = [];
        for ($i = 0; $i < count($itemNames); $i++) {
            $name = trim($itemNames[$i] ?? '');
            if (empty($name)) continue;

            $rate = floatval(str_replace(',', '', $rates[$i] ?? '0'));
            $count = floatval(str_replace(',', '', $counts[$i] ?? '0'));
            $amount = floatval(str_replace(',', '', $amounts[$i] ?? '0'));
            
            // If rate > 0 and amount not manually specified differently, auto-calc
            if ($rate > 0 && $count > 0 && empty($amount)) {
                $amount = $rate * $count;
            }

            $newRevenues[] = [
                'id' => $i + 1,
                'item_name' => $name,
                'rate_per_head' => $rate,
                'eligible_count' => $count,
                'calculated_amount' => $amount,
                'note' => trim($notes[$i] ?? ''),
            ];
        }

        saveRevenuesData($currentSchoolId, $newRevenues);
        $successMsg = 'บันทึกการประมาณการรายรับสถานศึกษาเรียบร้อยแล้ว';
    }
}

$revenues = getRevenuesData($currentSchoolId);
$totalRevenue = array_sum(array_column($revenues, 'calculated_amount'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb and Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">ประมาณการรายรับ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="wallet" class="w-6 h-6 text-blue-900"></i>
                <span>ประมาณการรายรับสถานศึกษา 11 หมวด</span>
            </h2>
            <p class="text-xs text-slate-500">เงินอุดหนุนทั่วไป, เงินเรียนฟรี 15 ปี, กสศ., อาหารกลางวัน, เงินระดมทรัพยากร และเงินรายได้สถานศึกษา</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openRevenueModal()" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
                <span>แก้ไขรายการรายรับ</span>
            </button>
            <div class="bg-blue-50 border border-blue-200 px-4 py-1.5 rounded-xl text-right">
                <span class="text-[10px] font-bold text-slate-500 uppercase block">ยอดประมาณการรวม</span>
                <span class="text-lg font-bold font-mono text-blue-900"><?= number_format($totalRevenue, 2) ?> บาท</span>
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

    <!-- Revenues Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i data-lucide="receipt" class="w-4 h-4 text-blue-900"></i>
                <h3 class="text-sm font-bold text-slate-900">ตารางรายละเอียดประมาณการรายรับ</h3>
            </div>
            <span class="text-xs text-slate-500 font-mono"><?= count($revenues) ?> รายการ</span>
        </div>

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
                    <tr class="bg-blue-50/70 font-bold text-slate-900 border-t-2 border-blue-200 text-sm">
                        <td colspan="4" class="py-3.5 px-4 text-right">รวมประมาณการรายรับทั้งสิ้น (<?= count($revenues) ?> หมวด)</td>
                        <td class="py-3.5 px-4 text-right font-mono text-blue-900"><?= number_format($totalRevenue, 2) ?></td>
                        <td class="py-3.5 px-4 text-xs font-normal text-slate-600">(<?= bahtText($totalRevenue) ?>)</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<!-- Modal: แก้ไขประมาณการรายรับ -->
<div id="revenueModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-5 h-5 text-blue-900"></i>
                    <span>แก้ไขประมาณการรายรับสถานศึกษา</span>
                </h3>
                <p class="text-xs text-slate-500">ปรับจำนวนนักเรียน อัตราต่อคน หรือยอดเงินประมาณการ</p>
            </div>
            <button type="button" onclick="closeRevenueModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="save_revenues">

            <div class="space-y-3" id="revenueRowsContainer">
                <?php foreach ($revenues as $idx => $r): ?>
                    <div class="revenue-row p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-5">
                                <label class="block font-bold text-slate-700 mb-1">หมวดรายรับ #<?= $idx + 1 ?></label>
                                <input type="text" name="item_name[]" value="<?= htmlspecialchars($r['item_name']) ?>" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-semibold focus:outline-none focus:ring-1 focus:ring-blue-900">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 mb-1">อัตรา/คน (บ.)</label>
                                <input type="number" step="0.01" min="0" name="rate_per_head[]" value="<?= $r['rate_per_head'] ?>" 
                                       class="rate-input w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900"
                                       oninput="calcRow(this)">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 mb-1">จำนวนเป้าหมาย</label>
                                <input type="number" step="1" min="0" name="eligible_count[]" value="<?= $r['eligible_count'] ?>" 
                                       class="count-input w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900"
                                       oninput="calcRow(this)">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block font-bold text-slate-700 mb-1">ยอดเงินรวม (บ.)</label>
                                <input type="number" step="0.01" min="0" name="calculated_amount[]" value="<?= $r['calculated_amount'] ?>" 
                                       class="amount-input w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right font-bold text-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-11">
                                <input type="text" name="note[]" value="<?= htmlspecialchars($r['note'] ?? '') ?>" placeholder="หมายเหตุ / เกณฑ์การจัดสรร..." 
                                       class="w-full px-3 py-1 bg-white border border-slate-200 rounded-lg text-[11px] text-slate-600 focus:outline-none">
                            </div>
                            <div class="sm:col-span-1 text-center">
                                <button type="button" onclick="removeRevenueRow(this)" class="p-1 text-rose-500 hover:bg-rose-50 rounded" title="ลบรายการนี้">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="addRevenueRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 hover:bg-slate-100 rounded-lg text-xs font-bold text-slate-700">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>เพิ่มหมวดรายรับใหม่</span>
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeRevenueModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>บันทึกรายรับทั้งหมด</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calcRow(el) {
    const row = el.closest('.revenue-row');
    const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
    const count = parseFloat(row.querySelector('.count-input').value) || 0;
    if (rate > 0 && count > 0) {
        row.querySelector('.amount-input').value = (rate * count).toFixed(2);
    }
}

function removeRevenueRow(btn) {
    if (confirm('ต้องการลบหมวดรายรับนี้หรือไม่?')) {
        btn.closest('.revenue-row').remove();
    }
}

function addRevenueRow() {
    const container = document.getElementById('revenueRowsContainer');
    const row = document.createElement('div');
    row.className = 'revenue-row p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2';
    row.innerHTML = `
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-5">
                <label class="block font-bold text-slate-700 mb-1">หมวดรายรับใหม่</label>
                <input type="text" name="item_name[]" placeholder="เช่น เงินระดมทรัพยากรเพื่อการศึกษา" required
                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-semibold focus:outline-none focus:ring-1 focus:ring-blue-900">
            </div>
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">อัตรา/คน (บ.)</label>
                <input type="number" step="0.01" min="0" name="rate_per_head[]" value="0" 
                       class="rate-input w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900"
                       oninput="calcRow(this)">
            </div>
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">จำนวนเป้าหมาย</label>
                <input type="number" step="1" min="0" name="eligible_count[]" value="1" 
                       class="count-input w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-900"
                       oninput="calcRow(this)">
            </div>
            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-700 mb-1">ยอดเงินรวม (บ.)</label>
                <input type="number" step="0.01" min="0" name="calculated_amount[]" value="0.00" 
                       class="amount-input w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right font-bold text-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-11">
                <input type="text" name="note[]" placeholder="หมายเหตุ / รายละเอียดเพิ่มเติม..." 
                       class="w-full px-3 py-1 bg-white border border-slate-200 rounded-lg text-[11px] text-slate-600 focus:outline-none">
            </div>
            <div class="sm:col-span-1 text-center">
                <button type="button" onclick="removeRevenueRow(this)" class="p-1 text-rose-500 hover:bg-rose-50 rounded" title="ลบรายการนี้">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
    lucide.createIcons();
}

function openRevenueModal() {
    document.getElementById('revenueModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeRevenueModal() {
    document.getElementById('revenueModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

