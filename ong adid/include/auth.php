<?php
require_once __DIR__ . '/db.php';

session_name('adid_sid');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function redirect(string $to): never { header('Location: ' . $to); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Session expirée ou requête invalide. Rechargez la page et réessayez.');
    }
}

function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $st = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1');
            $st->execute([$_SESSION['uid']]);
            $u = $st->fetch() ?: null;
        }
    }
    return $u;
}

function require_login(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') {
        flash('danger', 'Cette section est réservée aux administrateurs.');
        redirect('index.php');
    }
    return $u;
}

/** Enregistre un fichier téléversé. Retourne le nom généré, ou null si aucun fichier. */
function save_upload(string $field, array $types, int $maxMb = 8): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Le téléversement a échoué (fichier trop volumineux ?).');
    if ($f['size'] > $maxMb * 1048576) throw new RuntimeException("Le fichier dépasse $maxMb Mo.");
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime])) throw new RuntimeException('Format non autorisé. Formats acceptés : ' . implode(', ', array_values($types)) . '.');
    $name = bin2hex(random_bytes(12)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Impossible d’enregistrer le fichier.');
    return $name;
}
function delete_upload(?string $name): void {
    if ($name && preg_match('/^[a-f0-9]{24}\.[a-z]{3,4}$/', $name) && is_file(UPLOAD_DIR . $name)) @unlink(UPLOAD_DIR . $name);
}
