<?php
$section  = $section  ?? '';
$page     = $page     ?? '';
$sec_page = $sec_page ?? '';

$adminUserPrava = (string)admin_session_prava();

if (!in_array($adminUserPrava, ['1','2'], true)) {
    return;
}

$isActive = ($section === "03" || ($section === '' && $page === ''));
?>

<a class="nav-link <?= $isActive ? 'active' : '' ?>" href="index.php">
    <i class="bi bi-speedometer2 me-2"></i>
    <span>Dashboard</span>
</a>
