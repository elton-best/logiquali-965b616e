#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

has_rg=0
if command -v rg >/dev/null 2>&1; then
  has_rg=1
fi

search() {
  local pattern="$1"
  shift
  if [[ $has_rg -eq 1 ]]; then
    rg -n "$pattern" "$@"
  else
    grep -RInE "$pattern" "$@"
  fi
}

echo "[check:migration:backend] Verifying legacy permission aliases are not reintroduced in runtime code..."
if [[ $has_rg -eq 1 ]]; then
  search "['\"][a-z0-9_.-]+\\.(view|edit)['\"]" app routes \
    -g '!**/*.md' >/tmp/check_migration_backend_permissions.txt || true
else
  search "['\"][a-z0-9_.-]+\\.(view|edit)['\"]" app routes \
    --exclude='*.md' >/tmp/check_migration_backend_permissions.txt || true
fi
if [[ -s /tmp/check_migration_backend_permissions.txt ]]; then
  echo "[check:migration:backend] ERROR: found legacy permission aliases in runtime code:"
  cat /tmp/check_migration_backend_permissions.txt
  exit 1
fi

echo "[check:migration:backend] Verifying tests do not rely on legacy permission aliases..."
if [[ $has_rg -eq 1 ]]; then
  search "['\"][a-z0-9_.-]+\\.(view|edit)['\"]" tests \
    -g '!**/*.md' >/tmp/check_migration_backend_test_permissions.txt || true
else
  search "['\"][a-z0-9_.-]+\\.(view|edit)['\"]" tests \
    --exclude='*.md' >/tmp/check_migration_backend_test_permissions.txt || true
fi
if [[ -s /tmp/check_migration_backend_test_permissions.txt ]]; then
  echo "[check:migration:backend] ERROR: found legacy permission aliases in tests:"
  cat /tmp/check_migration_backend_test_permissions.txt
  exit 1
fi

echo "[check:migration:backend] OK"
