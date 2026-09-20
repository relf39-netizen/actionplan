<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allSchools = getAllSchoolsList();
$selectedSchoolId = isset($_GET['school_id']) ? (int)$_GET['school_id'] : (!empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1);
$currentSchool = getSchoolData($selectedSchoolId);
$error = null;
$successMsg = null;

// Handle form post or quick switch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? 'login');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $schoolId = (int)($_POST['school_id'] ?? 0);

    if ($action === 'switch_school_direct') {
        // Direct switch from dropdown
        $targetSchool = getSchoolData($schoolId);
        if ($targetSchool) {
            $_SESSION['school_id'] = (int)$targetSchool['id'];
            $_SESSION['school'] = $targetSchool;
            $_SESSION['user_id'] = (int)$targetSchool['id'] * 1000 + 1;
            $_SESSION['username'] = $targetSchool['admin_username'] ?? 'admin';
            $_SESSION['full_name'] = 'ผู้ดูแลระบบ (' . $targetSchool['name'] . ')';
            $_SESSION['user_role'] = 'admin';
            header('Location: dashboard.php');
            exit;
        }
    }

    $authenticated = false;
    $authSchool = null;
    $authUser = null;

    // 1. Check Super Admin
    if ($username === 'superadmin' && ($password === 'super123456' || $password === '123456')) {
        $_SESSION['user_id'] = 9999;
        $_SESSION['username'] = 'superadmin';
        $_SESSION['full_name'] = 'ผู้ดูแลระบบส่วนกลาง (Super Admin)';
        $_SESSION['user_role'] = 'superadmin';
        $_SESSION['is_super_admin'] = true;
        header('Location: super_admin.php');
        exit;
    }

    // 2. Check in MySQL Database if connected
    $pdo = Database::getConnection();
    if ($pdo) {
        // 2.1 Check in users table
        try {
            $stmt = $pdo->prepare("SELECT u.*, s.name as school_name, s.is_active as school_is_active, s.smis_code, s.school_code 
                                   FROM users u 
                                   LEFT JOIN schools s ON u.school_id = s.id 
                                   WHERE u.username = ? LIMIT 1");
            $stmt->execute([$username]);
            $dbUser = $stmt->fetch();
            if ($dbUser) {
                if (password_verify($password, $dbUser['password_hash']) || $password === '123456') {
                    if (isset($dbUser['school_is_active']) && (int)$dbUser['school_is_active'] === 0) {
                        $error = 'สถานศึกษานี้ถูกระงับการใช้งานชั่วคราว กรุณาติดต่อผู้ดูแลระบบ (Super Admin)';
                    } else {
                        $authenticated = true;
                        $authUser = $dbUser;
                        $authSchool = getSchoolData((int)$dbUser['school_id']);
                    }
                }
            }
        } catch (Exception $e) {}

        // 2.2 Check in schools table (admin_username or smis_code)
        if (!$authenticated && !$error) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM schools WHERE admin_username = ? OR smis_code = ? OR school_code = ? LIMIT 1");
                $stmt->execute([$username, $username, $username]);
                $dbSchool = $stmt->fetch();
                if ($dbSchool) {
                    $plainPass = $dbSchool['admin_password_plain'] ?? '123456';
                    $hashPass = $dbSchool['admin_password_hash'] ?? '';
                    if ($password === $plainPass || ($hashPass && password_verify($password, $hashPass)) || $password === '123456') {
                        if (isset($dbSchool['is_active']) && (int)$dbSchool['is_active'] === 0) {
                            $error = 'โรงเรียน ' . htmlspecialchars($dbSchool['name']) . ' ถูกระงับการใช้งานชั่วคราว';
                        } else {
                            $authenticated = true;
                            $authSchool = $dbSchool;
                            $authUser = [
                                'id' => (int)$dbSchool['id'] * 1000 + 1,
                                'username' => $dbSchool['admin_username'] ?: $username,
                                'full_name' => 'ผู้ดูแลระบบ (' . $dbSchool['name'] . ')',
                                'role' => 'admin',
                                'school_id' => (int)$dbSchool['id']
                            ];
                        }
                    }
                }
            } catch (Exception $e) {}
        }
    }

    // 3. Check in config/schools_data.json
    if (!$authenticated && !$error) {
        foreach ($allSchools as $s) {
            $matchUser = ($s['admin_username'] === $username) || 
                         ($s['smis_code'] === $username) || 
                         ($s['school_code'] === $username);
            $matchPass = ($password === ($s['admin_password_plain'] ?? '123456')) || ($password === '123456');

            if ($matchUser && $matchPass) {
                if (isset($s['is_active']) && (int)$s['is_active'] === 0) {
                    $error = 'โรงเรียน ' . htmlspecialchars($s['name']) . ' ถูกระงับการใช้งานชั่วคราว';
                } else {
                    $authenticated = true;
                    $authSchool = $s;
                    $authUser = [
                        'id' => (int)$s['id'] * 1000 + 1,
                        'username' => $s['admin_username'],
                        'full_name' => 'ผู้ดูแลระบบ (' . $s['name'] . ')',
                        'role' => 'admin',
                        'school_id' => (int)$s['id']
                    ];
                    break;
                }
            }
        }
    }

    // 4. Fallback demo users for School 1
    if (!$authenticated && !$error && ($password === '123456')) {
        $defaultSchool = !empty($allSchools) ? $allSchools[0] : getSchoolData(1);
        if ($username === 'admin') {
            $authenticated = true;
            $authSchool = $defaultSchool;
            $authUser = [
                'id' => 1,
                'username' => 'admin',
                'full_name' => 'นายพิเชษฐ์ ปัญญาวงศ์ (หัวหน้างานแผนงาน)',
                'role' => 'admin',
                'school_id' => (int)$defaultSchool['id']
            ];
        } elseif ($username === 'director') {
            $authenticated = true;
            $authSchool = $defaultSchool;
            $authUser = [
                'id' => 2,
                'username' => 'director',
                'full_name' => $defaultSchool['director_name'] ?? 'ดร.สมศักดิ์ พัฒนศึกษา (ผู้อำนวยการ)',
                'role' => 'director',
                'school_id' => (int)$defaultSchool['id']
            ];
        } elseif ($username === 'teacher') {
            $authenticated = true;
            $authSchool = $defaultSchool;
            $authUser = [
                'id' => 3,
                'username' => 'teacher',
                'full_name' => 'นางสาวกนกพร ใจมั่น (ครูผู้รับผิดชอบโครงการ)',
                'role' => 'teacher',
                'school_id' => (int)$defaultSchool['id']
            ];
        }
    }

    if ($authenticated && $authUser && $authSchool) {
        $_SESSION['user_id'] = $authUser['id'];
        $_SESSION['username'] = $authUser['username'];
        $_SESSION['full_name'] = $authUser['full_name'];
        $_SESSION['user_role'] = $authUser['role'] ?? 'admin';
        $_SESSION['school_id'] = (int)$authSchool['id'];
        $_SESSION['school'] = $authSchool;
        $_SESSION['fiscal_year_id'] = 1;
        header('Location: dashboard.php');
        exit;
    } else {
        if (!$error) {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบหรือใช้ปุ่มเข้าสู่ระบบด่วนของโรงเรียนด้านล่าง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณโรงเรียน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'Prompt', 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between p-4 sm:p-6">

    <!-- Top Simple Bar -->
    <header class="max-w-4xl w-full mx-auto flex items-center justify-between py-2">
        <div class="flex items-center gap-2 text-white font-bold text-sm">
            <div class="w-8 h-8 rounded-lg bg-amber-400 text-slate-950 font-black flex items-center justify-center text-xs shadow-md">
                สพ
            </div>
            <span>ระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณ</span>
        </div>
        <a href="super_admin.php" class="text-xs bg-slate-800 hover:bg-slate-700 text-amber-400 border border-slate-700 font-semibold px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
            <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
            <span>ศูนย์ควบคุม Super Admin</span>
        </a>
    </header>

    <!-- Main Card Container -->
    <div class="max-w-4xl w-full mx-auto my-auto grid grid-cols-1 md:grid-cols-12 gap-6 bg-slate-950/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-md">
        
        <!-- Left Column: School Selection & Direct Switch -->
        <div class="md:col-span-5 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-800 pb-6 md:pb-0 md:pr-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 text-xs font-semibold mb-4">
                    <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                    <span>สถานศึกษาในระบบ (Multi-Tenant)</span>
                </div>
                <h2 class="text-xl font-bold text-white mb-2">เลือกโรงเรียนที่ต้องการเข้าใช้งาน</h2>
                <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                    ระบบแยกข้อมูลแต่ละโรงเรียนอย่างเด็ดขาดด้วยรหัส SMIS 8 หลัก ข้อมูลแผนงาน โครงการ และงบประมาณจะแสดงตามโรงเรียนที่เลือก
                </p>

                <!-- Registered Schools List -->
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    <?php foreach ($allSchools as $s): ?>
                        <?php 
                            $isCur = ((int)$s['id'] === $selectedSchoolId);
                            $isActive = !empty($s['is_active']);
                        ?>
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="switch_school_direct">
                            <input type="hidden" name="school_id" value="<?= (int)$s['id'] ?>">
                            <button type="submit" class="w-full text-left p-3 rounded-xl border transition-all flex items-center justify-between group <?= $isCur ? 'bg-blue-600/20 border-blue-500/60 text-white' : 'bg-slate-900 border-slate-800 hover:border-slate-700 text-slate-300' ?>">
                                <div class="truncate mr-2">
                                    <div class="text-xs font-bold text-white group-hover:text-blue-400 flex items-center gap-1.5 truncate">
                                        <span><?= htmlspecialchars($s['name']) ?></span>
                                        <?php if (!$isActive): ?>
                                            <span class="text-[9px] px-1.5 py-0.2 rounded bg-red-900/60 text-red-300 border border-red-700/50 font-normal">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        SMIS: <?= htmlspecialchars($s['smis_code'] ?? '-') ?> • รหัส: <?= htmlspecialchars($s['admin_username'] ?? 'admin') ?>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-1 text-[11px] font-semibold text-blue-400 group-hover:translate-x-0.5 transition-transform">
                                    <span>เข้าใช้</span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </div>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Create New School Hint -->
            <div class="mt-6 pt-4 border-t border-slate-800 text-xs text-slate-400 flex items-center justify-between">
                <span>ต้องการเพิ่มโรงเรียนใหม่?</span>
                <a href="super_admin.php" class="text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>สร้างใน Super Admin</span>
                </a>
            </div>
        </div>

        <!-- Right Column: Login Form -->
        <div class="md:col-span-7 flex flex-col justify-center md:pl-2">
            <div class="mb-6 text-center md:text-left">
                <div class="inline-flex items-center gap-2 text-xs text-slate-400 mb-1">
                    <span>สถานศึกษาปัจจุบัน:</span>
                    <span class="font-bold text-white font-mono"><?= htmlspecialchars($currentSchool['name']) ?></span>
                </div>
                <h1 class="text-2xl font-bold text-white">ลงชื่อเข้าสู่ระบบ</h1>
                <p class="text-xs text-slate-400 mt-1">
                    กรอกชื่อผู้ใช้และรหัสผ่านของโรงเรียนที่สร้างใหม่ หรือทดสอบด้วยปุ่มด่วนด้านล่าง
                </p>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 p-3.5 bg-rose-950/60 border border-rose-700/60 text-rose-300 text-xs rounded-xl flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 text-rose-400"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="school_id" value="<?= (int)$currentSchool['id'] ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">ชื่อผู้ใช้งาน (Username หรือ รหัส SMIS 8 หลัก)</label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-3 text-slate-500"></i>
                        <input 
                            type="text" 
                            name="username" 
                            value="<?= htmlspecialchars($currentSchool['admin_username'] ?? 'admin') ?>" 
                            placeholder="ระบุชื่อผู้ใช้ เช่น admin หรือ <?= htmlspecialchars($currentSchool['smis_code'] ?? '10000001') ?>"
                            class="w-full text-xs pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder:text-slate-600 font-mono"
                            required
                        >
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-slate-300">รหัสผ่าน (Password)</label>
                        <span class="text-[11px] text-slate-500 font-mono">รหัสเริ่มต้น: 123456</span>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-3 text-slate-500"></i>
                        <input 
                            type="password" 
                            name="password" 
                            value="<?= htmlspecialchars($currentSchool['admin_password_plain'] ?? '123456') ?>" 
                            placeholder="ระบุรหัสผ่าน"
                            class="w-full text-xs pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder:text-slate-600 font-mono"
                            required
                        >
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>เข้าสู่ระบบ <?= htmlspecialchars($currentSchool['name']) ?></span>
                </button>
            </form>

            <!-- Quick Demo Credentials for Fast Testing -->
            <div class="mt-6 pt-5 border-t border-slate-800">
                <span class="text-[11px] font-semibold text-slate-400 block mb-2 text-center md:text-left">
                    สิทธิ์การใช้งานสำหรับทดสอบระบบ (รหัสผ่าน 123456):
                </span>
                <div class="grid grid-cols-3 gap-2">
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="username" value="admin">
                        <input type="hidden" name="password" value="123456">
                        <input type="hidden" name="school_id" value="<?= (int)$currentSchool['id'] ?>">
                        <button type="submit" class="w-full py-2 px-2 bg-slate-900 hover:bg-slate-800 text-blue-400 text-[11px] font-bold rounded-xl border border-slate-700 transition-colors flex flex-col items-center gap-0.5">
                            <span>ผู้ดูแลแผนงาน</span>
                            <span class="text-[9px] text-slate-500 font-normal">admin</span>
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="username" value="director">
                        <input type="hidden" name="password" value="123456">
                        <input type="hidden" name="school_id" value="<?= (int)$currentSchool['id'] ?>">
                        <button type="submit" class="w-full py-2 px-2 bg-slate-900 hover:bg-slate-800 text-purple-400 text-[11px] font-bold rounded-xl border border-slate-700 transition-colors flex flex-col items-center gap-0.5">
                            <span>ผู้อำนวยการ</span>
                            <span class="text-[9px] text-slate-500 font-normal">director</span>
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="username" value="teacher">
                        <input type="hidden" name="password" value="123456">
                        <input type="hidden" name="school_id" value="<?= (int)$currentSchool['id'] ?>">
                        <button type="submit" class="w-full py-2 px-2 bg-slate-900 hover:bg-slate-800 text-emerald-400 text-[11px] font-bold rounded-xl border border-slate-700 transition-colors flex flex-col items-center gap-0.5">
                            <span>ครูผู้รับผิดชอบ</span>
                            <span class="text-[9px] text-slate-500 font-normal">teacher</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>

    <!-- Bottom Footer Note -->
    <footer class="max-w-4xl w-full mx-auto text-center py-4 text-[11px] text-slate-500">
        ระบบบริหารงบประมาณและแผนปฏิบัติการประจำปีสถานศึกษา • ตามเกณฑ์มาตรฐาน สพฐ. กระทรวงศึกษาธิการ
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
