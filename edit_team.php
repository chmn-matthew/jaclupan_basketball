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

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = $_POST['team_name'] ?? '';
    $coach_name = $_POST['coach_name'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $division = $_POST['division'] ?? '';
// Players field removed from edit form
    
    // Validate inputs
    if (empty($team_name)) $errors[] = "Team name is required";
    if (empty($coach_name)) $errors[] = "Coach name is required";
    if (empty($contact_number)) $errors[] = "Contact number is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($division)) $errors[] = "Division is required";
    // Players list removed from validation
    
    // Team logo upload removed from edit form
    
    // If no errors, update team in database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, coach_name = ?, contact_number = ?, 
                                  email = ?, division = ? WHERE id = ?");
            $stmt->execute([$team_name, $coach_name, $contact_number, $email, $division, $team_id]);
            
            $success = true;
            $_SESSION['message'] = "Team information updated successfully";
            
            // Refresh team data
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
            $stmt->execute([$team_id]);
            $team = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['team'] = $team;
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team - JACLUPAN BASKETBALL LEAGUE</title>
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
            max-width: 1000px;
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
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            border-color: #1a237e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }
        
        textarea {
            height: 120px;
            resize: vertical;
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
        
        .logo-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .logo-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #eee;
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
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>JACLUPAN BASKETBALL LEAGUE</h1>
            <p>EDIT TEAM INFORMATION</p>
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
                Team information updated successfully!
            </div>
        <?php endif; ?>
        
        <div class="content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="team_name">Team Name *</label>
                    <input type="text" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="coach_name">Coach Name *</label>
                    <input type="text" id="coach_name" name="coach_name" value="<?php echo htmlspecialchars($team['coach_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($team['contact_number']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($team['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="division">Division *</label>
                    <select id="division" name="division" required>
                        <option value="">-- Select Division --</option>
                        <option value="UNDER_12" <?php echo ($team['division'] == 'UNDER_12') ? 'selected' : ''; ?>>UNDER 12 DIVISION</option>
                        <option value="UNDER_16" <?php echo ($team['division'] == 'UNDER_16') ? 'selected' : ''; ?>>UNDER 16 DIVISION</option>
                        <option value="UNDER_20" <?php echo ($team['division'] == 'UNDER_20') ? 'selected' : ''; ?>>UNDER 20 DIVISION</option>
                        <option value="UNDER_30" <?php echo ($team['division'] == 'UNDER_30') ? 'selected' : ''; ?>>UNDER 30 DIVISION</option>
                        <option value="31_39" <?php echo ($team['division'] == '31_39') ? 'selected' : ''; ?>>31-39 DIVISION</option>
                        <option value="40_UP" <?php echo ($team['division'] == '40_UP') ? 'selected' : ''; ?>>40UP DIVISION</option>
                    </select>
                </div>
                
                
                
                
                
                <div class="actions">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Update Team
                    </button>
                    
                    <a href="team_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>