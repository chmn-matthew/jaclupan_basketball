<?php
include 'config.php';

// Simple admin authentication
$admin_username = 'admin';
$admin_password = 'jaclupan2024';

// Check if user is logged in as admin
$is_logged_in = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $is_logged_in = true;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $is_logged_in = true;
    } else {
        $error = "Invalid username or password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Get all teams if logged in
$teams = [];
if ($is_logged_in) {
    // Load announcement data
    $announcement_file = __DIR__ . DIRECTORY_SEPARATOR . 'announcement.json';
    $announcement_message = '';
    $announcement_active = false;
    $announcement_attachment = '';
    if (file_exists($announcement_file)) {
        $raw = file_get_contents($announcement_file);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            $announcement_message = isset($data['message']) ? (string)$data['message'] : '';
            $announcement_active = !empty($data['active']);
            $announcement_attachment = isset($data['file_path']) ? (string)$data['file_path'] : '';
        }
    }

    // Handle announcement save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
        $new_message = isset($_POST['announcement_message']) ? trim($_POST['announcement_message']) : '';
        $new_active = isset($_POST['announcement_active']) ? true : false;

        // Preserve existing attachment unless a new one is uploaded
        $new_file_path = $announcement_attachment;

        if (isset($_FILES['announcement_file']) && $_FILES['announcement_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['announcement_file']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed_ext, true)) {
                $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'announcements';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $basename = uniqid('announcement_', true) . '.' . $ext;
                $abs_path = $dir . DIRECTORY_SEPARATOR . $basename;
                $rel_path = 'uploads/announcements/' . $basename;
                if (move_uploaded_file($_FILES['announcement_file']['tmp_name'], $abs_path)) {
                    $new_file_path = $rel_path;
                } else {
                    $error = 'Failed to upload announcement file.';
                }
            } else {
                $error = 'Invalid file type. Allowed: PDF, JPG, JPEG, PNG, GIF';
            }
        }

        // Only save if no upload error occurred
        if (!isset($error)) {
            $payload = [
                'message' => $new_message,
                'active' => $new_active,
                'file_path' => $new_file_path,
                'updated_at' => date('c')
            ];
            if (file_put_contents($announcement_file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false) {
                $success = 'Announcement updated successfully';
                $announcement_message = $new_message;
                $announcement_active = $new_active;
                $announcement_attachment = $new_file_path;
            } else {
                $error = 'Failed to save announcement. Please check file permissions.';
            }
        }
    }

    // Handle team review status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
        $status_notes = isset($_POST['status_notes']) ? trim($_POST['status_notes']) : null;
        $allowed = ['pending', 'approved', 'lacking', 'rejected'];
        if (!in_array($status, $allowed, true)) { $status = 'pending'; }
        if ($team_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE teams SET status = ?, status_notes = ? WHERE id = ?");
                $stmt->execute([$status, $status_notes, $team_id]);
                $success = 'Team review status updated.';
            } catch (PDOException $e) {
                $error = 'Failed to update status: ' . $e->getMessage();
            }
        }
    }

    try {
        $stmt = $pdo->query("SELECT * FROM teams ORDER BY division, registration_date DESC");
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Compute summary counts
        $counts = [
            'total' => count($teams),
            'approved' => 0,
            'pending' => 0,
            'lacking' => 0,
            'rejected' => 0
        ];
        foreach ($teams as $t) {
            $st = isset($t['status']) ? $t['status'] : 'pending';
            if (isset($counts[$st])) { $counts[$st]++; }
            else { $counts['pending']++; }
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get players for a specific team
$team_players = [];
if ($is_logged_in && isset($_GET['view_players'])) {
    $team_id = $_GET['view_players'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY jersey_number");
        $stmt->execute([$team_id]);
        $team_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get team info
        $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        $current_team = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - JACLUPAN BASKETBALL LEAGUE</title>
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
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .stat-chips { display:flex; gap:8px; flex-wrap:wrap; }
        .chip {
            display:inline-flex; align-items:center; gap:6px;
            padding:8px 12px; border-radius:20px; font-size:13px; font-weight:600;
            background:#f1f3f5; color:#333; border:1px solid #e5e7eb;
        }
        .chip i { opacity: .8; }
        .chip-approved { background:#e6f4ea; color:#1e7e34; border-color:#c7e8d0; }
        .chip-pending { background:#fff3cd; color:#856404; border-color:#ffe8a1; }
        .chip-lacking { background:#ffe9e0; color:#a14500; border-color:#ffd1bf; }
        .chip-rejected { background:#fdecea; color:#c92a2a; border-color:#fac5c0; }
        .search-input {
            flex:1; min-width:220px;
            padding:10px 12px; border:1px solid #ddd; border-radius:8px; font-size:14px;
        }
        
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #1a237e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
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
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }
        thead th { position: sticky; top: 0; background:#f8f9fa; z-index:1; }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #1a237e;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .team-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #eaeaea;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .section-title {
            color: #1a237e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        details.division {
            background:#fafbff; border:1px solid #eef1ff; border-radius:8px; margin:14px 0; padding:10px 12px;
        }
        details.division summary {
            list-style:none; cursor:pointer; font-weight:700; color:#283593;
        }
        details.division summary::-webkit-details-marker { display:none; }
        .legend { display:flex; gap:8px; flex-wrap:wrap; margin-left:auto; }
        
        .actions {
            display: flex;
            gap: 10px;
            margin: 25px 0;
        }
        
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .player-card {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            background: #fff;
        }
        
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .player-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .player-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid #f0f0f0;
        }
        
        .player-info {
            flex-grow: 1;
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
        
        .player-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: #1a237e;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .player-grid {
                grid-template-columns: 1fr;
            }
            
            .player-header {
                flex-direction: column;
                text-align: center;
            }
            
            .player-photo {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>JACLUPAN BASKETBALL LEAGUE</h1>
            <p>ADMIN PANEL</p>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$is_logged_in): ?>
            <div class="login-form">
                <h2 style="text-align: center; margin-bottom: 25px; color: #1a237e;">Admin Login</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="actions">
                <a href="admin.php?logout=true" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                
                <?php if (isset($_GET['view_players'])): ?>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Teams
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="content">
                <?php if ($is_logged_in && !isset($_GET['view_players'])): ?>
                    <h2 class="section-title">Site Announcement</h2>
                    <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom:20px;">
                        <div class="form-group">
                            <label for="announcement_message">Announcement Message</label>
                            <textarea id="announcement_message" name="announcement_message" rows="4" style="width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:6px; font-size:16px;" placeholder="Enter announcement to display site-wide..."><?php echo htmlspecialchars($announcement_message); ?></textarea>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" id="announcement_active" name="announcement_active" <?php echo $announcement_active ? 'checked' : ''; ?>>
                            <label for="announcement_active" style="margin:0;">Active (show announcement to users)</label>
                        </div>
                        <div class="form-group">
                            <label for="announcement_file">Attachment (PDF/Image, optional)</label>
                            <input type="file" id="announcement_file" name="announcement_file" accept=".pdf,.jpg,.jpeg,.png,.gif" style="width:100%; padding:10px; border:2px dashed #ddd; border-radius:6px; background:#fafafa;">
                            <?php if (!empty($announcement_attachment)): ?>
                                <div style="margin-top:8px;">
                                    Current file: <a href="<?php echo htmlspecialchars($announcement_attachment); ?>" target="_blank">View attachment</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="save_announcement" class="btn">
                            <i class="fas fa-save"></i> Save Announcement
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (isset($_GET['view_players'])): ?>
                    <a href="admin.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Teams List
                    </a>
                    
                    <h2 class="section-title">Players for <?php echo htmlspecialchars($current_team['team_name'] ?? 'Unknown Team'); ?></h2>
                    
                    <?php if (count($team_players) > 0): ?>
                        <div class="player-grid">
                            <?php foreach ($team_players as $player): ?>
                                <div class="player-card">
                                    <div class="player-header">
                                        <?php if (!empty($player['photo_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($player['photo_path']); ?>" alt="Player Photo" class="player-photo">
                                        <?php else: ?>
                                            <div style="width: 70px; height: 70px; border-radius: 50%; background: #eaeaea; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="font-size: 30px; color: #9e9e9e;"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="player-info">
                                            <div class="player-name"><?php echo htmlspecialchars($player['name']); ?></div>
                                            <div class="player-details">#<?php echo htmlspecialchars($player['jersey_number']); ?> | <?php echo htmlspecialchars($player['position']); ?></div>
                                            <div class="player-details">DOB: <?php echo date('M j, Y', strtotime($player['birthdate'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="player-actions">
                                        <?php if (!empty($player['document_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($player['document_path']); ?>" target="_blank" class="btn btn-success">
                                                <i class="fas fa-id-card"></i> View ID
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($player['birth_certificate_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($player['birth_certificate_path']); ?>" target="_blank" class="btn btn-secondary">
                                                <i class="fas fa-file"></i> Birth Certificate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: #6c757d;">
                            <i class="fas fa-users" style="font-size: 48px; display: block; margin-bottom: 15px; color: #ced4da;"></i>
                            No players registered for this team yet.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 class="section-title">Registered Teams (<?php echo count($teams); ?>)</h2>
                    <div class="toolbar">
                        <input type="text" id="teamSearch" class="search-input" placeholder="Search team, coach, contact, notes...">
                        <div class="stat-chips">
                            <span class="chip"><i class="fas fa-users"></i> Total: <?php echo (int)$counts['total']; ?></span>
                            <span class="chip chip-approved"><i class="fas fa-check-circle"></i> Approved: <?php echo (int)$counts['approved']; ?></span>
                            <span class="chip chip-pending"><i class="fas fa-clock"></i> Pending: <?php echo (int)$counts['pending']; ?></span>
                            <span class="chip chip-lacking"><i class="fas fa-exclamation-triangle"></i> Lacking: <?php echo (int)$counts['lacking']; ?></span>
                            <span class="chip chip-rejected"><i class="fas fa-times-circle"></i> Not Approved: <?php echo (int)$counts['rejected']; ?></span>
                        </div>
                    </div>
                    <?php if (count($teams) > 0): ?>
                        <?php 
                            $teams_by_division = [];
                            foreach ($teams as $t) {
                                $div = $t['division'] ?? 'UNSPECIFIED';
                                if (!isset($teams_by_division[$div])) { $teams_by_division[$div] = []; }
                                $teams_by_division[$div][] = $t;
                            }
                            // Define desired order
                            $division_order = ['UNDER_12','UNDER_16','UNDER_20','UNDER_30','31_39','40_UP','UNSPECIFIED'];
                            // Helper to pretty print
                            $pretty = function($key) {
                                $map = [
                                    'UNDER_12' => 'UNDER 12 DIVISION',
                                    'UNDER_16' => 'UNDER 16 DIVISION',
                                    'UNDER_20' => 'UNDER 20 DIVISION',
                                    'UNDER_30' => 'UNDER 30 DIVISION',
                                    '31_39' => '31-39 DIVISION',
                                    '40_UP' => '40UP DIVISION',
                                    'UNSPECIFIED' => 'UNSPECIFIED'
                                ];
                                return isset($map[$key]) ? $map[$key] : $key;
                            };
                            // Build ordered list of [division_key => teams]
                            $ordered = [];
                            foreach ($division_order as $dk) {
                                if (isset($teams_by_division[$dk])) { $ordered[$dk] = $teams_by_division[$dk]; }
                            }
                            // Append any remaining unexpected keys
                            foreach ($teams_by_division as $k => $v) {
                                if (!isset($ordered[$k])) { $ordered[$k] = $v; }
                            }
                        ?>
                        <?php foreach ($ordered as $division => $division_teams): ?>
                            <details class="division" open>
                                <summary>Division: <?php echo htmlspecialchars($pretty($division)); ?> (<?php echo count($division_teams); ?>)</summary>
                                <table class="teams-table" data-division="<?php echo htmlspecialchars($division); ?>">
                                <thead>
                                    <tr>
                                        <th>Team</th>
                                        <th>Coach</th>
                                        <th>Contact</th>
                                        <th>Review</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($division_teams as $team): ?>
                                        <tr class="team-row">
                                            <td>
                                                <div style="display: flex; align-items: center;">
                                                    <?php if (!empty($team['logo_path'])): ?>
                                                        <img src="<?php echo htmlspecialchars($team['logo_path']); ?>" alt="Team Logo" class="team-logo" style="margin-right: 10px;">
                                                    <?php else: ?>
                                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #eaeaea; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                            <i class="fas fa-basketball-ball" style="color: #9e9e9e;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($team['coach_name']); ?></td>
                                            <td><?php echo htmlspecialchars($team['contact_number']); ?></td>
                                            <td>
                                                <?php 
                                                    $review = isset($team['status']) ? $team['status'] : 'pending';
                                                    $notes = isset($team['status_notes']) ? $team['status_notes'] : '';
                                                    $badgeClass = 'badge-warning';
                                                    $label = 'Pending Review';
                                                    if ($review === 'approved') { $badgeClass = 'badge-success'; $label = 'Approved'; }
                                                    if ($review === 'lacking') { $badgeClass = 'badge-warning'; $label = 'Lacking Requirements'; }
                                                    if ($review === 'rejected') { $badgeClass = 'badge-warning'; $label = 'Not Approved'; }
                                                ?>
                                                <div style="margin-bottom:8px;">
                                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($label); ?></span>
                                                </div>
                                                <?php if (!empty($notes)): ?>
                                                    <div style="font-size:12px;color:#555;margin-bottom:8px;">Notes: <?php echo nl2br(htmlspecialchars($notes)); ?></div>
                                                <?php endif; ?>
                                                <form method="POST" action="" style="display:flex; flex-direction:column; gap:6px; max-width:300px;">
                                                    <input type="hidden" name="team_id" value="<?php echo (int)$team['id']; ?>">
                                                    <select name="status" style="padding:8px; border:1px solid #ddd; border-radius:6px;">
                                                        <option value="pending" <?php echo $review==='pending'?'selected':''; ?>>Pending</option>
                                                        <option value="approved" <?php echo $review==='approved'?'selected':''; ?>>Approved</option>
                                                        <option value="lacking" <?php echo $review==='lacking'?'selected':''; ?>>Lacking</option>
                                                        <option value="rejected" <?php echo $review==='rejected'?'selected':''; ?>>Not Approved</option>
                                                    </select>
                                                    <input type="text" name="status_notes" value="<?php echo htmlspecialchars($notes); ?>" placeholder="Optional notes" style="padding:8px; border:1px solid #ddd; border-radius:6px;">
                                                    <button type="submit" name="update_status" class="btn" style="padding:8px 12px; font-size:14px; align-self:flex-start;">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($team['registration_date'])); ?></td>
                                            <td>
                                                <a href="admin.php?view_players=<?php echo $team['id']; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 14px;">
                                                    <i class="fas fa-users"></i> View Players
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                </table>
                            </details>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: #6c757d;">
                            <i class="fas fa-trophy" style="font-size: 48px; display: block; margin-bottom: 15px; color: #ced4da;"></i>
                            No teams registered yet.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Simple search filter across all team rows
        const searchInput = document.getElementById('teamSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('table.teams-table tbody .team-row').forEach(function(row) {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(q) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>