<?php
// Set Timezone to Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');
session_start();
require 'db.php';

// --- AUTH CHECK ---
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $stmt->bind_result($hash);
        if ($stmt->fetch() && password_verify($pass, $hash)) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php"); exit;
        } else { $error = "Access Denied"; }
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin</title><link rel="stylesheet" href="style.css?v='.time().'"></head><body style="display:flex;justify-content:center;align-items:center;height:100vh;"><div class="stat-card" style="width:100%;max-width:400px;padding:30px;"><h2>ADMIN ACCESS</h2><form method="POST"><input type="text" name="username" placeholder="User" required><input type="password" name="password" placeholder="Pass" required><button name="login" class="btn btn-primary" style="width:100%">LOGIN</button></form></div></body></html>';
    exit;
}

// Z. HARD RESET LEAGUE (The "Fresh Start" Button)
if (isset($_POST['reset_league'])) {
    // 1. Delete ALL matches
    $conn->query("TRUNCATE TABLE matches");
    
    // 2. Reset ALL players to 0
    $conn->query("UPDATE users SET total_matches=0, total_goals=0, goal_diff=0, total_points=0");
    
    $_SESSION['msg'] = "⚠️ SYSTEM WIPED. STARTING FRESH.";
    header("Location: admin.php"); exit;
}

// A. GENERATE SCHEDULE (The "Circle Method" - Guarantees 2 Matches/Day)
if (isset($_POST['generate_schedule'])) {
    
    $start_str = $_POST['start_date'];
    $end_str   = $_POST['end_date'];
    
    if (strtotime($start_str) > strtotime($end_str)) {
        $_SESSION['msg'] = "Error: Invalid Date Range.";
        header("Location: admin.php"); exit;
    }

    // 1. Clear Empty Matches in Range
    $conn->query("DELETE FROM matches WHERE match_date BETWEEN '$start_str' AND '$end_str' AND stats_applied = 0 AND paid_a = 0 AND paid_b = 0");

    // 2. Get Players
    $players = [];
    $q = $conn->query("SELECT id FROM users WHERE status='Active'");
    while($r = $q->fetch_assoc()) $players[] = $r['id'];
    $total_players = count($players);

    if ($total_players < 2) {
        $_SESSION['msg'] = "Error: Need 2+ players.";
    } else {
        $scheduled = 0;
        $current_date = strtotime($start_str);
        $end_date_ts = strtotime($end_str);

        // 3. Loop Through Each Day
        while ($current_date <= $end_date_ts) {
            $date_sql = date('Y-m-d', $current_date);
            
            // SHUFFLE THE CIRCLE
            // By shuffling the order every day, we ensure players meet different opponents.
            shuffle($players);

            // CREATE THE CIRCLE (Everyone plays the person next to them)
            // If order is [A, B, C, D, E]
            // Matches: A-B, B-C, C-D, D-E, E-A
            for ($i = 0; $i < $total_players; $i++) {
                
                // Player 1 is current index
                $p1 = $players[$i];
                
                // Player 2 is next index (wrapping around to 0 at the end)
                $p2_index = ($i + 1) % $total_players;
                $p2 = $players[$p2_index];

                // INSERT MATCH
                // This guarantees exactly 1 Home and 1 Away game for everyone per day.
                // No duplicate checks needed because the circle structure prevents self-play.
                $stmt = $conn->prepare("INSERT INTO matches (player_a_id, player_b_id, match_date, paid_a, paid_b, stats_applied) VALUES (?, ?, ?, 0, 0, 0)");
                $stmt->bind_param("iis", $p1, $p2, $date_sql);
                $stmt->execute();
                
                $scheduled++;
            }

            // Move to next day
            $current_date = strtotime("+1 day", $current_date);
        }
        
        $_SESSION['msg'] = "Schedule Generated: $scheduled matches (Perfect 2/Day Cycle).";
    }
    header("Location: admin.php"); exit;
}

