<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$menuItems = [
    ['id' => 'dashboard', 'file' => 'dashboard.php', 'label' => '1. ภาพรวม (Dashboard)', 'icon' => 'layout-dashboard'],
    ['id' => 'school', 'file' => 'school.php', 'label' => '2. ข้อมูลโรงเรียน', 'icon' => 'school'],
    ['id' => 'students', 'file' => 'students.php', 'label' => '3. ข้อมูลนักเรียน', 'icon' => 'users'],
    ['id' => 'revenue', 'file' => 'revenue.php', 'label' => '4. ประมาณการรายรับ', 'icon' => 'calculator'],
    ['id' => 'budget', 'file' => 'budget.php', 'label' => '5. จัดสรรงบประมาณ', 'icon' => 'pie-chart'],
    ['id' => 'learner_activities', 'file' => 'learner_activities.php', 'label' => '6. กิจกรรมพัฒนาผู้เรียน', 'icon' => 'sparkles'],
    ['id' => 'ai_project_writer', 'file' => 'ai_project_writer.php', 'label' => '7. เขียนโครงการด้วย AI', 'icon' => 'bot', 'badge' => 'AI สพฐ.'],
    ['id' => 'projects', 'file' => 'projects.php', 'label' => '8. โครงการ', 'icon' => 'folder-git-2'],
    ['id' => 'expenses', 'file' => 'expenses.php', 'label' => '9. รายละเอียดงบโครงการ', 'icon' => 'file-spreadsheet'],
    ['id' => 'disbursements', 'file' => 'disbursements.php', 'label' => '10. การเบิกจ่าย / ใช้เงิน', 'icon' => 'receipt'],
    ['id' => 'action_plan', 'file' => 'action_plan.php', 'label' => '11. แผนปฏิบัติการประจำปี', 'icon' => 'target'],
    ['id' => 'reports', 'file' => 'reports.php', 'label' => '12. รายงาน', 'icon' => 'file-text'],
    ['id' => 'settings', 'file' => 'settings.php', 'label' => '13. ตั้งค่าระบบ', 'icon' => 'settings'],
    ['id' => 'users', 'file' => 'users.php', 'label' => '14. ผู้ใช้งาน', 'icon' => 'shield-alert'],
];
?>
<!-- Sidebar Navigation -->
<aside id="sidebar" class="w-64 bg-white border-r border-slate-200 shrink-0 flex flex-col fixed lg:static inset-y-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out no-print">
    <div class="p-4 border-b border-slate-100 flex items-center justify-between lg:hidden">
        <span class="font-bold text-slate-800 text-sm">เมนูระบบงาน</span>
        <button id="close-sidebar-btn" class="p-1 text-slate-400 hover:text-slate-700">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation links -->
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <?php foreach ($menuItems as $item): ?>
            <?php 
                $isActive = ($currentPage === $item['id']) || ($currentPage === '' && $item['id'] === 'dashboard');
            ?>
            <a href="<?= $item['file'] ?>" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-colors <?= $isActive ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 <?= $isActive ? 'text-white' : 'text-slate-500' ?>"></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </div>
                <?php if (!empty($item['badge'])): ?>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $isActive ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-700 border border-purple-200' ?>">
                        <?= htmlspecialchars($item['badge']) ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Quick Actions at Sidebar Bottom -->
    <div class="p-3 border-t border-slate-100 bg-slate-50/50 space-y-2">
        <a href="ai_project_writer.php" class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-purple-700 to-indigo-600 text-white text-xs font-bold rounded-lg shadow-xs hover:from-purple-800 hover:to-indigo-700 transition-all">
            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-300"></i>
            <span>เขียนโครงการด้วย AI</span>
        </a>
        <div class="text-[10px] text-center text-slate-400">
            ระบบแผนงาน สพฐ. เวอร์ชัน PHP 8.x
        </div>
    </div>
</aside>

<!-- Backdrop for mobile drawer -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-30 hidden lg:hidden"></div>
