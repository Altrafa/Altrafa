<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

/** Informations de l'organisation (table settings). */
function settings(): array {
    static $s = null;
    if ($s === null) {
        $rows = db()->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $s = array_merge(['theme_mode' => 'auto', 'color_primary' => '#0f5c57', 'color_accent' => '#e8a317'], $rows);
    }
    return $s;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/**
 * Adresse d'une image/fichier enregistré en base. Accepte :
 *  - un nom seul (« photo.jpg »)            → dossier uploads/
 *  - un chemin relatif (« assets/img/a.jpg ») → relatif à la racine du site
 *  - une URL complète (« https://… »)
 */
function upload_url(?string $name, string $prefix = './'): string {
    $name = trim((string)$name);
    if ($name === '') return '';
    if (preg_match('#^(https?:)?//#i', $name)) return $name;
    if (strpos($name, '/') !== false) {
        $segs = array_map('rawurlencode', explode('/', ltrim($name, './')));
        return $prefix . implode('/', $segs);
    }
    return $prefix . 'uploads/' . rawurlencode($name);
}

function fr_date(string $d): string {
    $m = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $t = strtotime($d);
    return $t ? date('d', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . date('Y', $t) : '';
}

/* ---------- Palette de couleurs du site ---------- */
function hex_rgb(string $h): array { $h = ltrim($h, '#'); return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))]; }
/** Mélange deux couleurs : $t = 0 → $a, $t = 1 → $b. */
function mix_hex(string $a, string $b, float $t): string {
    [$r1, $g1, $b1] = hex_rgb($a); [$r2, $g2, $b2] = hex_rgb($b);
    return sprintf('#%02x%02x%02x', round($r1 + ($r2 - $r1) * $t), round($g1 + ($g2 - $g1) * $t), round($b1 + ($b2 - $b1) * $t));
}
function luminance(string $h): float {
    $c = array_map(fn($v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4), hex_rgb($h));
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
/** Texte lisible (sombre ou blanc) sur une couleur de fond. */
function on_color(string $h): string { return luminance($h) > 0.4 ? '#2a1d00' : '#ffffff'; }

/** Feuille de style générée à partir des deux couleurs choisies dans l'administration. */
function theme_css(array $S): string {
    $ok = fn($c, $d) => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$c) ? strtolower($c) : $d;
    $P = $ok($S['color_primary'] ?? '', '#0f5c57');
    $A = $ok($S['color_accent'] ?? '', '#e8a317');
    $hero = mix_hex($P, '#000000', .65);
    [$hr, $hg, $hb] = hex_rgb($hero);
    $light = "--teal:$P;--gold:$A;--ink:" . mix_hex($P, '#000000', .6) . ';--muted:' . mix_hex($P, '#444444', .65)
           . ';--bg:' . mix_hex($P, '#ffffff', .96) . ';--line:' . mix_hex($P, '#ffffff', .85) . ';--foot:' . mix_hex($P, '#000000', .55);
    $dark  = '--teal:' . mix_hex($P, '#ffffff', .4) . ';--gold:' . $A . ';--ink:#e6f0ed;--muted:#a3b8b3;--bg:' . mix_hex($P, '#101414', .85)
           . ';--surface:' . mix_hex($P, '#101414', .78) . ';--line:' . mix_hex($P, '#101414', .65) . ';--foot:' . mix_hex($P, '#000000', .85);
    $onA = on_color($A); $hov = mix_hex($A, '#ffffff', .2);
    $darkExtra = '';
    return ":root{{$light};--hero:$hero;--hero-2:$P;--hero-rgb:$hr,$hg,$hb}"
         . "@media (prefers-color-scheme:dark){:root:not([data-theme=\"light\"]){{$dark}}:root:not([data-theme=\"light\"]) .filters .btn.active{color:#0b1f1c}}"
         . ":root[data-theme=\"dark\"]{{$dark}}:root[data-theme=\"dark\"] .filters .btn.active{color:#0b1f1c}"
         . ".btn-don,.btn-don:hover,.tag,#up{color:$onA}.btn-don:hover,#up:hover{background:$hov}";
}
