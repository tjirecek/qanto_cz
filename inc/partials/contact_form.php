<?php
declare(strict_types=1);

$lang = (string)($lang ?? 'cz');
$contactFormCategories = is_array($contactFormCategories ?? null) ? $contactFormCategories : [];
$contactFormResult = $contactFormResult ?? null;
$contactFormToken = (string)($contactFormToken ?? '');
$selectedContactCategory = (int)($selectedContactCategory ?? 0);
$contactFormValue = is_callable($contactFormValue ?? null)
    ? $contactFormValue
    : static fn (string $key): string => '';
?>
<section class="contacts-form-section" id="kontaktni-formular" aria-labelledby="contacts-form-title">
    <div class="contacts-form-section__intro">
        <span class="contacts-form-section__icon" aria-hidden="true">
            <span class="contacts-icon contacts-icon--mail"></span>
        </span>
        <h2 id="contacts-form-title"><?= frontend_contacts_e(ui_text('contacts.form_title')) ?></h2>
        <p><?= frontend_contacts_e(stat_vyraz_text('contacts.form_text')) ?></p>
        <div class="contacts-form-section__illustration" aria-hidden="true">
            <img src="/img/design/contact-question.png" alt="" loading="lazy">
        </div>
    </div>
    <div class="contacts-form-card">
        <?php if (is_array($contactFormResult)): ?>
            <div class="contacts-form-card__message <?= (bool)($contactFormResult['ok'] ?? false) ? 'is-success' : 'is-error' ?>" role="status">
                <?= frontend_contacts_e((string)($contactFormResult['message'] ?? '')) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="#kontaktni-formular" class="contacts-form">
            <input type="hidden" name="action" value="contacts_form">
            <input type="hidden" name="csrf_token" value="<?= frontend_contacts_e($contactFormToken) ?>">

            <div class="contacts-form__field contacts-form__field--select" data-contact-select>
                <label for="contacts_category"><?= frontend_contacts_e(ui_text('contacts.form_category')) ?> *</label>
                <select name="category_id" id="contacts_category" required data-contact-select-native>
                    <option value=""><?= frontend_contacts_e(ui_text('common.select_empty')) ?></option>
                    <?php foreach ($contactFormCategories as $category): ?>
                        <?php $categoryId = (int)$category['id']; ?>
                        <option value="<?= $categoryId ?>" <?= $selectedContactCategory === $categoryId ? 'selected' : '' ?>>
                            <?= frontend_contacts_e((string)$category['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="contacts-custom-select" data-contact-select-ui>
                    <button type="button" class="contacts-custom-select__trigger" data-contact-select-trigger aria-haspopup="listbox" aria-expanded="false">
                        <span data-contact-select-label><?= frontend_contacts_e(ui_text('common.select_empty')) ?></span>
                    </button>
                    <div class="contacts-custom-select__panel" data-contact-select-panel role="listbox" hidden>
                        <button type="button" class="contacts-custom-select__option" data-contact-select-option data-value="" role="option" aria-selected="<?= $selectedContactCategory === 0 ? 'true' : 'false' ?>">
                            <?= frontend_contacts_e(ui_text('common.select_empty')) ?>
                        </button>
                        <?php foreach ($contactFormCategories as $category): ?>
                            <?php $categoryId = (int)$category['id']; ?>
                            <button type="button" class="contacts-custom-select__option" data-contact-select-option data-value="<?= $categoryId ?>" role="option" aria-selected="<?= $selectedContactCategory === $categoryId ? 'true' : 'false' ?>">
                                <?= frontend_contacts_e((string)$category['label']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="contacts-form__field">
                <label for="contacts_name"><?= frontend_contacts_e(ui_text('contacts.form_name')) ?> *</label>
                <input type="text" name="name" id="contacts_name" autocomplete="name" required placeholder="<?= frontend_contacts_e(ui_text('contacts.form_name_placeholder')) ?>" value="<?= frontend_contacts_e($contactFormValue('name')) ?>">
            </div>

            <div class="contacts-form__field">
                <label for="contacts_email"><?= frontend_contacts_e(ui_text('contacts.form_email')) ?> *</label>
                <input type="email" name="email" id="contacts_email" autocomplete="email" required placeholder="<?= frontend_contacts_e(ui_text('contacts.form_email_placeholder')) ?>" value="<?= frontend_contacts_e($contactFormValue('email')) ?>">
            </div>

            <div class="contacts-form__field">
                <label for="contacts_phone"><?= frontend_contacts_e(ui_text('contacts.form_phone')) ?></label>
                <input type="tel" name="phone" id="contacts_phone" autocomplete="tel" placeholder="<?= frontend_contacts_e(ui_text('contacts.form_phone_placeholder')) ?>" value="<?= frontend_contacts_e($contactFormValue('phone')) ?>">
            </div>

            <div class="contacts-form__field">
                <label for="contacts_message"><?= frontend_contacts_e(ui_text('contacts.form_message')) ?> *</label>
                <textarea name="message" id="contacts_message" rows="5" required placeholder="<?= frontend_contacts_e(ui_text('contacts.form_message_placeholder')) ?>"><?= frontend_contacts_e($contactFormValue('message')) ?></textarea>
            </div>

            <?php if (function_exists('frontend_captcha_render')): ?>
                <?php frontend_captcha_render('contacts_form', 'contacts-form'); ?>
            <?php endif; ?>

            <button type="submit"><?= frontend_contacts_e(ui_text('contacts.form_submit')) ?></button>

            <p class="contacts-form__consent">
                <?= frontend_contacts_e(ui_text('contacts.form_consent')) ?>
                <a href="/<?= frontend_contacts_e($lang) ?>/osobni-udaje"><?= frontend_contacts_e(ui_text('contacts.form_privacy_link')) ?></a>.
            </p>
        </form>
    </div>
</section>
