<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_news.php';

$messages = [];
$errors = [];
$baseUrl = 'index.php?section=01&amp;page=01&amp;sec_page=04';
$defaultLimit = 500;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
if ($limit < 0) {
    $limit = $defaultLimit;
}
$valid = isset($_GET['valid']) ? (int)$_GET['valid'] : 1;
$valid = $valid === 0 ? 0 : 1;
$editTagId = isset($_GET['edit_tag']) ? (int)$_GET['edit_tag'] : 0;
$editTagId = $editTagId > 0 ? $editTagId : 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_tag') {
            $id = isset($_POST['id']) && (int)$_POST['id'] > 0 ? (int)$_POST['id'] : null;
            news_tag_save($_POST, $id);
            $messages[] = $id === null ? 'Štítek novinky byl vytvořen.' : 'Štítek novinky byl uložen.';
            $editTagId = 0;
        }
    }

    if (isset($_GET['delete_tag'])) {
        news_tag_delete((int)$_GET['delete_tag']);
        $messages[] = 'Štítek novinky byl smazán.';
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$tagCount = news_tag_count($valid);
$tagLimit = ($limit === 0 || $tagCount <= $limit) ? $tagCount : $limit;
$tags = news_tags_all($valid, $tagLimit);
$editTag = $editTagId > 0 ? news_tag_get($editTagId) : null;

$toggleParams = [
    'section' => '01',
    'page' => '01',
    'sec_page' => '04',
];
if ($limit !== $defaultLimit) {
    $toggleParams['limit'] = (string)$limit;
}
$toggleParams['valid'] = $valid === 1 ? '0' : '1';
$validToggleUrl = 'index.php?' . http_build_query($toggleParams, '', '&amp;');

$tagTotal = news_tag_count();
$tagValidTotal = news_tag_count(1);
$tagOrderValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['poradi'] ?? '')
    : (string)($editTag['poradi'] ?? news_tag_next_order());
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Štítky novinek</h1>

    <div class="d-flex flex-wrap gap-2">
        <?php if ((int)admin_session_prava() === 1): ?>
            <?php if ($valid === 1): ?>
                <a href="<?= $validToggleUrl ?>" class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm">
                    zobrazit nevalidní záznamy <i class="bi bi-slash-circle ms-1"></i>
                </a>
            <?php else: ?>
                <a href="<?= $validToggleUrl ?>" class="d-none d-sm-inline-block btn btn-sm btn-outline-primary shadow-sm">
                    zobrazit validní záznamy <i class="bi bi-arrow-repeat ms-1"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <span class="btn btn-sm btn-light shadow-sm">vše: <?= number_format($tagTotal, 0, ',', ' ') ?></span>
        <span class="btn btn-sm btn-outline-primary shadow-sm">aktivní: <?= number_format($tagValidTotal, 0, ',', ' ') ?></span>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-success py-2 mb-2"><i class="bi bi-check2-circle me-2"></i><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger py-2 mb-2"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary d-sm-inline"><?= $editTag ? 'Upravit štítek novinky' : 'Přidání štítku novinky' ?></h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_tag">
                    <input type="hidden" name="id" value="<?= (int)($editTag['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="news_tag_poradi">Pořadí</label>
                        <input type="number" class="form-control" name="poradi" id="news_tag_poradi" value="<?= htmlspecialchars($tagOrderValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_tag_nazev_cz">Název CZ</label>
                        <input type="text" class="form-control" name="nazev_cz" id="news_tag_nazev_cz" required value="<?= htmlspecialchars((string)($editTag['nazev_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="news_tag_nazev_en">Název EN</label>
                        <input type="text" class="form-control" name="nazev_en" id="news_tag_nazev_en" value="<?= htmlspecialchars((string)($editTag['nazev_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="news_tag_slug_cz">Slug CZ</label>
                            <input type="text" class="form-control" name="slug_cz" id="news_tag_slug_cz" value="<?= htmlspecialchars((string)($editTag['slug_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="automaticky z názvu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="news_tag_slug_en">Slug EN</label>
                            <input type="text" class="form-control" name="slug_en" id="news_tag_slug_en" value="<?= htmlspecialchars((string)($editTag['slug_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="automaticky z názvu">
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label" for="news_tag_color">Barva</label>
                        <input type="text" class="form-control" name="color" id="news_tag_color" value="<?= htmlspecialchars((string)($editTag['color'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="např. text-bg-qanto-markety">
                        <div class="form-text">Použij Bootstrap třídu nebo projektovou třídu z dashboardu.</div>
                    </div>

                    <?php if ($editTag): ?>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="valid" id="news_tag_valid" value="1" <?= (int)($editTag['valid'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="news_tag_valid">valid</label>
                        </div>

                        <div class="small text-muted mb-3">
                            Založeno: <?= htmlspecialchars((string)format_datetime_www((string)($editTag['ts_i'] ?? '')), ENT_QUOTES, 'UTF-8') ?>;
                            Založil: <?= htmlspecialchars((string)($editTag['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;<br>
                            Upraveno: <?= htmlspecialchars((string)format_datetime_www((string)($editTag['ts_u'] ?? '')), ENT_QUOTES, 'UTF-8') ?>;
                            Upravil: <?= htmlspecialchars((string)($editTag['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Uložit štítek</button>
                    <?php if ($editTag): ?>
                        <a class="btn btn-outline-secondary" href="<?= $baseUrl ?>">Zrušit editaci</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary d-sm-inline">Štítky novinek</h6>
                <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format($tagLimit, 0, ',', ' ') ?> záznamů</span>
                <?php if ($tagCount > $tagLimit): ?>
                    <a href="<?= $baseUrl ?>&amp;limit=0&amp;valid=<?= (int)$valid ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ms-2">
                        načíst všechny záznamy (<?= number_format($tagCount, 0, ',', ' ') ?>) <i class="bi bi-arrow-repeat ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm js-datatable align-middle w-100" data-order='[[ 1, "asc" ], [ 0, "asc" ]]' data-page-length="500">
                        <thead class="table-dark align-middle">
                        <tr>
                            <th class="no-filter">ID</th>
                            <th class="text-filter dt-autocomplete">Pořadí</th>
                            <th class="text-filter dt-autocomplete">Název</th>
                            <th class="text-filter dt-autocomplete">Slug</th>
                            <th class="text-filter dt-autocomplete">Barva</th>
                            <th class="no-filter">Valid</th>
                            <th class="text-filter dt-autocomplete">Upraveno</th>
                            <th class="no-sort no-filter">Upravit</th>
                            <th class="no-sort no-filter">Smazat</th>
                        </tr>
                        </thead>
                        <tfoot class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Pořadí</th>
                            <th>Název</th>
                            <th>Slug</th>
                            <th>Barva</th>
                            <th>Valid</th>
                            <th>Upraveno</th>
                            <th>Upravit</th>
                            <th>Smazat</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td><?= (int)$tag['id'] ?></td>
                                <td><?= (int)($tag['poradi'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($tag['nazev_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($tag['slug_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($tag['color'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <?php if ((int)($tag['valid'] ?? 0) === 1): ?>
                                        <span class="badge text-bg-success">ANO</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">NE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string)format_datetime_www((string)($tag['ts_u'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    <br><small class="text-muted"><?= htmlspecialchars((string)($tag['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-success btn-sm" href="<?= $baseUrl ?>&amp;edit_tag=<?= (int)$tag['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Upravit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-danger btn-sm" href="<?= $baseUrl ?>&amp;delete_tag=<?= (int)$tag['id'] ?>&amp;valid=<?= (int)$valid ?>" title="Smazat" data-confirm="Opravdu smazat štítek novinky?">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
