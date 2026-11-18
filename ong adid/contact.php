<?php
// Réception du formulaire de contact : enregistrement en base + notification par e-mail.
require __DIR__ . '/include/db.php';
header('Content-Type: application/json; charset=utf-8');

function out(bool $ok, string $msg, int $code = 200): never {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Méthode non autorisée.', 405);

// Champ piège : un robot le remplit, un visiteur ne le voit pas. On feint le succès.
if (trim($_POST['website'] ?? '') !== '') out(true, 'Merci, votre message a bien été envoyé.');

$name  = trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $_POST['name'] ?? ''));
$email = trim($_POST['email'] ?? '');
$msg   = trim($_POST['message'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $msg === '') out(false, 'Renseignez votre nom, une adresse e-mail valide et un message.', 422);
if (mb_strlen($name) > 120 || strlen($email) > 190 || mb_strlen($msg) > 3000) out(false, 'Votre message est trop long (3 000 caractères maximum).', 422);

$ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
try {
    $st = db()->prepare('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
    $st->execute([$ip]);
    if ((int)$st->fetchColumn() >= 5) out(false, 'Trop de messages envoyés. Réessayez dans une heure.', 429);

    db()->prepare('INSERT INTO messages (name, email, message, ip) VALUES (?,?,?,?)')->execute([$name, $email, $msg, $ip]);
} catch (PDOException $e) {
    out(false, 'Une erreur est survenue. Réessayez plus tard.', 500);
}

// Notification e-mail (n'empêche jamais l'enregistrement si l'envoi échoue)
$to = settings()['email'] ?? '';
if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $host = preg_replace('/:\d+$/', '', preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = "From: " . settings()['site_name'] . " <noreply@$host>\r\n"
             . "Reply-To: $email\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $headers = str_replace(["\n\n"], "\n", $headers);
    $subject = '=?UTF-8?B?' . base64_encode('Nouveau message de ' . $name) . '?=';
    $body = "Nom : $name\nE-mail : $email\n\n$msg\n\n— Message aussi consultable dans l'administration du site.";
    @mail($to, $subject, $body, $headers);
}
out(true, 'Merci ' . $name . ', votre message a bien été envoyé.');
