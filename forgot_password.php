<?php
include 'config.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
	<title>Forgot Password - JACLUPAN BASKETBALL LEAGUE</title>
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
		.container {
			width: 100%;
			max-width: 480px;
			padding: 30px;
			background: white;
			border-radius: 8px;
			box-shadow: 0 4px 15px rgba(0,0,0,0.1);
		}
		.header {
			text-align: center;
			margin-bottom: 20px;
		}
		.header h1 { color:#1a237e; font-size: 22px; margin-bottom: 6px; }
		.header p { color:#666; }
		.form-group { margin-bottom: 16px; }
		label { display:block; margin-bottom:8px; font-weight:bold; color:#555; }
		input[type="text"], input[type="email"] { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:4px; font-size:16px; }
		button { width:100%; padding:12px; background:#1a237e; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:16px; font-weight:bold; }
		button:hover { background:#283593; }
		.error { background:#ffebee; color:#c62828; padding:12px; margin:15px 0; border-radius:4px; border-left:4px solid #c62828; }
		.error ul { margin-left:20px; }
		.success { background:#e8f5e9; color:#2e7d32; padding:12px; margin:15px 0; border-radius:4px; border-left:4px solid #2e7d32; }
		.link { display:block; text-align:center; margin-top:16px; color:#1a237e; text-decoration:none; }
		.link:hover { text-decoration:underline; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1>JACLUPAN BASKETBALL LEAGUE</h1>
			<p>Forgot Password</p>
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

		<?php if ($success_message): ?>
			<div class="success"><?php echo $success_message; ?></div>
		<?php endif; ?>

		<form method="POST" action="">
			<div class="form-group">
				<label for="fp_username">Username</label>
				<input type="text" id="fp_username" name="fp_username" required>
			</div>
			<div class="form-group">
				<label for="fp_email">Email Address</label>
				<input type="email" id="fp_email" name="fp_email" required>
			</div>
			<button type="submit">Generate temporary password</button>
		</form>

		<a href="team_login.php" class="link">← Back to Login</a>
	</div>
</body>
</html>


