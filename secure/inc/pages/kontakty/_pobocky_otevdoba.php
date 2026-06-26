<?php
declare(strict_types=1);

$hoursStatus = (string)($_GET['hours_status'] ?? '');
$hoursEdit = (int)($_GET['hours_edit'] ?? 0);
$hoursSuccessMessage = '';
$hoursErrorMessage = '';
$exceptionErrorMessage = '';
$weekRows = pobocky_otevdoba_fetch_week($pdo, $edit);
$exceptionRows = pobocky_otevdoba_fetch_exceptions($pdo, $edit);
$exceptionForm = pobocky_otevdoba_default_exception();

if ($hoursStatus === 'standard_saved') {
    $hoursSuccessMessage = 'Standardní otevírací doba byla uložena.';
} elseif ($hoursStatus === 'exception_saved') {
    $hoursSuccessMessage = 'Výjimka otevírací doby byla uložena.';
} elseif ($hoursStatus === 'exception_deleted') {
    $hoursSuccessMessage = 'Výjimka otevírací doby byla smazána.';
}

if ($hoursEdit > 0) {
    foreach ($exceptionRows as $row) {
        if ((int)($row['id'] ?? 0) === $hoursEdit) {
            $exceptionForm = array_merge($exceptionForm, $row);
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoursAction = (string)($_POST['hours_action'] ?? '');

    if ($hoursAction === 'save_standard') {
        try {
            $weekRows = pobocky_otevdoba_normalize_week((array)($_POST['standard'] ?? []));
            pobocky_otevdoba_save_week($pdo, $edit, $weekRows);
            pobocky_redirect($type, [
                'show' => 2,
                'edit' => $edit,
                'limit' => $loadedCount,
                'valid' => $valid,
                'hours_status' => 'standard_saved',
            ]);
            return;
        } catch (Throwable $e) {
            $hoursErrorMessage = $e->getMessage();
            try {
                $weekRows = pobocky_otevdoba_normalize_week((array)($_POST['standard'] ?? []));
            } catch (Throwable $inner) {
            }
        }
    } elseif ($hoursAction === 'save_exception') {
        try {
            $exceptionForm = pobocky_otevdoba_normalize_exception((array)($_POST['exception'] ?? []));
            pobocky_otevdoba_save_exception($pdo, $edit, $exceptionForm);
            pobocky_redirect($type, [
                'show' => 2,
                'edit' => $edit,
                'limit' => $loadedCount,
                'valid' => $valid,
                'hours_status' => 'exception_saved',
            ]);
            return;
        } catch (Throwable $e) {
            $exceptionErrorMessage = $e->getMessage();
            $exceptionForm = array_merge(
                pobocky_otevdoba_default_exception(),
                [
                    'datum' => (string)($_POST['exception']['datum'] ?? ''),
                    'zavreno' => isset($_POST['exception']['zavreno']) ? 1 : 0,
                    'od1' => (string)($_POST['exception']['od1'] ?? ''),
                    'do1' => (string)($_POST['exception']['do1'] ?? ''),
                    'od2' => (string)($_POST['exception']['od2'] ?? ''),
                    'do2' => (string)($_POST['exception']['do2'] ?? ''),
                    'poznamka_cz' => (string)($_POST['exception']['poznamka_cz'] ?? ''),
                    'poznamka_en' => (string)($_POST['exception']['poznamka_en'] ?? ''),
                    'valid' => 1,
                ]
            );
        }
    } elseif ($hoursAction === 'delete_exception') {
        $exceptionId = (int)($_POST['exception_id'] ?? 0);
        if ($exceptionId > 0) {
            pobocky_otevdoba_delete_exception($pdo, $edit, $exceptionId);
            pobocky_redirect($type, [
                'show' => 2,
                'edit' => $edit,
                'limit' => $loadedCount,
                'valid' => $valid,
                'hours_status' => 'exception_deleted',
            ]);
            return;
        }
    }

    $exceptionRows = pobocky_otevdoba_fetch_exceptions($pdo, $edit);
}
?>

<div class="mt-4 pt-4 border-top">
    <h6 class="fw-bold text-primary mb-3">Standardní otevírací doba</h6>

    <?php if ($hoursSuccessMessage !== ''): ?>
        <div class="alert alert-success mb-3">
            <?= htmlspecialchars($hoursSuccessMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <?php if ($hoursErrorMessage !== ''): ?>
        <div class="alert alert-danger mb-3">
            <?= htmlspecialchars($hoursErrorMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="hours_action" value="save_standard">

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered table-sm align-middle mb-3">
                <thead class="table-light">
                <tr>
                    <th>Den</th>
                    <th>Zavřeno</th>
                    <th>Od 1</th>
                    <th>Do 1</th>
                    <th>Od 2</th>
                    <th>Do 2</th>
                    <th>Poznámka CZ</th>
                    <th>Poznámka EN</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($weekRows as $day => $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars(pobocky_day_label((int)$day), ENT_QUOTES) ?></td>
                        <td class="text-center">
                            <input type="hidden" name="standard[<?= (int)$day ?>][valid]" value="1">
                            <input type="hidden" name="standard[<?= (int)$day ?>][sync_lock]" value="<?= (int)($row['sync_lock'] ?? 0) ?>">
                            <div class="form-check d-inline-flex justify-content-center m-0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="standard[<?= (int)$day ?>][zavreno]"
                                    value="1"
                                    <?= ((int)($row['zavreno'] ?? 0) === 1 ? 'checked' : '') ?>
                                >
                            </div>
                        </td>
                        <td><input type="time" class="form-control form-control-sm" name="standard[<?= (int)$day ?>][od1]" value="<?= htmlspecialchars((string)($row['od1'] ?? ''), ENT_QUOTES) ?>"></td>
                        <td><input type="time" class="form-control form-control-sm" name="standard[<?= (int)$day ?>][do1]" value="<?= htmlspecialchars((string)($row['do1'] ?? ''), ENT_QUOTES) ?>"></td>
                        <td><input type="time" class="form-control form-control-sm" name="standard[<?= (int)$day ?>][od2]" value="<?= htmlspecialchars((string)($row['od2'] ?? ''), ENT_QUOTES) ?>"></td>
                        <td><input type="time" class="form-control form-control-sm" name="standard[<?= (int)$day ?>][do2]" value="<?= htmlspecialchars((string)($row['do2'] ?? ''), ENT_QUOTES) ?>"></td>
                        <td><input type="text" class="form-control form-control-sm" name="standard[<?= (int)$day ?>][poznamka_cz]" value="<?= htmlspecialchars((string)($row['poznamka_cz'] ?? ''), ENT_QUOTES) ?>"></td>
                        <td><input type="text" class="form-control form-control-sm" name="standard[<?= (int)$day ?>][poznamka_en]" value="<?= htmlspecialchars((string)($row['poznamka_en'] ?? ''), ENT_QUOTES) ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-start">
            <button type="submit" class="btn btn-primary">Uložit standardní dobu</button>
        </div>
    </form>
</div>

<div class="mt-4 pt-4 border-top">
    <h6 class="fw-bold text-primary mb-3">Výjimky otevírací doby</h6>

    <?php if ($exceptionErrorMessage !== ''): ?>
        <div class="alert alert-danger mb-3">
            <?= htmlspecialchars($exceptionErrorMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="mb-4">
        <input type="hidden" name="hours_action" value="save_exception">
        <input type="hidden" name="exception[id]" value="<?= (int)($exceptionForm['id'] ?? 0) ?>">
        <input type="hidden" name="exception[valid]" value="1">

        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="exception_datum" class="form-label">Datum</label>
                <input type="date" name="exception[datum]" id="exception_datum" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['datum'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-2">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="exception[zavreno]" id="exception_zavreno" value="1" <?= ((int)($exceptionForm['zavreno'] ?? 0) === 1 ? 'checked' : '') ?>>
                    <label class="form-check-label" for="exception_zavreno">zavřeno</label>
                </div>
            </div>

            <div class="col-md-2">
                <label for="exception_od1" class="form-label">Od 1</label>
                <input type="time" name="exception[od1]" id="exception_od1" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['od1'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-2">
                <label for="exception_do1" class="form-label">Do 1</label>
                <input type="time" name="exception[do1]" id="exception_do1" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['do1'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-2">
                <label for="exception_od2" class="form-label">Od 2</label>
                <input type="time" name="exception[od2]" id="exception_od2" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['od2'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-2">
                <label for="exception_do2" class="form-label">Do 2</label>
                <input type="time" name="exception[do2]" id="exception_do2" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['do2'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-6">
                <label for="exception_poznamka_cz" class="form-label">Poznámka CZ</label>
                <input type="text" name="exception[poznamka_cz]" id="exception_poznamka_cz" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['poznamka_cz'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-6">
                <label for="exception_poznamka_en" class="form-label">Poznámka EN</label>
                <input type="text" name="exception[poznamka_en]" id="exception_poznamka_en" class="form-control" value="<?= htmlspecialchars((string)($exceptionForm['poznamka_en'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary w-100"><?= ((int)($exceptionForm['id'] ?? 0) > 0 ? 'Uložit výjimku' : 'Přidat výjimku') ?></button>
            </div>
            <?php if ((int)($exceptionForm['id'] ?? 0) > 0): ?>
                <div class="col-md-3">
                    <a href="<?= htmlspecialchars(pobocky_page_url($type, ['show' => 2, 'edit' => $edit, 'limit' => $loadedCount, 'valid' => $valid]), ENT_QUOTES) ?>#otevdoba-vyjimky" class="btn btn-outline-secondary w-100">Zrušit editaci</a>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-responsive" id="otevdoba-vyjimky">
        <table class="table table-striped table-hover table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Datum</th>
                <th>Režim</th>
                <th>Poznámka CZ</th>
                <th>Poznámka EN</th>
                <th>Upraveno</th>
                <th>Editace</th>
                <th>Smazat</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($exceptionRows === []): ?>
                <tr>
                    <td colspan="7" class="text-muted text-center">Zatím nejsou zadané žádné výjimky.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($exceptionRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)format_date_www((string)$row['datum']), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars(pobocky_otevdoba_time_range_label($row), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($row['poznamka_cz'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($row['poznamka_en'] ?? ''), ENT_QUOTES) ?></td>
                        <td>
                            <?= htmlspecialchars((string)format_datetime_www((string)($row['ts_u'] ?? '')), ENT_QUOTES) ?>
                            <br><small class="text-muted"><?= htmlspecialchars((string)($row['user_u'] ?? ''), ENT_QUOTES) ?></small>
                        </td>
                        <td class="text-center">
                            <a href="<?= htmlspecialchars(pobocky_page_url($type, ['show' => 2, 'edit' => $edit, 'limit' => $loadedCount, 'valid' => $valid, 'hours_edit' => (int)$row['id']]), ENT_QUOTES) ?>#otevdoba-vyjimky" class="btn btn-warning btn-circle btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <form method="post" autocomplete="off" onsubmit="return confirm('Opravdu smazat tuto výjimku?');">
                                <input type="hidden" name="hours_action" value="delete_exception">
                                <input type="hidden" name="exception_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-circle btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
