<?php
declare(strict_types=1);

$errorMessage = '';
$formAudit = [];

if ($edit <= 0) {
    ?>
    <div class="card-body">
        <div class="alert alert-warning mb-0">Nebyl vybrán záznam k editaci.</div>
    </div>
    <?php
    return;
}

$record = pobocky_fetch_one($pdo, $edit, $type);
if ($record === null) {
    ?>
    <div class="card-body">
        <div class="alert alert-warning mb-0">Požadovaná pobočka nebyla nalezena.</div>
    </div>
    <?php
    return;
}

$formValues = array_merge(pobocky_default_form_data($type), $record);
$formAudit = $record;
$existingImage = (string)($record['image'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)($_POST['add'] ?? 0) === 2) {
    try {
        $formValues = pobocky_normalize_form_data($_POST, $type);
        $formValues['image'] = pobocky_image_upload($_FILES['userfile'] ?? null, $existingImage);
        pobocky_edit($pdo, $edit, $type, $formValues);
        pobocky_redirect($type, [
            'show' => 21,
            'limit' => $loadedCount,
            'valid' => $valid,
        ]);
        return;
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        $formValues = array_merge($formValues, [
            'poradi' => (int)($_POST['poradi'] ?? ($formValues['poradi'] ?? 0)),
            'stredisko' => (string)($_POST['stredisko'] ?? ($formValues['stredisko'] ?? '')),
            'galerie_id' => trim((string)($_POST['galerie_id'] ?? ($formValues['galerie_id'] ?? ''))),
            'nazev_cz' => (string)($_POST['nazev_cz'] ?? ($formValues['nazev_cz'] ?? '')),
            'nazev_en' => (string)($_POST['nazev_en'] ?? ($formValues['nazev_en'] ?? '')),
            'mobil' => (string)($_POST['mobil'] ?? ($formValues['mobil'] ?? '')),
            'email' => (string)($_POST['email'] ?? ($formValues['email'] ?? '')),
            'adresa' => (string)($_POST['adresa'] ?? ($formValues['adresa'] ?? '')),
            'gps' => (string)($_POST['gps'] ?? ($formValues['gps'] ?? '')),
            'vedouci' => (string)($_POST['vedouci'] ?? ($formValues['vedouci'] ?? '')),
            'image' => $existingImage,
            'sluzby_cz' => (string)($_POST['sluzby_cz'] ?? ($formValues['sluzby_cz'] ?? '')),
            'sluzby_en' => (string)($_POST['sluzby_en'] ?? ($formValues['sluzby_en'] ?? '')),
            'valid' => isset($_POST['valid']) ? 1 : 0,
        ]);
    }
}

$formActionValue = 2;
$formSubmitLabel = 'Uložit pobočku';
?>

<div class="card-body">
    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger mb-3">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/_pobocky_form.php'; ?>

    <?php include __DIR__ . '/_pobocky_otevdoba.php'; ?>
</div>
