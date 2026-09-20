<?php
$pageTitle = 'ข้อมูลสถานศึกษา';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$currentSchoolId = !empty($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$successMsg = '';
$errorMsg = '';

// ตรวจสอบการส่งฟอร์มแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle logo file upload if provided
    $logoUrl = trim($_POST['logo_url'] ?? '');
    if (!empty($_FILES['logo_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/logos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $fileName = 'logo_school_' . $currentSchoolId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (@move_uploaded_file($_FILES['logo_file']['tmp_name'], $targetPath)) {
                $logoUrl = 'uploads/logos/' . $fileName;
            }
        }
    }

    $updateData = [
        'name' => trim($_POST['name'] ?? ''),
        'school_code' => trim($_POST['school_code'] ?? ''),
        'smis_code' => trim($_POST['smis_code'] ?? ''),
        'education_area' => trim($_POST['education_area'] ?? ''),
        'affiliation' => trim($_POST['affiliation'] ?? ''),
        'director_name' => trim($_POST['director_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'website' => trim($_POST['website'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'subdistrict' => trim($_POST['subdistrict'] ?? ''),
        'district' => trim($_POST['district'] ?? ''),
        'province' => trim($_POST['province'] ?? ''),
        'zipcode' => trim($_POST['zipcode'] ?? ''),
        'student_count' => (int)($_POST['student_count'] ?? 180),
        'teacher_count' => (int)($_POST['teacher_count'] ?? 15),
        'philosophy' => trim($_POST['philosophy'] ?? ''),
        'vision' => trim($_POST['vision'] ?? ''),
        'mission' => trim($_POST['mission'] ?? ''),
        'goals' => trim($_POST['goals'] ?? ''),
    ];

    if (!empty($logoUrl)) {
        $updateData['logo_url'] = $logoUrl;
    }

    if (!empty($updateData['name'])) {
        updateSchoolData($currentSchoolId, $updateData);
        $successMsg = 'บันทึกและปรับปรุงข้อมูลสถานศึกษาเรียบร้อยแล้ว';
    } else {
        $errorMsg = 'กรุณากรอกชื่อโรงเรียน';
    }
}

$school = getSchoolData($currentSchoolId);
$fiscalYear = getFiscalYearData();
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <!-- Breadcrumb and Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-blue-900 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">ข้อมูลพื้นฐานสถานศึกษา</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                <i data-lucide="school" class="w-6 h-6 text-blue-900"></i>
                <span>ข้อมูลและบริบทสถานศึกษา</span>
            </h2>
            <p class="text-xs text-slate-500">จัดการข้อมูลทั่วไป ปรัชญา วิสัยทัศน์ พันธกิจ และผู้บริหารสถานศึกษาสำหรับแผนปฏิบัติการ</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-900 border border-blue-200 text-xs font-bold font-mono">
                <i data-lucide="key" class="w-3.5 h-3.5"></i>
                <span>รหัสโรงเรียน: <?= htmlspecialchars($school['school_code'] ?? '-') ?></span>
            </span>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($successMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 text-xs">
                <span class="font-bold text-sm block">สำเร็จ!</span>
                <span><?= htmlspecialchars($successMsg) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 text-xs">
                <span class="font-bold text-sm block">ข้อผิดพลาด</span>
                <span><?= htmlspecialchars($errorMsg) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Edit Form -->
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- 1. Card: ข้อมูลทั่วไปและตราสัญลักษณ์ -->
        <div class="bg-white rounded-xl p-5 sm:p-6 border border-slate-200 shadow-xs">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                <i data-lucide="building" class="w-4 h-4 text-blue-900"></i>
                <span>ข้อมูลทั่วไปและสัญลักษณ์ประจำโรงเรียน</span>
            </h3>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Logo section -->
                <div class="lg:col-span-4 flex flex-col items-center justify-center p-4 bg-slate-50/70 rounded-xl border border-slate-200 text-center">
                    <div class="relative group mb-3">
                        <img id="logoPreview" src="<?= htmlspecialchars($school['logo_url'] ?? '') ?>" alt="School Logo" 
                             class="w-28 h-28 rounded-2xl object-cover border-2 border-white shadow-md bg-white">
                    </div>
                    <span class="text-xs font-bold text-slate-800 mb-1">ตราสัญลักษณ์โรงเรียน</span>
                    <p class="text-[11px] text-slate-500 mb-3">แนะนำไฟล์สี่เหลี่ยมจัตุรัส PNG หรือ JPG</p>
                    
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg text-xs font-bold text-slate-700 shadow-2xs transition-colors">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        <span>เลือกรูปภาพจากเครื่อง</span>
                        <input type="file" name="logo_file" accept="image/*" class="hidden" onchange="previewUploadedImage(this)">
                    </label>
                    <div class="w-full mt-3">
                        <input type="text" name="logo_url" id="logoUrlInput" value="<?= htmlspecialchars($school['logo_url'] ?? '') ?>" 
                               placeholder="หรือระบุ URL รูปภาพ เช่น https://..." 
                               class="w-full text-[11px] px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-900">
                    </div>
                </div>

                <!-- Input fields -->
                <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">ชื่อโรงเรียน / สถานศึกษา <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($school['name'] ?? '') ?>" 
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">รหัสสถานศึกษา 10 หลัก (DMC / สพฐ.)</label>
                        <input type="text" name="school_code" value="<?= htmlspecialchars($school['school_code'] ?? '') ?>" 
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">รหัส SMIS 8 หลัก</label>
                        <input type="text" name="smis_code" value="<?= htmlspecialchars($school['smis_code'] ?? '') ?>" 
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">หน่วยงานต้นสังกัด</label>
                        <input type="text" name="affiliation" value="<?= htmlspecialchars($school['affiliation'] ?? 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)') ?>" 
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">สำนักงานเขตพื้นที่การศึกษา (สพป. / สพม.)</label>
                        <input type="text" name="education_area" value="<?= htmlspecialchars($school['education_area'] ?? '') ?>" 
                               placeholder="เช่น สำนักงานเขตพื้นที่การศึกษาประถมศึกษาขอนแก่น เขต 1"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">ชื่อผู้อำนวยการ / ผู้บริหารสถานศึกษา</label>
                        <input type="text" name="director_name" value="<?= htmlspecialchars($school['director_name'] ?? '') ?>" 
                               placeholder="เช่น นายสมชาย ใจดี (ผู้อำนวยการ)"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">จำนวนนักเรียน (คน)</label>
                            <input type="number" name="student_count" min="0" value="<?= htmlspecialchars($school['student_count'] ?? 180) ?>" 
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">จำนวนครู/บุคลากร (คน)</label>
                            <input type="number" name="teacher_count" min="0" value="<?= htmlspecialchars($school['teacher_count'] ?? 15) ?>" 
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Card: ที่อยู่และการติดต่อ -->
        <div class="bg-white rounded-xl p-5 sm:p-6 border border-slate-200 shadow-xs">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                <span>สถานที่ตั้งและการติดต่อสถานศึกษา</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">ที่อยู่ (เลขที่, หมู่ที่, ถนน)</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($school['address'] ?? '') ?>" 
                           placeholder="เช่น 124 หมู่ที่ 3 ถนนมิตรภาพ"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">ตำบล / แขวง</label>
                    <input type="text" name="subdistrict" value="<?= htmlspecialchars($school['subdistrict'] ?? '') ?>" 
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">อำเภอ / เขต</label>
                    <input type="text" name="district" value="<?= htmlspecialchars($school['district'] ?? '') ?>" 
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">จังหวัด</label>
                    <input type="text" name="province" value="<?= htmlspecialchars($school['province'] ?? '') ?>" 
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">รหัสไปรษณีย์</label>
                    <input type="text" name="zipcode" value="<?= htmlspecialchars($school['zipcode'] ?? '') ?>" 
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($school['phone'] ?? '') ?>" 
                           placeholder="เช่น 02-123-4567"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">อีเมลติดต่อ (OBEC Mail / ทางการ)</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($school['email'] ?? '') ?>" 
                           placeholder="เช่น school@obec.mail.go.th"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900">
                </div>
            </div>
        </div>

        <!-- 3. Card: ปรัชญา วิสัยทัศน์ พันธกิจ เป้าประสงค์ -->
        <div class="bg-white rounded-xl p-5 sm:p-6 border border-slate-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                <i data-lucide="target" class="w-4 h-4 text-purple-600"></i>
                <span>ทิศทางการจัดการศึกษา (ปรัชญา วิสัยทัศน์ พันธกิจ เป้าประสงค์)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">ปรัชญา / อัตลักษณ์ / เอกลักษณ์ / คำขวัญ</label>
                    <textarea name="philosophy" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"><?= htmlspecialchars($school['philosophy'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">วิสัยทัศน์ (Vision)</label>
                    <textarea name="vision" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"><?= htmlspecialchars($school['vision'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">พันธกิจ (Mission)</label>
                    <textarea name="mission" rows="4" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"><?= htmlspecialchars($school['mission'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">เป้าประสงค์ (Goals)</label>
                    <textarea name="goals" rows="4" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"><?= htmlspecialchars($school['goals'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="dashboard.php" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-colors">
                ยกเลิก
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold shadow-md shadow-blue-900/20 transition-all">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>บันทึกการแก้ไขข้อมูลสถานศึกษา</span>
            </button>
        </div>
    </form>
</main>

<script>
function previewUploadedImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
document.getElementById('logoUrlInput')?.addEventListener('input', function(e) {
    if (e.target.value.trim().length > 5) {
        document.getElementById('logoPreview').src = e.target.value.trim();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

