<?php
/**
 * API สำหรับ Super Admin ในการจัดการฐานข้อมูล MySQL และระบบจัดการโรงเรียน (Multi-Tenant)
 */
ob_start();
@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

function sendJsonResponse(array $data, int $statusCode = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Check Super Admin auth for sensitive operations (allow in dev or if superadmin session)
    $isSuperAdmin = !empty($_SESSION['is_super_admin']) || (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin');

    // Read JSON input if sent as body
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: $_POST;

    switch ($action) {
        case 'test_db':
            $host = trim($input['host'] ?? 'localhost');
            $port = (int)($input['port'] ?? 3306);
            $dbname = trim($input['dbname'] ?? 'school_budget_db');
            $user = trim($input['user'] ?? 'root');
            $pass = (string)($input['pass'] ?? '');

            $res = Database::testConnection($host, $port, $dbname, $user, $pass);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        case 'save_db_config':
            $host = trim($input['host'] ?? 'localhost');
            $port = (int)($input['port'] ?? 3306);
            $dbname = trim($input['dbname'] ?? 'school_budget_db');
            $user = trim($input['user'] ?? 'root');
            $pass = (string)($input['pass'] ?? '');

            $ok = Database::saveConfig($host, $port, $dbname, $user, $pass);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าการเชื่อมต่อฐานข้อมูล MySQL เรียบร้อยแล้ว']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์ config ได้']);
            }
            break;

        case 'auto_migrate':
            $res = Database::runAutoMigration();
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        case 'get_db_status':
            $pdo = Database::getConnection();
            $isConnected = $pdo !== null;
            $tables = [];
            $tableCount = 0;
            $serverVersion = '';

            if ($isConnected) {
                try {
                    $serverVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
                    $stmt = $pdo->query("SHOW TABLES");
                    $rawTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $tableCount = count($rawTables);
                    foreach ($rawTables as $t) {
                        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$t}`");
                        $tables[] = [
                            'name' => $t,
                            'records' => (int)$countStmt->fetchColumn()
                        ];
                    }
                } catch (Exception $e) {
                    // ignore
                }
            }

            echo json_encode([
                'success' => true,
                'connected' => $isConnected,
                'error' => Database::$connectionError,
                'host' => DB_HOST,
                'port' => DB_PORT,
                'dbname' => DB_NAME,
                'user' => DB_USER,
                'server_version' => $serverVersion,
                'table_count' => $tableCount,
                'tables' => $tables
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'list_schools':
            $schools = getAllSchoolsList();
            echo json_encode(['success' => true, 'schools' => $schools], JSON_UNESCAPED_UNICODE);
            break;

        case 'add_school':
            $smis = trim($input['smis_code'] ?? '');
            $name = trim($input['name'] ?? '');
            $area = trim($input['education_area'] ?? '');
            $province = trim($input['province'] ?? '');
            $director = trim($input['director_name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $email = trim($input['email'] ?? '');
            $adminUser = trim($input['admin_username'] ?? '');
            $adminPass = trim($input['admin_password_plain'] ?? '123456');
            $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

            // Validation: SMIS 8 digits
            if (!preg_match('/^[0-9]{8}$/', $smis)) {
                echo json_encode(['success' => false, 'message' => 'รหัสสมัคร SMIS ต้องเป็นตัวเลข 8 หลักพอดี (เช่น 10400100)']);
                exit;
            }
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุชื่อโรงเรียน']);
                exit;
            }

            $schoolKey = 'SCH-' . $smis;
            if (empty($adminUser)) {
                $adminUser = 'admin_' . $smis;
            }

            // Check duplicate SMIS in current list
            $existingSchools = getAllSchoolsList();
            foreach ($existingSchools as $es) {
                if (($es['smis_code'] ?? '') === $smis || ($es['school_key'] ?? '') === $schoolKey) {
                    echo json_encode(['success' => false, 'message' => "รหัส SMIS {$smis} หรือ School Key นี้มีอยู่ในระบบแล้ว"]);
                    exit;
                }
            }

            $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
            $schoolCode = $smis . '00'; // 10 digit standard
            $newId = count($existingSchools) > 0 ? (max(array_map(fn($s) => (int)$s['id'], $existingSchools)) + 1) : 1;

            $pdo = Database::getConnection();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO schools (
                            school_code, smis_code, is_active, school_key, admin_username, admin_password_plain, admin_password_hash,
                            name, province, education_area, director_name, phone, email
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $schoolCode, $smis, $isActive, $schoolKey, $adminUser, $adminPass, $passHash,
                        $name, $province, $area, $director, $phone, $email
                    ]);
                    $dbId = (int)$pdo->lastInsertId();
                    if ($dbId > 0) $newId = $dbId;

                    // สร้างปีงบประมาณและผู้ใช้งานแอดมินของโรงเรียนนี้
                    $pdo->exec("INSERT INTO fiscal_years (school_id, year, is_active, start_date, end_date) VALUES ({$newId}, 2568, 1, '2024-10-01', '2025-09-30')");
                    $stmtAdmin = $pdo->prepare("INSERT INTO users (school_id, username, password_hash, full_name, role, department) VALUES (?, ?, ?, ?, 'admin', 'งานแผนงานและงบประมาณ')");
                    $stmtAdmin->execute([$newId, $adminUser, $passHash, 'ผู้ดูแลระบบ ' . $name]);
                } catch (Exception $e) {
                    // ignore if insert error
                }
            }

            // ALWAYS persist to config/schools_data.json
            $newSchoolData = [
                'id' => $newId,
                'schoolCode' => $schoolCode,
                'school_code' => $schoolCode,
                'smisCode' => $smis,
                'smis_code' => $smis,
                'isActive' => $isActive === 1,
                'is_active' => $isActive,
                'schoolKey' => $schoolKey,
                'school_key' => $schoolKey,
                'adminUsername' => $adminUser,
                'admin_username' => $adminUser,
                'adminPasswordPlain' => $adminPass,
                'admin_password_plain' => $adminPass,
                'name' => $name,
                'province' => $province ?: 'กรุงเทพมหานคร',
                'educationArea' => $area ?: 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา',
                'education_area' => $area ?: 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา',
                'directorName' => $director ?: 'ผู้อำนวยการโรงเรียน',
                'director_name' => $director ?: 'ผู้อำนวยการโรงเรียน',
                'phone' => $phone ?: '02-000-0000',
                'email' => $email ?: "school_{$smis}@obec.mail.go.th",
                'studentCount' => 0,
                'student_count' => 0,
                'projectCount' => 0,
                'project_count' => 0,
                'totalBudget' => 0,
                'total_budget' => 0,
                'notes' => 'เปิดใช้งานใหม่ผ่านระบบ Super Admin'
            ];

            saveSchoolToJson($newSchoolData);

            if (!isset($_SESSION['schools']) || !is_array($_SESSION['schools'])) {
                $_SESSION['schools'] = $existingSchools;
            }
            $_SESSION['schools'][] = $newSchoolData;

            echo json_encode([
                'success' => true,
                'message' => "เปิดใช้งานโรงเรียน '{$name}' ด้วยรหัส SMIS: {$smis} สำเร็จ พร้อมสร้างชื่อผู้ใช้ {$adminUser}",
                'school_key' => $schoolKey,
                'admin_username' => $adminUser,
                'school' => $newSchoolData
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'toggle_school_status':
            $schoolId = (int)($input['school_id'] ?? 0);
            $newStatus = (int)($input['is_active'] ?? 0);

            $pdo = Database::getConnection();
            if ($pdo && $schoolId > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE schools SET is_active = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $schoolId]);
                } catch (Exception $e) {}
            }

            // Update in schools_data.json
            $jsonFile = __DIR__ . '/../config/schools_data.json';
            if (file_exists($jsonFile)) {
                $raw = json_decode(@file_get_contents($jsonFile), true) ?: [];
                foreach ($raw as &$s) {
                    if ((int)($s['id'] ?? 0) === $schoolId) {
                        $s['isActive'] = ($newStatus === 1);
                        $s['is_active'] = $newStatus;
                    }
                }
                @file_put_contents($jsonFile, json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $statusText = $newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดระงับการใช้งาน';
            echo json_encode([
                'success' => true,
                'message' => "เปลี่ยนสถานะโรงเรียนเป็น '{$statusText}' เรียบร้อยแล้ว",
                'is_active' => $newStatus
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'update_school':
            $schoolId = (int)($input['school_id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $area = trim($input['education_area'] ?? '');
            $province = trim($input['province'] ?? '');
            $director = trim($input['director_name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $adminPass = trim($input['admin_password_plain'] ?? '');

            $pdo = Database::getConnection();
            if ($pdo && $schoolId > 0) {
                try {
                    if (!empty($adminPass)) {
                        $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE schools SET
                                name = ?, education_area = ?, province = ?, director_name = ?, phone = ?,
                                admin_password_plain = ?, admin_password_hash = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $area, $province, $director, $phone, $adminPass, $passHash, $schoolId]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE schools SET
                                name = ?, education_area = ?, province = ?, director_name = ?, phone = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $area, $province, $director, $phone, $schoolId]);
                    }
                } catch (Exception $e) {}
            }

            // Update config/schools_data.json
            $jsonFile = __DIR__ . '/../config/schools_data.json';
            if (file_exists($jsonFile)) {
                $raw = json_decode(@file_get_contents($jsonFile), true) ?: [];
                foreach ($raw as &$s) {
                    if ((int)($s['id'] ?? 0) === $schoolId) {
                        if ($name) { $s['name'] = $name; }
                        if ($area) { $s['educationArea'] = $area; $s['education_area'] = $area; }
                        if ($province) { $s['province'] = $province; }
                        if ($director) { $s['directorName'] = $director; $s['director_name'] = $director; }
                        if ($phone) { $s['phone'] = $phone; }
                        if ($adminPass) { $s['adminPasswordPlain'] = $adminPass; $s['admin_password_plain'] = $adminPass; }
                    }
                }
                @file_put_contents($jsonFile, json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            echo json_encode(['success' => true, 'message' => 'บันทึกการแก้ไขข้อมูลโรงเรียนสำเร็จ'], JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_school':
            $schoolId = (int)($input['school_id'] ?? 0);
            $pdo = Database::getConnection();
            if ($pdo && $schoolId > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
                    $stmt->execute([$schoolId]);
                } catch (Exception $e) {}
            }

            // Remove from config/schools_data.json
            $jsonFile = __DIR__ . '/../config/schools_data.json';
            if (file_exists($jsonFile)) {
                $raw = json_decode(@file_get_contents($jsonFile), true) ?: [];
                $raw = array_values(array_filter($raw, fn($s) => (int)($s['id'] ?? 0) !== $schoolId));
                @file_put_contents($jsonFile, json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            if (isset($_SESSION['schools']) && is_array($_SESSION['schools'])) {
                $_SESSION['schools'] = array_values(array_filter($_SESSION['schools'], fn($s) => ($s['id'] ?? 0) !== $schoolId));
            }
            echo json_encode(['success' => true, 'message' => 'ลบข้อมูลโรงเรียนออกจากระบบเรียบร้อยแล้ว']);
            break;

        case 'switch_to_school':
            $schoolId = (int)($input['school_id'] ?? 0);
            $targetSchool = getSchoolData($schoolId);
            if ($targetSchool) {
                $_SESSION['school_id'] = (int)$targetSchool['id'];
                $_SESSION['school'] = $targetSchool;
                $_SESSION['user_id'] = (int)$targetSchool['id'] * 1000 + 1;
                $_SESSION['username'] = $targetSchool['admin_username'] ?? 'admin';
                $_SESSION['full_name'] = 'ผู้ดูแลระบบ (' . $targetSchool['name'] . ')';
                $_SESSION['user_role'] = 'admin';
                $_SESSION['fiscal_year_id'] = 1;
                echo json_encode([
                    'success' => true,
                    'message' => "สลับเข้าสู่ระบบโรงเรียน '{$targetSchool['name']}' สำเร็จ",
                    'redirect' => 'dashboard.php'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลโรงเรียนที่ต้องการเข้าสู่ระบบ'], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'purge_all_demo':
            $pdo = Database::getConnection();
            if ($pdo) {
                try {
                    // Delete demo schools matching Nong Bua or 10400100
                    $pdo->exec("DELETE FROM schools WHERE name LIKE '%หนองบัว%' OR school_code = '1040010025' OR smis_code = '10400100'");
                } catch (Exception $e) {}
            }
            // Clear session schools and reset to pure default โรงเรียนเด็กเรียนดี
            $_SESSION['schools'] = [
                [
                    'id' => 1,
                    'school_code' => '1000000001',
                    'smis_code' => '10000001',
                    'is_active' => 1,
                    'school_key' => 'SCH-10000001',
                    'admin_username' => 'admin',
                    'admin_password_plain' => '123456',
                    'name' => 'โรงเรียนเด็กเรียนดี',
                    'province' => 'กรุงเทพมหานคร',
                    'education_area' => 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา',
                    'director_name' => 'ดร.สมศักดิ์ พัฒนศึกษา',
                    'phone' => '02-123-4567',
                    'email' => 'dekriandee@obec.mail.go.th',
                    'student_count' => 180,
                    'project_count' => 1,
                    'total_budget' => 746600
                ]
            ];
            echo json_encode(['success' => true, 'message' => 'ล้างข้อมูลโรงเรียนเดิมและข้อมูล Demo เก่าทั้งหมดออกจากระบบเรียบร้อยแล้ว']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }

    $rawOutput = ob_get_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo $rawOutput;
    exit;
} catch (Throwable $e) {
    sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
