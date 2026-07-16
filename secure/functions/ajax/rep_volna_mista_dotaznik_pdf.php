<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_rep_volna_mista.php';

$autoload = ROOT_DIR . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo 'Composer vendor/autoload.php neni dostupny. Spust composer install.';
    exit;
}
require_once $autoload;

/**
 * @return array<string, string>
 */
function rep_volna_mista_pdf_detail_fields(): array
{
    return [
        'dot_adresa' => 'Adresa',
        'dot_birthday' => 'Datum narození',
        'dot_vzdelani' => 'Vzdělání',
        'dot_rp' => 'ŘP',
        'dot_jazyk' => 'Jazyky',
        'dot_pc' => 'PC',
        'dot_predchozizam' => 'Předchozí zaměstnavatel',
        'dot_funkcezam' => 'Funkce',
        'dot_delkazam' => 'Délka zaměstnání',
        'dot_pracdoba' => 'Pracovní doba',
        'dot_plat' => 'Plat',
        'dot_koureni' => 'Kouření',
        'dot_rejstrik' => 'Rejstřík',
        'dot_zdravstav' => 'Zdravotní stav',
        'dot_zaliby' => 'Záliby',
        'dot_onas' => 'Jak se dozvěděl/a',
        'dot_prinos' => 'Přínos',
        'dot_profzivot' => 'Profesní životopis',
    ];
}

function rep_volna_mista_pdf_value(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '<span class="muted">-</span>';
    }
    return nl2br(rep_volna_mista_e($value));
}

function rep_volna_mista_pdf_row(string $label, mixed $value): string
{
    return '<tr><th>' . rep_volna_mista_e($label) . '</th><td>' . rep_volna_mista_pdf_value($value) . '</td></tr>';
}

/** @param array<string, mixed> $application */
function rep_volna_mista_pdf_filename(array $application): string
{
    $id = (int)($application['id'] ?? 0);
    $name = strtolower(trim((string)($application['dot_name'] ?? '')));
    $name = preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: '') ?: '';
    $name = trim($name, '-');

    return 'dotaznik-' . $id . ($name !== '' ? '-' . $name : '') . '.pdf';
}

/** @param array<string, mixed> $application */
function rep_volna_mista_pdf_html(array $application): string
{
    global $pdo;

    $position = trim((string)($application['dot_pozice'] ?? ''));
    $jobName = trim((string)($application['misto_nazev_cz'] ?? ''));
    if ($jobName !== '' && $jobName !== $position) {
        $position .= ($position !== '' ? "\n" : '') . $jobName;
    }

    $rows = '';
    $rows .= rep_volna_mista_pdf_row('Datum', rep_volna_mista_format_date($application['dot_datum'] ?? ''));
    $rows .= rep_volna_mista_pdf_row('Uchazeč', $application['dot_name'] ?? '');
    $rows .= rep_volna_mista_pdf_row('E-mail', $application['dot_email'] ?? '');
    $rows .= rep_volna_mista_pdf_row('Mobil', $application['dot_mobil'] ?? '');
    $rows .= rep_volna_mista_pdf_row('Pozice', $position);
    $rows .= rep_volna_mista_pdf_row('Skupina', $application['typ_nazev_cz'] ?? '');
    $attachmentNames = [];
    if ($pdo instanceof PDO) {
        foreach (rep_volna_mista_application_attachments($pdo, (int)($application['id'] ?? 0)) as $attachment) {
            $label = rep_volna_mista_application_attachment_row_label($attachment);
            if ($label !== '') {
                $attachmentNames[] = $label;
            }
        }
    }
    if ($attachmentNames === []) {
        $legacyAttachmentLabel = rep_volna_mista_application_attachment_label($application);
        if ($legacyAttachmentLabel !== '') {
            $attachmentNames[] = $legacyAttachmentLabel;
        }
    }
    $rows .= rep_volna_mista_pdf_row('Přílohy', implode("\n", $attachmentNames));

    foreach (rep_volna_mista_pdf_detail_fields() as $field => $label) {
        $rows .= rep_volna_mista_pdf_row($label, $application[$field] ?? '');
    }

    return '<!doctype html><html lang="cs"><head><meta charset="utf-8"><style>
        @page { margin: 16px 20px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1f2937; font-size: 8px; line-height: 1.18; }
        h1 { margin: 0 0 2px; font-size: 14px; color: #111827; }
        .meta { margin-bottom: 7px; color: #6b7280; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { vertical-align: top; border-bottom: 0.5px solid #e5e7eb; padding: 2.5px 4px; }
        th { width: 27%; color: #374151; text-align: left; background: #f9fafb; font-weight: 700; }
        td { width: 73%; }
        .muted { color: #9ca3af; }
    </style></head><body><h1>Dotazník uchazeče #' . (int)($application['id'] ?? 0) . '</h1><div class="meta">Export z administrace qanto.cz, vygenerováno ' . rep_volna_mista_e((new DateTimeImmutable())->format('d.m.Y H:i')) . '</div><table>' . $rows . '</table></body></html>';
}

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }
    if (!admin_session_is_logged() || !in_array((int)admin_session_prava(), [1, 2], true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo 'Neplatne ID dotazniku.';
        exit;
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET NAMES utf8mb4');

    $application = rep_volna_mista_application($pdo, $id);
    if (!$application) {
        http_response_code(404);
        echo 'Dotaznik nebyl nalezen.';
        exit;
    }

    $options = new Options();
    $options->setDefaultFont('DejaVu Sans');
    $options->setIsRemoteEnabled(false);
    $options->setIsHtml5ParserEnabled(true);

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml(rep_volna_mista_pdf_html($application), 'UTF-8');
    $dompdf->render();

    if (ob_get_length() !== false) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . rep_volna_mista_pdf_filename($application) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $dompdf->output();
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'PDF se nepodarilo vytvorit: ' . $e->getMessage();
}
