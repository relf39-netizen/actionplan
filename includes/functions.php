<?php
/**
 * ฟังก์ชันกลางสำหรับระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณโรงเรียน
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ป้องกัน XSS
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * สร้างและตรวจสอบ CSRF Token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * จัดรูปแบบตัวเลขเงินบาท
 */
function formatMoney($amount): string {
    return number_format((float)$amount, 2, '.', ',');
}

/**
 * แปลงวันที่ ค.ศ. เป็น วันที่ภาษาไทย พ.ศ.
 */
function formatThaiDate(?string $dateStr): string {
    if (!$dateStr) return '-';
    $thaiMonths = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;
    $d = date('j', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts) + 543;
    return "$d {$thaiMonths[$m]} $y";
}

/**
 * แปลงตัวเลขเป็นคำอ่านเงินบาทไทย
 */
function bahtText(float $number): string {
    $number = number_format($number, 2, '.', '');
    [$integer, $fraction] = explode('.', $number);
    
    $digits = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $positions = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
    
    $convert = function ($numStr) use ($digits, $positions) {
        $len = strlen($numStr);
        $res = '';
        for ($i = 0; $i < $len; $i++) {
            $d = (int)$numStr[$i];
            $pos = $len - $i - 1;
            if ($d !== 0) {
                if ($pos % 6 === 1 && $d === 1 && $len > 1) {
                    $res .= 'สิบ';
                } elseif ($pos % 6 === 1 && $d === 2) {
                    $res .= 'ยี่สิบ';
                } elseif ($pos % 6 === 0 && $d === 1 && $len > 1 && $i === $len - 1) {
                    $res .= 'เอ็ด';
                } else {
                    $res .= $digits[$d] . $positions[$pos % 6];
                }
            }
            if ($pos % 6 === 0 && $pos > 0) {
                $res .= 'ล้าน';
            }
        }
        return $res;
    };

    $intPart = (int)$integer === 0 ? 'ศูนย์บาท' : $convert($integer) . 'บาท';
    $fracPart = (int)$fraction === 0 ? 'ถ้วน' : $convert($fraction) . 'สตางค์';
    return $intPart . $fracPart;
}

/**
 * ดึงรายชื่อโรงเรียนทั้งหมดในระบบ (จาก DB หรือจากไฟล์ config/schools_data.json)
 */
function getAllSchoolsList(): array {
    $schools = [];
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM schools ORDER BY id ASC");
            $schools = $stmt->fetchAll();
            if (!empty($schools)) {
                return $schools;
            }
        } catch (Exception $e) {
            // fallback to JSON
        }
    }

    // Read from config/schools_data.json
    $jsonFile = __DIR__ . '/../config/schools_data.json';
    if (file_exists($jsonFile)) {
        $jsonContent = @file_get_contents($jsonFile);
        $decoded = json_decode($jsonContent, true);
        if (is_array($decoded) && !empty($decoded)) {
            // Normalize field names
            foreach ($decoded as $s) {
                $schools[] = [
                    'id' => (int)($s['id'] ?? 1),
                    'school_code' => $s['schoolCode'] ?? ($s['school_code'] ?? '1000000001'),
                    'smis_code' => $s['smisCode'] ?? ($s['smis_code'] ?? '10000001'),
                    'name' => $s['name'] ?? 'โรงเรียนเด็กเรียนดี',
                    'province' => $s['province'] ?? 'กรุงเทพมหานคร',
                    'education_area' => $s['educationArea'] ?? ($s['education_area'] ?? 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา'),
                    'director_name' => $s['directorName'] ?? ($s['director_name'] ?? 'ผู้อำนวยการโรงเรียน'),
                    'phone' => $s['phone'] ?? '02-000-0000',
                    'email' => $s['email'] ?? '',
                    'admin_username' => $s['adminUsername'] ?? ($s['admin_username'] ?? 'admin'),
                    'admin_password_plain' => $s['adminPasswordPlain'] ?? ($s['admin_password_plain'] ?? '123456'),
                    'is_active' => isset($s['isActive']) ? ($s['isActive'] ? 1 : 0) : (isset($s['is_active']) ? (int)$s['is_active'] : 1),
                    'school_key' => $s['schoolKey'] ?? ($s['school_key'] ?? 'SCH-10000001'),
                    'student_count' => (int)($s['studentCount'] ?? ($s['student_count'] ?? 180)),
                    'project_count' => (int)($s['projectCount'] ?? ($s['project_count'] ?? 1)),
                    'total_budget' => (float)($s['totalBudget'] ?? ($s['total_budget'] ?? 746600)),
                ];
            }
            return $schools;
        }
    }

    if (isset($_SESSION['schools']) && is_array($_SESSION['schools']) && !empty($_SESSION['schools'])) {
        return $_SESSION['schools'];
    }

    // Default fallback
    return [
        [
            'id' => 1,
            'school_code' => '1000000001',
            'smis_code' => '10000001',
            'name' => 'โรงเรียนเด็กเรียนดี',
            'province' => 'กรุงเทพมหานคร',
            'education_area' => 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา',
            'director_name' => 'ดร.สมศักดิ์ พัฒนศึกษา (ผู้อำนวยการ)',
            'phone' => '02-123-4567',
            'email' => 'dekriandee_school@obec.mail.go.th',
            'admin_username' => 'admin',
            'admin_password_plain' => '123456',
            'is_active' => 1,
            'school_key' => 'SCH-10000001',
            'student_count' => 180,
            'project_count' => 1,
            'total_budget' => 746600,
        ]
    ];
}

/**
 * บันทึกโรงเรียนใหม่ลงในไฟล์ config/schools_data.json
 */
