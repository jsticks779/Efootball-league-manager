<?php
date_default_timezone_set('Africa/Dar_es_Salaam');
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: welcome.php"); exit; }

$uid = $_SESSION['user_id'];
$me = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Date Logic
$date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$curr = new DateTime($date_str);
$prev = clone $curr; $prev->modify('-1 day');
$next = clone $curr; $next->modify('+1 day');

$is_today = ($date_str == date('Y-m-d'));
$fixture_title = $is_today ? "TODAY'S MATCHES" : strtoupper($curr->format('d M')) . " MATCHES";

// Fetch Matches (With LIMIT 2 Clean View)
$matches = $conn->query("SELECT m.*, IF(m.player_a_id = $uid, u2.username, u1.username) as opponent 
                         FROM matches m 
                         JOIN users u1 ON m.player_a_id = u1.id 
                         JOIN users u2 ON m.player_b_id = u2.id 
                         WHERE (m.player_a_id = $uid OR m.player_b_id = $uid) 
                         AND DATE(m.match_date) = '$date_str' 
                         ORDER BY m.id ASC LIMIT 2");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars(strtoupper($me['username'])); ?> | Efootball-League</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div style="position: absolute; top: 20px; right: 20px; z-index: 10;">
        <button class="theme-toggle" onclick="toggleTheme()" title="Switch Theme">
            <i class="fas fa-sun"></i>
        </button>
    </div>
    <div class="container">
        <header class="dashboard-header">
            <div class="logo">
                <h1>MY <span>DASHBOARD</span></h1>
                <div class="user-status">PLAYER: <?php echo htmlspecialchars(strtoupper($me['username'])); ?></div>
            </div>

            <div class="header-actions">
                <button class="btn-icon" onclick="toggleNotif()">
                    <i class="fas fa-bell"></i>
                    <?php if($notif_c > 0): ?><span class="notif-badge"></span><?php endif; ?>
                </button>
                <div id="notifMenu" class="notif-menu">
                    <h4 style="color:var(--text-main); margin-bottom:10px;">ALERTS</h4>
                    <p style="color:#666; font-size:0.9rem;">
                        <?php echo ($notif_c > 0) ? "$notif_c pending matches." : "You are up to date."; ?>
                    </p>
                </div>

                <button onclick="document.getElementById('leaderModal').style.display='flex'" class="btn btn-outline">
                    <i class="fas fa-trophy"></i> LEADERBOARD
                </button>
                <a href="logout.php?type=player" class="btn btn-primary">
                    LOGOUT
                </a>
            </div>
        </header>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-title">MATCHES</div>
                <div class="stat-value"><?php echo $me['total_matches']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">GOALS</div>
                <div class="stat-value glow"><?php echo $me['total_goals']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">POINTS</div>
                <div class="stat-value"><?php echo $me['total_points']; ?></div>
            </div>
        </div>

        <div class="date-nav">
            <a href="?date=<?php echo $prev->format('Y-m-d'); ?>" class="nav-arrow"><i class="fas fa-chevron-left"></i></a>
            <span><?php echo $curr->format('D, d M'); ?></span>
            <a href="?date=<?php echo $next->format('Y-m-d'); ?>" class="nav-arrow"><i class="fas fa-chevron-right"></i></a>
        </div>

        <h3 class="section-title"><?php echo $fixture_title; ?></h3>
        
        <div class="match-list">
            <?php if($matches->num_rows > 0): ?>
                <?php while($row = $matches->fetch_assoc()): 
    // 1. Determine Logic
    $is_a = ($row['player_a_id'] == $uid);
    $my_pay = $is_a ? $row['paid_a'] : $row['paid_b'];
    $has_result = ($row['stats_applied'] == 1 || !empty($row['screenshot']));
    $can_see = ($has_result && $my_pay == 1);

    // 2. Setup Home vs Away Display
    if ($is_a) {
        // I am HOME (Player A)
        $home_name = $me['username'];
        $away_name = $row['opponent'];
        $home_color = 'var(--accent)'; // My Color
        $away_color = '#ffffff';       // Opponent Color
    } else {
        // I am AWAY (Player B) -> Opponent is Home
        $home_name = $row['opponent'];
        $away_name = $me['username'];
        $home_color = '#ffffff';       // Opponent Color
        $away_color = 'var(--accent)'; // My Color
    }
?>
<div class="match-item">
    <div class="match-details">
        <h3 style="display:flex; align-items:center; gap:8px; margin:0;">
            <span style="color:<?php echo $home_color; ?>; font-weight:bold;">
                <?php echo htmlspecialchars(strtoupper($home_name)); ?>
            </span>
            <span style="color:#666; font-size:0.8rem;">VS</span>
            <span style="color:<?php echo $away_color; ?>; font-weight:bold;">
                <?php echo htmlspecialchars(strtoupper($away_name)); ?>
            </span>
        </h3>
        
        <div class="match-meta" style="margin-top:4px;">
            <?php echo ($is_a) ? "You are Home" : "You are Away"; ?>
        </div>
    </div>

    <div class="match-score">
        <?php if ($can_see): ?>
            <?php echo $row['score_a'] . " - " . $row['score_b']; ?>
            
            <?php if($row['screenshot']): ?>
                <a href="uploads/<?php echo $row['screenshot']; ?>" target="_blank" style="display:block; font-size:0.7rem; color:#aaa; margin-top:5px; text-decoration:none;">
                    <i class="fas fa-image"></i> Proof
                </a>
            <?php endif; ?>
        <?php else: ?>
            <span style="color:#444; font-size:1.2rem;">-- : --</span>
        <?php endif; ?>
    </div>

    <div class="match-action">
        <?php if ($my_pay == 0): ?>
            <a href="payment.php?match_id=<?php echo $row['id']; ?>" class="btn btn-pay">PAY 200/=</a>
        <?php elseif ($my_pay == 2): ?>
            <span class="status-badge st-paid">Verifying</span>
        <?php else: ?>
            <span class="status-badge st-done">PAID</span>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#444; border:1px dashed #333; border-radius:12px;">
                    <i class="fas fa-calendar-times" style="font-size:2rem; margin-bottom:10px;"></i><br>
                    NO MATCHES SCHEDULED
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="leaderModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:2000; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#111; width:100%; max-width:500px; border:1px solid var(--accent); border-radius:12px; padding:25px; box-shadow:0 0 30px var(--accent-glow);">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h2 style="color:white; font-family:'Rajdhani'; margin:0;">LEADERBOARD</h2>
                <button onclick="document.getElementById('leaderModal').style.display='none'" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <table style="width:100%; color:white; border-collapse:collapse;">
                <tr style="color:#666; font-size:0.8rem; text-align:left; border-bottom:1px solid #333;">
                    <th style="padding-bottom:10px;">#</th>
                    <th style="padding-bottom:10px;">PLAYER</th>
                    <th style="padding-bottom:10px;">GD</th>
                    <th style="padding-bottom:10px;">PTS</th>
                </tr>
                <?php 
                $leaders = $conn->query("SELECT username, total_points, goal_diff FROM users WHERE status='Active' ORDER BY total_points DESC, goal_diff DESC, total_goals DESC");
                $r=1; while($l = $leaders->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:12px 0; color:var(--accent);"><?php echo $r++; ?></td>
                    <td style="padding:12px 0;"><?php echo htmlspecialchars($l['username']); ?></td>
                    <td style="padding:12px 0; color:#888;"><?php echo $l['goal_diff']; ?></td>
                    <td style="padding:12px 0; font-weight:bold; color:white;"><?php echo $l['total_points']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <script>
        function toggleNotif() {
            var menu = document.getElementById('notifMenu');
            menu.classList.toggle('show');
        }
        window.onclick = function(e) {
            if (!e.target.closest('.header-actions')) {
                document.getElementById('notifMenu').classList.remove('show');
            }
        }
    </script>
    <script src="theme.js"></script>
</body>
</html>
