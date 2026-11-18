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
            $type = ($_POST['type'] ?? '') === 'PDF' ? 'PDF' : 'JPG';
            $title = trim($_POST['title'] ?? '');
            $summary = trim($_POST['summary'] ?? '');
            $date = $_POST['published_at'] ?? '';
            $active = isset($_POST['active']) ? 1 : 0;
            if ($title === '') throw new RuntimeException('Le titre est obligatoire.');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) throw new RuntimeException('Date de publication invalide.');

            $old = ['image' => null, 'doc' => null];
            if ($id) {
                $st = $pdo->prepare('SELECT image, doc FROM news WHERE id = ?'); $st->execute([$id]);
                $old = $st->fetch();
                if (!$old) throw new RuntimeException('Actualité introuvable.');
            }
            $newImg = save_upload('image', IMG_TYPES, 8);
            $newDoc = save_upload('doc', PDF_TYPES, 20);
            $image = $newImg ?? $old['image'];
            $doc   = $newDoc ?? $old['doc'];
            if ($type === 'JPG' && !$image) { delete_upload($newDoc); throw new RuntimeException('Une actualité de type JPG nécessite une photo.'); }
            if ($type === 'PDF' && !$doc)   { delete_upload($newImg); throw new RuntimeException('Un rapport de type PDF nécessite un fichier PDF.'); }

            if ($id) {
                $pdo->prepare('UPDATE news SET type=?, title=?, summary=?, published_at=?, image=?, doc=?, active=? WHERE id=?')
                    ->execute([$type, $title, $summary, $date, $image, $doc, $active, $id]);
                if ($newImg) delete_upload($old['image']);
                if ($newDoc) delete_upload($old['doc']);
            } else {
                $pdo->prepare('INSERT INTO news (type, title, summary, published_at, image, doc, active) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$type, $title, $summary, $date, $image, $doc, $active]);
            }
            flash('success', 'Actualité enregistrée.');
        } elseif ($act === 'delete') {
            $st = $pdo->prepare('SELECT image, doc FROM news WHERE id = ?'); $st->execute([$id]);
            $old = $st->fetch();
            $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);
            if ($old) { delete_upload($old['image']); delete_upload($old['doc']); }
            flash('success', 'Actualité supprimée.');
        } elseif ($act === 'toggle') {
            $pdo->prepare('UPDATE news SET active = 1 - active WHERE id = ?')->execute([$id]);
            flash('success', 'Visibilité mise à jour.');
        }
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        flash('danger', 'Erreur de base de données. Réessayez.');
    }
    redirect('news.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM news WHERE id = ?'); $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM news ORDER BY published_at DESC, id DESC')->fetchAll();
$v = fn(string $k, $d = '') => $edit[$k] ?? $d;

admin_header('Actualités et rapports');
?>
<div class="panel">
 <h2><?= $edit ? 'Modifier l’actualité' : 'Ajouter une actualité' ?></h2>
 <form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$v('id', 0) ?>">
  <div class="row g-3">
   <div class="col-md-3"><label class="form-label" for="type">Type</label>
    <select id="type" name="type" class="form-select">
     <option value="JPG"<?= $v('type', 'JPG') === 'JPG' ? ' selected' : '' ?>>Photo (JPG)</option>
     <option value="PDF"<?= $v('type') === 'PDF' ? ' selected' : '' ?>>Rapport (PDF)</option>
    </select></div>
   <div class="col-md-6"><label class="form-label" for="title">Titre</label><input id="title" name="title" class="form-control" maxlength="200" required value="<?= e($v('title')) ?>"></div>
   <div class="col-md-3"><label class="form-label" for="published_at">Date de publication</label><input id="published_at" name="published_at" type="date" class="form-control" required value="<?= e($v('published_at', date('Y-m-d'))) ?>"></div>
   <div class="col-12"><label class="form-label" for="summary">Résumé</label><textarea id="summary" name="summary" rows="2" maxlength="500" class="form-control"><?= e($v('summary')) ?></textarea></div>
   <div class="col-md-6">
    <label class="form-label" for="image">Photo <span class="text-secondary">(obligatoire pour une photo, facultative comme couverture d’un PDF)</span></label>
    <input id="image" name="image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp">
    <?php if ($edit && $edit['image']): ?><img class="mt-2 rounded" src="<?= e(upload_url($edit['image'], '../')) ?>" alt="Photo actuelle" style="max-height:90px"><?php endif; ?>
   </div>
   <div class="col-md-6">
    <label class="form-label" for="doc">Fichier PDF <span class="text-secondary">(obligatoire pour un rapport, 20 Mo maximum)</span></label>
    <input id="doc" name="doc" type="file" class="form-control" accept="application/pdf">
    <?php if ($edit && $edit['doc']): ?><div class="form-text"><i class="bi bi-file-earmark-pdf"></i> <a href="<?= e(upload_url($edit['doc'], '../')) ?>" target="_blank" rel="noopener">PDF actuel</a> (laisser vide pour le conserver)</div><?php endif; ?>
   </div>
   <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $v('active', 1) ? 'checked' : '' ?>><label class="form-check-label" for="active">Visible sur le site</label></div></div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-teal" type="submit">Enregistrer l’actualité</button><?php if ($edit): ?><a class="btn btn-outline-secondary" href="news.php">Annuler</a><?php endif; ?></div>
 </form>
</div>

<div class="panel">
 <h2>Publications existantes</h2>
 <?php if (!$rows): ?><p class="text-secondary mb-0">Aucune publication. Ajoutez votre première actualité ou rapport avec le formulaire ci-dessus.</p><?php else: ?>
 <div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Aperçu</th><th>Titre</th><th>Type</th><th>Date</th><th>État</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
   <tr>
    <td><?php if ($r['image']): ?><img class="thumb-s" src="<?= e(upload_url($r['image'], '../')) ?>" alt=""><?php else: ?><span class="thumb-s"><i class="bi bi-file-earmark-pdf fs-4"></i></span><?php endif; ?></td>
    <td><strong><?= e($r['title']) ?></strong></td>
    <td><span class="badge text-bg-warning"><?= e($r['type']) ?></span></td>
    <td class="text-nowrap"><?= e(fr_date($r['published_at'])) ?></td>
    <td><span class="badge text-bg-<?= $r['active'] ? 'success' : 'secondary' ?>"><?= $r['active'] ? 'Publiée' : 'Masquée' ?></span></td>
    <td class="text-end text-nowrap">
     <a class="btn btn-sm btn-outline-secondary" href="news.php?edit=<?= (int)$r['id'] ?>">Modifier</a>
     <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $r['active'] ? 'Masquer' : 'Publier' ?></button></form>
     <form method="post" class="d-inline" <?= confirm_attr('Supprimer cette publication et ses fichiers ?') ?>><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
    </td>
   </tr>
  <?php endforeach; ?></tbody></table></div>
 <?php endif; ?>
</div>
<?php admin_footer();
