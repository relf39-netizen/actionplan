<?php
$pageTitle = 'ตั้งค่าระบบ';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$isDbConnected = Database::isConnected();
$connectionError = Database::$connectionError;
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">การตั้งค่าระบบและการเชื่อมต่อ</h2>
            <p class="text-xs text-slate-500">ตรวจสอบสถานะการเชื่อมต่อฐานข้อมูล MySQL และการตั้งค่า AI Gemini</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <!-- Database Connection Status & Guide -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-2">
                <i data-lucide="database" class="w-4 h-4 text-blue-600"></i>
                <span>สถานะการเชื่อมต่อฐานข้อมูล MySQL</span>
            </h3>

            <?php if ($isDbConnected): ?>
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2 font-semibold">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <span>เชื่อมต่อฐานข้อมูล MySQL บน Server สำเร็จ พร้อมบันทึกข้อมูลถาวร</span>
                </div>
            <?php else: ?>
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold">ระบบกำลังทำงานในโหมดสาธิต (Demo Mode)</span>
                        <p class="mt-0.5 text-[11px] text-amber-800">
                            ยังไม่ได้เชื่อมต่อฐานข้อมูล MySQL แต่ระบบสามารถเปิดใช้งาน ดูข้อมูลตัวอย่าง และใช้งาน AI ร่างโครงการได้ตามปกติ
                        </p>
                        <?php if ($connectionError): ?>
                            <div class="mt-2 p-2 bg-amber-100/70 rounded text-[10px] font-mono text-amber-950">
                                ข้อผิดพลาด: <?= htmlspecialchars($connectionError) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Step-by-step Setup instructions -->
                <div class="border-t border-slate-100 pt-3">
                    <h4 class="text-xs font-bold text-slate-800 mb-2">วิธีเชื่อมต่อฐานข้อมูล MySQL บน Web Hosting / cPanel:</h4>
                    <ol class="list-decimal list-inside text-xs text-slate-600 space-y-1.5 pl-1 leading-relaxed">
                        <li>เปิดระบบควบคุมโฮสติ้งของคุณ (cPanel / DirectAdmin / phpMyAdmin)</li>
                        <li>สร้างฐานข้อมูลใหม่ เช่น <code>school_budget_db</code></li>
                        <li>เปิด phpMyAdmin แล้วกด <strong>Import (นำเข้า)</strong> เลือกไฟล์ <code>database/schema.sql</code> และ <code>database/seed.sql</code></li>
                        <li>เปิดไฟล์ <code>config/database.php</code> แก้ไขข้อมูลให้ตรงกับโฮสติ้ง:
                            <pre class="bg-slate-900 text-slate-100 p-2.5 rounded-lg text-[11px] font-mono mt-1 overflow-x-auto">
define('DB_HOST', 'localhost');
define('DB_NAME', 'ชื่อฐานข้อมูลที่คุณสร้าง');
define('DB_USER', 'ชื่อผู้ใช้ MySQL');
define('DB_PASS', 'รหัสผ่าน MySQL');</pre>
                        </li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>

        <!-- Gemini AI Settings -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-2">
                <i data-lucide="bot" class="w-4 h-4 text-purple-600"></i>
                <span>การเชื่อมต่อ Google Gemini AI</span>
            </h3>

            <p class="text-xs text-slate-600">
                ระบบใช้โมเดล Google Gemini ในการร่างข้อเสนอโครงการตามแบบฟอร์ม สพฐ. โดยส่งออกเป็นไฟล์ Word (.doc) และ PDF ได้ทันที
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Gemini API Key</label>
                <div class="flex gap-2">
                    <input type="password" id="settings-gemini-key" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 font-mono" placeholder="AIzaSy...">
                    <button type="button" onclick="saveSettingsApiKey()" class="px-4 py-2 bg-purple-700 text-white text-xs font-bold rounded-lg hover:bg-purple-800 shrink-0">
                        บันทึก
                    </button>
                </div>
            </div>

            <div class="text-xs text-slate-500 flex items-center justify-between pt-2">
                <span>สามารถขอรับ API Key ได้ฟรี</span>
                <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-purple-700 font-bold hover:underline">
                    Google AI Studio &rarr;
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    const savedKey = localStorage.getItem('gemini_api_key') || '';
    document.getElementById('settings-gemini-key').value = savedKey;

    function saveSettingsApiKey() {
        const val = document.getElementById('settings-gemini-key').value.trim();
        if (val) {
            localStorage.setItem('gemini_api_key', val);
            alert('บันทึก Gemini API Key เรียบร้อยแล้ว!');
        } else {
            localStorage.removeItem('gemini_api_key');
            alert('ลบ API Key เรียบร้อยแล้ว');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
