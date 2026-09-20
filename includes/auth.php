<?php
/**
 * ตรวจสอบสิทธิ์การเข้าใช้งานระบบ (RBAC)
 */

require_once __DIR__ . '/functions.php';

function checkAuth(array $allowedRoles = []): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    $userRole = $_SESSION['user_role'] ?? 'teacher';
    if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        die("<h1>403 Forbidden - สิทธิ์ไม่เพียงพอ</h1><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาติดต่อผู้ดูแลระบบ</p><p><a href='dashboard.php'>กลับสู่หน้าหลัก</a></p>");
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $userRole,
        'school_id' => $_SESSION['school_id'] ?? 1,
        'fiscal_year_id' => $_SESSION['fiscal_year_id'] ?? 1
    ];
}
