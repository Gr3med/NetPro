<?php
require '../config.php';
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

// --- المنطق البرمجي (PHP Logic) ---
$msg = ""; $error = "";

// الحفظ (إضافة / تعديل)
if (isset($_POST['save_card'])) {
    $type = clean($_POST['type']);
    $code = clean($_POST['code']);
    $amount = (int)$_POST['amount'];
    
    if (empty($_POST['card_id'])) {
        $check = $pdo->prepare("SELECT id FROM inventory WHERE code = ?");
        $check->execute([$code]);
        if ($check->rowCount() > 0) { $error = "الكود موجود مسبقاً!"; } 
        else {
            $stmt = $pdo->prepare("INSERT INTO inventory (code, amount, type, status) VALUES (?, ?, ?, 'available')");
            $stmt->execute([$code, $amount, $type]);
            $msg = "تمت الإضافة بنجاح";
        }
    } else {
        $id = (int)$_POST['card_id'];
        $stmt = $pdo->prepare("UPDATE inventory SET code=?, amount=?, type=? WHERE id=?");
        $stmt->execute([$code, $amount, $type, $id]);
        $msg = "تم التعديل بنجاح";
    }
}

// الحذف
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM inventory WHERE id=?")->execute([(int)$_GET['delete']]);
    header("Location: inventory.php?msg=deleted"); exit;
}
if (isset($_GET['msg']) && $_GET['msg']=='deleted') $msg = "تم الحذف";

// جلب البيانات
$where = "1=1"; $params = [];
$filter_type = $_GET['type'] ?? '';
if ($filter_type) { $where .= " AND i.type = ?"; $params[] = $filter_type; }
if (!empty($_GET['q'])) { $where .= " AND i.code LIKE ?"; $params[] = "%" . $_GET['q'] . "%"; }

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50; $offset = ($page - 1) * $limit;

$sql = "SELECT i.*, u.full_name FROM inventory i LEFT JOIN users u ON i.used_by = u.id WHERE $where ORDER BY i.id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cards = $stmt->fetchAll();

