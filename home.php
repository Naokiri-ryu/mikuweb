<?php
session_start();
$EVENT = "ACCESS_HOME";
include "logger.php";


// --- 1. KEAMANAN SESI ---
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// --- 2. LOGIKA UPLOAD & SECURITY CHECK ---
$target_dir = "uploads/";
$message = "";

if (isset($_POST["submit"])) {
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $fileName = basename($_FILES["fileToUpload"]["name"]);
    $target_file = $target_dir . $fileName;
    $uploadOk = 1;

    // Limit 15MB
    if ($_FILES["fileToUpload"]["size"] > 15000000) {
        $message = "<div class='alert alert-danger'>❌ File terlalu besar (>15MB). Gagal Upload.</div>";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            // HITUNG HASH (INTEGRITY CHECK)
            $hash = hash_file('sha256', $target_file);
            $size = $_FILES["fileToUpload"]["size"];
            $type = mime_content_type($target_file);

            $EVENT = "UPLOAD";
            $FILE  = $fileName;
            $SIZE  = $_FILES["fileToUpload"]["size"];
            $TYPE  = mime_content_type($target_file);
            $HASH  = $hash;

include "logger.php";

            $message = "<div class='alert alert-success'>
                            <strong>✅ SUKSES!</strong> File <b>$fileName</b> berhasil diamankan.<br>
                            🔐 <b>Integrity Hash (SHA-256):</b> <small>$hash</small>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Error saat memindahkan file.</div>";             
            include "logger.php?event=UPLOAD_FAILED&file=$fileName";
        }
    }
}

// --- 3. LOGIKA PENGELOMPOKAN FILE (SMART SORTING) ---
$files_img = [];
$files_doc = [];
$files_music = [];
$files_other = [];

