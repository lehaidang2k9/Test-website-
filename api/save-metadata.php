<?php
require_once 'config.php';
$data = json_decode(file_get_contents('php://input'), true);
if ($data && isset($_SESSION['user_id'])) {
    $uuid = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO files (uuid, user_id, original_name, stored_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$uuid, $_SESSION['user_id'], $data['name'], $data['url'], $data['type'], $data['size']]);
}
