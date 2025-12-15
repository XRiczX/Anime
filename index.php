<?php
// --- KONFIGURASI ---
// Folder dasar (relative terhadap file ini)
$base_dir = 'anime'; 
// Judul Tab
$app_title = "Explorer Pi";

// --- BACKEND PHP (API) ---
// Menangani request AJAX dari JavaScript
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $path = isset($_POST['path']) ? $_POST['path'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $destination = isset($_POST['destination']) ? $_POST['destination'] : '';

    // Security Check: Mencegah akses ke folder sistem (../)
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
                
                // Tentukan Tipe File
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

    // 3. COPY / PASTE (Sederhana)
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

// Fungsi Format Size
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- GAYA WINDOWS 11 DARK MODE --- */
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
            font-family: 'Segoe UI Variable', 'Segoe UI', sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
            user-select: none; /* Mencegah blok teks biru */
        }

        /* SIDEBAR */
        .sidebar {
            width: 200px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 10px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-item {
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            color: var(--text-sub);
            font-size: 14px;
        }
        .sidebar-item:hover { background-color: var(--item-hover); color: white; }
        .sidebar-item i { margin-right: 10px; width: 20px; text-align: center; }

        /* MAIN CONTENT */
        .main { flex: 1; display: flex; flex-direction: column; }

        /* ADDRESS BAR */
        .toolbar {
            background-color: var(--bg-color);
            padding: 10px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-btn { background: none; border: none; color: white; cursor: pointer; font-size: 16px; }
        .address-bar {
            flex: 1;
            background-color: #333;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 13px;
            color: white;
            display: flex;
            align-items: center;
        }

        /* FILE GRID AREA */
        .file-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
            align-content: start;
        }

        /* FILE ITEM */
        .item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: 0.1s;
        }
        .item:hover { background-color: var(--item-hover); }
        .item.active { background-color: var(--item-selected); border: 1px solid rgba(255,255,255,0.2); }
        
        .item-icon { font-size: 42px; margin-bottom: 8px; position: relative; }
        .folder-icon { color: #fce100; }
        .file-icon { color: #fff; }
        .video-icon { color: #e50914; }
        .image-icon { color: #60cdff; }
        .pdf-icon { color: #ff4d4d; }
        .office-icon { color: #2b579a; }

        .item-name {
            font-size: 12px;
            text-align: center;
            word-break: break-word;
            max-width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* CONTEXT MENU (KLIK KANAN) */
        .context-menu {
            position: absolute;
            background-color: var(--menu-bg);
            border: 1px solid #454545;
            border-radius: 8px;
            padding: 5px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            display: none;
            z-index: 1000;
            min-width: 180px;
            backdrop-filter: blur(10px);
        }
        .menu-item {
            padding: 8px 15px;
            font-size: 13px;
            color: white;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .menu-item:hover { background-color: var(--accent); color: black; }
        .separator { height: 1px; background-color: #454545; margin: 4px 0; }

        /* MODAL PREVIEW */
        .modal {
            display: none; position: fixed; z-index: 2000;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: #222; padding: 0; border-radius: 8px;
            max-width: 90%; max-height: 90%;
            display: flex; flex-direction: column; overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
        }
        .modal-header {
            padding: 10px 20px; background: #333; color: white;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-body {
            display: flex; justify-content: center; align-items: center;
            background: black;
            min-width: 600px; min-height: 400px;
        }
        iframe, video, img { max-width: 100%; max-height: 80vh; }
        .close-btn { cursor: pointer; font-size: 20px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-item" onclick="loadDir('<?php echo $base_dir; ?>')"><i class="fa-solid fa-house"></i> Home</div>
        <div class="sidebar-item"><i class="fa-solid fa-clock"></i> Recent</div>
        <div class="sidebar-item"><i class="fa-solid fa-cloud"></i> Network</div>
    </div>

    <div class="main">
        <div class="toolbar">
            <button class="nav-btn" onclick="goUp()"><i class="fa-solid fa-arrow-up"></i></button>
            <div class="address-bar">
                <i class="fa-solid fa-desktop" style="margin-right: 10px;"></i>
                <span id="address-text">This PC > Anime</span>
            </div>
            <button class="nav-btn" onclick="location.reload()"><i class="fa-solid fa-rotate-right"></i></button>
        </div>

        <div class="file-area" id="file-list" oncontextmenu="showGlobalMenu(event)">
            </div>
    </div>

    <div id="ctx-menu" class="context-menu">
        <div class="menu-item" onclick="previewFile()"><i class="fa-solid fa-eye"></i> Open / Preview</div>
        <div class="separator"></div>
        <div class="menu-item" onclick="copyFile()"><i class="fa-regular fa-copy"></i> Copy</div>
        <div class="menu-item" onclick="pasteFile()"><i class="fa-regular fa-paste"></i> Paste</div>
        <div class="separator"></div>
        <div class="menu-item" onclick="renameFile()"><i class="fa-solid fa-i-cursor"></i> Rename</div>
        <div class="menu-item" onclick="downloadFile()"><i class="fa-solid fa-download"></i> Download</div>
        <div class="separator"></div>
        <div class="menu-item" onclick="showProps()"><i class="fa-solid fa-circle-info"></i> Properties</div>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span id="modalTitle">Preview</span>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                </div>
        </div>
    </div>

    <script>
        // CONFIG
        let currentPath = '<?php echo $base_dir; ?>';
        let selectedFile = null; // Object file yang sedang diklik kanan
        let clipboard = null; // Untuk copy paste
        
        // INIT
        document.addEventListener('DOMContentLoaded', () => {
            loadDir(currentPath);
            
            // Hide menu on click elsewhere
            document.addEventListener('click', () => {
                document.getElementById('ctx-menu').style.display = 'none';
            });
        });

        // 1. LOAD DIRECTORY
        function loadDir(path) {
            currentPath = path;
            document.getElementById('address-text').innerText = 'This PC > ' + path;
            const container = document.getElementById('file-list');
            container.innerHTML = '<div style="text-align:center; width:100%; color:#888;">Loading...</div>';

            const formData = new FormData();
            formData.append('action', 'list');
            formData.append('path', path);

            fetch('', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                container.innerHTML = '';
                if(res.data.length === 0) {
                    container.innerHTML = '<div style="text-align:center; width:100%; color:#888;">Folder Kosong</div>';
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
                    
                    // Left Click (Buka Folder / Preview)
                    div.onclick = (e) => {
                        e.stopPropagation();
                        if (file.type === 'folder') {
                            loadDir(file.path);
                        } else {
                            selectedFile = file;
                            previewFile();
                        }
                    };

                    // Right Click (Context Menu)
                    div.oncontextmenu = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        selectedFile = file;
                        showMenu(e.pageX, e.pageY);
                    };

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

        // 2. CONTEXT MENU
        function showMenu(x, y) {
            const menu = document.getElementById('ctx-menu');
            menu.style.display = 'block';
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
        }

        function showGlobalMenu(e) {
            // Menu jika klik kanan di area kosong (Paste option)
            e.preventDefault();
            const menu = document.getElementById('ctx-menu');
            // Hide file specific options here if needed, but for simplicity we keep basic
        }

        // 3. ACTIONS
        function previewFile() {
            if (!selectedFile) return;
            const type = selectedFile.type;
            const url = selectedFile.path; // Path relative untuk browser
            const modal = document.getElementById('previewModal');
            const body = document.getElementById('modalBody');
            const title = document.getElementById('modalTitle');

            title.innerText = selectedFile.name;
            body.innerHTML = '';

            if (type === 'video') {
                body.innerHTML = `<video controls autoplay style="width:100%"><source src="${url}" type="video/mp4">Browser not supported</video>`;
                modal.style.display = 'flex';
            } else if (type === 'image') {
                body.innerHTML = `<img src="${url}" />`;
                modal.style.display = 'flex';
            } else if (type === 'pdf') {
                body.innerHTML = `<iframe src="${url}" width="100%" height="600px" style="border:none;"></iframe>`;
                modal.style.display = 'flex';
            } else if (type === 'office') {
                alert("File Office tidak bisa dipreview di Local Server tanpa Internet. File akan didownload.");
                downloadFile();
            } else {
                alert("Tipe file ini tidak bisa dipreview. Silakan download.");
            }
        }

        function closeModal() {
            document.getElementById('previewModal').style.display = 'none';
            document.getElementById('modalBody').innerHTML = ''; // Stop video
        }

        function downloadFile() {
            const a = document.createElement('a');
            a.href = selectedFile.path;
            a.download = selectedFile.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function renameFile() {
            const newName = prompt("Rename file menjadi:", selectedFile.name);
            if (newName && newName !== selectedFile.name) {
                const formData = new FormData();
                formData.append('action', 'rename');
                formData.append('path', selectedFile.path); // path lama
                formData.append('name', newName); // nama baru

                fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if(res.status === 'success') loadDir(currentPath);
                    else alert(res.message);
                });
            }
        }

        function copyFile() {
            clipboard = selectedFile.path;
            alert("File disalin ke clipboard!");
        }

        function pasteFile() {
            if (!clipboard) {
                alert("Tidak ada file di clipboard!");
                return;
            }
            if (confirm("Paste file di sini?")) {
                const formData = new FormData();
                formData.append('action', 'paste');
                formData.append('source', clipboard);
                formData.append('destination', currentPath);

                fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if(res.status === 'success') loadDir(currentPath);
                    else alert("Gagal paste: " + res.message);
                });
            }
        }

        function showProps() {
            alert(`Nama: ${selectedFile.name}\nUkuran: ${selectedFile.size}\nTanggal: ${selectedFile.date}`);
        }

    </script>
</body>
</html>
