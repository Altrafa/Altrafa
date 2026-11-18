<?php
require __DIR__ . '/../include/admin_ui.php';
$me = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);
    try {
        if ($act === 'save') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'editor';
            $active = isset($_POST['active']) ? 1 : 0;
            $pw = $_POST['password'] ?? '';
            if ($name === '') throw new RuntimeException('Le nom est obligatoire.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Adresse e-mail invalide.');
            if ($pw !== '' && strlen($pw) < 8) throw new RuntimeException('Le mot de passe doit contenir 8 caractères minimum.');
            if ($id) {
                if ($id === (int)$me['id'] && ($role !== 'admin' || !$active)) throw new RuntimeException('Vous ne pouvez pas retirer vos propres droits ni désactiver votre compte.');
                $pdo->prepare('UPDATE users SET name=?, email=?, role=?, active=? WHERE id=?')->execute([$name, $email, $role, $active, $id]);
                if ($pw !== '') $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            } else {
                if ($pw === '') throw new RuntimeException('Définissez un mot de passe pour le nouvel utilisateur.');
                $pdo->prepare('INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,?)')->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $active]);
            }
            flash('success', 'Utilisateur enregistré.');
        } elseif ($act === 'delete') {
            if ($id === (int)$me['id']) throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('success', 'Utilisateur supprimé.');
        }
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        flash('danger', $e->getCode() === '23000' ? 'Cette adresse e-mail est déjà utilisée.' : 'Erreur de base de données. Réessayez.');
    }
    redirect('users.php');
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$rows = $pdo->query('SELECT * FROM users ORDER BY name')->fetchAll();
$v = fn(string $k, $d = '') => $edit[$k] ?? $d;

admin_header('Utilisateurs');
?>
<div class="panel">
 <h2><?= $edit ? 'Modifier l’utilisateur' : 'Ajouter un utilisateur' ?></h2>
 <form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$v('id', 0) ?>">
  <div class="row g-3">
   <div class="col-md-4"><label class="form-label" for="name">Nom complet</label><input id="name" name="name" class="form-control" required value="<?= e($v('name')) ?>"></div>
   <div class="col-md-4"><label class="form-label" for="email">Adresse e-mail</label><input id="email" name="email" type="email" class="form-control" required value="<?= e($v('email')) ?>"></div>
   <div class="col-md-4"><label class="form-label" for="password">Mot de passe <?= $edit ? '(vide = inchangé)' : '' ?></label><input id="password" name="password" type="password" minlength="8" class="form-control" autocomplete="new-password" <?= $edit ? '' : 'required' ?>></div>
   <div class="col-md-4"><label class="form-label" for="role">Rôle</label>
    <select id="role" name="role" class="form-select">
     <option value="editor"<?= $v('role', 'editor') === 'editor' ? ' selected' : '' ?>>Éditeur : bannière, actualités, informations</option>
     <option value="admin"<?= $v('role') === 'admin' ? ' selected' : '' ?>>Administrateur : tout, y compris les utilisateurs</option>
    </select></div>
   <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $v('active', 1) ? 'checked' : '' ?>><label class="form-check-label" for="active">Compte actif</label></div></div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-teal" type="submit">Enregistrer l’utilisateur</button><?php if ($edit): ?><a class="btn btn-outline-secondary" href="users.php">Annuler</a><?php endif; ?></div>
 </form>
</div>

<div class="panel">
 <h2>Comptes existants</h2>
 <div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>État</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
   <tr>
    <td><strong><?= e($r['name']) ?></strong><?= (int)$r['id'] === (int)$me['id'] ? ' <small class="text-secondary">(vous)</small>' : '' ?></td>
    <td><?= e($r['email']) ?></td>
    <td><?= $r['role'] === 'admin' ? 'Administrateur' : 'Éditeur' ?></td>
    <td><span class="badge text-bg-<?= $r['active'] ? 'success' : 'secondary' ?>"><?= $r['active'] ? 'Actif' : 'Désactivé' ?></span></td>
    <td class="text-end text-nowrap">
     <a class="btn btn-sm btn-outline-secondary" href="users.php?edit=<?= (int)$r['id'] ?>">Modifier</a>
     <?php if ((int)$r['id'] !== (int)$me['id']): ?>
     <form method="post" class="d-inline" <?= confirm_attr('Supprimer cet utilisateur ?') ?>><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
     <?php endif; ?>
    </td>
   </tr>
  <?php endforeach; ?></tbody></table></div>
</div>
<?php admin_footer();
