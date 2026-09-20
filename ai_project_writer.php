<?php
$pageTitle = 'เขียนโครงการด้วย AI (แบบฟอร์ม สพฐ.)';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle save project into session/database
$saveMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_project') {
    $pName = trim($_POST['p_name'] ?? '');
    $pDept = trim($_POST['p_dept'] ?? 'ฝ่ายบริหารงานวิชาการ');
    $pResp = trim($_POST['p_resp'] ?? 'ผู้รับผิดชอบโครงการ');
    $pBudget = floatval($_POST['p_budget'] ?? 0);
    $pRationale = trim($_POST['p_rationale'] ?? '');

    if (!empty($pName)) {
        if (!isset($_SESSION['projects'])) {
            getProjectsData();
        }
        $newId = count($_SESSION['projects']) + 1;
        $code = 'กค.' . str_pad($newId, 2, '0', STR_PAD_LEFT) . '/' . $fiscalYear['year'];
        
        $newProject = [
            'id' => $newId,
            'project_code' => $code,
            'project_name' => $pName,
            'department' => $pDept,
            'responsible_person' => $pResp,
            'allocated_budget' => $pBudget,
            'spent_budget' => 0,
            'remaining_budget' => $pBudget,
            'status' => 'not_started',
            'approval_status' => 'pending',
            'duration' => 'ตลอดปีการศึกษา ' . $fiscalYear['year'],
            'rationale' => $pRationale,
        ];
        array_unshift($_SESSION['projects'], $newProject);
        $saveMessage = "บันทึกโครงการ \"$pName\" เข้าสู่แผนปฏิบัติการเรียบร้อยแล้ว!";
    }
}
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <?php if ($saveMessage): ?>
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between text-xs font-semibold shadow-xs">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span><?= htmlspecialchars($saveMessage) ?></span>
            </div>
            <a href="projects.php" class="bg-emerald-600 text-white px-3 py-1 rounded-lg hover:bg-emerald-700 transition-colors">
                ดูโครงการทั้งหมด &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- Title & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                    <i data-lucide="bot" class="w-3 h-3"></i>
                    <span>ระบบร่างเอกสารโครงการราชการ สพฐ.</span>
                </span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">ผู้ช่วยเขียนโครงการด้วย AI</h2>
            <p class="text-xs text-slate-500">สร้างแบบเสนอโครงการฉบับสมบูรณ์ 13 หัวข้อ ตามระเบียบแบบฟอร์ม สพฐ. พร้อมตารางงบประมาณ 4 หมวด</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openApiKeyModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold rounded-lg shadow-xs transition-colors">
                <i data-lucide="key" class="w-3.5 h-3.5 text-amber-500"></i>
                <span id="api-key-status-text">ตั้งค่า Gemini Key</span>
            </button>
        </div>
    </div>

    <!-- Quick Preset Buttons -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs mb-6">
        <span class="text-xs font-bold text-slate-700 block mb-2">⚡ เลือกแม่แบบโครงการยอดนิยมของโรงเรียน สพฐ.:</span>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="applyPreset(0)" class="text-xs px-3 py-1.5 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 rounded-lg transition-colors font-medium">
                🎯 ยกระดับผลสัมฤทธิ์ O-NET / NT (45,000 บ.)
            </button>
            <button type="button" onclick="applyPreset(1)" class="text-xs px-3 py-1.5 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 rounded-lg transition-colors font-medium">
                🤖 AI Literacy & ทักษะดิจิทัล (40,000 บ.)
            </button>
            <button type="button" onclick="applyPreset(2)" class="text-xs px-3 py-1.5 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 rounded-lg transition-colors font-medium">
                ⚖️ โรงเรียนสุจริต & คุณธรรม (25,000 บ.)
            </button>
            <button type="button" onclick="applyPreset(3)" class="text-xs px-3 py-1.5 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 rounded-lg transition-colors font-medium">
                🏫 โรงเรียนปลอดภัย Safety School (50,000 บ.)
            </button>
            <button type="button" onclick="applyPreset(4)" class="text-xs px-3 py-1.5 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 rounded-lg transition-colors font-medium">
                🌱 เกษตรเพื่ออาหารกลางวันพอเพียง (35,000 บ.)
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Input Form (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-xl p-5 border border-slate-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-4 h-4 text-purple-600"></i>
                <span>กรอกข้อมูลความต้องการของโครงการ</span>
            </h3>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อโครงการ *</label>
                <input type="text" id="inp-name" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none" value="โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ฝ่ายที่รับผิดชอบ</label>
                    <select id="inp-dept" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        <option value="ฝ่ายบริหารงานวิชาการ">ฝ่ายบริหารงานวิชาการ</option>
                        <option value="ฝ่ายบริหารงานงบประมาณ">ฝ่ายบริหารงานงบประมาณ</option>
                        <option value="ฝ่ายบริหารงานบุคคล">ฝ่ายบริหารงานบุคคล</option>
                        <option value="ฝ่ายบริหารงานทั่วไป">ฝ่ายบริหารงานทั่วไป</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">งบประมาณขอจัดสรร (บาท) *</label>
                    <input type="number" id="inp-budget" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none" value="45000" step="500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ผู้รับผิดชอบโครงการ</label>
                    <input type="text" id="inp-resp" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none" value="นางสาวกนกพร ใจมั่น">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">กลุ่มเป้าหมาย</label>
                    <input type="text" id="inp-target" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none" value="นักเรียนชั้น ป.3 และ ป.6 ทุกคน">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">จุดเน้นหรือวัตถุประสงค์พิเศษ (ถ้ามี)</label>
                <textarea id="inp-objectives" rows="2" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none" placeholder="เช่น มุ่งเน้นการติวเข้มและฝึกทำข้อสอบเสมือนจริงในกลุ่มสาระคณิตศาสตร์และภาษาอังกฤษ"></textarea>
            </div>

            <button type="button" id="btn-generate" onclick="generateProjectAI()" class="w-full py-2.5 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                <i data-lucide="sparkles" class="w-4 h-4 text-amber-300"></i>
                <span id="btn-generate-text">ให้ AI ร่างข้อเสนอโครงการ สพฐ. ทันที</span>
            </button>
        </div>

        <!-- Right Column: Proposal Preview & Word/PDF Actions (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Action Bar on Top of Preview -->
            <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-2 no-print">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                    <i data-lucide="file-check" class="w-4 h-4 text-emerald-600"></i>
                    <span>ตัวอย่างเอกสารแบบเสนอโครงการ (พร้อมพิมพ์ / เสนออนุมัติ)</span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Export to Word Form -->
                    <form action="export_doc.php" method="POST" target="_blank" id="form-export-word" class="inline">
                        <input type="hidden" name="project_name" id="exp-name">
                        <input type="hidden" name="department" id="exp-dept">
                        <input type="hidden" name="responsible_person" id="exp-resp">
                        <input type="hidden" name="budget" id="exp-budget">
                        <input type="hidden" name="html_content" id="exp-html">
                        <button type="button" onclick="submitWordExport()" class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-colors">
                            <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                            <span>ดาวน์โหลด Word (.doc)</span>
                        </button>
                    </form>

                    <!-- Print / PDF -->
                    <button type="button" onclick="window.print()" class="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-colors">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>พิมพ์ / PDF</span>
                    </button>

                    <!-- Save to Action Plan Button -->
                    <form action="ai_project_writer.php" method="POST" id="form-save-project" class="inline">
                        <input type="hidden" name="action" value="save_project">
                        <input type="hidden" name="p_name" id="save-p-name">
                        <input type="hidden" name="p_dept" id="save-p-dept">
                        <input type="hidden" name="p_resp" id="save-p-resp">
                        <input type="hidden" name="p_budget" id="save-p-budget">
                        <input type="hidden" name="p_rationale" id="save-p-rationale">
                        <button type="button" onclick="submitSaveProject()" class="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-colors shadow-xs">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            <span>บันทึกเข้าแผนงาน</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Paper-like Printable Proposal Preview Area -->
            <div id="proposal-document" class="printable-area bg-white rounded-xl p-8 border border-slate-300 shadow-md text-slate-800 text-xs leading-relaxed space-y-4 font-sans">
                <!-- Proposal Header -->
                <div class="text-center border-b border-slate-200 pb-4">
                    <h3 class="text-base font-bold text-slate-900 tracking-wide">แบบเสนอโครงการตามแผนปฏิบัติการประจำปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?></h3>
                    <p class="text-xs text-slate-600 font-semibold"><?= htmlspecialchars($school['name']) ?></p>
                    <p class="text-[11px] text-slate-500"><?= htmlspecialchars($school['affiliation']) ?></p>
                </div>

                <!-- 1. Project Title & Basic info -->
                <div class="space-y-1.5">
                    <div><strong>1. ชื่อโครงการ:</strong> <span id="view-name" class="font-bold text-blue-900">โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)</span></div>
                    <div><strong>ลักษณะโครงการ:</strong> <span id="view-type">โครงการต่อเนื่องตามแผนปฏิบัติการประจำปี</span></div>
                    <div><strong>ความสอดคล้องกับยุทธศาสตร์:</strong> <span id="view-align">สอดคล้องกับยุทธศาสตร์สถานศึกษา ด้านคุณภาพผู้เรียน และนโยบาย สพฐ. ยกระดับคุณภาพการศึกษา</span></div>
                    <div><strong>ฝ่ายที่รับผิดชอบ:</strong> <span id="view-dept">ฝ่ายบริหารงานวิชาการ</span></div>
                    <div><strong>ผู้รับผิดชอบโครงการ:</strong> <span id="view-resp">นางสาวกนกพร ใจมั่น</span></div>
                </div>

                <!-- 2. Rationale -->
                <div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">2. หลักการและเหตุผล:</h4>
                    <p id="view-rationale" class="text-slate-700 text-justify indent-8">
                        ตามที่สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.) มีนโยบายมุ่งเน้นการยกระดับคุณภาพการศึกษาและการประเมินผลสัมฤทธิ์ทางการเรียนของผู้เรียนในระดับชาติ การทดสอบระดับชาติขั้นพื้นฐาน (O-NET) และการประเมินคุณภาพผู้เรียน (NT) ถือเป็นดัชนีชี้วัดสำคัญของสถานศึกษา โรงเรียนจึงได้จัดทำโครงการนี้เพื่อพัฒนาสมรรถนะการเรียนรู้และเตรียมความพร้อมให้นักเรียนอย่างเป็นระบบ
                    </p>
                </div>

                <!-- 3. Objectives -->
                <div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">3. วัตถุประสงค์:</h4>
                    <ul id="view-objectives" class="list-disc list-inside space-y-0.5 text-slate-700 pl-2">
                        <li>เพื่อยกระดับผลสัมฤทธิ์ทางการเรียนของนักเรียนชั้น ป.3 และ ป.6 ให้สูงขึ้นกว่าปีการศึกษาที่ผ่านมา</li>
                        <li>เพื่อเตรียมความพร้อมและสร้างความมั่นใจให้นักเรียนในการสอบ NT และ O-NET</li>
                        <li>เพื่อพัฒนาครูผู้สอนให้สามารถวิเคราะห์ผลการประเมินและปรับปรุงการจัดการเรียนรู้ได้อย่างตรงจุด</li>
                    </ul>
                </div>

                <!-- 4. Targets -->
                <div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">4. เป้าหมาย:</h4>
                    <div class="pl-2 space-y-1">
                        <div><strong>เชิงปริมาณ:</strong> <span id="view-target-quant">นักเรียนชั้น ป.3 และ ป.6 ทุกคนเข้าร่วมกิจกรรมร้อยละ 100</span></div>
                        <div><strong>เชิงคุณภาพ:</strong> <span id="view-target-qual">ผลคะแนนเฉลี่ยการทดสอบ NT และ O-NET สูงกว่าระดับประเทศ และนักเรียนมีความพึงพอใจในระดับดีมาก</span></div>
                    </div>
                </div>

                <!-- 5. Location & Duration -->
                <div class="grid grid-cols-2 gap-4">
                    <div><strong>5. สถานที่ดำเนินการ:</strong> <span id="view-loc"><?= htmlspecialchars($school['name']) ?></span></div>
                    <div><strong>6. ระยะเวลาดำเนินการ:</strong> <span id="view-duration">ตุลาคม 2567 - กุมภาพันธ์ 2568</span></div>
                </div>

                <!-- 7. PDCA Schedule -->
                <div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">7. ขั้นตอนและปฏิทินการดำเนินงาน (PDCA):</h4>
                    <table class="w-full border-collapse border border-slate-300 text-[11px]">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-300 p-1.5 text-center w-24">ขั้นตอน</th>
                                <th class="border border-slate-300 p-1.5 text-left">กิจกรรมสำคัญ</th>
                                <th class="border border-slate-300 p-1.5 text-center w-28">ระยะเวลา</th>
                                <th class="border border-slate-300 p-1.5 text-left w-32">ผู้รับผิดชอบ</th>
                            </tr>
                        </thead>
                        <tbody id="view-pdca">
                            <tr>
                                <td class="border border-slate-300 p-1.5 text-center font-semibold">ขั้นวางแผน (P)</td>
                                <td class="border border-slate-300 p-1.5">ประชุมคณะครู วิเคราะห์ผลสอบปีก่อน กำหนดแผนติวเข้ม</td>
                                <td class="border border-slate-300 p-1.5 text-center">ต.ค. 2567</td>
                                <td class="border border-slate-300 p-1.5">นางสาวกนกพร ใจมั่น</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-1.5 text-center font-semibold">ขั้นดำเนินการ (D)</td>
                                <td class="border border-slate-300 p-1.5">จัดค่ายยกระดับผลสัมฤทธิ์ ติวเข้ม และทดสอบ Pre O-NET/NT</td>
                                <td class="border border-slate-300 p-1.5 text-center">พ.ย. 67 - ม.ค. 68</td>
                                <td class="border border-slate-300 p-1.5">คณะครูสายชั้น ป.3, ป.6</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-1.5 text-center font-semibold">ขั้นตรวจสอบ (C)</td>
                                <td class="border border-slate-300 p-1.5">ประเมินผลการสอบจำลอง และการสอบจริงระดับชาติ</td>
                                <td class="border border-slate-300 p-1.5 text-center">ก.พ. 2568</td>
                                <td class="border border-slate-300 p-1.5">ฝ่ายวัดและประเมินผล</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-1.5 text-center font-semibold">ขั้นปรับปรุง (A)</td>
                                <td class="border border-slate-300 p-1.5">สรุปรายงานผลโครงการ นำผลไปปรับปรุงหลักสูตรการสอน</td>
                                <td class="border border-slate-300 p-1.5 text-center">มี.ค. 2568</td>
                                <td class="border border-slate-300 p-1.5">นางสาวกนกพร ใจมั่น</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 8. Budget Items (4 Categories) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-bold text-slate-900 text-xs">8. งบประมาณและรายละเอียดค่าใช้จ่าย (4 หมวด สพฐ.):</h4>
                        <span class="font-bold text-blue-900 text-xs">ยอดรวม: <span id="view-budget-total" class="font-mono">45,000.00</span> บาท</span>
                    </div>
                    <table class="w-full border-collapse border border-slate-300 text-[11px]">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-300 p-1.5 text-left">หมวดรายจ่าย</th>
                                <th class="border border-slate-300 p-1.5 text-left">รายการค่าใช้จ่าย</th>
                                <th class="border border-slate-300 p-1.5 text-center w-16">จำนวน</th>
                                <th class="border border-slate-300 p-1.5 text-center w-16">หน่วย</th>
                                <th class="border border-slate-300 p-1.5 text-right w-20">ราคา/หน่วย</th>
                                <th class="border border-slate-300 p-1.5 text-right w-24">รวมเงิน (บาท)</th>
                            </tr>
                        </thead>
                        <tbody id="view-budget-items">
                            <tr>
                                <td class="border border-slate-300 p-1.5 font-semibold">ค่าตอบแทน</td>
                                <td class="border border-slate-300 p-1.5">ค่าสมนาคุณวิทยากรติวเข้มภายนอก</td>
                                <td class="border border-slate-300 p-1.5 text-center">15</td>
                                <td class="border border-slate-300 p-1.5 text-center">ชั่วโมง</td>
                                <td class="border border-slate-300 p-1.5 text-right">600</td>
                                <td class="border border-slate-300 p-1.5 text-right font-mono font-bold">9,000.00</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-1.5 font-semibold">ค่าใช้สอย</td>
                                <td class="border border-slate-300 p-1.5">ค่าอาหารกลางวันและอาหารว่างนักเรียนเข้าค่ายติว</td>
                                <td class="border border-slate-300 p-1.5 text-center">76</td>
                                <td class="border border-slate-300 p-1.5 text-center">คน</td>
                                <td class="border border-slate-300 p-1.5 text-right">150</td>
                                <td class="border border-slate-300 p-1.5 text-right font-mono font-bold">11,400.00</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-1.5 font-semibold">ค่าวัสดุ</td>
                                <td class="border border-slate-300 p-1.5">ค่าเอกสารประกอบการติว แบบฝึก และข้อสอบเสมือนจริง</td>
                                <td class="border border-slate-300 p-1.5 text-center">76</td>
                                <td class="border border-slate-300 p-1.5 text-center">เล่ม</td>
                                <td class="border border-slate-300 p-1.5 text-right">323.68</td>
                                <td class="border border-slate-300 p-1.5 text-right font-mono font-bold">24,600.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 font-bold">
                                <td colspan="5" class="border border-slate-300 p-1.5 text-right">รวมงบประมาณทั้งสิ้น</td>
                                <td class="border border-slate-300 p-1.5 text-right font-mono text-blue-900" id="view-budget-footer">45,000.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 9. Indicators & Outcomes -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-900 text-xs mb-1">9. ตัวชี้วัดความสำเร็จ:</h4>
                        <ul id="view-indicators" class="list-disc list-inside space-y-0.5 text-slate-700 pl-1">
                            <li>ร้อยละ 85 ของนักเรียนมีคะแนนการทดสอบจำลองผ่านเกณฑ์</li>
                            <li>คะแนนเฉลี่ย O-NET และ NT สูงกว่าค่าเฉลี่ยระดับประเทศ</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-xs mb-1">10. ประโยชน์ที่คาดว่าจะได้รับ:</h4>
                        <ul id="view-outcomes" class="list-disc list-inside space-y-0.5 text-slate-700 pl-1">
                            <li>นักเรียนมีผลสัมฤทธิ์ทางการเรียนและทักษะการคิดวิเคราะห์สูงขึ้น</li>
                            <li>โรงเรียนมีผลงานเชิงประจักษ์ในการยกระดับคุณภาพการศึกษา</li>
                        </ul>
                    </div>
                </div>

                <!-- 11. Official Signatures Section (3 columns) -->
                <div class="pt-8 border-t border-slate-200 mt-6">
                    <div class="grid grid-cols-3 gap-2 text-center text-[10px]">
                        <div>
                            <p class="mb-8">ลงชื่อ....................................................</p>
                            <p class="font-bold">(<span id="sign-resp">นางสาวกนกพร ใจมั่น</span>)</p>
                            <p>ผู้เสนอโครงการ</p>
                        </div>
                        <div>
                            <p class="mb-8">ลงชื่อ....................................................</p>
                            <p class="font-bold">(นายพิเชษฐ์ ปัญญาวงศ์)</p>
                            <p>หัวหน้างานแผนงานและงบประมาณ</p>
                        </div>
                        <div>
                            <p class="mb-8">ลงชื่อ....................................................</p>
                            <p class="font-bold">(ดร.สมศักดิ์ พัฒนศึกษา)</p>
                            <p>ผู้อำนวยการโรงเรียน</p>
                            <p class="text-[9px] text-slate-500 mt-1">[ &nbsp; ] อนุมัติ &nbsp;&nbsp; [ &nbsp; ] ไม่อนุมัติ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Gemini API Key Modal -->
