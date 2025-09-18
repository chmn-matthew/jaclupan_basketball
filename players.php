<?php
include 'config.php';

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

// Check if players are already registered (with proper validation)
$players_registered = isset($team['players_registered']) ? (bool)$team['players_registered'] : false;
$review_status = isset($team['status']) ? $team['status'] : 'pending';
$editing_locked = ($review_status === 'approved');

// Editing is controlled by admin approval status, not by players_registered

// Handle form submission for adding a new player
$errors = [];
$success = false;

if (!$editing_locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_player'])) {
    $name = $_POST['name'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $address = $_POST['address'] ?? '';
    // Jersey number and position removed from form; jersey will be auto-assigned, position left empty
    
    // Validate inputs
    if (empty($name)) $errors[] = "Player name is required";
    if (empty($birthdate)) $errors[] = "Birthdate is required";
    
    // Handle file uploads
    $photo_path = null;
    $document_path = null;
    $birth_certificate_path = null;
    
    // Photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_photo_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($photo_ext, $allowed_photo_ext)) {
            $photo_name = uniqid('player_', true) . '.' . $photo_ext;
            $photo_path = 'uploads/photos/' . $photo_name;
            
            // Create directory if it doesn't exist
            if (!is_dir('uploads/photos')) {
                mkdir('uploads/photos', 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                $errors[] = "Failed to upload photo";
            }
        } else {
            $errors[] = "Invalid photo format. Only JPG, JPEG, PNG, and GIF are allowed";
        }
    } else {
        $errors[] = "Player photo is required";
    }
    
    // Document upload (Valid ID or Any ID) - required
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $doc_ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowed_doc_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (in_array($doc_ext, $allowed_doc_ext)) {
            $doc_name = uniqid('doc_', true) . '.' . $doc_ext;
            $document_path = 'uploads/documents/' . $doc_name;
            
            // Create directory if it doesn't exist
            if (!is_dir('uploads/documents')) {
                mkdir('uploads/documents', 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['document']['tmp_name'], $document_path)) {
                $errors[] = "Failed to upload document";
            }
        } else {
            $errors[] = "Invalid document format. Only JPG, JPEG, PNG, GIF, and PDF are allowed";
        }
    } else {
        $errors[] = "Valid ID or Any ID is required";
    }
    
    // Optional Birth Certificate upload (photo or PDF)
    if (isset($_FILES['birth_certificate']) && $_FILES['birth_certificate']['error'] === UPLOAD_ERR_OK) {
        $bc_ext = strtolower(pathinfo($_FILES['birth_certificate']['name'], PATHINFO_EXTENSION));
        $allowed_bc_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (in_array($bc_ext, $allowed_bc_ext)) {
            $bc_name = uniqid('birth_', true) . '.' . $bc_ext;
            $birth_certificate_path = 'uploads/documents/' . $bc_name;
            if (!is_dir('uploads/documents')) {
                mkdir('uploads/documents', 0777, true);
            }
            if (!move_uploaded_file($_FILES['birth_certificate']['tmp_name'], $birth_certificate_path)) {
                $errors[] = "Failed to upload birth certificate";
            }
        } else {
            $errors[] = "Invalid birth certificate format. Only JPG, JPEG, PNG, GIF, and PDF are allowed";
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        // Generate next available jersey number (1-99) unique per team to satisfy DB constraint
        $jersey_number = 0;
        try {
            $taken = [];
            $q = $pdo->prepare("SELECT jersey_number FROM players WHERE team_id = ?");
            $q->execute([$team_id]);
            foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $jn) {
                if ($jn !== null) { $taken[(int)$jn] = true; }
            }
            for ($i = 1; $i <= 99; $i++) {
                if (!isset($taken[$i])) { $jersey_number = $i; break; }
            }
            if ($jersey_number === 0) { $jersey_number = rand(1, 99); }
        } catch (Exception $e) {
            $jersey_number = rand(1, 99);
        }
        $position = null;
        try {
            $stmt = $pdo->prepare("INSERT INTO players (team_id, name, birthdate, address, jersey_number, position, photo_path, document_path, birth_certificate_path) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$team_id, $name, $birthdate, $address, $jersey_number, $position, $photo_path, $document_path, $birth_certificate_path]);
            
            $success = true;
            $_POST = []; // Clear form
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            
            // Clean up uploaded files if database insert failed
            if ($photo_path && file_exists($photo_path)) unlink($photo_path);
            if ($document_path && file_exists($document_path)) unlink($document_path);
        }
    }
}