// B. EDIT MATCH
if (isset($_POST['edit_match'])) {
    $mid = $_POST['match_id'];
    $sA = $_POST['score_a']; $sB = $_POST['score_b'];
    $pA = isset($_POST['paid_a']) ? 1 : 0;
    $pB = isset($_POST['paid_b']) ? 1 : 0;

    $imgSQL = "";
    if (!empty($_FILES['new_screenshot']['name'])) {
        $target = "uploads/" . time() . "_" . basename($_FILES["new_screenshot"]["name"]);
        if(move_uploaded_file($_FILES["new_screenshot"]["tmp_name"], $target)) {
            $imgSQL = ", screenshot='" . basename($target) . "'";
        }
    }

    $stats_applied = ($sA !== "" && $sB !== "") ? 1 : 0;
    $conn->query("UPDATE matches SET score_a='$sA', score_b='$sB', paid_a='$pA', paid_b='$pB', stats_applied='$stats_applied' $imgSQL WHERE id=$mid");

    $m = $conn->query("SELECT player_a_id, player_b_id FROM matches WHERE id=$mid")->fetch_assoc();
    recalc_player($conn, $m['player_a_id']);
    recalc_player($conn, $m['player_b_id']);
    
    $_SESSION['msg'] = "Match Updated.";
    header("Location: admin.php"); exit;
}

// C. QUICK VERIFY
if (isset($_POST['quick_verify'])) {
    $mid = $_POST['mid']; $who = $_POST['who'];
    $col = ($who == 'A') ? 'paid_a' : 'paid_b';
    $conn->query("UPDATE matches SET $col = 1 WHERE id=$mid");
    
    $m = $conn->query("SELECT player_a_id, player_b_id FROM matches WHERE id=$mid")->fetch_assoc();
    recalc_player($conn, $m['player_a_id']);
    recalc_player($conn, $m['player_b_id']);

    $_SESSION['msg'] = "Payment Verified!";
    header("Location: admin.php"); exit;
}

// D. DELETE MATCH
if (isset($_POST['delete_match'])) {
    $mid = $_POST['match_id'];
    $m = $conn->query("SELECT player_a_id, player_b_id FROM matches WHERE id=$mid")->fetch_assoc();
    $conn->query("DELETE FROM matches WHERE id=$mid");
    if($m) { recalc_player($conn, $m['player_a_id']); recalc_player($conn, $m['player_b_id']); }
    $_SESSION['msg'] = "Match Deleted.";
    header("Location: admin.php"); exit;
}

// E. USER ACTIONS
if (isset($_GET['action'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'ban') $conn->query("UPDATE users SET status='Banned' WHERE id=$id");
    elseif ($_GET['action'] == 'unban') $conn->query("UPDATE users SET status='Active' WHERE id=$id");
    elseif ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM users WHERE id=$id");
        $conn->query("DELETE FROM matches WHERE player_a_id=$id OR player_b_id=$id");
    }
    $_SESSION['msg'] = "User Action Completed.";
    header("Location: admin.php"); exit;
}

// F. EDIT USER
if (isset($_POST['edit_user'])) {
    $uid = $_POST['user_id'];
    $u = $_POST['username'];
    $p = $_POST['phone'];
    $conn->query("UPDATE users SET username='$u', phone='$p' WHERE id=$uid");
    $_SESSION['msg'] = "User Saved.";
    header("Location: admin.php"); exit;
}

// --- HELPER: RECALCULATE ---
function recalc_player($conn, $uid) {
    $conn->query("UPDATE users SET total_matches=0, total_goals=0, goal_diff=0, total_points=0 WHERE id=$uid");
    
    $qA = $conn->query("SELECT COUNT(*) as m, SUM(score_a) as gf, SUM(score_b) as ga, SUM(CASE WHEN score_a > score_b THEN 3 WHEN score_a = score_b THEN 1 ELSE 0 END) as pts FROM matches WHERE player_a_id=$uid AND stats_applied=1 AND paid_a=1");
    $rA = $qA->fetch_assoc();

    $qB = $conn->query("SELECT COUNT(*) as m, SUM(score_b) as gf, SUM(score_a) as ga, SUM(CASE WHEN score_b > score_a THEN 3 WHEN score_b = score_a THEN 1 ELSE 0 END) as pts FROM matches WHERE player_b_id=$uid AND stats_applied=1 AND paid_b=1");
    $rB = $qB->fetch_assoc();

    $tm = $rA['m'] + $rB['m'];
    $tg = $rA['gf'] + $rB['gf'];
    $gd = ($rA['gf'] + $rB['gf']) - ($rA['ga'] + $rB['ga']);
    $tp = $rA['pts'] + $rB['pts'];

    if ($tm > 0) $conn->query("UPDATE users SET total_matches=$tm, total_goals=$tg, goal_diff=$gd, total_points=$tp WHERE id=$uid");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }
    </script>
