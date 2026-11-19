<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="../assets/images/aclc_logo.png" alt="ACLC" class="brand-logo">
    <div class="brand-text">
      <strong>ACLC COLLEGE</strong>
      <span>MANDAUE</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="admin_dashboard.php" class="nav-item <?= $current==='admin_dashboard.php' ?'active':'' ?>">
      <span class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin: round><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </span>
      <span class="nav-label">HOME</span>
    </a>
    <a href="manage_equipment.php" class="nav-item <?= $current==='manage_equipment.php' ?'active':'' ?>">
      <span class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
      </span>
      <span class="nav-label">Manage Catalog</span>
    </a>
    <a href="history.php" class="nav-item <?= $current==='history.php' ?'active':'' ?>">
      <span class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </span>
      <span class="nav-label">History Log</span>
    </a>
    <a href="active_checkouts.php" class="nav-item <?= $current==='active_checkouts.php' ?'active':'' ?>">
      <span class="nav-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="17" rx="2"/><path d="M9 4.5V3h6v1.5"/><path d="m8 13 2.5 2.5L16 10"/></svg>
      </span>
      <span class="nav-label">Active Checkouts</span>
    </a>
  </nav>
</aside>