#!/usr/bin/env bash
set -euo pipefail

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

if [[ -z "${DATABASE_URL:-}" && -z "${DB_URL:-}" ]]; then
  cat >&2 <<'EOF'
Missing Neon connection string.

Run with:
  DATABASE_URL='postgresql://user:password@host.neon.tech/dbname?sslmode=require' ./scripts/vercel-set-neon-env.sh

EOF
  exit 1
fi

PROJECT_SCOPE="${PROJECT_SCOPE:-geevheits-projects}"
VERCEL_PREVIEW_BRANCH="${VERCEL_PREVIEW_BRANCH:-main}"
NEON_URL="${DATABASE_URL:-${DB_URL}}"

upsert_preview_env() {
  local name="$1"
  local value="$2"

  vercel env add "${name}" preview \
    "${VERCEL_PREVIEW_BRANCH}" \
    --scope "${PROJECT_SCOPE}" \
    --force \
    --yes \
    --value "${value}" >/dev/null

  echo "Configured ${name}"
}

upsert_preview_env DB_CONNECTION pgsql
upsert_preview_env DB_URL "${NEON_URL}"
upsert_preview_env DB_SSLMODE require
upsert_preview_env SESSION_DRIVER array
upsert_preview_env CACHE_STORE array
upsert_preview_env QUEUE_CONNECTION sync

cat <<EOF

Vercel preview environment configured for Neon.

Next:
  vercel deploy . -y --scope ${PROJECT_SCOPE}

EOF
