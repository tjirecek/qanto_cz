#!/usr/bin/env bash
set -euo pipefail

PRIMARY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SECONDARY_ROOTS=()

usage() {
  cat <<USAGE
Usage: $(basename "$0") [secondary-project-root ...]

Compares shared admin candidate files from primary qanto_cz against secondary
projects. When no secondary roots are passed, compares against:
  /Users/tjirecek/mamp/qrs-qanto_cz
  /Users/tjirecek/mamp/qantoplus_cz

Primary project is always the repository containing this script:
  $PRIMARY_ROOT
USAGE
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

if [[ "$#" -gt 0 ]]; then
  SECONDARY_ROOTS=("$@")
else
  SECONDARY_ROOTS=(
    "/Users/tjirecek/mamp/qrs-qanto_cz"
    "/Users/tjirecek/mamp/qantoplus_cz"
  )
fi

for root in "${SECONDARY_ROOTS[@]}"; do
  if [[ ! -d "$root" ]]; then
    echo "ERROR: secondary project not found: $root" >&2
    exit 1
  fi
done

project_label() {
  local root="$1"
  basename "$root" | tr '[:lower:]' '[:upper:]' | tr '-' '_'
}

file_status_code() {
  local secondary_root="$1"
  local secondary_label="$2"
  local rel="$3"
  local left="$PRIMARY_ROOT/$rel"
  local right="$secondary_root/$rel"

  if [[ -f "$left" && -f "$right" ]]; then
    if cmp -s "$left" "$right"; then
      printf 'SAME'
    else
      printf 'DIFF'
    fi
  elif [[ -f "$left" ]]; then
    printf 'ONLY_PRIMARY'
  elif [[ -f "$right" ]]; then
    printf 'ONLY_%s' "$secondary_label"
  else
    printf 'MISSING'
  fi
}

collect_files() {
  local root="$1"
  shift
  for rel in "$@"; do
    if [[ -d "$root/$rel" ]]; then
      (cd "$root" && find "$rel" -type f ! -name '.DS_Store' | sort)
    elif [[ -f "$root/$rel" ]]; then
      printf '%s\n' "$rel"
    fi
  done
}

collect_asset_files() {
  local root="$1"
  if [[ -d "$root/assets/css" ]]; then
    (cd "$root" && find assets/css -maxdepth 1 -type f ! -name '.DS_Store' | sort)
  fi
  if [[ -d "$root/assets/js" ]]; then
    (cd "$root" && find assets/js -maxdepth 1 -type f ! -name '.DS_Store' | sort)
  fi
}

collect_secure_function_files() {
  local root="$1"

  if [[ -d "$root/secure/functions" ]]; then
    (
      cd "$root"
      find secure/functions -maxdepth 1 -type f -name '*.php' \
        ! -name 'fun_rep_*' \
        ! -name 'pages_include_rep*' \
        | sort
    )
  fi

  if [[ -d "$root/secure/functions/ajax" ]]; then
    (
      cd "$root"
      find secure/functions/ajax -maxdepth 1 -type f -name '*.php' \
        ! -name 'rep_*' \
        | sort
    )
  fi
}

is_project_path() {
  local rel="$1"
  case "$rel" in
    secure/index.php)
      return 0
      ;;
    secure/inc/settings/cron_vypis.php)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

asset_class() {
  local rel="$1"
  case "$rel" in
    assets/js/sec_rep_*|assets/css/sec_rep_*)
      printf 'PROJECT_ADMIN'
      ;;
    assets/js/sec_*|assets/css/secure.css)
      printf 'SHARED_ADMIN'
      ;;
    assets/js/rep_*|assets/css/rep_*|assets/css/default.css)
      printf 'FRONTEND_PROJECT'
      ;;
    *)
      printf 'PROJECT_OR_LEGACY'
      ;;
  esac
}

asset_is_decided() {
  local rel="$1"
  case "$rel" in
    assets/js/sec_rep_*|assets/css/sec_rep_*|assets/js/rep_*|assets/css/rep_*|assets/js/sec_*|assets/css/default.css|assets/css/secure.css)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

print_section() {
  printf '\n## %s\n\n' "$1"
}

count_files() {
  local root="$1"
  local path="$2"
  find "$root/$path" -type f 2>/dev/null | wc -l | tr -d ' '
}

