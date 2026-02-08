<?php
require '../config.php';
if (!isset($_SESSION['admin_id'])) header("Location: login.php");

if (isset($_POST['download'])) {
    $tables = ['users', 'inventory', 'transactions', 'admins'];
    $content = "-- NetPro Backup: " . date("Y-m-d H:i:s") . "\n\n";

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT * FROM $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_keys($row);
            $vals = array_map(function($v){ return $v === null ? "NULL" : "'" . addslashes($v) . "'"; }, array_values($row));
            $content .= "INSERT INTO $table (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
        }
        $content .= "\n";
    }

    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=backup_".date("Y-m-d").".sql");
    echo $content;
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>النسخ الاحتياطي | NetPro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="app-container">
    <?php include 'sidebar.php'; ?>
    <main>
        <h1>حماية البيانات 🛡️</h1>
        <div class="card" style="text-align:center; padding:40px;">
            <i class="fas fa-database" style="font-size:4rem; color:var(--primary); margin-bottom:20px;"></i>
            <h3>النسخ الاحتياطي لقاعدة البيانات</h3>
            <p style="color:var(--text-muted); margin-bottom:30px;">قم بتحميل نسخة كاملة من بيانات العملاء والمخزون والعمليات وحفظها في جهازك.</p>
            
            <form method="POST">
                <button name="download" class="btn btn-primary" style="font-size:1.1rem; padding:15px 40px;">
                    <i class="fas fa-download"></i> تحميل النسخة الآن
                </button>
            </form>
        </div>
    </main>
</div>
</body>
</html>