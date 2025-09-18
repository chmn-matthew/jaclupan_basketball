<?php
include 'config.php';

// Handle login
$errors = [];
$success_message = '';

// Redirect if already logged in
if (isset($_SESSION['team_id']) && $_SESSION['team_id']) {
    header("Location: team_dashboard.php");
    exit();
}

// CSRF token management
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Basic CSRF check
    $posted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $errors[] = 'Invalid session token. Please refresh the page and try again.';
    }

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    
    if ($username === '' || $password === '') {
        $errors[] = "Username and password are required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM teams WHERE username = ?");
            $stmt->execute([$username]);
            $team = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($team && !empty($team['password']) && password_verify($password, $team['password'])) {
                session_regenerate_id(true);
                $_SESSION['team_id'] = $team['id'];
                header("Location: team_dashboard.php");
                exit();
            } elseif ($team && isset($team['password'])) {
                // Legacy fallback: if stored password was plaintext, accept once and upgrade to hash
                $stored = (string)$team['password'];
                $isLikelyHashed = strlen($stored) >= 60 && $stored[0] === '$';
                if (!$isLikelyHashed && hash_equals($stored, $password)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE teams SET password = ? WHERE id = ?");
                    $upd->execute([$newHash, $team['id']]);
                    session_regenerate_id(true);
                    $_SESSION['team_id'] = $team['id'];
                    header("Location: team_dashboard.php");
                    exit();
                } else {
                    $errors[] = "Invalid username or password";
                }
            } else {
                $errors[] = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Pre-fill username if available
$prefill_username = isset($_SESSION['team_username']) ? $_SESSION['team_username'] : '';
unset($_SESSION['team_username']);

// Handle forgot password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    $fp_username = isset($_POST['fp_username']) ? trim($_POST['fp_username']) : '';
    $fp_email = isset($_POST['fp_email']) ? trim($_POST['fp_email']) : '';
    if ($fp_username === '' || $fp_email === '') {
        $errors[] = 'Username and email are required for password reset';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM teams WHERE username = ? AND email = ?");
            $stmt->execute([$fp_username, $fp_email]);
            $team = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($team) {
                $temp = substr(bin2hex(random_bytes(8)), 0, 10);
                $hash = password_hash($temp, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE teams SET password = ? WHERE id = ?");
                $upd->execute([$hash, $team['id']]);
                $success_message = 'Temporary password: ' . htmlspecialchars($temp) . ' (please login and change it)';
            } else {
                $errors[] = 'No account found with that username and email';
            }
        } catch (Exception $e) {
            $errors[] = 'Unable to reset password at this time';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Login - JACLUPAN BASKETBALL LEAGUE</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .league-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .league-header h1 {
            color: #1a237e;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .league-header p {
            color: #666;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="email"], input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus, input[type="text"]:focus, input[type="password"]:focus {
            border-color: #1a237e;
            outline: none;
            box-shadow: 0 0 0 2px rgba(26, 35, 126, 0.2);
        }
        .password-wrapper { position: relative; }
        /* Right padding to keep text clear of the small toggle */
        .password-wrapper input { width: 100%; box-sizing: border-box; padding-right: 72px; }
        .password-wrapper .toggle-btn {
            position:absolute;
            right:8px;
            top:50%;
            transform: translateY(-50%);
            background:transparent;
            border:none;
            border-radius:0;
            padding:4px 6px;
            cursor:pointer;
            font-size:12px;
            line-height:1;
            color:#1a237e;
            z-index: 2; /* stay above input but only in padded area */
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #1a237e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #283593;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid #c62828;
        }
        
        .error ul {
            margin-left: 20px;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #1a237e;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
            text-align: center;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
            }
            
            .league-header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="league-header">
            <h1>JACLUPAN BASKETBALL LEAGUE</h1>
            <p>SEASON 1 - TEAM LOGIN</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" autocomplete="on" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($prefill_username); ?>" required autocomplete="username" minlength="3" maxlength="50">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" autocomplete="current-password" required inputmode="text">
                    <button type="button" class="toggle-btn" id="togglePassword" aria-label="Show password">Show</button>
                </div>
            </div>
            
            <button type="submit" name="login">Login to Team Dashboard</button>
        </form>
        
        <div style="margin-top: 12px; text-align: right;">
            <a href="forgot_password.php" style="color:#1a237e; text-decoration:none; font-weight:bold;">Forgot password?</a>
        </div>
        
        <a href="index.php" class="back-link">← Back to Team Registration</a>
    </div>
    <script>
    (function(){
        var btn = document.getElementById('togglePassword');
        var input = document.getElementById('password');
        if (btn && input) {
            btn.addEventListener('click', function(e){
                e.preventDefault();
                var isPwd = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPwd ? 'text' : 'password');
                btn.textContent = isPwd ? 'Hide' : 'c';
                btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
                input.focus();
            });
        }
    })();
    </script>
</body>
</html>