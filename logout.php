<?php
require 'config.php';

// 1. حذف التوكن من قاعدة البيانات
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$uid]);
}

// 2. تدمير الجلسة (Session)
session_unset();
session_destroy();

// 3. حذف الكوكيز من المتصفح (بإرجاع تاريخها للماضي)
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 4. العودة للرئيسية
header("Location: index.php");
exit;
?>