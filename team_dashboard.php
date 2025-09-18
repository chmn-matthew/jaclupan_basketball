<?php
session_start();
include 'config.php';

// Check if team is logged in
if (!isset($_SESSION['team_id'])) {
    header("Location: team_login.php");
    exit();
}

$team_id = $_SESSION['team_id'];

// Get team information
try {
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        session_destroy();
        header("Location: team_login.php");
        exit();
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get players
try {
    $stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY jersey_number");
    $stmt->execute([$team_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $players = [];
}

// Check for messages
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Check if players are registered
$players_registered = isset($team['players_registered']) ? (bool)$team['players_registered'] : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Dashboard - JACLUPAN BASKETBALL LEAGUE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: #fff;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .content {
            padding: 25px;
            background: #fff;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .team-info {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .team-info h2 {
            color: #1a237e;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
        }
        
        .info-item i {
            margin-right: 10px;
            color: #1a237e;
            font-size: 18px;
            width: 24px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
        }
        
        .success i {
            margin-right: 10px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
        }
        
        .error i {
            margin-right: 10px;
        }
        
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .player-card {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            background: #fff;
        }
        
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .player-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #f0f0f0;
        }
        
        .player-info {
            margin-top: 10px;
        }
        
        .player-name {
            font-weight: 600;
            color: #1a237e;
            margin-bottom: 5px;
        }
        
        .player-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .actions {
            margin-top: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 20px;
            background: #1a237e;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #283593;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-secondary {
            background: #5a6268;
        }
        
        .btn-secondary:hover {
            background: #6c757d;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
        }
        
        .status-complete {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .section-title {
            color: #1a237e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ced4da;
        }
        
        @media (max-width: 768px) {
            .player-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>JACLUPAN BASKETBALL LEAGUE</h1>
            <p>TEAM DASHBOARD</p>
        </div>
    </header>
    
    <div class="container">
        <?php
        // Load site-wide announcement
        $announcement_file = __DIR__ . DIRECTORY_SEPARATOR . 'announcement.json';
        if (file_exists($announcement_file)) {
            $raw = file_get_contents($announcement_file);
            $data = json_decode($raw, true);
            if (is_array($data) && !empty($data['active']) && !empty($data['message'])) {
                echo '<div class="success" style="background:#e2e3ff;color:#1b1e6b;border-left:4px solid #1a237e;">'
                    . '<i class="fas fa-bullhorn" style="margin-right:10px;"></i>'
                    . htmlspecialchars($data['message'])
                    . '</div>';
            }
        }
        ?>
        <?php if ($message): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="content">
            <div class="team-info">
                <h2><?php echo htmlspecialchars($team['team_name'] ?? 'Unknown Team'); ?></h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-trophy"></i>
                        <span><strong>Division:</strong> <?php echo htmlspecialchars($team['division'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-tie"></i>
                        <span><strong>Coach:</strong> <?php echo htmlspecialchars($team['coach_name'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span><strong>Contact:</strong> <?php echo htmlspecialchars($team['contact_number'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <span><strong>Email:</strong> <?php echo htmlspecialchars($team['email'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
                
                <div class="status-badge <?php echo $players_registered ? 'status-complete' : 'status-pending'; ?>">
                    <i class="fas <?php echo $players_registered ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                    <?php echo $players_registered ? 'Registration Complete' : 'Registration Pending'; ?>
                </div>
            </div>
            
            <div class="actions">
                <?php if (!$players_registered): ?>
                    <a href="players.php" class="btn">
                        <i class="fas fa-plus"></i> Add Players
                    </a>
                <?php else: ?>
                    <a href="players.php" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Edit Players
                    </a>
                <?php endif; ?>
                
                <a href="edit_team.php" class="btn btn-secondary">
                    <i class="fas fa-cog"></i> Edit Team Info
                </a>
                
                <a href="logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="content">
            <h2 class="section-title">Registered Players (<?php echo count($players); ?>)</h2>
            
            <?php if (count($players) > 0): ?>
                <div class="player-grid">
                    <?php foreach ($players as $player): ?>
                        <div class="player-card">
                            <?php if (!empty($player['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($player['photo_path']); ?>" alt="Player Photo" class="player-photo">
                            <?php else: ?>
                                <div style="width: 100px; height: 100px; border-radius: 50%; background: #eaeaea; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                                    <i class="fas fa-user" style="font-size: 40px; color: #9e9e9e;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="player-info">
                                <div class="player-name"><?php echo htmlspecialchars($player['name'] ?? 'Unknown Player'); ?></div>
                                <div class="player-details">#<?php echo htmlspecialchars($player['jersey_number'] ?? 'N/A'); ?> | <?php echo htmlspecialchars($player['position'] ?? 'N/A'); ?></div>
                                <div class="player-details">DOB: <?php echo !empty($player['birthdate']) ? date('M j, Y', strtotime($player['birthdate'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Players Registered</h3>
                    <p>Add players to complete your team registration</p>
                    <a href="players.php" class="btn btn-success" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Add Players
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>