<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || !isset($_GET['uuid'])) exit;

$stmt = $pdo->prepare("SELECT stored_name FROM files WHERE uuid = ? AND user_id = ?");
$stmt->execute([$_GET['uuid'], $_SESSION['user_id']]);
$file = $stmt->fetch();

if ($file) {
    // Xóa trên Vercel Blob vật lý
    $ch = curl_init("https://blob.vercel-storage.com/delete");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['urls' => [$file['stored_name']]]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".getenv('BLOB_READ_WRITE_TOKEN'), "Content-Type: application/json"]);
    curl_exec($ch);
    
    // Xóa trong Database
    $pdo->prepare("DELETE FROM files WHERE uuid = ?")->execute([$_GET['uuid']]);
}