function saveSchoolToJson(array $newSchool): bool {
    $jsonFile = __DIR__ . '/../config/schools_data.json';
    $existing = [];
    if (file_exists($jsonFile)) {
        $existing = json_decode(@file_get_contents($jsonFile), true) ?: [];
    }
    
    // Check if duplicate SMIS exists
    $smis = $newSchool['smisCode'] ?? ($newSchool['smis_code'] ?? '');
    $updated = false;
    foreach ($existing as $idx => $s) {
        $sSmis = $s['smisCode'] ?? ($s['smis_code'] ?? '');
        if ($sSmis === $smis && !empty($smis)) {
            $existing[$idx] = array_merge($s, $newSchool);
            $updated = true;
            break;
        }
    }
    if (!$updated) {
        $existing[] = $newSchool;
    }

    $dir = dirname($jsonFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return @file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * โฟลเดอร์เก็บข้อมูลเฉพาะของแต่ละโรงเรียน (Multi-Tenant Local Storage)
 */
function getSchoolDataDir(int $schoolId): string {
    $dir = __DIR__ . '/../config/data_school_' . $schoolId;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

/**
 * ดึงข้อมูลโรงเรียนปัจจุบัน (ตาม school_id ใน Session หรือระบุ)
 */
function getSchoolData(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    // 1. ตรวจสอบไฟล์ config/data_school_{$schoolId}/school_info.json ก่อน
    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/school_info.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data) && !empty($data['name'])) {
            return $data;
        }
    }

    // 2. ตรวจสอบจาก MySQL
    $db = Database::getConnection();
    if ($db) {
        try {
            if ($schoolId > 0) {
                $stmt = $db->prepare("SELECT * FROM schools WHERE id = ? LIMIT 1");
                $stmt->execute([$schoolId]);
                $row = $stmt->fetch();
                if ($row) return $row;
            }
        } catch (Exception $e) {
            // fallback
        }
    }

    // 3. ตรวจสอบจาก config/schools_data.json
    $allSchools = getAllSchoolsList();
    foreach ($allSchools as $s) {
        if ((int)$s['id'] === $schoolId) {
            return array_merge([
                'address' => '124 หมู่ที่ 3 ถนนมิตรภาพ',
                'subdistrict' => 'ในเมือง',
                'district' => 'เมือง',
                'province' => $s['province'] ?? 'กรุงเทพมหานคร',
                'zipcode' => '10100',
                'affiliation' => 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)',
                'education_area' => $s['education_area'] ?? 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา',
                'fiscal_year' => 2568,
                'director_name' => $s['director_name'] ?? 'ผู้อำนวยการโรงเรียน',
                'phone' => $s['phone'] ?? '02-000-0000',
                'email' => $s['email'] ?? 'school@obec.mail.go.th',
                'logo_url' => 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=160&auto=format&fit=crop&q=80',
                'philosophy' => 'ปญฺญา โลกสฺมิ ปชฺโชโต (ปัญญาเป็นแสงสว่างในโลก)',
                'vision' => 'มุ่งมั่นจัดการศึกษาอย่างมีคุณภาพ ผู้เรียนมีคุณธรรม จริยธรรม ก้าวทันเทคโนโลยีและปัญญาประดิษฐ์ ดำรงชีวิตตามหลักปรัชญาของเศรษฐกิจพอเพียง',
                'mission' => "1. พัฒนาผู้เรียนให้มีคุณภาพตามมาตรฐานการศึกษาขั้นพื้นฐานและทักษะแห่งศตวรรษที่ 21\n2. ส่งเสริมคุณธรรม จริยธรรม ความเป็นไทย และน้อมนำหลักปรัชญาของเศรษฐกิจพอเพียง\n3. พัฒนาครูและบุคลากรทางการศึกษาให้มีความเชี่ยวชาญด้านการจัดการเรียนรู้เชิงรุก (Active Learning)\n4. บริหารจัดการสถานศึกษาอย่างมีประสิทธิภาพด้วยระบบธรรมาภิบาลและการมีส่วนร่วม",
                'goals' => "1. ผู้เรียนมีผลสัมฤทธิ์ทางการเรียนสูงขึ้นและผ่านเกณฑ์การประเมินระดับชาติ\n2. นักเรียนทุกคนมีทักษะดิจิทัลและการใช้ AI อย่างปลอดภัยและสร้างสรรค์\n3. ครูจัดการเรียนรู้เชิงรุกโดยเน้นผู้เรียนเป็นสำคัญ 100%\n4. แผนปฏิบัติการประจำปีได้รับการบริหารจัดการอย่างโปร่งใส ตรวจสอบได้",
                'teacher_count' => 15,
                'student_count' => (int)($s['student_count'] ?? 180),
            ], $s);
        }
    }

    // 4. Default Fallback
    return [
        'id' => $schoolId,
        'school_code' => '1000000001',
        'smis_code' => '10000001',
        'name' => 'โรงเรียนเด็กเรียนดี',
        'address' => '124 หมู่ที่ 3 ถนนมิตรภาพ',
        'subdistrict' => 'ในเมือง',
        'district' => 'เมือง',
        'province' => 'กรุงเทพมหานคร',
        'zipcode' => '10100',
        'affiliation' => 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)',
        'education_area' => 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา',
        'fiscal_year' => 2568,
        'director_name' => 'ดร.สมศักดิ์ พัฒนศึกษา (ผู้อำนวยการ)',
        'phone' => '02-123-4567',
        'email' => 'dekriandee_school@obec.mail.go.th',
        'logo_url' => 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=160&auto=format&fit=crop&q=80',
        'philosophy' => 'ปญฺญา โลกสฺมิ ปชฺโชโต (ปัญญาเป็นแสงสว่างในโลก)',
        'vision' => 'มุ่งมั่นจัดการศึกษาอย่างมีคุณภาพ ผู้เรียนมีคุณธรรม จริยธรรม ก้าวทันเทคโนโลยี ดำรงชีวิตตามหลักปรัชญาของเศรษฐกิจพอเพียง',
        'mission' => "1. พัฒนาผู้เรียนให้มีคุณภาพตามมาตรฐานการศึกษาขั้นพื้นฐาน\n2. ส่งเสริมคุณธรรม จริยธรรมและค่านิยมที่พึงประสงค์\n3. พัฒนาครูและบุคลากรทางการศึกษาให้มีสมรรถนะสูง",
        'goals' => "1. ผู้เรียนมีผลสัมฤทธิ์ทางการเรียนสูงขึ้น\n2. ครูร้อยละ 100 จัดการเรียนรู้เชิงรุก",
        'teacher_count' => 15,
        'student_count' => 180,
    ];
}

/**
 * อัปเดตข้อมูลโรงเรียนและตราสัญลักษณ์ (Save School Profile)
 */
function updateSchoolData(int $schoolId, array $data): bool {
    $current = getSchoolData($schoolId);
    $merged = array_merge($current, $data);
    $merged['id'] = $schoolId;

    // 1. บันทึกลง config/data_school_{$schoolId}/school_info.json
    $dir = getSchoolDataDir($schoolId);
    @file_put_contents($dir . '/school_info.json', json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 2. อัปเดตไฟล์ config/schools_data.json สำหรับ Super Admin & Login List
    saveSchoolToJson([
        'id' => $schoolId,
        'schoolCode' => $merged['school_code'] ?? ($merged['schoolCode'] ?? '1000000001'),
        'smisCode' => $merged['smis_code'] ?? ($merged['smisCode'] ?? '10000001'),
        'name' => $merged['name'] ?? 'โรงเรียนเด็กเรียนดี',
        'province' => $merged['province'] ?? '',
        'educationArea' => $merged['education_area'] ?? ($merged['educationArea'] ?? ''),
        'directorName' => $merged['director_name'] ?? ($merged['directorName'] ?? ''),
        'phone' => $merged['phone'] ?? '',
        'email' => $merged['email'] ?? '',
        'adminUsername' => $merged['admin_username'] ?? 'admin',
        'adminPasswordPlain' => $merged['admin_password_plain'] ?? '123456',
        'isActive' => true,
        'studentCount' => (int)($merged['student_count'] ?? 180),
        'totalBudget' => (float)($merged['total_budget'] ?? 746600),
    ]);

    // 3. อัปเดตฐานข้อมูล MySQL (ถ้าต่ออยู่)
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE schools SET 
                name = :name,
                school_code = :school_code,
                smis_code = :smis_code,
                affiliation = :affiliation,
                education_area = :education_area,
                director_name = :director_name,
                phone = :phone,
                email = :email,
                province = :province,
                logo_url = :logo_url
                WHERE id = :id");
            $stmt->execute([
                ':id' => $schoolId,
                ':name' => $merged['name'] ?? '',
                ':school_code' => $merged['school_code'] ?? '',
                ':smis_code' => $merged['smis_code'] ?? '',
                ':affiliation' => $merged['affiliation'] ?? '',
                ':education_area' => $merged['education_area'] ?? '',
                ':director_name' => $merged['director_name'] ?? '',
                ':phone' => $merged['phone'] ?? '',
                ':email' => $merged['email'] ?? '',
                ':province' => $merged['province'] ?? '',
                ':logo_url' => $merged['logo_url'] ?? '',
            ]);
        } catch (Exception $e) {
            // ignore DB update failure if table columns differ
        }
    }

    // 4. อัปเดต Session
    $_SESSION['school'] = $merged;
    return true;
}