</head>
<body>
    <div class="container">
        
        <header class="dashboard-header">
    <div class="logo"><h1>ADMIN <span>CONTROL</span></h1></div>
    <div class="header-actions">
        <button onclick="document.getElementById('scheduleModal').style.display='flex'" class="btn btn-outline" style="border-color:var(--accent); color:var(--accent);">
    <i class="fas fa-calendar-plus"></i> NEW FIXTURES
</button>

        <div class="dropdown" style="display:inline-block; position:relative;">
            <button class="btn btn-primary" onclick="document.getElementById('reportMenu').classList.toggle('show')">
                <i class="fas fa-file-alt"></i> GENERATE REPORT
            </button>
            <div id="reportMenu" class="notif-menu" style="width:200px; right:0;">
                <a href="view_report.php?type=today" target="_blank" style="display:block; padding:10px; color:white; text-decoration:none; border-bottom:1px solid #333;">
                    <i class="fas fa-clock"></i> Today's Report
                </a>
                <a href="view_report.php?type=week" target="_blank" style="display:block; padding:10px; color:white; text-decoration:none; border-bottom:1px solid #333;">
                    <i class="fas fa-calendar-week"></i> Weekly Summary
                </a>
                <a href="view_report.php?type=all" target="_blank" style="display:block; padding:10px; color:white; text-decoration:none;">
                    <i class="fas fa-archive"></i> Full Archive
                </a>
            </div>
        </div>

        <a href="logout.php?type=admin" class="btn btn-primary" style="background:var(--danger);">LOGOUT</a>
    </div>
            <div style="margin-top: 50px; padding: 20px; border: 1px dashed var(--danger); border-radius: 8px; text-align: center;">
    <h3 style="color: var(--danger); margin-top: 0;">⚠️ DANGER ZONE</h3>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
        This will delete ALL matches and reset ALL points to 0. <br>
        Use this only when starting a new season.
    </p>
    <form method="POST" onsubmit="return confirm('ARE YOU SURE? This will delete EVERYTHING and cannot be undone.');">
        <button name="reset_league" class="btn btn-primary" style="background: var(--danger); width: 100%;">
            <i class="fas fa-bomb"></i> RESET LEAGUE & START FRESH
        </button>
    </form>
