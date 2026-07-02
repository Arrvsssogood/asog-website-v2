<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= esc($pageTitle ?? 'Admin') ?> — ASOG TBI</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('icon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet"/>
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminLayout.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/adminCustomSelect.css') ?>">
</head>
<body>
<div class="shell">

    <aside class="side">
        <div class="side-brand">
            <?= responsiveStaticImg('assets/img/ASOG TBI/WebP/ASOG-TBI_seal', 'default', 'ASOG TBI', 'side-logo') ?>
            <div class="side-brand-text">
                <h2>ASOG TBI</h2>
                <span>Content Manager</span>
            </div>
        </div>
        <div class="side-sep"></div>

        <div class="side-label">Menu</div>
        <?php $sessionRole = session()->get('admin_role'); ?>
        <nav class="side-nav">
            <a href="<?= site_url('admin') ?>" class="<?= ($activePage ?? '') === 'dashboard' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                Dashboard
            </a>
            <a href="<?= site_url('admin/posts') ?>" class="<?= ($activePage ?? '') === 'posts' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h10"/></svg>
                Posts
            </a>
            <?php if (in_array($sessionRole, ['admin', 'superadmin'], true)): ?>
            <a href="<?= site_url('admin/applications') ?>" class="<?= ($activePage ?? '') === 'applications' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Applications
            </a>
            <a href="<?= site_url('admin/incubatees') ?>" class="<?= ($activePage ?? '') === 'incubatees' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Incubatees
            </a>
            <a href="<?= site_url('admin/faqs') ?>" class="<?= ($activePage ?? '') === 'faqs' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9a3.75 3.75 0 117.252 1.344c-.584 1.238-1.98 1.656-2.73 2.406-.45.45-.75.9-.75 1.5M12 18h.008"/><circle cx="12" cy="12" r="9"/></svg>
                FAQs
            </a>
            <a href="<?= site_url('admin/organization') ?>" class="<?= ($activePage ?? '') === 'organization' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l8 4-8 4-8-4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 12l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l8 4 8-4"/></svg>
                Organization
            </a>
            <?php $unreadMsgCount = (int) ($adminUnreadMessageCount ?? 0); ?>
            <a href="<?= site_url('admin/messages') ?>" class="<?= ($activePage ?? '') === 'messages' ? 'on' : '' ?>" style="position:relative">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Messages
                <?php if ($unreadMsgCount > 0): ?>
                    <span style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:#03558C;color:#fff;font-size:.5rem;font-weight:700;min-width:16px;height:16px;border-radius:99px;display:flex;align-items:center;justify-content:center;padding:0 4px"><?= $unreadMsgCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <?php if ($sessionRole === 'superadmin'): ?>
            <a href="<?= site_url('admin/accounts') ?>" class="<?= ($activePage ?? '') === 'admins' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Accounts
            </a>
            <a href="<?= site_url('admin/settings') ?>" class="<?= ($activePage ?? '') === 'settings' ? 'on' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            <?php endif; ?>
        </nav>

        <div class="side-sep" style="margin-top:.4rem"></div>
        <nav class="side-nav" style="padding-top:.3rem">
            <a href="<?= site_url('/') ?>" target="_blank">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                View website
            </a>
        </nav>

        <div class="side-foot">
            <div class="side-foot-user">
                <div class="user"><strong><?= esc(session()->get('admin_name') ?? 'Admin') ?></strong></div>
                <div class="user"><?= esc(session()->get('admin_email') ?? '') ?></div>
            </div>
            <nav class="side-nav">
                <a href="<?= site_url('asog-admin/logout') ?>" class="out">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </a>
            </nav>
        </div>
    </aside>

    <div class="body">
        <header class="bar">
            <?php if (($activePage ?? '') === 'dashboard'): ?>
                <div>
                    <h1>Welcome back, <?= esc(session()->get('admin_name') ?? 'Admin') ?></h1>
                </div>
                <div style="font-size:.62rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;white-space:nowrap;">
                    <?= date('l, M j, Y') ?>
                </div>
            <?php else: ?>
                <h1><?= esc($pageTitle ?? 'Dashboard') ?></h1>
            <?php endif; ?>
        </header>

        <div class="page">
            <?php helper('toast'); ?>
            <?= renderToast() ?>
