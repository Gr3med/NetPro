<?php
// 1. الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "mikrotik_system");

// بيانات قادمة من المستخدم (بعد تسجيل الدخول)
$user_id = $_SESSION['user_id']; // آيدي المستخدم الحالي
$card_code = $_POST['card_code']; // الكود الذي أدخله

// 2. التحقق من صحة الكرت
$sql = "SELECT * FROM cards WHERE code = '$card_code' AND is_used = 0";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $card = $result->fetch_assoc();
    $card_value = $card['category']; // فئة الكرت: 200, 500...
    $final_time = $card['duration_minutes']; // الوقت الأصلي للكرت

    // 3. جلب بيانات المستخدم لفحص الديون
    $user_sql = "SELECT * FROM users WHERE id = $user_id";
    $user = $conn->query($user_sql)->fetch_assoc();

    // 4. خصم الدين (إذا وجد)
    if ($user['loan_balance'] > 0) {
        // لنفترض أن الدين يحسب بالدقائق، نخصمه من وقت الكرت الجديد
        // يجب وضع معادلة تناسبك، هنا خصمنا الدين كامل من وقت الكرت
        $final_time = $final_time - $user['loan_balance']; 
        
        // تصفير الدين في قاعدة البيانات
        $conn->query("UPDATE users SET loan_balance = 0 WHERE id = $user_id");
        echo "تم خصم سلفة سابقة من وقت الكرت الجديد.";
    }

    // 5. منطق "اجمع واربح" (Logic)
    $reward_msg = "";
    
    // زيادة العداد حسب الفئة
    if ($card_value == 200) {
        $new_count = $user['count_200'] + 1;
        if ($new_count >= 10) {
            // تحقق الشرط! امنحه كرت مجاني
            // هنا يمكنك توليد كرت جديد وإظهاره له، أو إضافة الوقت مباشرة لرصيده
            $final_time += $card['duration_minutes']; // إضافة وقت كرت كامل مجاناً
            $new_count = 0; // تصفير العداد
            $reward_msg = "مبروك! لقد أتممت 10 كروت وحصلت على كرت مجاني (تمت إضافته لرصيدك).";
        }
        $conn->query("UPDATE users SET count_200 = $new_count WHERE id = $user_id");
    } 
    elseif ($card_value == 500) {
        $new_count = $user['count_500'] + 1;
        if ($new_count >= 7) {
            $final_time += $card['duration_minutes']; 
            $new_count = 0;
            $reward_msg = "مبروك! أتممت 7 كروت وحصلت على مكافأة فئة 500.";
        }
        $conn->query("UPDATE users SET count_500 = $new_count WHERE id = $user_id");
    }
    // ... تكرر نفس الكود للفئات 1000 و 5000

    // 6. حرق الكرت (تمييزه كمستخدم)
    $conn->query("UPDATE cards SET is_used = 1, used_by = $user_id WHERE id = " . $card['id']);

    // 7. إرسال الأمر للميكروتك لفتح النت (User Manager / Hotspot User)
    // هنا نستخدم كلاس الـ API
    require('routeros_api.class.php');
    $API = new RouterosAPI();
    if ($API->connect('192.168.88.1', 'admin', 'password')) {
        
        // تعديل وقت المستخدم في الميكروتك (أو إنشاء يوزر جديد إذا لم يوجد)
        // إضافة الوقت $final_time لرصيد المستخدم
        
        // مثال بسيط: تعديل Limit-Uptime
        // ملاحظة: التعامل مع الوقت في الميكروتك يحتاج تحويل الدقائق لصيغة 1h30m
        
        $API->disconnect();
    }

    echo "تم شحن الرصيد بنجاح! " . $reward_msg;

} else {
    echo "الكرت غير صحيح أو مستخدم مسبقاً";
}
?>