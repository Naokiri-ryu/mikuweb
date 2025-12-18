<?php
session_start();

// Jika sudah login, lempar ke Home
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// --- KONEKSI DATABASE ---
$conn = new mysqli('localhost', 'root', '', 'miku_project');

// Cek Koneksi Database
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

$pesan = "";
$tipe_pesan = "";

// --- LOGIKA REGISTER & LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. JIKA TOMBOL DAFTAR
    if (isset($_POST['daftar'])) {
        $user = $_POST['username'];
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Cek username duplikat dulu
        $check = $conn->query("SELECT * FROM users WHERE username='$user'");
        if ($check->num_rows > 0) {
            $pesan = "❌ Username sudah terpakai!";
            $tipe_pesan = "danger";
        } else {
            $sql = "INSERT INTO users (username, password) VALUES ('$user', '$pass')";
            if ($conn->query($sql) === TRUE) {
                $pesan = "✅ Akun berhasil dibuat! Silakan Login di atas.";
                $tipe_pesan = "success";
            } else {
                $pesan = "❌ Gagal membuat akun.";
                $tipe_pesan = "danger";
            }
        }
    }
    
    // 2. JIKA TOMBOL LOGIN
    elseif (isset($_POST['login'])) {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        
        $result = $conn->query("SELECT * FROM users WHERE username='$user'");
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                include "logger.php?event=LOGIN_SUCCESS";
                header("Location: home.php");
                exit();
            } else {
                $pesan = "❌ Password Salah!";
                include "logger.php?event=LOGIN_FAILED";
                $tipe_pesan = "danger";
            }
        } else {
            $pesan = "❌ Username tidak ditemukan.";
            $tipe_pesan = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miku Server - Login</title>
    <style>
        :root {
            --miku-cyan: #39c5bb;
            --miku-dark: #263238;
        }
        body {
            background-color: #e0f7fa;
            font-family: sans-serif;
            background-image: url('https://w.wallhaven.cc/full/wq/wallhaven-wqvvr6.jpg');
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
            text-align: center;
        }
        .card-header {
            background: var(--miku-cyan);
            color: white;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        input {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            width: 95%;
            padding: 10px;
            border: none;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-login {
            background-color: var(--miku-cyan);
            color: white;
        }
        .btn-login:hover { background-color: #2da8a0; }
        
        .btn-register {
            background-color: #555;
            color: white;
            margin-top: 20px;
        }
        .btn-register:hover { background-color: #333; }

        .divider {
            margin: 20px 0;
            border-top: 1px solid #ddd;
        }
        .alert {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            MIKU BEAM SERVER
            <div style="font-size: 0.8rem; font-weight: normal;">Secure Access Gateway</div>
        </div>

        <div class="card-body">
            <?php if($pesan): ?>
                <div class="alert alert-<?php echo $tipe_pesan; ?>">
                    <?php echo $pesan; ?>
                </div>
            <?php endif; ?>

            <h3>🔓 LOGIN</h3>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login" class="btn-login">MASUK SEKARANG</button>
            </form>

            <div class="divider"></div>

            <h5>Belum punya akun?</h5>
            <form method="POST">
                <input type="text" name="username" placeholder="Username Baru" required>
                <input type="password" name="password" placeholder="Password Baru" required>
                <button type="submit" name="daftar" class="btn-register">DAFTAR AKUN BARU 📝</button>
            </form>

        </div>
    </div>
</div>

</body>
</html>