<?php
declare(strict_types=1);

global $pdo, $lang;

$type = isset($_GET['typ']) ? (int)$_GET['typ'] : 1;
$unify = trim((string)($_GET['unify'] ?? ''));
$identify = trim((string)($_GET['identify'] ?? ''));
$selectedPeriod = trim((string)($_GET['obdobi'] ?? ''));
$invoice = null;
$summaryRows = [];
$detailRows = [];
$emailRows = [];
$emailPeriods = [];
$emailTotal = 0.0;
$rowTotalWithoutVat = 0.0;
$rowTotalWithVat = 0.0;

if ($type === 3 && $identify !== '') {
    $emailPeriods = frontend_volani_periods_by_email($pdo, $identify);
    $availablePeriods = array_map(static fn (array $row): string => (string)($row['obdobi'] ?? ''), $emailPeriods);
    if ($selectedPeriod === '' || !in_array($selectedPeriod, $availablePeriods, true)) {
        $selectedPeriod = $availablePeriods[0] ?? '';
    }

    $emailRows = frontend_volani_invoices_by_email($pdo, $identify, $selectedPeriod);
    foreach ($emailRows as $row) {
        $emailTotal += (float)($row['celkem'] ?? 0);
    }
} elseif ($unify !== '') {
    $invoice = frontend_volani_invoice_by_unify($pdo, $unify);
    if ($invoice !== null) {
        if ($type === 2) {
            $detailRows = frontend_volani_detail($pdo, $invoice);
            foreach ($detailRows as $row) {
                $rowTotalWithoutVat += (float)($row['celkem_bez_dph'] ?? 0);
                $rowTotalWithVat += (float)($row['celkem_s_dph'] ?? 0);
            }
        } else {
            $summaryRows = frontend_volani_summary($pdo, $invoice);
            foreach ($summaryRows as $row) {
                $rowTotalWithoutVat += (float)($row['celkem_bez_dph'] ?? 0);
                $rowTotalWithVat += (float)($row['celkem_s_dph'] ?? 0);
            }
        }
    }
}
?>

