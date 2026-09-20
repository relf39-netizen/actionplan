<?php
/**
 * Root Entry Point for PHP Web Server
 * ระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณโรงเรียน
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load dashboard directly if authenticated
require_once __DIR__ . '/dashboard.php';