/**
 * ค่าเริ่มต้นเกณฑ์อัตราเงินอุดหนุนและโครงการเรียนฟรี 15 ปี (มติ ครม. ปรับอัตราใหม่)
 */
function getDefaultSubsidyRates(int $year = 2568): array {
    // อัตราก้าวหน้าตามมติ ครม.
    if ($year >= 2569) {
        return [
            'kindergarten' => 1908,
            'primary' => 2194,
            'secondary_lower' => 3716,
            'secondary_upper' => 4236,
            'topup' => 500,
            'textbook' => 650,
            'uniform' => 400,
            'stationery' => 440,
            'activity' => 460,
            'lunch_per_day' => 24,
            'lunch_days' => 200,
            'poor_fund' => 1500,
        ];
    } elseif ($year >= 2568) {
        return [
            'kindergarten' => 1854,
            'primary' => 2122,
            'secondary_lower' => 3608,
            'secondary_upper' => 4018,
            'topup' => 500,
            'textbook' => 650,
            'uniform' => 400,
            'stationery' => 440,
            'activity' => 460,
            'lunch_per_day' => 24,
            'lunch_days' => 200,
            'poor_fund' => 1500,
        ];
    } else {
        return [
            'kindergarten' => 1800,
            'primary' => 2050,
            'secondary_lower' => 3500,
            'secondary_upper' => 3800,
            'topup' => 500,
            'textbook' => 650,
            'uniform' => 380,
            'stationery' => 400,
            'activity' => 460,
            'lunch_per_day' => 24,
            'lunch_days' => 200,
            'poor_fund' => 1500,
        ];
    }
}

/**
 * ดึงการตั้งค่าปีงบประมาณและอัตราเงินอุดหนุน
 */
function getFiscalYearConfig(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $configFile = $dir . '/fiscal_year_config.json';
    if (file_exists($configFile)) {
        $data = json_decode(@file_get_contents($configFile), true);
        if (is_array($data) && !empty($data['active_year'])) {
            return $data;
        }
    }

    $defaultYear = 2568;
    return [
        'active_year' => $defaultYear,
        'fiscal_years' => [
            ['id' => 1, 'year' => 2567, 'is_active' => false, 'start_date' => '2023-10-01', 'end_date' => '2024-09-30'],
            ['id' => 2, 'year' => 2568, 'is_active' => true, 'start_date' => '2024-10-01', 'end_date' => '2025-09-30'],
            ['id' => 3, 'year' => 2569, 'is_active' => false, 'start_date' => '2025-10-01', 'end_date' => '2026-09-30'],
            ['id' => 4, 'year' => 2570, 'is_active' => false, 'start_date' => '2026-10-01', 'end_date' => '2027-09-30'],
        ],
        'rates' => getDefaultSubsidyRates($defaultYear),
        'proposal_window' => [
            'is_open' => true,
            'open_date' => '2024-10-01',
            'close_date' => '2025-01-31',
            'notice' => "เปิดรับการเสนอโครงการตามแผนปฏิบัติการประจำปีงบประมาณ พ.ศ. {$defaultYear}",
        ]
    ];
}

/**
 * ดึงอัตราเงินอุดหนุนรายหัวและโครงการเรียนฟรี 15 ปีของโรงเรียน
 */
function getFiscalYearRates(?int $schoolId = null): array {
    $config = getFiscalYearConfig($schoolId);
    return $config['rates'] ?? getDefaultSubsidyRates($config['active_year'] ?? 2568);
}

/**
 * บันทึกการตั้งค่าปีงบประมาณและอัตราเงินอุดหนุน
 */
