<?php
declare(strict_types=1);

global $pdo;

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$code = trim((string)($_POST['code'] ?? ''));
$cz = (string)($_POST['cz'] ?? '');
$en = (string)($_POST['en'] ?? '');
$cz = str_replace("\r\n", '', $cz);
$en = str_replace("\r\n", '', $en);
$valid = isset($_POST['valid']) ? 1 : 0;

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <?php
        $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

        if (!$isPost) {
            $stmt = $pdo->prepare('SELECT * FROM stat_vyrazy WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $edit]);
            $dev = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dev) {
                echo '<div class="alert alert-danger">Záznam nenalezen.</div>';
                return;
            }

            $code = (string)($dev['code'] ?? '');
            $cz = (string)($dev['cz'] ?? '');
            $en = (string)($dev['en'] ?? '');
            $valid = (int)($dev['valid'] ?? 0);
        } else {
            $stmt = $pdo->prepare('SELECT ts_i, user_i, ts_u, user_u FROM stat_vyrazy WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $edit]);
            $dev = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        ?>

        <form method="post" autocomplete="off">
            <div class="row g-3">

                <div class="col-md-6">
                    <label for="code" class="form-label">Kód výrazu</label>
                    <input type="text" name="code" id="code" class="form-control"
                           value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12">
                    <label for="cz" class="form-label">Statický výraz (cz)</label>
                    <textarea name="cz" id="cz" class="form-control js-tinymce" rows="8" data-tinymce-height="260"><?= $cz ?></textarea>
                </div>

                <div class="col-12">
                    <label for="en" class="form-label">Statický výraz (en)</label>
                    <textarea name="en" id="en" class="form-control js-tinymce" rows="8" data-tinymce-height="260"><?= $en ?></textarea>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1"
                                <?= ($valid === 1 ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="valid">valid</label>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-primary w-100">Upravit statický výraz</button>
                </div>

                <div class="col-12 small text-muted">
                    Založeno: <?= isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : '' ?>;
                    Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;
                    Upraveno: <?= isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : '' ?>;
                    Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>

            </div>
        </form>

    <?php elseif ($add === 2): ?>

        <?php statvyrazy_edit($edit, $code, $cz, $en, $valid); ?>

    <?php endif; ?>
</div>
