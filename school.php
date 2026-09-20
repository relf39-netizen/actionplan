<?php
$pageTitle = 'ข้อมูลสถานศึกษา';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$school = getSchoolData();
$fiscalYear = getFiscalYearData();
?>

<main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">ข้อมูลพื้นฐานสถานศึกษา</h2>
            <p class="text-xs text-slate-500">ข้อมูลทั่วไป รหัสสถานศึกษา 10 หลัก และหน่วยงานต้นสังกัด</p>
        </div>
    </div>

    <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-xs max-w-3xl">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
            <img src="<?= htmlspecialchars($school['logo_url']) ?>" alt="Logo" class="w-16 h-16 rounded-xl object-cover border border-slate-200 shadow-xs">
            <div>
                <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($school['name']) ?></h3>
                <p class="text-xs text-slate-500">รหัสสถานศึกษา: <span class="font-mono font-bold text-blue-900"><?= htmlspecialchars($school['school_code']) ?></span></p>
                <p class="text-xs text-slate-500"><?= htmlspecialchars($school['education_area']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-500 font-semibold">สังกัด:</span>
                <p class="font-bold text-slate-800 mt-0.5"><?= htmlspecialchars($school['affiliation']) ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold">ผู้อำนวยการสถานศึกษา:</span>
                <p class="font-bold text-slate-800 mt-0.5"><?= htmlspecialchars($school['director_name']) ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold">ที่ตั้งสถานศึกษา:</span>
                <p class="font-bold text-slate-800 mt-0.5">
                    <?= htmlspecialchars($school['address']) ?> ต.<?= htmlspecialchars($school['subdistrict']) ?> อ.<?= htmlspecialchars($school['district']) ?> จ.<?= htmlspecialchars($school['province']) ?> <?= htmlspecialchars($school['zipcode']) ?>
                </p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold">การติดต่อ:</span>
                <p class="font-bold text-slate-800 mt-0.5">
                    โทร: <?= htmlspecialchars($school['phone']) ?> | อีเมล: <?= htmlspecialchars($school['email']) ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
