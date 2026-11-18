<?php
require_once __DIR__ . '/auth.php';

function admin_header(string $title): void {
    $u = require_login();
    $S = settings();
    $logo = $S['logo'] ? upload_url($S['logo'], '../') : '../assets/img/logo.png';
    $cur = basename($_SERVER['SCRIPT_NAME']);
    try { $unread = (int)db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(); } catch (PDOException $e) { $unread = 0; }
    $nav = [
        ['index.php', 'Tableau de bord', 'speedometer2'],
        ['messages.php', 'Messages', 'envelope'],
        ['banners.php', 'Bannière', 'images'],
        ['news.php', 'Actualités', 'newspaper'],
        ['settings.php', 'Informations', 'building'],
    ];
    if ($u['role'] === 'admin') $nav[] = ['users.php', 'Utilisateurs', 'people'];
    $nav[] = ['profile.php', 'Mon profil', 'person-circle'];
    ?>
<!DOCTYPE html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> – Administration</title>
<link rel="icon" href="../assets/img/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="shell">
 <aside class="side">
  <a class="brand" href="index.php"><img src="<?= e($logo) ?>" alt=""><?= e($S['site_name']) ?></a>
  <nav aria-label="Administration">
   <?php foreach ($nav as [$href, $label, $icon]): ?>
    <a class="nl<?= $cur === $href ? ' on' : '' ?>" href="<?= $href ?>"><i class="bi bi-<?= $icon ?>"></i><?= $label ?><?php if ($href === 'messages.php' && $unread): ?><span class="badge text-bg-warning ms-auto"><?= $unread ?></span><?php endif; ?></a>
   <?php endforeach; ?>
   <a class="nl" href="../index.php" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>Voir le site</a>
  </nav>
  <div class="me"><?= e($u['name']) ?><br><a class="text-warning" href="logout.php">Se déconnecter</a></div>
 </aside>
 <main class="main">
  <h1><?= e($title) ?></h1>
  <?php foreach (flashes() as [$t, $m]): ?>
   <div class="alert alert-<?= e($t) ?>" role="alert"><?= e($m) ?></div>
  <?php endforeach; ?>
<?php }

function admin_footer(): void { ?>
 </main>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body></html>
<?php }

/** Page simple (connexion, première installation). */
function plain_header(string $title): void { ?>
<!DOCTYPE html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?></title>
<link rel="icon" href="../assets/img/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="auth">
 <div class="text-center mb-4"><img src="../assets/img/logo.png" alt="" width="84" height="84"><h1 class="h3 mt-2"><?= e($title) ?></h1></div>
 <?php foreach (flashes() as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>" role="alert"><?= e($m) ?></div><?php endforeach; ?>
<?php }
function plain_footer(): void { echo '</div></body></html>'; }

/** Case à cocher « Actif » et boutons d'action réutilisables. */
function confirm_attr(string $msg): string { return 'onsubmit="return confirm(\'' . e(addslashes($msg)) . '\')"'; }
