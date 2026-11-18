<?php
require __DIR__ . '/../include/admin_ui.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);
    try {
        if ($act === 'save') {
            $title = trim($_POST['title'] ?? '');
            $sub   = trim($_POST['subtitle'] ?? '');
            $layout = ($_POST['layout'] ?? '') === 'split' ? 'split' : 'full';
            $focus = max(0, min(100, (int)($_POST['focus_y'] ?? 30)));
            $sort  = (int)($_POST['sort_order'] ?? 0);
            $active = isset($_POST['active']) ? 1 : 0;
            if ($title === '') throw new RuntimeException('Le titre est obligatoire.');
            $old = null;
            if ($id) {
                $st = $pdo->prepare('SELECT image FROM banners WHERE id = ?'); $st->execute([$id]);
                $old = $st->fetchColumn();
                if ($old === false) throw new RuntimeException('Bannière introuvable.');
            }
            $img = save_upload('image', IMG_TYPES, 8);
            if (!$id && !$img) throw new RuntimeException('Choisissez une image pour la bannière.');
            if ($id) {
                $pdo->prepare('UPDATE banners SET title=?, subtitle=?, layout=?, focus_y=?, sort_order=?, active=?, image=COALESCE(?, image) WHERE id=?')
                    ->execute([$title, $sub, $layout, $focus, $sort, $active, $img, $id]);
                if ($img) delete_upload($old);
            } else {
                $pdo->prepare('INSERT INTO banners (title, subtitle, image, layout, focus_y, sort_order, active) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$title, $sub, $img, $layout, $focus, $sort, $active]);
            }
            flash('success', 'Bannière enregistrée.');
        } elseif ($act === 'delete') {
            $st = $pdo->prepare('SELECT image FROM banners WHERE id = ?'); $st->execute([$id]);
            $old = $st->fetchColumn();
            $pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
            delete_upload($old ?: null);
            flash('success', 'Bannière supprimée.');
        } elseif ($act === 'toggle') {
            $pdo->prepare('UPDATE banners SET active = 1 - active WHERE id = ?')->execute([$id]);
            flash('success', 'Visibilité mise à jour.');
        }
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        flash('danger', 'Erreur de base de données. Réessayez.');
    }
    redirect('banners.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM banners WHERE id = ?'); $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM banners ORDER BY sort_order, id')->fetchAll();
$v = fn(string $k, $d = '') => $edit[$k] ?? $d;

admin_header('Bannière d’accueil');
?>
<div class="panel">
 <h2><?= $edit ? 'Modifier la bannière' : 'Ajouter une bannière' ?></h2>
 <form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$v('id', 0) ?>">
  <div class="row g-3">
   <div class="col-md-6"><label class="form-label" for="title">Titre</label><input id="title" name="title" class="form-control" maxlength="160" required value="<?= e($v('title')) ?>"></div>
   <div class="col-md-6"><label class="form-label" for="subtitle">Texte d’accompagnement</label><input id="subtitle" name="subtitle" class="form-control" maxlength="300" value="<?= e($v('subtitle')) ?>"></div>
   <div class="col-md-6">
    <label class="form-label" for="image">Image <?= $edit ? '(laisser vide pour conserver l’actuelle)' : '' ?></label>
    <input id="image" name="image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" <?= $edit ? '' : 'required' ?>>
    <div class="form-text">JPG, PNG ou WebP, 8 Mo maximum. Format conseillé : 1600 × 700 px.</div>
    <?php if ($edit): ?><img class="mt-2 rounded" src="<?= e(upload_url($edit['image'], '../')) ?>" alt="Image actuelle" style="max-height:110px"><?php endif; ?>
   </div>
   <div class="col-md-3"><label class="form-label" for="layout">Disposition</label>
    <select id="layout" name="layout" class="form-select">
     <option value="full"<?= $v('layout', 'full') === 'full' ? ' selected' : '' ?>>Photo plein cadre</option>
     <option value="split"<?= $v('layout') === 'split' ? ' selected' : '' ?>>Texte à gauche, photo à droite</option>
    </select></div>
   <div class="col-md-3"><label class="form-label" for="focus_y">Cadrage vertical (%)</label><input id="focus_y" name="focus_y" type="number" min="0" max="100" class="form-control" value="<?= (int)$v('focus_y', 30) ?>"><div class="form-text">0 = haut de l’image, 100 = bas.</div></div>
   <div class="col-md-3"><label class="form-label" for="sort_order">Ordre d’affichage</label><input id="sort_order" name="sort_order" type="number" class="form-control" value="<?= (int)$v('sort_order', 0) ?>"></div>
   <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $v('active', 1) ? 'checked' : '' ?>><label class="form-check-label" for="active">Visible sur le site</label></div></div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-teal" type="submit">Enregistrer la bannière</button><?php if ($edit): ?><a class="btn btn-outline-secondary" href="banners.php">Annuler</a><?php endif; ?></div>
 </form>
</div>

<div class="panel">
 <h2>Bannières existantes</h2>
 <?php if (!$rows): ?><p class="text-secondary mb-0">Aucune bannière. Ajoutez-en une avec le formulaire ci-dessus ; en attendant, le site affiche un bandeau uni.</p><?php else: ?>
 <div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Image</th><th>Titre</th><th>Ordre</th><th>État</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
   <tr>
    <td><img class="thumb-s" src="<?= e(upload_url($r['image'], '../')) ?>" alt=""></td>
    <td><strong><?= e($r['title']) ?></strong><br><small class="text-secondary"><?= e($r['subtitle']) ?></small></td>
    <td><?= (int)$r['sort_order'] ?></td>
    <td><span class="badge text-bg-<?= $r['active'] ? 'success' : 'secondary' ?>"><?= $r['active'] ? 'Visible' : 'Masquée' ?></span></td>
    <td class="text-end text-nowrap">
     <a class="btn btn-sm btn-outline-secondary" href="banners.php?edit=<?= (int)$r['id'] ?>">Modifier</a>
     <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $r['active'] ? 'Masquer' : 'Afficher' ?></button></form>
     <form method="post" class="d-inline" <?= confirm_attr('Supprimer cette bannière et son image ?') ?>><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
    </td>
   </tr>
  <?php endforeach; ?></tbody></table></div>
 <?php endif; ?>
</div>
<?php admin_footer();