function saveFiscalYearConfig(int $schoolId, array $config, bool $syncRevenues = true): bool {
    $dir = getSchoolDataDir($schoolId);
    $saved = @file_put_contents($dir . '/fiscal_year_config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;

    // อัปเดตตาราง fiscal_years ใน MySQL ถ้ามี
    $db = Database::getConnection();
    if ($db) {
        try {
            $activeYear = (int)($config['active_year'] ?? 2568);
            $db->prepare("UPDATE fiscal_years SET is_active = 0 WHERE school_id = ?")->execute([$schoolId]);
            $stmt = $db->prepare("INSERT INTO fiscal_years (school_id, year, is_active, start_date, end_date) 
                                  VALUES (?, ?, 1, ?, ?) 
                                  ON DUPLICATE KEY UPDATE is_active = 1");
            $stmt->execute([
                $schoolId, 
                $activeYear, 
                ($activeYear - 544) . '-10-01', 
                ($activeYear - 543) . '-09-30'
            ]);
        } catch (Exception $e) {
            // ignore
        }
    }

    if ($syncRevenues) {
        syncRevenuesFromStudentsAndRates($schoolId);
    }

    return $saved;
}

/**
 * ซิงค์ยอดประมาณการรายรับสถานศึกษาจากจำนวนนักเรียนและอัตราเงินอุดหนุนปีงบประมาณโดยอัตโนมัติ
 */
function syncRevenuesFromStudentsAndRates(int $schoolId): array {
    $rates = getFiscalYearRates($schoolId);
    $students = getStudentsData($schoolId);
    $totalStudents = array_sum(array_column($students, 'total_count'));

    // คำนวณจำนวนนักเรียนแยกช่วงชั้น
    $kCount = 0;
    $pCount = 0;
    $sCount = 0;
    $uCount = 0;
    foreach ($students as $s) {
        $stg = $s['stage'] ?? 'ประถม';
        $cnt = (int)($s['total_count'] ?? 0);
        if ($stg === 'อนุบาล') $kCount += $cnt;
        elseif ($stg === 'มัธยมต้น') $sCount += $cnt;
        elseif ($stg === 'มัธยมปลาย') $uCount += $cnt;
        else $pCount += $cnt;
    }

    $kRate = (float)($rates['kindergarten'] ?? 1854);
    $pRate = (float)($rates['primary'] ?? 2122);
    $sRate = (float)($rates['secondary_lower'] ?? 3608);
    $uRate = (float)($rates['secondary_upper'] ?? 4018);

    // ยอดเงินอุดหนุนรายหัวรวม
    $generalSubsidy = ($kCount * $kRate) + ($pCount * $pRate) + ($sCount * $sRate) + ($uCount * $uRate);
    $avgRatePerHead = $totalStudents > 0 ? round($generalSubsidy / $totalStudents) : $pRate;

    // รายรับเดิมของโรงเรียน (ถ้ามี เพื่อรักษาหมวด 7, 9, 10, 11)
    $existing = getRevenuesData($schoolId);
    $existingMap = [];
    foreach ($existing as $ex) {
        $existingMap[$ex['id']] = $ex;
    }

    $lunchPerDay = (float)($rates['lunch_per_day'] ?? 24);
    $lunchDays = (int)($rates['lunch_days'] ?? 200);
    $lunchRatePerHead = $lunchPerDay * $lunchDays;
    $lunchStudents = $kCount + $pCount; // สถิติจัดสรรเฉพาะอนุบาลและประถม
    $lunchTotal = $lunchStudents * $lunchRatePerHead;

    $poorCount = isset($existingMap[7]) ? (int)$existingMap[7]['eligible_count'] : round($totalStudents * 0.45);
    $poorRate = (float)($rates['poor_fund'] ?? 1500);

    $newRevenues = [
        [
            'id' => 1,
            'category' => 'subsidy',
            'item_name' => '1. เงินอุดหนุนรายหัว (การจัดการศึกษาขั้นพื้นฐาน)',
            'rate_per_head' => $avgRatePerHead,
            'eligible_count' => $totalStudents,
            'calculated_amount' => $generalSubsidy,
            'note' => "คำนวณตามเกณฑ์ปีงบประมาณ: อ. {$kCount} คน (@{$kRate}) + ป. {$pCount} คน (@{$pRate})" . ($sCount > 0 ? " + ม.ต้น {$sCount} คน (@{$sRate})" : '')
        ],
        [
            'id' => 2,
            'category' => 'subsidy',
            'item_name' => '2. เงินอุดหนุนรายหัวส่วนเพิ่ม (Top Up) โรงเรียนคุณภาพประจำตำบล',
            'rate_per_head' => (float)($rates['topup'] ?? 500),
            'eligible_count' => $totalStudents,
            'calculated_amount' => $totalStudents * (float)($rates['topup'] ?? 500),
            'note' => 'สนับสนุนพัฒนาคุณภาพการศึกษา สพฐ. ตามเป้าหมายนักเรียนรวม'
        ],
        [
            'id' => 3,
            'category' => 'welfare',
            'item_name' => '3. ค่าหนังสือเรียน (โครงการเรียนฟรี 15 ปี)',
            'rate_per_head' => (float)($rates['textbook'] ?? 650),
            'eligible_count' => $totalStudents,
            'calculated_amount' => $totalStudents * (float)($rates['textbook'] ?? 650),
            'note' => 'จัดสรรตามเกณฑ์ระดับการศึกษา สพฐ.'
        ],
        [
            'id' => 4,
            'category' => 'welfare',
            'item_name' => '4. ค่าเครื่องแบบนักเรียน (2 ชุด/คน/ปี)',
            'rate_per_head' => (float)($rates['uniform'] ?? 400),
            'eligible_count' => $totalStudents,
            'calculated_amount' => $totalStudents * (float)($rates['uniform'] ?? 400),
            'note' => 'อัตราเฉลี่ยเครื่องแบบนักเรียนตามเกณฑ์ปีงบประมาณ'
        ],
        [
            'id' => 5,
            'category' => 'welfare',
            'item_name' => '5. ค่าอุปกรณ์การเรียน (สมุด ดินสอ ยางลบ สี ไม้บรรทัด)',
            'rate_per_head' => (float)($rates['stationery'] ?? 440),
            'eligible_count' => $totalStudents,
            'calculated_amount' => $totalStudents * (float)($rates['stationery'] ?? 440),
            'note' => 'จัดสรร 2 ภาคเรียน/ปีการศึกษา'
        ],
        [
            'id' => 6,
            'category' => 'activity',
            'item_name' => '6. ค่ากิจกรรมพัฒนาผู้เรียน (4 กิจกรรมหลัก สพฐ.)',
            'rate_per_head' => (float)($rates['activity'] ?? 460),
            'eligible_count' => $totalStudents,
            'calculated_amount' => $totalStudents * (float)($rates['activity'] ?? 460),
            'note' => 'วิชาการ, คุณธรรม, ทัศนศึกษา, เทคโนโลยี ICT'
        ],
        [
            'id' => 7,
            'category' => 'welfare',
            'item_name' => '7. เงินปัจจัยพื้นฐานนักเรียนยากจน (กสศ. / สพฐ.)',
            'rate_per_head' => $poorRate,
            'eligible_count' => $poorCount,
            'calculated_amount' => $poorCount * $poorRate,
            'note' => $existingMap[7]['note'] ?? "จำนวนนักเรียนที่ผ่านเกณฑ์คัดกรอง {$poorCount} คน"
        ],
        [
            'id' => 8,
            'category' => 'lunch',
            'item_name' => '8. ค่าอาหารกลางวัน (อปท. จัดสรรผ่าน อบต./เทศบาล)',
            'rate_per_head' => $lunchRatePerHead,
            'eligible_count' => $lunchStudents,
            'calculated_amount' => $lunchTotal,
            'note' => "อัตรา {$lunchPerDay} บ./วัน จำนวน {$lunchDays} วันทำการ (เฉพาะ อ.1-3 และ ป.1-6)"
        ],
        [
            'id' => 9,
            'category' => 'fundraising',
            'item_name' => '9. เงินระดมทรัพยากร / เงินบริจาค / ผ้าป่าเพื่อการศึกษา',
            'rate_per_head' => 0,
            'eligible_count' => 1,
            'calculated_amount' => isset($existingMap[9]) ? (float)$existingMap[9]['calculated_amount'] : 185000,
            'note' => $existingMap[9]['note'] ?? 'ศิษย์เก่าและคณะกรรมการสถานศึกษาจัดทอดผ้าป่า'
        ],
        [
            'id' => 10,
            'category' => 'revenue',
            'item_name' => '10. เงินรายได้สถานศึกษา (ค่าเช่าร้านค้าสหกรณ์, ดอกเบี้ย)',
            'rate_per_head' => 0,
            'eligible_count' => 1,
            'calculated_amount' => isset($existingMap[10]) ? (float)$existingMap[10]['calculated_amount'] : 64000,
            'note' => $existingMap[10]['note'] ?? 'ดอกเบี้ยเงินฝากธนาคาร และเงินบำรุงสหกรณ์'
        ],
        [
            'id' => 11,
            'category' => 'other',
            'item_name' => '11. รายรับอื่น ๆ (เงินอุดหนุนเฉพาะกิจ/โครงการพิเศษ)',
            'rate_per_head' => 0,
            'eligible_count' => 1,
            'calculated_amount' => isset($existingMap[11]) ? (float)$existingMap[11]['calculated_amount'] : 50000,
            'note' => $existingMap[11]['note'] ?? 'เงินสนับสนุนจาก อบจ. โครงการส่งเสริมดนตรีพื้นบ้าน'
        ],
    ];

    saveRevenuesData($schoolId, $newRevenues);
    return $newRevenues;
}

/**
 * ดึงข้อมูลปีงบประมาณปัจจุบัน
 */
function getFiscalYearData(?int $schoolId = null): array {
    $config = getFiscalYearConfig($schoolId);
    $activeYear = (int)($config['active_year'] ?? 2568);
    $students = getStudentsData($schoolId);
    $totalStudents = array_sum(array_column($students, 'total_count'));

    return [
        'id' => 1,
        'year' => $activeYear,
        'is_active' => 1,
        'start_date' => ($activeYear - 544) . '-10-01',
        'end_date' => ($activeYear - 543) . '-09-30',
        'total_students' => $totalStudents,
        'teacher_count' => 22,
        'rates' => $config['rates'] ?? getDefaultSubsidyRates($activeYear),
        'proposal_window' => $config['proposal_window'] ?? [],
    ];
}

/**
 * ดึงข้อมูลนักเรียน (แยกตามโรงเรียน)
 */
function getStudentsData(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/students.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM students WHERE school_id = ? ORDER BY id ASC");
            $stmt->execute([$schoolId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) return $rows;
        } catch (Exception $e) {
            // fallback
        }
    }

    return [
        ['id' => 1, 'school_id' => $schoolId, 'grade_level' => 'อนุบาล 1', 'stage' => 'อนุบาล', 'male_count' => 12, 'female_count' => 14, 'total_count' => 26],
        ['id' => 2, 'school_id' => $schoolId, 'grade_level' => 'อนุบาล 2', 'stage' => 'อนุบาล', 'male_count' => 15, 'female_count' => 16, 'total_count' => 31],
        ['id' => 3, 'school_id' => $schoolId, 'grade_level' => 'อนุบาล 3', 'stage' => 'อนุบาล', 'male_count' => 14, 'female_count' => 15, 'total_count' => 29],
        ['id' => 4, 'school_id' => $schoolId, 'grade_level' => 'ประถมศึกษาปีที่ 1', 'stage' => 'ประถม', 'male_count' => 20, 'female_count' => 18, 'total_count' => 38],
        ['id' => 5, 'school_id' => $schoolId, 'grade_level' => 'ประถมศึกษาปีที่ 2', 'stage' => 'ประถม', 'male_count' => 19, 'female_count' => 17, 'total_count' => 36],
        ['id' => 6, 'school_id' => $schoolId, 'grade_level' => 'ประถมศึกษาปีที่ 3', 'stage' => 'ประถม', 'male_count' => 21, 'female_count' => 19, 'total_count' => 40],
        ['id' => 7, 'school_id' => $schoolId, 'grade_level' => 'ประถมศึกษาปีที่ 4', 'stage' => 'ประถม', 'male_count' => 18, 'female_count' => 20, 'total_count' => 38],
        ['id' => 8, 'school_id' => $schoolId, 'grade_level' => 'ประถมศึกษาปีที่ 5', 'stage' => 'ประถม', 'male_count' => 20, 'female_count' => 18, 'total_count' => 38],
        ['id' => 9, 'school_id' => $schoolId, 'grade_level' => 'ประถมศึกษาปีที่ 6', 'stage' => 'ประถม', 'male_count' => 19, 'female_count' => 17, 'total_count' => 36],
    ];
}

/**
 * บันทึกข้อมูลนักเรียน (แยกตามโรงเรียนและอัปเดตยอดรวมโรงเรียน)
 */
function saveStudentsData(int $schoolId, array $students): bool {
    $dir = getSchoolDataDir($schoolId);
    $saved = @file_put_contents($dir . '/students.json', json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;

    // คำนวณยอดรวมนักเรียนทั้งหมด
    $totalStudents = array_sum(array_column($students, 'total_count'));

    // 1. อัปเดต student_count ใน school_info.json
    $schoolInfoFile = $dir . '/school_info.json';
    if (file_exists($schoolInfoFile)) {
        $info = json_decode(@file_get_contents($schoolInfoFile), true);
        if (is_array($info)) {
            $info['student_count'] = $totalStudents;
            @file_put_contents($schoolInfoFile, json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    // 2. อัปเดต Session
    if (!empty($_SESSION['school']) && (int)($_SESSION['school']['id'] ?? 0) === $schoolId) {
        $_SESSION['school']['student_count'] = $totalStudents;
    }

    // 3. อัปเดต schools_data.json
    $allSchools = getAllSchoolsList();
    foreach ($allSchools as &$s) {
        if ((int)$s['id'] === $schoolId) {
            $s['student_count'] = $totalStudents;
            $s['studentCount'] = $totalStudents;
            break;
        }
    }
    $allSchoolsFile = __DIR__ . '/../config/schools_data.json';
    if (file_exists($allSchoolsFile)) {
        @file_put_contents($allSchoolsFile, json_encode($allSchools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // 4. บันทึกลง MySQL ถ้าต่อเชื่อมอยู่
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmtDel = $db->prepare("DELETE FROM students WHERE school_id = ?");
            $stmtDel->execute([$schoolId]);
            $stmtIns = $db->prepare("INSERT INTO students (id, school_id, fiscal_year_id, grade_level, stage, male_count, female_count, total_count) VALUES (?, ?, 1, ?, ?, ?, ?, ?)");
            foreach ($students as $idx => $st) {
                $id = !empty($st['id']) ? (int)$st['id'] : ($idx + 1);
                $stmtIns->execute([
                    $id,
                    $schoolId,
                    $st['grade_level'] ?? '',
                    $st['stage'] ?? 'ประถม',
                    (int)($st['male_count'] ?? 0),
                    (int)($st['female_count'] ?? 0),
                    (int)($st['total_count'] ?? 0),
                ]);
            }
            // อัปเดต student_count ในตาราง schools
            $stmtUpd = $db->prepare("UPDATE schools SET student_count = ? WHERE id = ?");
            $stmtUpd->execute([$totalStudents, $schoolId]);
        } catch (Exception $e) {
            // ignore
        }
    }

    return $saved;
}

/**
 * ดึงข้อมูลรายรับ (ตามโรงเรียน)
 */
function getRevenuesData(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/revenues.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM revenues WHERE school_id = ? ORDER BY id ASC");
            $stmt->execute([$schoolId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) return $rows;
        } catch (Exception $e) {
            // fallback
        }
    }

    return [
        ['id' => 1, 'category' => 'subsidy', 'item_name' => '1. เงินอุดหนุนรายหัว (การจัดการศึกษาขั้นพื้นฐาน)', 'rate_per_head' => 1980, 'eligible_count' => 312, 'calculated_amount' => 617760, 'note' => 'เฉลี่ยรวม อ.1-3 และ ป.1-6'],
        ['id' => 2, 'category' => 'subsidy', 'item_name' => '2. เงินอุดหนุนรายหัวส่วนเพิ่ม (Top Up) โรงเรียนคุณภาพประจำตำบล', 'rate_per_head' => 500, 'eligible_count' => 312, 'calculated_amount' => 156000, 'note' => 'สนับสนุนพัฒนาคุณภาพการศึกษา สพฐ.'],
        ['id' => 3, 'category' => 'welfare', 'item_name' => '3. ค่าหนังสือเรียน (โครงการเรียนฟรี 15 ปี)', 'rate_per_head' => 650, 'eligible_count' => 312, 'calculated_amount' => 202800, 'note' => 'จัดสรรตามเกณฑ์ระดับการศึกษา สพฐ.'],
        ['id' => 4, 'category' => 'welfare', 'item_name' => '4. ค่าเครื่องแบบนักเรียน (2 ชุด/คน/ปี)', 'rate_per_head' => 380, 'eligible_count' => 312, 'calculated_amount' => 118560, 'note' => 'อนุบาล 325 บ., ประถม 400 บ.'],
        ['id' => 5, 'category' => 'welfare', 'item_name' => '5. ค่าอุปกรณ์การเรียน (สมุด ดินสอ ยางลบ สี ไม้บรรทัด)', 'rate_per_head' => 400, 'eligible_count' => 312, 'calculated_amount' => 124800, 'note' => 'อนุบาล 290 บ./ปี, ประถม 440 บ./ปี'],
        ['id' => 6, 'category' => 'activity', 'item_name' => '6. ค่ากิจกรรมพัฒนาผู้เรียน (4 กิจกรรมหลัก สพฐ.)', 'rate_per_head' => 460, 'eligible_count' => 312, 'calculated_amount' => 143520, 'note' => 'วิชาการ, คุณธรรม, ทัศนศึกษา, เทคโนโลยี ICT'],
        ['id' => 7, 'category' => 'welfare', 'item_name' => '7. เงินปัจจัยพื้นฐานนักเรียนยากจน (กสศ. / สพฐ.)', 'rate_per_head' => 1500, 'eligible_count' => 145, 'calculated_amount' => 217500, 'note' => 'จำนวนนักเรียนที่ผ่านเกณฑ์คัดกรอง 145 คน'],
        ['id' => 8, 'category' => 'lunch', 'item_name' => '8. ค่าอาหารกลางวัน (อปท. จัดสรรผ่าน อบต./เทศบาล)', 'rate_per_head' => 4800, 'eligible_count' => 312, 'calculated_amount' => 1497600, 'note' => 'อัตรา 24 บ./วัน จำนวน 200 วันทำการ'],
        ['id' => 9, 'category' => 'fundraising', 'item_name' => '9. เงินระดมทรัพยากร / เงินบริจาค / ผ้าป่าเพื่อการศึกษา', 'rate_per_head' => 0, 'eligible_count' => 1, 'calculated_amount' => 185000, 'note' => 'ศิษย์เก่าและคณะกรรมการสถานศึกษาจัดทอดผ้าป่า'],
        ['id' => 10, 'category' => 'revenue', 'item_name' => '10. เงินรายได้สถานศึกษา (ค่าเช่าร้านค้าสหกรณ์, ดอกเบี้ย)', 'rate_per_head' => 0, 'eligible_count' => 1, 'calculated_amount' => 64000, 'note' => 'ดอกเบี้ยเงินฝากธนาคาร และเงินบำรุงสหกรณ์'],
        ['id' => 11, 'category' => 'other', 'item_name' => '11. รายรับอื่น ๆ (เงินอุดหนุนเฉพาะกิจ/โครงการพิเศษ)', 'rate_per_head' => 0, 'eligible_count' => 1, 'calculated_amount' => 50000, 'note' => 'เงินสนับสนุนจาก อบจ. โครงการส่งเสริมดนตรีพื้นบ้าน'],
    ];
}

/**
 * บันทึกข้อมูลรายรับ
 */
function saveRevenuesData(int $schoolId, array $revenues): bool {
    $dir = getSchoolDataDir($schoolId);
    @file_put_contents($dir . '/revenues.json', json_encode($revenues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * ดึงข้อมูลการจัดสรรงบประมาณตามฝ่าย
 */
function getBudgetAllocations(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/budget_allocations.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM budget_allocations WHERE school_id = ? ORDER BY id ASC");
            $stmt->execute([$schoolId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) return $rows;
        } catch (Exception $e) {
            // fallback
        }
    }

    return [
        ['id' => 1, 'department_name' => 'ฝ่ายบริหารงานวิชาการ', 'percentage' => 60.0, 'allocated_amount' => 1050000, 'spent_amount' => 432500, 'remaining_amount' => 617500, 'color_hex' => '#2563eb', 'description' => 'พัฒนาหลักสูตร การจัดการเรียนการสอน สื่อ นวัตกรรม'],
        ['id' => 2, 'department_name' => 'ฝ่ายบริหารงานงบประมาณ', 'percentage' => 5.0, 'allocated_amount' => 87500, 'spent_amount' => 35000, 'remaining_amount' => 52500, 'color_hex' => '#0284c7', 'description' => 'การเงิน บัญชี พัสดุ สินทรัพย์ และแผนงานงบประมาณ'],
        ['id' => 3, 'department_name' => 'ฝ่ายบริหารงานบุคคล', 'percentage' => 12.0, 'allocated_amount' => 210000, 'spent_amount' => 78000, 'remaining_amount' => 132000, 'color_hex' => '#059669', 'description' => 'พัฒนาครู วินัย สวัสดิการ ทัศนศึกษาดูงาน และสรรหาบุคลากร'],
        ['id' => 4, 'department_name' => 'ฝ่ายบริหารงานทั่วไป', 'percentage' => 8.0, 'allocated_amount' => 140000, 'spent_amount' => 65400, 'remaining_amount' => 74600, 'color_hex' => '#d97706', 'description' => 'อาคารสถานที่ สิ่งแวดล้อม ประชาสัมพันธ์ และชุมชนสัมพันธ์'],
        ['id' => 5, 'department_name' => 'งบกลาง / สำรองจ่ายฉุกเฉิน', 'percentage' => 15.0, 'allocated_amount' => 262500, 'spent_amount' => 42000, 'remaining_amount' => 220500, 'color_hex' => '#7c3aed', 'description' => 'กรณีภัยพิบัติ ซ่อมแซมฉุกเฉิน และกิจกรรมที่มิได้คาดหมายล่วงหน้า'],
    ];
}

/**
 * บันทึกการจัดสรรงบประมาณ
 */
function saveBudgetAllocations(int $schoolId, array $allocations): bool {
    $dir = getSchoolDataDir($schoolId);
    @file_put_contents($dir . '/budget_allocations.json', json_encode($allocations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * ดึงข้อมูลโครงการ (ตามโรงเรียน)
 */
function getProjectsData(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/projects.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data)) {
            return $data;
        }
    }

    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM projects WHERE school_id = ? ORDER BY id DESC");
            $stmt->execute([$schoolId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) return $rows;
        } catch (Exception $e) {
            // fallback
        }
    }

    // ข้อมูลเริ่มต้นสำหรับโรงเรียนที่ 1
    if ($schoolId === 1) {
        return [
            [
                'id' => 1,
                'project_code' => 'กค.01/2568',
                'project_name' => 'โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)',
                'department' => 'ฝ่ายบริหารงานวิชาการ',
                'responsible_person' => 'นางสาวกนกพร ใจมั่น',
                'allocated_budget' => 45000,
                'spent_budget' => 35000,
                'remaining_budget' => 10000,
                'status' => 'in_progress',
                'approval_status' => 'approved',
                'duration' => 'พ.ย. 2567 - ก.พ. 2568',
                'rationale' => 'เพื่อพัฒนายกระดับผลคะแนนการทดสอบ O-NET และ NT ของนักเรียนชั้น ป.3 และ ป.6 ให้สูงกว่าค่าเฉลี่ยระดับประเทศ',
            ],
            [
                'id' => 2,
                'project_code' => 'กค.02/2568',
                'project_name' => 'โครงการพัฒนาทักษะดิจิทัลและการรู้เท่าทันปัญญาประดิษฐ์ (AI Literacy) เพื่อการเรียนรู้ในศตวรรษที่ 21',
                'department' => 'ฝ่ายบริหารงานวิชาการ',
                'responsible_person' => 'นายพิเชษฐ์ ปัญญาวงศ์',
                'allocated_budget' => 40000,
                'spent_budget' => 28000,
                'remaining_budget' => 12000,
                'status' => 'in_progress',
                'approval_status' => 'approved',
                'duration' => 'ตลอดปีการศึกษา 2568',
                'rationale' => 'ส่งเสริมให้นักเรียนและครูสามารถใช้เครื่องมือ AI และเทคโนโลยีดิจิทัลในการสืบค้น การเรียนรู้ และการสร้างสรรค์ผลงานอย่างมีจริยธรรม',
            ],
            [
                'id' => 3,
                'project_code' => 'กค.03/2568',
                'project_name' => 'โครงการส่งเสริมคุณธรรม จริยธรรม และวิถีประชาธิปไตยในสถานศึกษา (โรงเรียนสุจริต)',
                'department' => 'ฝ่ายบริหารงานบุคคล',
                'responsible_person' => 'นายสมชาย วงศ์สว่าง',
                'allocated_budget' => 25000,
                'spent_budget' => 12000,
                'remaining_budget' => 13000,
                'status' => 'in_progress',
                'approval_status' => 'approved',
                'duration' => 'ตลอดปีการศึกษา 2568',
                'rationale' => 'ปลูกฝังความซื่อสัตย์สุจริต วินัย และความเป็นพลเมืองดีตามวิถีประชาธิปไตย',
            ],
            [
                'id' => 4,
                'project_code' => 'กค.04/2568',
                'project_name' => 'โครงการปรับปรุงซ่อมแซมอาคารสถานที่และพัฒนาสิ่งแวดล้อมเพื่อความปลอดภัย (Safety School)',
                'department' => 'ฝ่ายบริหารงานทั่วไป',
                'responsible_person' => 'นายอำนวย สุขเกษม',
                'allocated_budget' => 50000,
                'spent_budget' => 50000,
                'remaining_budget' => 0,
                'status' => 'completed',
                'approval_status' => 'approved',
                'duration' => 'ต.ค. 2567 - ธ.ค. 2567',
                'rationale' => 'เพื่อปรับปรุงจุดเสี่ยง ซ่อมแซมระบบไฟฟ้า ห้องน้ำ และทาสีอาคารเรียนให้มีความปลอดภัยและเอื้อต่อการเรียนรู้',
            ],
            [
                'id' => 5,
                'project_code' => 'กค.05/2568',
                'project_name' => 'โครงการพัฒนาศักยภาพครูสู่การจัดการเรียนรู้เชิงรุก (Active Learning)',
                'department' => 'ฝ่ายบริหารงานบุคคล',
                'responsible_person' => 'นางสาวกนกพร ใจมั่น',
                'allocated_budget' => 30000,
                'spent_budget' => 0,
                'remaining_budget' => 30000,
                'status' => 'not_started',
                'approval_status' => 'pending',
                'duration' => 'มี.ค. 2568 - พ.ค. 2568',
                'rationale' => 'อบรมเชิงปฏิบัติการพัฒนาครูด้านการจัดกิจกรรมการเรียนรู้แบบ Active Learning และการวัดผลประเมินผลตามสภาพจริง',
            ]
        ];
    }

    // สำหรับโรงเรียนใหม่ที่สร้างขึ้น
    return [];
}

/**
 * บันทึกหรือเพิ่มโครงการ
 */
function saveProject(int $schoolId, array $project): int {
    $projects = getProjectsData($schoolId);
    $id = isset($project['id']) ? (int)$project['id'] : 0;
    
    if ($id > 0) {
        $updated = false;
        foreach ($projects as $idx => $p) {
            if ((int)$p['id'] === $id) {
                $projects[$idx] = array_merge($p, $project);
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $projects[] = $project;
        }
    } else {
        $maxId = 0;
        foreach ($projects as $p) {
            if ((int)$p['id'] > $maxId) $maxId = (int)$p['id'];
        }
        $id = $maxId + 1;
        $project['id'] = $id;
        $projects[] = $project;
    }

    $dir = getSchoolDataDir($schoolId);
    @file_put_contents($dir . '/projects.json', json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $id;
}

/**
 * ลบโครงการ
 */
function deleteProject(int $schoolId, int $projectId): bool {
    $projects = getProjectsData($schoolId);
    $filtered = array_values(array_filter($projects, fn($p) => (int)$p['id'] !== $projectId));
    $dir = getSchoolDataDir($schoolId);
    return @file_put_contents($dir . '/projects.json', json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * ดึงข้อมูลการเบิกจ่าย
 */
function getDisbursementsData(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/disbursements.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data)) {
            return $data;
        }
    }

    if ($schoolId === 1) {
        return [
            [
                'id' => 1,
                'doc_no' => 'ขจ.001/2568',
                'date' => '2024-11-15',
                'project_id' => 1,
                'project_name' => 'โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)',
                'payee' => 'นายสมควร สอนดี (วิทยากร)',
                'category' => 'ค่าตอบแทน',
                'amount' => 9000.00,
                'status' => 'paid',
                'note' => 'ค่าสมนาคุณวิทยากรติวเข้มรอบที่ 1'
            ],
            [
                'id' => 2,
                'doc_no' => 'ขจ.002/2568',
                'date' => '2024-11-20',
                'project_id' => 1,
                'project_name' => 'โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)',
                'payee' => 'ร้านครัวคุณแม่',
                'category' => 'ค่าใช้สอย',
                'amount' => 11400.00,
                'status' => 'paid',
                'note' => 'ค่าอาหารกลางวันและอาหารว่างนักเรียนเข้าค่าย'
            ],
            [
                'id' => 3,
                'doc_no' => 'ขจ.003/2568',
                'date' => '2024-12-05',
                'project_id' => 4,
                'project_name' => 'โครงการปรับปรุงซ่อมแซมอาคารสถานที่และพัฒนาสิ่งแวดล้อมเพื่อความปลอดภัย (Safety School)',
                'payee' => 'หจก.ขอนแก่นการช่าง',
                'category' => 'ค่าวัสดุ',
                'amount' => 50000.00,
                'status' => 'paid',
                'note' => 'ค่าวัสดุปรับปรุงซ่อมแซมระบบไฟฟ้าและสีอาคาร'
            ],
        ];
    }

    return [];
}

/**
 * บันทึกการเบิกจ่าย
 */
function saveDisbursement(int $schoolId, array $disbursement): int {
    $disbursements = getDisbursementsData($schoolId);
    $id = isset($disbursement['id']) ? (int)$disbursement['id'] : 0;

    if ($id > 0) {
        $updated = false;
        foreach ($disbursements as $idx => $d) {
            if ((int)$d['id'] === $id) {
                $disbursements[$idx] = array_merge($d, $disbursement);
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $disbursements[] = $disbursement;
        }
    } else {
        $maxId = 0;
        foreach ($disbursements as $d) {
            if ((int)$d['id'] > $maxId) $maxId = (int)$d['id'];
        }
        $id = $maxId + 1;
        $disbursement['id'] = $id;
        $disbursements[] = $disbursement;
    }

    $dir = getSchoolDataDir($schoolId);
    @file_put_contents($dir . '/disbursements.json', json_encode($disbursements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $id;
}

/**
 * ลบการเบิกจ่าย
 */
function deleteDisbursement(int $schoolId, int $disbursementId): bool {
    $disbursements = getDisbursementsData($schoolId);
    $filtered = array_values(array_filter($disbursements, fn($d) => (int)$d['id'] !== $disbursementId));
    $dir = getSchoolDataDir($schoolId);
    return @file_put_contents($dir . '/disbursements.json', json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * บันทึกรายการเบิกจ่ายทั้งหมดเป็นชุด
 */
function saveDisbursementsData(int $schoolId, array $disbursements): bool {
    $dir = getSchoolDataDir($schoolId);
    return @file_put_contents($dir . '/disbursements.json', json_encode($disbursements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * ดึงข้อมูลกิจกรรมพัฒนาผู้เรียน 4 กิจกรรมหลัก
 */
function getLearnerActivitiesData(?int $schoolId = null): array {
    if ($schoolId === null && !empty($_SESSION['school_id'])) {
        $schoolId = (int)$_SESSION['school_id'];
    }
    $schoolId = $schoolId ?: 1;

    $dir = getSchoolDataDir($schoolId);
    $customFile = $dir . '/learner_activities.json';
    if (file_exists($customFile)) {
        $data = json_decode(@file_get_contents($customFile), true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    $students = getStudentsData($schoolId);
    $totalStudents = array_sum(array_column($students, 'total_count'));

    return [
        [
            'id' => 1,
            'name' => '1. กิจกรรมวิชาการ (ค่ายวิชาการ / ติวเข้ม / แข่งขันความสามารถ)',
            'percentage' => 30.0,
            'rate_per_head' => 138.0,
            'description' => 'ส่งเสริมความเป็นเลิศทางวิชาการ ค่ายภาษาไทย ภาษาอังกฤษ วิทยาศาสตร์และคณิตศาสตร์',
            'allocated' => round($totalStudents * 460 * 0.30)
        ],
        [
            'id' => 2,
            'name' => '2. กิจกรรมคุณธรรม จริยธรรม / ลูกเสือ เนตรนารี / ยุวกาชาด',
            'percentage' => 25.0,
            'rate_per_head' => 115.0,
            'description' => 'การเข้าค่ายพักแรมลูกเสือ-เนตรนารี กิจกรรมค่ายคุณธรรม และปฏิบัติธรรมวันสำคัญ',
            'allocated' => round($totalStudents * 460 * 0.25)
        ],
        [
            'id' => 3,
            'name' => '3. กิจกรรมทัศนศึกษาตามแหล่งเรียนรู้ (1 ครั้ง/ปีการศึกษา)',
            'percentage' => 30.0,
            'rate_per_head' => 138.0,
            'description' => 'ยานพาหนะ ค่าผ่านทาง ค่าประกันอุบัติเหตุ และค่าเข้าชมพิพิธภัณฑ์/แหล่งเรียนรู้',
            'allocated' => round($totalStudents * 460 * 0.30)
        ],
        [
            'id' => 4,
            'name' => '4. กิจกรรมการจัดการเรียนรู้เทคโนโลยีสารสนเทศ (ICT / AI Literacy)',
            'percentage' => 15.0,
            'rate_per_head' => 69.0,
            'description' => 'การพัฒนาทักษะคอมพิวเตอร์ การเขียนโปรแกรม Coding และทักษะดิจิทัลในศตวรรษที่ 21',
            'allocated' => round($totalStudents * 460 * 0.15)
        ],
    ];
}

/**
 * บันทึกข้อมูลกิจกรรมพัฒนาผู้เรียน 4 กิจกรรมหลัก
 */
function saveLearnerActivitiesData(int $schoolId, array $activities): bool {
    $dir = getSchoolDataDir($schoolId);
    return @file_put_contents($dir . '/learner_activities.json', json_encode($activities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * ดึงรายการจำแนกงบประมาณ 4 หมวดของโครงการ
 */
function getProjectExpensesData(int $schoolId, int $projectId): array {
    $dir = getSchoolDataDir($schoolId);
    $file = $dir . '/project_expenses_' . $projectId . '.json';
    if (file_exists($file)) {
        $data = json_decode(@file_get_contents($file), true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    // Default template items
    return [
        ['category' => 'ค่าตอบแทน', 'item' => 'ค่าสมนาคุณวิทยากรบรรยายและฝึกปฏิบัติการ', 'qty' => 15, 'unit' => 'ชั่วโมง', 'price' => 600, 'total' => 9000],
        ['category' => 'ค่าใช้สอย', 'item' => 'ค่าอาหารกลางวันและอาหารว่างสำหรับผู้เข้าร่วมกิจกรรม', 'qty' => 76, 'unit' => 'คน', 'price' => 150, 'total' => 11400],
        ['category' => 'ค่าวัสดุ', 'item' => 'ค่าวัสดุ อุปกรณ์ เอกสารประกอบการฝึกอบรม และข้อสอบ', 'qty' => 76, 'unit' => 'ชุด', 'price' => 323.68, 'total' => 24600],
    ];
}

/**
 * บันทึกรายการจำแนกงบประมาณ 4 หมวดของโครงการ
 */
function saveProjectExpensesData(int $schoolId, int $projectId, array $items): bool {
    $dir = getSchoolDataDir($schoolId);
    $file = $dir . '/project_expenses_' . $projectId . '.json';
    return @file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}




