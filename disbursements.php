<?php
$pageTitle = 'การเบิกจ่ายงบประมาณ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$filterProjectId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$successMsg = '';
$errorMsg = '';

$projects = getProjectsData($currentSchoolId);

// Handle POST actions: Save new disbursement or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_disbursement') {
        $pId = intval($_POST['project_id'] ?? 0);
        $amount = floatval(str_replace(',', '', $_POST['amount'] ?? '0'));
        $payee = trim($_POST['payee'] ?? '');
        $category = trim($_POST['category'] ?? 'ค่าใช้สอย');
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $docNo = trim($_POST['doc_no'] ?? '');
        $note = trim($_POST['note'] ?? '');

        // Find project name
        $projName = 'โครงการทั่วไป';
        foreach ($projects as $p) {
            if ((int)$p['id'] === $pId) {
                $projName = $p['project_name'];
                break;
            }
        }

        if (empty($docNo)) {
            $docNo = 'ขจ.' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) . '/2568';
        }

        if ($amount > 0 && !empty($payee)) {
            $disbData = [
                'id' => intval($_POST['disbursement_id'] ?? 0),
                'doc_no' => $docNo,
                'date' => $date,
                'project_id' => $pId,
                'project_name' => $projName,
                'payee' => $payee,
                'category' => $category,
                'amount' => $amount,
                'status' => 'paid',
                'note' => $note,
            ];
            saveDisbursement($currentSchoolId, $disbData);
            $successMsg = 'บันทึกการเบิกจ่ายงบประมาณเรียบร้อยแล้ว';
            // Refresh projects data to reflect updated spent_budget
            $projects = getProjectsData($currentSchoolId);
        } else {
            $errorMsg = 'กรุณาระบุผู้รับเงินและจำนวนเงินที่ถูกต้อง';
        }
    } elseif ($action === 'delete_disbursement') {
        $disbId = intval($_POST['disbursement_id'] ?? 0);
        $disbursements = getDisbursementsData($currentSchoolId);
        $updatedDisbursements = [];
        $deletedItem = null;
        foreach ($disbursements as $d) {
            if ((int)$d['id'] === $disbId) {
                $deletedItem = $d;
            } else {
                $updatedDisbursements[] = $d;
            }
        }
        
        saveDisbursementsData($currentSchoolId, $updatedDisbursements);
        
        // If MySQL is active, also delete from DB
        try {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $stmt = $db->getConnection()->prepare("DELETE FROM disbursements WHERE id = ?");
                $stmt->execute([$disbId]);
            }
        } catch (Exception $e) {}

        // Re-adjust project spent amount
        if ($deletedItem && !empty($deletedItem['project_id'])) {
            $pId = (int)$deletedItem['project_id'];
            foreach ($projects as $p) {
                if ((int)$p['id'] === $pId) {
                    $p['spent_budget'] = max(0, floatval($p['spent_budget'] ?? 0) - floatval($deletedItem['amount'] ?? 0));
                    $p['remaining_budget'] = max(0, floatval($p['allocated_budget'] ?? 0) - $p['spent_budget']);
                    saveProject($currentSchoolId, $p);
                    break;
                }
            }
        }

        $successMsg = 'ลบรายการเบิกจ่ายเรียบร้อยแล้ว';
    }
}

