<?php
require_once 'config.php';

// Generate CSRF token
$csrf_token = generateCSRFToken();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Validate CSRF token
        if (!validateCSRFToken()) {
            $error = "Security token invalid. Please try again.";
        } else {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            // Rate limiting check
            if (!checkRateLimit($username . $_SERVER['REMOTE_ADDR'])) {
                $error = "Too many login attempts. Please try again in 15 minutes.";
            } else {
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT id, username, password, email, full_name, role, is_active FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Check if account is active
                    if (!$user['is_active']) {
                        $error = "Account is deactivated. Please contact administrator.";
                    } else if (password_verify($password, $user['password'])) {
                        // Successful login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['login_time'] = time();
                        
                        // Log login activity
                        $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                        $log_stmt->bind_param("iss", $user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                        $log_stmt->execute();
                        $log_stmt->close();
                        
                        // Clear login attempts
                        $clear_stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
                        $identifier = $username . $_SERVER['REMOTE_ADDR'];
                        $clear_stmt->bind_param("s", $identifier);
                        $clear_stmt->execute();
                        $clear_stmt->close();
                        
                        // Set remember me cookie if requested
                        if (isset($_POST['remember'])) {
                            $token = bin2hex(random_bytes(32));
                            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                            setcookie('remember_token', $token, $expiry, '/');
                            
                            $token_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                            $expiry_date = date('Y-m-d H:i:s', $expiry);
                            $token_stmt->bind_param("iss", $user['id'], $token, $expiry_date);
                            $token_stmt->execute();
                            $token_stmt->close();
                        }
                        
                        $stmt->close();
                        $conn->close();
                        
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid password!";
                    }
                } else {
                    $error = "User not found!";
                }
                
                $stmt->close();
                $conn->close();
            }
        }
    }
}

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Event Management System</title>
    <!-- UIKIT CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.16.0/dist/css/uikit.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .login-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e5e5;
        }
        .form-icon {
            position: absolute;
            left: -25px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }
        .form-control {
            padding-left: 45px;
        }
        .role-badge {
            font-size: 0.7em;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="uk-text-center">
                    <i class="fas fa-graduation-cap" style="font-size: 3em; margin-bottom: 15px;"></i>
                    <h2 class="uk-margin-remove">School Event System</h2>
                    <p class="uk-margin-remove-top">Sign in to your account</p>
                </div>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="uk-alert-danger" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="uk-alert-warning" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p><i class="fas fa-clock"></i> Your session has expired. Please login again.</p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="uk-margin">
                        <div class="uk-inline uk-width-1-1">
                            <span class="form-icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input class="uk-input uk-form-large" type="text" name="username" placeholder="Username" required>
                        </div>
                    </div>
                    
                    <div class="uk-margin">
                        <div class="uk-inline uk-width-1-1" style="position: relative;">
                            <span class="form-icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input class="uk-input uk-form-large" type="password" name="password" id="password" placeholder="Password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="uk-margin">
                        <button type="submit" name="login" class="uk-button uk-button-primary uk-button-large uk-width-1-1">
                            <i class="fas fa-sign-in-alt uk-margin-small-right"></i>Sign In
                        </button>
                    </div>
                    
                    <div class="uk-margin">
                        <label>
                            <input class="uk-checkbox" type="checkbox" name="remember"> Remember me
                        </label>
                    </div>
                </form>
                
            </div>
            
            <div class="login-footer">
                <p class="uk-text-small uk-text-muted">
                    <a href="#forgot-password" uk-toggle>Forgot your password?</a> | 
                    Don't have an account? <a href="#contact-admin" uk-toggle>Contact administrator</a>
                </p>
                <a href="index.php" class="uk-button uk-button-default uk-button-small">
                    <i class="fas fa-home uk-margin-small-right"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Contact Admin Modal -->
    <div id="contact-admin" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close></button>
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">Contact Administrator</h2>
            </div>
            <div class="uk-modal-body">
                <p>To create a new account, please contact your system administrator:</p>
                <div class="uk-grid-small" uk-grid>
                    <div class="uk-width-1-4"><strong>Email:</strong></div>
                    <div class="uk-width-3-4">admin@schoolsystem.edu</div>
                    
                    <div class="uk-width-1-4"><strong>Phone:</strong></div>
                    <div class="uk-width-3-4">(555) 123-4567</div>
                    
                    <div class="uk-width-1-4"><strong>Office:</strong></div>
                    <div class="uk-width-3-4">Main Administration Building, Room 101</div>
                </div>
            </div>
            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-default uk-modal-close" type="button">Close</button>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-password" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close></button>
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">Forgot Password</h2>
            </div>
            <div class="uk-modal-body">
                <p>Please contact your system administrator to reset your password:</p>
                <div class="uk-grid-small" uk-grid>
                    <div class="uk-width-1-4"><strong>Email:</strong></div>
                    <div class="uk-width-3-4">admin@schoolsystem.edu</div>
                    
                    <div class="uk-width-1-4"><strong>Phone:</strong></div>
                    <div class="uk-width-3-4">(555) 123-4567</div>
                </div>
                <hr>
                <p class="uk-text-small uk-text-muted">
                    For security reasons, password resets must be processed by the system administrator.
                </p>
            </div>
            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-default uk-modal-close" type="button">Close</button>
            </div>
        </div>
    </div>

    <!-- UIKIT JS -->
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.16.0/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.16.0/dist/js/uikit-icons.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
        
        function fillDemo(username, password) {
            document.querySelector('input[name="username"]').value = username;
            document.querySelector('input[name="password"]').value = password;
        }
    </script>
</body>
</html>