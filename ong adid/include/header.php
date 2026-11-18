<?php
require_once __DIR__ . '/db.php';
$S = settings();
$logo = $S['logo'] ? upload_url($S['logo']) : './assets/img/logo.png';
?>
<!DOCTYPE html>
<html lang="fr"<?= in_array($S['theme_mode'], ['light', 'dark'], true) ? ' data-theme="' . $S['theme_mode'] . '"' : '' ?>>
    <head>
        <meta charset="utf-8">
        <title><?= e($S['site_title']) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="description" content="<?= e($S['site_description']) ?>">
        <link rel="icon" type="image/png" href="./assets/img/favicon.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="./assets/css/style.css">
        <style><?= theme_css($S) ?></style>
        <script>try{var t=localStorage.getItem('adid_theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
    </head>
    <body>

    <header class="top">
    <nav class="navbar navbar-expand-md container py-2" aria-label="Navigation principale">
    <a class="brand" href="#accueil"><img class="logo" src="<?= e($logo) ?>" alt="Logo <?= e($S['site_name']) ?>" width="56" height="56"><?= e($S['site_name']) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Ouvrir le menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse justify-content-end" id="menu">
    <ul class="navbar-nav align-items-md-center gap-md-2">
        <li class="nav-item"><a class="nav-link" href="#accueil">Accueil</a></li>
        <li class="nav-item"><a class="nav-link" href="#actualites">Actualités</a></li>
        <li class="nav-item"><a class="nav-link" href="#apropos">À propos</a></li>
        <li class="nav-item"><a class="nav-link" href="#mission">Mission</a></li>
        <li class="nav-item"><button id="themeBtn" type="button" class="btn nav-link" title="Basculer entre thème clair et sombre" aria-label="Basculer entre thème clair et sombre"><i class="bi bi-circle-half"></i></button></li>
        <li class="nav-item ms-md-2"><a class="btn btn-don" href="#contact">Laisser un Message</a></li>
    </ul>
    </div>
    </nav>
    </header>
