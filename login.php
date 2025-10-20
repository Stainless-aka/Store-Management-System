<?php
session_start();
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($user === '' || $pass === '') { 
        $error = 'Enter username and password.'; 
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$user]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user'] = $u['username'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - ABBA RISKY & CO Store</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #198754, #0d6efd);
      background-attachment: fixed;
      font-family: "Poppins", sans-serif;
    }
    .login-card {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      padding: 2.5rem;
      max-width: 420px;
      width: 100%;
      animation: fadeIn 0.8s ease-in-out;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }
    .brand-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #157347, #198754);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: #fff;
      font-size: 30px;
      box-shadow: 0 4px 12px rgba(25,135,84,0.3);
    }
    .btn-login {
      background: linear-gradient(90deg, #198754, #157347);
      border: none;
      transition: all 0.3s;
    }
    .btn-login:hover {
      background: linear-gradient(90deg, #157347, #198754);
      transform: scale(1.03);
    }
  </style>
</head>
<body>
  <div class="login-card text-center">
    <div class="brand-icon">
      <i class="bi bi-shop"></i>
    </div>
    <h4 class="text-success fw-bold mb-3">ABBA RISKY &amp; CO Store</h4>
    <p class="text-muted mb-4">Secure Admin Lyogin</p>
    
    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" class="text-start">
      <div class="form-floating mb-3">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
        <label for="username"><i class="bi bi-person me-2"></i>Username</label>
      </div>
      <div class="form-floating mb-4">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
      </div>
      <button class="btn btn-login text-white w-100 py-2 fw-semibold">Login</button>
    </form>
      </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
