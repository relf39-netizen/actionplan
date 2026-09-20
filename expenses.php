<?php
$pageTitle = 'รายละเอียดงบโครงการ (4 หมวด สพฐ.)';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';

$projects = getProjectsData($currentSchoolId);
$selectedId = intval($_GET['id'] ?? ($projects[0]['id'] ?? 1));
$currentProj = null;
foreach ($projects as $p) {
    if ((int)$p['id'] === $selectedId) {
        $currentProj = $p;
        break;
    }
}
if (!$currentProj && !empty($projects)) {
    $currentProj = $projects[0];
    $selectedId = (int)$currentProj['id'];
}

// Handle POST request to update 4-category items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_expenses') {
    $categories = $_POST['category'] ?? [];
    $items = $_POST['item'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $units = $_POST['unit'] ?? [];
    $prices = $_POST['price'] ?? [];
    $totals = $_POST['total'] ?? [];

    $newExpenseItems = [];
    for ($i = 0; $i < count($items); $i++) {
        $itemName = trim($items[$i] ?? '');
        if (empty($itemName)) continue;

        $qty = floatval(str_replace(',', '', $qtys[$i] ?? 1));
        $price = floatval(str_replace(',', '', $prices[$i] ?? 0));
        $total = floatval(str_replace(',', '', $totals[$i] ?? 0));
        if ($total <= 0 && $qty > 0 && $price > 0) {
            $total = $qty * $price;
        }

        $newExpenseItems[] = [
            'category' => trim($categories[$i] ?? 'ค่าใช้สอย'),
            'item' => $itemName,
            'qty' => $qty,
            'unit' => trim($units[$i] ?? 'รายการ'),
            'price' => $price,
            'total' => $total,
        ];
    }

    saveProjectExpensesData($currentSchoolId, $selectedId, $newExpenseItems);
    $successMsg = 'บันทึกรายละเอียดงบประมาณ 4 หมวดของโครงการเรียบร้อยแล้ว';
}

