<?php
declare(strict_types=1);

/**
 * fun_news.php (PDO)
 * - bezpečné dotazy (prepared statements)
 * - sjednocení CZ/EN sloupců bez duplicit
 */

function news_typ_field(string $lang, string $fieldBase): string
{
    // povol jen cz/en
    $lang = ($lang === 'en') ? 'en' : 'cz';

    return match ($fieldBase) {
        'nazev' => ($lang === 'en') ? 'nazev_en' : 'nazev_cz',
        'popis' => ($lang === 'en') ? 'popis_en' : 'popis_cz',
        default => 'nazev_cz',
    };
}

function news_typ_name(string $lang, int $id): string
{
    global $pdo;

    $col = news_typ_field($lang, 'nazev');
    $stmt = $pdo->prepare("SELECT {$col} AS val FROM news_typ WHERE id = :id AND valid = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['val'] ?? '');
}

function news_typ_popis(string $lang, int $id): string
{
    global $pdo;

    $col = news_typ_field($lang, 'popis');
    $stmt = $pdo->prepare("SELECT {$col} AS val FROM news_typ WHERE id = :id AND valid = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['val'] ?? '');
}

function news_typ_color(int $id): string
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT color FROM news_typ WHERE id = :id AND valid = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['color'] ?? '');
}

/**
 * Výpis novinek (karty)
 * $category = 0 => všechny
 */
function news_vypis(int $category, string $lang): void
{
    global $pdo;

    if ($category === 0) {
        $sql = "SELECT *
                FROM news
                WHERE valid = 1
                  AND visible IN (1,2,3)
                ORDER BY datum DESC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        $sql = "SELECT *
                FROM news
                WHERE news_typ = :category
                  AND valid = 1
                  AND visible IN (1,2,3)
                ORDER BY datum DESC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':category' => $category]);
    }

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $typId = (int)($dev['news_typ'] ?? 0);

        $news_typ_popis = news_typ_popis($lang, $typId);
        $news_typ_color = news_typ_color($typId);

        $datum = format_date_www((string)($dev['datum'] ?? ''));

        // zatím bere CZ text jako dřív (můžeš později přepnout na EN variantu)
        $textCz = (string)($dev['text_cz'] ?? '');
        $perex  = mb_substr(strip_tags($textCz), 0, 130, 'UTF-8') . '…';

        $bg_color = ($news_typ_color !== '') ? 'bg-' . preg_replace('~[^a-z0-9_-]~i', '', $news_typ_color) : '';

        $urlCz   = (string)($dev['url_cz'] ?? '');
        $nazevCz = (string)($dev['nazev_cz'] ?? '');
        $tsu     = (string)($dev['ts_u'] ?? '');

        echo '
        <div class="col mb-4">
            <a class="underlineHover text-dark" href="/cz/news/' . htmlspecialchars($urlCz, ENT_QUOTES, 'UTF-8') . '">
            <div class="card h-100">
                <div class="card-header ' . htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8') . '">
                    <small class="text-light fw-semibold">' . htmlspecialchars($news_typ_popis, ENT_QUOTES, 'UTF-8') . '</small>
                </div>
                <div class="card-body pb-0">
                    <h5 class="card-title text-dark">' . htmlspecialchars($nazevCz, ENT_QUOTES, 'UTF-8') . '</h5>
                    <p class="card-text"><small>' . htmlspecialchars($perex, ENT_QUOTES, 'UTF-8') . '</small></p>
                </div>
                <div class="card-footer bg-dark">
                    <small class="text-light">Aktualizováno: ' . htmlspecialchars(format_datetime_www($tsu), ENT_QUOTES, 'UTF-8') . '</small>
                </div>
            </div>
            </a>
        </div>';
    }
}

/**
 * Detail novinky
 * $news_url = url_cz
 */
function news_detail(string $lang, string $news_url): void
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM news WHERE url_cz = :url LIMIT 1");
    $stmt->execute([':url' => $news_url]);
    $dev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dev) {
        echo '<div class="alert alert-warning">Novinka nebyla nalezena.</div>';
        return;
    }

    $typId = (int)($dev['news_typ'] ?? 0);

    $news_typ_popis = news_typ_popis($lang, $typId);
    $news_typ_color = news_typ_color($typId);
    $bg_color = ($news_typ_color !== '') ? 'bg-' . preg_replace('~[^a-z0-9_-]~i', '', $news_typ_color) : '';

    $nazev = (string)($dev['nazev_cz'] ?? '');
    $text  = (string)($dev['text_cz'] ?? '');
    $tsu   = (string)($dev['ts_u'] ?? '');
    $userU = (string)($dev['user_u'] ?? '');

    $catLink = '/cz/news?category=' . (int)$typId;

    echo '
        <div class="card">
            <div class="card-header ' . htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8') . ' fw-bold">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <a href="' . htmlspecialchars($catLink, ENT_QUOTES, 'UTF-8') . '" class="btn btn-danger btn-sm m-0 px-4 py-1">'
        . htmlspecialchars($news_typ_popis, ENT_QUOTES, 'UTF-8') .
        '</a>
                    </div>
                    <div class="col text-end">
                        <a href="/cz/news" class="btn btn-primary btn-sm m-0 px-4 py-1">&lt; zpět na výpis novinek</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h2 class="card-title">' . htmlspecialchars($nazev, ENT_QUOTES, 'UTF-8') . '</h2>
                <div class="card-text text-start">' . $text . '</div>
            </div>
            <div class="card-footer text-light bg-dark">
                Aktualizováno ' . htmlspecialchars(format_datetime_www($tsu), ENT_QUOTES, 'UTF-8') .
        ' uživatelem ' . htmlspecialchars(user_name($userU), ENT_QUOTES, 'UTF-8') . '
            </div>
        </div>';
}