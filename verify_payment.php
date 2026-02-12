<?php
session_start();
require 'db.php';
if (!isset($_POST['match_id'])) header("Location: index.php");

$mid = intval($_POST['match_id']);
$uid = $_SESSION['user_id'];
$tid = strtoupper(trim($_POST['trans_id']));

// Determine if user is Player A or Player B
$q = $conn->query("SELECT * FROM matches WHERE id=$mid");
$m = $q->fetch_assoc();

if ($m['player_a_id'] == $uid) {
    $sql = "UPDATE matches SET paid_a = 2, trans_id_a = ? WHERE id = ?";
} else {
    $sql = "UPDATE matches SET paid_b = 2, trans_id_b = ? WHERE id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $tid, $mid);
$stmt->execute();

$_SESSION['msg'] = "Payment submitted! Waiting for verification.";
header("Location: index.php");
?>
