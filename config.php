<?php
// config.php
$db_host = 'localhost';
$db_name = 'netpro_enterprise';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ اتصال بالقاعدة");
}

session_start();

define('POINTS_RATE', 100);
define('POINT_VALUE', 10);
// رقم الدعم الفني (عدله لرقمك)
define('SUPPORT_PHONE', '967712272493'); 

// 1. دالة التنظيف
function clean($data) {
    if (is_null($data)) return '';
    return htmlspecialchars(strip_tags(trim($data)));
}

// 2. دالة فحص المحاولات (الحماية)
function checkLoginAttempts($ip) {
    global $pdo;
    // مسح المحاولات القديمة (التي مر عليها 15 دقيقة)
    $pdo->prepare("DELETE FROM login_attempts WHERE last_attempt < (NOW() - INTERVAL 15 MINUTE)")->execute();

    // فحص المحاولات الحالية
    $stmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $count = $stmt->fetchColumn();

    if ($count >= 5) {
        // إذا وصل 5 محاولات فاشلة
        die("
            <div style='text-align:center; padding:50px; font-family:sans-serif;'>
                <h1 style='color:red'>🚫 تم حظرك مؤقتاً</h1>
                <p>لقد تجاوزت الحد المسموح من محاولات الدخول الخاطئة.</p>
                <p>يرجى الانتظار <b>15 دقيقة</b> والمحاولة مجدداً.</p>
            </div>
        ");
    }
}

// 3. دالة تسجيل فشل الدخول
function recordFailedLogin($ip) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
    $stmt->execute([$ip]);
}

// 4. دالة مسح المحاولات (عند النجاح)
function clearLoginAttempts($ip) {
    global $pdo;
    $pdo->prepare("DELETE FROM login_attempts WHERE ip=?")->execute([$ip]);
}

function response($status, $msg, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'msg' => $msg, 'data' => $data]);
    exit;
}
?>