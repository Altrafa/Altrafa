<?php
require __DIR__ . '/../include/admin_ui.php';
require_login();

// clé => [libellé, type, groupe]
$fields = [
 'site_name'        => ['Nom de l’organisation', 'text', 'Identité'],
 'site_title'       => ['Titre de l’onglet du navigateur', 'text', 'Identité'],
 'site_description' => ['Description courte (référencement)', 'text', 'Identité'],
 'theme_mode'       => ['Mode d’affichage par défaut', 'select', 'Apparence'],
 'color_primary'    => ['Couleur principale (menu, titres, pied de page)', 'color', 'Apparence'],
 'color_accent'     => ['Couleur d’accent (boutons, étiquettes)', 'color', 'Apparence'],
 'news_title'       => ['Titre de la section actualités', 'text', 'Section actualités'],
 'news_intro'       => ['Introduction de la section', 'text', 'Section actualités'],
 'about_title'      => ['Titre de la section « À propos »', 'text', 'À propos'],
 'about_p1'         => ['Premier paragraphe', 'textarea', 'À propos'],
 'about_p2'         => ['Second paragraphe', 'textarea', 'À propos'],
 'mission'          => ['Notre mission', 'textarea', 'À propos'],
 'vision'           => ['Notre vision', 'textarea', 'À propos'],
 'values'           => ['Nos valeurs', 'textarea', 'À propos'],
 'activities'       => ['Nos activités (une par ligne)', 'textarea', 'Pied de page'],
 'address'          => ['Adresse', 'text', 'Pied de page'],
 'email'            => ['E-mail de contact', 'email', 'Pied de page'],
 'phone'            => ['Téléphone', 'text', 'Pied de page'],
 'copyright'        => ['Mention de copyright', 'text', 'Pied de page'],
];

$selects = ['theme_mode' => ['auto' => 'Automatique (selon l’appareil du visiteur)', 'light' => 'Toujours clair', 'dark' => 'Toujours sombre']];
$presets = [
    ['Vert et or', '#0f5c57', '#e8a317'], ['Bleu et orange', '#1f4e8c', '#f08a24'], ['Bordeaux et or', '#8c1d40', '#e8a317'],
    ['Violet et vert', '#5b3f9e', '#2fb47c'], ['Anthracite et rouge', '#2e3640', '#d63a3a'], ['Forêt et citron', '#2d6a2f', '#f2c744'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $S = settings();
        $up = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
        foreach ($fields as $k => [$label, $type]) {
            $val = trim($_POST[$k] ?? '');
            if ($type === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('L’adresse e-mail de contact est invalide.');
            if ($type === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $val)) throw new RuntimeException('Couleur invalide.');
            if ($type === 'select' && !isset($selects[$k][$val])) throw new RuntimeException('Valeur invalide pour « ' . $label . ' ».');
            if ($k === 'site_name' && $val === '') throw new RuntimeException('Le nom de l’organisation est obligatoire.');
            $up->execute([$k, $val]);
        }
        $logo = save_upload('logo', IMG_TYPES, 3);
        if ($logo) { $up->execute(['logo', $logo]); delete_upload($S['logo'] ?: null); }
        elseif (isset($_POST['reset_logo'])) { $up->execute(['logo', '']); delete_upload($S['logo'] ?: null); }
        flash('success', 'Informations enregistrées.');
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        flash('danger', 'Erreur de base de données. Réessayez.');
    }
    redirect('settings.php');
}

$S = settings();
admin_header('Informations de l’organisation');
?>
<form method="post" enctype="multipart/form-data">
 <?= csrf_field() ?>
 <?php $group = null; foreach ($fields as $k => [$label, $type, $g]): ?>
  <?php if ($g !== $group): if ($group !== null) echo '</div></div>'; $group = $g; ?>
   <div class="panel"><h2><?= e($g) ?></h2><div class="row g-3">
   <?php if ($g === 'Apparence'): ?>
    <div class="col-12"><span class="form-label d-block">Palettes prêtes à l’emploi</span>
     <div class="d-flex flex-wrap gap-2">
      <?php foreach ($presets as [$pn, $p1, $p2]): ?>
       <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 preset" data-p1="<?= $p1 ?>" data-p2="<?= $p2 ?>"><span style="display:inline-flex"><i style="width:16px;height:16px;border-radius:50%;background:<?= $p1 ?>;display:block"></i><i style="width:16px;height:16px;border-radius:50%;background:<?= $p2 ?>;display:block;margin-left:-5px"></i></span><?= e($pn) ?></button>
      <?php endforeach; ?>
     </div><div class="form-text">Cliquez sur une palette, ajustez si besoin avec les sélecteurs, puis enregistrez. Les teintes sombres, les ombres et le pied de page se calculent automatiquement.</div></div>
   <?php endif; ?>
   <?php if ($g === 'Identité'): ?>
    <div class="col-12"><label class="form-label" for="logo">Logo</label>
     <div class="d-flex align-items-center gap-3 flex-wrap">
      <img src="<?= e($S['logo'] ? upload_url($S['logo'], '../') : '../assets/img/logo.png') ?>" alt="Logo actuel" width="64" height="64" style="object-fit:contain">
      <input id="logo" name="logo" type="file" class="form-control" style="max-width:340px" accept="image/jpeg,image/png,image/webp">
      <?php if ($S['logo']): ?><div class="form-check"><input class="form-check-input" type="checkbox" id="reset_logo" name="reset_logo" value="1"><label class="form-check-label" for="reset_logo">Revenir au logo par défaut</label></div><?php endif; ?>
     </div></div>
   <?php endif; ?>
  <?php endif; ?>
  <div class="<?= $type === 'textarea' ? 'col-12' : ($type === 'color' ? 'col-md-4' : 'col-md-6') ?>">
   <label class="form-label" for="<?= $k ?>"><?= e($label) ?></label>
   <?php if ($type === 'textarea'): ?><textarea id="<?= $k ?>" name="<?= $k ?>" rows="<?= $k === 'activities' ? 4 : 3 ?>" class="form-control"><?= e($S[$k] ?? '') ?></textarea>
   <?php elseif ($type === 'select'): ?><select id="<?= $k ?>" name="<?= $k ?>" class="form-select"><?php foreach ($selects[$k] as $ov => $ol): ?><option value="<?= $ov ?>"<?= ($S[$k] ?? '') === $ov ? ' selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
   <?php elseif ($type === 'color'): ?><input id="<?= $k ?>" name="<?= $k ?>" type="color" class="form-control form-control-color w-100" value="<?= e($S[$k] ?? '#000000') ?>">
   <?php else: ?><input id="<?= $k ?>" name="<?= $k ?>" type="<?= $type ?>" class="form-control" value="<?= e($S[$k] ?? '') ?>"><?php endif; ?>
  </div>
 <?php endforeach; ?>
 </div></div>
 <button class="btn btn-teal" type="submit">Enregistrer les informations</button>
</form>
<script>document.querySelectorAll('.preset').forEach(function(b){b.onclick=function(){document.getElementById('color_primary').value=b.dataset.p1;document.getElementById('color_accent').value=b.dataset.p2}})</script>
<?php admin_footer();
