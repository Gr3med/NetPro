<?php
// process.php
session_start();
$conn = new mysqli("localhost", "root", "", "mikrotik_system");
$user_id = $_SESSION['user_id'];

// دالة لتسجيل استخدام الكرت
function markCardAsUsed($conn, $card_code, $user_id) {
    $conn->query("UPDATE cards SET is_used = 1, used_by = $user_id, used_at = NOW() WHERE code = '$card_code'");
}

if ($_POST['action'] == 'recharge') {
    $code = $conn->real_escape_string($_POST['card_code']);
    
    // 1. فحص الكرت (يجب أن يكون من نوع normal وغير مستخدم)
    $sql = "SELECT * FROM cards WHERE code = '$code' AND is_used = 0 AND type = 'normal'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $card = $result->fetch_assoc();
        
        // 2. تحديث عدادات المستخدم
        $cat = $card['category'];
        
        // زيادة العداد حسب الفئة
        $col_name = "count_" . $cat; // مثال: count_500
        
        // التأكد من وجود العمود لتجنب الأخطاء
        $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
        if (isset($user[$col_name])) {
            $conn->query("UPDATE users SET $col_name = $col_name + 1 WHERE id = $user_id");
        }

        // 3. حرق الكرت في النظام
        markCardAsUsed($conn, $code, $user_id);
        
        // 4. تسديد الديون (مجرد تسجيل، لا يمكننا خصم وقت من الكرت لأنه خارجي)
        if ($user['loan_balance'] > 0) {
            $conn->query("UPDATE users SET loan_balance = 0 WHERE id = $user_id");
            echo "تم تسجيل الكرت بنجاح! وتم تصفير ديونك السابقة ✅";
        } else {
            echo "تم تسجيل الكرت وإضافة النقاط بنجاح! ✅";
        }

        // 5. فحص المكافآت (هل استحق جائزة؟)
        // هذا يتم فحصه في صفحة الداشبورد لإظهار زر "استلم الجائزة"
        
    } else {
        echo "خطأ: الكرت غير موجود أو تم استخدامه مسبقاً!";
    }
}

elseif ($_POST['action'] == 'get_loan') {
    // 1. هل عليه دين؟
    $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
    if ($user['loan_balance'] > 0) {
        die("عفواً، يجب عليك تسديد الدين السابق أولاً بشحن كرت جديد.");
    }

    // 2. البحث عن كرت سلفة متاح في المخزون
    // (افترضنا أن كروت السلف هي فئة 100 ريال أو 60 دقيقة مثلاً)
    $sql = "SELECT * FROM cards WHERE type = 'loan_stock' AND is_used = 0 LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $loan_card = $result->fetch_assoc();
        
        // 3. إعطاء الكرت للعميل وتسجيل الدين
        markCardAsUsed($conn, $loan_card['code'], $user_id);
        $conn->query("UPDATE users SET loan_balance = 1 WHERE id = $user_id"); // 1 تعني عليه دين واحد
        
        echo "success|" . $loan_card['code']; // نرسل الكود للداشبورد ليظهر للعميل
    } else {
        echo "عفواً، خدمة السلف غير متاحة حالياً (نفذت الكروت من المخزون).";
    }
}

elseif ($_POST['action'] == 'get_reward') {
    $category = (int)$_POST['category']; // فئة الجائزة المطلوبة (200, 500...)
    
    // التحقق من النقاط
    $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
    $required_points = 0;
    $point_col = "";

    if ($category == 200) { $required_points = 10; $point_col = "count_200"; }
    elseif ($category == 500) { $required_points = 7; $point_col = "count_500"; }
    // ...

    if ($user[$point_col] >= $required_points) {
        // البحث عن كرت جائزة من نفس الفئة
        $sql = "SELECT * FROM cards WHERE type = 'reward_stock' AND category = $category AND is_used = 0 LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $reward_card = $result->fetch_assoc();
            
            // خصم النقاط ومنح الكرت
            markCardAsUsed($conn, $reward_card['code'], $user_id);
            $conn->query("UPDATE users SET $point_col = $point_col - $required_points WHERE id = $user_id");
            
            echo "success|" . $reward_card['code'];
        } else {
            echo "عفواً، كروت الهدايا لهذه الفئة نفذت حالياً.";
        }
    } else {
        echo "لا تملك نقاطاً كافية!";
    }
}
?>