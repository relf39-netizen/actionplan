<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle school switch if requested via URL
if (isset($_GET['switch_school'])) {
    $targetId = (int)$_GET['switch_school'];
    $targetSchool = getSchoolData($targetId);
    if ($targetSchool) {
        $_SESSION['school_id'] = (int)$targetSchool['id'];
        $_SESSION['school'] = $targetSchool;
        $_SESSION['user_id'] = (int)$targetSchool['id'] * 1000 + 1;
        $_SESSION['username'] = $targetSchool['admin_username'] ?? 'admin';
        $_SESSION['full_name'] = 'ผู้ดูแลระบบ (' . $targetSchool['name'] . ')';
        $_SESSION['user_role'] = 'admin';
        $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: {$cleanUrl}");
        exit;
    }
}

// Redirect to login.php if session is not authenticated
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$school = getSchoolData();
$fiscalYear = getFiscalYearData();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isDbConnected = Database::isConnected();
$allSchools = getAllSchoolsList();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณโรงเรียน') ?> - <?= htmlspecialchars($school['name']) ?></title>
    
    <!-- Google Fonts: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Sarabun', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .printable-area { width: 100% !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs no-print">
        <div class="px-4 sm:px-6 py-2.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button id="mobile-menu-btn" type="button" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-3">
                    <img src="<?= htmlspecialchars($school['logo_url'] ?? 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=160&auto=format&fit=crop&q=80') ?>" alt="Logo" class="w-9 h-9 rounded-lg object-cover border border-slate-200 shadow-xs">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-bold text-slate-900 leading-tight"><?= htmlspecialchars($school['name']) ?></h1>
                            <?php if (count($allSchools) > 1): ?>
                            <div class="relative group">
                                <button type="button" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold px-2 py-0.5 rounded flex items-center gap-1 border border-slate-300 cursor-pointer">
                                    <span>สลับโรงเรียน (<?= count($allSchools) ?>)</span>
                                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                </button>
                                <div class="absolute left-0 top-full mt-1 w-64 bg-white border border-slate-200 rounded-xl shadow-xl p-1 z-50 hidden group-hover:block">
                                    <div class="text-[10px] font-bold text-slate-400 px-2 py-1">รายชื่อโรงเรียนในระบบ</div>
                                    <?php foreach ($allSchools as $sItem): ?>
                                        <a href="?switch_school=<?= (int)$sItem['id'] ?>" class="flex items-center justify-between px-2 py-1.5 rounded-lg text-xs hover:bg-blue-50 text-slate-700 <?= ((int)$sItem['id'] === (int)$school['id']) ? 'font-bold text-blue-700 bg-blue-50/70' : '' ?>">
                                            <span class="truncate mr-2"><?= htmlspecialchars($sItem['name']) ?></span>
                                            <span class="text-[10px] text-slate-400 font-mono shrink-0"><?= htmlspecialchars($sItem['smis_code'] ?? '') ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                    <div class="border-t border-slate-100 mt-1 pt-1">
                                        <a href="super_admin.php" class="flex items-center gap-1 px-2 py-1 text-[11px] text-amber-600 hover:bg-amber-50 rounded-lg font-semibold">
                                            <i data-lucide="plus-circle" class="w-3 h-3"></i>
                                            <span>เพิ่ม/จัดการโรงเรียน (Super Admin)</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 hidden sm:block"><?= htmlspecialchars($school['affiliation'] ?? 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)') ?> • รหัส SMIS: <?= htmlspecialchars($school['smis_code'] ?? '-') ?> • ปีงบ <?= $fiscalYear['year'] ?></p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Super Admin link -->
                <a href="super_admin.php" class="hidden sm:flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold border border-amber-200 transition-colors" title="ไปยังศูนย์ควบคุม Super Admin">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span>Super Admin</span>
                </a>
                <!-- Fiscal Year Badge -->
                <div class="hidden md:flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-800 rounded-full text-xs font-semibold border border-blue-200">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <span>ปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?></span>
                </div>

                <!-- Database Status Badge -->
                <?php if ($isDbConnected): ?>
                    <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-full border border-emerald-200" title="เชื่อมต่อฐานข้อมูล MySQL แล้ว">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="hidden md:inline">MySQL Online</span>
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-full border border-amber-200" title="ทำงานในโหมดสาธิต (สามารถตั้งค่า config/database.php เพื่อเชื่อมต่อ MySQL)">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="hidden md:inline">Demo Mode</span>
                    </span>
                <?php endif; ?>

                <!-- User Profile -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold text-xs">
                        <?= mb_substr($_SESSION['username'] ?? 'A', 0, 1, 'UTF-8') ?>
                    </div>
                    <div class="text-left hidden lg:block">
                        <div class="text-xs font-bold text-slate-800"><?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?></div>
                        <div class="text-[10px] text-slate-500"><?= htmlspecialchars($_SESSION['user_role'] ?? 'ผู้ดูแลระบบ') ?></div>
                    </div>
                    <a href="logout.php" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-slate-100 rounded-lg" title="ออกจากระบบ">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Offline/Demo Notice banner if MySQL is not connected -->
        <?php if (!$isDbConnected): ?>
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-t border-amber-200 px-4 py-1.5 text-xs text-amber-900 flex items-center justify-between no-print">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0"></i>
                <span>ระบบกำลังทำงานในโหมดสาธิต (ไม่จำเป็นต้องมี MySQL ก็ใช้งานได้ทันที) หากต้องการเชื่อมต่อฐานข้อมูล ให้สร้างฐานข้อมูล นำเข้า <code>database/schema.sql</code> และแก้ <code>config/database.php</code></span>
            </div>
            <a href="settings.php" class="underline font-semibold hover:text-amber-950 shrink-0 ml-2">ดูวิธีตั้งค่า DB</a>
        </div>
        <?php endif; ?>
    </header>

    <div class="flex-1 flex flex-col lg:flex-row">
