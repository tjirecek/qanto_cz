<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_changelog.php';

function changelog_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$messages = [];
$show = (int)($_GET['show'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);
$detailId = (int)($_GET['detail'] ?? 0);
$sendId = isset($_GET['send']) ? (int)$_GET['send'] : (int)($_POST['send'] ?? 0);
$categoryEditId = (int)($_GET['category_edit'] ?? 0);
$form = changelog_default();
$categoryForm = changelog_category_default();
$editing = false;
$categoryEditing = false;
$detailRow = null;
$sendRow = null;
$sendResult = null;
$sendSelectedGroups = [];
$tableExists = changelog_table_exists();
$categoryTableExists = changelog_category_table_exists();
$newsLinkAvailable = $tableExists && changelog_news_link_available();

$sendCsrfToken = (string)admin_session_get('changelog_send_csrf_token', '');
if ($sendCsrfToken === '') {
    $sendCsrfToken = bin2hex(random_bytes(16));
    admin_session_set('changelog_send_csrf_token', $sendCsrfToken);
}

if (!$tableExists) {
    $messages[] = [
        'type' => 'warning',
        'text' => 'Tabulka changelog zatím neexistuje. Nejdříve spusť odpovídající aktivní migraci v secure/sql/.',
    ];
} elseif (!$newsLinkAvailable) {
    $messages[] = [
        'type' => 'info',
        'text' => 'Vazba ChangeLogu na novinky zatím není aktivní. Spusť migraci pro sloupec changelog.news_id.',
    ];
}
if ($tableExists && !$categoryTableExists) {
    $messages[] = [
        'type' => 'info',
        'text' => 'Kategorie ChangeLogu zatím běží z výchozího fallbacku. Spusť migraci pro tabulku changelog_cat.',
    ];
}

if ($tableExists) {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        $action = trim((string)($_POST['action'] ?? ''));

        if ($action === 'category_archive') {
            $archiveId = (int)($_POST['id'] ?? 0);
            if ($archiveId > 0) {
                try {
                    changelog_category_archive($archiveId);
                    $messages[] = ['type' => 'success', 'text' => 'Kategorie byla skryta z aktivního výběru.'];
                    $show = 3;
                } catch (Throwable $e) {
                    $messages[] = ['type' => 'danger', 'text' => 'Kategorii se nepodařilo skrýt: ' . $e->getMessage()];
                    $show = 3;
                }
            }
        }

        if ($action === 'category_create' || $action === 'category_update') {
            $postId = (int)($_POST['id'] ?? 0);
            $base = changelog_category_default();
            if ($action === 'category_update' && $postId > 0) {
                $existingCategory = changelog_category_fetch($postId);
                if ($existingCategory !== null) {
                    $base = array_merge($base, $existingCategory);
                }
            }

            $categoryForm = changelog_category_from_request($_POST, $base);
            [$categoryErrors, $normalizedCategory] = changelog_category_validate($categoryForm, $postId);
            $categoryForm = $normalizedCategory;

            if ($categoryErrors) {
                foreach ($categoryErrors as $error) {
                    $messages[] = ['type' => 'warning', 'text' => $error];
                }
                $show = 3;
                $categoryEditing = $action === 'category_update';
                $categoryEditId = $postId;
            } else {
                try {
                    if ($action === 'category_create') {
                        changelog_category_create($categoryForm);
                        $messages[] = ['type' => 'success', 'text' => 'Kategorie byla založena.'];
                        $categoryForm = changelog_category_default();
                    } else {
                        changelog_category_update($postId, $categoryForm);
                        $messages[] = ['type' => 'success', 'text' => 'Kategorie byla uložena.'];
                        $existingCategory = changelog_category_fetch($postId);
                        if ($existingCategory !== null) {
                            $categoryForm = array_merge(changelog_category_default(), $existingCategory);
                            $categoryEditing = true;
                            $categoryEditId = $postId;
                        }
                    }
                    $show = 3;
                } catch (Throwable $e) {
                    $messages[] = ['type' => 'danger', 'text' => 'Kategorii se nepodařilo uložit: ' . $e->getMessage()];
                    $show = 3;
                    $categoryEditing = $action === 'category_update';
                    $categoryEditId = $postId;
                }
            }
        }

        if ($action === 'archive') {
            $archiveId = (int)($_POST['id'] ?? 0);
            if ($archiveId > 0) {
                try {
                    changelog_archive($archiveId);
                    $messages[] = ['type' => 'success', 'text' => 'Změna byla skryta z aktivní evidence.'];
                } catch (Throwable $e) {
                    $messages[] = ['type' => 'danger', 'text' => 'Změnu se nepodařilo skrýt: ' . $e->getMessage()];
                }
            }
        }

        if ($action === 'send_email') {
            $postId = (int)($_POST['send'] ?? 0);
            $sendSelectedGroups = changelog_email_selected_groups((array)($_POST['group_keys'] ?? []));
            $show = 5;
            $sendId = $postId;

            try {
                if (!hash_equals($sendCsrfToken, (string)($_POST['csrf_token'] ?? ''))) {
                    throw new RuntimeException('Neplatný bezpečnostní token formuláře.');
                }

                $sendResult = changelog_email_send($postId, $sendSelectedGroups);
                $messages[] = [
                    'type' => 'success',
                    'text' => 'ChangeLog byl odeslán: ' . (int)$sendResult['sent'] . ' úspěšně, '
                        . (int)$sendResult['failed'] . ' chyb.',
                ];
            } catch (Throwable $e) {
                $messages[] = ['type' => 'danger', 'text' => 'Odeslání se nepodařilo: ' . $e->getMessage()];
            }
        }

        if ($action === 'create' || $action === 'update') {
            $postId = (int)($_POST['id'] ?? 0);
            $base = changelog_default();
            if ($action === 'update' && $postId > 0) {
                $existing = changelog_fetch($postId);
                if ($existing !== null) {
                    $base = array_merge($base, $existing);
                }
            }

            $form = changelog_from_request($_POST, $base);
            [$errors, $normalized] = changelog_validate($form);
            $form = $normalized;

            if ($errors) {
                foreach ($errors as $error) {
                    $messages[] = ['type' => 'warning', 'text' => $error];
                }
                $show = $action === 'update' ? 2 : 1;
                $editing = $action === 'update';
                $editId = $postId;
            } else {
                try {
                    if ($action === 'create') {
                        changelog_create($form);
                        $messages[] = ['type' => 'success', 'text' => 'Změna byla zaevidována.'];
                        $form = changelog_default();
                        $show = 0;
                    } else {
                        changelog_update($postId, $form);
                        $messages[] = ['type' => 'success', 'text' => 'Změna byla uložena.'];
                        $existing = changelog_fetch($postId);
                        if ($existing !== null) {
                            $form = array_merge(changelog_default(), $existing);
                            $editing = true;
                            $show = 2;
                            $editId = $postId;
                        }
                    }
                } catch (Throwable $e) {
                    $messages[] = ['type' => 'danger', 'text' => 'Změnu se nepodařilo uložit: ' . $e->getMessage()];
                    $show = $action === 'update' ? 2 : 1;
                    $editing = $action === 'update';
                    $editId = $postId;
                }
            }
        }
    }

    if ($show === 5 || $sendId > 0) {
        $show = 5;
        $sendRow = $sendId > 0 ? changelog_fetch($sendId) : null;
        if ($sendRow === null) {
            $messages[] = ['type' => 'warning', 'text' => 'Požadovaná změna pro odeslání neexistuje.'];
            $show = 0;
            $sendId = 0;
        } elseif ((string)($sendRow['status'] ?? '') !== 'nasazeno') {
            $messages[] = ['type' => 'warning', 'text' => 'E-mailem lze odeslat pouze změnu ve stavu Nasazeno.'];
        }
    }

    if ($show === 2 && $editId > 0 && !$editing) {
        $existing = changelog_fetch($editId);
        if ($existing === null) {
            $messages[] = ['type' => 'warning', 'text' => 'Požadovaná změna neexistuje.'];
            $show = 0;
        } else {
            $form = array_merge(changelog_default(), $existing);
            $editing = true;
        }
    }

    if ($show === 4) {
        $detailRow = $detailId > 0 ? changelog_fetch($detailId) : null;
        if ($detailRow === null) {
            $messages[] = ['type' => 'warning', 'text' => 'Požadovaná změna neexistuje.'];
            $show = 0;
        }
    }

    if ($show === 3 && $categoryEditId > 0 && !$categoryEditing) {
        $existingCategory = changelog_category_fetch($categoryEditId);
        if ($existingCategory === null) {
            $messages[] = ['type' => 'warning', 'text' => 'Požadovaná kategorie neexistuje.'];
            $categoryEditId = 0;
        } else {
            $categoryForm = array_merge(changelog_category_default(), $existingCategory);
            $categoryEditing = true;
        }
    }
}

