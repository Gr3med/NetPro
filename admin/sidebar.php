<?php
// تحديد الصفحة الحالية لتلوين الأيقونة
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="desktop-sidebar">
    <h2 style="color:var(--primary); margin-bottom:40px; font-weight:900; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-shield-alt"></i> لوحة الإدارة
    </h2>
    <nav style="display:flex; flex-direction:column; gap:10px;">
        <a href="index.php" class="btn <?php echo $current_page == 'index.php' ? 'btn-primary' : 'btn-outline'; ?>" style="justify-content:flex-start; border:none;">
            <i class="fas fa-chart-line" style="width:25px;"></i> الرئيسية
        </a>
        <a href="users.php" class="btn <?php echo $current_page == 'users.php' ? 'btn-primary' : 'btn-outline'; ?>" style="justify-content:flex-start; border:none;">
            <i class="fas fa-users" style="width:25px;"></i> العملاء
        </a>
        <a href="inventory.php" class="btn <?php echo $current_page == 'inventory.php' ? 'btn-primary' : 'btn-outline'; ?>" style="justify-content:flex-start; border:none;">
            <i class="fas fa-box" style="width:25px;"></i> المخزون
        </a>
        <a href="transactions.php" class="btn <?php echo $current_page == 'transactions.php' ? 'btn-primary' : 'btn-outline'; ?>" style="justify-content:flex-start; border:none;">
            <i class="fas fa-history" style="width:25px;"></i> السجلات
        </a>
        <a href="backup.php" class="btn <?php echo $current_page == 'backup.php' ? 'btn-primary' : 'btn-outline'; ?>" style="justify-content:flex-start; border:none;">
            <i class="fas fa-database" style="width:25px;"></i> نسخ احتياطي
        </a>
        
        <div style="flex:1;"></div>
        
        <a href="logout.php" class="btn btn-danger" style="margin-top:auto;">
            <i class="fas fa-sign-out-alt"></i> خروج
        </a>
    </nav>
</aside>

<nav class="admin-mobile-nav">
    <a href="index.php" class="mobile-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>الرئيسية</span>
    </a>
    <a href="users.php" class="mobile-nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        <span>العملاء</span>
    </a>
    
    <a href="inventory.php" class="mobile-nav-item <?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
        <div style="background:var(--primary); color:white; width:45px; height:45px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-top:-25px; box-shadow:0 4px 10px rgba(79, 70, 229, 0.4);">
            <i class="fas fa-box-open" style="margin:0; font-size:1.2rem;"></i>
        </div>
        <span style="margin-top:5px;">المخزون</span>
    </a>

    <a href="transactions.php" class="mobile-nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
        <i class="fas fa-exchange-alt"></i>
        <span>السجلات</span>
    </a>
    <a href="logout.php" class="mobile-nav-item" style="color:#ef4444;">
        <i class="fas fa-sign-out-alt"></i>
        <span>خروج</span>
    </a>
</nav>