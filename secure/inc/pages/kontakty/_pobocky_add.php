<?php
declare(strict_types=1);

$errorMessage = '';
$formValues = pobocky_default_form_data($type);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)($_POST['add'] ?? 0) === 1) {
    try {
        $formValues = pobocky_normalize_form_data($_POST, $type);
        $formValues['image'] = pobocky_image_upload($_FILES['userfile'] ?? null, '');
        pobocky_add($pdo, $formValues);
        pobocky_redirect($type, [
            'show' => 11,
            'limit' => $loadedCount,
            'valid' => $valid,
        ]);
        return;
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        try {
            $formValues = pobocky_normalize_form_data(array_merge($_POST, ['valid' => $_POST['valid'] ?? null]), $type);
        } catch (Throwable $inner) {
            $formValues = array_merge($formValues, pobocky_default_form_data($type));
            $formValues['typ'] = $type;
            $formValues['poradi'] = (int)($_POST['poradi'] ?? 0);
            $formValues['stredisko'] = (string)($_POST['stredisko'] ?? '');
            $formValues['galerie_id'] = trim((string)($_POST['galerie_id'] ?? ''));
            $formValues['nazev_cz'] = (string)($_POST['nazev_cz'] ?? '');
            $formValues['nazev_en'] = (string)($_POST['nazev_en'] ?? '');
            $formValues['mobil'] = (string)($_POST['mobil'] ?? '');
            $formValues['email'] = (string)($_POST['email'] ?? '');
            $formValues['adresa'] = (string)($_POST['adresa'] ?? '');
            $formValues['gps'] = (string)($_POST['gps'] ?? '');
            $formValues['vedouci'] = (string)($_POST['vedouci'] ?? '');
            $formValues['image'] = '';
            $formValues['sluzby_cz'] = (string)($_POST['sluzby_cz'] ?? '');
            $formValues['sluzby_en'] = (string)($_POST['sluzby_en'] ?? '');
            $formValues['valid'] = isset($_POST['valid']) ? 1 : 0;
        }
    }
}

$formActionValue = 1;
$formSubmitLabel = 'Vložit pobočku';
$formAudit = [];
?>

<div class="card-body">
    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger mb-3">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/_pobocky_form.php'; ?>
</div>
