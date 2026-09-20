<?php
$pageTitle = 'รายงานและสถิติ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$revenues = getRevenuesData();
$allocations = getBudgetAllocations();
$projects = getProjectsData();

$totalRevenue = array_sum(array_column($revenues, 'calculated_amount'));
$totalAllocated = array_sum(array_column($allocations, 'allocated_amount'));
$totalSpent = array_sum(array_column($allocations, 'spent_amount'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">รายงานทางการเงินและแผนงาน</h2>
            <p class="text-xs text-slate-500">แบบรายงานทางการเงินและงบประมาณตามระเบียบ สพฐ. และกรมบัญชีกลาง</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 bg-blue-700 text-white hover:bg-blue-800 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>พิมพ์รายงานสรุป</span>
            </button>
        </div>
    </div>

    <!-- Reports Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center mb-3">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">แบบ สงป.101 (รายงานการจัดสรรงบ)</h3>
                <p class="text-xs text-slate-500 mt-1">รายงานสรุปวงเงินงบประมาณและการจัดสรรตาม 4 ฝ่ายบริหาร</p>
            </div>
            <button onclick="window.print()" class="mt-4 text-xs text-blue-700 font-bold hover:underline flex items-center gap-1">
                <span>เปิดพิมพ์แบบรายงาน &rarr;</span>
            </button>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                    <i data-lucide="file-check" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">รายงานผลการใช้จ่ายงบประมาณรายไตรมาส</h3>
                <p class="text-xs text-slate-500 mt-1">สรุปยอดเบิกจ่ายสะสม เปรียบเทียบเป้าหมายและผลการปฏิบัติงานจริง</p>
            </div>
            <button onclick="window.print()" class="mt-4 text-xs text-emerald-700 font-bold hover:underline flex items-center gap-1">
                <span>เปิดพิมพ์แบบรายงาน &rarr;</span>
            </button>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center mb-3">
                    <i data-lucide="pie-chart" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">รายงานสรุปผลการดำเนินงานโครงการ</h3>
                <p class="text-xs text-slate-500 mt-1">รายงานตัวชี้วัดความสำเร็จ (KPI) และความพึงพอใจของผู้เข้าร่วมโครงการ</p>
            </div>
            <button onclick="window.print()" class="mt-4 text-xs text-purple-700 font-bold hover:underline flex items-center gap-1">
                <span>เปิดพิมพ์แบบรายงาน &rarr;</span>
            </button>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
