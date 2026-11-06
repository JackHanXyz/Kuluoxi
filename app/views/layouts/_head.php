<?php
/** 控制台布局公共 head（供 topbar / rail / console 三套布局复用） */
$skin = $skin ?? current_skin();
$pageTitle = $title ?? '库洛汐控制台';
?>
<!doctype html>
<html lang="zh-CN" data-theme="light" data-skin="<?= e($skin) ?>" data-layout="<?= e(current_layout()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231668ff'/%3E%3Cpath d='M5 20c4-7 9-7 13 0s7 3 9-2' stroke='%23ffffff' stroke-width='2.5' fill='none' stroke-linecap='round'/%3E%3Ccircle cx='24' cy='10' r='2.5' fill='%23ffd08a'/%3E%3C/svg%3E">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/app.css')) ?>">
    <script>try{var t=localStorage.getItem('kuluoxi.theme');document.documentElement.dataset.theme=t==='dark'?'dark':'light';var s=localStorage.getItem('kuluoxi.skin');if(s)document.documentElement.dataset.skin=s;}catch(e){}</script>
</head>
