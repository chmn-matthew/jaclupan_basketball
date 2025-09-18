<?php
include 'config.php';

if (!isset($_GET['id'])) {
    die("Team ID not specified");
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    die("Team not found");
}

$division_labels = [
    'UNDER_12' => 'UNDER 12 DIVISION',
    'UNDER_16' => 'UNDER 16 DIVISION',
    'UNDER_20' => 'UNDER 20 DIVISION',
    'UNDER_30' => 'UNDER 30 DIVISION',
    '31_39' => '31-39 DIVISION',
    '40_UP' => '40UP DIVISION'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> - JACLUPAN BASKETBALL LEAGUE</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #1a237e; border-bottom: 2px solid #1a237e; padding-bottom: 10px; }
        .team-info { margin: 20px 0; }
        .team-info div { margin: 10px 0; }
        .label { font-weight: bold; display: inline-block; width: 150px; }
        .players-list { background: #f9f9f9; padding: 15px; border-radius: 5px; white-space: pre-line; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 15px; background: #1a237e; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($team['team_name']); ?></h1>
        
        <div class="team-info">
            <div><span class="label">Coach:</span> <?php echo htmlspecialchars($team['coach_name']); ?></div>
            <div><span class="label">Contact Number:</span> <?php echo htmlspecialchars($team['contact_number']); ?></div>
            <div><span class="label">Email:</span> <?php echo htmlspecialchars($team['email']); ?></div>
            <div><span class="label">Division:</span> <?php echo $division_labels[$team['division']]; ?></div>
            <div><span class="label">Registration Date:</span> <?php echo date('M j, Y g:i A', strtotime($team['registration_date'])); ?></div>
        </div>
        
        <h2>Players</h2>
        <div class="players-list"><?php echo htmlspecialchars($team['players']); ?></div>
        
        <a href="admin.php" class="back-link">Back to Admin Panel</a>
    </div>
</body>
</html>