$total_pages = ceil($pdo->prepare("SELECT COUNT(*) FROM inventory i WHERE $where")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM inventory i WHERE $where")->fetchColumn() / $limit : 1);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>المخزون | NetPro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* =========================================================
           GLOBAL PROFESSIONAL MOBILE STYLE
           ========================================================= */
        body { background-color: #f8fafc !important; font-family: 'Tajawal', sans-serif; }

        /* 1. Reset Layout for Mobile */
        @media (max-width: 991px) {
            .desktop-sidebar { display: none !important; }
            .desktop-table-view { display: none !important; }
            
            .app-container {
                display: block !important;
                padding: 15px !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            main { padding-bottom: 90px; width: 100% !important; }
        }

        @media (min-width: 992px) {
            .mobile-card-list { display: none !important; }
            .fab-btn { display: none !important; }
        }

        /* 2. Professional Card Design */
        /* الحاوية التي تجبر الكروت تحت بعضها */
        .mobile-card-list {
            display: flex;
            flex-direction: column; /* هذا هو الحل للمشكلة السابقة */
            gap: 12px;
        }

        .pro-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            width: 100%; /* عرض كامل */
            box-sizing: border-box;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); /* ظل ناعم جداً */
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            transition: transform 0.2s;
        }
        .pro-card:active { transform: scale(0.98); }

        /* الأيقونة اليسرى */
        .card-icon-wrapper {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-left: 15px; flex-shrink: 0;
        }
        /* ألوان الفئات */
        .cat-sales { background: #dcfce7; color: #166534; }
        .cat-loan { background: #fee2e2; color: #991b1b; }
        .cat-reward { background: #f3e8ff; color: #6b21a8; }

        /* تفاصيل النص */
        .card-code { font-size: 1.15rem; font-weight: 800; color: #1e293b; letter-spacing: 0.5px; margin-bottom: 4px; font-family: 'Segoe UI', monospace; }
        .card-status { font-size: 0.8rem; display: flex; align-items: center; gap: 5px; font-weight: 600; }
        .status-ok { color: #10b981; }
        .status-sold { color: #ef4444; }

        /* السعر والحذف */
        .card-price-tag { font-weight: 900; font-size: 1.2rem; color: #0f172a; text-align: left; }
        .card-currency { font-size: 0.7rem; color: #94a3b8; font-weight: normal; }

        /* 3. Navigation Tabs (Pills) */
        .tabs-container {
            display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; margin-bottom: 20px;
            -webkit-overflow-scrolling: touch; scrollbar-width: none;
        }
        .tab-pill {
            padding: 10px 24px; border-radius: 50px; background: white; border: 1px solid #e2e8f0;
            color: #64748b; font-weight: 700; font-size: 0.9rem; white-space: nowrap; text-decoration: none;
            transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .tab-pill.active {
            background: var(--primary); color: white; border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); transform: translateY(-1px);
        }

        /* 4. Search Bar */
        .modern-search { position: relative; margin-bottom: 20px; }
        .search-input {
            width: 100%; padding: 16px 50px 16px 20px; border-radius: 18px; border: none;
            background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.03); font-size: 1rem; outline: none;
            color: #334155;
        }
        .search-icon { position: absolute; top: 18px; right: 20px; color: #94a3b8; font-size: 1.1rem; }

        /* 5. Floating Action Button (FAB) */
        .fab-btn {
            position: fixed; bottom: 95px; left: 20px;
            width: 60px; height: 60px; border-radius: 50%;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            border: none; cursor: pointer; z-index: 1000; transition: transform 0.2s;
        }
        .fab-btn:active { transform: scale(0.9); }

        /* 6. Modal Sheet */
        .sheet-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 2000;
            backdrop-filter: blur(4px); align-items: flex-end;
        }
        .sheet-content {
            background: white; width: 100%; padding: 30px 25px; border-radius: 28px 28px 0 0;
            animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @media(min-width: 992px) {
            .sheet-overlay { align-items: center; justify-content: center; }
            .sheet-content { width: 420px; border-radius: 24px; }
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    </style>
</head>
<body>

<div class="app-container">
    <?php include 'sidebar.php'; ?>

    <main>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <div>
                <h1 style="margin:0; font-size:1.8rem; color:#0f172a; font-weight:800;">المخزون</h1>
                <p style="margin:0; color:#64748b; font-size:0.9rem;">إدارة كروت الشبكة</p>
            </div>
            <button onclick="openSheet()" class="btn btn-primary desktop-table-view">
                <i class="fas fa-plus"></i> إضافة
            </button>
        </div>

        <div class="tabs-container">
            <a href="inventory.php" class="tab-pill <?php echo $filter_type==''?'active':''; ?>">الكل</a>
            <a href="inventory.php?type=sales" class="tab-pill <?php echo $filter_type=='sales'?'active':''; ?>">🛒 مبيعات</a>
            <a href="inventory.php?type=loan" class="tab-pill <?php echo $filter_type=='loan'?'active':''; ?>">🚑 سلف</a>
            <a href="inventory.php?type=reward" class="tab-pill <?php echo $filter_type=='reward'?'active':''; ?>">🎁 جوائز</a>
        </div>

        <form class="modern-search">
            <i class="fas fa-search search-icon"></i>
            <input type="number" name="q" value="<?php echo htmlspecialchars($_GET['q']??''); ?>" 
                   class="search-input" placeholder="بحث برقم الكرت...">
            <?php if($filter_type): ?><input type="hidden" name="type" value="<?php echo $filter_type; ?>"><?php endif; ?>
        </form>

        <?php if($msg): ?>
            <div style="background:#ecfdf5; color:#065f46; padding:15px; border-radius:16px; margin-bottom:20px; font-weight:bold; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="mobile-card-list">
            <?php if(empty($cards)): ?>
                <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                    <i class="fas fa-box-open" style="font-size:3.5rem; margin-bottom:15px; opacity:0.3;"></i>
                    <p style="font-size:1.1rem;">لا توجد كروت هنا</p>
                </div>
            <?php else: ?>
                <?php foreach($cards as $c): 
                    $cls = ($c['type']=='sales') ? 'cat-sales' : (($c['type']=='loan') ? 'cat-loan' : 'cat-reward');
                    $icn = ($c['type']=='sales') ? 'fa-shopping-cart' : (($c['type']=='loan') ? 'fa-life-ring' : 'fa-gift');
                ?>
                <div class="pro-card" onclick='openSheet(<?php echo json_encode($c); ?>)'>
                    <div style="display:flex; align-items:center;">
                        <div class="card-icon-wrapper <?php echo $cls; ?>">
                            <i class="fas <?php echo $icn; ?>"></i>
                        </div>
                        <div style="margin-right:15px;">
                            <div class="card-code"><?php echo $c['code']; ?></div>
                            <div class="card-status">
                                <?php if($c['status']=='available'): ?>
                                    <span class="status-ok"><i class="fas fa-check-circle" style="font-size:0.7rem;"></i> متاح</span>
                                <?php else: ?>
                                    <span class="status-sold"><i class="fas fa-user" style="font-size:0.7rem;"></i> <?php echo explode(' ', $c['full_name'])[0]; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align:left;">
                        <div class="card-price-tag"><?php echo $c['amount']; ?> <span class="card-currency">ريال</span></div>
                        <a href="?delete=<?php echo $c['id']; ?>" onclick="event.stopPropagation(); return confirm('حذف نهائي؟')" 
                           style="color:#cbd5e1; padding:8px; display:inline-block; font-size:1.1rem; margin-top:5px;">
                            <i class="fas fa-trash-alt" style="color:#ef4444;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="desktop-table-view card" style="padding:0; overflow:hidden; border:none; border-radius:16px;">
            <div class="table-container">
                <table style="width:100%;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:18px;">الكود</th><th>النوع</th><th>القيمة</th><th>الحالة</th><th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cards as $c): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:bold; font-size:1.1rem; padding:15px;"><?php echo $c['code']; ?></td>
                            <td><?php echo $c['type']; ?></td>
                            <td><b><?php echo $c['amount']; ?></b></td>
                            <td><?php echo $c['status']; ?></td>
                            <td>
                                <button onclick='openSheet(<?php echo json_encode($c); ?>)' class="btn btn-outline" style="padding:6px 15px;">تعديل</button>
                                <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('حذف؟')" class="btn btn-danger" style="padding:6px 15px;">حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if($total_pages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:30px;">
            <?php for($i=1; $i<=$total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&type=<?php echo $filter_type; ?>" 
                   style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:12px; font-weight:bold; text-decoration:none;
                   <?php echo $i==$page ? 'background:var(--primary); color:white; box-shadow:0 4px 10px rgba(79,70,229,0.3);' : 'background:white; border:1px solid #e2e8f0; color:#64748b;'; ?>">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </main>
    
    <button class="fab-btn" onclick="openSheet()"><i class="fas fa-plus"></i></button>

</div>

<div class="sheet-overlay" id="sheetModal">
    <div class="sheet-content">
        <div style="display:flex; justify-content:space-between; margin-bottom:25px;">
            <h3 style="margin:0; font-size:1.4rem; color:#0f172a;" id="sheetTitle">إضافة كرت</h3>
            <span onclick="closeSheet()" style="font-size:1.5rem; cursor:pointer; color:#94a3b8; width:30px; height:30px; display:flex; align-items:center; justify-content:center; background:#f1f5f9; border-radius:50%;">&times;</span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="card_id" id="cardId">
            
            <label style="display:block; margin-bottom:12px; font-weight:700; color:#334155;">نوع الكرت</label>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:25px;">
                <label style="cursor:pointer; text-align:center;">
                    <input type="radio" name="type" value="sales" checked style="display:none;" onchange="styleTypes(this)">
                    <div class="t-box" style="padding:15px; border:2px solid var(--primary); background:#eef2ff; border-radius:16px; transition:0.2s;">
                        <i class="fas fa-shopping-cart" style="font-size:1.2rem; margin-bottom:5px;"></i><br><small style="font-weight:bold;">مبيعات</small>
                    </div>
                </label>
                <label style="cursor:pointer; text-align:center;">
                    <input type="radio" name="type" value="loan" style="display:none;" onchange="styleTypes(this)">
                    <div class="t-box" style="padding:15px; border:2px solid #e2e8f0; border-radius:16px; transition:0.2s;">
                        <i class="fas fa-life-ring" style="font-size:1.2rem; margin-bottom:5px;"></i><br><small style="font-weight:bold;">سلف</small>
                    </div>
                </label>
                <label style="cursor:pointer; text-align:center;">
                    <input type="radio" name="type" value="reward" style="display:none;" onchange="styleTypes(this)">
                    <div class="t-box" style="padding:15px; border:2px solid #e2e8f0; border-radius:16px; transition:0.2s;">
                        <i class="fas fa-gift" style="font-size:1.2rem; margin-bottom:5px;"></i><br><small style="font-weight:bold;">جوائز</small>
                    </div>
                </label>
            </div>

            <label style="display:block; margin-bottom:8px; font-weight:700; color:#334155;">كود الكرت</label>
            <input type="number" name="code" id="cardCode" style="width:100%; padding:16px; border-radius:16px; border:2px solid #e2e8f0; margin-bottom:15px; background:white; outline:none; font-size:1rem;" required placeholder="أدخل الرقم هنا">

            <label style="display:block; margin-bottom:8px; font-weight:700; color:#334155;">القيمة (ريال)</label>
            <input type="number" name="amount" id="cardAmount" style="width:100%; padding:16px; border-radius:16px; border:2px solid #e2e8f0; margin-bottom:30px; background:white; outline:none; font-size:1rem;" required placeholder="مثلاً: 200">

            <button name="save_card" class="btn btn-primary" style="width:100%; padding:16px; border-radius:16px; font-size:1.1rem; font-weight:800; box-shadow:0 4px 15px rgba(79,70,229,0.3);">حفظ البيانات</button>
        </form>
    </div>
</div>

<script>
const sheet = document.getElementById('sheetModal');
const title = document.getElementById('sheetTitle');

function openSheet(data = null) {
    if(data) {
        title.innerText = 'تعديل الكرت';
        document.getElementById('cardId').value = data.id;
        document.getElementById('cardCode').value = data.code;
        document.getElementById('cardAmount').value = data.amount;
        document.querySelector(`input[name="type"][value="${data.type}"]`).click();
    } else {
        title.innerText = 'إضافة جديد';
        document.getElementById('cardId').value = '';
        document.getElementById('cardCode').value = '';
        document.getElementById('cardAmount').value = '';
        document.querySelector(`input[name="type"][value="sales"]`).click();
    }
    sheet.style.display = 'flex';
}

function closeSheet() { sheet.style.display = 'none'; }
sheet.addEventListener('click', (e) => { if(e.target === sheet) closeSheet(); });

function styleTypes(input) {
    document.querySelectorAll('.t-box').forEach(b => { b.style.border='2px solid #e2e8f0'; b.style.background='white'; });
    input.nextElementSibling.style.border = '2px solid var(--primary)';
    input.nextElementSibling.style.background = '#eef2ff';
}
</script>

</body>
</html>