<?php
require 'config.php';

// منع الوصول المباشر أو غير المسجل
if (!isset($_SESSION['user_id'])) {
    response('error', 'يجب تسجيل الدخول أولاً');
}

$uid = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// جلب بيانات المستخدم للتأكد منها
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    response('error', 'بيانات المستخدم غير صحيحة');
}

try {
    // =========================================================
    // 1. عملية شحن الرصيد (Recharge)
    // =========================================================
    if ($action == 'recharge') {
        $code = clean($_POST['code']);
        
        // البحث عن الكرت (يجب أن يكون متاح + نوعه sales)
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE code = ? AND status = 'available' AND type = 'sales'");
        $stmt->execute([$code]);
        $card = $stmt->fetch();

        if ($card) {
            $pdo->beginTransaction();

            // أ. تحديث حالة الكرت إلى "مستخدم"
            $pdo->prepare("UPDATE inventory SET status = 'sold', used_by = ?, used_at = NOW() WHERE id = ?")
                ->execute([$uid, $card['id']]);

            // ب. حساب النقاط وإضافتها للمستخدم
            $points = floor(($card['amount'] / POINTS_RATE) * POINT_VALUE);
            $pdo->prepare("UPDATE users SET wallet_points = wallet_points + ? WHERE id = ?")
                ->execute([$points, $uid]);

            // ج. حفظ الكرت في محفظة العميل
            $pdo->prepare("INSERT INTO user_cards (user_id, card_code, amount, source) VALUES (?, ?, ?, 'purchased')")
                ->execute([$uid, $card['code'], $card['amount']]);

            // د. تسجيل العملية في السجل المالي (هام جداً)
            $desc = "شحن رصيد بقيمة {$card['amount']} ريال";
            $pdo->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'recharge', ?, ?)")
                ->execute([$uid, $card['amount'], $desc]);

            $pdo->commit();
            
            response('success', "تم الشحن بنجاح! حصلت على $points نقطة ولاء 💎", ['code' => $card['code']]);
        } else {
            response('error', 'كود الكرت غير صحيح أو تم استخدامه مسبقاً.');
        }
    }

    // =========================================================
    // 2. طلب سلفة طوارئ (Loan)
    // =========================================================
    elseif ($action == 'loan') {
        
        // شرط 1: لا توجد سلفة سابقة
        if ($user['loan_status'] == 'active') {
            response('error', 'عذراً، يجب سداد السلفة الحالية أولاً.');
        }

        // شرط 2: (حماية الاحتيال) يجب أن يكون قد اشترى كرت شحن واحد على الأقل سابقاً
        $check_history = $pdo->prepare("SELECT COUNT(*) FROM user_cards WHERE user_id = ? AND source = 'purchased'");
        $check_history->execute([$uid]);
        if ($check_history->fetchColumn() == 0) {
            response('error', 'خدمة السلفة متاحة فقط للعملاء الذين قاموا بالشحن سابقاً.');
        }

        // البحث عن كرت سلفة متاح
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE type = 'loan' AND status = 'available' LIMIT 1");
        $stmt->execute();
        $card = $stmt->fetch();

        if ($card) {
            $pdo->beginTransaction();

            // تحديث الكرت
            $pdo->prepare("UPDATE inventory SET status = 'sold', used_by = ?, used_at = NOW() WHERE id = ?")
                ->execute([$uid, $card['id']]);

            // تحديث حالة العميل
            $pdo->prepare("UPDATE users SET loan_status = 'active' WHERE id = ?")
                ->execute([$uid]);

            // حفظ في المحفظة
            $pdo->prepare("INSERT INTO user_cards (user_id, card_code, amount, source) VALUES (?, ?, ?, 'loan')")
                ->execute([$uid, $card['code'], $card['amount']]);

            // تسجيل العملية
            $pdo->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'loan', ?, 'سلفة طوارئ')")
                ->execute([$uid, $card['amount']]);

            $pdo->commit();

            response('success', 'تم منحك السلفة بنجاح، تم حفظ الكرت في محفظتك 🚑', ['code' => $card['code']]);
        } else {
            response('error', 'نعتذر، كروت الطوارئ نفذت مؤقتاً.');
        }
    }

    // =========================================================
    // 3. شراء من المتجر (Buy Reward)
    // =========================================================
    elseif ($action == 'buy_reward') {
        // لا يوجد تغيير كبير هنا، لكن سنضيف تسجيل العملية في الجدول الجديد
        // ... (يمكنك إضافة منطق المتجر لاحقاً إذا فعلت خاصية الهدايا في المخزون)
        // حالياً سنتركه بسيطاً
        response('error', 'المتجر تحت الصيانة حالياً');
    }

    else {
        response('error', 'طلب غير معروف');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // تسجيل الخطأ في ملف لوج داخلي وعدم عرضه للمستخدم لزيادة الأمان
    error_log($e->getMessage());
    response('error', 'حدث خطأ في النظام، حاول لاحقاً.');
}
?>