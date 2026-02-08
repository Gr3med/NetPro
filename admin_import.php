<?php
require 'config.php';
$msg = "";

if (isset($_POST['upload'])) {
    $type = $_POST['card_type'];
    $file = $_FILES['csv_file']['tmp_name'];

    if ($file) {
        $handle = fopen($file, "r");
        $count = 0;
        $pdo->beginTransaction();
        
        // تحضير الاستعلام (سريع وآمن)
        $stmt = $pdo->prepare("INSERT IGNORE INTO cards (code, category, type) VALUES (?, ?, ?)");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // تنظيف البيانات من ="123"
            $raw_code = $data[0] ?? '';
            $raw_cat  = $data[2] ?? '';

            $code = preg_replace('/[^0-9]/', '', $raw_code);
            $cat  = preg_replace('/[^0-9]/', '', $raw_cat);

            if (!empty($code) && !empty($cat)) {
                $stmt->execute([$code, $cat, $type]);
                $count++;
            }
        }
        $pdo->commit();
        $msg = "تم رفع $count كرت بنجاح إلى مخزون: " . ($type == 'normal' ? 'البيع' : ($type=='loan'?'السلف':'الجوائز'));
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المخزون</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>📥 رفع الكروت للمخزون</h2>
            <?php if($msg) echo "<p style='color:green; font-weight:bold'>$msg</p>"; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>نوع الكروت في هذا الملف:</label>
                    <select name="card_type">
                        <option value="normal">كروت بيع عادية (للزبائن)</option>
                        <option value="loan">كروت سلف (للطوارئ)</option>
                        <option value="reward">كروت جوائز (للمكافآت)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>ملف CSV (من الميكروتك):</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                
                <button type="submit" name="upload" class="btn btn-primary">رفع الملف</button>
            </form>
            <br>
            <p>ملاحظة: النظام سيتجاهل الكروت المكررة تلقائياً.</p>
        </div>
    </div>
</body>
</html>