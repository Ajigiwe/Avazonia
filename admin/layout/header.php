<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> — Avazonia</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        :root {
            --sidebar-w: 260px;
            --admin-bg: #F9FAFB;
        }
        body { background: var(--admin-bg); min-height: 100vh; font-weight: 400; overflow-x: hidden; }
        
        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-w); background: var(--ink); border-right: 1px solid rgba(255,255,255,0.05);
            display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 1000;
            transition: transform 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            overflow-y: auto; /* Enable scrolling */
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        
        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        
        @media (max-width: 900px) {
            .admin-sidebar { transform: translateX(-100%); width: 280px; }
            .admin-sidebar.active { transform: translateX(0); box-shadow: 20px 0 60px rgba(0,0,0,0.5); }
        }

        .sidebar-brand {
            padding: 32px 24px; font-family: var(--f-display); font-weight: 900; font-size: 20px; color: #fff;
            letter-spacing: -0.02em; display: flex; align-items: center; gap: 4px;
        }
        .sidebar-brand span { color: var(--red); font-weight: 400; font-size: 14px; margin-top: 4px; }
        
        .sidebar-nav { flex: 1; padding: 0 12px; display: flex; flex-direction: column; gap: 4px; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: rgba(255,255,255,0.6); font-family: var(--f-semi); font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.08em; transition: all 0.2s; border-radius: 4px;
        }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-item.active { background: var(--red); color: #fff; font-weight: 700; box-shadow: 0 8px 24px rgba(232,0,45,0.3); }
        .nav-icon { font-size: 16px; opacity: 0.8; }
        .nav-item.logout { margin-top: auto; color: var(--red); }
        .sidebar-footer { padding: 24px 12px; border-top: 1px solid rgba(255,255,255,0.05); }

        /* Main Content */
        .admin-main { margin-left: var(--sidebar-w); padding: 40px; box-sizing: border-box; }
        
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .admin-header h1 { font-family: var(--f-display); font-weight: 800; font-size: 32px; text-transform: uppercase; letter-spacing: -0.01em; }
        
        /* Dashboard Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 48px; }

        /* Universal Responsive Table Container */
        .table-container { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid var(--light-gray); background: #fff; margin-bottom: 24px; }
        .table-container .admin-table { border: none; min-width: 800px; }

        /* Tables & Panels */
        .panel { background: #fff; border: 2px solid var(--ink); margin-bottom: 40px; border-radius: 0; overflow: hidden; }
        .panel-header { padding: 24px; border-bottom: 1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
        .panel-title { font-family: var(--f-display); font-weight: 700; font-size: 18px; text-transform: uppercase; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th { padding: 16px 24px; background: var(--off); text-align: left; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; }
        .admin-table td { padding: 20px 24px; border-bottom: 1px solid var(--light-gray); font-size: 13px; color: var(--ink); }
        .admin-table tr:last-child td { border-bottom: none; }
        
        .stat-card {
            background: #fff; padding: 32px; border: 2px solid var(--ink); position: relative;
            transition: all 0.3s var(--ease); overflow: hidden;
        }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--light-gray); transition: background 0.3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.04); }
        .stat-card:hover::before { background: var(--red); }
        
        .stat-label { font-family: 'Outfit', sans-serif; font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.1em; font-weight: 700; }
        .stat-value { font-family: var(--f-display); font-weight: 900; font-size: 36px; color: var(--ink); line-height: 1; }
        .stat-trend { font-size: 10px; margin-top: 12px; display: flex; align-items: center; gap: 4px; }
        .trend-up { color: #00a854; }

        .status-badge {
            padding: 4px 10px; border-radius: 99px; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;
        }
        .status-paid { background: #e6f7ec; color: #00a854; }
        .status-pending { background: #fff7e6; color: #fa8c16; }
        .status-cancelled { background: #fff1f0; color: #f5222d; }

        /* --- NUCLEAR MOBILE REFRESH --- */
        @media (max-width: 900px) {
            body { display: block !important; overflow-x: hidden; width: 100vw; position: relative; }
            .admin-sidebar { transform: translateX(-102%); width: 280px; visibility: hidden; }
            .admin-sidebar.active { transform: translateX(0); box-shadow: 20px 0 60px rgba(0,0,0,0.5); visibility: visible; }
            
            .admin-main { 
                margin-left: 0 !important; 
                width: 100% !important; 
                max-width: 100% !important; 
                padding: 80px 16px 40px !important; 
                display: block !important;
                box-sizing: border-box !important;
                position: relative;
                z-index: 10;
            }
            
            .admin-header { flex-direction: column; align-items: flex-start !important; gap: 16px; margin-bottom: 32px; }
            .admin-header h1 { font-size: 26px !important; line-height: 1.1 !important; letter-spacing: -0.02em; }
            
            .stats-grid { grid-template-columns: 1fr !important; gap: 16px; }
            .stat-card { padding: 24px !important; }
            
            #admin-toggle { display: flex !important; }
            #admin-toggle.active { left: 290px; }
        }

        /* Sidebar Toggle Component */
        #admin-toggle {
            position: fixed; top: 16px; left: 16px; z-index: 2000;
            width: 44px; height: 44px; border-radius: 12px; background: var(--ink);
            color: #fff; border: none; display: none; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 10px 30px rgba(0,0,0,0.15); transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        }

        /* Sidebar Overlay */
        .admin-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 900;
            opacity: 0; visibility: hidden; transition: all 0.4s; backdrop-filter: blur(4px);
        }
        .admin-overlay.active { opacity: 1; visibility: visible; }
    </style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="admin-overlay"></div>
<button id="admin-toggle">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
</button>

<script>
    const toggle = document.getElementById('admin-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.admin-overlay');
    
    if (toggle && sidebar && overlay) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('active');
            toggle.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        };

        toggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) toggleSidebar();
        });
    }
</script>

<main class="admin-main">
