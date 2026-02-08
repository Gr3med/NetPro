<?php
require '../config.php';
if (isset($_SESSION['admin_id'])) header("Location: index.php");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = clean($_POST['username']);
    $pass = $_POST['password'];

    // تحقق من الأدمن (يمكنك تغيير البيانات هنا أو في قاعدة البيانات)
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit;
    } else {
        $msg = "بيانات الدخول غير صحيحة!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>دخول الإدارة | NetPro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); }
        .login-card { width: 100%; max-width: 350px; text-align: center; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white; backdrop-filter: blur(10px); }
        input { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; }
        input::placeholder { color: rgba(255,255,255,0.5); }
        input:focus { background: rgba(255,255,255,0.2); border-color: var(--primary-light); }
    </style>
</head>
<body>
    <div class="card login-card">
        <div style="margin-bottom: 30px;">
            <i class="fas fa-user-shield" style="font-size: 3rem; color: var(--primary-light);"></i>
            <h2 style="margin: 10px 0;">لوحة الإدارة</h2>
        </div>
        
        <?php if($msg): ?>
            <div style="background: rgba(239, 68, 68, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                ⚠️ <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required style="margin-bottom: 15px;">
            <input type="password" name="password" placeholder="كلمة المرور" required style="margin-bottom: 20px;">
            <button class="btn btn-primary" style="width: 100%;">دخول</button>
        </form>
    </div>
</body>
</html>