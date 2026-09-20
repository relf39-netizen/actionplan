<?php
/**
 * Safe entry point if Web Server DocumentRoot is configured to /public
 */
if (file_exists(__DIR__ . '/../dashboard.php')) {
    require_once __DIR__ . '/../dashboard.php';
} else {
    header('Location: ../index.php');
}
exit;

