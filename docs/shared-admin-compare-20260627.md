# Shared Admin Compare

- Primary qanto_cz: /Users/tjirecek/mamp/qanto_cz
- Secondary QRS_QANTO_CZ: /Users/tjirecek/mamp/qrs-qanto_cz
- Secondary QANTOPLUS_CZ: /Users/tjirecek/mamp/qantoplus_cz
- Generated: 2026-06-27 00:27:49
- Scope: shared/admin baseline only; project files are classified or scanned, not synchronization candidates.

## Overview

| Secondary | Area | Type | Files | SAME | DIFF | ONLY_PRIMARY | ONLY_SECONDARY | Note |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| QANTOPLUS_CZ | Shared admin assets | SHARED_ADMIN | 4 | 4 | 0 | 0 | 0 | Shared admin assets outside /secure, e.g. secure.css and sec_*.js. |
| QANTOPLUS_CZ | Shared/System candidates | SHARED_SYSTEM | 70 | 70 | 0 | 0 | 0 | Shared/admin baseline inside repository structure. |
| QRS_QANTO_CZ | Shared admin assets | SHARED_ADMIN | 4 | 4 | 0 | 0 | 0 | Shared admin assets outside /secure, e.g. secure.css and sec_*.js. |
| QRS_QANTO_CZ | Shared/System candidates | SHARED_SYSTEM | 70 | 69 | 1 | 0 | 0 | Shared/admin baseline inside repository structure. |

## Open Shared/Admin Differences

| Secondary | Area | Status | Path | Difference |
| --- | --- | --- | --- | --- |
| QRS_QANTO_CZ | Shared/System candidates | DIFF | `functions/settings.php` | Exists in both projects but content differs; manual review required. |

## secure/functions Classification Rules

| Path/Mask | Type | Decision |
| --- | --- | --- |
| `secure/functions/fun_rep_*` | PROJECT_HELPER | Projektove helpery; neporovnavat jako shared/admin. |
| `secure/functions/pages_include_rep*` | PROJECT_ROUTER | Projektovy router; rozdily mezi projekty jsou ocekavane. |
| `secure/functions/fun_rep_cron.php` | PROJECT_CRON_LIST | Projektovy seznam cron uloh se sdilenym API. |
| `secure/index.php` | PROJECT_ADMIN_SHELL | Admin shell s projektovym brandingem, projektovym menu a volitelnymi project assety; neporovnavat jako byte-identical shared soubor bez refaktoru na konfiguraci. |
| `secure/functions/*.php` ostatni | SHARED_SYSTEM | Ostatni soubory primo v secure/functions jsou shared/admin kandidati. |
| `secure/functions/ajax/rep_*` | PROJECT_AJAX | Projektove AJAX endpointy; neporovnavat jako shared/admin. |
| `secure/functions/ajax/*.php` ostatni | SHARED_SYSTEM | Ostatni AJAX endpointy jsou shared/admin kandidati. |

## Asset Decisions

| Pattern/Path | Type | Decision |
| --- | --- | --- |
| `assets/js/sec_rep_*` | PROJECT_ADMIN | Admin/project JS prefix; vyhodnocovat pred obecnym `sec_*`. |
| `assets/css/sec_rep_*` | PROJECT_ADMIN | Admin/project CSS prefix; vyhodnocovat pred obecnym `rep_*`. |
| `assets/js/rep_*` | FRONTEND_PROJECT | Frontend/project JS prefix. |
| `assets/css/rep_*` | FRONTEND_PROJECT | Frontend/project CSS prefix. |
| `assets/js/sec_*` | SHARED_ADMIN | Admin/shared JS prefix. |
| `assets/css/default.css` | FRONTEND_PROJECT | Frontend/projektove CSS; nesynchronizovat automaticky. |
| `assets/css/secure.css` | SHARED_ADMIN | Shared/admin CSS; ma zustat byte-identical napric projekty. |

## Explicit No-Prefix Asset Review

| Secondary | Type | Status | Path |
| --- | --- | --- | --- |
| NONE | - | - | Zadne explicitni no-prefix assety k review. |

## Project Naming Scan

| Secondary | qanto_cz secure/inc/pages files | qanto_cz rep_* files | Secondary secure/inc/pages files | Secondary rep_* files |
| --- | --- | --- | --- | --- |
| QRS_QANTO_CZ | 25 | 0 | 67 | 39 |
| QANTOPLUS_CZ | 25 | 0 | 38 | 13 |

## Application / Library Versions

| Library | qanto_cz | Secondary | Version | Source | Usage / Note |
| --- | --- | --- | --- | --- | --- |
| PHPMailer | 7.1.1 | QRS_QANTO_CZ | 7.1.1 | `secure/lib/PHPMailer` | Local mail library used by shared mail helpers. |
| PHPMailer | 7.1.1 | QANTOPLUS_CZ | 7.1.1 | `secure/lib/PHPMailer` | Local mail library used by shared mail helpers. |

## Current Findings

| Area / Path | Finding |
| --- | --- |
| Shared/System candidates | 1 otevrenych rozdilu proti sekundarnim projektum; review pred prenosem. |
| Shared admin assets | Shared admin assety jsou shodne proti sekundarnim projektum. |

## Interpretation Rules

| Status / Type | Meaning |
| --- | --- |
| SAME | Byte-identical in qanto_cz and secondary project. |
| DIFF | Shown for shared rows; exists in both projects but content differs. |
| ONLY_PRIMARY | Exists only in qanto_cz; port to secondary only if shared/system. |
| ONLY_<SECONDARY> | Exists only in secondary project; import to qanto_cz only if generic shared admin. |
| PROJECT_* | Project file; do not synchronize as shared/system without explicit task. |
| FRONTEND_PROJECT | Frontend/project asset; outside shared admin synchronization. |

## Next Review Hints

- Treat DIFF in shared/system files and shared admin assets as manual review, not automatic overwrite.
- Treat ONLY_PRIMARY as candidate to port to secondary projects only when it is shared/system.
- Treat ONLY_<SECONDARY> as candidate to import into qanto_cz only when it is generic shared admin and not project-specific.
- Keep rep_*, sec_rep_*, project imports/exports, project cron scripts and project DB tables outside qanto_cz.