</div>
</header>

        <?php if(isset($_SESSION['msg'])): ?>
            <div class="stat-card" style="padding:15px; margin-bottom:20px; border-color:var(--success); color:var(--success); display:flex; align-items:center; justify-content:center; gap:10px;">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-grid">
            <div class="admin-card">
                <h2>MATCH MANAGEMENT</h2>
                <?php 
                // Fetch ALL matches
                $q = $conn->query("SELECT m.*, u1.username as u1n, u2.username as u2n FROM matches m JOIN users u1 ON m.player_a_id=u1.id JOIN users u2 ON m.player_b_id=u2.id ORDER BY m.match_date DESC, m.id DESC");
                
                if ($q->num_rows > 0): while($m = $q->fetch_assoc()): 
                    $is_complete = ($m['stats_applied'] == 1 && $m['paid_a'] == 1 && $m['paid_b'] == 1);
                    $border_color = $is_complete ? 'var(--success)' : '#333';
                ?>
                <div class="match-item" style="flex-wrap:wrap; position:relative; padding-right:50px; border:1px solid <?php echo $border_color; ?>; margin-bottom:15px;">
                    
                    <div class="match-details" style="min-width:180px; margin-bottom:10px;">
                        <h3><?php echo htmlspecialchars($m['u1n']); ?> <span style="color:var(--text-muted)">vs</span> <?php echo htmlspecialchars($m['u2n']); ?></h3>
                        <div class="match-meta">
                            <?php echo date('D d M', strtotime($m['match_date'])); ?>
                            <?php if($is_complete): ?><span style="color:var(--success); margin-left:10px;"><i class="fas fa-check"></i> Done</span><?php endif; ?>
                        </div>
                        <div style="font-family:'Rajdhani'; font-size:1.2rem; font-weight:bold; color:var(--accent); margin-top:5px;">
                            <?php echo ($m['score_a'] !== null) ? $m['score_a'] . " - " . $m['score_b'] : "--"; ?>
                        </div>
                    </div>

                    <div style="flex:1; display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="background:rgba(255,255,255,0.05); padding:5px 10px; border-radius:4px; text-align:center; min-width:100px;">
                            <small><?php echo $m['u1n']; ?></small><br>
                            <?php if($m['paid_a']==2): ?>
                                <form method="POST"><input type="hidden" name="mid" value="<?php echo $m['id']; ?>"><input type="hidden" name="who" value="A"><button name="approve_pay" class="btn-sm" style="background:orange; width:100%;">Verify</button></form>
                            <?php elseif($m['paid_a']==1): ?><span style="color:var(--success)">PAID</span>
                            <?php else: ?><span style="color:#555">Unpaid</span><?php endif; ?>
                        </div>
                        <div style="background:rgba(255,255,255,0.05); padding:5px 10px; border-radius:4px; text-align:center; min-width:100px;">
                            <small><?php echo $m['u2n']; ?></small><br>
                            <?php if($m['paid_b']==2): ?>
                                <form method="POST"><input type="hidden" name="mid" value="<?php echo $m['id']; ?>"><input type="hidden" name="who" value="B"><button name="approve_pay" class="btn-sm" style="background:orange; width:100%;">Verify</button></form>
                            <?php elseif($m['paid_b']==1): ?><span style="color:var(--success)">PAID</span>
                            <?php else: ?><span style="color:#555">Unpaid</span><?php endif; ?>
                        </div>
                    </div>

                    <button class="btn-icon" style="position:absolute; right:10px; top:10px; border:none; background:rgba(255,255,255,0.1);" onclick="openMatchEdit(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                        <i class="fas fa-pen"></i>
                    </button>

                </div>
                <?php endwhile; else: ?><p style="color:#666;">No matches found.</p><?php endif; ?>
            </div>
        </div>

        <h3 class="section-title" style="margin-top:40px;">ACTIVE ROSTER</h3>
        <div class="table-wrapper">
            <table class="admin-table">
                <tr><th>User</th><th>Phone</th><th>Pts</th><th>GD</th><th>Status</th><th>Actions</th></tr>
                <?php $users = $conn->query("SELECT * FROM users ORDER BY total_points DESC");
                while($u = $users->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:bold;"><?php echo $u['username']; ?></td>
                    <td><?php echo $u['phone']; ?></td>
                    <td style="color:var(--accent);"><?php echo $u['total_points']; ?></td>
                    <td><?php echo $u['goal_diff']; ?></td>
                    <td><span class="status-badge <?php echo ($u['status']=='Active'?'st-done':'st-paid'); ?>"><?php echo $u['status']; ?></span></td>
                    <td>
                        <div class="action-group">
                            <button class="btn-sm btn-edit" onclick="openUserEdit(<?php echo htmlspecialchars(json_encode($u)); ?>)"><i class="fas fa-pen"></i></button>
                            <?php if($u['status']=='Active'): ?>
                                <a href="?action=ban&id=<?php echo $u['id']; ?>" class="btn-sm btn-ban"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="?action=unban&id=<?php echo $u['id']; ?>" class="btn-sm btn-edit"><i class="fas fa-unlock"></i></a>
                            <?php endif; ?>
                            <a href="?action=delete&id=<?php echo $u['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Delete user?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

    </div>

    <div id="matchModal" class="modal-overlay">
        <div class="modal-box">
            <button onclick="closeModals()" style="float:right; background:none; border:none; color:white; font-size:1.5rem;">&times;</button>
            <h2 style="color:var(--accent);">EDIT MATCH</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="match_id" id="edit_mid">
                
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <div style="flex:1;"><label style="color:#aaa; font-size:0.8rem;">Score A</label><input type="number" name="score_a" id="edit_sa" class="input-dark"></div>
                    <div style="flex:1;"><label style="color:#aaa; font-size:0.8rem;">Score B</label><input type="number" name="score_b" id="edit_sb" class="input-dark"></div>
                </div>

                <div style="margin-bottom:15px; background:#222; padding:10px; border-radius:8px;">
                    <label style="color:#aaa; font-size:0.8rem; display:block; margin-bottom:5px;">Payment Override</label>
                    <label style="margin-right:15px;"><input type="checkbox" name="paid_a" id="chk_pa"> Player A Paid</label>
                    <label><input type="checkbox" name="paid_b" id="chk_pb"> Player B Paid</label>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="color:#aaa; font-size:0.8rem;">Update Proof</label>
                    <input type="file" name="new_screenshot" style="font-size:0.8rem;">
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" name="edit_match" class="btn btn-primary" style="flex:1;">SAVE CHANGES</button>
                    <button type="submit" name="delete_match" onclick="return confirm('Delete this match permanently?')" class="btn btn-primary" style="background:var(--danger); flex:1;">DELETE</button>
                </div>
            </form>
        </div>
    </div>

    <div id="userModal" class="modal-overlay">
        <div class="modal-box">
            <button onclick="closeModals()" style="float:right; background:none; border:none; color:white; font-size:1.5rem;">&times;</button>
            <h2 style="color:var(--accent);">EDIT PLAYER</h2>
            <form method="POST">
                <input type="hidden" name="user_id" id="u_id">
                <label style="color:#aaa; font-size:0.8rem;">Username</label><input type="text" name="username" id="u_name" required>
                <label style="color:#aaa; font-size:0.8rem;">Phone</label><input type="text" name="phone" id="u_phone" required>
                <button type="submit" name="edit_user" class="btn btn-primary" style="width:100%;">UPDATE PROFILE</button>
            </form>
        </div>
    </div>
<div id="scheduleModal" class="modal-overlay">
    <div class="modal-box" style="text-align:center;">
        <button onclick="closeModals()" style="float:right; background:none; border:none; color:white; font-size:1.5rem;">&times;</button>
        <h2 style="color:var(--accent); margin-bottom:10px;">GENERATE FIXTURES</h2>
        <p style="color:#aaa; font-size:0.9rem; margin-bottom:20px;">Select the date range for the new matches.</p>
        
        <form method="POST">
            <div style="margin-bottom:20px; text-align:left;">
                <label style="color:#fff; font-size:0.9rem; display:block; margin-bottom:5px;">From (Start Date)</label>
                <input type="date" name="start_date" required 
                       value="<?php echo date('Y-m-d'); ?>" 
                       style="width:100%; padding:10px; background:#222; border:1px solid #444; color:white; border-radius:5px;">
            </div>

            <div style="margin-bottom:25px; text-align:left;">
                <label style="color:#fff; font-size:0.9rem; display:block; margin-bottom:5px;">To (End Date)</label>
                <input type="date" name="end_date" required 
                       value="<?php echo date('Y-m-d', strtotime('+6 days')); ?>" 
                       style="width:100%; padding:10px; background:#222; border:1px solid #444; color:white; border-radius:5px;">
            </div>

            <button name="generate_schedule" class="btn btn-primary" style="width:100%;">
                <i class="fas fa-calendar-check"></i> CREATE MATCHES
            </button>
        </form>
    </div>
</div>
    <script>
        function openMatchEdit(m) {
            document.getElementById('edit_mid').value = m.id;
            document.getElementById('edit_sa').value = m.score_a;
            document.getElementById('edit_sb').value = m.score_b;
            document.getElementById('chk_pa').checked = (m.paid_a == 1);
            document.getElementById('chk_pb').checked = (m.paid_b == 1);
            document.getElementById('matchModal').style.display = 'flex';
        }
        function openUserEdit(u) {
            document.getElementById('u_id').value = u.id;
            document.getElementById('u_name').value = u.username;
            document.getElementById('u_phone').value = u.phone;
            document.getElementById('userModal').style.display = 'flex';
        }
        function closeModals() { document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); }
        window.onclick = function(e) { if (e.target.classList.contains('modal-overlay')) closeModals(); }
    </script>
</body>
</html>
