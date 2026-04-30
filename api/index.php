<?php
require_once 'config.php';
if (!isset($_SESSION['username'])) { header('Location: auth.php'); exit; }
$username = $_SESSION['username'];
$avatar = $_SESSION['avatar'] ?? '';
$firstLetter = strtoupper(substr($username, 0, 1));

// Thống kê dữ liệu từ Postgres
$stmtMedia = $pdo->query("SELECT COUNT(*) FROM files");
$totalMedia = $stmtMedia->fetchColumn();
$stmtSize = $pdo->query("SELECT SUM(file_size) FROM files");
$totalSizeBytes = $stmtSize->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT * FROM files ORDER BY created_at DESC LIMIT 40");
$recent_files = $stmt->fetchAll();

function getRawSize($bytes) {
    if ($bytes <= 0) return ['val' => 0, 'unit' => 'B'];
    $base = log($bytes, 1024);
    $units = array('B', 'KB', 'MB', 'GB');
    return ['val' => round(pow(1024, $base - floor($base)), 1), 'unit' => $units[floor($base)]];
}
$sizeData = getRawSize($totalSizeBytes);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CloudX — Hải Đăng</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" />
    <style>
        /* GIỮ NGUYÊN CSS CŨ CỦA BẠN */
        :root { --primary: #8b5cf6; --bg: #050508; --card-bg: #111118; --border: rgba(255, 255, 255, 0.1); --text: #ffffff; --danger: #ff4d4d; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg); color: var(--text); padding: 15px 15px 80px 15px; min-height: 100vh; }
        .container { width: 100%; max-width: 450px; margin: 0 auto; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; position: relative; }
        .header h1 { font-size: 22px; font-weight: 800; }
        .header h1 span { color: var(--primary); }
        .avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; border: 2px solid var(--border); overflow: hidden; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .p-menu { position: absolute; top: 50px; right: 0; background: #1a1a25; border: 1px solid var(--border); border-radius: 12px; width: 140px; display: none; flex-direction: column; z-index: 2000; }
        .p-menu.show { display: flex; }
        .p-menu a { padding: 12px; font-size: 13px; color: #fff; text-decoration: none; border-bottom: 1px solid var(--border); }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(17, 17, 24, 0.8); backdrop-filter: blur(15px); border-top: 1px solid var(--border); display: flex; justify-content: space-around; padding: 12px 0; z-index: 5000; }
        .nav-item { color: #888; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .nav-item.active { color: var(--primary); }
        .stats { display: flex; gap: 8px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: var(--card-bg); border: 1px solid var(--border); padding: 15px; border-radius: 20px; text-align: center; }
        .stat-val { font-size: 18px; font-weight: 800; color: #ffffff; }
        .upload-card { background: var(--card-bg); border: 1px solid var(--border); padding: 15px; border-radius: 20px; margin-bottom: 25px; }
        .drop-zone { border: 2px dashed var(--border); padding: 20px 10px; border-radius: 15px; cursor: pointer; text-align: center; display: block; margin-bottom: 12px; }
        .btn-up { width: 100%; background: var(--primary); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: bold; }
        .prog-wrap { margin-top: 12px; display: none; }
        .prog-bar-bg { width: 100%; background: rgba(255,255,255,0.05); border-radius: 10px; height: 6px; overflow: hidden; }
        .prog-bar-fill { width: 0%; height: 100%; background: var(--primary); transition: width 0.2s; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
        .item-wrapper { position: relative; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: var(--card-bg); }
        .item img { width: 100%; height: 100%; object-fit: cover; }
        .del-btn { position: absolute; top: 1px; right: 1px; background: var(--danger); color: white; border: none; width: 16px; height: 16px; border-radius: 3px; z-index: 5; }
        #toast { position: fixed; top: 20px; right: -300px; background: var(--primary); color: white; padding: 10px 18px; border-radius: 10px 0 0 10px; z-index: 10000; transition: right 0.4s; }
        #toast.show { right: 0; }
        #cModal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 9000; }
        .m-content { background: #1a1a25; border: 1px solid var(--border); border-radius: 20px; padding: 20px; width: 280px; text-align: center; }
    </style>
</head>
<body>

<div id="toast"><span id="t-msg">Thông báo</span></div>

<div id="cModal">
    <div class="m-content">
        <p style="font-weight:bold; margin-bottom:15px; font-size:14px">Xóa tệp này?</p>
        <div style="display:flex; gap:8px">
            <button class="btn-up" style="background:#333; flex:1" onclick="closeM()">HỦY</button>
            <button class="btn-up" style="background:var(--danger); flex:1" id="confirmBtn">XÓA</button>
        </div>
    </div>
</div>

<nav class="nav-bar">
    <a href="/" class="nav-item active"><span class="material-symbols-outlined">home</span><small>Trang chủ</small></a>
    <a href="/api.php" class="nav-item"><span class="material-symbols-outlined">chat</span><small>Tin nhắn</small></a>
    <a href="/search.php" class="nav-item"><span class="material-symbols-outlined">group</span><small>Bạn bè</small></a>
    <a href="/profile.php" class="nav-item"><span class="material-symbols-outlined">person</span><small>Hồ sơ</small></a>
</nav>

<div class="container">
    <div class="header">
        <h1>Hải Đăng <span>CloudX</span></h1>
        <div class="avatar" id="avBtn">
            <?php if($avatar): ?> <img src="<?= $avatar ?>"> <?php else: ?> <?= $firstLetter ?> <?php endif; ?>
        </div>
        <div class="p-menu" id="m">
            <a href="/profile.php">Hồ sơ</a>
            <a href="/auth.php?logout=1" style="color:var(--danger)">Đăng xuất</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box"><small>Tổng tệp</small><div class="stat-val"><?= $totalMedia ?></div></div>
        <div class="stat-box"><small>Dung lượng</small><div class="stat-val"><?= $sizeData['val'] ?><small style="font-size:10px"><?= $sizeData['unit'] ?></small></div></div>
    </div>

    <div class="upload-card">
        <label for="fInp" class="drop-zone">
            <input type="file" id="fInp" style="display:none">
            <i class="material-symbols-outlined" style="font-size:30px;color:var(--primary)">cloud_upload</i>
            <p id="info" style="font-size:11px">Chọn Video / Ảnh</p>
        </label>
        <button class="btn-up" id="upBtn">TẢI LÊN NGAY</button>
        <div class="prog-wrap" id="pw">
            <div class="prog-bar-bg"><div class="prog-bar-fill" id="pb"></div></div>
            <div class="prog-text" id="pt">0%</div>
        </div>
    </div>

    <div class="grid">
        <?php foreach ($recent_files as $f): 
            $isImg = (strpos($f['mime_type'], 'image/') === 0);
        ?>
            <div class="item-wrapper">
                <button class="del-btn" onclick="openM('<?= $f['uuid'] ?>')">×</button>
                <a href="/view.php?id=<?= $f['uuid'] ?>" class="item">
                    <?php if($isImg): ?><img src="<?= $f['stored_name'] ?>" loading="lazy"><?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:20px">
                        <?= (strpos($f['mime_type'], 'video/') === 0) ? '🎬' : '📄' ?>
                    </div><?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script type="module">
    import { upload } from 'https://esm.sh/@vercel/blob/dist/index.browser.js';

    // Menu & Thông báo
    const av = document.getElementById('avBtn'), menu = document.getElementById('m');
    av.onclick = (e) => { e.stopPropagation(); menu.classList.toggle('show'); };
    document.onclick = () => menu.classList.remove('show');

    function notify(msg) {
        const t = document.getElementById('toast'), m = document.getElementById('t-msg');
        m.innerText = msg; t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2500);
    }

    // Xử lý Xóa
    let curId = null;
    window.openM = (id) => { curId = id; document.getElementById('cModal').style.display='flex'; };
    window.closeM = () => { document.getElementById('cModal').style.display='none'; };
    document.getElementById('confirmBtn').onclick = async () => {
        const res = await fetch('/delete.php?uuid=' + curId);
        if(res.ok) { notify("Đã xóa xong!"); setTimeout(() => location.reload(), 600); }
    };

    // Xử lý Upload trực tiếp lên Vercel Blob
    const fInp = document.getElementById('fInp'), upBtn = document.getElementById('upBtn');
    fInp.onchange = () => { document.getElementById('info').innerText = fInp.files[0].name; };

    upBtn.onclick = async () => {
        if(!fInp.files.length) return notify("Chưa chọn file!");
        
        upBtn.disabled = true;
        document.getElementById('pw').style.display = 'block';
        notify("Đang tải lên mây...");

        try {
            const file = fInp.files[0];
            const blob = await upload(file.name, file, {
                access: 'public',
                handleUploadUrl: '/upload-token.php',
                onProgress: (p) => {
                    document.getElementById('pb').style.width = p.percentage + '%';
                    document.getElementById('pt').innerText = Math.round(p.percentage) + '%';
                }
            });

            // Lưu vào Database
            await fetch('/save-metadata.php', {
                method: 'POST',
                body: JSON.stringify({ url: blob.url, name: file.name, size: file.size, type: file.type })
            });

            notify("Thành công!");
            setTimeout(() => location.reload(), 800);
        } catch (e) {
            notify("Lỗi: " + e.message);
            upBtn.disabled = false;
        }
    };
</script>
</body>
</html>
