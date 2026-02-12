<?php
require 'db.php';
echo "<h1>System Cleanup Tool</h1>";

// 1. Find all matches scheduled for TODAY and FUTURE
$today = date('Y-m-d');
$query = "SELECT * FROM matches WHERE match_date >= '$today' ORDER BY match_date ASC, id ASC";
$result = $conn->query($query);

$seen_matches = []; // To track who plays who
$deleted_count = 0;

while ($row = $result->fetch_assoc()) {
    $date = $row['match_date'];
    $p1 = $row['player_a_id'];
    $p2 = $row['player_b_id'];
    
    // Create a unique key for this player for this date
    // We count how many times p1 appears today
    if (!isset($seen_matches[$date][$p1])) $seen_matches[$date][$p1] = 0;
    if (!isset($seen_matches[$date][$p2])) $seen_matches[$date][$p2] = 0;

    // INCREMENT COUNT
    $seen_matches[$date][$p1]++;
    $seen_matches[$date][$p2]++;

    // CHECK LIMIT: If this match pushes anyone over 2 games, DELETE IT
    // (Only if nobody paid yet)
    if (($seen_matches[$date][$p1] > 2 || $seen_matches[$date][$p2] > 2) 
        && $row['paid_a'] == 0 && $row['paid_b'] == 0) {
        
        $del_id = $row['id'];
        $conn->query("DELETE FROM matches WHERE id = $del_id");
        
        echo "<div style='color:red;'>Deleted Extra Match #$del_id ($date) - Unpaid Duplicate</div>";
        $deleted_count++;
        
        // Fix the count back down
        $seen_matches[$date][$p1]--;
        $seen_matches[$date][$p2]--;
    }
}

if ($deleted_count == 0) {
    echo "<h3 style='color:green;'>System is Clean! No duplicates found.</h3>";
} else {
    echo "<h3 style='color:green;'>Cleanup Complete. Deleted $deleted_count duplicate matches.</h3>";
}
echo "<a href='admin.php'>Back to Admin</a>";
?>