<section class="site-section">
    <div class="site-shell">
        <?php if ($type === 3): ?>
            <div class="volani-card">
                <div class="volani-hero">
                    <div>
                        <div class="volani-kicker"><?= htmlspecialchars(ui_text('volani.title', 'Vyúčtování volání'), ENT_QUOTES, 'UTF-8') ?></div>
                        <h1><?= htmlspecialchars(ui_text('volani.overview_title', 'Přehled vyúčtování'), ENT_QUOTES, 'UTF-8') ?></h1>
                        <div class="volani-meta">
                            <span><?= htmlspecialchars(ui_text('volani.email', 'E-mail'), ENT_QUOTES, 'UTF-8') ?>: <?= rep_volani_e($identify) ?></span>
                            <?php if ($selectedPeriod !== ''): ?>
                                <span><?= htmlspecialchars(ui_text('volani.period', 'Období'), ENT_QUOTES, 'UTF-8') ?>: <?= rep_volani_e(rep_volani_period_label($selectedPeriod)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($emailPeriods !== []): ?>
                        <form method="get" class="volani-period-form">
                            <input type="hidden" name="typ" value="3">
                            <input type="hidden" name="identify" value="<?= rep_volani_e($identify) ?>">
                            <label for="volani-obdobi"><?= htmlspecialchars(ui_text('volani.select_period', 'Vyberte období'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="volani-period-form__row">
                                <select name="obdobi" id="volani-obdobi">
                                    <?php foreach ($emailPeriods as $periodRow): ?>
                                        <?php $periodValue = (string)($periodRow['obdobi'] ?? ''); ?>
                                        <option value="<?= rep_volani_e($periodValue) ?>" <?= $periodValue === $selectedPeriod ? 'selected' : '' ?>>
                                            <?= rep_volani_e(rep_volani_period_label($periodValue)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"><?= htmlspecialchars(ui_text('common.show', 'Zobrazit'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if ($emailRows === []): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars(ui_text('volani.not_found', 'Vyúčtování nebylo nalezeno.'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <div class="volani-summary-grid">
                        <div class="volani-summary-box">
                            <span><?= htmlspecialchars(ui_text('volani.phone', 'Telefon'), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= number_format(count($emailRows), 0, ',', ' ') ?></strong>
                        </div>
                        <div class="volani-summary-box volani-summary-box--primary">
                            <span><?= htmlspecialchars(ui_text('common.total', 'Celkem'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(ui_text('volani.with_vat', 'S DPH'), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= rep_volani_money($emailTotal) ?></strong>
                        </div>
                    </div>
                    <div class="volani-table-wrap">
                        <table class="volani-table">
                            <thead>
                            <tr>
                                <th><?= htmlspecialchars(ui_text('volani.period', 'Období'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(ui_text('volani.name', 'Jméno'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(ui_text('volani.phone', 'Telefon'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-end"><?= htmlspecialchars(ui_text('volani.without_vat', 'Bez DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-end"><?= htmlspecialchars(ui_text('volani.with_vat', 'S DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($emailRows as $row): ?>
                                <tr>
                                    <td><?= rep_volani_e(rep_volani_period_label((string)($row['obdobi'] ?? ''))) ?></td>
                                    <td><?= rep_volani_e($row['jmeno'] ?? '') ?></td>
                                    <td><?= rep_volani_e($row['mobil'] ?? '') ?></td>
                                    <td class="text-end"><?= rep_volani_money($row['zakladcelkem'] ?? 0) ?></td>
                                    <td class="text-end fw-semibold"><?= rep_volani_money($row['celkem'] ?? 0) ?></td>
                                    <td><a class="volani-link" href="/volani/index.php?typ=1&amp;unify=<?= rawurlencode((string)$row['unify']) ?>"><?= htmlspecialchars(ui_text('volani.summary', 'Souhrn'), ENT_QUOTES, 'UTF-8') ?></a></td>
                                    <td><a class="volani-link" href="/volani/index.php?typ=2&amp;unify=<?= rawurlencode((string)$row['unify']) ?>"><?= htmlspecialchars(ui_text('volani.detail', 'Detail'), ENT_QUOTES, 'UTF-8') ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold"><?= htmlspecialchars(ui_text('common.total', 'Celkem'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-bold"><?= rep_volani_money($emailTotal) ?></td>
                                <td colspan="2"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="volani-card">
                <?php if ($invoice === null): ?>
                    <h1><?= htmlspecialchars(ui_text('volani.title', 'Vyúčtování volání'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="alert alert-warning"><?= htmlspecialchars(ui_text('volani.not_found', 'Vyúčtování nebylo nalezeno.'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <div class="volani-hero">
                        <div>
                            <div class="volani-kicker"><?= htmlspecialchars(ui_text('volani.title', 'Vyúčtování volání'), ENT_QUOTES, 'UTF-8') ?></div>
                            <h1><?= htmlspecialchars($type === 2 ? ui_text('volani.detail_title', 'Podrobný výpis') : ui_text('volani.summary_title', 'Souhrnné vyúčtování'), ENT_QUOTES, 'UTF-8') ?></h1>
                            <div class="volani-meta">
                                <span><?= rep_volani_e($invoice['jmeno'] ?? '') ?></span>
                                <span><?= rep_volani_e($invoice['mobil'] ?? '') ?></span>
                                <span><?= rep_volani_e(rep_volani_period_label((string)($invoice['obdobi'] ?? ''))) ?></span>
                            </div>
                        </div>
                        <div class="volani-actions">
                            <?php if (trim((string)($invoice['email'] ?? '')) !== ''): ?>
                                <a class="volani-action" href="/volani/index.php?typ=3&amp;identify=<?= rawurlencode((string)$invoice['email']) ?>&amp;obdobi=<?= rawurlencode((string)$invoice['obdobi']) ?>"><?= htmlspecialchars(ui_text('volani.back_overview', 'Zpět na přehled'), ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endif; ?>
                            <a class="volani-action <?= $type !== 2 ? 'is-active' : '' ?>" href="/volani/index.php?typ=1&amp;unify=<?= rawurlencode($unify) ?>"><?= htmlspecialchars(ui_text('volani.summary', 'Souhrn'), ENT_QUOTES, 'UTF-8') ?></a>
                            <a class="volani-action <?= $type === 2 ? 'is-active' : '' ?>" href="/volani/index.php?typ=2&amp;unify=<?= rawurlencode($unify) ?>"><?= htmlspecialchars(ui_text('volani.detail', 'Detail'), ENT_QUOTES, 'UTF-8') ?></a>
                        </div>
                    </div>

                    <div class="volani-summary-grid">
                        <div class="volani-summary-box">
                            <span><?= htmlspecialchars(ui_text('volani.period', 'Období'), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= rep_volani_e(rep_volani_period_label((string)($invoice['obdobi'] ?? ''))) ?></strong>
                        </div>
                        <div class="volani-summary-box">
                            <span><?= htmlspecialchars(ui_text('common.total', 'Celkem'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(ui_text('volani.without_vat', 'Bez DPH'), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= rep_volani_money($rowTotalWithoutVat ?: ($invoice['zakladcelkem'] ?? 0)) ?></strong>
                        </div>
                        <div class="volani-summary-box volani-summary-box--primary">
                            <span><?= htmlspecialchars(ui_text('common.total', 'Celkem'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(ui_text('volani.with_vat', 'S DPH'), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= rep_volani_money($rowTotalWithVat ?: ($invoice['celkem'] ?? 0)) ?></strong>
                        </div>
                    </div>

                    <?php if ($type === 2): ?>
                        <div class="volani-table-wrap">
                            <table class="volani-table">
                                <thead>
                                <tr>
                                    <th><?= htmlspecialchars(ui_text('volani.period', 'Období'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.product', 'Produktová řada'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.item', 'Položka'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.datetime', 'Datum a čas'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.direction', 'Směr'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.called_number', 'Volané číslo'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.duration', 'Trvání'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.without_vat', 'Bez DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.with_vat', 'S DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($detailRows as $row): ?>
                                    <tr>
                                        <td><?= rep_volani_e(rep_volani_period_label((string)$row['obdobi'])) ?></td>
                                        <td><?= rep_volani_e($row['produkt'] ?? '') ?></td>
                                        <td><?= rep_volani_e($row['polozka'] ?? '') ?></td>
                                        <td><?= rep_volani_e(rep_volani_datetime_label((string)($row['datumcas'] ?? ''))) ?></td>
                                        <td><?= rep_volani_e($row['smer'] ?? '') ?></td>
                                        <td><?= rep_volani_e($row['cislo'] ?? '') ?></td>
                                        <td class="text-end"><?= rep_volani_number($row['trvani'] ?? 0) ?></td>
                                        <td class="text-end"><?= rep_volani_money($row['celkem_bez_dph'] ?? 0) ?></td>
                                        <td class="text-end"><?= rep_volani_money($row['celkem_s_dph'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="7" class="text-end fw-semibold"><?= htmlspecialchars(ui_text('common.total', 'Celkem'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end fw-bold"><?= rep_volani_money($rowTotalWithoutVat) ?></td>
                                    <td class="text-end fw-bold"><?= rep_volani_money($rowTotalWithVat) ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="volani-table-wrap">
                            <table class="volani-table">
                                <thead>
                                <tr>
                                    <th><?= htmlspecialchars(ui_text('volani.period', 'Období'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.product', 'Produktová řada'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.item', 'Položka'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(ui_text('volani.service', 'Služba'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.count', 'Počet'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.duration', 'Trvání'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.volume', 'Objem'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.without_vat', 'Bez DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.vat', 'DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(ui_text('volani.with_vat', 'S DPH'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($summaryRows as $row): ?>
                                    <tr>
                                        <td><?= rep_volani_e(rep_volani_period_label((string)$row['obdobi'])) ?></td>
                                        <td><?= rep_volani_e($row['produkt'] ?? '') ?></td>
                                        <td><?= rep_volani_e($row['polozka'] ?? '') ?></td>
                                        <td><?= rep_volani_e($row['sluzba'] ?? '') ?></td>
                                        <td class="text-end"><?= rep_volani_number($row['pocet'] ?? 0) ?></td>
                                        <td class="text-end"><?= rep_volani_number($row['trvani'] ?? 0) ?></td>
                                        <td class="text-end"><?= rep_volani_number($row['objem'] ?? 0) ?></td>
                                        <td class="text-end"><?= rep_volani_money($row['celkem_bez_dph'] ?? 0) ?></td>
                                        <td class="text-end"><?= rep_volani_number($row['dph'] ?? 0) ?> %</td>
                                        <td class="text-end"><?= rep_volani_money($row['celkem_s_dph'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="7" class="text-end fw-semibold"><?= htmlspecialchars(ui_text('common.total', 'Celkem'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end fw-bold"><?= rep_volani_money($rowTotalWithoutVat) ?></td>
                                    <td></td>
                                    <td class="text-end fw-bold"><?= rep_volani_money($rowTotalWithVat) ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
