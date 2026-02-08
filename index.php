<?php
require 'config.php';
if (isset($_SESSION['user_id'])) header("Location: dashboard.php");

$msg = "";
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = clean($_POST['phone']);
    $pass = $_POST['password'];

    // التحقق من أن الرقم يمني (يبدأ بـ 7 وطوله 9)
    if (!preg_match('/^7[0-9]{8}$/', $phone)) {
        $msg = "يجب إدخال رقم يمني صحيح (9 أرقام ويبدأ بـ 7)";
    } else {
        if (isset($_POST['login'])) {
            checkLoginAttempts($ip);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $u = $stmt->fetch();

            if ($u && password_verify($pass, $u['password'])) {
                clearLoginAttempts($ip);
                $_SESSION['user_id'] = $u['id'];
                
                // حفظ التوكن (Remember Me)
                $token = bin2hex(random_bytes(32)); 
                $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $u['id']]);
                setcookie('remember_token', $token, time() + (86400 * 30), "/");

                header("Location: dashboard.php");
                exit;
            } else {
                recordFailedLogin($ip);
                $msg = "بيانات الدخول غير صحيحة";
            }
        } elseif (isset($_POST['register'])) {
            $name = clean($_POST['name']);
            $check = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $check->execute([$phone]);
            if ($check->rowCount() > 0) {
                $msg = "هذا الرقم مسجل مسبقاً!";
            } else {
                try {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, password) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $phone, $hash]);
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    header("Location: dashboard.php");
                    exit;
                } catch (Exception $e) { $msg = "حدث خطأ ما"; }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>دخول | NetPro</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #e0e7ff 0%, #f3f4f6 100%); padding: 20px; }
        .auth-card { width: 100%; max-width: 400px; text-align: center; border-top: 5px solid var(--primary); }
        .input-group { position: relative; margin-bottom: 15px; }
        .input-group i { position: absolute; top: 50%; right: 15px; transform: translateY(-50%); color: var(--text-muted); }
        .input-group input { padding-right: 40px; }
    </style>
</head>
<body>

    <div class="card auth-card">
        <div style="margin-bottom: 30px;">
            <div style="width: 70px; height: 70px; background: #e0e7ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; font-size: 2rem;">
                <i class="fas fa-cube"></i>
            </div>
            <h1 style="margin: 0; color: var(--primary);">NetPro</h1>
            <p style="color: var(--text-muted);">بوابة المشتركين</p>
        </div>

        <?php if($msg): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                ⚠️ <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div id="login-box">
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="phone" placeholder="رقم الهاتف (7xxxxxxxx)" required pattern="[7][0-9]{8}" maxlength="9" style="direction:ltr; text-align:right;">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="كلمة المرور" required>
                </div>
                <button name="login" class="btn btn-primary">تسجيل الدخول <i class="fas fa-arrow-left"></i></button>
            </form>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f3f4f6;">
                <button onclick="toggle('reg')" class="btn btn-outline">إنشاء حساب جديد</button>
                <a href="https://wa.me/<?php echo SUPPORT_PHONE; ?>" target="_blank" style="display:block; margin-top:15px; color:var(--text-muted); text-decoration:none; font-size:0.9rem;">نسيت كلمة المرور؟</a>
            </div>
        </div>

        <div id="reg-box" style="display:none">
            <form method="POST">
                <div class="input-group"><i class="fas fa-user"></i><input type="text" name="name" placeholder="الاسم الكامل" required></div>
                <div class="input-group"><i class="fas fa-phone"></i><input type="tel" name="phone" placeholder="رقم الهاتف (7xxxxxxxx)" required pattern="[7][0-9]{8}" maxlength="9"></div>
                <div class="input-group"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="كلمة المرور" required></div>
                <button name="register" class="btn btn-primary">إنشاء الحساب</button>
            </form>
            <button onclick="toggle('login')" class="btn btn-outline" style="margin-top: 15px;">الرجوع للدخول</button>
        </div>
    </div>

    <script>
        function toggle(id) {
            document.getElementById('login-box').style.display = id=='login'?'block':'none';
            document.getElementById('reg-box').style.display = id=='reg'?'block':'none';
        }
    </script>
</body>
</html>