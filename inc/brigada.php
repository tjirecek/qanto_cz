<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$brigadaResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($_POST['brigada_registration'] ?? '') === '1' && function_exists('frontend_brigadnici_submit')) {
    $brigadaResult = frontend_brigadnici_submit($_POST, $lang);
}

$brigadaValues = is_array($brigadaResult) ? (array)($brigadaResult['values'] ?? []) : [];
$brigadaBranches = function_exists('frontend_brigadnici_branches') ? frontend_brigadnici_branches($lang) : [];
$selectedBranchId = (int)($brigadaValues['pobocka_id'] ?? 0);
$selectedBranch = null;
foreach ($brigadaBranches as $branch) {
    if ((int)$branch['id'] === $selectedBranchId) {
        $selectedBranch = $branch;
        break;
    }
}
$csrfToken = function_exists('frontend_brigadnici_csrf_token') ? frontend_brigadnici_csrf_token() : '';
?>
<section class="brigada-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= frontend_brigadnici_e(ui_text('aria.breadcrumb')) ?>">
            <ol>
                <li>
                    <a href="/<?= frontend_brigadnici_e($lang) ?>" aria-label="<?= frontend_brigadnici_e(ui_text('aria.breadcrumb_home')) ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li><a href="/<?= frontend_brigadnici_e($lang) ?>/kariera"><?= frontend_brigadnici_e(ui_text('nav.kariera')) ?></a></li>
                <li><span aria-current="page"><?= frontend_brigadnici_e(ui_text('brigada.breadcrumb')) ?></span></li>
            </ol>
        </nav>

        <div class="brigada-layout">
            <div class="brigada-intro">
                <span><?= frontend_brigadnici_e(ui_text('brigada.label')) ?></span>
                <h1><?= frontend_brigadnici_e(ui_text('brigada.title')) ?></h1>
                <p><?= frontend_brigadnici_e(stat_vyraz_text('brigada.intro')) ?></p>
                <ul>
                    <li><?= frontend_brigadnici_e(stat_vyraz_text('brigada.point_1')) ?></li>
                    <li><?= frontend_brigadnici_e(stat_vyraz_text('brigada.point_2')) ?></li>
                    <li><?= frontend_brigadnici_e(stat_vyraz_text('brigada.point_3')) ?></li>
                </ul>
            </div>

            <section class="career-application brigada-form-card" id="registrace-brigadnika">
                <div class="career-application__head">
                    <span><?= frontend_brigadnici_e(ui_text('brigada.form_label')) ?></span>
                    <h2><?= frontend_brigadnici_e(ui_text('brigada.form_title')) ?></h2>
                    <p><?= frontend_brigadnici_e(stat_vyraz_text('brigada.form_intro')) ?></p>
                </div>

                <?php if (is_array($brigadaResult)): ?>
                    <div class="career-application__message <?= !empty($brigadaResult['ok']) ? 'career-application__message--success' : 'career-application__message--error' ?>" role="status">
                        <?= frontend_brigadnici_e($brigadaResult['message'] ?? '') ?>
                    </div>
                <?php endif; ?>

                <form class="career-application__form brigada-form" method="post" action="/<?= frontend_brigadnici_e($lang) ?>/brigada#registrace-brigadnika" data-brigada-form>
                    <input type="hidden" name="brigada_registration" value="1">
                    <input type="hidden" name="csrf_token" value="<?= frontend_brigadnici_e($csrfToken) ?>">
                    <input type="hidden" name="pobocka_id" value="<?= $selectedBranchId > 0 ? $selectedBranchId : '' ?>" data-brigada-branch-id required>

                    <div class="brigada-branch-field">
                        <span><?= frontend_brigadnici_e(ui_text('brigada.branch_label')) ?> *</span>
                        <button type="button" class="brigada-branch-picker" data-brigada-branch-open aria-haspopup="dialog">
                            <strong data-brigada-branch-name><?= $selectedBranch ? frontend_brigadnici_e((string)$selectedBranch['title']) : frontend_brigadnici_e(ui_text('brigada.branch_choose')) ?></strong>
                            <small data-brigada-branch-meta><?= $selectedBranch ? frontend_brigadnici_e(trim((string)$selectedBranch['type_label'] . ' · ' . (string)$selectedBranch['address'], " ·")) : frontend_brigadnici_e(ui_text('brigada.branch_help')) ?></small>
                        </button>
                        <div class="brigada-branch-field__error" data-brigada-branch-error hidden><?= frontend_brigadnici_e(ui_text('brigada.error_branch')) ?></div>
                    </div>

                    <label class="career-application__field">
                        <span><?= frontend_brigadnici_e(ui_text('brigada.position')) ?> *</span>
                        <input type="text" name="pozice" value="<?= frontend_brigadnici_e(ui_text('brigada.position_value')) ?>" readonly>
                    </label>

                    <div class="career-application__grid brigada-form__grid">
                        <label class="career-application__field">
                            <span><?= frontend_brigadnici_e(ui_text('brigada.first_name')) ?> *</span>
                            <input type="text" name="jmeno" value="<?= frontend_brigadnici_form_value($brigadaValues, 'jmeno') ?>" autocomplete="given-name" required>
                        </label>
                        <label class="career-application__field">
                            <span><?= frontend_brigadnici_e(ui_text('brigada.last_name')) ?> *</span>
                            <input type="text" name="prijmeni" value="<?= frontend_brigadnici_form_value($brigadaValues, 'prijmeni') ?>" autocomplete="family-name" required>
                        </label>
                        <label class="career-application__field">
                            <span><?= frontend_brigadnici_e(ui_text('brigada.phone')) ?> *</span>
                            <input type="tel" name="mobil" value="<?= frontend_brigadnici_form_value($brigadaValues, 'mobil') ?>" autocomplete="tel" required>
                        </label>
                        <label class="career-application__field">
                            <span><?= frontend_brigadnici_e(ui_text('brigada.email')) ?> *</span>
                            <input type="email" name="email" value="<?= frontend_brigadnici_form_value($brigadaValues, 'email') ?>" autocomplete="email" required>
                        </label>
                        <label class="brigada-checkbox">
                            <input type="checkbox" name="zkusenosti_l" value="1" <?= (string)($brigadaValues['zkusenosti_l'] ?? '') === '1' ? 'checked' : '' ?>>
                            <span><?= frontend_brigadnici_e(ui_text('brigada.experience')) ?></span>
                        </label>
                    </div>

                    <label class="career-application__field career-application__field--wide">
                        <span><?= frontend_brigadnici_e(ui_text('brigada.note')) ?></span>
                        <textarea name="poznamka" rows="4"><?= frontend_brigadnici_form_value($brigadaValues, 'poznamka') ?></textarea>
                    </label>

                    <?php if (function_exists('frontend_captcha_render')): ?>
                        <?php frontend_captcha_render('brigada_registration', 'brigada-registration'); ?>
                    <?php endif; ?>

                    <button class="career-application__submit" type="submit"><?= frontend_brigadnici_e(ui_text('brigada.submit')) ?></button>

                    <p class="brigada-form__consent">
                        <?= frontend_brigadnici_e(ui_text('contacts.form_consent')) ?>
                        <a href="/<?= frontend_brigadnici_e($lang) ?>/osobni-udaje"><?= frontend_brigadnici_e(ui_text('contacts.form_privacy_link')) ?></a>.
                    </p>
                </form>
            </section>
        </div>
    </div>

    <div class="brigada-branch-modal" data-brigada-branch-modal hidden>
        <div class="brigada-branch-modal__backdrop" data-brigada-branch-close></div>
        <div class="brigada-branch-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="brigada-branch-modal-title">
            <div class="brigada-branch-modal__head">
                <div>
                    <span><?= frontend_brigadnici_e(ui_text('brigada.modal_label')) ?></span>
                    <h2 id="brigada-branch-modal-title"><?= frontend_brigadnici_e(ui_text('brigada.modal_title')) ?></h2>
                </div>
                <button type="button" class="brigada-branch-modal__close" data-brigada-branch-close aria-label="<?= frontend_brigadnici_e(ui_text('common.close')) ?>">×</button>
            </div>
            <label class="brigada-branch-modal__search">
                <span><?= frontend_brigadnici_e(ui_text('common.search')) ?></span>
                <input type="search" data-brigada-branch-search placeholder="<?= frontend_brigadnici_e(ui_text('brigada.modal_search')) ?>">
            </label>
            <div class="brigada-branch-modal__list" data-brigada-branch-list>
                <?php $lastGroup = ''; ?>
                <?php foreach ($brigadaBranches as $branch): ?>
                    <?php $group = (string)$branch['group_label']; ?>
                    <?php if ($group !== $lastGroup): ?>
                        <?php $lastGroup = $group; ?>
                        <h3><?= frontend_brigadnici_e($group) ?></h3>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="brigada-branch-option"
                        data-brigada-branch-option
                        data-id="<?= (int)$branch['id'] ?>"
                        data-name="<?= frontend_brigadnici_e($branch['title']) ?>"
                        data-meta="<?= frontend_brigadnici_e(trim((string)$branch['type_label'] . ' · ' . (string)$branch['address'], " ·")) ?>"
                        data-search="<?= frontend_brigadnici_e($branch['search']) ?>"
                    >
                        <strong><?= frontend_brigadnici_e($branch['title']) ?></strong>
                        <span><?= frontend_brigadnici_e(trim((string)$branch['type_label'] . ' · ' . (string)$branch['address'], " ·")) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="brigada-branch-modal__empty" data-brigada-branch-empty hidden><?= frontend_brigadnici_e(ui_text('brigada.modal_empty')) ?></div>
        </div>
    </div>
</section>