count_rep_page_files() {
  local root="$1"
  find "$root/secure/inc/pages" -type f -path '*/rep_*/*' 2>/dev/null | wc -l | tr -d ' '
}

phpmailer_version() {
  local root="$1"
  if [[ -f "$root/secure/lib/PHPMailer/VERSION" ]]; then
    cat "$root/secure/lib/PHPMailer/VERSION"
  else
    printf 'missing'
  fi
}

shared_roots=(
  "functions/bootstrap.php"
  "functions/fun_email_log.php"
  "functions/fun_mailer.php"
  "functions/fun_users_password_reset.php"
  "functions/settings.php"
  "functions/mysql_connect.php"
  "secure/index.php"
  "secure/inc/menu/mm_all.php"
  "secure/inc/menu/mm_dashboard.php"
  "secure/inc/menu/mm_system.php"
  "secure/inc/settings"
  "secure/inc/pages/news"
  "secure/inc/pages/stattexty"
  "secure/inc/pages/kontakty"
  "secure/scripts/apply_migration.php"
  "secure/lib/flmngr/flmngr.php"
)

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT
ALL_STATUS="$WORK_DIR/status.tsv"
PROJECT_SCAN="$WORK_DIR/project_scan.tsv"
PHPMAILER_SCAN="$WORK_DIR/phpmailer.tsv"
: > "$ALL_STATUS"
: > "$PROJECT_SCAN"
: > "$PHPMAILER_SCAN"

write_statuses_for_secondary() {
  local secondary_root="$1"
  local secondary_label="$2"
  local tmp_shared="$WORK_DIR/shared_$secondary_label.txt"
  local tmp_assets="$WORK_DIR/assets_$secondary_label.txt"

  {
    collect_files "$PRIMARY_ROOT" "${shared_roots[@]}"
    collect_files "$secondary_root" "${shared_roots[@]}"
    collect_secure_function_files "$PRIMARY_ROOT"
    collect_secure_function_files "$secondary_root"
  } | sort -u > "$tmp_shared"

  while IFS= read -r rel; do
    [[ -z "$rel" ]] && continue
    if is_project_path "$rel"; then
      continue
    fi
    printf '%s\t%s\t%s\t%s\t%s\n' \
      "$secondary_label" \
      "Shared/System candidates" \
      "SHARED_SYSTEM" \
      "$(file_status_code "$secondary_root" "$secondary_label" "$rel")" \
      "$rel" >> "$ALL_STATUS"
  done < "$tmp_shared"

  { collect_asset_files "$PRIMARY_ROOT"; collect_asset_files "$secondary_root"; } | sort -u > "$tmp_assets"

  while IFS= read -r rel; do
    [[ -z "$rel" ]] && continue
    local class
    class="$(asset_class "$rel")"
    if [[ "$class" == "SHARED_ADMIN" ]]; then
      printf '%s\t%s\t%s\t%s\t%s\n' \
        "$secondary_label" \
        "Shared admin assets" \
        "SHARED_ADMIN" \
        "$(file_status_code "$secondary_root" "$secondary_label" "$rel")" \
        "$rel" >> "$ALL_STATUS"
    elif ! asset_is_decided "$rel"; then
      printf '%s\t%s\t%s\t%s\t%s\n' \
        "$secondary_label" \
        "Explicit no-prefix asset review" \
        "$class" \
        "$(file_status_code "$secondary_root" "$secondary_label" "$rel")" \
        "$rel" >> "$ALL_STATUS"
    fi
  done < "$tmp_assets"

  printf '%s\t%s\t%s\t%s\t%s\n' \
    "$secondary_label" \
    "$(count_files "$PRIMARY_ROOT" "secure/inc/pages")" \
    "$(count_rep_page_files "$PRIMARY_ROOT")" \
    "$(count_files "$secondary_root" "secure/inc/pages")" \
    "$(count_rep_page_files "$secondary_root")" >> "$PROJECT_SCAN"

  printf '%s\t%s\t%s\n' \
    "$secondary_label" \
    "$(phpmailer_version "$PRIMARY_ROOT")" \
    "$(phpmailer_version "$secondary_root")" >> "$PHPMAILER_SCAN"
}

for root in "${SECONDARY_ROOTS[@]}"; do
  write_statuses_for_secondary "$root" "$(project_label "$root")"
done