<div id="modal-apikey" class="fixed inset-0 bg-slate-900/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="key" class="w-4 h-4 text-amber-500"></i>
                <span>กำหนด Google Gemini API Key</span>
            </h3>
            <button type="button" onclick="closeApiKeyModal()" class="p-1 text-slate-400 hover:text-slate-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <p class="text-xs text-slate-600">
            ระบบสามารถเรียกใช้งาน Google Gemini ได้โดยตรง คุณสามารถขอรับ API Key ได้ฟรีจาก Google AI Studio
        </p>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Gemini API Key ของคุณ</label>
            <input type="password" id="user-gemini-key" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none font-mono" placeholder="AIzaSy...">
        </div>
        <div class="flex items-center justify-between pt-2">
            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-xs text-purple-700 hover:underline font-semibold flex items-center gap-1">
                <span>ขอ API Key ฟรี &rarr;</span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeApiKeyModal()" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="button" onclick="saveApiKey()" class="px-4 py-1.5 text-xs bg-purple-700 text-white font-bold rounded-lg hover:bg-purple-800">บันทึก Key</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Presets data
    const presets = [
        {
            name: 'โครงการยกระดับผลสัมฤทธิ์ทางการเรียนและการทดสอบระดับชาติ (O-NET / NT)',
            dept: 'ฝ่ายบริหารงานวิชาการ',
            budget: 45000,
            resp: 'นางสาวกนกพร ใจมั่น',
            target: 'นักเรียนชั้น ป.3 และ ป.6 ทุกคน',
            obj: 'มุ่งเน้นการติวเข้มและฝึกทำข้อสอบเสมือนจริงในกลุ่มสาระคณิตศาสตร์ วิทยาศาสตร์ และภาษาอังกฤษ'
        },
        {
            name: 'โครงการพัฒนาทักษะดิจิทัลและการรู้เท่าทันปัญญาประดิษฐ์ (AI Literacy) เพื่อการเรียนรู้ในศตวรรษที่ 21',
            dept: 'ฝ่ายบริหารงานวิชาการ',
            budget: 40000,
            resp: 'นายพิเชษฐ์ ปัญญาวงศ์',
            target: 'นักเรียนชั้น ป.4 - ป.6 และครูผู้สอนทุกคน',
            obj: 'ส่งเสริมการใช้เครื่องมือ AI ในการเรียนรู้ สื่อการสอน และการสืบค้นข้อมูลอย่างปลอดภัยและมีจริยธรรม'
        },
        {
            name: 'โครงการส่งเสริมคุณธรรม จริยธรรม และวิถีประชาธิปไตยในสถานศึกษา (โรงเรียนสุจริต)',
            dept: 'ฝ่ายบริหารงานบุคคล',
            budget: 25000,
            resp: 'นายสมชาย วงศ์สว่าง',
            target: 'นักเรียนทุกระดับชั้นและบุคลากรในโรงเรียน',
            obj: 'ปลูกฝังความซื่อสัตย์สุจริต วินัย จิตอาสา และค่านิยมต่อต้านการทุจริตคอร์รัปชัน'
        },
        {
            name: 'โครงการปรับปรุงซ่อมแซมอาคารสถานที่และพัฒนาสิ่งแวดล้อมเพื่อความปลอดภัย (Safety School)',
            dept: 'ฝ่ายบริหารงานทั่วไป',
            budget: 50000,
            resp: 'นายอำนวย สุขเกษม',
            target: 'อาคารเรียน ห้องน้ำ สนามเด็กเล่น และระบบไฟฟ้า',
            obj: 'ปรับปรุงจุดเสี่ยง ซ่อมแซมระบบไฟฟ้า ห้องน้ำ และจัดระเบียบสภาพแวดล้อมให้ปลอดภัยตามเกณฑ์สถานศึกษาปลอดภัย'
        },
        {
            name: 'โครงการเกษตรเพื่ออาหารกลางวันตามหลักปรัชญาของเศรษฐกิจพอเพียง',
            dept: 'ฝ่ายบริหารงานทั่วไป',
            budget: 35000,
            resp: 'นายวิชัย สุวรรณโชติ',
            target: 'นักเรียนแกนนำ แปลงผัก โรงเห็ด บ่อปลา',
            obj: 'ฝึกทักษะอาชีพการเกษตร ปลูกผักปลอดสารพิษ นำผลผลิตสมทบโครงการอาหารกลางวัน'
        }
    ];

    function applyPreset(idx) {
        const p = presets[idx];
        if (!p) return;
        document.getElementById('inp-name').value = p.name;
        document.getElementById('inp-dept').value = p.dept;
        document.getElementById('inp-budget').value = p.budget;
        document.getElementById('inp-resp').value = p.resp;
        document.getElementById('inp-target').value = p.target;
        document.getElementById('inp-objectives').value = p.obj;
        generateProjectAI();
    }

    // Modal API Key
    function openApiKeyModal() {
        const modal = document.getElementById('modal-apikey');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const saved = localStorage.getItem('gemini_api_key') || '';
        document.getElementById('user-gemini-key').value = saved;
    }
    function closeApiKeyModal() {
        const modal = document.getElementById('modal-apikey');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function saveApiKey() {
        const key = document.getElementById('user-gemini-key').value.trim();
        if (key) {
            localStorage.setItem('gemini_api_key', key);
            document.getElementById('api-key-status-text').innerText = 'ตั้งค่า Key แล้ว ✓';
        } else {
            localStorage.removeItem('gemini_api_key');
            document.getElementById('api-key-status-text').innerText = 'ตั้งค่า Gemini Key';
        }
        closeApiKeyModal();
    }

    // Initialize API Key status text
    if (localStorage.getItem('gemini_api_key')) {
        document.getElementById('api-key-status-text').innerText = 'ตั้งค่า Key แล้ว ✓';
    }

    // Generate Proposal using AI
    async function generateProjectAI() {
        const name = document.getElementById('inp-name').value.trim();
        const dept = document.getElementById('inp-dept').value;
        const budget = parseFloat(document.getElementById('inp-budget').value) || 0;
        const resp = document.getElementById('inp-resp').value.trim();
        const target = document.getElementById('inp-target').value.trim();
        const objectives = document.getElementById('inp-objectives').value.trim();
        const userKey = localStorage.getItem('gemini_api_key') || '';

        if (!name) {
            alert('กรุณาระบุชื่อโครงการ');
            return;
        }

        const btnText = document.getElementById('btn-generate-text');
        btnText.innerText = 'AI กำลังเรียบเรียงข้อเสนอโครงการ สพฐ....';

        try {
            const res = await fetch('api/ai_generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_name: name,
                    department: dept,
                    budget: budget,
                    responsible_person: resp,
                    target_audience: target,
                    key_objectives: objectives,
                    api_key: userKey
                })
            });
            const json = await res.json();
            if (json.success && json.data) {
                renderProposal(json.data);
            } else {
                alert(json.error || 'เกิดข้อผิดพลาดในการสร้างเอกสาร');
            }
        } catch (err) {
            console.error(err);
            alert('ไม่สามารถเชื่อมต่อ API ได้ ระบบจะแสดงผลด้วยเทมเพลตมาตรฐาน สพฐ.');
        } finally {
            btnText.innerText = 'ให้ AI ร่างข้อเสนอโครงการ สพฐ. ทันที';
        }
    }

    // Render Proposal Data into Preview
    function renderProposal(data) {
        document.getElementById('view-name').innerText = data.projectName || '';
        document.getElementById('view-type').innerText = data.projectType || 'โครงการต่อเนื่องตามแผนปฏิบัติการประจำปี';
        document.getElementById('view-align').innerText = data.alignment || 'สอดคล้องกับยุทธศาสตร์สถานศึกษา';
        document.getElementById('view-dept').innerText = data.department || '';
        document.getElementById('view-resp').innerText = data.responsiblePerson || '';
        document.getElementById('sign-resp').innerText = data.responsiblePerson || '';
        document.getElementById('view-rationale').innerText = data.rationale || '';

        // Objectives
        const objUl = document.getElementById('view-objectives');
        objUl.innerHTML = '';
        (data.objectives || []).forEach(o => {
            const li = document.createElement('li');
            li.innerText = o;
            objUl.appendChild(li);
        });

        // Targets
        document.getElementById('view-target-quant').innerText = (data.quantitativeTargets || []).join(', ');
        document.getElementById('view-target-qual').innerText = (data.qualitativeTargets || []).join(', ');

        if (data.location) document.getElementById('view-loc').innerText = data.location;
        if (data.duration) document.getElementById('view-duration').innerText = data.duration;

        // PDCA Schedule
        const pdcaTbody = document.getElementById('view-pdca');
        pdcaTbody.innerHTML = '';
        (data.pdcaSchedule || []).forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border border-slate-300 p-1.5 text-center font-semibold">${p.phase}</td>
                <td class="border border-slate-300 p-1.5">${p.activities}</td>
                <td class="border border-slate-300 p-1.5 text-center">${p.period}</td>
                <td class="border border-slate-300 p-1.5">${p.responsible}</td>
            `;
            pdcaTbody.appendChild(tr);
        });

        // Budget Items
        const bTbody = document.getElementById('view-budget-items');
        bTbody.innerHTML = '';
        let total = 0;
        (data.budgetItems || []).forEach(b => {
            total += Number(b.total || 0);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border border-slate-300 p-1.5 font-semibold">${b.category}</td>
                <td class="border border-slate-300 p-1.5">${b.item}</td>
                <td class="border border-slate-300 p-1.5 text-center">${b.quantity}</td>
                <td class="border border-slate-300 p-1.5 text-center">${b.unit}</td>
                <td class="border border-slate-300 p-1.5 text-right font-mono">${Number(b.unitPrice).toLocaleString()}</td>
                <td class="border border-slate-300 p-1.5 text-right font-mono font-bold">${Number(b.total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            `;
            bTbody.appendChild(tr);
        });

        const totalFormatted = total.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('view-budget-total').innerText = totalFormatted;
        document.getElementById('view-budget-footer').innerText = totalFormatted;

        // Indicators & Outcomes
        const indUl = document.getElementById('view-indicators');
        indUl.innerHTML = '';
        (data.indicators || []).forEach(i => {
            const li = document.createElement('li');
            li.innerText = i;
            indUl.appendChild(li);
        });

        const outUl = document.getElementById('view-outcomes');
        outUl.innerHTML = '';
        (data.expectedOutcomes || []).forEach(o => {
            const li = document.createElement('li');
            li.innerText = o;
            outUl.appendChild(li);
        });
    }

    // Submit Word Export
    function submitWordExport() {
        document.getElementById('exp-name').value = document.getElementById('view-name').innerText;
        document.getElementById('exp-dept').value = document.getElementById('view-dept').innerText;
        document.getElementById('exp-resp').value = document.getElementById('view-resp').innerText;
        document.getElementById('exp-budget').value = document.getElementById('view-budget-total').innerText.replace(/,/g, '');
        document.getElementById('exp-html').value = document.getElementById('proposal-document').innerHTML;
        document.getElementById('form-export-word').submit();
    }

    // Submit Save Project to Action Plan
    function submitSaveProject() {
        document.getElementById('save-p-name').value = document.getElementById('view-name').innerText;
        document.getElementById('save-p-dept').value = document.getElementById('view-dept').innerText;
        document.getElementById('save-p-resp').value = document.getElementById('view-resp').innerText;
        document.getElementById('save-p-budget').value = document.getElementById('view-budget-total').innerText.replace(/,/g, '');
        document.getElementById('save-p-rationale').value = document.getElementById('view-rationale').innerText;
        document.getElementById('form-save-project').submit();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
