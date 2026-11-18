<?php $S = settings(); $activities = array_filter(array_map('trim', explode("\n", $S['activities']))); ?>
    <footer id="contact">
        <div class="container">
            <div class="row g-5">
            <div class="col-lg-5">
                <h3>Contactez-nous</h3>
                <form id="cf" novalidate>
                    <div style="position:absolute;left:-9999px" aria-hidden="true"><label for="website">Ne pas remplir</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="mb-3"><label class="form-label" for="n">Nom complet</label><input id="n" class="form-control" required autocomplete="name"></div>
                    <div class="mb-3"><label class="form-label" for="e">Adresse e-mail</label><input id="e" type="email" class="form-control" required autocomplete="email"></div>
                    <div class="mb-3"><label class="form-label" for="m">Message</label><textarea id="m" rows="4" class="form-control" required></textarea></div>
                    <button class="btn btn-don" type="submit">Envoyer le message</button>
                    <div id="fb" class="mt-3" role="status" aria-live="polite"></div>
                </form>
            </div>
            <div class="col-md-6 col-lg-3">
                <h3>Nos activités</h3>
                <ul><?php foreach ($activities as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
            </div>
            <div class="col-md-6 col-lg-4">
                <h3>Informations</h3>
                <ul>
                    <li><strong><i class="bi bi-geo-alt-fill"></i> Adresse :</strong> <?= e($S['address']) ?></li>
                    <li><strong><i class="bi bi-envelope-fill"></i> E-mail :</strong> <a href="mailto:<?= e($S['email']) ?>"><?= e($S['email']) ?></a></li>
                    <li><strong><i class="bi bi-telephone-fill"></i> Téléphone :</strong> <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $S['phone'])) ?>"><?= e($S['phone']) ?></a></li>
                </ul>
            </div>
            </div>
            <div class="copy d-flex flex-wrap justify-content-between gap-2"><span><?= e($S['copyright']) ?></span><a href="./admin/"><i class="bi bi-person-lock"></i> Espace admin</a></div>
        </div>
    </footer>

    <button id="up" type="button" aria-label="Retour au début de la page" title="Retour au début"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>

    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/main.js"></script>
    </body>
</html>