$expenseItems = $currentProj ? getProjectExpensesData($currentSchoolId, $selectedId) : [];
$totalExpense = array_sum(array_column($expenseItems, 'total'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <a href="projects.php" class="hover:text-blue-900 transition-colors">โครงการ</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">รายละเอียดงบ 4 หมวด</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="layers" class="w-6 h-6 text-blue-900"></i>
                <span>รายละเอียดงบประมาณโครงการ 4 หมวด สพฐ.</span>
            </h2>
            <p class="text-xs text-slate-500">จำแนกตาม ค่าตอบแทน, ค่าใช้สอย, ค่าวัสดุ, ค่าครุภัณฑ์ ตามระเบียบการเบิกจ่ายกระทรวงการคลัง</p>
        </div>
        
        <!-- Project Selector Dropdown -->
        <div class="flex items-center gap-2.5">
            <form method="GET" class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-700">เลือกโครงการ:</label>
                <select name="id" onchange="this.form.submit()" class="text-xs px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-900/20 font-semibold text-slate-800">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] === $selectedId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['project_code']) ?>: <?= htmlspecialchars($p['project_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button type="button" onclick="openExpenseModal()" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
                <span>แก้ไขรายการ 4 หมวด</span>
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

    <?php if ($currentProj): ?>
        <!-- Active Project Banner -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="text-xs font-mono font-bold text-blue-900"><?= htmlspecialchars($currentProj['project_code']) ?></span>
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
            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h4 class="text-sm font-bold text-slate-900">ตารางจำแนกรายจ่าย 4 หมวด</h4>
                <span class="text-xs text-slate-500 font-mono">รวมยอดงบ: <?= number_format($totalExpense, 2) ?> บ.</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="py-2.5 px-4 font-semibold w-28">หมวดรายจ่าย</th>
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
                                <td class="py-3 px-4 font-bold text-slate-800">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($item['category']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-800 font-medium"><?= htmlspecialchars($item['item']) ?></td>
                                <td class="py-3 px-4 text-center font-mono"><?= $item['qty'] ?></td>
                                <td class="py-3 px-4 text-center text-slate-500"><?= htmlspecialchars($item['unit']) ?></td>
                                <td class="py-3 px-4 text-right font-mono text-slate-600"><?= number_format($item['price'], 2) ?></td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-blue-900"><?= number_format($item['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-blue-50/70 font-bold text-slate-900 border-t-2 border-blue-200">
                            <td colspan="5" class="py-3.5 px-4 text-right">รวมเงินทั้งสิ้น</td>
                            <td class="py-3.5 px-4 text-right font-mono text-blue-900 text-sm"><?= number_format($totalExpense, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Modal: แก้ไขรายการรายจ่าย 4 หมวด -->
<div id="expenseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-5 h-5 text-blue-900"></i>
                    <span>แก้ไขรายละเอียดงบประมาณ 4 หมวด</span>
                </h3>
                <p class="text-xs text-slate-500">โครงการ: <?= htmlspecialchars($currentProj['project_name'] ?? '') ?></p>
            </div>
            <button type="button" onclick="closeExpenseModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="save_expenses">

            <div class="space-y-3" id="expenseRowsContainer">
                <?php foreach ($expenseItems as $idx => $item): ?>
                    <div class="expense-row p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-3">
                                <label class="block font-bold text-slate-700 mb-1">หมวดรายจ่าย</label>
                                <select name="category[]" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-none">
                                    <option value="ค่าตอบแทน" <?= $item['category'] === 'ค่าตอบแทน' ? 'selected' : '' ?>>ค่าตอบแทน</option>
                                    <option value="ค่าใช้สอย" <?= $item['category'] === 'ค่าใช้สอย' ? 'selected' : '' ?>>ค่าใช้สอย</option>
                                    <option value="ค่าวัสดุ" <?= $item['category'] === 'ค่าวัสดุ' ? 'selected' : '' ?>>ค่าวัสดุ</option>
                                    <option value="ค่าครุภัณฑ์" <?= $item['category'] === 'ค่าครุภัณฑ์' ? 'selected' : '' ?>>ค่าครุภัณฑ์</option>
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="block font-bold text-slate-700 mb-1">รายการค่าใช้จ่าย</label>
                                <input type="text" name="item[]" value="<?= htmlspecialchars($item['item']) ?>" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-semibold focus:outline-none">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="block font-bold text-slate-700 mb-1">จำนวน</label>
                                <input type="number" step="0.1" name="qty[]" value="<?= $item['qty'] ?>" oninput="calcExpenseRow(this)"
                                       class="exp-qty w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center font-mono">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="block font-bold text-slate-700 mb-1">หน่วย</label>
                                <input type="text" name="unit[]" value="<?= htmlspecialchars($item['unit']) ?>"
                                       class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="block font-bold text-slate-700 mb-1">ราคา</label>
                                <input type="number" step="0.01" name="price[]" value="<?= $item['price'] ?>" oninput="calcExpenseRow(this)"
                                       class="exp-price w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 mb-1">รวมเงิน (บ.)</label>
                                <input type="number" step="0.01" name="total[]" value="<?= $item['total'] ?>"
                                       class="exp-total w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right font-bold text-blue-900">
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button" onclick="this.closest('.expense-row').remove()" class="text-rose-500 hover:text-rose-700 inline-flex items-center gap-1 text-[11px]">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>ลบแถวนี้</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="addExpenseRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 hover:bg-slate-100 rounded-lg text-xs font-bold text-slate-700">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>เพิ่มรายการใหม่</span>
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeExpenseModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>บันทึกรายการ 4 หมวด</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calcExpenseRow(el) {
    const row = el.closest('.expense-row');
    const qty = parseFloat(row.querySelector('.exp-qty').value) || 0;
    const price = parseFloat(row.querySelector('.exp-price').value) || 0;
    if (qty > 0 && price > 0) {
        row.querySelector('.exp-total').value = (qty * price).toFixed(2);
    }
}

function addExpenseRow() {
    const container = document.getElementById('expenseRowsContainer');
    const row = document.createElement('div');
    row.className = 'expense-row p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2';
    row.innerHTML = `
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-700 mb-1">หมวดรายจ่าย</label>
                <select name="category[]" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-none">
                    <option value="ค่าตอบแทน">ค่าตอบแทน</option>
                    <option value="ค่าใช้สอย" selected>ค่าใช้สอย</option>
                    <option value="ค่าวัสดุ">ค่าวัสดุ</option>
                    <option value="ค่าครุภัณฑ์">ค่าครุภัณฑ์</option>
                </select>
            </div>
            <div class="sm:col-span-4">
                <label class="block font-bold text-slate-700 mb-1">รายการค่าใช้จ่าย</label>
                <input type="text" name="item[]" placeholder="เช่น ค่าวัสดุฝึกอบรม..." required
                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-semibold focus:outline-none">
            </div>
            <div class="sm:col-span-1">
                <label class="block font-bold text-slate-700 mb-1">จำนวน</label>
                <input type="number" step="0.1" name="qty[]" value="1" oninput="calcExpenseRow(this)"
                       class="exp-qty w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center font-mono">
            </div>
            <div class="sm:col-span-1">
                <label class="block font-bold text-slate-700 mb-1">หน่วย</label>
                <input type="text" name="unit[]" value="ชุด"
                       class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center">
            </div>
            <div class="sm:col-span-1">
                <label class="block font-bold text-slate-700 mb-1">ราคา</label>
                <input type="number" step="0.01" name="price[]" value="0" oninput="calcExpenseRow(this)"
                       class="exp-price w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right">
            </div>
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">รวมเงิน (บ.)</label>
                <input type="number" step="0.01" name="total[]" value="0.00"
                       class="exp-total w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-right font-bold text-blue-900">
            </div>
        </div>
        <div class="text-right">
            <button type="button" onclick="this.closest('.expense-row').remove()" class="text-rose-500 hover:text-rose-700 inline-flex items-center gap-1 text-[11px]">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>ลบแถวนี้</span>
            </button>
        </div>
    `;
    container.appendChild(row);
    lucide.createIcons();
}

function openExpenseModal() {
    document.getElementById('expenseModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeExpenseModal() {
    document.getElementById('expenseModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

