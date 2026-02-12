<?php
session_start();
require 'db.php';

// Set Timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

if (isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $phone = trim($_POST['reg_phone']);
    $pass1 = $_POST['reg_pass'];
    $pass2 = $_POST['reg_confirm'];

    if ($pass1 !== $pass2) {
        $error = "❌ Passwords do not match!";
    } else {
        // 1. Check if Username Exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "❌ Username already taken!";
        } else {
            // 2. Create New User ONLY (No Matches)
            $hashed = password_hash($pass1, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, phone, password, status) VALUES (?, ?, ?, 'Active')");
            $stmt->bind_param("sss", $username, $phone, $hashed);
            
            if ($stmt->execute()) {
                // Success! No matches generated.
                $_SESSION['success'] = "Registration successful! Please wait for the Admin to schedule your matches.";
                header("Location: login.php");
                exit;
            } else {
                $error = "❌ Database error.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>League Registration</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh;">

    <div style="position: absolute; top: 20px; right: 20px; z-index: 10;">
        <button class="theme-toggle" onclick="toggleTheme()" title="Switch Theme">
            <i class="fas fa-sun"></i>
        </button>
    </div>

    <div class="stat-card" style="width:100%; max-width:400px; padding:40px; text-align:left;">
        <h1 style="text-align:center; margin-bottom:10px; font-family:'Rajdhani', sans-serif;">JOIN THE <span style="color:var(--accent)">LEAGUE</span></h1>
        <p style="text-align:center; color:var(--text-muted); margin-bottom:30px; font-size:0.9rem;">Create your league account</p>

        <?php if(isset($error)): ?>
            <div style="background:rgba(255, 51, 51, 0.1); color:#ff3333; padding:12px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #ff3333; font-size:0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:15px;">
                <label style="color:var(--text-muted); font-size:0.8rem; display:block; margin-bottom:5px;">Username</label>
                <div style="position:relative;">
                    <i class="fas fa-user" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#666;"></i>
                    <input type="text" name="reg_username" required placeholder="Exact Efootball Name" style="padding-left:35px; width:100%; box-sizing:border-box;">
                </div>
            </div>

            <div style="margin-bottom:15px;">
                <label style="color:var(--text-muted); font-size:0.8rem; display:block; margin-bottom:5px;">Phone Number</label>
                <div style="position:relative;">
                    <i class="fas fa-phone" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#666;"></i>
                    <input type="tel" name="reg_phone" required placeholder="07..." pattern="[0-9]+" style="padding-left:35px; width:100%; box-sizing:border-box;">
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-bottom:20px;">
                <div style="flex:1;">
                    <label style="color:var(--text-muted); font-size:0.8rem; display:block; margin-bottom:5px;">Password</label>
                    <input type="password" name="reg_pass" required placeholder="Create" style="width:100%; box-sizing:border-box;">
                </div>
                <div style="flex:1;">
                    <label style="color:var(--text-muted); font-size:0.8rem; display:block; margin-bottom:5px;">Confirm</label>
                    <input type="password" name="reg_confirm" required placeholder="Repeat" style="width:100%; box-sizing:border-box;">
                </div>
            </div>
            
            <button type="submit" name="register" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px;">
                CREATE ACCOUNT
            </button>
        </form>

        <div style="text-align:center; margin-top:20px; padding-top:20px; border-top:1px solid var(--card-border);">
            <p style="color:var(--text-muted); font-size:0.9rem;">Already have an account?</p>
            <a href="login.php" class="btn btn-outline" style="font-size:0.8rem; width:100%; justify-content:center;">BACK TO LOGIN</a>
        </div>
    </div>

    <script src="theme.js"></script>
</body>
</html>