$rows = $tableExists ? changelog_list(false) : [];
$statuses = changelog_statuses();
$categories = changelog_categories();
$categoryRows = changelog_category_rows(false);
$categoryBadgeOptions = changelog_category_badge_options();
$currentYear = (int)date('Y');
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">ChangeLog</h1>

    <div class="d-flex flex-wrap gap-2">
        <?php if ($tableExists): ?>
            <a href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=1"
               class="btn btn-sm btn-primary shadow-sm">
                Přidat změnu <i class="bi bi-plus-circle"></i>
            </a>
            <a href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=3"
               class="btn btn-sm btn-outline-primary shadow-sm">
                Kategorie <i class="bi bi-tags"></i>
            </a>
        <?php endif; ?>
        <span class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm">
            aktivních: <?= (int)count($rows) ?>
        </span>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-<?= changelog_e((string)$message['type']) ?> py-2 mb-2">
        <?= changelog_e((string)$message['text']) ?>
    </div>
<?php endforeach; ?>

<?php if ($tableExists && $show === 4 && is_array($detailRow)): ?>
    <?php
    $status = (string)($detailRow['status'] ?? '');
    $category = (string)($detailRow['category'] ?? '');
    $description = trim((string)($detailRow['description'] ?? ''));
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="small text-muted">Detail změny #<?= (int)$detailRow['id'] ?></div>
                <h6 class="m-0 fw-bold text-primary"><?= changelog_e((string)$detailRow['title']) ?></h6>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge <?= changelog_e(changelog_category_badge($category)) ?>">
                    <?= changelog_e(changelog_category_label($category)) ?>
                </span>
                <span class="badge <?= changelog_e(changelog_status_badge($status)) ?>">
                    <?= changelog_e(changelog_status_label($status)) ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <?php if ($description !== ''): ?>
                <div class="lead fs-6 mb-4"><?= nl2br(changelog_e($description)) ?></div>
            <?php else: ?>
                <div class="text-muted mb-4">Popis změny není vyplněný.</div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="small text-muted">Zaevidováno</div>
                    <div class="fw-semibold"><?= changelog_e((string)format_date_www((string)($detailRow['recorded_on'] ?? ''))) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Předpoklad nasazení</div>
                    <div class="fw-semibold"><?= changelog_e(changelog_planned_text($detailRow)) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Hotovo</div>
                    <div class="fw-semibold"><?= changelog_e((string)format_date_www((string)($detailRow['done_on'] ?? ''))) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Pořadí</div>
                    <div class="fw-semibold"><?= (int)($detailRow['priority'] ?? 0) ?></div>
                </div>
            </div>

            <div class="border rounded-3 p-3 bg-light">
                <div class="fw-semibold mb-2">Navázaná novinka</div>
                <?php if ((int)($detailRow['news_id'] ?? 0) > 0): ?>
                    <?php if (changelog_has_linked_news($detailRow)): ?>
                        <div class="fw-semibold">
                            #<?= (int)$detailRow['news_id'] ?> - <?= changelog_e(changelog_linked_news_title($detailRow)) ?>
                        </div>
                        <?php $newsPerex = changelog_linked_news_perex_html($detailRow); ?>
                        <?php $newsBody = changelog_linked_news_body_html($detailRow); ?>
                        <?php if ($newsPerex !== ''): ?>
                            <div class="mt-3 border-top pt-3 changelog-linked-news-perex">
                                <div class="small text-muted mb-1">Perex</div>
                                <?= $newsPerex ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($newsBody !== ''): ?>
                            <div class="mt-3 border-top pt-3 changelog-linked-news-body">
                                <div class="small text-muted mb-1">Tělo</div>
                                <?= $newsBody ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($newsPerex === '' && $newsBody === ''): ?>
                            <div class="small text-muted mt-2">Perex ani tělo navázané novinky nejsou vyplněné.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-danger">Novinka #<?= (int)$detailRow['news_id'] ?> nenalezena</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">Tato změna nemá navázanou novinku.</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap gap-2">
            <a href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=2&amp;edit=<?= (int)$detailRow['id'] ?>" class="btn btn-primary">
                Upravit změnu
            </a>
            <?php if ($status === 'nasazeno'): ?>
                <a href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=5&amp;send=<?= (int)$detailRow['id'] ?>" class="btn btn-success">
                    <i class="bi bi-envelope me-1"></i> Odeslat e-mailem
                </a>
            <?php endif; ?>
            <a href="<?= changelog_admin_list_url() ?>" class="btn btn-outline-secondary">Zpět na ChangeLog</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($tableExists && $show === 5 && is_array($sendRow)): ?>
    <?php
    $groupOptions = changelog_email_group_options();
    $groupOptionsBySource = [];
    foreach ($groupOptions as $groupOption) {
        $groupOptionsBySource[(string)$groupOption['source_label']][] = $groupOption;
    }
    $previewRecipients = $sendSelectedGroups !== [] ? changelog_email_recipients($sendSelectedGroups) : [];
    $emailPreviewHtml = '';
    $emailPreviewError = '';
    try {
        $emailPreviewHtml = changelog_email_body_html($sendRow, changelog_logo_preview_src());
    } catch (Throwable $e) {
        $emailPreviewError = $e->getMessage();
    }
    $sendAllowed = (string)($sendRow['status'] ?? '') === 'nasazeno';
    ?>
    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Rozeslání ChangeLogu e-mailem</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-4">Změna</dt>
                        <dd class="col-sm-8">#<?= (int)$sendRow['id'] ?> - <?= changelog_e((string)$sendRow['title']) ?></dd>

                        <dt class="col-sm-4">Stav</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?= changelog_e(changelog_status_badge((string)$sendRow['status'])) ?>">
                                <?= changelog_e(changelog_status_label((string)$sendRow['status'])) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Předmět</dt>
                        <dd class="col-sm-8"><?= changelog_e(changelog_email_subject($sendRow)) ?></dd>

                        <dt class="col-sm-4">Vybraní příjemci</dt>
                        <dd class="col-sm-8">
                            <?= (int)count($previewRecipients) ?> unikátních e-mailů
                            <?php if ($sendSelectedGroups === []): ?>
                                <div class="text-muted small">Po výběru skupin se příjemci ověří při odeslání.</div>
                            <?php endif; ?>
                        </dd>
                    </dl>

                    <?php if (!$sendAllowed): ?>
                        <div class="alert alert-warning mb-0">Tuto změnu nelze odeslat, protože není ve stavu Nasazeno.</div>
                    <?php elseif ($groupOptions === []): ?>
                        <div class="alert alert-warning mb-0">Nejsou dostupné žádné skupiny uživatelů.</div>
                    <?php elseif ($emailPreviewError !== ''): ?>
                        <div class="alert alert-warning mb-0">Náhled e-mailu nelze sestavit: <?= changelog_e($emailPreviewError) ?></div>
                    <?php else: ?>
                        <form method="post" onsubmit="return confirm('Opravdu odeslat ChangeLog vybraným skupinám?');">
                            <input type="hidden" name="csrf_token" value="<?= changelog_e($sendCsrfToken) ?>">
                            <input type="hidden" name="action" value="send_email">
                            <input type="hidden" name="send" value="<?= (int)$sendRow['id'] ?>">

                            <label for="changelog_group_keys" class="form-label">Odeslat skupinám</label>
                            <select name="group_keys[]" id="changelog_group_keys" class="form-select" multiple size="<?= max(6, min(12, count($groupOptions) + count($groupOptionsBySource))) ?>" required>
                                <?php foreach ($groupOptionsBySource as $sourceLabel => $sourceOptions): ?>
                                    <optgroup label="<?= changelog_e($sourceLabel) ?>">
                                        <?php foreach ($sourceOptions as $groupOption): ?>
                                            <option value="<?= changelog_e((string)$groupOption['key']) ?>" <?= in_array((string)$groupOption['key'], $sendSelectedGroups, true) ? 'selected' : '' ?>>
                                                <?= changelog_e((string)$groupOption['name']) ?> (<?= (int)$groupOption['recipient_count'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Drž Ctrl/Cmd pro výběr více skupin. Duplicitní e-maily napříč skupinami se odešlou jen jednou.</div>

                            <?php if (is_array($sendResult) && ($sendResult['errors'] ?? []) !== []): ?>
                                <details class="mt-3">
                                    <summary>Chyby odeslání</summary>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach (array_slice((array)$sendResult['errors'], 0, 30) as $sendError): ?>
                                            <li><?= changelog_e((string)$sendError) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-send me-1"></i> Odeslat e-mailem
                                </button>
                                <a href="<?= changelog_admin_detail_url((int)$sendRow['id']) ?>" class="btn btn-outline-secondary">Zpět na detail</a>
                                <a href="<?= changelog_admin_list_url() ?>" class="btn btn-outline-secondary">Zpět na ChangeLog</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Náhled e-mailu</h6>
                </div>
                <div class="card-body">
                    <?php if ($emailPreviewHtml !== ''): ?>
                        <iframe class="newsletter-preview-frame" title="Náhled e-mailu ChangeLogu" srcdoc="<?= changelog_e($emailPreviewHtml) ?>"></iframe>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">Náhled není k dispozici.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tableExists && $show === 3): ?>
    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">
                        <?= $categoryEditing ? 'Editace kategorie' : 'Přidání kategorie' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!$categoryTableExists): ?>
                        <div class="alert alert-warning mb-0">
                            Správa kategorií bude dostupná po spuštění migrace `changelog_cat`.
                        </div>
                    <?php else: ?>
                        <form method="post" autocomplete="off">
                            <input type="hidden" name="action" value="<?= $categoryEditing ? 'category_update' : 'category_create' ?>">
                            <?php if ($categoryEditing): ?>
                                <input type="hidden" name="id" value="<?= (int)$categoryForm['id'] ?>">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-12 col-md-5">
                                    <label for="cat_code" class="form-label">Kód</label>
                                    <input type="text" name="code" id="cat_code" class="form-control" maxlength="64"
                                           value="<?= changelog_e((string)$categoryForm['code']) ?>" required>
                                    <div class="form-text">Např. `objednavky`, bez diakritiky.</div>
                                </div>

                                <div class="col-12 col-md-7">
                                    <label for="cat_name" class="form-label">Název</label>
                                    <input type="text" name="name" id="cat_name" class="form-control" maxlength="120"
                                           value="<?= changelog_e((string)$categoryForm['name']) ?>" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="cat_badge_class" class="form-label">Barva</label>
                                    <select name="badge_class" id="cat_badge_class" class="form-select">
                                        <?php foreach ($categoryBadgeOptions as $value => $label): ?>
                                            <option value="<?= changelog_e($value) ?>" <?= (string)$categoryForm['badge_class'] === $value ? 'selected' : '' ?>>
                                                <?= changelog_e($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="cat_sort_order" class="form-label">Pořadí</label>
                                    <input type="number" min="0" max="999" step="1" name="sort_order" id="cat_sort_order" class="form-control"
                                           value="<?= (int)$categoryForm['sort_order'] ?>">
                                </div>

                                <div class="col-6 col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="active_l" id="cat_active_l" value="1"
                                            <?= (int)($categoryForm['active_l'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="cat_active_l">aktivní</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?= $categoryEditing ? 'Uložit kategorii' : 'Vložit kategorii' ?>
                                </button>
                                <a href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=3" class="btn btn-outline-secondary">Nová kategorie</a>
                                <a href="index.php?section=09&amp;page=02&amp;sec_page=07" class="btn btn-outline-secondary">Zpět na ChangeLog</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Kategorie ChangeLogu</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered table-sm align-middle">
                            <thead class="table-dark">
                            <tr>
                                <th>Kód</th>
                                <th>Název</th>
                                <th>Barva</th>
                                <th>Pořadí</th>
                                <th>Aktivní</th>
                                <th class="text-end">Akce</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($categoryRows as $categoryRow): ?>
                                <tr>
                                    <td><code><?= changelog_e((string)$categoryRow['code']) ?></code></td>
                                    <td><?= changelog_e((string)$categoryRow['name']) ?></td>
                                    <td>
                                        <span class="badge <?= changelog_e(changelog_safe_badge_class((string)$categoryRow['badge_class'])) ?>">
                                            <?= changelog_e((string)$categoryRow['name']) ?>
                                        </span>
                                    </td>
                                    <td><?= (int)($categoryRow['sort_order'] ?? 0) ?></td>
                                    <td>
                                        <?= (int)($categoryRow['active_l'] ?? 0) === 1
                                            ? '<span class="badge text-bg-success">ANO</span>'
                                            : '<span class="badge text-bg-secondary">NE</span>' ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ((int)($categoryRow['id'] ?? 0) > 0): ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a class="btn btn-outline-primary" href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=3&amp;category_edit=<?= (int)$categoryRow['id'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Skrýt tuto kategorii z aktivního výběru?');">
                                                    <input type="hidden" name="action" value="category_archive">
                                                    <input type="hidden" name="id" value="<?= (int)$categoryRow['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-eye-slash"></i></button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">fallback</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tableExists && ($show === 1 || $show === 2)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">
                <?= $editing ? 'Editace změny' : 'Přidání změny' ?>
            </h6>
        </div>

        <div class="card-body">
            <form method="post" autocomplete="off">
                <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <label for="title" class="form-label">Název změny</label>
                        <input type="text" name="title" id="title" class="form-control" maxlength="180"
                               value="<?= changelog_e((string)$form['title']) ?>" required>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="category" class="form-label">Kategorie</label>
                        <select
                            name="category"
                            id="category"
                            class="form-select js-admin-single-picker"
                            data-picker-title="Vybrat kategorii změny"
                            data-picker-description="Vyberte jednu kategorii pro evidovanou změnu."
                            data-picker-search-placeholder="Hledat podle názvu kategorie…"
                            required
                        >
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= changelog_e($value) ?>" <?= (string)$form['category'] === $value ? 'selected' : '' ?>>
                                    <?= changelog_e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="status" class="form-label">Stav</label>
                        <select name="status" id="status" class="form-select" required>
                            <?php foreach ($statuses as $value => $meta): ?>
                                <option value="<?= changelog_e($value) ?>" <?= (string)$form['status'] === $value ? 'selected' : '' ?>>
                                    <?= changelog_e((string)$meta['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Popis</label>
                        <textarea name="description" id="description" class="form-control" rows="4"><?= changelog_e((string)$form['description']) ?></textarea>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="recorded_on" class="form-label">Zaevidováno</label>
                        <input type="date" name="recorded_on" id="recorded_on" class="form-control"
                               value="<?= changelog_e((string)$form['recorded_on']) ?>" required>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="planned_month" class="form-label">Plán měsíc</label>
                        <select name="planned_month" id="planned_month" class="form-select">
                            <option value="">bez termínu</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= (int)($form['planned_month'] ?? 0) === $m ? 'selected' : '' ?>>
                                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="planned_year" class="form-label">Plán rok</label>
                        <select name="planned_year" id="planned_year" class="form-select">
                            <option value="">bez termínu</option>
                            <?php for ($y = $currentYear - 1; $y <= $currentYear + 4; $y++): ?>
                                <option value="<?= $y ?>" <?= (int)($form['planned_year'] ?? 0) === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="done_on" class="form-label">Hotovo</label>
                        <input type="date" name="done_on" id="done_on" class="form-control"
                               value="<?= changelog_e((string)($form['done_on'] ?? '')) ?>">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="news_id" class="form-label">ID novinky</label>
                        <input type="number" min="1" step="1" name="news_id" id="news_id" class="form-control"
                               value="<?= changelog_e((string)($form['news_id'] ?? '')) ?>"
                            <?= $newsLinkAvailable ? '' : 'disabled' ?>>
                        <div class="form-text">Volitelná vazba na manuál/novinku.</div>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="priority" class="form-label">Pořadí</label>
                        <input type="number" min="0" max="255" step="1" name="priority" id="priority" class="form-control"
                               value="<?= (int)$form['priority'] ?>">
                    </div>

                    <div class="col-6 col-lg-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="active_l" id="active_l" value="1"
                                <?= (int)($form['active_l'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active_l">aktivní</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?= $editing ? 'Uložit změnu' : 'Vložit změnu' ?>
                    </button>
                    <a href="index.php?section=09&amp;page=02&amp;sec_page=07" class="btn btn-outline-secondary">Zpět na přehled</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($tableExists && !in_array($show, [4, 5], true)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Evidence změn</h6>
            <span class="d-none d-sm-inline-block ms-2 text-muted">plánované a nasazené změny</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm align-middle js-datatable" data-order='[[ 5, "asc" ], [ 0, "desc" ]]' data-page-length='50'>
                    <thead class="table-dark align-middle">
                    <tr>
                        <th class="text-filter">ID</th>
                        <th class="text-filter">Název</th>
                        <th class="text-filter">Kategorie</th>
                        <th class="text-filter">Stav</th>
                        <th data-type="date">Zaevidováno</th>
                        <th class="text-filter">Plán</th>
                        <th data-type="date">Hotovo</th>
                        <th>Pořadí</th>
                        <th class="text-filter">Novinka</th>
                        <th class="no-sort no-filter text-end">Akce</th>
                    </tr>
                    </thead>
                    <tfoot class="table-light">
                    <tr>
                        <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $status = (string)($row['status'] ?? '');
                        $category = (string)($row['category'] ?? '');
                        ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= changelog_e((string)$row['title']) ?></div>
                                <?php if (trim((string)($row['description'] ?? '')) !== ''): ?>
                                    <div class="small text-muted"><?= nl2br(changelog_e((string)$row['description'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= changelog_e(changelog_category_badge($category)) ?>">
                                    <?= changelog_e(changelog_category_label($category)) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= changelog_e(changelog_status_badge($status)) ?>">
                                    <?= changelog_e(changelog_status_label($status)) ?>
                                </span>
                            </td>
                            <td><?= changelog_e((string)format_date_www((string)($row['recorded_on'] ?? ''))) ?></td>
                            <td><?= changelog_e(changelog_planned_text($row)) ?></td>
                            <td><?= changelog_e((string)format_date_www((string)($row['done_on'] ?? ''))) ?></td>
                            <td><?= (int)($row['priority'] ?? 0) ?></td>
                            <td>
                                <?php if ((int)($row['news_id'] ?? 0) > 0): ?>
                                    <?php if (changelog_has_linked_news($row)): ?>
                                        <span class="fw-semibold">
                                            #<?= (int)$row['news_id'] ?> - <?= changelog_e(changelog_linked_news_title($row)) ?>
                                        </span>
                                        <?php if ($status === 'nasazeno'): ?>
                                            <?php $previewText = changelog_news_preview_text($row); ?>
                                            <div class="small text-muted mt-1">
                                                <?php if ((string)($row['changelog_news_date'] ?? '') !== ''): ?>
                                                    <span class="badge text-bg-light border me-1">
                                                        <?= changelog_e((string)format_date_www((string)$row['changelog_news_date'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?= $previewText !== '' ? changelog_e($previewText) : 'Náhled textu není vyplněný.' ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger">Novinka #<?= (int)$row['news_id'] ?> nenalezena</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">bez vazby</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a class="btn btn-outline-secondary" href="<?= changelog_admin_detail_url((int)$row['id']) ?>" title="Detail změny">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a class="btn btn-outline-primary" href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=2&amp;edit=<?= (int)$row['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($status === 'nasazeno'): ?>
                                        <a class="btn btn-outline-success" href="index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=5&amp;send=<?= (int)$row['id'] ?>" title="Odeslat e-mailem">
                                            <i class="bi bi-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Skrýt tuto změnu z aktivní evidence?');">
                                        <input type="hidden" name="action" value="archive">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-eye-slash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
