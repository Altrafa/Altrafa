<?php
// Création du premier administrateur. Inaccessible dès qu'un utilisateur existe.
require __DIR__ . '/../include/admin_ui.php';
if ((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) redirect('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) {
        flash('danger', 'Renseignez un nom, une adresse e-mail valide et un mot de passe de 8 caractères minimum.');
        redirect('setup.php');
    }
    db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"admin")')
        ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
    flash('success', 'Administrateur créé. Connectez-vous.');
    redirect('login.php');
}
plain_header('Créer le premier administrateur');
?>
<form method="post" class="panel">
 <?= csrf_field() ?>
 <div class="mb-3"><label class="form-label" for="name">Nom complet</label><input id="name" name="name" class="form-control" required></div>
 <div class="mb-3"><label class="form-label" for="email">Adresse e-mail</label><input id="email" name="email" type="email" class="form-control" required></div>
 <div class="mb-3"><label class="form-label" for="password">Mot de passe (8 caractères minimum)</label><input id="password" name="password" type="password" minlength="8" class="form-control" required autocomplete="new-password"></div>
 <button class="btn btn-teal w-100" type="submit">Créer l’administrateur</button>
</form>
<?php plain_footer();
