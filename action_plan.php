<?php
$pageTitle = 'แผนปฏิบัติการประจำปี';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$projects = getProjectsData();
$allocations = getBudgetAllocations();
$totalBudget = array_sum(array_column($projects, 'allocated_budget'));
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">แผนปฏิบัติการประจำปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?></h2>
            <p class="text-xs text-slate-500">รวมแผนงาน โครงการ กิจกรรม และปฏิทินปฏิบัติงานรอบ 12 เดือน ตามยุทธศาสตร์ สพฐ.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 bg-blue-700 text-white hover:bg-blue-800 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>พิมพ์รูปเล่มแผนงาน</span>
            </button>
        </div>
    </div>

    <!-- Strategy Accordion / Cards -->
    <div class="space-y-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <h3 class="text-sm font-bold text-blue-900 mb-2 flex items-center gap-2">
                <i data-lucide="compass" class="w-4 h-4 text-blue-600"></i>
                <span>วิสัยทัศน์ พันธกิจ และเป้าประสงค์ของสถานศึกษา</span>
            </h3>
            <div class="text-xs text-slate-700 space-y-2 leading-relaxed">
                <p><strong>วิสัยทัศน์ (Vision):</strong> จัดการศึกษาอย่างมีคุณภาพตามมาตรฐานสากล ผู้เรียนมีคุณธรรม จริยธรรม ก้าวทันเทคโนโลยีและปัญญาประดิษฐ์ น้อมนำหลักปรัชญาของเศรษฐกิจพอเพียง</p>
                <p><strong>พันธกิจ (Mission):</strong> พัฒนาคุณภาพผู้เรียนทุกระดับชั้น, พัฒนาครูสู่ความเป็นมืออาชีพ, บริหารจัดการด้วยหลักธรรมาภิบาล และส่งเสริมการมีส่วนร่วมของชุมชน</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                <span>ปฏิทินปฏิบัติงาน 12 เดือน (ตุลาคม <?= $fiscalYear['year'] - 1 ?> - กันยายน <?= $fiscalYear['year'] ?>)</span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="py-2.5 px-3 font-semibold">โครงการ</th>
                            <th class="py-2.5 px-3 font-semibold">ฝ่าย</th>
                            <th class="py-2.5 px-3 font-semibold text-right">งบประมาณ</th>
                            <th class="py-2.5 px-3 font-semibold text-center">ไตรมาส 1 (ต.ค.-ธ.ค.)</th>
                            <th class="py-2.5 px-3 font-semibold text-center">ไตรมาส 2 (ม.ค.-มี.ค.)</th>
                            <th class="py-2.5 px-3 font-semibold text-center">ไตรมาส 3 (เม.ย.-มิ.ย.)</th>
                            <th class="py-2.5 px-3 font-semibold text-center">ไตรมาส 4 (ก.ค.-ก.ย.)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($projects as $p): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 px-3 font-medium text-slate-900"><?= htmlspecialchars($p['project_name']) ?></td>
                                <td class="py-2.5 px-3 text-slate-600"><?= htmlspecialchars($p['department']) ?></td>
                                <td class="py-2.5 px-3 font-mono font-bold text-right text-slate-900"><?= number_format($p['allocated_budget']) ?></td>
                                <td class="py-2.5 px-3 text-center"><span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span></td>
                                <td class="py-2.5 px-3 text-center"><span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span></td>
                                <td class="py-2.5 px-3 text-center"><span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span></td>
                                <td class="py-2.5 px-3 text-center"><span class="w-3 h-3 rounded-full bg-emerald-600 inline-block"></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
