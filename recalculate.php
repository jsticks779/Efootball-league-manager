<?php
// recalculate.php
require 'db.php';

echo "<h1>System Recalculation Tool</h1>";
echo "<p>Resetting all player stats to 0...</p>";

// 1. Reset everyone to 0 first
$conn->query("UPDATE users SET total_matches=0, total_goals=0, total_points=0, goal_diff=0");

// 2. Get all players
$players = $conn->query("SELECT id FROM users");

while ($p = $players->fetch_assoc()) {
    $uid = $p['id'];
    
    // 3. Calculate stats for this player from scratch
    // We look for matches where:
    // a) The player was involved (A or B)
    // b) The match has a result (stats_applied = 1)
    // c) The player has PAID (paid_a/paid_b = 1)
    
    // Totals as Player A
    $qA = $conn->query("SELECT COUNT(*) as m, SUM(score_a) as gf, SUM(score_b) as ga, 
                        SUM(CASE 
                            WHEN score_a > score_b THEN 3 
                            WHEN score_a = score_b THEN 1 
                            ELSE 0 END) as pts 
                        FROM matches WHERE player_a_id=$uid AND stats_applied=1 AND paid_a=1");
    $rA = $qA->fetch_assoc();

    // Totals as Player B
    $qB = $conn->query("SELECT COUNT(*) as m, SUM(score_b) as gf, SUM(score_a) as ga, 
                        SUM(CASE 
                            WHEN score_b > score_a THEN 3 
                            WHEN score_b = score_a THEN 1 
                            ELSE 0 END) as pts 
                        FROM matches WHERE player_b_id=$uid AND stats_applied=1 AND paid_b=1");
    $rB = $qB->fetch_assoc();

    // Sum them up
    $matches = $rA['m'] + $rB['m'];
    $goals_for = $rA['gf'] + $rB['gf'];
    $goals_against = $rA['ga'] + $rB['ga'];
    $points = $rA['pts'] + $rB['pts'];
    $goal_diff = $goals_for - $goals_against;

    // 4. Update the User
    if ($matches > 0) {
        $conn->query("UPDATE users SET 
            total_matches = $matches, 
            total_goals = $goals_for, 
            goal_diff = $goal_diff, 
            total_points = $points 
            WHERE id=$uid");
        echo "Updated Player ID $uid: $points Points<br>";
    }
}

echo "<h2 style='color:green'>SUCCESS! All stats are now correct.</h2>";
echo "<a href='admin.php'>Go Back to Admin</a>";
?>