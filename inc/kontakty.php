<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$branches = function_exists('frontend_contacts_wholesale_branches') ? frontend_contacts_wholesale_branches($lang) : [];
$peopleGroups = function_exists('frontend_contacts_people_groups') ? frontend_contacts_people_groups($lang) : [];
$contactFormCategories = function_exists('frontend_contacts_form_categories') ? frontend_contacts_form_categories($lang) : [];
$contactFormResult = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && (string)($_POST['action'] ?? '') === 'contacts_form'
    && function_exists('frontend_contacts_form_save')
) {
    $contactFormResult = frontend_contacts_form_save($_POST);
}

$contactFormToken = function_exists('frontend_contacts_form_token') ? frontend_contacts_form_token() : '';
$keepContactFormValues = is_array($contactFormResult) && !(bool)($contactFormResult['ok'] ?? false);
$contactFormValue = static function (string $key) use ($keepContactFormValues): string {
    return $keepContactFormValues ? (string)($_POST[$key] ?? '') : '';
};
$selectedContactCategory = $keepContactFormValues ? (int)($_POST['category_id'] ?? 0) : 0;

$companyName = frontend_contacts_expr('contact.company.name', $lang);
$companyAddress = frontend_contacts_expr('contact.company.address', $lang);
$companyVat = frontend_contacts_expr('contact.company.vat', $lang);
$companyId = frontend_contacts_expr('contact.company.id', $lang);
$companyNote = frontend_contacts_expr('contact.company.note', $lang);
$adminLabel = frontend_contacts_expr('contact.admin.label', $lang);
$adminAddress = frontend_contacts_expr('contact.admin.address', $lang);
$adminEmail = frontend_contacts_expr('contact.admin.email', $lang);
?>
<section class="contacts-page">
    <div class="site-shell">
        <nav class="site-breadcrumb" aria-label="<?= frontend_contacts_e(ui_text('aria.breadcrumb')) ?>">
            <ol>
                <li>
                    <a href="/<?= frontend_contacts_e($lang) ?>" aria-label="<?= frontend_contacts_e(ui_text('aria.breadcrumb_home')) ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3.2 3.8 8.3v7.4h4.1v-4.6h4.2v4.6h4.1V8.3L10 3.2Zm0-2.1 8 6.6v9.6h-7.5v-4.6h-1v4.6H2V7.7l8-6.6Z"/></svg>
                    </a>
                </li>
                <li><span aria-current="page"><?= frontend_contacts_e(ui_text('contacts.title')) ?></span></li>
            </ol>
        </nav>

        <header class="contacts-page__head">
            <h1><?= frontend_contacts_e(ui_text('contacts.title')) ?></h1>
            <div class="contacts-page__quick">
                <?php if ($branches !== []): ?>
                    <div class="contacts-page__quick-block">
                        <strong><?= frontend_contacts_e(ui_text('contacts.jump_wholesale')) ?></strong>
                        <div>
                            <?php foreach ($branches as $branch): ?>
                                <a href="#<?= frontend_contacts_e((string)$branch['anchor']) ?>"><?= frontend_contacts_e((string)$branch['short_name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="contacts-page__quick-block">
                    <strong><?= frontend_contacts_e(ui_text('contacts.contact_us')) ?></strong>
                    <div><a href="#kontaktni-formular"><?= frontend_contacts_e(ui_text('contacts.form_link')) ?></a></div>
                </div>
            </div>
        </header>

        <section class="contacts-company" aria-label="<?= frontend_contacts_e(ui_text('contacts.company_section')) ?>">
            <article class="contacts-company__block">
                <h2><?= frontend_contacts_e(ui_text('contacts.company_contact')) ?></h2>
                <address>
                    <strong><?= frontend_contacts_e($companyName) ?></strong><br>
                    <?= frontend_contacts_e(ui_text('contacts.company_address_label')) ?>: <?= frontend_contacts_e($companyAddress) ?><br>
                    <?= frontend_contacts_e(ui_text('contacts.company_vat_label')) ?>: <?= frontend_contacts_e($companyVat) ?><br>
                    <?= frontend_contacts_e(ui_text('contacts.company_id_label')) ?>: <?= frontend_contacts_e($companyId) ?>
                </address>
                <?php if ($companyNote !== ''): ?>
                    <p><?= frontend_contacts_e($companyNote) ?></p>
                <?php endif; ?>
            </article>

            <article class="contacts-company__block contacts-company__block--admin">
                <h2><?= frontend_contacts_e(ui_text('contacts.admin_contact')) ?></h2>
                <div class="contacts-admin-card">
                    <div class="contacts-admin-card__logo" aria-hidden="true"><?= frontend_contacts_e($adminLabel) ?></div>
                    <div class="contacts-admin-card__links">
                        <?php if ($adminAddress !== ''): ?>
                            <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($adminAddress) ?>" target="_blank" rel="noopener">
                                <span class="contacts-icon contacts-icon--pin" aria-hidden="true"></span><?= frontend_contacts_e($adminAddress) ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($adminEmail !== ''): ?>
                            <a href="mailto:<?= frontend_contacts_e($adminEmail) ?>">
                                <span class="contacts-icon contacts-icon--mail" aria-hidden="true"></span><?= frontend_contacts_e($adminEmail) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </section>

        <?php if ($peopleGroups !== []): ?>
            <section class="contacts-people">
                <h2><?= frontend_contacts_e(ui_text('contacts.people_title')) ?></h2>
                <?php foreach ($peopleGroups as $group): ?>
                    <?php if (empty($group['people'])) continue; ?>
                    <section class="contacts-people__group">
                        <h3><?= frontend_contacts_e((string)$group['label']) ?></h3>
                        <div class="contacts-people__grid">
                            <?php foreach ($group['people'] as $person): ?>
                                <article class="contacts-person">
                                    <?php if ((string)$person['image'] !== ''): ?>
                                        <img src="<?= frontend_contacts_e((string)$person['image']) ?>" alt="<?= frontend_contacts_e((string)$person['name']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="contacts-person__initial" aria-hidden="true"><?= frontend_contacts_e(mb_strtoupper(mb_substr((string)$person['name'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                                    <?php endif; ?>
                                    <div class="contacts-person__body">
                                        <h4><?= frontend_contacts_e((string)$person['name']) ?></h4>
                                        <?php if ((string)$person['role'] !== ''): ?><p class="contacts-person__role"><?= frontend_contacts_e((string)$person['role']) ?></p><?php endif; ?>
                                        <?php if ((string)$person['description'] !== ''): ?><div class="contacts-person__description"><?= (string)$person['description'] ?></div><?php endif; ?>
                                        <div class="contacts-person__links">
                                            <?php if ((string)$person['phone'] !== ''): ?>
                                                <a href="tel:<?= frontend_contacts_e(preg_replace('~\s+~', '', (string)$person['phone']) ?? '') ?>">
                                                    <svg class="contacts-person__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.1 3.1 10 8.8 7.8 11c1.1 2.2 3 4.1 5.2 5.2l2.2-2.2 5.7 2.9-.9 4.5-1 .2c-.7.1-1.4.2-2 .2C8.9 21.8 2.2 15.1 2.2 7c0-.7.1-1.4.2-2l.2-1 4.5-.9Zm-.9 2.5-1.5.3v1.2c0 6.9 5.5 12.4 12.3 12.4h1.1l.3-1.5-2.7-1.4-2.2 2.2-.7-.3A13.2 13.2 0 0 1 5.5 11l-.3-.7 2.2-2.2-1.2-2.5Z"/></svg>
                                                    <?= frontend_contacts_e((string)$person['phone']) ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ((string)$person['email'] !== ''): ?>
                                                <a href="mailto:<?= frontend_contacts_e((string)$person['email']) ?>">
                                                    <svg class="contacts-person__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 5h18v14H3V5Zm2.4 2 6.6 5.1L18.6 7H5.4Zm13.2 10V9.6L12 14.7 5.4 9.6V17h13.2Z"/></svg>
                                                    <?= frontend_contacts_e((string)$person['email']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($branches !== []): ?>
            <section class="contacts-branches">
                <h2><?= frontend_contacts_e(ui_text('contacts.wholesale_title')) ?></h2>
                <div class="contacts-branches__grid">
                    <?php foreach ($branches as $branch): ?>
                        <article class="contacts-branch" id="<?= frontend_contacts_e((string)$branch['anchor']) ?>">
                            <h3><?= frontend_contacts_e((string)$branch['name']) ?></h3>
                            <?php if ((string)$branch['address'] !== ''): ?><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode((string)$branch['address']) ?>" target="_blank" rel="noopener"><span class="contacts-icon contacts-icon--pin" aria-hidden="true"></span><?= frontend_contacts_e((string)$branch['address']) ?></a><?php endif; ?>
                            <?php if ((string)$branch['phone'] !== ''): ?><a href="tel:<?= frontend_contacts_e(preg_replace('~[^0-9+]+~', '', (string)$branch['phone']) ?? '') ?>"><span class="contacts-icon contacts-icon--phone" aria-hidden="true"></span><?= frontend_contacts_e((string)$branch['phone']) ?></a><?php endif; ?>
                            <?php if ((string)$branch['email'] !== ''): ?><a href="mailto:<?= frontend_contacts_e((string)$branch['email']) ?>"><span class="contacts-icon contacts-icon--mail" aria-hidden="true"></span><?= frontend_contacts_e((string)$branch['email']) ?></a><?php endif; ?>
                            <?php if ((string)$branch['manager'] !== ''): ?><p><?= frontend_contacts_e(ui_text('contacts.branch_manager')) ?>: <?= frontend_contacts_e((string)$branch['manager']) ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php include __DIR__ . '/partials/contact_form.php'; ?>
    </div>
</section>
