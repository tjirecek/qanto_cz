<?php
declare(strict_types=1);
?>

<form method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="typ" value="<?= htmlspecialchars((string)$formValues['typ'], ENT_QUOTES) ?>">

    <div class="row g-3 mb-3">
        <div class="col-lg-2">
            <label for="typ_display" class="form-label">Typ pobočky</label>
            <input
                type="text"
                id="typ_display"
                class="form-control"
                value="<?= htmlspecialchars($typeLabel, ENT_QUOTES) ?>"
                disabled
            >
        </div>

        <div class="col-lg-1 col-md-2">
            <label for="poradi" class="form-label">Pořadí</label>
            <input
                type="number"
                name="poradi"
                id="poradi"
                class="form-control"
                value="<?= (int)($formValues['poradi'] ?? 0) ?>"
            >
        </div>

        <div class="col-lg-2 col-md-4">
            <label for="stredisko" class="form-label">Středisko</label>
            <input
                type="text"
                name="stredisko"
                id="stredisko"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['stredisko'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>

        <div class="col-lg-2 col-md-3">
            <label for="galerie_id" class="form-label">Galerie ID</label>
            <input
                type="number"
                name="galerie_id"
                id="galerie_id"
                class="form-control"
                value="<?= ($formValues['galerie_id'] === null || $formValues['galerie_id'] === '') ? '' : (int)$formValues['galerie_id'] ?>"
            >
        </div>

        <div class="col-lg-5 col-md-12">
            <label for="userfile" class="form-label">Obrázek pobočky</label>
            <input
                type="file"
                name="userfile"
                id="userfile"
                class="form-control"
                accept="image/*"
            >
            <?php if (!empty($formValues['image'])): ?>
                <div class="form-text">
                    Aktuální soubor:
                    <a href="<?= htmlspecialchars(asset_version((string)$formValues['image']), ENT_QUOTES) ?>" target="_blank">
                        <?= htmlspecialchars((string)basename((string)$formValues['image']), ENT_QUOTES) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3">
            <label for="nazev_cz" class="form-label">Název CZ</label>
            <input
                type="text"
                name="nazev_cz"
                id="nazev_cz"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['nazev_cz'] ?? ''), ENT_QUOTES) ?>"
                required
            >
        </div>

        <div class="col-lg-2">
            <label for="nazev_en" class="form-label">Název EN</label>
            <input
                type="text"
                name="nazev_en"
                id="nazev_en"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['nazev_en'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>

        <div class="col-lg-5">
            <label for="adresa" class="form-label">Adresa</label>
            <input
                type="text"
                name="adresa"
                id="adresa"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['adresa'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>

        <div class="col-lg-2">
            <label for="gps" class="form-label">GPS</label>
            <input
                type="text"
                name="gps"
                id="gps"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['gps'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3">
            <label for="mobil" class="form-label">Mobil</label>
            <input
                type="text"
                name="mobil"
                id="mobil"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['mobil'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>

        <div class="col-lg-3">
            <label for="email" class="form-label">E-mail</label>
            <input
                type="email"
                name="email"
                id="email"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['email'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>

        <div class="col-lg-4">
            <label for="vedouci" class="form-label">Vedoucí</label>
            <input
                type="text"
                name="vedouci"
                id="vedouci"
                class="form-control"
                value="<?= htmlspecialchars((string)($formValues['vedouci'] ?? ''), ENT_QUOTES) ?>"
            >
        </div>

        <div class="col-lg-2 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ((int)($formValues['valid'] ?? 0) === 1 ? 'checked' : '') ?>>
                <label class="form-check-label" for="valid">valid</label>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <label for="sluzby_cz" class="form-label">Služby CZ</label>
            <textarea name="sluzby_cz" id="sluzby_cz" class="form-control js-tinymce" rows="8" data-tinymce-height="280"><?= (string)($formValues['sluzby_cz'] ?? '') ?></textarea>
        </div>

        <div class="col-lg-6">
            <label for="sluzby_en" class="form-label">Služby EN</label>
            <textarea name="sluzby_en" id="sluzby_en" class="form-control js-tinymce" rows="8" data-tinymce-height="280"><?= (string)($formValues['sluzby_en'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <input type="hidden" name="add" value="<?= (int)$formActionValue ?>">
            <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars($formSubmitLabel, ENT_QUOTES) ?></button>
        </div>

        <?php if (!empty($formAudit)): ?>
            <div class="col-12 small text-muted">
                Založeno: <?= isset($formAudit['ts_i']) ? htmlspecialchars((string)format_datetime_www((string)$formAudit['ts_i']), ENT_QUOTES) : '' ?>;
                Založil: <?= htmlspecialchars((string)($formAudit['user_i'] ?? ''), ENT_QUOTES) ?>;
                Upraveno: <?= isset($formAudit['ts_u']) ? htmlspecialchars((string)format_datetime_www((string)$formAudit['ts_u']), ENT_QUOTES) : '' ?>;
                Upravil: <?= htmlspecialchars((string)($formAudit['user_u'] ?? ''), ENT_QUOTES) ?>
            </div>
        <?php endif; ?>
    </div>
</form>