echo "# Shared Admin Compare"
echo
echo "- Primary qanto_cz: $PRIMARY_ROOT"
for root in "${SECONDARY_ROOTS[@]}"; do
  echo "- Secondary $(project_label "$root"): $root"
done
echo "- Generated: $(date '+%Y-%m-%d %H:%M:%S')"
echo "- Scope: shared/admin baseline only; project files are classified or scanned, not synchronization candidates."

print_section "Overview"
echo '| Secondary | Area | Type | Files | SAME | DIFF | ONLY_PRIMARY | ONLY_SECONDARY | Note |'
echo '| --- | --- | --- | --- | --- | --- | --- | --- | --- |'
awk -F '\t' '
  {
    key = $1 SUBSEP $2 SUBSEP $3;
    label[key] = $1;
    area[key] = $2;
    type[key] = $3;
    files[key]++;
    if ($4 == "SAME") same[key]++;
    else if ($4 == "DIFF") diff[key]++;
    else if ($4 == "ONLY_PRIMARY") only_primary[key]++;
    else if ($4 ~ /^ONLY_/) only_secondary[key]++;
    else other[key]++;
  }
  END {
    for (key in files) {
      note = "";
      if (type[key] == "SHARED_SYSTEM") note = "Shared/admin baseline inside repository structure.";
      else if (type[key] == "SHARED_ADMIN") note = "Shared admin assets outside /secure, e.g. secure.css and sec_*.js.";
      else note = "Manual asset classification review.";
      printf "| %s | %s | %s | %d | %d | %d | %d | %d | %s |\n", label[key], area[key], type[key], files[key], same[key]+0, diff[key]+0, only_primary[key]+0, only_secondary[key]+0, note;
    }
  }
' "$ALL_STATUS" | sort

print_section "Open Shared/Admin Differences"
echo '| Secondary | Area | Status | Path | Difference |'
echo '| --- | --- | --- | --- | --- |'
awk -F '\t' '
  $4 != "SAME" && ($3 == "SHARED_SYSTEM" || $3 == "SHARED_ADMIN") {
    if ($4 == "DIFF") msg = "Exists in both projects but content differs; manual review required.";
    else if ($4 == "ONLY_PRIMARY") msg = "Exists only in qanto_cz; port only if it is shared/system.";
    else if ($4 ~ /^ONLY_/) msg = "Exists only in secondary project; import only if generic shared admin.";
    else msg = "Missing or unexpected status.";
    printf "| %s | %s | %s | `%s` | %s |\n", $1, $2, $4, $5, msg;
    found = 1;
  }
  END {
    if (!found) print "| SAME | - | - | - | Zadne otevrene shared/admin rozdily. |";
  }
' "$ALL_STATUS"

print_section "secure/functions Classification Rules"
echo '| Path/Mask | Type | Decision |'
echo '| --- | --- | --- |'
echo '| `secure/functions/fun_rep_*` | PROJECT_HELPER | Projektove helpery; neporovnavat jako shared/admin. |'
echo '| `secure/functions/pages_include_rep*` | PROJECT_ROUTER | Projektovy router; rozdily mezi projekty jsou ocekavane. |'
echo '| `secure/functions/fun_rep_cron.php` | PROJECT_CRON_LIST | Projektovy seznam cron uloh se sdilenym API. |'
echo '| `secure/index.php` | PROJECT_ADMIN_SHELL | Admin shell s projektovym brandingem, projektovym menu a volitelnymi project assety; neporovnavat jako byte-identical shared soubor bez refaktoru na konfiguraci. |'
echo '| `secure/inc/settings/cron_vypis.php` | PROJECT_CRON_SETTINGS | Projektovy vypis cron uloh; kazdy projekt muze mit vlastni cron seznam. |'
echo '| `secure/functions/*.php` ostatni | SHARED_SYSTEM | Ostatni soubory primo v secure/functions jsou shared/admin kandidati. |'
echo '| `secure/functions/ajax/rep_*` | PROJECT_AJAX | Projektove AJAX endpointy; neporovnavat jako shared/admin. |'
echo '| `secure/functions/ajax/*.php` ostatni | SHARED_SYSTEM | Ostatni AJAX endpointy jsou shared/admin kandidati. |'

