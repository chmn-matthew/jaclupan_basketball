<?php
include 'config.php';
session_start();

// Check if team is logged in
if (!isset($_SESSION['team_id'])) {
    header("Location: team_login.php");
    exit();
}

$team_id = $_SESSION['team_id'];

// Get team information
$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    session_destroy();
    header("Location: team_login.php");
    exit();
}

// Check if players are already registered
if ($team['players_registered']) {
    $_SESSION['message'] = "Your players have already been registered.";
    header("Location: team_dashboard.php");
    exit();
}

// Count players
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM players WHERE team_id = ?");
$stmt->execute([$team_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] < 5) {
    $_SESSION['error'] = "You need at least 5 players to complete registration.";
    header("Location: players.php");
    exit();
}

// Update team registration status
$stmt = $pdo->prepare("UPDATE teams SET players_registered = 1 WHERE id = ?");
$stmt->execute([$team_id]);

$_SESSION['message'] = "Player registration completed successfully!";
header("Location: team_dashboard.php");
exit();
?>