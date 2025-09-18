<?php
include 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = $_POST['team_name'];
    $coach_name = $_POST['coach_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $division = $_POST['division'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    $errors = [];
    if (empty($team_name)) $errors[] = "Team name is required";
    if (empty($coach_name)) $errors[] = "Coach name is required";
    if (empty($contact_number)) $errors[] = "Contact number is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($division)) $errors[] = "Division is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    
    // Check if username already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM teams WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username already exists. Please choose a different one.";
            }
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO teams (team_name, coach_name, contact_number, email, division, username, password) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$team_name, $coach_name, $contact_number, $email, $division, $username, $hashed_password]);
            
            $_SESSION['success'] = "Team registered successfully! You can now login with your username and password.";
            $_SESSION['team_email'] = $email;
            $_SESSION['team_contact'] = $contact_number;
            header("Location: team_login.php");
            exit();
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
    <title>JACLUPAN BASKETBALL LEAGUE - SEASON 1</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Anton&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a237e;
            --primary-light: #534bae;
            --primary-dark: #000051;
            --accent: #ff6f00;
            --accent-light: #ffa040;
            --accent-dark: #c43e00;
            --light: #f5f5f5;
            --dark: #333;
            --gray: #e0e0e0;
            --success: #4caf50;
            --error: #f44336;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(to bottom, #f9f9f9, #eaeaea);
            color: var(--dark);
            line-height: 1.6;
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .logo-icon {
            font-size: 2.5rem;
            margin-right: 15px;
            color: var(--accent);
        }
        
        header h1 {
            font-family: 'Anton', sans-serif;
            font-size: 2.8rem;
            letter-spacing: 1px;
            margin-bottom: 5px;
            text-transform: uppercase;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        header p {
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        /* Main Content */
        main {
            flex: 1;
            padding: 30px 0;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .error {
            background-color: #ffebee;
            color: var(--error);
            border-left: 5px solid var(--error);
        }
        
        .success {
            background-color: #e8f5e9;
            color: var(--success);
            border-left: 5px solid var(--success);
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.8rem;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .card h2 i {
            margin-right: 10px;
            color: var(--accent);
        }
        
        .division-info {
            background: linear-gradient(to right, #e3f2fd, #bbdefb);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid var(--primary);
        }
        
        .division-info h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .division-info h3 i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .division-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .division-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .division-item i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        /* removed inline icons next to inputs */
        
        .input-with-icon input,
        .input-with-icon select {
            padding-left: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"],
        select {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--gray);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--primary-light);
        }
        
        button {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        button:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .btn-icon {
            margin-right: 10px;
        }
        
        /* Links */
        .links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .link-btn {
            display: inline-block;
            padding: 12px 25px;
            background: white;
            color: var(--primary);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid var(--primary);
        }
        
        .link-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 25px 0;
            margin-top: auto;
        }
        
        footer p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 2.2rem;
            }
            
            .division-list {
                grid-template-columns: 1fr;
            }
            
            .links {
                flex-direction: column;
                gap: 10px;
            }
            
            .link-btn {
                width: 100%;
                text-align: center;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .card:nth-child(2) {
            animation-delay: 0.2s;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-basketball-ball logo-icon"></i>
                <h1>JACLUPAN BASKETBALL LEAGUE</h1>
            </div>
            <p>SEASON 1 - TEAM REGISTRATION</p>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php
            // Load site-wide announcement (from admin.php saved file)
            $announcement_file = __DIR__ . DIRECTORY_SEPARATOR . 'announcement.json';
            if (file_exists($announcement_file)) {
                $raw = file_get_contents($announcement_file);
                $data = json_decode($raw, true);
                if (is_array($data) && !empty($data['active']) && !empty($data['message'])) {
                    echo '<div class="alert" style="background:#fff3cd;color:#856404;border-left:5px solid #ffc107;">'
                        . '<i class="fas fa-bullhorn" style="margin-right:8px;"></i>'
                        . htmlspecialchars($data['message'])
                        . '</div>';
                }
            }
            ?>
            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> 
                    <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><i class="fas fa-clipboard-list"></i> Team Registration Form</h2>
                <p>Register your team for JACLUPAN BASKETBALL LEAGUE SEASON 1</p>
                
                <div class="division-info">
                    <h3><i class="fas fa-trophy"></i> Available Divisions:</h3>
                    <div class="division-list">
                        <div class="division-item"><i class="fas fa-basketball-ball"></i> UNDER 12 DIVISION</div>
                        <div class="division-item"><i class="fas fa-basketball-ball"></i> UNDER 16 DIVISION</div>
                        <div class="division-item"><i class="fas fa-basketball-ball"></i> UNDER 20 DIVISION</div>
                        <div class="division-item"><i class="fas fa-basketball-ball"></i> UNDER 30 DIVISION</div>
                        <div class="division-item"><i class="fas fa-basketball-ball"></i> 31-39 DIVISION</div>
                        <div class="division-item"><i class="fas fa-basketball-ball"></i> 40UP DIVISION</div>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="team_name">Team Name</label>
                        <div class="input-with-icon">
                            <input type="text" id="team_name" name="team_name" required 
                                   placeholder="Enter your team name" value="<?php echo isset($_POST['team_name']) ? htmlspecialchars($_POST['team_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="coach_name">Coach Name</label>
                        <div class="input-with-icon">
                            <input type="text" id="coach_name" name="coach_name" required 
                                   placeholder="Enter coach's full name" value="<?php echo isset($_POST['coach_name']) ? htmlspecialchars($_POST['coach_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <div class="input-with-icon">
                            <input type="tel" id="contact_number" name="contact_number" required 
                                   placeholder="Enter contact number" value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <input type="email" id="email" name="email" required 
                                   placeholder="Enter email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="division">Select Division</label>
                        <div class="input-with-icon">
                            <select id="division" name="division" required>
                                <option value="">Select a division</option>
                                <option value="UNDER_12" <?php echo (isset($_POST['division']) && $_POST['division'] == 'UNDER_12') ? 'selected' : ''; ?>>UNDER 12 DIVISION</option>
                                <option value="UNDER_16" <?php echo (isset($_POST['division']) && $_POST['division'] == 'UNDER_16') ? 'selected' : ''; ?>>UNDER 16 DIVISION</option>
                                <option value="UNDER_20" <?php echo (isset($_POST['division']) && $_POST['division'] == 'UNDER_20') ? 'selected' : ''; ?>>UNDER 20 DIVISION</option>
                                <option value="UNDER_30" <?php echo (isset($_POST['division']) && $_POST['division'] == 'UNDER_30') ? 'selected' : ''; ?>>UNDER 30 DIVISION</option>
                                <option value="31_39" <?php echo (isset($_POST['division']) && $_POST['division'] == '31_39') ? 'selected' : ''; ?>>31-39 DIVISION</option>
                                <option value="40_UP" <?php echo (isset($_POST['division']) && $_POST['division'] == '40_UP') ? 'selected' : ''; ?>>40UP DIVISION</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <input type="text" id="username" name="username" required 
                                   placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Create a password (min. 6 characters)">
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your password">
                            <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    
                    <button type="submit"><i class="fas fa-paper-plane btn-icon"></i> REGISTER TEAM</button>
                </form>
                
                <div class="links">
                    <a href="team_login.php" class="link-btn"><i class="fas fa-sign-in-alt"></i> Team Login</a>
                    <a href="admin.php" class="link-btn"><i class="fas fa-lock"></i> Admin Panel</a>
                </div>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Registration Guidelines</h2>
                <ul style="list-style-type: none; padding-left: 20px;">
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 10px;"></i> All teams must complete this registration form</li>
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 10px;"></i> After registration, you can login using your username and password</li>
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 10px;"></i> Ensure all information provided is accurate</li>
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 10px;"></i> Registration is free of charge</li>
                    <li><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 10px;"></i> For inquiries, contact league officials</li>
                </ul>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> JACLUPAN BASKETBALL LEAGUE - SEASON 1. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>