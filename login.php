<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $status);
    
    if ($stmt->fetch()) {
        if ($status === 'Banned') {
            $error = "🚫 Access Denied: Account Banned";
        } elseif (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $error = "❌ Invalid Password";
        }
    } else {
        $error = "❌ User not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Login</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh;">

    <div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 15px; z-index: 10;">
        <button class="theme-toggle" onclick="toggleTheme()" title="Switch Theme">
            <i class="fas fa-sun"></i>
        </button>
        <a href="admin.php" class="theme-toggle" style="color:var(--danger); border-color:var(--danger); text-decoration:none;" title="Admin Portal">
            <i class="fas fa-user-shield"></i>
        </a>
    </div>

    <div class="stat-card" style="width:100%; max-width:400px; padding:40px; text-align:left;">
        <h1 style="text-align:center; margin-bottom:10px;">GAMER'S <span style="color:var(--accent)">LOGIN</span></h1>
        <p style="text-align:center; color:var(--text-muted); margin-bottom:30px; font-size:0.9rem;">Enter the Arena</p>
        
        <?php if(isset($error)) echo "<div style='background:rgba(255, 51, 51, 0.1); color:#ff3333; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #ff3333;'>$error</div>"; ?>
        <?php if(isset($_SESSION['success'])): ?>
            <div style="background:rgba(0, 255, 136, 0.1); color:#00ff88; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #00ff88;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label style="color:var(--text-muted); font-size:0.8rem;">Username</label>
            <input type="text" name="username" required placeholder="Enter Username">
            
            <label style="color:var(--text-muted); font-size:0.8rem;">Password</label>
            <input type="password" name="password" required placeholder="Enter Your Password">
            
            <button type="submit" name="login" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px;">
                SIGN IN
            </button>
        </form>

        <div style="text-align:center; margin-top:20px; padding-top:20px; border-top:1px solid var(--card-border);">
            <p style="color:var(--text-muted); font-size:0.9rem;">New in Efootball league?</p>
            <a href="register.php" class="btn btn-outline" style="font-size:0.8rem;">CREATE ACCOUNT</a>
        </div>
    </div>

    <script src="theme.js"></script>
</body>
</html>
