<?php
include 'config.php';

// Check if team is logged in
if (!isset($_SESSION['team_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$team_id = $_SESSION['team_id'];

if (isset($_GET['id'])) {
    $player_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ? AND team_id = ?");
        $stmt->execute([$player_id, $team_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($player) {
            header('Content-Type: application/json');
            echo json_encode($player);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Player not found']);
        }
    } catch(PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error']);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Player ID required']);
}
?>