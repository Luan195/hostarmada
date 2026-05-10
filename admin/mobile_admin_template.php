<?php
/**
 * MOBILE ADMIN TEMPLATE COMPONENTS
 * Copy-paste these snippets into all admin pages
 */

// ===================================================
// 1. CSS STYLES (Add to <head>)
// ===================================================
?>
<style>
    /* Desktop Sidebar */
    .sidebar { width: 260px; min-height: 100vh; transition: transform 0.3s ease; }
    .main-content { margin-left: 260px; transition: margin 0.3s ease; }
    .nav-item { transition: all 0.2s ease; }
    .nav-item:hover { background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, transparent 100%); }
    .nav-item.active { 
        background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); 
        color: white !important;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }
    .nav-item.active i, .nav-item.active span { color: white !important; }
    
    /* Mobile Responsive */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar.mobile-open {
            transform: translateX(0);
        }
        .main-content { 
            margin-left: 0; 
            padding-bottom: 70px;
        }
        .mobile-header { display: flex; }
        .desktop-header { display: none; }
    }
    
    @media (min-width: 1025px) {
        .mobile-header { display: none; }
        .desktop-header { display: flex; }
        .mobile-bottom-nav { display: none; }
    }
    
    /* Mobile Bottom Navigation */
    .mobile-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 2px solid #e2e8f0;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 50;
        display: none;
    }
    
    .mobile-bottom-nav a {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 0.5rem;
        text-decoration: none;
        color: #64748b;
        font-size: 0.75rem;
        transition: all 0.2s;
        position: relative;
    }
    
    .mobile-bottom-nav a.active {
        color: #6366f1;
        background: linear-gradient(to top, rgba(99,102,241,0.1), transparent);
    }
    
    .mobile-bottom-nav a i {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    
    /* Mobile Overlay */
    .mobile-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 35;
    }
    
    .mobile-overlay.active {
        display: block;
    }
</style>
<?php

// ===================================================
// 2. MOBILE OVERLAY (Add after <body>)
// ===================================================
?>
<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileSidebar()"></div>
<?php

// ===================================================
// 3. SIDEBAR (Update existing sidebar)
// ===================================================
?>
<!-- Sidebar -->
<aside class="sidebar fixed left-0 top-0 bg-slate-900 text-white z-40" id="sidebar">
    <!-- ... sidebar content ... -->
</aside>
<?php

// ===================================================
// 4. MOBILE HEADER (Add at top of main content)
// ===================================================
?>
<!-- Mobile Header -->
<header class="mobile-header bg-white shadow-sm px-4 py-3 items-center justify-between sticky top-0 z-30">
    <button onclick="toggleMobileSidebar()" class="p-2 hover:bg-slate-100 rounded-lg">
        <i class="fa-solid fa-bars text-xl text-slate-700"></i>
    </button>
    <h1 class="text-lg font-black text-slate-800">PAGE_TITLE</h1>
    <a href="logout.php" class="p-2 hover:bg-red-50 rounded-lg text-red-600">
        <i class="fa-solid fa-sign-out-alt"></i>
    </a>
</header>

<!-- Desktop Header -->
<header class="desktop-header bg-white shadow-sm px-6 py-4 items-center justify-between sticky top-0 z-30">
    <!-- ... desktop header content ... -->
</header>
<?php

// ===================================================
// 5. BOTTOM NAVIGATION (Add before </body>)
// ===================================================
?>
<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav flex">
    <a href="index.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Dashboard</span>
    </a>
    <a href="orders.php" class="<?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
        <i class="fa-solid fa-shopping-cart"></i>
        <span>Đơn Hàng</span>
        <?php if ($pendingCount > 0): ?>
        <span style="position: absolute; top: 0.5rem; right: 1rem; background: #ef4444; color: white; font-size: 0.65rem; padding: 0.125rem 0.375rem; border-radius: 9999px; font-weight: bold;"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="users.php" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i>
        <span>Users</span>
    </a>
    <a href="affiliates.php" class="<?php echo $currentPage === 'affiliates' ? 'active' : ''; ?>">
        <i class="fa-solid fa-handshake"></i>
        <span>CTV</span>
    </a>
    <a href="reports.php" class="<?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-line"></i>
        <span>Báo Cáo</span>
    </a>
</nav>
<?php

// ===================================================
// 6. JAVASCRIPT (Add to <script> section)
// ===================================================
?>
<script>
    // Mobile Sidebar Toggle
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
        
        // Prevent body scroll when sidebar open
        if (sidebar.classList.contains('mobile-open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    
    // Auto-close sidebar when clicking any navigation link on mobile
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        const navLinks = sidebar.querySelectorAll('a[href]:not([target="_blank"])');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Only close on mobile (when sidebar is in mobile mode)
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    });
</script>
