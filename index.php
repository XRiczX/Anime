<?php
// --- KONFIGURASI ---
// Folder dasar (relative terhadap file ini)
$base_dir = 'anime'; 
// Judul Tab
$app_title = "XRiczX Explorer";

// --- BACKEND PHP (API) ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $path = isset($_POST['path']) ? $_POST['path'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $destination = isset($_POST['destination']) ? $_POST['destination'] : '';

    if (strpos($path, '..') !== false || strpos($destination, '..') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
        exit;
    }

    $real_path = __DIR__ . '/' . $path;
    
    // 1. LIST FILE
    if ($action == 'list') {
        $files = [];
        if (is_dir($real_path)) {
            $items = scandir($real_path);
            foreach ($items as $item) {
                if ($item == '.' || $item == '..') continue;
                $full_path = $real_path . '/' . $item;
                $web_path = $path . '/' . $item;
                $is_dir = is_dir($full_path);
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                
                $type = 'file';
                if ($is_dir) $type = 'folder';
                elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $type = 'image';
                elseif (in_array($ext, ['mp4', 'mkv', 'webm', 'avi', 'mov'])) $type = 'video';
                elseif ($ext == 'pdf') $type = 'pdf';
                elseif (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) $type = 'office';

                $files[] = [
                    'name' => $item,
                    'path' => $web_path,
                    'type' => $type,
                    'size' => $is_dir ? '-' : formatSize(filesize($full_path)),
                    'date' => date("Y-m-d H:i", filemtime($full_path))
                ];
            }
        }
        echo json_encode(['status' => 'success', 'data' => $files]);
        exit;
    }

    // 2. RENAME
    if ($action == 'rename') {
        $new_path = dirname($real_path) . '/' . $name;
        if (rename($real_path, $new_path)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal rename. Cek permission.']);
        }
        exit;
    }

    // 3. COPY / PASTE
    if ($action == 'paste') {
        $source = __DIR__ . '/' . $_POST['source'];
        $dest = $real_path . '/' . basename($source);
        if (copy($source, $dest)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal copy.']);
        }
        exit;
    }
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $app_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #202020;
            --sidebar-bg: #191919;
            --header-bg: #2c2c2c;
            --item-hover: rgba(255, 255, 255, 0.08);
            --item-selected: rgba(255, 255, 255, 0.15);
            --text-main: #ffffff;
            --text-sub: #a0a0a0;
            --accent: #60cdff;
            --menu-bg: #2b2b2b;
            --border: #383838;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
            -webkit-user-select: none; /* Disable text select on iOS */
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 10px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 10px 15px;
            font-size: 18px;
            font-weight: bold;
            color: var(--text-main);
            border-bottom: 1px solid var(--border);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sidebar-item {
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--text-sub);
            font-size: 15px;
            display: flex;
            align-items: center;
        }
        .sidebar-item:hover { background-color: var(--item-hover); color: white; }
        .sidebar-item i { margin-right: 15px; width: 20px; text-align: center; font-size: 18px; }

        /* --- MAIN CONTENT --- */
        .main { flex: 1; display: flex; flex-direction: column; position: relative; width: 100%; }

        /* --- TOOLBAR --- */
        .toolbar {
            background-color: var(--header-bg);
            padding: 10px 15px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 15px;
            height: 50px;
        }
        .nav-btn { background: none; border: none; color: white; cursor: pointer; font-size: 18px; padding: 5px; }
        .hamburger-btn { display: none; font-size: 20px; margin-right: 5px; }
        
        .address-bar {
            flex: 1;
            background-color: #111;
            border: 1px solid #444;
            border-radius: 6px;
            padding: 8px 15px;
            font-size: 14px;
            color: white;
            display: flex;
            align-items: center;
            overflow: hidden;
            white-space: nowrap;
        }
        .address-text { overflow: hidden; text-overflow: ellipsis; }

        /* --- FILE GRID --- */
        .file-area {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); /* Responsive Grid */
            gap: 15px;
            align-content: start;
            -webkit-overflow-scrolling: touch; /* Smooth scroll iOS */
        }

        .item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 5px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.1s;
            position: relative;
        }
        .item:active { background-color: var(--item-selected); transform: scale(0.98); }
        
        .item-icon { font-size: 48px; margin-bottom: 10px; }
        .folder-icon { color: #fce100; }
        .file-icon { color: #fff; }
        .video-icon { color: #e50914; }
        .image-icon { color: #60cdff; }
        .pdf-icon { color: #ff4d4d; }
        .office-icon { color: #2b579a; }

        .item-name {
            font-size: 13px;
            text-align: center;
            word-break: break-word;
            line-height: 1.3;
            max-width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #ddd;
        }

        /* --- RESPONSIVE MOBILE & TABLET --- */
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                left: -100%; /* Sembunyikan sidebar ke kiri */
                height: 100%;
                width: 250px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            }
            .sidebar.active {
                transform: translateX(100%); /* Geser masuk */
                left: -250px; /* Reset base position logic */
            }
            .hamburger-btn { display: block; }
            
            .grid-template-columns {
                grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            }
            .address-bar { font-size: 12px; }
            .item-icon { font-size: 40px; }
        }

        /* OVERLAY GELAP SAAT SIDEBAR MUNCUL DI HP */
        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 900;
        }
        .overlay.active { display: block; }

        /* --- CONTEXT MENU --- */
        .context-menu {
            position: fixed; /* Ganti absolute ke fixed agar aman di scroll */
            background-color: rgba(40, 40, 40, 0.95);
            border: 1px solid #555;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            display: none;
            z-index: 2000;
            min-width: 200px;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }
        .menu-item {
            padding: 12px 15px;
            font-size: 14px;
            color: white;
            cursor: pointer;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .menu-item:last-child { border-bottom: none; }
        .menu-item:active { background-color: var(--accent); color: black; }

        /* --- MODAL --- */
        .modal {
            display: none; position: fixed; z-index: 3000;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.9);
            align-items: center; justify-content: center;
        }
        .modal-content {
            width: 95%; max-width: 800px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            max-height: 80vh;
            display: flex; flex-direction: column;
        }
        .modal-header { padding: 15px; background: #222; display: flex; justify-content: space-between; align-items: center; }
        .close-btn { font-size: 24px; padding: 0 10px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span>Server Menu</span>
            <i class="fa-solid fa-times" style="cursor:pointer;" onclick="toggleSidebar()"></i>
        </div>
        <div class="sidebar-item" onclick="loadDir('<?php echo $base_dir; ?>'); toggleSidebar();"><i class="fa-solid fa-house"></i> Home</div>
        <div class="sidebar-item"><i class="fa-solid fa-clock"></i> Recent</div>
        <div class="sidebar-item"><i class="fa-solid fa-cloud"></i> Network</div>
    </div>

    <div class="main">
        <div class="toolbar">
            <button class="nav-btn hamburger-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <button class="nav-btn" onclick="goUp()"><i class="fa-solid fa-arrow-up"></i></button>
            <div class="address-bar">
                <i class="fa-solid fa-hard-drive" style="margin-right: 10px; color: #888;"></i>
                <span id="address-text" class="address-text">Anime</span>
            </div>
            <button class="nav-btn" onclick="location.reload()"><i class="fa-solid fa-rotate-right"></i></button>
        </div>

        <div class="file-area" id="file-list" onclick="hideMenu()">
            </div>
    </div>

    <div id="ctx-menu" class="context-menu">
        <div class="menu-item" onclick="previewFile()"><i class="fa-solid fa-eye"></i> Open / Play</div>
        <div class="menu-item" onclick="copyFile()"><i class="fa-regular fa-copy"></i> Copy</div>
        <div class="menu-item" onclick="pasteFile()"><i class="fa-regular fa-paste"></i> Paste Here</div>
        <div class="menu-item" onclick="renameFile()"><i class="fa-solid fa-i-cursor"></i> Rename</div>
        <div class="menu-item" onclick="downloadFile()"><i class="fa-solid fa-download"></i> Download</div>
        <div class="menu-item" onclick="showProps()"><i class="fa-solid fa-circle-info"></i> Properties</div>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span id="modalTitle" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:80%;">Preview</span>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody" style="display:flex; justify-content:center; background:black; min-height:200px;"></div>
        </div>
    </div>

    <script>
        let currentPath = '<?php echo $base_dir; ?>';
        let selectedFile = null;
        let clipboard = null;
        let longPressTimer; 
        
        document.addEventListener('DOMContentLoaded', () => {
            loadDir(currentPath);
        });

        // --- SIDEBAR LOGIC ---
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        }

        // --- CORE FUNCTIONS ---
        function loadDir(path) {
            currentPath = path;
            document.getElementById('address-text').innerText = path;
            const container = document.getElementById('file-list');
            container.innerHTML = '<div style="color:#888; text-align:center; margin-top:20px;">Loading...</div>';

            const formData = new FormData();
            formData.append('action', 'list');
            formData.append('path', path);

            fetch('', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                container.innerHTML = '';
                if(res.data.length === 0) {
                    container.innerHTML = '<div style="color:#666; text-align:center; margin-top:50px;">Folder Kosong</div>';
                    return;
                }

                res.data.forEach(file => {
                    let icon = 'fa-file';
                    let colorClass = 'file-icon';
                    
                    if (file.type === 'folder') { icon = 'fa-folder'; colorClass = 'folder-icon'; }
                    else if (file.type === 'video') { icon = 'fa-film'; colorClass = 'video-icon'; }
                    else if (file.type === 'image') { icon = 'fa-image'; colorClass = 'image-icon'; }
                    else if (file.type === 'pdf') { icon = 'fa-file-pdf'; colorClass = 'pdf-icon'; }
                    else if (file.type === 'office') { icon = 'fa-file-word'; colorClass = 'office-icon'; }

                    const div = document.createElement('div');
                    div.className = 'item';
                    div.innerHTML = `
                        <div class="item-icon ${colorClass}"><i class="fa-solid ${icon}"></i></div>
                        <div class="item-name">${file.name}</div>
                    `;
                    
                    // --- EVENT LISTENER UNTUK HP (TOUCH) & PC (MOUSE) ---
                    
                    // 1. Desktop Right Click
                    div.oncontextmenu = (e) => {
                        e.preventDefault();
                        showMenu(e.clientX, e.clientY, file);
                    };

                    // 2. Desktop Left Click / Mobile Tap
                    div.onclick = () => {
                        if (file.type === 'folder') loadDir(file.path);
                        else {
                            selectedFile = file;
                            previewFile();
                        }
                    };

                    // 3. Mobile Long Press Logic
                    div.addEventListener('touchstart', (e) => {
                        // Reset timer jika user menyentuh
                        longPressTimer = setTimeout(() => {
                            // Jika tahan 800ms, anggap klik kanan
                            const touch = e.touches[0];
                            showMenu(touch.clientX, touch.clientY, file);
                        }, 800); 
                    }, {passive: true});

                    div.addEventListener('touchend', () => {
                        clearTimeout(longPressTimer); // Batal jika jari diangkat cepat
                    });

                    div.addEventListener('touchmove', () => {
                        clearTimeout(longPressTimer); // Batal jika jari menggeser (scroll)
                    });

                    container.appendChild(div);
                });
            });
        }

        function goUp() {
            if (currentPath.includes('/')) {
                let parts = currentPath.split('/');
                parts.pop();
                loadDir(parts.join('/'));
            }
        }

        // --- CONTEXT MENU LOGIC ---
        function showMenu(x, y, file) {
            selectedFile = file;
            const menu = document.getElementById('ctx-menu');
            
            // Logika agar menu tidak keluar layar (Penting buat HP)
            const w = window.innerWidth;
            const h = window.innerHeight;
            
            if (x + 200 > w) x = w - 210; // Geser ke kiri jika mepet kanan
            if (y + 300 > h) y = h - 310; // Geser ke atas jika mepet bawah
            
            menu.style.display = 'block';
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            
            // Getar dikit di HP kalau support
            if (navigator.vibrate) navigator.vibrate(50); 
        }

        function hideMenu() {
            document.getElementById('ctx-menu').style.display = 'none';
        }

        // --- ACTIONS ---
        function previewFile() {
            hideMenu();
            if (!selectedFile) return;
            const type = selectedFile.type;
            const url = selectedFile.path;
            const modal = document.getElementById('previewModal');
            const body = document.getElementById('modalBody');
            document.getElementById('modalTitle').innerText = selectedFile.name;
            
            body.innerHTML = '';

            if (type === 'video') {
                body.innerHTML = `<video controls autoplay style="width:100%; max-height:70vh;"><source src="${url}" type="video/mp4">Not Supported</video>`;
                modal.style.display = 'flex';
            } else if (type === 'image') {
                body.innerHTML = `<img src="${url}" style="max-width:100%; max-height:70vh; object-fit:contain;" />`;
                modal.style.display = 'flex';
            } else if (type === 'pdf') {
                body.innerHTML = `<iframe src="${url}" style="width:100%; height:70vh; border:none;"></iframe>`;
                modal.style.display = 'flex';
            } else {
                if(confirm("File ini tidak bisa di-preview. Download saja?")) {
                    downloadFile();
                }
            }
        }

        function closeModal() {
            document.getElementById('previewModal').style.display = 'none';
            document.getElementById('modalBody').innerHTML = '';
        }

        function downloadFile() {
            hideMenu();
            const a = document.createElement('a');
            a.href = selectedFile.path;
            a.download = selectedFile.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function renameFile() {
            hideMenu();
            const newName = prompt("Nama baru:", selectedFile.name);
            if (newName && newName !== selectedFile.name) {
                const formData = new FormData();
                formData.append('action', 'rename');
                formData.append('path', selectedFile.path);
                formData.append('name', newName);

                fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if(res.status === 'success') loadDir(currentPath);
                    else alert(res.message);
                });
            }
        }

        function copyFile() {
            hideMenu();
            clipboard = selectedFile.path;
            alert("Disalin! Pindah ke folder lain dan pilih Paste.");
        }

        function pasteFile() {
            hideMenu();
            if (!clipboard) return alert("Clipboard kosong!");
            
            const formData = new FormData();
            formData.append('action', 'paste');
            formData.append('source', clipboard);
            formData.append('destination', currentPath);

            fetch('', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') loadDir(currentPath);
                else alert("Gagal paste.");
            });
        }
        
        function showProps() {
            hideMenu();
            alert(`File: ${selectedFile.name}\nSize: ${selectedFile.size}\nDate: ${selectedFile.date}`);
        }
    </script>
</body>
</html>
