<?php
/**
 * Script ส่งออกข้อเสนอโครงการเป็นไฟล์ Microsoft Word (.doc)
 * รูปแบบราชการ สพฐ.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'] ?? 'แบบเสนอโครงการโรงเรียน';
    $department = $_POST['department'] ?? 'ฝ่ายบริหารงานวิชาการ';
    $responsible = $_POST['responsible_person'] ?? 'หัวหน้าโครงการ';
    $budget = floatval($_POST['budget'] ?? 0);
    $htmlContent = $_POST['html_content'] ?? '';

    // File name
    $filename = 'โครงการ_' . preg_replace('/[^a-zA-Z0-9_\x{0E00}-\x{0E7F}]/u', '_', $projectName) . '.doc';

    header("Content-Type: application/vnd.ms-word; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>';
    echo '@page { size: A4; margin: 2.54cm 2.54cm 2.54cm 2.54cm; mso-page-orientation: portrait; }';
    echo 'body { font-family: "TH Sarabun PSK", "TH Sarabun New", "Sarabun", Tahoma, sans-serif; font-size: 16pt; line-height: 1.25; }';
    echo 'h1 { font-size: 18pt; text-align: center; font-weight: bold; margin-bottom: 8pt; }';
    echo 'h2 { font-size: 16pt; font-weight: bold; margin-top: 12pt; margin-bottom: 4pt; }';
    echo 'p { margin: 0 0 6pt 0; text-indent: 1.5cm; }';
    echo 'p.no-indent { text-indent: 0; }';
    echo 'table { width: 100%; border-collapse: collapse; margin: 10pt 0; }';
    echo 'th, td { border: 1px solid #000; padding: 6pt; font-size: 15pt; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; text-align: center; }';
    echo '.text-center { text-align: center; }';
    echo '.text-right { text-align: right; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo $htmlContent;
    echo '</body>';
    echo '</html>';
    exit;
}
?>
