<?php
require __DIR__ . '/../include/admin_ui.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') throw new RuntimeException('Le nom est obligatoire.');
        db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $me['id']]);
        $new = $_POST['new_password'] ?? '';
        if ($new !== '') {
            $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?'); $st->execute([$me['id']]);
            if (!password_verify($_POST['current_password'] ?? '', (string)$st->fetchColumn())) throw new RuntimeException('Le mot de passe actuel est incorrect.');
            if (strlen($new) < 8) throw new RuntimeException('Le nouveau mot de passe doit contenir 8 caractères minimum.');
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        }
        flash('success', 'Profil mis à jour.');
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    }
    redirect('profile.php');
}
admin_header('Mon profil');
?>
<div class="panel" style="max-width:560px">
 <form method="post">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="name">Nom complet</label><input id="name" name="name" class="form-control" required value="<?= e($me['name']) ?>"></div>
  <div class="mb-3"><label class="form-label">Adresse e-mail</label><input class="form-control" value="<?= e($me['email']) ?>" disabled></div>
  <hr>
  <p class="text-secondary">Pour changer de mot de passe, remplissez les deux champs suivants.</p>
  <div class="mb-3"><label class="form-label" for="current_password">Mot de passe actuel</label><input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password"></div>
  <div class="mb-3"><label class="form-label" for="new_password">Nouveau mot de passe</label><input id="new_password" name="new_password" type="password" minlength="8" class="form-control" autocomplete="new-password"></div>
  <button class="btn btn-teal" type="submit">Enregistrer le profil</button>
 </form>
</div>
<?php admin_footer();
