<?php
declare(strict_types=1);

$tenisResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && (string)($_POST['tenis_qcup_registration'] ?? '') === '1'
    && function_exists('frontend_tenis_qcup_submit')
) {
    $tenisResult = frontend_tenis_qcup_submit($_POST);
}

$tenisValues = is_array($tenisResult) ? (array)($tenisResult['values'] ?? []) : [];
$tenisEnabled = function_exists('frontend_tenis_qcup_enabled') && frontend_tenis_qcup_enabled();
$tenisYear = function_exists('frontend_tenis_qcup_year') ? frontend_tenis_qcup_year() : (int)date('Y');
$tenisCsrf = function_exists('frontend_tenis_qcup_csrf_token') ? frontend_tenis_qcup_csrf_token() : '';
$tenisValue = static fn(string $key): string => frontend_tenis_qcup_e($tenisValues[$key] ?? '');
$tenisTextTitle = function_exists('stat_text') ? stat_text('tenisqcup', (string)($lang ?? 'cz'), 'nazev') : null;
$tenisTextBody = function_exists('stat_text') ? stat_text('tenisqcup', (string)($lang ?? 'cz')) : null;
?>
<section class="brigada-page tenis-qcup-page">
    <div class="site-shell">
        <div class="brigada-layout">
            <div class="brigada-intro tenis-qcup-intro">
                <span><?= frontend_tenis_qcup_e(sprintf(ui_text('tenis.kicker'), $tenisYear)) ?></span>
                <h1><?= frontend_tenis_qcup_e($tenisTextTitle ?: ui_text('tenis.title_fallback')) ?></h1>
                <?php if (is_string($tenisTextBody) && trim($tenisTextBody) !== ''): ?>
                    <div class="tenis-qcup-intro__content"><?= $tenisTextBody ?></div>
                <?php endif; ?>
            </div>

            <section class="career-application brigada-form-card tenis-qcup-card" id="registrace-tenisqcup">
                <div class="career-application__head tenis-qcup-card__head">
                    <span><?= frontend_tenis_qcup_e(sprintf(ui_text('tenis.year'), $tenisYear)) ?></span>
                    <h2><?= frontend_tenis_qcup_e(ui_text('tenis.form_title')) ?></h2>
                    <p><?= frontend_tenis_qcup_e(ui_text('tenis.required_note')) ?></p>
                </div>

                <?php if (is_array($tenisResult)): ?>
                    <div class="career-application__message <?= !empty($tenisResult['ok']) ? 'career-application__message--success' : 'career-application__message--error' ?>" role="status">
                        <?= frontend_tenis_qcup_e($tenisResult['message'] ?? '') ?>
                    </div>
                <?php endif; ?>

                <?php if (!$tenisEnabled): ?>
                    <div class="career-application__message career-application__message--error" role="status">
                        <?= frontend_tenis_qcup_e(ui_text('tenis.closed')) ?>
                    </div>
                <?php else: ?>
                    <form class="career-application__form brigada-form tenis-qcup-form" method="post" action="/<?= frontend_tenis_qcup_e((string)($lang ?? 'cz')) ?>/tenisqcup#registrace-tenisqcup">
                        <input type="hidden" name="tenis_qcup_registration" value="1">
                        <input type="hidden" name="csrf_token" value="<?= frontend_tenis_qcup_e($tenisCsrf) ?>">

                        <label class="career-application__field career-application__field--wide">
                            <span><?= frontend_tenis_qcup_e(ui_text('tenis.team_name')) ?> *</span>
                            <input type="text" name="team_name" value="<?= $tenisValue('team_name') ?>" maxlength="255" autocomplete="organization" required>
                        </label>

                        <div class="career-application__head tenis-qcup-form__section-head">
                            <span><?= frontend_tenis_qcup_e(ui_text('tenis.player_1')) ?></span>
                            <h2><?= frontend_tenis_qcup_e(ui_text('tenis.contact_player')) ?></h2>
                        </div>
                        <div class="career-application__grid brigada-form__grid">
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.first_name')) ?> *</span>
                                <input type="text" name="name1" value="<?= $tenisValue('name1') ?>" maxlength="100" autocomplete="given-name" required>
                            </label>
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.last_name')) ?> *</span>
                                <input type="text" name="surname1" value="<?= $tenisValue('surname1') ?>" maxlength="100" autocomplete="family-name" required>
                            </label>
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.email')) ?> *</span>
                                <input type="email" name="email1" value="<?= $tenisValue('email1') ?>" maxlength="190" autocomplete="email" required>
                            </label>
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.phone')) ?> *</span>
                                <input type="tel" name="mobil1" value="<?= $tenisValue('mobil1') ?>" maxlength="50" autocomplete="tel" required>
                            </label>
                        </div>

                        <div class="career-application__head tenis-qcup-form__section-head">
                            <span><?= frontend_tenis_qcup_e(ui_text('tenis.player_2')) ?></span>
                            <h2><?= frontend_tenis_qcup_e(ui_text('tenis.additional_player')) ?></h2>
                        </div>
                        <div class="career-application__grid brigada-form__grid">
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.first_name')) ?></span>
                                <input type="text" name="name2" value="<?= $tenisValue('name2') ?>" maxlength="100" autocomplete="off">
                            </label>
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.last_name')) ?></span>
                                <input type="text" name="surname2" value="<?= $tenisValue('surname2') ?>" maxlength="100" autocomplete="off">
                            </label>
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.email')) ?></span>
                                <input type="email" name="email2" value="<?= $tenisValue('email2') ?>" maxlength="190" autocomplete="off">
                            </label>
                            <label class="career-application__field">
                                <span><?= frontend_tenis_qcup_e(ui_text('tenis.phone')) ?></span>
                                <input type="tel" name="mobil2" value="<?= $tenisValue('mobil2') ?>" maxlength="50" autocomplete="off">
                            </label>
                        </div>

                        <label class="career-application__field career-application__field--wide">
                            <span><?= frontend_tenis_qcup_e(ui_text('tenis.invited_by')) ?> *</span>
                            <input type="text" name="pozval" value="<?= $tenisValue('pozval') ?>" maxlength="1000" required>
                        </label>
                        <label class="career-application__field career-application__field--wide">
                            <span><?= frontend_tenis_qcup_e(ui_text('tenis.note')) ?></span>
                            <textarea name="poznamka" rows="4" maxlength="3000"><?= $tenisValue('poznamka') ?></textarea>
                        </label>

                        <?php if (function_exists('frontend_captcha_render')): ?>
                            <?php frontend_captcha_render('tenis_qcup_registration', 'tenis-qcup-registration'); ?>
                        <?php endif; ?>

                        <label class="brigada-checkbox">
                            <input type="checkbox" name="souhlas" value="1" <?= !empty($tenisValues['souhlas']) ? 'checked' : '' ?> required>
                            <span><?= frontend_tenis_qcup_e(ui_text('tenis.consent_prefix')) ?> <a href="/<?= frontend_tenis_qcup_e((string)($lang ?? 'cz')) ?>/osobni-udaje"><?= frontend_tenis_qcup_e(ui_text('contacts.form_privacy_link')) ?></a> <?= frontend_tenis_qcup_e(ui_text('tenis.consent_suffix')) ?></span>
                        </label>

                        <button class="career-application__submit" type="submit"><?= frontend_tenis_qcup_e(ui_text('tenis.submit')) ?></button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>
