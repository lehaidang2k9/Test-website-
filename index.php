<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['username'])) { header('Location: auth.php'); exit; }
$username = $_SESSION['username'];
$avatar = $_SESSION['avatar'] ?? '';
$firstLetter = strtoupper(substr($username, 0, 1));

// Thống kê dữ liệu
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
        :root { --primary: #8b5cf6; --bg: #050508; --card-bg: #111118; --border: rgba(255, 255, 255, 0.1); --text: #ffffff; --danger: #ff4d4d; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg); color: var(--text); padding: 15px 15px 80px 15px; min-height: 100vh; overflow-x: hidden; }
        .container { width: 100%; max-width: 450px; margin: 0 auto; }
        
        /* HEADER & MENU AVATAR */
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; position: relative; }
        .header h1 { font-size: 22px; font-weight: 800; }
        .header h1 span { color: var(--primary); }
        .avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; border: 2px solid var(--border); overflow: hidden; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .p-menu { position: absolute; top: 50px; right: 0; background: #1a1a25; border: 1px solid var(--border); border-radius: 12px; width: 140px; display: none; flex-direction: column; z-index: 2000; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .p-menu.show { display: flex; }
        .p-menu a { padding: 12px; font-size: 13px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); }

        /* BOTTOM NAVIGATION MENU */
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(17, 17, 24, 0.8); backdrop-filter: blur(15px); border-top: 1px solid var(--border); display: flex; justify-content: space-around; padding: 12px 0; z-index: 5000; }
        .nav-item { color: #888; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 4px; transition: 0.3s; }
        .nav-item.active { color: var(--primary); }
        .nav-item span { font-size: 24px; }
        .nav-item small { font-size: 10px; font-weight: 600; }

        /* STATS (Số trắng) */
        .stats { display: flex; gap: 8px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: var(--card-bg); border: 1px solid var(--border); padding: 15px; border-radius: 20px; text-align: center; }
        .stat-box small { display: block; font-size: 9px; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: bold; }
        .stat-val { font-size: 18px; font-weight: 800; color: #ffffff; }
        .stat-unit { font-size: 11px; color: #888; margin-left: 2px; }

        /* UPLOAD & % */
        .upload-card { background: var(--card-bg); border: 1px solid var(--border); padding: 15px; border-radius: 20px; margin-bottom: 25px; }
        .drop-zone { border: 2px dashed var(--border); padding: 20px 10px; border-radius: 15px; cursor: pointer; text-align: center; display: block; margin-bottom: 12px; }
        .drop-zone i { font-size: 30px; color: var(--primary); display: block; }
        .btn-up { width: 100%; background: var(--primary); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: bold; cursor: pointer; }
        .prog-wrap { margin-top: 12px; display: none; }
        .prog-bar-bg { width: 100%; background: rgba(255,255,255,0.05); border-radius: 10px; height: 6px; overflow: hidden; }
        .prog-bar-fill { width: 0%; height: 100%; background: var(--primary); transition: width 0.2s; }
        .prog-text { font-size: 10px; color: var(--primary); margin-top: 4px; text-align: right; font-weight: bold; }

        /* GRID */
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
        .item-wrapper { position: relative; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: var(--card-bg); }
        .item img { width: 100%; height: 100%; object-fit: cover; }
        .file-name { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; font-size: 7px; padding: 2px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .del-btn { position: absolute; top: 1px; right: 1px; background: var(--danger); color: white; border: none; width: 16px; height: 16px; border-radius: 3px; z-index: 5; font-size: 12px; display: flex; align-items: center; justify-content: center; }

        /* TOAST TRƯỢT PHẢI */
        #toast { position: fixed; top: 20px; right: -300px; background: var(--primary); color: white; padding: 10px 18px; border-radius: 10px 0 0 10px; font-size: 11px; font-weight: 600; z-index: 10000; transition: right 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; align-items: center; gap: 8px; }
        #toast.show { right: 0; }
        #cModal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 9000; backdrop-filter: blur(4px); }
        .m-content { background: #1a1a25; border: 1px solid var(--border); border-radius: 20px; padding: 20px; width: 280px; text-align: center; }
    </style>
</head>
<body>

<div id="toast"><span class="material-symbols-outlined" style="font-size:16px">bolt</span><span id="t-msg">Thông báo</span></div>

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
    <a href="index.php" class="nav-item active">
        <span class="material-symbols-outlined">home</span>
        <small>Trang chủ</small>
    </a>
    <a href="api.php" class="nav-item">
        <span class="material-symbols-outlined">chat</span>
        <small>Tin nhắn</small>
    </a>
    <a href="search.php" class="nav-item">
        <span class="material-symbols-outlined">group</span>
        <small>Bạn bè</small>
    </a>
    <a href="profile.php" class="nav-item">
        <span class="material-symbols-outlined">person</span>
        <small>Hồ sơ</small>
    </a>
</nav>

<div class="container">
    <div class="header">
        <h1>Hải Đăng <span>CloudX</span></h1>
        <div class="avatar" id="avBtn">
            <?php if($avatar): ?> <img src="uploads/avatars/<?= $avatar ?>"> <?php else: ?> <?= $firstLetter ?> <?php endif; ?>
        </div>
        <div class="p-menu" id="m">
            <a href="profile.php"><span class="material-symbols-outlined" style="font-size:16px">person</span> Hồ sơ</a>
            <a href="auth.php?logout=1" style="color:var(--danger)"><span class="material-symbols-outlined" style="font-size:16px">logout</span> Đăng xuất</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box"><small>Tổng tệp</small><div><span class="stat-val" id="count-files" data-target="<?= $totalMedia ?>">0</span></div></div>
        <div class="stat-box"><small>Dung lượng</small><div><span class="stat-val" id="count-size" data-target="<?= $sizeData['val'] ?>">0</span><span class="stat-unit"><?= $sizeData['unit'] ?></span></div></div>
    </div>

    <div class="upload-card">
        <label for="fInp" class="drop-zone">
            <input type="file" id="fInp" style="display:none">
            <i class="material-symbols-outlined">cloud_upload</i>
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
                <a href="view.php?id=<?= $f['uuid'] ?>" class="item">
                    <?php if($isImg): ?><img src="uploads/<?= $f['stored_name'] ?>" loading="lazy"><?php else: ?>
                    <span style="font-size:20px"><?= (strpos($f['mime_type'], 'video/') === 0) ? '🎬' : '📄' ?></span><?php endif; ?>
                    <div class="file-name"><?= htmlspecialchars($f['original_name']) ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>
<script>
    function countUp(id) {
        const el = document.getElementById(id);
        const target = parseFloat(el.getAttribute('data-target'));
        let count = 0; const speed = 2000 / 60; const inc = target / (2000 / speed);
        const update = () => {
            count += inc;
            if (count < target) { el.innerText = target % 1 === 0 ? Math.floor(count) : count.toFixed(1); requestAnimationFrame(update); } 
            else { el.innerText = target; }
        };
        update();
    }
    window.onload = () => { countUp('count-files'); countUp('count-size'); };

    const av = document.getElementById('avBtn'), menu = document.getElementById('m');
    av.onclick = (e) => { e.stopPropagation(); menu.classList.toggle('show'); };
    document.onclick = () => menu.classList.remove('show');

    function notify(msg) {
        const t = document.getElementById('toast'), m = document.getElementById('t-msg');
        m.innerText = msg; t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2500);
    }

    let curId = null;
    function openM(id) { curId = id; document.getElementById('cModal').style.display='flex'; }
    function closeM() { document.getElementById('cModal').style.display='none'; }
    document.getElementById('confirmBtn').onclick = function() {
        fetch('delete.php?uuid=' + curId).then(() => { notify("Đã xóa xong!"); setTimeout(() => location.reload(), 600); });
    };

    const r = new Resumable({ target: 'upload.php', testChunks: false });
    r.assignBrowse(document.getElementById('fInp'));
    r.on('fileAdded', (f) => { document.getElementById('info').innerText = f.fileName; notify("Đã nhận tệp!"); });
    document.getElementById('upBtn').onclick = () => {
        if(r.files.length) { r.upload(); document.getElementById('pw').style.display = 'block'; document.getElementById('upBtn').disabled = true; notify("Đang tải lên..."); }
        else notify("Bạn chưa chọn file!");
    };
    r.on('progress', () => {
        const p = Math.floor(r.progress() * 100);
        document.getElementById('pb').style.width = p + '%';
        document.getElementById('pt').innerText = p + '%';
    });
    r.on('fileSuccess', () => { notify("Thành công!"); setTimeout(() => location.reload(), 800); });
</script>
</body>
</html>
