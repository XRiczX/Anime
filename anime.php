<?php
// KONFIGURASI FOLDER AWAL
// Sesuaikan ini dengan nama shortcut folder anime Anda
$base_dir = 'anime'; 

// Logika Navigasi (Jangan diubah jika tidak paham)
$request_dir = isset($_GET['dir']) ? $_GET['dir'] : $base_dir;

// Mencegah user naik ke folder sistem (Security)
if (strpos($request_dir, '..') !== false || strpos($request_dir, $base_dir) !== 0) {
    $request_dir = $base_dir;
}

// Path fisik di server
$real_path = '/var/www/html/' . $request_dir;

// Ambil daftar file
$files = [];
if (is_dir($real_path)) {
    $files = scandir($real_path);
}

// Fungsi untuk cek ekstensi video
function is_video($file) {
    $video_ext = ['mp4', 'mkv', 'webm', 'avi', 'mov'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($ext, $video_ext);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Anime Server</title>
    <style>
        /* TAMPILAN (CSS) */
        :root {
            --bg-color: #141414;
            --card-bg: #1f1f1f;
            --text-main: #ffffff;
            --text-sub: #b3b3b3;
            --accent: #e50914;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        h1 { text-align: center; color: var(--accent); letter-spacing: 2px; }
        
        /* Breadcrumb (Navigasi Atas) */
        .breadcrumb {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .breadcrumb a {
            color: var(--accent);
            text-decoration: none;
            font-weight: bold;
        }
        .breadcrumb span { color: var(--text-sub); margin: 0 5px; }

        /* Grid Layout */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
        }

        /* Kartu File/Folder */
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: transform 0.2s, background 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 140px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .card:hover {
            transform: scale(1.05);
            background-color: #2a2a2a;
            border: 1px solid var(--accent);
        }
        
        /* Icon Styling */
        .icon { font-size: 40px; margin-bottom: 10px; }
        .folder-icon { color: #f5c518; }
        .video-icon { color: #e50914; }
        .file-name {
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Modal Video Player */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.95);
        }
        .modal-content {
            margin: auto;
            display: block;
            width: 90%;
            max-width: 1000px;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }
        
        /* Tombol Kembali */
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <h1>🎬 STREAMING LOKAL</h1>

    <div class="breadcrumb">
        Lokasi: / 
        <?php
            // Memecah path url untuk membuat navigasi
            $parts = explode('/', $request_dir);
            $path_acc = "";
            foreach ($parts as $part) {
                if(empty($part)) continue;
                $path_acc .= $part . "/";
                // Hilangkan slash terakhir untuk link
                $link_path = rtrim($path_acc, "/");
                echo "<a href='?dir=" . urlencode($link_path) . "'>" . htmlspecialchars($part) . "</a> <span>/</span> ";
            }
        ?>
    </div>

    <?php if ($request_dir !== $base_dir): ?>
        <a href="?dir=<?php echo urlencode(dirname($request_dir)); ?>" class="back-btn">⬅ Kembali</a>
    <?php endif; ?>

    <div class="grid-container">
        <?php
        // Loop membaca file
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue; // Skip dot files

            $file_path = $request_dir . '/' . $file;
            $real_file_path = '/var/www/html/' . $file_path;
            
            // Tampilan Folder
            if (is_dir($real_file_path)) {
                echo "
                <a href='?dir=" . urlencode($file_path) . "' class='card'>
                    <div class='icon folder-icon'>📁</div>
                    <div class='file-name'>" . htmlspecialchars($file) . "</div>
                </a>";
            } 
            // Tampilan Video
            elseif (is_video($file)) {
                // Encode path untuk URL video
                $video_url = implode('/', array_map('rawurlencode', explode('/', $file_path)));
                
                echo "
                <div class='card' onclick=\"playVideo('$video_url', '" . htmlspecialchars($file, ENT_QUOTES) . "')\">
                    <div class='icon video-icon'>▶️</div>
                    <div class='file-name'>" . htmlspecialchars($file) . "</div>
                </div>";
            }
        }
        ?>
    </div>

    <div id="videoModal" class="modal">
        <span class="close" onclick="closeVideo()">&times;</span>
        <div class="modal-content">
            <h2 id="videoTitle" style="color:white; text-align:center; margin-bottom:10px;"></h2>
            <video id="player" controls style="width: 100%; border-radius: 8px;">
                <source src="" type="video/mp4">
                Browser Anda tidak support tag video.
            </video>
        </div>
    </div>

    <script>
        // Script Sederhana untuk Player
        var modal = document.getElementById("videoModal");
        var player = document.getElementById("player");
        var title = document.getElementById("videoTitle");

        function playVideo(url, name) {
            modal.style.display = "block";
            player.src = url; // Load URL video
            title.innerText = name; // Set judul
            player.play(); // Auto play
        }

        function closeVideo() {
            modal.style.display = "none";
            player.pause(); // Stop video
            player.src = ""; // Kosongkan source agar hemat memori
        }

        // Tutup modal jika klik di luar video
        window.onclick = function(event) {
            if (event.target == modal) {
                closeVideo();
            }
        }
    </script>

</body>
</html>
