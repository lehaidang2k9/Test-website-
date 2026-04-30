<?php
session_start();
$dbUrl = getenv('POSTGRES_URL'); 
try {
    $pdo = new PDO($dbUrl);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Lỗi kết nối hệ thống.");
}
function isLoggedIn() { return isset($_SESSION['user_id']); }
?>
