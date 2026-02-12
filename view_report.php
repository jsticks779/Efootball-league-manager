<?php
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['admin_logged_in'])) { die("ACCESS DENIED"); }

$type = isset($_GET['type']) ? $_GET['type'] : 'today';
$title = "MATCH REPORT";
$sql = "";

// 1. DETERMINE REPORT TYPE
if ($type == 'today') {
    $title = "DAILY REPORT: " . date("d M Y");
    $sql = "AND DATE(m.match_date) = CURDATE()";
} elseif ($type == 'week') {
    $title = "WEEKLY REPORT (Current Week)";
    $sql = "AND YEARWEEK(m.match_date, 1) = YEARWEEK(CURDATE(), 1)";
} else {
    $title = "FULL LEAGUE ARCHIVE";
    $sql = ""; // Show everything
}

// 2. FETCH DETAILED DATA
// We fetch EVERYTHING: Payment status, Scores, Points, Player Details
$query = "SELECT 
            m.id as match_id,
            m.match_date,
            m.score_a, m.score_b,
            m.paid_a, m.paid_b,
            m.stats_applied,
            u1.username as home_team, u1.phone as home_phone,
            u2.username as away_team, u2.phone as away_phone
          FROM matches m
          JOIN users u1 ON m.player_a_id = u1.id
          JOIN users u2 ON m.player_b_id = u2.id
          WHERE 1=1 $sql
          ORDER BY m.match_date DESC, m.id ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Report</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #fff; color: #000; padding: 20px; }
        .report-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .report-header h1 { margin: 0; text-transform: uppercase; }
        .meta-info { font-size: 0.8rem; color: #555; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background: #eee; text-transform: uppercase; }
        
        .status-paid { font-weight: bold; color: green; }
        .status-unpaid { font-weight: bold; color: red; }
        .score-box { font-size: 1.1rem; font-weight: bold; }
        
        /* Print Button Style */
        .no-print { margin-bottom: 20px; text-align: right; }
        .btn { padding: 10px 20px; background: black; color: white; text-decoration: none; cursor: pointer; border: none; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn">🖨️ PRINT / SAVE PDF</button>
    </div>

    <div class="report-header">
        <h1>E-LEAGUE OFFICIAL REPORT</h1>
        <h2><?php echo $title; ?></h2>
        <div class="meta-info">Generated on: <?php echo date("Y-m-d H:i:s"); ?> | By: ADMIN</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>DATE</th>
                <th>HOME TEAM (Phone)</th>
                <th>SCORE</th>
                <th>AWAY TEAM (Phone)</th>
                <th>PAYMENT (H / A)</th>
                <th>STATUS</th>
                <th>POINTS</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    // Logic for display
                    $home_pay = ($row['paid_a'] == 1) ? "PAID" : (($row['paid_a'] == 2) ? "VERIFY" : "UNPAID");
                    $away_pay = ($row['paid_b'] == 1) ? "PAID" : (($row['paid_b'] == 2) ? "VERIFY" : "UNPAID");
                    
                    $score_display = ($row['stats_applied'] == 1) ? $row['score_a'] . " - " . $row['score_b'] : "VS";
                    
                    $status = "PENDING";
                    $points = "-";
                    
                    if ($row['stats_applied'] == 1) {
                        $status = "COMPLETED";
                        if ($row['score_a'] > $row['score_b']) $points = "HOME +3";
                        elseif ($row['score_b'] > $row['score_a']) $points = "AWAY +3";
                        else $points = "DRAW +1";
                    }
                ?>
                <tr>
                    <td>#<?php echo $row['match_id']; ?></td>
                    <td><?php echo date("d M", strtotime($row['match_date'])); ?></td>
                    
                    <td style="text-align:left;">
                        <strong><?php echo strtoupper($row['home_team']); ?></strong><br>
                        <small><?php echo $row['home_phone']; ?></small>
                    </td>
                    
                    <td class="score-box"><?php echo $score_display; ?></td>
                    
                    <td style="text-align:left;">
                        <strong><?php echo strtoupper($row['away_team']); ?></strong><br>
                        <small><?php echo $row['away_phone']; ?></small>
                    </td>
                    
                    <td>
                        <span class="<?php echo ($home_pay=='PAID')?'status-paid':'status-unpaid'; ?>"><?php echo $home_pay; ?></span>
                        / 
                        <span class="<?php echo ($away_pay=='PAID')?'status-paid':'status-unpaid'; ?>"><?php echo $away_pay; ?></span>
                    </td>
                    
                    <td><?php echo $status; ?></td>
                    <td><?php echo $points; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="padding:20px;">NO RECORDS FOUND FOR THIS PERIOD</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; border-top: 1px solid #000; padding-top: 10px; display:flex; justify-content:space-between;">
        <div>
            <strong>Verified By:</strong> ___________________
        </div>
        <div>
            <strong>Signature:</strong> ___________________
        </div>
    </div>

</body>
</html>