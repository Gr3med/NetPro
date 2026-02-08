<?php
// import_cards.php
$conn = new mysqli("localhost", "root", "", "mikrotik_system");

$message = "";
if (isset($_POST["import"])) {
    $fileName = $_FILES["file"]["tmp_name"];
    $card_type = $_POST['card_type']; // نوع الكروت المرفوعة

    if ($_FILES["file"]["size"] > 0) {
        $file = fopen($fileName, "r");
        $count = 0;
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // تنظيف البيانات
            $code = str_replace(['="', '"'], '', $column[0]); 
            $category = (int) str_replace(['="', '"'], '', $column[2]);

            if (!empty($code)) {
                // إدخال الكرت مع تحديد نوعه
                $sql = "INSERT INTO cards (code, category, type) VALUES ('$code', '$category', '$card_type')";
                if ($conn->query($sql)) $count++;
            }
        }
        $message = "تم استيراد $count كرت بنجاح كـ ($card_type)!";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<body style="font-family: Tahoma; text-align: center; padding: 50px;">
    <h2>رفع الكروت للمخزون</h2>
    <?php if ($message) echo "<h3 style='color:green'>$message</h3>"; ?>
    
    <form method="post" enctype="multipart/form-data" style="background:#f9f9f9; padding:20px; border:1px solid #ccc; display:inline-block;">
        <label>حدد نوع الكروت في هذا الملف:</label><br>
        <select name="card_type" style="padding:10px; margin:10px;">
            <option value="normal">كروت بيع عادية (للزبائن)</option>
            <option value="loan_stock">كروت سلفني (يخزنها النظام للتوزيع)</option>
            <option value="reward_stock">كروت جوائز (يخزنها النظام للمكافآت)</option>
        </select>
        <br><br>
        <input type="file" name="file" accept=".csv" required><br><br>
        <button type="submit" name="import" style="padding:10px 20px; background:blue; color:white; border:none;">رفع</button>
    </form>
</body>
</html>