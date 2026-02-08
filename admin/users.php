<?php
require '../config.php';
// التحقق من صلاحية الأدمن
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$msg = "";

// ---------------------------------------------------------
// 1. معالجة طلب تعديل النقاط (إضافة / خصم)
// ---------------------------------------------------------
if (isset($_POST['update_points'])) {
    $uid = $_POST['user_id'];
    $amount = (int)$_POST['amount'];
    $type = $_POST['operation']; // 'add' أو 'deduct'
    $note = clean($_POST['note']);

    if ($uid && $amount > 0) {
        try {
            // بدأ العملية الآمنة
            $pdo->beginTransaction();

            // أ. تحديث رصيد المستخدم
            if ($type == 'add') {
                $stmt = $pdo->prepare("UPDATE users SET wallet_points = wallet_points + ? WHERE id = ?");
                $stmt->execute([$amount, $uid]);
                
                $transType = 'admin_gift';
                $desc = "مكافأة إدارية: " . $note;
                $successMsg = "تم إضافة $amount نقطة للعميل بنجاح ✅";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET wallet_points = wallet_points - ? WHERE id = ?");
                $stmt->execute([$amount, $uid]);
                
                $transType = 'admin_deduct';
                $desc = "خصم إداري: " . $note;
                $successMsg = "تم خصم $amount نقطة من العميل ⚠️";
            }

            // ب. تسجيل العملية في السجل (مهم جداً)
            // إذا لم يكن الجدول موجوداً سيتوقف الكود هنا ويذهب لـ catch
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$uid, $transType, $amount, $desc]);

            // اعتماد التغييرات
            $pdo->commit();
            $msg = $successMsg;

        } catch (Exception $e) {
            // في حال حدوث أي خطأ، تراجع عن كل شيء
            $pdo->rollBack();
            // عرض رسالة الخطأ التقنية لتساعدك في الحل
            $msg = "خطأ في النظام: " . $e->getMessage(); 
        }
    } else {
        $msg = "يرجى إدخال كمية صحيحة أكبر من صفر.";
    }
}

// ---------------------------------------------------------
// 2. البحث وجلب العملاء
// ---------------------------------------------------------
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM users WHERE full_name LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>إدارة العملاء | NetPro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    
    <?php include 'sidebar.php'; ?>

    <main>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1 style="margin:0;">قاعدة العملاء 👥</h1>
        </div>

        <?php if($msg): ?>
            <div style="padding:15px; border-radius:12px; margin-bottom:20px; font-weight:bold; 
                background: <?php echo strpos($msg, 'خطأ') !== false ? '#fee2e2' : '#dcfce7'; ?>; 
                color: <?php echo strpos($msg, 'خطأ') !== false ? '#b91c1c' : '#166534'; ?>;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form class="card" style="padding:15px; margin-bottom:20px; display:flex; gap:10px;">
            <input type="text" name="q" placeholder="ابحث بالاسم أو الرقم..." value="<?php echo htmlspecialchars($search); ?>" style="background:#f9fafb;">
            <button class="btn btn-primary" style="padding:0 30px;">بحث</button>
        </form>

        <div class="card" style="padding:0; overflow:hidden;">
            <?php if(empty($users)): ?>
                <div style="padding:20px; text-align:center; color:var(--text-muted);">لا يوجد عملاء بهذا الاسم</div>
            <?php else: ?>
                <?php foreach($users as $u): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid #f3f4f6;">
                    
                    <div style="display:flex; align-items:center; gap:15px;">
                        <div style="width:45px; height:45px; background:#e0e7ff; color:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:1.1rem;">
                            <?php echo mb_substr($u['full_name'], 0, 1); ?>
                        </div>
                        <div>
                            <div style="font-weight:bold; color:var(--text-main);"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted); font-family:sans-serif; direction:ltr; text-align:right;"><?php echo $u['phone']; ?></div>
                        </div>
                    </div>
                    
                    <div style="text-align:left;">
                        <div style="font-weight:bold; color:var(--primary); margin-bottom:5px;"><?php echo number_format($u['wallet_points']); ?> نقطة</div>
                        <button onclick="openModal(<?php echo $u['id']; ?>, '<?php echo $u['full_name']; ?>')" class="btn btn-outline" style="padding:6px 15px; font-size:0.8rem;">
                            <i class="fas fa-edit"></i> تعديل
                        </button>
                    </div>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="pointsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div class="card" style="width:90%; max-width:400px; animation: slideUp 0.3s ease;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">تعديل رصيد: <span id="modalUser" style="color:var(--primary)"></span></h3>
        
        <form method="POST">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <label style="display:block; margin:10px 0 5px; font-weight:bold; font-size:0.9rem;">نوع العملية:</label>
            <select name="operation" style="margin-bottom:15px;">
                <option value="add">➕ إضافة نقاط (مكافأة)</option>
                <option value="deduct">➖ خصم نقاط (عقوبة/تصحيح)</option>
            </select>

            <label style="display:block; margin:10px 0 5px; font-weight:bold; font-size:0.9rem;">الكمية:</label>
            <input type="number" name="amount" placeholder="0" required style="margin-bottom:15px;">

            <label style="display:block; margin:10px 0 5px; font-weight:bold; font-size:0.9rem;">السبب (ملاحظة):</label>
            <input type="text" name="note" placeholder="مثلاً: تعويض عن انقطاع النت" required style="margin-bottom:20px;">

            <div style="display:flex; gap:10px;">
                <button name="update_points" class="btn btn-primary" style="flex:1;">حفظ وتنفيذ</button>
                <button type="button" onclick="document.getElementById('pointsModal').style.display='none'" class="btn btn-danger" style="flex:1; background:#f3f4f6; color:#333;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
// دالة فتح النافذة
function openModal(id, name) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalUser').innerText = name;
    document.getElementById('pointsModal').style.display = 'flex';
}

// إغلاق النافذة عند الضغط خارجها
window.onclick = function(event) {
    if (event.target == document.getElementById('pointsModal')) {
        document.getElementById('pointsModal').style.display = "none";
    }
}
</script>

<style>
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

</body>
</html>