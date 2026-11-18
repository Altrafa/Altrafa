<?php
require "include/header.php";
$banners = db()->query('SELECT * FROM banners WHERE active = 1 ORDER BY sort_order, id')->fetchAll();
$news    = db()->query('SELECT * FROM news WHERE active = 1 ORDER BY published_at DESC, id DESC LIMIT 60')->fetchAll();
?>
    <main>
        <section id="accueil" class="hero">
            <div id="banner" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000" aria-label="Bannière de bienvenue">
                <?php if (count($banners) > 1): ?>
                <div class="carousel-indicators">
                <?php foreach ($banners as $i => $b): ?>
                    <button type="button" data-bs-target="#banner" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Diapositive <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="carousel-inner">
                <?php if (!$banners): ?>
                <div class="carousel-item active" style="--g:linear-gradient(120deg,var(--hero-2),var(--hero))">
                    <div class="caption"><h1><?= e($S['site_name']) ?></h1><p><?= e($S['site_description']) ?></p></div>
                </div>
                <?php endif; ?>
                <?php foreach ($banners as $i => $b):
                    $y = (int)$b['focus_y'];
                ?>
                <div class="carousel-item bn<?= $b['layout'] === 'split' ? ' split' : '' ?><?= $i === 0 ? ' active' : '' ?>" style="--y:<?= $y ?>%">
                    <img src="<?= e(upload_url($b['image'])) ?>" alt="" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> onerror="this.style.display='none'">
                    <div class="caption"><h1><?= e($b['title']) ?></h1><?php if ($b['subtitle']): ?><p><?= e($b['subtitle']) ?></p><?php endif; ?></div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php if (count($banners) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#banner" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Précédent</span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#banner" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Suivant</span></button>
                <?php endif; ?>
            </div>
        </section>

        <section id="actualites" class="sec">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div><h2><?= e($S['news_title']) ?></h2><p class="lead-m mb-0"><?= e($S['news_intro']) ?></p></div>
                <div class="filters btn-group" role="group" aria-label="Filtrer les publications">
                    <button class="btn active" data-f="all">Tout</button><button class="btn" data-f="PDF">PDF</button><button class="btn" data-f="JPG">JPG</button>
                </div>
                </div>
                <div class="row g-4" id="grid">
                <?php foreach ($news as $n):
                    $isPdf = $n['type'] === 'PDF';
                    $cover = $n['image'] ? upload_url($n['image']) : '';
                    $link  = $isPdf ? upload_url($n['doc']) : $cover;
                ?>
                    <div class="col-sm-6 col-lg-3" data-type="<?= e($n['type']) ?>"><article class="card-n">
                        <?php if ($cover): ?>
                        <div class="thumb" role="img" aria-label="<?= e($n['title']) ?>" style="background:url('<?= e($cover) ?>') center/cover no-repeat"></div>
                        <?php else: ?>
                        <div class="thumb" style="--c:#2b4f7a"><svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg></div>
                        <?php endif; ?>
                        <div class="b">
                            <span class="tag"><?= e($n['type']) ?></span>
                            <h3 class="h5"><?= e($n['title']) ?></h3>
                            <small><?= e(fr_date($n['published_at'])) ?></small>
                            <p class="mt-2"><?= e($n['summary']) ?></p>
                            <?php if ($link): ?>
                            <a href="<?= e($link) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"<?= $isPdf ? ' download' : '' ?>><?= $isPdf ? 'Télécharger le PDF' : 'Voir la photo' ?></a>
                            <?php endif; ?>
                        </div>
                    </article></div>
                <?php endforeach; ?>
                </div>
                <p id="empty" class="lead-m mt-3"<?= $news ? ' hidden' : '' ?>>Aucune publication pour le moment.</p>
            </div>
        </section>

        <section id="apropos" class="sec about">
            <div class="container">
                <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <h2><?= e($S['about_title']) ?></h2>
                    <p class="lead-m"><?= e($S['about_p1']) ?></p>
                    <p class="lead-m"><?= e($S['about_p2']) ?></p>
                </div>
                <div class="col-lg-6" id="mission">
                    <div class="val"><h3>Notre mission</h3><p><?= e($S['mission']) ?></p></div>
                    <div class="val"><h3>Notre vision</h3><p><?= e($S['vision']) ?></p></div>
                    <div class="val"><h3>Nos valeurs</h3><p><?= e($S['values']) ?></p></div>
                </div>
                </div>
            </div>
        </section>
    </main>

<?php require "include/footer.php" ?>
