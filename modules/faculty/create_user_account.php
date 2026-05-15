<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

$faculty_id = $_GET['id'] ?? null;

if (!$faculty_id) {
    header("Location: faculty.php");
    exit;
}

// Get faculty information
$stmt = $conn->prepare("SELECT * FROM faculty WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();

if (!$faculty) {
    header("Location: faculty.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username and password are required';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = 'Username already exists';
        } else {
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, first_name, middle_name, last_name, password, role_id, is_active) VALUES (?, ?, ?, ?, ?, 4, 1)");
            $stmt->bind_param("sssss", $username, $faculty['fname'], $faculty['mname'], $faculty['lname'], $hashed_password);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Update faculty record to link to user account
                $stmt = $conn->prepare("UPDATE faculty SET user_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $user_id, $faculty_id);
                $stmt->execute();
                
                $_SESSION['success'] = 'User account created successfully for ' . $faculty['name'];
                header("Location: faculty.php");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create user account';
            }
        }
    }
}

// Generate suggested username
$suggested_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $faculty['fname'] . $faculty['lname']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        body { 
            background: #ffffff;
            color: #333;
            min-height: 100vh;
            line-height: 1.6;
        }
        .container { 
            width: 90%;
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .form-group {
            margin-bottom: 24px;
        }
        label { 
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #7B0000;
            font-size: 1em;
            margin-bottom: 12px;
        }
        label i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .help-text i {
            color: #7B0000;
        }
        .faculty-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #7B0000;
        }
        .faculty-info h3 {
            color: #7B0000;
            margin-bottom: 10px;
        }
        .faculty-info p {
            color: #666;
            margin-bottom: 5px;
        }
        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: center;
        }
        button, .buttons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .submit-btn {
            background: #7B0000;
            color: white;
            border: 1px solid #7B0000;
        }
        .submit-btn:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }
        .cancel-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
        }
        .cancel-btn:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .suggested-username {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .suggested-username:hover {
            background: #bbdefb;
        }
        .suggested-username i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>
<div class="header">
    <span>Create User Account</span>
</div>

<div class="container">
    <div class="faculty-info">
        <h3><?php echo htmlspecialchars($faculty['name']); ?></h3>
        <p><strong>Position:</strong> <?php echo htmlspecialchars($faculty['position']); ?></p>
        <?php if (!empty($faculty['specialization'])): ?>
        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($faculty['specialization']); ?></p>
        <?php endif; ?>
    </div>

    <form action="" method="POST">
        <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($suggested_username); ?>" required>
            <div class="suggested-username" onclick="useSuggestedUsername()">
                <i class="fas fa-lightbulb"></i>
                Suggested username: <?php echo htmlspecialchars($suggested_username); ?>
            </div>
            <p class="help-text">
                <i class="fas fa-info-circle"></i>
                Username must be unique and will be used for login
            </p>
        </div>

        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password</label>
            <input type="password" id="password" name="password" value="faculty123" required>
            <p class="help-text">
                <i class="fas fa-info-circle"></i>
                Default password is 'faculty123'. Faculty should change this after first login.
            </p>
        </div>

        <div class="buttons">
            <button type="submit" class="submit-btn"><i class="fas fa-user-plus"></i> Create Account</button>
            <a href="faculty.php" class="cancel-btn"><i class="fas fa-arrow-left"></i> Back to Faculty</a>
        </div>
    </form>
</div>

<script>
function useSuggestedUsername() {
    document.getElementById('username').value = '<?php echo htmlspecialchars($suggested_username); ?>';
}
</script>

</body>
</html>
