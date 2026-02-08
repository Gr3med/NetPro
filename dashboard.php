<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$uid = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$uid]);
$u = $user->fetch();

// جلب آخر 5 حركات
$trans = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$trans->execute([$uid]);
$history = $trans->fetchAll();

// قائمة الجوائز (المتجر)
$rewards = [200, 500, 1000, 2000, 5000];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>حسابي | NetPro</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="app-container">
    
    <aside class="desktop-sidebar">
        <h2>NetPro 💎</h2>
        <div style="margin-top:20px;">
            <p style="color:var(--text-muted)">أهلاً بك،</p>
            <h3><?php echo htmlspecialchars($u['full_name']); ?></h3>
        </div>
        <nav style="margin-top:40px; display:flex; flex-direction:column; gap:10px;">
            <a href="#home" class="btn btn-outline" style="justify-content:flex-start; border:none;"><i class="fas fa-home"></i> الرئيسية</a>
            <a href="#shop" class="btn btn-outline" style="justify-content:flex-start; border:none;"><i class="fas fa-store"></i> المتجر</a>
        </nav>
        <div style="flex:1;"></div>
        <a href="logout.php" class="btn btn-danger">تسجيل خروج</a>
    </aside>

    <main id="home">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h1 style="margin:0; font-size:1.5rem;">محفظتي</h1>
                <span style="color:var(--text-muted); font-size:0.9rem;">رقم الحساب: <?php echo $u['phone']; ?></span>
            </div>
            <div class="badge" style="background:<?php echo $u['loan_status']=='active'?'#fee2e2':'#dcfce7'; ?>; color:<?php echo $u['loan_status']=='active'?'#991b1b':'#166534'; ?>;">
                <?php echo $u['loan_status']=='active' ? 'عليك سلفة' : 'وضعك سليم'; ?>
            </div>
        </div>

        <div class="card wallet-card" style="margin-bottom:25px;">
            <div style="display:flex; justify-content:space-between;">
                <span style="opacity:0.8;">رصيد النقاط</span>
                <i class="fas fa-wallet" style="opacity:0.5; font-size:1.5rem;"></i>
            </div>
            <div style="font-size:3rem; font-weight:900; margin:10px 0;"><?php echo number_format($u['wallet_points']); ?></div>
            <div style="opacity:0.8; font-size:0.8rem;">كل 100 ريال شحن = 10 نقاط</div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:30px;">
            <div class="card" style="padding:15px; text-align:center; cursor:pointer;" onclick="showRecharge()">
                <div style="width:50px; height:50px; background:#e0e7ff; color:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                    <i class="fas fa-bolt" style="font-size:1.2rem;"></i>
                </div>
                <div style="font-weight:bold;">شحن رصيد</div>
            </div>
            <div class="card" style="padding:15px; text-align:center; cursor:pointer;" onclick="api('loan')">
                <div style="width:50px; height:50px; background:#fee2e2; color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                    <i class="fas fa-life-ring" style="font-size:1.2rem;"></i>
                </div>
                <div style="font-weight:bold;">سلفة طوارئ</div>
            </div>
        </div>

        <div id="shop" style="margin-bottom:30px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0;">🛍️ متجر المكافآت</h3>
                <span style="font-size:0.8rem; color:var(--text-muted);">استبدل نقاطك بكروت</span>
            </div>
            
            <div class="shop-grid">
                <?php foreach($rewards as $r): $cost = ceil($r/10); ?>
                    <div class="card" style="padding:15px; text-align:center; border:1px solid <?php echo ($u['wallet_points'] >= $cost) ? '#10b981' : '#e5e7eb'; ?>;">
                        <div style="font-size:1.2rem; font-weight:bold;"><?php echo $r; ?> ريال</div>
                        <div style="margin:5px 0 10px; color:var(--text-muted); font-size:0.8rem;">كرت شحن</div>
                        
                        <button onclick="buy(<?php echo $r; ?>, <?php echo $cost; ?>)" 
                            class="btn" 
                            style="width:100%; font-size:0.8rem; padding:8px; 
                            background:<?php echo ($u['wallet_points'] >= $cost) ? 'var(--primary)' : '#f3f4f6'; ?>;
                            color:<?php echo ($u['wallet_points'] >= $cost) ? 'white' : '#9ca3af'; ?>;">
                            💎 <?php echo $cost; ?> نقطة
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3 style="margin-bottom:15px;">آخر العمليات 📝</h3>
        <div class="card" style="padding:0; overflow:hidden;">
            <?php if(empty($history)): ?>
                <div style="padding:20px; text-align:center; color:var(--text-muted);">لا توجد عمليات حديثة</div>
            <?php else: ?>
                <?php foreach($history as $h): ?>
                <div style="display:flex; justify-content:space-between; padding:15px; border-bottom:1px solid #f3f4f6;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:35px; height:35px; background:#f9fafb; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-history" style="color:var(--text-muted);"></i>
                        </div>
                        <div>
                            <div style="font-size:0.9rem; font-weight:bold;">
                                <?php echo ($h['type']=='recharge')?'شحن رصيد':(($h['type']=='loan')?'سلفة':'مكافأة'); ?>
                            </div>
                            <div style="font-size:0.7rem; color:var(--text-muted);"><?php echo $h['created_at']; ?></div>
                        </div>
                    </div>
                    <div style="font-weight:bold; color:<?php echo ($h['type']=='recharge')?'#10b981':'#ef4444'; ?>">
                        <?php echo ($h['type']=='recharge')?'+':'-'; ?><?php echo $h['amount']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<nav class="mobile-nav">
    <a href="#home" class="nav-link active"><i class="fas fa-home"></i>الرئيسية</a>
    <a href="#shop" class="nav-link"><i class="fas fa-store"></i>المتجر</a>
    <a href="logout.php" class="nav-link" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i>خروج</a>
</nav>

<script>
async function api(action, data = {}) {
    let fd = new FormData();
    fd.append('action', action);
    if(data.code) fd.append('code', data.code);
    if(data.amount) fd.append('amount', data.amount);

    try {
        let res = await fetch('api.php', {method:'POST', body:fd}).then(r=>r.json());
        if(res.status === 'success') {
            let msg = res.msg;
            if(res.data && res.data.code) {
                msg = `<div style="font-size:1.5rem; font-weight:bold; color:#4f46e5; border:2px dashed #4f46e5; padding:10px; margin:10px 0; border-radius:10px;">${res.data.code}</div>تم حفظه في سجل العمليات`;
            }
            Swal.fire({icon: 'success', title: 'تمت العملية', html: msg}).then(()=>location.reload());
        } else {
            Swal.fire({icon: 'error', title: 'عذراً', text: res.msg});
        }
    } catch(e) { Swal.fire('خطأ', 'فشل الاتصال', 'error'); }
}

function showRecharge() {
    Swal.fire({
        title: 'شحن رصيد',
        input: 'text',
        inputPlaceholder: 'أدخل كود الكرت',
        confirmButtonText: 'شحن',
        showCancelButton: true,
        cancelButtonText: 'إلغاء'
    }).then((res) => {
        if (res.isConfirmed && res.value) api('recharge', {code: res.value});
    });
}

function buy(amount, points) {
    Swal.fire({
        title: 'شراء كرت؟',
        text: `بقيمـة ${amount} ريال مقابل ${points} نقطة`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'شراء',
        cancelButtonText: 'إلغاء'
    }).then((res) => {
        if(res.isConfirmed) api('buy_reward', {amount: amount});
    });
}
</script>

</body>
</html>