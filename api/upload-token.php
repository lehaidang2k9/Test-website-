<?php
require_once 'config.php';
// Vercel Blob yêu cầu trả về token an toàn
echo json_encode(['token' => getenv('BLOB_READ_WRITE_TOKEN')]);
