<?php
require '../config.php';
if (!isset($_SESSION['admin_id'])) header("Location: login.php");

$msg = "";
// رفع الكروت CSV
if (isset($_POST['upload'])) {
    $type = $_POST['type'];
    $file = $_FILES['csv']['tmp_name'];
    if ($file) {
        $handle = fopen($file, "r");
        $c = 0;
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT IGNORE INTO inventory (code, amount, type, status) VALUES (?, ?, ?, 'available')");
        while (($d = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $code = preg_replace('/[^0-9]/', '', $d[0] ?? '');
            $amt = preg_replace('/[^0-9]/', '', $d[2] ?? ''); // نفترض أن العمود الثالث هو السعر
            if ($code && $amt) { $stmt->execute([$code, $amt, $type]); $c++; }
        }
        $pdo->commit();
        $msg = "تم استيراد $c كرت بنجاح ✅";
    }
}

// الإحصائيات
$users_count = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
$stock = $pdo->query("SELECT type, count(*) as c FROM inventory WHERE status='available' GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
$income = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type='recharge'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>لوحة القيادة | NetPro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'sidebar.php'; ?>

    <main>
        <h1 style="margin-bottom:20px;">نظرة عامة 🚀</h1>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:30px;">
            <div class="card" style="border-right:5px solid var(--primary);">
                <div style="color:var(--text-muted);">إجمالي العملاء</div>
                <div style="font-size:1.8rem; font-weight:bold;"><?php echo number_format($users_count); ?></div>
            </div>
            <div class="card" style="border-right:5px solid #10b981;">
                <div style="color:var(--text-muted);">إجمالي المبيعات</div>
                <div style="font-size:1.8rem; font-weight:bold; color:#10b981;"><?php echo number_format($income); ?> ريال</div>
            </div>
            <div class="card" style="border-right:5px solid #f59e0b;">
                <div style="color:var(--text-muted);">مخزون الطوارئ</div>
                <div style="font-size:1.8rem; font-weight:bold; color:#f59e0b;"><?php echo number_format($stock['loan']??0); ?></div>
            </div>
            <div class="card" style="border-right:5px solid #6366f1;">
                <div style="color:var(--text-muted);">مخزون المبيعات</div>
                <div style="font-size:1.8rem; font-weight:bold; color:#6366f1;"><?php echo number_format($stock['sales']??0); ?></div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-cloud-upload-alt"></i> استيراد كروت</h3>
            <?php if($msg) echo "<p style='background:#dcfce7; color:#166534; padding:10px; border-radius:8px;'>$msg</p>"; ?>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top:20px;">
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <select name="type">
                        <option value="sales">🛒 مبيعات (شحن)</option>
                        <option value="loan">🚑 طوارئ (سلف)</option>
                        <option value="reward">🎁 مكافآت (متجر)</option>
                    </select>
                </div>
                
                <input type="file" name="csv" accept=".csv" required style="padding:10px; background:white;">
                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">الملف يجب أن يكون CSV: (الكود, الرقم التسلسلي, المبلغ)</p>
                
                <button name="upload" class="btn btn-primary" style="margin-top:15px;">رفع وتخزين</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>