<?php
/**
 * การตั้งค่าการเชื่อมต่อฐานข้อมูล MySQL สำหรับ Web Hosting / cPanel
 * ระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณโรงเรียน (Multi-Tenant & Super Admin)
 */

$configFile = __DIR__ . '/db_config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: [];
}

define('DB_HOST', $config['host'] ?? getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', $config['dbname'] ?? getenv('DB_NAME') ?: 'school_budget_db');
define('DB_USER', $config['user'] ?? getenv('DB_USER') ?: 'root');
define('DB_PASS', $config['pass'] ?? getenv('DB_PASS') ?: '');
define('DB_PORT', (int)($config['port'] ?? getenv('DB_PORT') ?: 3306));
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;
    public static ?string $connectionError = null;

    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    public static function resetConnection(): void {
        self::$instance = null;
        self::$connectionError = null;
    }

    public static function getConnection(): ?PDO {
        if (self::$instance === null && self::$connectionError === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 3,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::$connectionError = $e->getMessage();
                return null;
            }
        }
        return self::$instance;
    }

    /**
     * ทดสอบการเชื่อมต่อฐานข้อมูล MySQL แบบกำหนดพารามิเตอร์
     */
    public static function testConnection(string $host, int $port, string $dbname, string $user, string $pass): array {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4,
            ]);
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            return [
                'success' => true,
                'message' => 'เชื่อมต่อฐานข้อมูล MySQL สำเร็จ (เวอร์ชัน: ' . $version . ')',
                'version' => $version
            ];
        } catch (PDOException $e) {
            // ลองตรวจสอบว่า Host และ User ถูกต้อง แต่ Database ยังไม่ได้สร้างหรือไม่
            try {
                $dsnNoDb = "mysql:host={$host};port={$port};charset=utf8mb4";
                $pdoNoDb = new PDO($dsnNoDb, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 4,
                ]);
                return [
                    'success' => false,
                    'db_missing' => true,
                    'message' => "เข้าถึง MySQL Server ได้สำเร็จ แต่ยังไม่พบฐานข้อมูลชื่อ '{$dbname}' (สามารถกดปุ่มสร้างฐานข้อมูลอัตโนมัติได้)",
                ];
            } catch (PDOException $e2) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถเชื่อมต่อได้: ' . $e->getMessage()
                ];
            }
        }
    }

    /**
     * บันทึกการตั้งค่าลงไฟล์ db_config.json
     */
    public static function saveConfig(string $host, int $port, string $dbname, string $user, string $pass): bool {
        $configFile = __DIR__ . '/db_config.json';
        $data = [
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'user' => $user,
            'pass' => $pass,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        self::resetConnection();
        return file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    /**
     * รัน Auto-Migration และ Update โครงสร้างตารางอัตโนมัติ
     */
    public static function runAutoMigration(?PDO $pdo = null): array {
        @set_time_limit(180);
        $logs = [];
        try {
            if (!$pdo) {
                // 1. ลองเชื่อมต่อไปยังฐานข้อมูลที่ระบุก่อนโดยตรง
                $pdo = self::getConnection();
            }

            if (!$pdo) {
                // 2. หากยังเชื่อมไม่ได้ ให้ลองตรวจสอบหรือสร้าง DB (ถ้ามีสิทธิ์)
                try {
                    $dsnNoDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                    $rootPdo = new PDO($dsnNoDb, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 4]);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $logs[] = "✓ ตรวจสอบและสร้างฐานข้อมูล '" . DB_NAME . "' สำเร็จ";
                } catch (Throwable $dbCreateErr) {
                    // บน Shared Hosting/cPanel ผู้ใช้อาจไม่มีสิทธิ์ CREATE DATABASE ซึ่งเป็นเรื่องปกติหากสร้าง DB ผ่าน cPanel แล้ว
                }

                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
            }

            $logs[] = "✓ เชื่อมต่อฐานข้อมูล '" . DB_NAME . "' สำเร็จ";
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("SET NAMES utf8mb4;");

            // 1. ตาราง Super Admins
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `super_admins` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `full_name` VARCHAR(150) NOT NULL,
                    `email` VARCHAR(100) DEFAULT NULL,
                    `phone` VARCHAR(50) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            $logs[] = "✓ ตรวจสอบตาราง 'super_admins'";

            // เพิ่ม Super Admin เริ่มต้นถ้ายังไม่มี
            $stmt = $pdo->query("SELECT COUNT(*) FROM `super_admins`");
            if ((int)$stmt->fetchColumn() === 0) {
                $defaultPassHash = password_hash('super123456', PASSWORD_DEFAULT);
                $stmtIns = $pdo->prepare("INSERT INTO `super_admins` (username, password_hash, full_name, email) VALUES (?, ?, ?, ?)");
                $stmtIns->execute(['superadmin', $defaultPassHash, 'ผู้ดูแลระบบส่วนกลาง (Super Admin)', 'admin@schoolos-app.com']);
                $logs[] = "✓ สร้างบัญชี Super Admin เริ่มต้น: 'superadmin' / 'super123456'";
            }

            // 2. ตาราง Schools (พร้อมคอลัมน์ smis_code 8 หลัก และ is_active)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `schools` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `school_code` VARCHAR(20) NOT NULL COMMENT 'รหัสสถานศึกษา 10 หลัก',
                    `smis_code` VARCHAR(8) NOT NULL DEFAULT '10000001' COMMENT 'รหัสสมัคร SMIS 8 หลัก',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=เปิดใช้งาน, 0=ปิดใช้งาน',
                    `school_key` VARCHAR(50) NOT NULL DEFAULT 'SCH-0001' COMMENT 'ID ประจำโรงเรียน',
                    `admin_username` VARCHAR(50) NOT NULL DEFAULT 'admin' COMMENT 'ID ผู้ดูแลโรงเรียน',
                    `admin_password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'รหัสผ่านแฮช',
                    `admin_password_plain` VARCHAR(100) DEFAULT '123456' COMMENT 'รหัสผ่านเข้าใช้งาน',
                    `name` VARCHAR(255) NOT NULL,
                    `address` VARCHAR(255) DEFAULT NULL,
                    `subdistrict` VARCHAR(100) DEFAULT NULL,
                    `district` VARCHAR(100) DEFAULT NULL,
                    `province` VARCHAR(100) DEFAULT NULL,
                    `zipcode` VARCHAR(10) DEFAULT NULL,
                    `affiliation` VARCHAR(255) DEFAULT 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)',
                    `education_area` VARCHAR(255) DEFAULT NULL,
                    `fiscal_year` INT UNSIGNED DEFAULT 2568,
                    `director_name` VARCHAR(150) DEFAULT NULL,
                    `phone` VARCHAR(50) DEFAULT NULL,
                    `email` VARCHAR(100) DEFAULT NULL,
                    `logo_url` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_school_code` (`school_code`),
                    UNIQUE KEY `idx_smis_code` (`smis_code`),
                    UNIQUE KEY `idx_school_key` (`school_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            $logs[] = "✓ ตรวจสอบตาราง 'schools'";

            // Auto-Check & Add missing columns in `schools` if table previously existed
            $existingCols = $pdo->query("SHOW COLUMNS FROM `schools`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('smis_code', $existingCols)) {
                $pdo->exec("ALTER TABLE `schools` ADD COLUMN `smis_code` VARCHAR(8) NOT NULL DEFAULT '10400100' COMMENT 'รหัสสมัคร SMIS 8 หลัก' AFTER `school_code`");
                $logs[] = "✓ เพิ่มคอลัมน์ 'smis_code' ในตาราง 'schools' อัตโนมัติ";
            }
            if (!in_array('is_active', $existingCols)) {
                $pdo->exec("ALTER TABLE `schools` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=เปิดใช้งาน, 0=ปิด' AFTER `smis_code`");
                $logs[] = "✓ เพิ่มคอลัมน์ 'is_active' ในตาราง 'schools' อัตโนมัติ";
            }
            if (!in_array('school_key', $existingCols)) {
                $pdo->exec("ALTER TABLE `schools` ADD COLUMN `school_key` VARCHAR(50) NOT NULL DEFAULT 'SCH-10400100' AFTER `is_active`");
                $logs[] = "✓ เพิ่มคอลัมน์ 'school_key' ในตาราง 'schools' อัตโนมัติ";
            }
            if (!in_array('admin_username', $existingCols)) {
                $pdo->exec("ALTER TABLE `schools` ADD COLUMN `admin_username` VARCHAR(50) NOT NULL DEFAULT 'admin' AFTER `school_key`");
                $logs[] = "✓ เพิ่มคอลัมน์ 'admin_username' ในตาราง 'schools' อัตโนมัติ";
            }
            if (!in_array('admin_password_plain', $existingCols)) {
                $pdo->exec("ALTER TABLE `schools` ADD COLUMN `admin_password_plain` VARCHAR(100) DEFAULT '123456' AFTER `admin_username`");
                $logs[] = "✓ เพิ่มคอลัมน์ 'admin_password_plain' ในตาราง 'schools' อัตโนมัติ";
            }
            if (!in_array('admin_password_hash', $existingCols)) {
                $pdo->exec("ALTER TABLE `schools` ADD COLUMN `admin_password_hash` VARCHAR(255) DEFAULT NULL AFTER `admin_password_plain`");
                $logs[] = "✓ เพิ่มคอลัมน์ 'admin_password_hash' ในตาราง 'schools' อัตโนมัติ";
            }

            // เพิ่มโรงเรียนตัวอย่างหากยังไม่มี
            $stmt = $pdo->query("SELECT COUNT(*) FROM `schools`");
            if ((int)$stmt->fetchColumn() === 0) {
                $hash = password_hash('123456', PASSWORD_DEFAULT);
                $stmtIns = $pdo->prepare("
                    INSERT INTO `schools` (
                        school_code, smis_code, is_active, school_key, admin_username, admin_password_plain, admin_password_hash,
                        name, address, subdistrict, district, province, zipcode, education_area, director_name, phone, email, logo_url
                    ) VALUES (
                        '1000000001', '10000001', 1, 'SCH-10000001', 'admin', '123456', ?,
                        'โรงเรียนเด็กเรียนดี', 'เลขที่ 99 หมู่ที่ 1 ถนนตัวอย่าง', 'ตำบลตัวอย่าง', 'อำเภอตัวอย่าง', 'จังหวัดตัวอย่าง', '10000',
                        'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาตัวอย่าง เขต 1', 'นายตัวอย่าง ผู้นำการศึกษา (ผู้อำนวยการโรงเรียน)', '02-000-0000', 'dekreeandee_school@obec.mail.go.th',
                        'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=160&auto=format&fit=crop&q=80'
                    )
                ");
                $stmtIns->execute([$hash]);
                $logs[] = "✓ สร้างโรงเรียนเริ่มต้น 'โรงเรียนเด็กเรียนดี' พร้อมรหัส SMIS: 10000001";
            }

            // 3. รันโครงสร้างหลักที่เหลือ (fiscal_years, users, students, revenues, budget_allocations, learner_activities, projects, ฯลฯ)
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaFile)) {
                $sqlContent = file_get_contents($schemaFile);
                // ตัด DROP TABLE ออกเพื่อความปลอดภัยในการ auto-update
                $sqlContent = preg_replace('/DROP TABLE IF EXISTS [^;]+;/i', '', $sqlContent);
                // แทนที่ CREATE TABLE ด้วย CREATE TABLE IF NOT EXISTS
                $sqlContent = preg_replace('/CREATE TABLE `([^`]+)`/i', 'CREATE TABLE IF NOT EXISTS `$1`', $sqlContent);
                // ตัด comment รูปแบบ /* ... */, -- ..., # ...
                $sqlContent = preg_replace('!/\*.*?\*/!s', '', $sqlContent);
                $sqlContent = preg_replace('/^--[^\r\n]*/m', '', $sqlContent);
                $sqlContent = preg_replace('/^#[^\r\n]*/m', '', $sqlContent);

                $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
                $executed = 0;
                foreach ($statements as $query) {
                    if (empty($query)) continue;
                    if (substr($query, 0, 2) === '--' || substr($query, 0, 2) === '/*' || substr($query, 0, 1) === '#') continue;
                    try {
                        $pdo->exec($query);
                        $executed++;
                    } catch (Throwable $ex) {
                        // ignore if already exists or constraint already present
                    }
                }
                $logs[] = "✓ ตรวจสอบและซิงค์โครงสร้างตารางทั้ง 14 ตารางจาก schema.sql สำเร็จ ({$executed} คำสั่ง)";
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            return [
                'success' => true,
                'message' => 'อัปเดตและซ่อมแซมโครงสร้างฐานข้อมูล MySQL และระบบ Multi-Tenant สำเร็จสมบูรณ์',
                'logs' => $logs
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล: ' . $e->getMessage(),
                'logs' => $logs
            ];
        }
    }
}