// Handle form submission for editing a player
if (!$editing_locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_player'])) {
    $player_id = $_POST['player_id'] ?? '';
    $name = $_POST['edit_name'] ?? '';
    $birthdate = $_POST['edit_birthdate'] ?? '';
    $address = $_POST['edit_address'] ?? '';
    // Jersey number and position editing removed
    
    // Validate inputs
    if (empty($name)) $errors[] = "Player name is required";
    if (empty($birthdate)) $errors[] = "Birthdate is required";
    if (empty($player_id)) $errors[] = "Player ID is required";
    
    // Get current player data
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ? AND team_id = ?");
    $stmt->execute([$player_id, $team_id]);
    $current_player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_player) {
        $errors[] = "Player not found";
    }
    
    $photo_path = $current_player['photo_path'] ?? null;
    $document_path = $current_player['document_path'] ?? null;
    $birth_certificate_path = $current_player['birth_certificate_path'] ?? null;
    
    // Handle photo upload if provided
    if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
        $photo_ext = strtolower(pathinfo($_FILES['edit_photo']['name'], PATHINFO_EXTENSION));
        $allowed_photo_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($photo_ext, $allowed_photo_ext)) {
            $photo_name = uniqid('player_', true) . '.' . $photo_ext;
            $new_photo_path = 'uploads/photos/' . $photo_name;
            
            if (move_uploaded_file($_FILES['edit_photo']['tmp_name'], $new_photo_path)) {
                // Delete old photo if exists
                if ($photo_path && file_exists($photo_path)) {
                    unlink($photo_path);
                }
                $photo_path = $new_photo_path;
            } else {
                $errors[] = "Failed to upload new photo";
            }
        } else {
            $errors[] = "Invalid photo format. Only JPG, JPEG, PNG, and GIF are allowed";
        }
    }
    
    // Handle document upload if provided (Valid ID)
    if (isset($_FILES['edit_document']) && $_FILES['edit_document']['error'] === UPLOAD_ERR_OK) {
        $doc_ext = strtolower(pathinfo($_FILES['edit_document']['name'], PATHINFO_EXTENSION));
        $allowed_doc_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (in_array($doc_ext, $allowed_doc_ext)) {
            $doc_name = uniqid('doc_', true) . '.' . $doc_ext;
            $new_document_path = 'uploads/documents/' . $doc_name;
            
            if (move_uploaded_file($_FILES['edit_document']['tmp_name'], $new_document_path)) {
                // Delete old document if exists
                if ($document_path && file_exists($document_path)) {
                    unlink($document_path);
                }
                $document_path = $new_document_path;
            } else {
                $errors[] = "Failed to upload new document";
            }
    // Handle birth certificate upload if provided (optional)
    if (isset($_FILES['edit_birth_certificate']) && $_FILES['edit_birth_certificate']['error'] === UPLOAD_ERR_OK) {
        $bc_ext = strtolower(pathinfo($_FILES['edit_birth_certificate']['name'], PATHINFO_EXTENSION));
        $allowed_bc_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (in_array($bc_ext, $allowed_bc_ext)) {
            $bc_name = uniqid('birth_', true) . '.' . $bc_ext;
            $new_bc_path = 'uploads/documents/' . $bc_name;
            if (move_uploaded_file($_FILES['edit_birth_certificate']['tmp_name'], $new_bc_path)) {
                if ($birth_certificate_path && file_exists($birth_certificate_path)) {
                    unlink($birth_certificate_path);
                }
                $birth_certificate_path = $new_bc_path;
            } else {
                $errors[] = "Failed to upload new birth certificate";
            }
        } else {
            $errors[] = "Invalid birth certificate format. Only JPG, JPEG, PNG, GIF, and PDF are allowed";
        }
    }
        } else {
            $errors[] = "Invalid document format. Only JPG, JPEG, PNG, GIF, and PDF are allowed";
        }
    }
    
    // If no errors, update player in database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE players SET name = ?, birthdate = ?, address = ?, 
                                  photo_path = ?, document_path = ?, birth_certificate_path = ? WHERE id = ? AND team_id = ?");
            $stmt->execute([$name, $birthdate, $address, $photo_path, $document_path, $birth_certificate_path, $player_id, $team_id]);
            
            $success = true;
            $_SESSION['message'] = "Player updated successfully";
            header("Location: players.php");
            exit();
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle player deletion
if (!$editing_locked && isset($_GET['delete'])) {
    $player_id = $_GET['delete'];
    
    // Verify player belongs to team
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ? AND team_id = ?");
    $stmt->execute([$player_id, $team_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($player) {
        // Delete files
        if ($player['photo_path'] && file_exists($player['photo_path'])) {
            unlink($player['photo_path']);
        }
        if ($player['document_path'] && file_exists($player['document_path'])) {
            unlink($player['document_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM players WHERE id = ? AND team_id = ?");
        $stmt->execute([$player_id, $team_id]);
        
        $_SESSION['message'] = "Player deleted successfully";
        header("Location: players.php");
        exit();
    } else {
        $errors[] = "Player not found or you don't have permission to delete this player";
    }
}

// Get all players for this team
$stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY name");
$stmt->execute([$team_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remove team-driven final submission; admin approval controls the lock
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Players - JACLUPAN BASKETBALL LEAGUE</title>
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 25px 0;
        }
        
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
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
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus {
            border-color: #1a237e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }
        
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            background: #fafafa;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        input[type="file"]:hover {
            border-color: #1a237e;
        }
        
        .file-hint {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
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
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        .section-title {
            color: #1a237e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .player-card {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            background: #fff;
            position: relative;
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
            display: flex;
            gap: 10px;
            margin-top: 15px;
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
        
        .final-submit {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        @media (max-width: 768px) {
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
            
            .player-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>JACLUPAN BASKETBALL LEAGUE</h1>
            <p>MANAGE PLAYERS</p>
        </div>
    </header>
    
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                Player added successfully!
            </div>
        <?php endif; ?>
        
        <div class="content">
            <div class="team-info">
                <h2><?php echo htmlspecialchars($team['team_name']); ?></h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-trophy"></i>
                        <span><strong>Division:</strong> <?php echo htmlspecialchars($team['division']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-tie"></i>
                        <span><strong>Coach:</strong> <?php echo htmlspecialchars($team['coach_name']); ?></span>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="team_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="content">
            <h2 class="section-title">Add New Player</h2>
            
            <form method="POST" action="" enctype="multipart/form-data" class="form-grid" <?php echo $editing_locked ? 'style="pointer-events:none;opacity:0.6;"' : ''; ?>>
                <div class="form-section">
                    <div class="form-group">
                        <label for="name">Player Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="birthdate">Birthdate *</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Street, Barangay, City/Municipality">
                    </div>
                    
            
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="photo">Player Photo *</label>
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                        <div class="file-hint">Accepted formats: JPG, JPEG, PNG, GIF</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="document">Valid ID or Any ID *</label>
                        <input type="file" id="document" name="document" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                        <div class="file-hint">Accepted formats: JPG, JPEG, PNG, GIF, PDF</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="birth_certificate">Birth Certificate (Optional)</label>
                        <input type="file" id="birth_certificate" name="birth_certificate" accept=".jpg,.jpeg,.png,.gif,.pdf">
                        <div class="file-hint">Accepted formats: JPG, JPEG, PNG, GIF, PDF</div>
                    </div>
                    
                    <button type="submit" name="add_player" class="btn" <?php echo $editing_locked ? 'disabled' : ''; ?>>
                        <i class="fas fa-plus"></i> Add Player
                    </button>
                </div>
            </form>
        </div>
        
        <div class="content">
            <h2 class="section-title">Registered Players (<?php echo count($players); ?>)</h2>
            
            <?php if (count($players) > 0): ?>
                <div class="player-grid">
                    <?php foreach ($players as $player): ?>
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
                                    <div class="player-details" style="font-weight:700; font-size:16px;"><?php 
                                        $dob = new DateTime($player['birthdate']);
                                        $today = new DateTime('today');
                                        echo max(0, $dob->diff($today)->y);
                                    ?></div>
                                    <?php if (!empty($player['address'])): ?>
                                        <div class="player-details">Address: <?php echo htmlspecialchars($player['address']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="player-actions">
                                <button type="button" class="btn btn-secondary" onclick="editPlayer(<?php echo $player['id']; ?>)" <?php echo $editing_locked ? 'disabled' : ''; ?>>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="<?php echo $editing_locked ? '#' : 'players.php?delete=' . (int)$player['id']; ?>" class="btn btn-danger" onclick="<?php echo $editing_locked ? 'return false;' : "return confirm('Are you sure you want to delete this player?');" ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Final submission removed; admin approval controls lock -->
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Players Added Yet</h3>
                    <p>Add players using the form above</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Player Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Player</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="player_id" id="edit_player_id">
                
                <div class="form-group">
                    <label for="edit_name">Player Full Name *</label>
                    <input type="text" id="edit_name" name="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_birthdate">Birthdate *</label>
                    <input type="date" id="edit_birthdate" name="edit_birthdate" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <input type="text" id="edit_address" name="edit_address">
                </div>
                
                
                
                <div class="form-group">
                    <label for="edit_photo">Player Photo (Leave empty to keep current)</label>
                    <input type="file" id="edit_photo" name="edit_photo" accept="image/*">
                    <div class="file-hint">Accepted formats: JPG, JPEG, PNG, GIF</div>
                </div>
                
                <div class="form-group">
                    <label for="edit_document">Valid ID or Any ID (Leave empty to keep current)</label>
                    <input type="file" id="edit_document" name="edit_document" accept=".jpg,.jpeg,.png,.gif,.pdf">
                    <div class="file-hint">Accepted formats: JPG, JPEG, PNG, GIF, PDF</div>
                </div>
                
                <div class="form-group">
                    <label for="edit_birth_certificate">Birth Certificate (Optional)</label>
                    <input type="file" id="edit_birth_certificate" name="edit_birth_certificate" accept=".jpg,.jpeg,.png,.gif,.pdf">
                    <div class="file-hint">Accepted formats: JPG, JPEG, PNG, GIF, PDF</div>
                </div>
                
                <button type="submit" name="edit_player" class="btn">
                    <i class="fas fa-save"></i> Update Player
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function editPlayer(playerId) {
            // Fetch player data via AJAX
            fetch('get_player.php?id=' + playerId)
                .then(response => response.json())
                .then(player => {
                    if (player) {
                        document.getElementById('edit_player_id').value = player.id;
                        document.getElementById('edit_name').value = player.name;
                        document.getElementById('edit_birthdate').value = player.birthdate;
                        document.getElementById('edit_address').value = player.address || '';
                        // Jersey number and position removed from edit modal
                        
                        // Show modal
                        document.getElementById('editModal').style.display = 'flex';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading player data');
                });
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>