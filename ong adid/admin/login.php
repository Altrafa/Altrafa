<?php
require __DIR__ . '/../include/admin_ui.php';

if ((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) redirect('setup.php');
if (current_user()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $locked = ($_SESSION['lock_until'] ?? 0) > time();
    if ($locked) {
        flash('danger', 'Trop de tentatives. Réessayez dans quelques minutes.');
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? AND active = 1');
        $st->execute([trim($_POST['email'] ?? '')]);
        $u = $st->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id'];
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            redirect('index.php');
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
        flash('danger', 'Adresse e-mail ou mot de passe incorrect.');
    }
    redirect('login.php');
}
plain_header('Connexion à l’administration');
?>
<form method="post" class="panel" autocomplete="on">
 <?= csrf_field() ?>
 <div class="mb-3"><label class="form-label" for="email">Adresse e-mail</label><input id="email" name="email" type="email" class="form-control" required autofocus autocomplete="username"></div>
 <div class="mb-3"><label class="form-label" for="password">Mot de passe</label><input id="password" name="password" type="password" class="form-control" required autocomplete="current-password"></div>
 <button class="btn btn-teal w-100" type="submit">Se connecter</button>
</form>
<p class="text-center"><a href="../index.php">Retour au site</a></p>
<?php plain_footer();
