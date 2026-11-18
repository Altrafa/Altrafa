<?php
require __DIR__ . '/../include/admin_ui.php';
$u = require_login();
$c = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
admin_header('Tableau de bord');
try { $unread = $c('SELECT COUNT(*) FROM messages WHERE is_read=0'); } catch (PDOException $e) { $unread = 0; }
$cards = [
    ['Messages non lus', $unread, 'messages.php', 'Lire les messages'],
    ['Bannières actives', $c('SELECT COUNT(*) FROM banners WHERE active=1'), 'banners.php', 'Gérer la bannière'],
    ['Actualités publiées', $c('SELECT COUNT(*) FROM news WHERE active=1'), 'news.php', 'Gérer les actualités'],
    ['Rapports PDF', $c("SELECT COUNT(*) FROM news WHERE type='PDF' AND active=1"), 'news.php', 'Voir les rapports'],
];
if ($u['role'] === 'admin') $cards[] = ['Utilisateurs actifs', $c('SELECT COUNT(*) FROM users WHERE active=1'), 'users.php', 'Gérer les utilisateurs'];
?>
<p class="text-secondary">Bonjour <?= e($u['name']) ?>. Ce que vous modifiez ici apparaît aussitôt sur le site.</p>
<div class="row g-3">
<?php foreach ($cards as [$label, $n, $href, $cta]): ?>
 <div class="col-sm-6 col-xl-3"><div class="panel h-100 mb-0">
  <div class="stat"><?= $n ?></div><div class="text-secondary mb-3"><?= e($label) ?></div>
  <a href="<?= $href ?>" class="btn btn-sm btn-teal"><?= e($cta) ?></a>
 </div></div>
<?php endforeach; ?>
</div>
<?php admin_footer();
