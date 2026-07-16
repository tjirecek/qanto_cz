<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$jobDetailPostId = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($_POST['career_application'] ?? '') === '1')
    ? max(0, (int)($_POST['volne_misto_id'] ?? 0))
    : 0;
$jobDetailId = $jobDetailPostId > 0 ? $jobDetailPostId : (isset($_GET['pozice']) ? max(0, (int)$_GET['pozice']) : 0);
$jobDetail = $jobDetailId > 0 && function_exists('frontend_volna_mista_job_detail')
    ? frontend_volna_mista_job_detail($jobDetailId, $lang)
    : null;
$applicationResult = null;
if ($jobDetail && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') && (string)($_POST['career_application'] ?? '') === '1' && function_exists('frontend_volna_mista_application_submit')) {
    $applicationResult = frontend_volna_mista_application_submit($_POST, $_FILES, $lang);
}
$applicationValues = is_array($applicationResult) ? ($applicationResult['values'] ?? []) : [];
$applicationCsrf = function_exists('frontend_volna_mista_application_csrf_token') ? frontend_volna_mista_application_csrf_token() : '';
$applicationInputFields = [
    ['name' => 'dot_name', 'label' => ui_text('kariera.application.name', 'Jméno a příjmení'), 'type' => 'text', 'required' => true],
    ['name' => 'dot_mobil', 'label' => ui_text('kariera.application.phone', 'Mobil'), 'type' => 'tel', 'required' => true],
    ['name' => 'dot_email', 'label' => ui_text('kariera.application.email', 'E-mail'), 'type' => 'email', 'required' => true],
    ['name' => 'dot_adresa', 'label' => ui_text('kariera.application.address', 'Adresa'), 'type' => 'text'],
    ['name' => 'dot_birthday', 'label' => ui_text('kariera.application.birthdate', 'Datum narození'), 'type' => 'text'],
    ['name' => 'dot_vzdelani', 'label' => ui_text('kariera.application.education', 'Vzdělání'), 'type' => 'text'],
    ['name' => 'dot_rp', 'label' => ui_text('kariera.application.license', 'Řidičský průkaz'), 'type' => 'text'],
    ['name' => 'dot_pracdoba', 'label' => ui_text('kariera.application.work_time', 'Možná pracovní doba'), 'type' => 'text'],
    ['name' => 'dot_plat', 'label' => ui_text('kariera.application.salary', 'Představa o platu'), 'type' => 'text'],
];
$applicationTextareaFields = [
    ['name' => 'dot_predchozizam', 'label' => ui_text('kariera.application.previous_employer', 'Předchozí zaměstnavatel')],
    ['name' => 'dot_funkcezam', 'label' => ui_text('kariera.application.previous_role', 'Funkce')],
    ['name' => 'dot_delkazam', 'label' => ui_text('kariera.application.previous_duration', 'Délka zaměstnání')],
    ['name' => 'dot_jazyk', 'label' => ui_text('kariera.application.languages', 'Jazykové znalosti')],
    ['name' => 'dot_pc', 'label' => ui_text('kariera.application.pc', 'Práce na PC')],
    ['name' => 'dot_zaliby', 'label' => ui_text('kariera.application.hobbies', 'Záliby')],
    ['name' => 'dot_onas', 'label' => ui_text('kariera.application.source', 'Jak jste se o nás dozvěděl/a?')],
    ['name' => 'dot_prinos', 'label' => ui_text('kariera.application.benefit', 'Čím můžete být pro Qanto přínosem?')],
    ['name' => 'dot_rejstrik', 'label' => ui_text('kariera.application.criminal_record', 'Rejstřík trestů')],
    ['name' => 'dot_profzivot', 'label' => ui_text('kariera.application.cv_text', 'Profesní životopis')],
];
$detailSidebarItems = $jobDetail && function_exists('frontend_detail_sidebar_ads') ? frontend_detail_sidebar_ads($lang, 3) : [];
$mapBranches = $jobDetail ? [] : (function_exists('frontend_volna_mista_map_branches') ? frontend_volna_mista_map_branches($lang) : []);
$jobs = $jobDetail ? [] : (function_exists('frontend_volna_mista_jobs') ? frontend_volna_mista_jobs($lang, null) : []);
$jobCityByStredisko = $jobDetail ? [] : (function_exists('frontend_volna_mista_branch_cities_by_stredisko') ? frontend_volna_mista_branch_cities_by_stredisko($lang) : []);
$heroTitle = function_exists('stat_text') ? (stat_text('kariera', $lang, 'nazev') ?? '') : '';
$heroBody = function_exists('stat_text') ? (stat_text('kariera', $lang) ?? '') : '';
if ($heroTitle === '') {
    $heroTitle = ui_text('kariera.hero_title', 'Kariéra v Qanto');
}
$processText1 = function_exists('frontend_volna_mista_text') ? frontend_volna_mista_text('kariera.process.text_1', $lang, '') : '';
$processText2 = function_exists('frontend_volna_mista_text') ? frontend_volna_mista_text('kariera.process.text_2', $lang, '') : '';
$mapBranchesJson = htmlspecialchars(
    json_encode($mapBranches, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT),
    ENT_QUOTES,
    'UTF-8'
);
?>
<section class="career-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= htmlspecialchars(ui_text('aria.breadcrumb', 'Drobečková navigace'), ENT_QUOTES, 'UTF-8') ?>">
            <ol>
                <li>
                    <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ui_text('aria.home', 'Domů'), ENT_QUOTES, 'UTF-8') ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li>
                    <?php if ($jobDetail): ?>
                        <a href="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/kariera"><?= htmlspecialchars(ui_text('kariera.breadcrumb', 'Kariéra'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= htmlspecialchars(ui_text('kariera.breadcrumb', 'Kariéra'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </li>
                <?php if ($jobDetail): ?>
                    <li><span aria-current="page"><?= htmlspecialchars((string)$jobDetail['title'], ENT_QUOTES, 'UTF-8') ?></span></li>
                <?php endif; ?>
            </ol>
        </nav>

        <?php if ($jobDetail): ?>
            <?php
            $jobDetailAddress = (string)$jobDetail['address'];
            $jobDetailLocation = (string)$jobDetail['location'] . ($jobDetailAddress !== '' ? ' - ' . $jobDetailAddress : '');
            ?>
            <div class="detail-page-layout career-detail-layout">
                <article class="career-detail detail-page-content">
                    <span class="career-detail__label"><?= htmlspecialchars(ui_text('kariera.position_label', 'Volná pozice'), ENT_QUOTES, 'UTF-8') ?></span>
                    <h1><?= htmlspecialchars((string)$jobDetail['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="career-detail__location"><?= htmlspecialchars($jobDetailLocation, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ((int)$jobDetail['count'] > 0): ?>
                        <p class="career-detail__count"><?= htmlspecialchars((int)$jobDetail['count'] === 1 ? ui_text('kariera.job_count_one', '1 místo') : sprintf(ui_text('kariera.job_count', '%d míst'), (int)$jobDetail['count']), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="career-detail__content">
                        <?= (string)$jobDetail['content'] !== '' ? (string)$jobDetail['content'] : '<p>' . htmlspecialchars(ui_text('common.text_unavailable', 'Text není aktuálně dostupný.'), ENT_QUOTES, 'UTF-8') . '</p>' ?>
                    </div>
                    <?php if ((string)$jobDetail['contact_name'] !== '' || (string)$jobDetail['contact_email'] !== '' || (string)$jobDetail['contact_phone'] !== ''): ?>
                        <div class="career-detail__contact">
                            <strong><?= htmlspecialchars(ui_text('kariera.contact_title', 'Kontakt'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ((string)$jobDetail['contact_name'] !== ''): ?><span><?= htmlspecialchars((string)$jobDetail['contact_name'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            <?php if ((string)$jobDetail['contact_email'] !== ''): ?><a href="mailto:<?= htmlspecialchars((string)$jobDetail['contact_email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$jobDetail['contact_email'], ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
                            <?php if ((string)$jobDetail['contact_phone'] !== ''): ?><a href="tel:<?= htmlspecialchars(preg_replace('~\s+~', '', (string)$jobDetail['contact_phone']) ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$jobDetail['contact_phone'], ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <section class="career-application" id="dotaznik">
                        <div class="career-application__head">
                            <span><?= htmlspecialchars(ui_text('kariera.application_label', 'Online dotazník'), ENT_QUOTES, 'UTF-8') ?></span>
                            <h2><?= htmlspecialchars(ui_text('kariera.application_title', 'Mám zájem o pozici'), ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars(ui_text('kariera.application_intro', 'Vyplňte kontaktní údaje a krátký dotazník. Pokud máte životopis nebo motivační dopis, můžete ho přiložit jako PDF nebo Word.'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <?php if (is_array($applicationResult)): ?>
                            <div class="career-application__message <?= $applicationResult['ok'] ? 'career-application__message--success' : 'career-application__message--error' ?>">
                                <?= htmlspecialchars((string)$applicationResult['message'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <form class="career-application__form" method="post" enctype="multipart/form-data" action="/<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/kariera?pozice=<?= (int)$jobDetail['id'] ?>#dotaznik">
                            <input type="hidden" name="career_application" value="1">
                            <input type="hidden" name="volne_misto_id" value="<?= (int)$jobDetail['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($applicationCsrf, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="career-application__grid">
                                <?php foreach ($applicationInputFields as $field): ?>
                                    <label class="career-application__field">
                                        <span><?= htmlspecialchars((string)$field['label'], ENT_QUOTES, 'UTF-8') ?><?= !empty($field['required']) ? ' *' : '' ?></span>
                                        <input type="<?= htmlspecialchars((string)$field['type'], ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars((string)$field['name'], ENT_QUOTES, 'UTF-8') ?>" value="<?= frontend_volna_mista_form_value($applicationValues, (string)$field['name']) ?>"<?= !empty($field['required']) ? ' required' : '' ?>>
                                    </label>
                                <?php endforeach; ?>
                                <label class="career-application__field">
                                    <span><?= htmlspecialchars(ui_text('kariera.application.smoking', 'Kouření'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <select name="dot_koureni">
                                        <option value=""><?= htmlspecialchars(ui_text('common.select_empty', 'Vyberte'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="<?= htmlspecialchars(ui_text('common.yes', 'Ano'), ENT_QUOTES, 'UTF-8') ?>"<?= ($applicationValues['dot_koureni'] ?? '') === ui_text('common.yes', 'Ano') ? ' selected' : '' ?>><?= htmlspecialchars(ui_text('common.yes', 'Ano'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="<?= htmlspecialchars(ui_text('common.no', 'Ne'), ENT_QUOTES, 'UTF-8') ?>"<?= ($applicationValues['dot_koureni'] ?? '') === ui_text('common.no', 'Ne') ? ' selected' : '' ?>><?= htmlspecialchars(ui_text('common.no', 'Ne'), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                </label>
                                <label class="career-application__field">
                                    <span><?= htmlspecialchars(ui_text('kariera.application.health', 'Zdravotní stav'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <input type="text" name="dot_zdravstav" value="<?= frontend_volna_mista_form_value($applicationValues, 'dot_zdravstav') ?>">
                                </label>
                            </div>
                            <div class="career-application__areas">
                                <?php foreach ($applicationTextareaFields as $field): ?>
                                    <label class="career-application__field career-application__field--wide">
                                        <span><?= htmlspecialchars((string)$field['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <textarea name="<?= htmlspecialchars((string)$field['name'], ENT_QUOTES, 'UTF-8') ?>" rows="3"><?= frontend_volna_mista_form_value($applicationValues, (string)$field['name']) ?></textarea>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <label class="career-application__upload">
                                <span><?= htmlspecialchars(ui_text('kariera.application.attachment', 'Zde můžete nahrát životopis, motivační dopis'), ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="file" name="dot_priloha[]" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple>
                                <small><?= htmlspecialchars(ui_text('kariera.application.attachment_help', 'Povolené typy: PDF, DOC, DOCX. Maximální velikost 10 MB.'), ENT_QUOTES, 'UTF-8') ?></small>
                            </label>
                            <?php if (function_exists('frontend_captcha_render')): ?>
                                <?php frontend_captcha_render('career_application', 'career-application'); ?>
                            <?php endif; ?>
                            <button class="career-application__submit" type="submit"><?= htmlspecialchars(ui_text('kariera.application.submit', 'Odeslat dotazník'), ENT_QUOTES, 'UTF-8') ?></button>
                        </form>
                    </section>
                </article>
                <?php include __DIR__ . '/detail_sidebar.php'; ?>
            </div>
        <?php else: ?>
        <div class="news-page__head career-page__head">
            <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        </div>

        <div class="career-hero">
            <div class="career-hero__copy">
                <?php if ($heroBody !== ''): ?>
                    <div class="career-hero__text"><?= $heroBody ?></div>
                <?php endif; ?>
            </div>
            <div class="career-video" aria-label="<?= htmlspecialchars(ui_text('kariera.video_label', 'Představujeme Qanto'), ENT_QUOTES, 'UTF-8') ?>">
                <img src="/img/design/career/hero-market.webp" width="1200" height="667" alt="<?= htmlspecialchars(ui_text('kariera.video_label', 'Představujeme Qanto'), ENT_QUOTES, 'UTF-8') ?>" loading="eager">
            </div>
        </div>

        <div class="career-jobs" id="volna-mista">
            <div class="career-jobs__layout">
                <div class="career-list">
                    <?php if ($jobs === []): ?>
                        <div class="career-list__empty"><?= htmlspecialchars(ui_text('kariera.no_jobs', 'Aktuálně zde nejsou žádná volná místa.'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <?php
                            $jobCity = $jobCityByStredisko[(int)($job['stredisko_kod'] ?? 0)] ?? '';
                            $jobStredisko = (int)($job['stredisko_kod'] ?? 0);
                            $jobLocationParts = array_values(array_filter([$jobCity, (string)$job['location']], static fn(string $part): bool => trim($part) !== ''));
                            $jobLocationLabel = implode(' - ', $jobLocationParts);
                            ?>
                            <article
                                class="career-card"
                                id="kariera-pozice-<?= (int)$job['id'] ?>"
                                data-career-job-card
                                data-city="<?= htmlspecialchars($jobCity, ENT_QUOTES, 'UTF-8') ?>"
                                data-stredisko="<?= $jobStredisko ?>"
                            >
                                <a class="career-card__link" href="<?= htmlspecialchars((string)$job['url'], ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="career-card__body">
                                        <h3><?= htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p><?= htmlspecialchars($jobLocationLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                    </span>
                                    <span class="career-card__arrow" aria-hidden="true">›</span>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="career-list__empty" data-career-jobs-empty hidden><?= htmlspecialchars(ui_text('kariera.jobs_filter_empty', 'Pro vybrané město nejsou dostupné žádné pozice.'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="career-map" aria-label="<?= htmlspecialchars(ui_text('kariera.map_label', 'Mapa poboček a volných míst'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="career-map__toolbar" data-career-map-controls>
                        <button type="button" class="career-map__toggle" data-career-map-jobs-only>
                            <?= htmlspecialchars(ui_text('kariera.map_only_jobs', 'Jen volná místa'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <div class="career-map-city" data-career-map-city-picker>
                            <button
                                type="button"
                                class="career-map-city__trigger"
                                data-career-map-city-trigger
                                aria-expanded="false"
                                aria-haspopup="listbox"
                            >
                                <span>
                                    <small><?= htmlspecialchars(ui_text('kariera.map_city', 'Město'), ENT_QUOTES, 'UTF-8') ?></small>
                                    <strong data-career-map-city-label><?= htmlspecialchars(ui_text('kariera.map_all_cities', 'Celá ČR'), ENT_QUOTES, 'UTF-8') ?></strong>
                                </span>
                            </button>
                            <div class="career-map-city__panel" data-career-map-city-panel role="listbox" hidden></div>
                        </div>
                        <button type="button" class="career-map__reset" data-career-map-reset hidden>
                            <?= htmlspecialchars(ui_text('kariera.reset_filter', 'Zrušit filtr'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                    <div
                        class="career-map__canvas"
                        data-career-branch-map
                        data-points="<?= $mapBranchesJson ?>"
                        data-lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
                        data-label-branch="<?= htmlspecialchars(ui_text('kariera.map_branch', 'Pobočka'), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-jobs="<?= htmlspecialchars(ui_text('kariera.map_open_jobs', 'Volná místa'), ENT_QUOTES, 'UTF-8') ?>"
                        data-empty="<?= htmlspecialchars(ui_text('kariera.map_no_gps', 'Pro mapu nejsou dostupné souřadnice poboček.'), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-all-cities="<?= htmlspecialchars(ui_text('kariera.map_all_cities', 'Celá ČR'), ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-empty="<?= htmlspecialchars(ui_text('kariera.map_filter_empty', 'Pro vybraný filtr nejsou dostupné žádné pobočky.'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <div class="career-map__empty"><?= htmlspecialchars(ui_text('kariera.map_no_gps', 'Pro mapu nejsou dostupné souřadnice poboček.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="career-map__filter-empty" data-career-map-filter-empty hidden>
                        <?= htmlspecialchars(ui_text('kariera.map_filter_empty', 'Pro vybraný filtr nejsou dostupné žádné pobočky.'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="career-map__legend" aria-hidden="true">
                        <span><i class="career-map__legend-dot career-map__legend-dot--branch"></i><?= htmlspecialchars(ui_text('kariera.map_legend_branches', 'pobočky'), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><i class="career-map__legend-dot career-map__legend-dot--jobs"></i><?= htmlspecialchars(ui_text('kariera.map_legend_jobs', 'volná místa'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="career-process">
            <h2><?= htmlspecialchars(ui_text('kariera.process_title', 'Jak to u nás chodí'), ENT_QUOTES, 'UTF-8') ?></h2>
            <div>
                <?php if ($processText1 !== ''): ?><p><?= htmlspecialchars($processText1, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($processText2 !== ''): ?><p><?= htmlspecialchars($processText2, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