$disbursements = getDisbursementsData($currentSchoolId);
if ($filterProjectId > 0) {
    $disbursements = array_filter($disbursements, fn($d) => ((int)($d['project_id'] ?? 0) === $filterProjectId));
}
$totalDisbursed = array_sum(array_column($disbursements, 'amount'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb and Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <a href="projects.php" class="hover:text-blue-900 transition-colors">โครงการ</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">การเบิกจ่ายงบประมาณ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="receipt" class="w-6 h-6 text-blue-900"></i>
                <span>การเบิกจ่าย / การใช้เงินตามโครงการ</span>
            </h2>
            <p class="text-xs text-slate-500">บันทึกฎีกาเบิกเงิน ใบสำคัญรับเงิน และติดตามสถานะการตัดจ่ายงบประมาณ</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openDisbursementModal()" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>บันทึกการเบิกจ่ายใหม่</span>
            </button>
            <div class="bg-amber-50 border border-amber-200 px-4 py-1.5 rounded-xl text-right">
                <span class="text-[10px] font-bold text-amber-800 uppercase block">ยอดเบิกจ่ายสะสม</span>
                <span class="text-lg font-bold font-mono text-amber-900"><?= number_format($totalDisbursed, 2) ?> บาท</span>
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

    <?php if ($errorMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center gap-3 shadow-xs">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <span class="text-xs font-bold"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Disbursements Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-blue-900"></i>
                <h3 class="text-sm font-bold text-slate-900">
                    ประวัติการเบิกจ่ายเงินงบประมาณ
                    <?php if ($filterProjectId > 0): ?>
                        <span class="text-xs font-normal text-blue-900">(กรองเฉพาะโครงการที่เลือก)</span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($filterProjectId > 0): ?>
                    <a href="disbursements.php" class="text-xs text-rose-600 hover:underline">ล้างตัวกรอง</a>
                <?php endif; ?>
                <span class="text-xs text-slate-500 font-mono">จำนวน <?= count($disbursements) ?> รายการ</span>
            </div>
        </div>

        <?php if (empty($disbursements)): ?>
            <div class="p-12 text-center text-slate-400">
                <i data-lucide="receipt" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                <h4 class="text-sm font-bold text-slate-700">ยังไม่มีบันทึกการเบิกจ่าย</h4>
                <p class="text-xs text-slate-500 mt-1 mb-4">คลิกปุ่มด้านบนเพื่อบันทึกใบเสร็จ / ฎีกาเบิกเงินของโครงการ</p>
                <button type="button" onclick="openDisbursementModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-900 text-white text-xs font-bold rounded-xl shadow-xs">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>บันทึกการเบิกจ่ายแรก</span>
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="py-2.5 px-4 font-semibold w-28">เลขที่เอกสาร</th>
                            <th class="py-2.5 px-4 font-semibold w-28">วันที่เบิกจ่าย</th>
                            <th class="py-2.5 px-4 font-semibold">โครงการที่ขอเบิก</th>
                            <th class="py-2.5 px-4 font-semibold">ผู้รับเงิน / ร้านค้า</th>
                            <th class="py-2.5 px-4 font-semibold w-28">หมวดรายจ่าย</th>
                            <th class="py-2.5 px-4 font-semibold text-right w-28">จำนวนเงิน (บาท)</th>
                            <th class="py-2.5 px-4 font-semibold text-center w-20">สถานะ</th>
                            <th class="py-2.5 px-4 font-semibold text-center w-16">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($disbursements as $d): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($d['doc_no']) ?></td>
                                <td class="py-3 px-4 text-slate-600 font-mono"><?= formatThaiDate($d['date']) ?></td>
                                <td class="py-3 px-4 font-medium text-slate-900 max-w-xs">
                                    <div class="truncate font-semibold"><?= htmlspecialchars($d['project_name']) ?></div>
                                    <?php if (!empty($d['note'])): ?>
                                        <div class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($d['note']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-slate-700"><?= htmlspecialchars($d['payee']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($d['category']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900"><?= number_format($d['amount'], 2) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        จ่ายแล้ว
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <form method="POST" onsubmit="return confirm('ต้องการลบรายการเบิกจ่ายนี้หรือไม่?');" class="inline">
                                        <input type="hidden" name="action" value="delete_disbursement">
                                        <input type="hidden" name="disbursement_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="ลบรายการนี้">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-amber-50/70 font-bold text-slate-900 border-t-2 border-amber-200 text-sm">
                            <td colspan="5" class="py-3 px-4 text-right">รวมยอดเบิกจ่ายทั้งสิ้น</td>
                            <td class="py-3 px-4 text-right font-mono text-amber-900"><?= number_format($totalDisbursed, 2) ?></td>
                            <td colspan="2" class="py-3 px-4 text-xs font-normal text-slate-600">บาท</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal: บันทึกการเบิกจ่าย -->
<div id="disbursementModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="receipt" class="w-5 h-5 text-blue-900"></i>
                    <span>บันทึกฎีกา / ใบสำคัญเบิกจ่าย</span>
                </h3>
                <p class="text-xs text-slate-500">บันทึกยอดเงินตัดจ่ายจริงเพื่ออัปเดตงบประมาณคงเหลือของโครงการ</p>
            </div>
            <button type="button" onclick="closeDisbursementModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="p-5 space-y-4 text-xs">
            <input type="hidden" name="action" value="save_disbursement">
            <input type="hidden" name="disbursement_id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">เลขที่เอกสาร / ฎีกา</label>
                    <input type="text" name="doc_no" id="modalDocNo" placeholder="เช่น ขจ.004/2568" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">วันที่เบิกจ่าย</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">โครงการที่ขอเบิก <span class="text-rose-500">*</span></label>
                <select name="project_id" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $filterProjectId === (int)$p['id'] ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($p['project_code']) ?>] <?= htmlspecialchars($p['project_name']) ?> (งบคงเหลือ: <?= number_format($p['remaining_budget']) ?> บ.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ผู้รับเงิน / ร้านค้า / บริษัท <span class="text-rose-500">*</span></label>
                    <input type="text" name="payee" required placeholder="เช่น หจก. พัฒนาการค้า / นางสมศรี" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">หมวดรายจ่าย</label>
                    <select name="category" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                        <option value="ค่าตอบแทน">ค่าตอบแทน (วิทยากร / กรรมการ)</option>
                        <option value="ค่าใช้สอย">ค่าใช้สอย (ค่าอาหาร / เบี้ยเลี้ยง / จ้างเหมา)</option>
                        <option value="ค่าวัสดุ">ค่าวัสดุ (สำนักงาน / สื่อ / ซ่อมแซม)</option>
                        <option value="ค่าสาธารณูปโภค">ค่าสาธารณูปโภค</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">จำนวนเงินที่เบิก (บาท) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" 
                       class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-slate-900 font-bold text-base focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">หมายเหตุ / คำอธิบายการจ่าย</label>
                <textarea name="note" rows="2" placeholder="เช่น ค่าวัสดุจัดนิทรรศการผลงานนักเรียน..." 
                          class="w-full px-3 py-2 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeDisbursementModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>บันทึกการเบิกจ่าย</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDisbursementModal() {
    document.getElementById('modalDocNo').value = 'ขจ.' + String(Math.floor(Math.random() * 900) + 100) + '/2568';
    document.getElementById('disbursementModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeDisbursementModal() {
    document.getElementById('disbursementModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

