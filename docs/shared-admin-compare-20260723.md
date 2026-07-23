# Shared Admin Compare

- Primary qanto_cz: /Users/tjirecek/www_dev/qanto_cz
- Secondary QRS_QANTO_CZ: /Users/tjirecek/www_dev/qrs-qanto_cz
- Secondary QANTOPLUS_CZ: /Users/tjirecek/www_dev/qantoplus_cz
- Generated: 2026-07-23 01:48:59
- Scope: shared/admin baseline only; project files are classified or scanned, not synchronization candidates.

## Overview

| Secondary | Area | Type | Files | SAME | DIFF | ONLY_PRIMARY | ONLY_SECONDARY | Note |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| QANTOPLUS_CZ | Explicit no-prefix asset review | PROJECT_OR_LEGACY | 1 | 0 | 0 | 1 | 0 | Manual asset classification review. |
| QANTOPLUS_CZ | Shared admin assets | SHARED_ADMIN | 14 | 14 | 0 | 0 | 0 | Shared admin assets outside /secure, e.g. secure.css and sec_*.js. |
| QANTOPLUS_CZ | Shared/System candidates | SHARED_SYSTEM | 87 | 87 | 0 | 0 | 0 | Shared/admin baseline inside repository structure. |
| QRS_QANTO_CZ | Explicit no-prefix asset review | PROJECT_OR_LEGACY | 1 | 0 | 0 | 1 | 0 | Manual asset classification review. |
| QRS_QANTO_CZ | Shared admin assets | SHARED_ADMIN | 14 | 14 | 0 | 0 | 0 | Shared admin assets outside /secure, e.g. secure.css and sec_*.js. |
| QRS_QANTO_CZ | Shared/System candidates | SHARED_SYSTEM | 87 | 87 | 0 | 0 | 0 | Shared/admin baseline inside repository structure. |

## Open Shared/Admin Differences

| Secondary | Area | Status | Path | Difference |
| --- | --- | --- | --- | --- |
| SAME | - | - | - | Zadne otevrene shared/admin rozdily. |

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

## Frontend Routing Decisions

| Path | Type | Decision |
| --- | --- | --- |
| `functions/settings.php` | FRONTEND_PROJECT_ROUTING | Projektovy frontend routing; QRS muze smerovat na `/cz/main`, verejne weby na `/cz`. Neporovnavat jako shared/admin. |

## Asset Decisions

| Pattern/Path | Type | Decision |
| --- | --- | --- |
| `assets/js/sec_rep_*` | PROJECT_ADMIN | Admin/project JS prefix; vyhodnocovat pred obecnym `sec_*`. |
| `assets/js/sec/rep_*` | PROJECT_ADMIN | Agendovy admin/project JS; neni soucast shared baseline. |
| `assets/css/sec_rep_*` | PROJECT_ADMIN | Admin/project CSS prefix; vyhodnocovat pred obecnym `rep_*`. |
| `assets/js/rep_*` | FRONTEND_PROJECT | Frontend/project JS prefix. |
| `assets/css/rep_*` | FRONTEND_PROJECT | Frontend/project CSS prefix. |
| `assets/js/sec_*` | SHARED_ADMIN | Admin/shared JS prefix. |
| `assets/js/sec/*` | SHARED_ADMIN | Admin/shared JS po agendach. |
| `assets/css/default.css` | FRONTEND_PROJECT | Frontend/projektove CSS; nesynchronizovat automaticky. |
| `assets/css/secure.css` | SHARED_ADMIN | Shared/admin CSS; ma zustat byte-identical napric projekty. |

## Explicit No-Prefix Asset Review

| Secondary | Type | Status | Path |
| --- | --- | --- | --- |
| QRS_QANTO_CZ | PROJECT_OR_LEGACY | ONLY_PRIMARY | `assets/js/default.js` |
| QANTOPLUS_CZ | PROJECT_OR_LEGACY | ONLY_PRIMARY | `assets/js/default.js` |

## Project Naming Scan

| Secondary | qanto_cz secure/inc/pages files | qanto_cz rep_* files | Secondary secure/inc/pages files | Secondary rep_* files |
| --- | --- | --- | --- | --- |
| QRS_QANTO_CZ | 40 | 11 | 70 | 41 |
| QANTOPLUS_CZ | 40 | 11 | 43 | 14 |

## Application / Library Versions

| Library | qanto_cz | Secondary | Version | Source | Usage / Note |
| --- | --- | --- | --- | --- | --- |
| PHPMailer | 7.1.1 | QRS_QANTO_CZ | 7.1.1 | `secure/lib/PHPMailer` | Local mail library used by shared mail helpers. |
| PHPMailer | 7.1.1 | QANTOPLUS_CZ | 7.1.1 | `secure/lib/PHPMailer` | Local mail library used by shared mail helpers. |

## Current Findings

| Area / Path | Finding |
| --- | --- |
| Shared/System candidates | Shared/system kandidati jsou shodni proti sekundarnim projektum. |
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
- Keep rep_*, sec_rep_*, project imports/exports, project cron scripts and project DB tables outside the shared baseline; they belong only in the relevant project layer.
