<?php
/**
 * หน้า Super Admin สำหรับจัดการระบบฐานข้อมูล MySQL และจัดการโรงเรียนในระบบ (Multi-Tenant)
 * - เพิ่ม/ตั้งค่าการเชื่อมต่อ MySQL
 * - อัปเดตโครงสร้างฐานข้อมูลอัตโนมัติ (Auto-Migration)
 * - เปิดใช้งานโรงเรียนด้วยรหัส SMIS 8 หลัก
 * - กำหนดเปิด/ปิดการใช้งานโรงเรียน
 * - จัดการ School ID และรหัสผ่านเพื่อป้องกันข้อมูลชนกัน
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = Database::getConnection();
$isConnected = $pdo !== null;
$connError = Database::$connectionError;

// Fetch schools
$schools = getAllSchoolsList();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - จัดการระบบฐานข้อมูล & โรงเรียนในระบบ (Multi-Tenant)</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Prompt', 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-950 border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-black text-lg shadow-lg">
                    SA
                </div>
                <div>
                    <h1 class="text-base font-bold text-white flex items-center gap-2">
                        <span>ศูนย์ควบคุม Super Admin</span>
                        <span class="text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-full font-semibold">
                            Multi-Tenant & MySQL
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400">ระบบบริหารแผนปฏิบัติการ & จัดสรรงบประมาณสถานศึกษา</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border <?= $isConnected ? 'bg-emerald-950/40 border-emerald-700/50 text-emerald-400' : 'bg-rose-950/40 border-rose-700/50 text-rose-400' ?> text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full <?= $isConnected ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' ?>"></span>
                    <span><?= $isConnected ? 'MySQL เชื่อมต่อแล้ว' : 'MySQL ออฟไลน์' ?></span>
                </div>
                <a href="dashboard.php" class="text-xs bg-blue-600 hover:bg-blue-500 text-white font-medium px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>เข้าสู่หน้าโรงเรียน</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Top Overview Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400">โรงเรียนทั้งหมดในระบบ</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                        <i data-lucide="building-2" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-bold text-white font-mono" id="stat-total-schools"><?= count($schools) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">ลงทะเบียนด้วยรหัส SMIS 8 หลัก</div>
            </div>

            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400">โรงเรียนที่เปิดใช้งาน (Active)</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-bold text-emerald-400 font-mono" id="stat-active-schools">
                    <?= count(array_filter($schools, fn($s) => !empty($s['is_active']))) ?>
                </div>
                <div class="text-[11px] text-emerald-300 mt-1">เข้าใช้งานและบันทึกข้อมูลได้ตามปกติ</div>
            </div>

            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400">โรงเรียนที่ปิดใช้งาน (Suspended)</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-bold text-amber-400 font-mono" id="stat-inactive-schools">
                    <?= count(array_filter($schools, fn($s) => empty($s['is_active']))) ?>
                </div>
                <div class="text-[11px] text-amber-300 mt-1">ระงับการเข้าสู่ระบบชั่วคราว</div>
            </div>

            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400">การแยกข้อมูล (Data Isolation)</span>
                    <div class="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-bold text-purple-400 font-mono">100%</div>
                <div class="text-[11px] text-purple-300 mt-1">Tenant ID & SMIS ป้องกันข้อมูลชนกัน</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-800 mb-6 pb-2">
            <button onclick="switchTab('schools')" id="tab-btn-schools" class="tab-btn active px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 bg-blue-600 text-white transition-all shadow-md">
                <i data-lucide="building" class="w-4 h-4"></i>
                <span>1. จัดการโรงเรียน & รหัส SMIS 8 หลัก</span>
            </button>
            <button onclick="switchTab('database')" id="tab-btn-database" class="tab-btn px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
                <i data-lucide="database" class="w-4 h-4"></i>
                <span>2. ตั้งค่า MySQL & อัปเดตฐานข้อมูลอัตโนมัติ</span>
            </button>
            <button onclick="switchTab('security')" id="tab-btn-security" class="tab-btn px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>3. สถาปัตยกรรมการแยกข้อมูล (Data Isolation)</span>
            </button>
        </div>

        <!-- TAB 1: SCHOOLS MANAGEMENT -->
        <div id="tab-content-schools" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Add School Form (Col 1) -->
                <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-md h-fit">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white">เปิดใช้งานโรงเรียนใหม่</h2>
                            <p class="text-xs text-slate-400">กำหนดรหัส SMIS 8 หลัก และ ID ประจำโรงเรียน</p>
                        </div>
                    </div>

                    <form id="add-school-form" onsubmit="handleAddSchool(event)" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                รหัสสมัคร SMIS (8 หลัก) <span class="text-rose-400">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="school-smis" required maxlength="8" pattern="[0-9]{8}" placeholder="เช่น 10400100" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono placeholder:text-slate-500 focus:outline-hidden focus:border-amber-500 focus:ring-1 focus:ring-amber-500" oninput="handleSmisChange(this.value)">
                                <span class="absolute right-3 top-2.5 text-[10px] text-slate-400 font-mono" id="smis-counter">0/8</span>
                            </div>
                            <p class="text-[11px] text-amber-300/80 mt-1">ใช้รหัสสถานศึกษา SMIS 8 หลักในการลงทะเบียนเปิดใช้งาน</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                ชื่อโรงเรียน <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" id="school-name" required placeholder="เช่น โรงเรียนบ้านหนองบัววิทยา" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:outline-hidden focus:border-amber-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">จังหวัด</label>
                                <input type="text" id="school-province" placeholder="เช่น ขอนแก่น" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:outline-hidden focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">เขตพื้นที่การศึกษา</label>
                                <input type="text" id="school-area" placeholder="สพป.ขอนแก่น เขต 1" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:outline-hidden focus:border-amber-500">
                            </div>
                        </div>

                        <div class="p-3 bg-slate-900/80 border border-slate-700/80 rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                    <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                    <span>บัญชีและ ID ประจำโรงเรียน (ป้องกันข้อมูลชนกัน)</span>
                                </span>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">School ID / Tenant Key (อัตโนมัติ)</label>
                                <input type="text" id="school-key-preview" readonly value="SCH-________" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-amber-300 font-mono">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">ID ผู้ดูแลโรงเรียน</label>
                                    <input type="text" id="school-admin-user" placeholder="admin_10400100" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">รหัสผ่านโรงเรียน</label>
                                    <input type="text" id="school-admin-pass" value="123456" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-emerald-300 font-mono">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="school-is-active" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 bg-slate-900 border-slate-700">
                                <span class="text-xs text-slate-300 font-medium">เปิดใช้งานทันที (Active)</span>
                            </label>
                        </div>

                        <button type="submit" id="btn-save-school" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm transition-all shadow-md flex items-center justify-center gap-2">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>บันทึกและเปิดใช้งานโรงเรียน</span>
                        </button>
                    </form>
                </div>

                <!-- Schools Table (Cols 2-3) -->
                <div class="lg:col-span-2 bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-md">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-700">
                        <div>
                            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>รายชื่อโรงเรียนในระบบ</span>
                                <span class="text-xs bg-slate-700 text-slate-300 px-2 py-0.5 rounded-full font-mono font-bold" id="schools-count-badge">
                                    <?= count($schools) ?> แห่ง
                                </span>
                            </h2>
                            <p class="text-xs text-slate-400">ควบคุมการเปิด/ปิดสิทธิ์การใช้งาน แก้ไข และลบข้อมูลโรงเรียนในระบบ</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="purgeAllDemo()" class="px-3 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 text-xs font-semibold flex items-center gap-1.5 transition-colors" title="ล้างข้อมูลโรงเรียนเดิมและข้อมูล Demo เก่าทั้งหมด">
                                <i data-lucide="trash" class="w-3.5 h-3.5 text-rose-400"></i>
                                <span>ล้างข้อมูล Demo เก่า</span>
                            </button>
                            <div class="relative w-full sm:w-56">
                                <input type="text" id="search-school-input" oninput="filterSchools(this.value)" placeholder="ค้นหาชื่อ, รหัส SMIS..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 pl-8 text-xs text-white placeholder:text-slate-500 focus:outline-hidden focus:border-blue-500">
                                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5"></i>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-700 text-slate-400 bg-slate-900/50">
                                    <th class="py-2.5 px-3 font-semibold">รหัส SMIS (8 หลัก)</th>
                                    <th class="py-2.5 px-3 font-semibold">ชื่อโรงเรียน</th>
                                    <th class="py-2.5 px-3 font-semibold">School ID / บัญชี</th>
                                    <th class="py-2.5 px-3 font-semibold">รหัสผ่าน</th>
                                    <th class="py-2.5 px-3 font-semibold text-center">สถานะ</th>
                                    <th class="py-2.5 px-3 font-semibold text-center">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="schools-table-body" class="divide-y divide-slate-700/60">
                                <?php foreach ($schools as $sch): ?>
                                    <tr class="hover:bg-slate-700/30 transition-colors school-row" data-name="<?= htmlspecialchars(strtolower($sch['name'])) ?>" data-smis="<?= htmlspecialchars($sch['smis_code'] ?? '') ?>">
                                        <td class="py-3 px-3">
                                            <div class="font-mono font-bold text-amber-400 text-sm"><?= htmlspecialchars($sch['smis_code'] ?? '10400100') ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($sch['school_code'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="font-semibold text-white"><?= htmlspecialchars($sch['name']) ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($sch['education_area'] ?? '') ?> • <?= htmlspecialchars($sch['province'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3 px-3 font-mono">
                                            <div class="text-blue-300 font-bold"><?= htmlspecialchars($sch['school_key'] ?? 'SCH-' . ($sch['smis_code'] ?? '0000')) ?></div>
                                            <div class="text-[10px] text-slate-400">User: <?= htmlspecialchars($sch['admin_username'] ?? 'admin') ?></div>
                                        </td>
                                        <td class="py-3 px-3 font-mono text-emerald-300">
                                            <?= htmlspecialchars($sch['admin_password_plain'] ?? '123456') ?>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <?php if (!empty($sch['is_active'])): ?>
                                                <button onclick="toggleSchoolStatus(<?= $sch['id'] ?>, 0)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30 transition-colors" title="คลิกเพื่อปิดการใช้งาน">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                    <span>เปิดใช้งาน</span>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="toggleSchoolStatus(<?= $sch['id'] ?>, 1)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 hover:bg-rose-500/30 transition-colors" title="คลิกเพื่อเปิดใช้งาน">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                                    <span>ระงับการใช้งาน</span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="switchToSchool(<?= (int)$sch['id'] ?>, '<?= htmlspecialchars(addslashes($sch['name'])) ?>')" class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-600/30 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/40 rounded-lg text-[11px] font-semibold transition-all shadow-xs cursor-pointer" title="เข้าสู่ระบบเป็นโรงเรียนนี้ทันที">
                                                    <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                                                    <span>เข้าใช้งาน</span>
                                                </button>
                                                <button onclick="editSchool(<?= htmlspecialchars(json_encode($sch)) ?>)" class="p-1.5 text-slate-400 hover:text-amber-400 hover:bg-slate-700 rounded-lg cursor-pointer" title="แก้ไข">
                                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                </button>
                                                <button onclick="deleteSchool(<?= (int)$sch['id'] ?>, '<?= htmlspecialchars(addslashes($sch['name'])) ?>')" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-700 rounded-lg cursor-pointer" title="ลบข้อมูลโรงเรียน">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 2: DATABASE SETTINGS & AUTO-MIGRATION -->
        <div id="tab-content-database" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Connection Form -->
                <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-md">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                            <i data-lucide="server" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white">ตั้งค่าการเชื่อมต่อ MySQL Database</h2>
                            <p class="text-xs text-slate-400">รองรับ cPanel MySQL, MariaDB, และ Cloud Database</p>
                        </div>
                    </div>

                    <form id="db-config-form" onsubmit="handleSaveDbConfig(event)" class="space-y-4">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Database Host</label>
                                <input type="text" id="db-host" value="<?= htmlspecialchars(DB_HOST) ?>" required placeholder="localhost" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-hidden focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Port</label>
                                <input type="number" id="db-port" value="<?= DB_PORT ?>" required placeholder="3306" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-hidden focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Database Name (ชื่อฐานข้อมูล)</label>
                            <input type="text" id="db-name" value="<?= htmlspecialchars(DB_NAME) ?>" required placeholder="school_budget_db" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-hidden focus:border-blue-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
                                <input type="text" id="db-user" value="<?= htmlspecialchars(DB_USER) ?>" required placeholder="root" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-hidden focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                                <input type="password" id="db-pass" value="<?= htmlspecialchars(DB_PASS) ?>" placeholder="รหัสผ่านฐานข้อมูล" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-hidden focus:border-blue-500">
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" onclick="handleTestDb()" id="btn-test-db" class="flex-1 py-2.5 px-4 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="activity" class="w-4 h-4 text-amber-400"></i>
                                <span>ทดสอบการเชื่อมต่อ (Test Connection)</span>
                            </button>
                            <button type="submit" id="btn-save-db" class="flex-1 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>บันทึกการตั้งค่า (Save Config)</span>
                            </button>
                        </div>
                        <div id="test-result-box" class="hidden p-3 rounded-xl text-xs font-medium border"></div>
                    </form>
                </div>

                <!-- Auto-Migration Box -->
                <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-md flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-700">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-white">อัปเดตโครงสร้างฐานข้อมูลอัตโนมัติ (Auto-Migration)</h2>
                                <p class="text-xs text-slate-400">สร้างตารางและคอลัมน์ Multi-Tenant อัตโนมัติในคลิกเดียว</p>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6 text-xs text-slate-300">
                            <div class="p-3.5 bg-slate-900/90 border border-slate-700/80 rounded-xl space-y-2">
                                <div class="font-bold text-emerald-400 flex items-center gap-2">
                                    <i data-lucide="check-check" class="w-4 h-4"></i>
                                    <span>ความสามารถของระบบ Auto-Migration:</span>
                                </div>
                                <ul class="space-y-1.5 text-slate-300 pl-5 list-disc text-[11px]">
                                    <li>สร้างตารางใหม่ทั้ง 14 ตารางหากยังไม่มีในฐานข้อมูล</li>
                                    <li>เพิ่มคอลัมน์ <code class="text-amber-300 font-mono">smis_code</code> (8 หลัก) และ <code class="text-amber-300 font-mono">is_active</code> ในตาราง schools โดยอัตโนมัติ</li>
                                    <li>เพิ่มคอลัมน์ <code class="text-amber-300 font-mono">school_key</code> และ <code class="text-amber-300 font-mono">admin_password_plain</code> ป้องกันข้อมูลชนกัน</li>
                                    <li>สร้างบัญชี Super Admin เริ่มต้น (<code class="text-blue-300 font-mono">superadmin</code> / <code class="text-blue-300 font-mono">super123456</code>)</li>
                                    <li>ไม่มีการลบข้อมูลเดิมที่มีอยู่ ปลอดภัยต่อ Production 100%</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button onclick="handleRunAutoMigrate()" id="btn-run-migrate" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm transition-all shadow-lg flex items-center justify-center gap-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4" id="migrate-icon"></i>
                            <span>อัปเดตและซ่อมแซมฐานข้อมูลทันที (Auto-Update MySQL)</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Migration Logs Console -->
            <div class="mt-6 bg-slate-950 border border-slate-800 rounded-2xl p-4 font-mono text-xs shadow-inner">
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-slate-400">
                    <div class="flex items-center gap-2">
                        <i data-lucide="terminal" class="w-4 h-4 text-emerald-400"></i>
                        <span class="font-bold text-slate-200">System Log & Migration Console</span>
                    </div>
                    <span class="text-[10px] text-slate-500">Live Status</span>
                </div>
                <div id="migration-log-box" class="h-44 overflow-y-auto space-y-1 text-slate-300 custom-scrollbar pr-2">
                    <div class="text-slate-500">> ระบบ Super Admin พร้อมทำงาน...</div>
                    <div class="text-slate-500">> สถานะ MySQL: <?= $isConnected ? '<span class="text-emerald-400">ออนไลน์ (' . htmlspecialchars(DB_HOST . ':' . DB_PORT . ' / ' . DB_NAME) . ')</span>' : '<span class="text-rose-400">ออฟไลน์ (' . htmlspecialchars($connError ?? 'ยังไม่ได้เชื่อมต่อ') . ')</span>' ?></div>
                </div>
            </div>
        </div>

        <!-- TAB 3: DATA ISOLATION ARCHITECTURE -->
        <div id="tab-content-security" class="tab-content hidden">
            <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-md space-y-6">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
                        <span>กลไกการแยกข้อมูลเด็ดขาด (Data Isolation Architecture)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        ระบบถูกออกแบบเพื่อรองรับหลายโรงเรียน (Multi-Tenancy) ภายใต้ฐานข้อมูลเดียวกันโดยที่ข้อมูลจะไม่ปะปนหรือชนกัน 100%
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-900/90 border border-slate-700/70 p-4 rounded-xl space-y-2">
                        <div class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                            <i data-lucide="hash" class="w-4 h-4"></i>
                            <span>1. SMIS 8 หลัก + School Key</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            แต่ละโรงเรียนมีรหัส SMIS 8 หลักที่ไม่ซ้ำกัน และมีรหัส School Key ประจำ เช่น <code class="text-amber-300 font-mono">SCH-10400100</code> เป็นตัวระบุ Tenant หลัก
                        </p>
                    </div>

                    <div class="bg-slate-900/90 border border-slate-700/70 p-4 rounded-xl space-y-2">
                        <div class="text-xs font-bold text-blue-400 flex items-center gap-1.5">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            <span>2. Foreign Key & Scoped Tables</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            ทุกตารางในระบบ (<code class="text-blue-300 font-mono">projects, revenues, students, budgets, users</code>) มีคอลัมน์ <code class="text-blue-300 font-mono">school_id</code> ผูกอยู่กับตารางโรงเรียนอย่างเด็ดขาด
                        </p>
                    </div>

                    <div class="bg-slate-900/90 border border-slate-700/70 p-4 rounded-xl space-y-2">
                        <div class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="toggle-right" class="w-4 h-4"></i>
                            <span>3. ปิด-เปิดการใช้งาน (Kill Switch)</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            เมื่อ Super Admin ปรับสถานะเป็น <b>ปิดใช้งาน (Inactive)</b> ผู้ใช้ของโรงเรียนนั้นจะถูกบล็อกจากการเข้าสู่ระบบทันทีแบบ Real-Time
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-blue-950/40 border border-blue-800/50 rounded-xl text-xs text-blue-200 flex items-start gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-400 shrink-0 mt-0.5"></i>
                    <div>
                        <div class="font-bold text-white mb-1">คำแนะนำในการติดตั้งบน cPanel / Web Hosting:</div>
                        <p class="leading-relaxed">
                            ท่านสามารถสร้างฐานข้อมูล MySQL เพียงก้อนเดียวใน cPanel แล้วกดปุ่ม <b>"อัปเดตและซ่อมแซมฐานข้อมูลทันที"</b> ในแท็บที่ 2 จากนั้นเพิ่มโรงเรียนได้ไม่จำกัด ทุกโรงเรียนจะได้รับบัญชีและรหัสผ่านแยกจากกันโดยสิ้นเชิง
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-600', 'text-white');
                btn.classList.add('text-slate-400');
            });

            document.getElementById('tab-content-' + tabId).classList.remove('hidden');
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            activeBtn.classList.add('active', 'bg-blue-600', 'text-white');
            activeBtn.classList.remove('text-slate-400');
        }

        function handleSmisChange(val) {
            const clean = val.replace(/\D/g, '').slice(0, 8);
            document.getElementById('school-smis').value = clean;
            document.getElementById('smis-counter').innerText = clean.length + '/8';
            if (clean.length === 8) {
                document.getElementById('school-key-preview').value = 'SCH-' + clean;
                if (!document.getElementById('school-admin-user').value) {
                    document.getElementById('school-admin-user').value = 'admin_' + clean;
                }
            } else {
                document.getElementById('school-key-preview').value = 'SCH-' + clean.padEnd(8, '_');
            }
        }

        function filterSchools(query) {
            const q = query.toLowerCase().trim();
            document.querySelectorAll('.school-row').forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const smis = row.getAttribute('data-smis') || '';
                if (name.includes(q) || smis.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        async function safeFetchJson(url, options) {
            const res = await fetch(url, options);
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                const preview = text ? text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200) : '';
                throw new Error(preview || `เซิร์ฟเวอร์ไม่ตอบกลับข้อมูล JSON (HTTP ${res.status})`);
            }
        }

        async function handleAddSchool(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-school');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>กำลังบันทึก...</span>';
            lucide.createIcons();

            const smis = document.getElementById('school-smis').value.trim();
            const name = document.getElementById('school-name').value.trim();
            const province = document.getElementById('school-province').value.trim();
            const area = document.getElementById('school-area').value.trim();
            const adminUser = document.getElementById('school-admin-user').value.trim();
            const adminPass = document.getElementById('school-admin-pass').value.trim();
            const isActive = document.getElementById('school-is-active').checked ? 1 : 0;

            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=add_school', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        smis_code: smis,
                        name: name,
                        province: province,
                        education_area: area,
                        admin_username: adminUser,
                        admin_password_plain: adminPass,
                        is_active: isActive
                    })
                });
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + data.message);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>บันทึกและเปิดใช้งานโรงเรียน</span>';
                lucide.createIcons();
            }
        }

        async function switchToSchool(schoolId, schoolName) {
            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=switch_to_school', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ school_id: schoolId })
                });
                if (data.success) {
                    window.location.href = data.redirect || 'dashboard.php';
                } else {
                    alert('ไม่สามารถสลับโรงเรียนได้: ' + data.message);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาด: ' + err.message);
            }
        }

        async function toggleSchoolStatus(schoolId, newStatus) {
            const actionText = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดระงับการใช้งาน';
            if (!confirm(`คุณต้องการ ${actionText} โรงเรียนนี้ใช่หรือไม่?`)) return;

            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=toggle_school_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ school_id: schoolId, is_active: newStatus })
                });
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: ' + err.message);
            }
        }

        async function deleteSchool(schoolId, name) {
            if (!confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบโรงเรียน "${name}" ?`)) return;
            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=delete_school', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ school_id: schoolId })
                });
                alert(data.message);
                if (data.success) location.reload();
            } catch (err) {
                alert('เกิดข้อผิดพลาด: ' + err.message);
            }
        }

        async function purgeAllDemo() {
            if (!confirm('ยืนยันการล้างข้อมูลโรงเรียนเดิมและข้อมูล Demo เก่าทั้งหมดหรือไม่?\n(ระบบจะลบข้อมูลโรงเรียนเดิมและตั้งค่าเริ่มต้นเป็น "โรงเรียนเด็กเรียนดี")')) return;
            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=purge_all_demo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                alert(data.message);
                if (data.success) location.reload();
            } catch (err) {
                alert('เกิดข้อผิดพลาด: ' + err.message);
            }
        }

        async function handleTestDb() {
            const btn = document.getElementById('btn-test-db');
            const box = document.getElementById('test-result-box');
            box.classList.remove('hidden', 'bg-emerald-950/40', 'border-emerald-700', 'text-emerald-300', 'bg-rose-950/40', 'border-rose-700', 'text-rose-300');
            box.innerText = 'กำลังทดสอบการเชื่อมต่อ...';

            const payload = {
                host: document.getElementById('db-host').value.trim(),
                port: document.getElementById('db-port').value.trim(),
                dbname: document.getElementById('db-name').value.trim(),
                user: document.getElementById('db-user').value.trim(),
                pass: document.getElementById('db-pass').value
            };

            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=test_db', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (data.success) {
                    box.classList.add('bg-emerald-950/40', 'border-emerald-700', 'text-emerald-300');
                    box.innerHTML = '✓ ' + data.message;
                } else {
                    box.classList.add('bg-rose-950/40', 'border-rose-700', 'text-rose-300');
                    box.innerHTML = '✗ ' + data.message;
                }
            } catch (e) {
                box.classList.add('bg-rose-950/40', 'border-rose-700', 'text-rose-300');
                box.innerText = '✗ ไม่สามารถเรียก API ทดสอบได้: ' + e.message;
            }
        }

        async function handleSaveDbConfig(e) {
            e.preventDefault();
            const payload = {
                host: document.getElementById('db-host').value.trim(),
                port: document.getElementById('db-port').value.trim(),
                dbname: document.getElementById('db-name').value.trim(),
                user: document.getElementById('db-user').value.trim(),
                pass: document.getElementById('db-pass').value
            };

            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=save_db_config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                alert(data.message);
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการบันทึก: ' + err.message);
            }
        }

        async function handleRunAutoMigrate() {
            if (!confirm('ยืนยันการรัน Auto-Migration โครงสร้างฐานข้อมูล MySQL และระบบ Multi-Tenant ?')) return;
            const btn = document.getElementById('btn-run-migrate');
            const icon = document.getElementById('migrate-icon');
            const logBox = document.getElementById('migration-log-box');

            icon.classList.add('animate-spin');
            btn.disabled = true;

            const appendLog = (msg, color = 'text-slate-300') => {
                const line = document.createElement('div');
                line.className = color;
                line.innerText = '> ' + msg;
                logBox.appendChild(line);
                logBox.scrollTop = logBox.scrollHeight;
            };

            appendLog('เริ่มดำเนินการอัปเดตและสร้างตาราง Multi-Tenant...', 'text-amber-400');

            try {
                const data = await safeFetchJson('api/super_admin_api.php?action=auto_migrate', { method: 'POST' });
                if (data.logs && data.logs.length) {
                    data.logs.forEach(l => appendLog(l, 'text-emerald-400'));
                }
                appendLog(data.message, data.success ? 'text-emerald-300 font-bold' : 'text-rose-400 font-bold');
                alert(data.message);
            } catch (err) {
                appendLog('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์: ' + err.message, 'text-rose-400 font-bold');
            } finally {
                icon.classList.remove('animate-spin');
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
