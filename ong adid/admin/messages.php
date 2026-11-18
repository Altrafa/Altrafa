<?php
require __DIR__ . '/../include/admin_ui.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'delete') { $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]); flash('success', 'Message supprimé.'); }
    elseif ($act === 'unread') { $pdo->prepare('UPDATE messages SET is_read = 0 WHERE id = ?')->execute([$id]); flash('success', 'Message marqué comme non lu.'); }
    elseif ($act === 'read_all') { $pdo->exec('UPDATE messages SET is_read = 1'); flash('success', 'Tous les messages sont marqués comme lus.'); }
    redirect('messages.php');
}

$view = null;
if (isset($_GET['view'])) {
    $st = $pdo->prepare('SELECT * FROM messages WHERE id = ?'); $st->execute([(int)$_GET['view']]);
    $view = $st->fetch() ?: null;
    if ($view && !$view['is_read']) $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$view['id']]);
}
$rows = $pdo->query('SELECT id, name, email, LEFT(message, 90) AS preview, is_read, created_at FROM messages ORDER BY created_at DESC, id DESC LIMIT 200')->fetchAll();

admin_header('Messages reçus');
?>
<?php if ($view): ?>
<div class="panel">
 <h2><?= e($view['name']) ?></h2>
 <p class="text-secondary mb-3"><a href="mailto:<?= e($view['email']) ?>"><?= e($view['email']) ?></a> · reçu le <?= e(date('d/m/Y à H:i', strtotime($view['created_at']))) ?></p>
 <div class="p-3 rounded" style="background:var(--bg);white-space:pre-wrap"><?= e($view['message']) ?></div>
 <div class="mt-3 d-flex gap-2 flex-wrap">
  <a class="btn btn-teal" href="mailto:<?= e($view['email']) ?>?subject=<?= rawurlencode('Re : votre message à ' . settings()['site_name']) ?>">Répondre par e-mail</a>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="unread"><input type="hidden" name="id" value="<?= (int)$view['id'] ?>"><button class="btn btn-outline-secondary">Marquer comme non lu</button></form>
  <form method="post" <?= confirm_attr('Supprimer ce message ?') ?>><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$view['id'] ?>"><button class="btn btn-outline-danger">Supprimer</button></form>
  <a class="btn btn-outline-secondary" href="messages.php">Retour à la liste</a>
 </div>
</div>
<?php endif; ?>

<div class="panel">
 <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0">Boîte de réception</h2>
  <?php if ($rows): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-sm btn-outline-secondary">Tout marquer comme lu</button></form><?php endif; ?>
 </div>
 <?php if (!$rows): ?><p class="text-secondary mb-0">Aucun message pour le moment. Ceux envoyés depuis le formulaire de contact du site apparaîtront ici.</p><?php else: ?>
 <div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>De</th><th>Message</th><th>Reçu le</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
   <tr class="<?= $r['is_read'] ? '' : 'fw-semibold' ?>">
    <td><?= $r['is_read'] ? '' : '<span class="badge text-bg-warning me-1">Nouveau</span>' ?><?= e($r['name']) ?><br><small class="text-secondary fw-normal"><?= e($r['email']) ?></small></td>
    <td class="text-secondary"><?= e($r['preview']) ?>…</td>
    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
    <td class="text-end text-nowrap">
     <a class="btn btn-sm btn-outline-secondary" href="messages.php?view=<?= (int)$r['id'] ?>">Lire</a>
     <form method="post" class="d-inline" <?= confirm_attr('Supprimer ce message ?') ?>><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
    </td>
   </tr>
  <?php endforeach; ?></tbody></table></div>
 <?php endif; ?>
</div>
<?php admin_footer();
