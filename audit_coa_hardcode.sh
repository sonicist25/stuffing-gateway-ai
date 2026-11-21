#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-application}"
CONFIG_RE='application/config/finance_coa\.php'

if ! command -v rg >/dev/null 2>&1; then
  echo "ERROR: ripgrep (rg) is required." >&2
  exit 2
fi

echo "== Known legacy/wrong COA literals outside central config =="
LEGACY='(1200|2100|2130|2150|4101|4102|4103|5100)'
legacy_hits="$(rg -n --glob '*.php' "['\"]${LEGACY}['\"]" "$ROOT" 2>/dev/null | grep -Ev "$CONFIG_RE" || true)"
if [[ -n "$legacy_hits" ]]; then
  printf '%s\n' "$legacy_hits"
  echo
  echo "FAIL: legacy COA hardcode found."
else
  echo "OK: none found."
fi

echo
echo "== Central COA codes hardcoded outside central config =="
CURRENT='(1120|1270|1271|1310|1610|1661|2110|2310|2340|4111|4112|4113|5111)'
current_hits="$(rg -n --glob '*.php' "['\"]${CURRENT}['\"]" "$ROOT" 2>/dev/null | grep -Ev "$CONFIG_RE" || true)"
if [[ -n "$current_hits" ]]; then
  printf '%s\n' "$current_hits"
  echo
  echo "WARN: move these references to semantic keys in finance_coa.php."
else
  echo "OK: none found."
fi

echo
echo "== Broad 4-digit literal candidates (manual review) =="
rg -n --glob '*.php' "['\"][1-5][0-9]{3}['\"]" "$ROOT" 2>/dev/null | grep -Ev "$CONFIG_RE" || true

if [[ -n "$legacy_hits" ]]; then
  exit 1
fi