print_section "Asset Decisions"
echo '| Pattern/Path | Type | Decision |'
echo '| --- | --- | --- |'
echo '| `assets/js/sec_rep_*` | PROJECT_ADMIN | Admin/project JS prefix; vyhodnocovat pred obecnym `sec_*`. |'
echo '| `assets/css/sec_rep_*` | PROJECT_ADMIN | Admin/project CSS prefix; vyhodnocovat pred obecnym `rep_*`. |'
echo '| `assets/js/rep_*` | FRONTEND_PROJECT | Frontend/project JS prefix. |'
echo '| `assets/css/rep_*` | FRONTEND_PROJECT | Frontend/project CSS prefix. |'
echo '| `assets/js/sec_*` | SHARED_ADMIN | Admin/shared JS prefix. |'
echo '| `assets/css/default.css` | FRONTEND_PROJECT | Frontend/projektove CSS; nesynchronizovat automaticky. |'
echo '| `assets/css/secure.css` | SHARED_ADMIN | Shared/admin CSS; ma zustat byte-identical napric projekty. |'

print_section "Explicit No-Prefix Asset Review"
echo '| Secondary | Type | Status | Path |'
echo '| --- | --- | --- | --- |'
awk -F '\t' '
  $2 == "Explicit no-prefix asset review" {
    printf "| %s | %s | %s | `%s` |\n", $1, $3, $4, $5;
    found = 1;
  }
  END {
    if (!found) print "| NONE | - | - | Zadne explicitni no-prefix assety k review. |";
  }
' "$ALL_STATUS"

print_section "Project Naming Scan"
echo '| Secondary | qanto_cz secure/inc/pages files | qanto_cz rep_* files | Secondary secure/inc/pages files | Secondary rep_* files |'
echo '| --- | --- | --- | --- | --- |'
awk -F '\t' '{ printf "| %s | %s | %s | %s | %s |\n", $1, $2, $3, $4, $5 }' "$PROJECT_SCAN"

print_section "Application / Library Versions"
echo '| Library | qanto_cz | Secondary | Version | Source | Usage / Note |'
echo '| --- | --- | --- | --- | --- | --- |'
awk -F '\t' '{ printf "| PHPMailer | %s | %s | %s | `secure/lib/PHPMailer` | Local mail library used by shared mail helpers. |\n", $2, $1, $3 }' "$PHPMAILER_SCAN"

print_section "Current Findings"
echo '| Area / Path | Finding |'
echo '| --- | --- |'
awk -F '\t' '
  BEGIN { shared_diff = 0; asset_diff = 0; }
  $3 == "SHARED_SYSTEM" && $4 != "SAME" { shared_diff++ }
  $3 == "SHARED_ADMIN" && $4 != "SAME" { asset_diff++ }
  END {
    if (shared_diff > 0) printf "| Shared/System candidates | %d otevrenych rozdilu proti sekundarnim projektum; review pred prenosem. |\n", shared_diff;
    else print "| Shared/System candidates | Shared/system kandidati jsou shodni proti sekundarnim projektum. |";
    if (asset_diff > 0) printf "| Shared admin assets | %d otevrenych asset rozdilu. |\n", asset_diff;
    else print "| Shared admin assets | Shared admin assety jsou shodne proti sekundarnim projektum. |";
  }
' "$ALL_STATUS"

print_section "Interpretation Rules"
echo '| Status / Type | Meaning |'
echo '| --- | --- |'
echo '| SAME | Byte-identical in qanto_cz and secondary project. |'
echo '| DIFF | Shown for shared rows; exists in both projects but content differs. |'
echo '| ONLY_PRIMARY | Exists only in qanto_cz; port to secondary only if shared/system. |'
echo '| ONLY_<SECONDARY> | Exists only in secondary project; import to qanto_cz only if generic shared admin. |'
echo '| PROJECT_* | Project file; do not synchronize as shared/system without explicit task. |'
echo '| FRONTEND_PROJECT | Frontend/project asset; outside shared admin synchronization. |'

print_section "Next Review Hints"
echo '- Treat DIFF in shared/system files and shared admin assets as manual review, not automatic overwrite.'
echo '- Treat ONLY_PRIMARY as candidate to port to secondary projects only when it is shared/system.'
echo '- Treat ONLY_<SECONDARY> as candidate to import into qanto_cz only when it is generic shared admin and not project-specific.'
echo '- Keep rep_*, sec_rep_*, project imports/exports, project cron scripts and project DB tables outside qanto_cz.'
