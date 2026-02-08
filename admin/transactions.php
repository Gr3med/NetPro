<?php
require '../config.php';
if (!isset($_SESSION['admin_id'])) header("Location: login.php");

$stmt = $pdo->query("
    SELECT t.*, u.full_name, u.phone 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.id DESC LIMIT 50
");
$trans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>السجلات | NetPro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Force App Style */
        body { background-color: #f0f2f5 !important; }
        
        @media (max-width: 991px) {
            .desktop-sidebar { display: none !important; }
            .app-container {
                display: block !important;
                padding: 15px !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            main { padding-bottom: 80px; width: 100% !important; }
            .desktop-view { display: none !important; }
        }
        @media (min-width: 992px) {
            .mobile-view { display: none !important; }
        }

        /* تصميم البطاقة */
        .trans-card {
            background: white; border-radius: 16px; padding: 15px; margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 1px solid #eef0f2;
            display: flex; align-items: center; justify-content: space-between;
        }
        
        .t-icon {
            width: 42px; height: 42px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; margin-left: 12px; flex-shrink: 0;
        }
        
        .t-user { font-weight: 800; color: #1f2937; font-size: 0.95rem; margin-bottom: 2px; }
        .t-date { font-size: 0.75rem; color: #9ca3af; direction: ltr; text-align: right; }
        .t-amount { font-weight: 900; font-size: 1rem; }
    </style>
</head>
<body>

<div class="app-container">
    <?php include 'sidebar.php'; ?>
    <main>
        <div style="margin-bottom:20px;">
            <h1 style="margin:0; font-size:1.6rem; color:#111827;">السجلات</h1>
            <span style="color:#6b7280; font-size:0.9rem;">أحدث الحركات المالية</span>
        </div>

        <div class="mobile-view">
            <?php foreach($trans as $t): 
                $bg = '#f3f4f6'; $co = '#6b7280'; $ic = 'fa-circle';
                if($t['type']=='recharge') { $bg='#dcfce7'; $co='#166534'; $ic='fa-arrow-down'; }
                if($t['type']=='loan') { $bg='#fee2e2'; $co='#991b1b'; $ic='fa-life-ring'; }
            ?>
            <div class="trans-card">
                <div style="display:flex; align-items:center;">
                    <div class="t-icon" style="background:<?php echo $bg; ?>; color:<?php echo $co; ?>;">
                        <i class="fas <?php echo $ic; ?>"></i>
                    </div>
                    <div>
                        <div class="t-user"><?php echo htmlspecialchars($t['full_name']); ?></div>
                        <div style="font-size:0.8rem; color:#6b7280;"><?php echo $t['description']; ?></div>
                    </div>
                </div>
                <div style="text-align:left;">
                    <div class="t-amount" style="color:<?php echo $co; ?>;"><?php echo $t['amount']; ?></div>
                    <div class="t-date"><?php echo date('m-d H:i', strtotime($t['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card desktop-view" style="padding:0; overflow:hidden; border:none; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
            <div class="table-container">
                <table style="width:100%;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="padding:15px;">العميل</th><th>العملية</th><th>المبلغ</th><th>التفاصيل</th><th>الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($trans as $t): ?>
                        <tr>
                            <td style="padding:15px;">
                                <b><?php echo htmlspecialchars($t['full_name']); ?></b>
                                <div style="font-size:0.8rem; color:#666;"><?php echo $t['phone']; ?></div>
                            </td>
                            <td><?php echo $t['type']; ?></td>
                            <td><b><?php echo $t['amount']; ?></b></td>
                            <td><?php echo $t['description']; ?></td>
                            <td style="direction:ltr; text-align:right; font-size:0.8rem;"><?php echo $t['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>