if (is_dir($target_dir)){
    $files = scandir($target_dir);
    foreach($files as $file) {
        if($file !== "." && $file !== "..") {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $path = "uploads/" . $file;
            if (!is_file($path)) continue;
            
            // Hitung ukuran file (KB/MB)
            $size_bytes = filesize($path);
            $size_str = ($size_bytes > 1048576) ? round($size_bytes/1048576, 2)." MB" : round($size_bytes/1024, 2)." KB";

            // Hitung Hash untuk tabel (Keamanan)
            $file_hash = hash_file('sha256', $path);
            $short_hash = substr($file_hash, 0, 15) . "..."; // Tampilkan sedikit saja biar rapi

            $data = ['name' => $file, 'path' => $path, 'size' => $size_str, 'hash' => $file_hash, 'short_hash' => $short_hash];

            if(in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $files_img[] = $data;
            } elseif(in_array($ext, ['pdf','doc','docx','xls','xlsx','ppt','txt'])) {
                $files_doc[] = $data;
            } elseif(in_array($ext, ['mp3','wav','ogg'])) {
                $files_music[] = $data;
            } else {
                $files_other[] = $data;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miku Beam - Data Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --miku-cyan: #39c5bb;
            --miku-dark: #263238;
            --miku-pink: #ff00cc;
            --bg-light: #e0f7fa;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Roboto', sans-serif;
            background-image: url('https://w.wallhaven.cc/full/wq/wallhaven-wqvvr6.jpg'); /* Background Miku Opsional */
            background-size: cover;
            background-attachment: fixed;
            background-blend-mode: overlay;
        }

        /* --- THEME CUSTOMIZATION --- */
        .navbar {
            background: rgba(38, 50, 56, 0.95) !important;
            border-bottom: 3px solid var(--miku-cyan);
            backdrop-filter: blur(10px);
        }
        .navbar-brand {
            font-family: 'Orbitron', sans-serif;
            color: var(--miku-cyan) !important;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(57, 197, 187, 0.5);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(4px);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: var(--miku-cyan);
            color: white;
            font-family: 'Orbitron', sans-serif;
            border-radius: 15px 15px 0 0 !important;
            font-weight: bold;
        }

        .btn-miku {
            background-color: var(--miku-cyan);
            color: white;
            border: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-miku:hover {
            background-color: #2da8a0;
            color: white;
            box-shadow: 0 0 15px var(--miku-cyan);
        }

        .nav-tabs .nav-link { color: var(--miku-dark); font-weight: bold; }
        .nav-tabs .nav-link.active {
            color: var(--miku-cyan);
            border-bottom: 3px solid var(--miku-cyan);
        }
        
        .hash-code {
            font-family: monospace;
            font-size: 0.8rem;
            color: #666;
            background: #eee;
            padding: 2px 5px;
            border-radius: 4px;
        }

        /* Gallery Grid */
        .gallery-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: 0.3s;
        }
        .gallery-img:hover {
            border-color: var(--miku-cyan);
            transform: scale(1.02);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">🎹 MIKU BEAM SERVER</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-md-block">User: <b style="color:var(--miku-pink)"><?php echo htmlspecialchars($username); ?></b></span>
                <a href="logout.php" class="btn btn-sm btn-danger rounded-pill px-3">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px;">
        
        <?php echo $message; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">📤 UPLOAD CENTER</div>
                    <div class="card-body">
                        <p class="small text-muted">File akan divalidasi dengan SHA-256 Checksum.</p>
                        <form action="home.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" class="form-control" name="fileToUpload" id="fileToUpload" required>
                            </div>
                            <button type="submit" name="submit" class="btn btn-miku w-100">📡 TRANSMIT DATA</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3 text-center">
                    <div class="card-body">
                        <h6 class="text-muted">STATUS KEAMANAN</h6>
                        <h3 style="color:var(--miku-cyan)">UNENCRYPTED</h3>
                        <small>Protokol HTTP aktif. Integritas data dijaga oleh Hash Verification.</small>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark">📂 DATA REPOSITORY</div>
                    <div class="card-body">
                        
                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#gallery">🖼️ Galeri</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#docs">📄 Dokumen</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#music">🎵 Musik</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#others">📦 Lainnya</button></li>
                        </ul>

                        <div class="tab-content">
                            
                            <div class="tab-pane fade show active" id="gallery">
                                <div class="row">
                                    <?php if(empty($files_img)) echo "<p class='text-muted text-center py-4'>Belum ada gambar.</p>"; ?>
                                    <?php foreach($files_img as $f): ?>
                                    <div class="col-6 col-md-4 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <a href="<?php echo $f['path']; ?>" target="_blank">
                                                <img src="<?php echo $f['path']; ?>" class="gallery-img p-2">
                                            </a>
                                            <div class="card-body p-2 text-center">
                                                <small class="d-block text-truncate"><?php echo $f['name']; ?></small>
                                                <span class="badge bg-light text-dark mb-2"><?php echo $f['size']; ?></span>
                                                <br>
                                                <a href="<?php echo $f['path']; ?>" download class="btn btn-sm btn-outline-info w-100">⬇️ Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="docs">
                                <table class="table table-hover">
                                    <thead><tr><th>Nama File</th><th>Integrity (Hash)</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <?php foreach($files_doc as $f): ?>
                                        <tr>
                                            <td>
                                                <b><?php echo $f['name']; ?></b><br>
                                                <small class="text-muted"><?php echo $f['size']; ?></small>
                                            </td>
                                            <td><span class="hash-code" title="<?php echo $f['hash']; ?>"><?php echo $f['short_hash']; ?></span></td>
                                            <td><a href="<?php echo $f['path']; ?>" download class="btn btn-sm btn-miku">⬇️ Unduh</a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if(empty($files_doc)) echo "<p class='text-muted text-center'>Kosong.</p>"; ?>
                            </div>

                            <div class="tab-pane fade" id="music">
                                <?php foreach($files_music as $f): ?>
                                <div class="d-flex align-items-center border-bottom py-2">
                                    <div class="me-3">🎵</div>
                                    <div class="flex-grow-1">
                                        <b><?php echo $f['name']; ?></b>
                                        <audio controls class="w-100 mt-1" style="height: 30px;">
                                            <source src="<?php echo $f['path']; ?>" type="audio/mpeg">
                                        </audio>
                                    </div>
                                    <div class="ms-3">
                                        <a href="<?php echo $f['path']; ?>" download class="btn btn-sm btn-secondary">⬇️</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="tab-pane fade" id="others">
                                <ul class="list-group">
                                    <?php foreach($files_other as $f): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo $f['name']; ?>
                                        <a href="<?php echo $f['path']; ?>" download class="btn btn-sm btn-secondary">⬇️ Download</a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                        </div> </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>