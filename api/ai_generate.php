<?php
/**
 * API Endpoint: AI Project Proposal Generator (PHP cURL to Gemini API)
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$projectName = trim($input['project_name'] ?? '');
$department = trim($input['department'] ?? 'ฝ่ายบริหารงานวิชาการ');
$responsible = trim($input['responsible_person'] ?? 'นายพิเชษฐ์ ปัญญาวงศ์');
$budget = floatval($input['budget'] ?? 45000);
$schoolLevel = trim($input['school_level'] ?? 'ประถมศึกษา');
$targetAudience = trim($input['target_audience'] ?? 'นักเรียนและครู');
$keyObjectives = trim($input['key_objectives'] ?? '');
$apiKey = trim($input['api_key'] ?? '') ?: (getenv('GEMINI_API_KEY') ?: ($_SESSION['gemini_api_key'] ?? ''));

if (empty($projectName)) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุชื่อโครงการ']);
    exit;
}

// If user supplied an API key, save in session for future use
if (!empty($input['api_key'])) {
    $_SESSION['gemini_api_key'] = $input['api_key'];
}

$generated = null;

// If we have an API Key, call Gemini API via cURL
if (!empty($apiKey)) {
    $prompt = "คุณคือผู้เชี่ยวชาญการเขียนข้อเสนอโครงการตามระเบียบและแบบฟอร์มมาตรฐานของสำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.) กระทรวงศึกษาธิการ 
กรุณาร่างเอกสารข้อเสนอโครงการฉบับสมบูรณ์สำหรับโรงเรียนระดับ {$schoolLevel}
โดยมีข้อมูลเบื้องต้นดังนี้:
- ชื่อโครงการ: {$projectName}
- ฝ่ายที่รับผิดชอบ: {$department}
- ผู้รับผิดชอบโครงการ: {$responsible}
- งบประมาณรวมที่ขอจัดสรร: " . number_format($budget, 2) . " บาท
- กลุ่มเป้าหมาย: {$targetAudience}
- วัตถุประสงค์/เป้าหมายเฉพาะ: {$keyObjectives}

กรุณาส่งกลับเป็น JSON ที่มีโครงสร้างต่อไปนี้เท่านั้น (ห้ามใส่ Markdown code block หรือข้อความอื่นนอกเหนือจาก JSON):
{
  \"projectName\": \"{$projectName}\",
  \"projectType\": \"โครงการใหม่ / โครงการต่อเนื่อง\",
  \"alignment\": \"สอดคล้องกับยุทธศาสตร์สถานศึกษา ข้อที่ 1 และนโยบาย สพฐ. ด้านการยกระดับคุณภาพการศึกษา\",
  \"department\": \"{$department}\",
  \"responsiblePerson\": \"{$responsible}\",
  \"rationale\": \"หลักการและเหตุผลอย่างละเอียด 2-3 ย่อหน้า อ้างอิงนโยบาย สพฐ. พ.ร.บ.การศึกษาแห่งชาติ และสภาพปัญหาความจำเป็น\",
  \"objectives\": [\"ข้อ 1...\", \"ข้อ 2...\", \"ข้อ 3...\"],
  \"quantitativeTargets\": [\"นักเรียนจำนวน... คน ร้อยละ...\", \"ครูจำนวน... คน\"],
  \"qualitativeTargets\": [\"นักเรียนมีทักษะ... ในระดับดีขึ้นไป\", \"ผลสัมฤทธิ์ทางการเรียนเพิ่มขึ้น...\"],
  \"location\": \"โรงเรียนและห้องปฏิบัติการ\",
  \"duration\": \"ตลอดปีการศึกษา\",
  \"pdcaSchedule\": [
    {\"phase\": \"ขั้นวางแผน (Plan)\", \"activities\": \"ประชุมคณะทำงาน สำรวจความต้องการ กำหนดกรอบงบประมาณ\", \"period\": \"พฤษภาคม\", \"responsible\": \"{$responsible}\"},
    {\"phase\": \"ขั้นดำเนินการ (Do)\", \"activities\": \"จัดกิจกรรมอบรมเชิงปฏิบัติการ และกิจกรรมพัฒนาผู้เรียน\", \"period\": \"มิถุนายน - ธันวาคม\", \"responsible\": \"คณะทำงาน\"},
    {\"phase\": \"ขั้นตรวจสอบ (Check)\", \"activities\": \"นิเทศติดตามผล สังเกตพฤติกรรม และทดสอบประเมินผล\", \"period\": \"มกราคม\", \"responsible\": \"ฝ่ายวิชาการ\"},
    {\"phase\": \"ขั้นปรับปรุงพัฒนา (Act)\", \"activities\": \"สรุปรายงานผลโครงการ นำเสนอผู้บริหาร และถอดบทเรียน\", \"period\": \"กุมภาพันธ์\", \"responsible\": \"{$responsible}\"}
  ],
  \"budgetItems\": [
    {\"category\": \"ค่าตอบแทน\", \"item\": \"ค่าวิทยากรบรรยายและฝึกปฏิบัติการ\", \"quantity\": 12, \"unit\": \"ชั่วโมง\", \"unitPrice\": 600, \"total\": 7200},
    {\"category\": \"ค่าใช้สอย\", \"item\": \"ค่าอาหารกลางวันและเครื่องดื่มสำหรับผู้เข้าอบรม\", \"quantity\": 50, \"unit\": \"มื้อ\", \"unitPrice\": 80, \"total\": 4000},
    {\"category\": \"ค่าวัสดุ\", \"item\": \"ค่าวัสดุ อุปกรณ์ และเอกสารประกอบการจัดกิจกรรม\", \"quantity\": 1, \"unit\": \"ชุด\", \"unitPrice\": " . ($budget - 11200) . ", \"total\": " . ($budget - 11200) . "}
  ],
  \"indicators\": [\"ร้อยละ 85 ของนักเรียนผ่านเกณฑ์การประเมิน\", \"ร้อยละ 90 ของผู้เข้าร่วมกิจกรรมมีความพึงพอใจในระดับดีมาก\"],
  \"evaluationMethods\": [\"แบบทดสอบวัดความรู้\", \"แบบประเมินความพึงพอใจ\", \"การสังเกตพฤติกรรมการเรียนรู้\"],
  \"expectedOutcomes\": [\"นักเรียนมีผลสัมฤทธิ์ทางการเรียนและทักษะที่จำเป็นสูงขึ้น\", \"โรงเรียนมีแนวปฏิบัติที่ดี (Best Practice) ในการจัดการเรียนรู้\"]
}";

    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'responseMimeType' => 'application/json'
        ]
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($response)) {
        $result = json_decode($response, true);
        $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $rawText = trim(preg_replace('/^```(json)?|```$/m', '', $rawText));
        $parsed = json_decode($rawText, true);
        if ($parsed && !empty($parsed['projectName'])) {
            $generated = $parsed;
        }
    }
}

// Fallback generator (if no API key, or cURL is disabled / offline)
if (!$generated) {
    // Generate realistic OBEC items totaling exact budget
    $compPart = round($budget * 0.20);
    $servicePart = round($budget * 0.25);
    $materialPart = $budget - ($compPart + $servicePart);

    $generated = [
        'projectName' => $projectName,
        'projectType' => 'โครงการต่อเนื่องตามแผนปฏิบัติการประจำปี',
        'alignment' => 'สอดคล้องกับยุทธศาสตร์สถานศึกษา ด้านคุณภาพผู้เรียน และนโยบาย สพฐ. ข้อที่ 1 ยกระดับคุณภาพการศึกษา',
        'department' => $department,
        'responsiblePerson' => $responsible,
        'rationale' => "ตามที่สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.) ได้กำหนดนโยบายและจุดเน้นเพื่อพัฒนาคุณภาพการศึกษาขั้นพื้นฐาน ให้ผู้เรียนมีความรู้ ทักษะในศตวรรษที่ 21 และมีคุณลักษณะอันพึงประสงค์ โดยมุ่งเน้นการยกระดับคุณภาพผู้เรียนให้เต็มตามศักยภาพนั้น\n\nโรงเรียนได้ตระหนักถึงความสำคัญในการพัฒนาผู้เรียนในโครงการ \"{$projectName}\" เพื่อตอบสนองต่อความต้องการจำเป็นของสถานศึกษา ส่งเสริมการจัดการเรียนรู้เชิงรุก (Active Learning) และเสริมสร้างทักษะที่สอดคล้องกับความก้าวหน้าทางเทคโนโลยีและบริบทสังคมปัจจุบัน จึงได้จัดทำโครงการนี้ขึ้นเพื่อขับเคลื่อนการศึกษาให้เกิดผลสัมฤทธิ์อย่างเป็นรูปธรรม",
        'objectives' => [
            "เพื่อส่งเสริมและพัฒนาศักยภาพของนักเรียนในกิจกรรม {$projectName} ให้มีคุณภาพตามเกณฑ์มาตรฐาน สพฐ.",
            "เพื่อพัฒนาครูและบุคลากรทางการศึกษาให้มีความรู้ ความสามารถในการจัดกิจกรรมการเรียนรู้ที่ทันสมัย",
            "เพื่อยกระดับผลสัมฤทธิ์ทางการเรียนและคุณลักษณะอันพึงประสงค์ของผู้เรียนให้สูงขึ้นอย่างต่อเนื่อง"
        ],
        'quantitativeTargets' => [
            "นักเรียนโรงเรียนเป้าหมายเข้าร่วมกิจกรรมร้อยละ 100",
            "ครูและบุคลากรทางการศึกษาผู้รับผิดชอบเข้าร่วมดำเนินงานจำนวน 100% ของเป้าหมาย"
        ],
        'qualitativeTargets' => [
            "นักเรียนที่เข้าร่วมโครงการมีความรู้ ทักษะ และเจตคติที่ดี มีผลการประเมินในระดับดีขึ้นไปไม่น้อยกว่าร้อยละ 85",
            "ผู้เรียนและผู้ปกครองมีความพึงพอใจต่อการดำเนินงานโครงการในระดับดีมาก"
        ],
        'location' => 'โรงเรียนอนุบาลและประถมศึกษาบ้านหนองบัววิทยา และแหล่งเรียนรู้ที่เกี่ยวข้อง',
        'duration' => 'ปีการศึกษา 2568 (ตุลาคม 2567 - กันยายน 2568)',
        'pdcaSchedule' => [
            ['phase' => 'ขั้นวางแผน (Plan)', 'activities' => 'ประชุมคณะกรรมการบริหารสถานศึกษาและคณะทำงานเพื่อวางแผน กำหนดปฏิทิน และอนุมัติโครงการ', 'period' => 'ต.ค. - พ.ย.', 'responsible' => $responsible],
            ['phase' => 'ขั้นดำเนินการ (Do)', 'activities' => 'จัดกิจกรรมตามวัตถุประสงค์ อบรมเชิงปฏิบัติการ และส่งเสริมการเรียนรู้ตามแผนงาน', 'period' => 'ธ.ค. - มิ.ย.', 'responsible' => 'คณะทำงานทุกฝ่าย'],
            ['phase' => 'ขั้นตรวจสอบ (Check)', 'activities' => 'นิเทศ ติดตามผล ประเมินผลสัมฤทธิ์ และทดสอบตามตัวชี้วัดความสำเร็จ', 'period' => 'ก.ค. - ส.ค.', 'responsible' => 'ฝ่ายวิชาการและวัดผล'],
            ['phase' => 'ขั้นปรับปรุงพัฒนา (Act)', 'activities' => 'สรุปรายงานผลการดำเนินโครงการ จัดทำรูปเล่ม และนำผลไปพัฒนาในรอบปีถัดไป', 'period' => 'ก.ย.', 'responsible' => $responsible]
        ],
        'budgetItems' => [
            ['category' => 'ค่าตอบแทน', 'item' => 'ค่าตอบแทนวิทยากรภายนอกผู้เชี่ยวชาญ', 'quantity' => 12, 'unit' => 'ชั่วโมง', 'unitPrice' => round($compPart / 12), 'total' => $compPart],
            ['category' => 'ค่าใช้สอย', 'item' => 'ค่าอาหารกลางวันและอาหารว่างสำหรับผู้เข้าร่วมกิจกรรม', 'quantity' => 1, 'unit' => 'งาน', 'unitPrice' => $servicePart, 'total' => $servicePart],
            ['category' => 'ค่าวัสดุ', 'item' => 'ค่าวัสดุ อุปกรณ์ เอกสารประกอบการอบรม และสื่อการเรียนรู้', 'quantity' => 1, 'unit' => 'ชุด', 'unitPrice' => $materialPart, 'total' => $materialPart]
        ],
        'indicators' => [
            'ร้อยละ 85 ของนักเรียนกลุ่มเป้าหมายมีพัฒนาการและทักษะผ่านเกณฑ์การประเมิน',
            'ร้อยละ 90 ของผู้เข้าร่วมโครงการมีความพึงพอใจในระดับดีขึ้นไป'
        ],
        'evaluationMethods' => [
            'แบบทดสอบวัดความรู้และการประเมินทักษะปฏิบัติจริง',
            'แบบสอบถามความพึงพอใจของผู้เรียน ครู และผู้ปกครอง',
            'การนิเทศและสังเกตพฤติกรรมในห้องเรียน'
        ],
        'expectedOutcomes' => [
            'นักเรียนได้รับการพัฒนาอย่างรอบด้าน มีความสุขในการเรียนรู้ และมีผลสัมฤทธิ์ที่สูงขึ้น',
            'ครูผู้สอนมีเทคนิคและสื่อการจัดการเรียนรู้ที่มีประสิทธิภาพ สามารถนำไปประยุกต์ใช้ในการสอนจริง',
            'สถานศึกษามีผลงานเชิงประจักษ์ที่เป็นเลิศตามเกณฑ์มาตรฐานคุณภาพ สพฐ.'
        ]
    ];
}

echo json_encode([
    'success' => true,
    'data' => $generated,
    'is_ai_live' => !empty($apiKey)
]);
