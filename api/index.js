import { sql } from '@vercel/postgres';

export default async function handler(req, res) {
    try {
        // 1. Lấy dữ liệu từ Postgres (Tương đương phần thống kê và truy vấn trong PHP cũ)
        const { rows: files } = await sql`SELECT * FROM files ORDER BY created_at DESC LIMIT 40`;
        const { rows: stats } = await sql`SELECT COUNT(*) as count, SUM(file_size) as size FROM files`;
        
        const totalMedia = stats[0].count || 0;
        const totalSizeBytes = stats[0].size || 0;
        const sizeMB = (totalSizeBytes / (1024 * 1024)).toFixed(1);

        // 2. Giao diện HTML & CSS (Giữ nguyên từ index.php của bạn)
        const htmlContent = `
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
        body { background: var(--bg); color: var(--text); padding: 15px 15px 80px 15px; min-height: 100vh; }
        .container { width: 100%; max-width: 450px; margin: 0 auto; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .header h1 { font-size: 22px; font-weight: 800; }
        .header h1 span { color: var(--primary); }
        .avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid var(--border); }
        .stats { display: flex; gap: 8px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: var(--card-bg); border: 1px solid var(--border); padding: 15px; border-radius: 20px; text-align: center; }
        .stat-val { font-size: 18px; font-weight: 800; color: #fff; }
        .upload-card { background: var(--card-bg); border: 1px solid var(--border); padding: 15px; border-radius: 20px; margin-bottom: 25px; }
        .drop-zone { border: 2px dashed var(--border); padding: 20px; border-radius: 15px; text-align: center; cursor: pointer; display: block; margin-bottom: 12px; }
        .btn-up { width: 100%; background: var(--primary); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: bold; cursor: pointer; }
        .prog-wrap { margin-top: 12px; display: none; }
        .prog-bar-bg { width: 100%; background: rgba(255,255,255,0.05); border-radius: 10px; height: 6px; overflow: hidden; }
        .prog-bar-fill { width: 0%; height: 100%; background: var(--primary); transition: width 0.2s; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
        .item-wrapper { position: relative; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: var(--card-bg); }
        .item img { width: 100%; height: 100%; object-fit: cover; }
        .del-btn { position: absolute; top: 1px; right: 1px; background: var(--danger); color: white; border: none; width: 18px; height: 18px; border-radius: 4px; z-index: 5; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; }
        #toast { position: fixed; top: 20px; right: -300px; background: var(--primary); color: white; padding: 10px 18px; border-radius: 10px 0 0 10px; transition: 0.4s; z-index: 10000; font-size: 12px; font-weight: 600; }
        #toast.show { right: 0; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(17,17,24,0.8); backdrop-filter: blur(15px); border-top: 1px solid var(--border); display: flex; justify-content: space-around; padding: 12px 0; z-index: 5000; }
        .nav-item { color: #888; text-decoration: none; text-align: center; font-size: 10px; font-weight: 600; }
        .nav-item.active { color: var(--primary); }
    </style>
</head>
<body>
    <div id="toast"><span id="t-msg">Thông báo</span></div>
    <div class="container">
        <div class="header">
            <h1>Hải Đăng <span>CloudX</span></h1>
            <div class="avatar">H</div>
        </div>
        <div class="stats">
            <div class="stat-box"><small style="font-size:9px; color:#888; font-weight:bold">TỔNG TỆP</small><div class="stat-val" id="c-files" data-target="${totalMedia}">0</div></div>
            <div class="stat-box"><small style="font-size:9px; color:#888; font-weight:bold">DUNG LƯỢNG</small><div class="stat-val"><span id="c-size" data-target="${sizeMB}">0</span><small style="font-size:10px"> MB</small></div></div>
        </div>
        <div class="upload-card">
            <label for="fInp" class="drop-zone">
                <input type="file" id="fInp" style="display:none">
                <span class="material-symbols-outlined" style="font-size:30px; color:var(--primary)">cloud_upload</span>
                <p id="info" style="font-size:11px; margin-top:8px">Chọn Video / Ảnh</p>
            </label>
            <button class="btn-up" id="upBtn">TẢI LÊN NGAY</button>
            <div class="prog-wrap" id="pw">
                <div class="prog-bar-bg"><div class="prog-bar-fill" id="pb"></div></div>
                <div class="prog-text" id="pt" style="text-align:right; font-size:10px; margin-top:4px; color:var(--primary); font-weight:bold">0%</div>
            </div>
        </div>
        <div class="grid">
            ${files.map(f => \`
                <div class="item-wrapper">
                    <button class="del-btn" onclick="deleteFile('\${f.uuid}')">×</button>
                    <a href="\${f.stored_name}" target="_blank" class="item">
                        \${f.mime_type.startsWith('image/') ? \`<img src="\${f.stored_name}" loading="lazy">\` : \`<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:20px">🎬</div>\`}
                    </a>
                </div>
            \`).join('')}
        </div>
    </div>
    <nav class="nav-bar">
        <a href="/" class="nav-item active"><span class="material-symbols-outlined">home</span><br>Trang chủ</a>
        <a href="#" class="nav-item"><span class="material-symbols-outlined">person</span><br>Hồ sơ</a>
    </nav>

    <script type="module">
        import { upload } from 'https://unpkg.com/@vercel/blob/dist/index.browser.js';

        // Hiệu ứng số chạy (CountUp) như file cũ của bạn
        function countUp(id) {
            const el = document.getElementById(id);
            const target = parseFloat(el.dataset.target);
            let count = 0;
            const update = () => {
                count += target / 60;
                if(count < target) { el.innerText = target % 1 === 0 ? Math.floor(count) : count.toFixed(1); requestAnimationFrame(update); }
                else el.innerText = target;
            };
            update();
        }
        window.onload = () => { countUp('c-files'); countUp('c-size'); };

        function notify(msg) {
            const t = document.getElementById('toast'), m = document.getElementById('t-msg');
            m.innerText = msg; t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2500);
        }

        window.deleteFile = async (uuid) => {
            if(!confirm("Xóa tệp này?")) return;
            const res = await fetch('/api/delete?uuid=' + uuid);
            if(res.ok) { notify("Đã xóa!"); setTimeout(() => location.reload(), 600); }
        };

        const fInp = document.getElementById('fInp'), upBtn = document.getElementById('upBtn');
        fInp.onchange = () => { if(fInp.files[0]) document.getElementById('info').innerText = fInp.files[0].name; };

        upBtn.onclick = async () => {
            if(!fInp.files.length) return notify("Chưa chọn file!");
            upBtn.disabled = true;
            document.getElementById('pw').style.display = 'block';

            try {
                const file = fInp.files[0];
                const blob = await upload(file.name, file, {
                    access: 'public',
                    handleUploadUrl: '/api/upload-token',
                    onProgress: (p) => {
                        document.getElementById('pb').style.width = p.percentage + '%';
                        document.getElementById('pt').innerText = Math.round(p.percentage) + '%';
                    }
                });

                await fetch('/api/save-metadata', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
        \`;

        res.setHeader('Content-Type', 'text/html');
        return res.status(200).send(htmlContent);
    } catch (error) {
        console.error("Lỗi:", error);
        return res.status(500).send("Lỗi Server: " + error.message);
    }
}
