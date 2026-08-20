#!/usr/bin/env bash
set -euo pipefail

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

if [[ -z "${DATABASE_URL_UNPOOLED:-}" && -z "${DATABASE_URL:-}" && -z "${DB_URL:-}" ]]; then
  cat >&2 <<'EOF'
Missing Neon connection string.

Run with:
  DATABASE_URL_UNPOOLED='postgresql://user:password@host.neon.tech/dbname?sslmode=require' ./scripts/setup-neon.sh

EOF
  exit 1
fi

export DB_CONNECTION=pgsql
export DB_URL="${DATABASE_URL_UNPOOLED:-${DATABASE_URL:-${DB_URL}}}"
export DB_SSLMODE="${DB_SSLMODE:-require}"

ADMIN_EMAIL="${ADMIN_EMAIL:-admin@frotacerta.local}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(openssl rand -base64 24)}"

docker compose run --rm \
  -e DB_CONNECTION \
  -e DB_URL \
  -e DB_SSLMODE \
  app php artisan migrate --force

docker compose run --rm \
  -e DB_CONNECTION \
  -e DB_URL \
  -e DB_SSLMODE \
  app php artisan fleet:bootstrap \
    --email="${ADMIN_EMAIL}" \
    --password="${ADMIN_PASSWORD}"

cat <<EOF

Neon setup completed.

Admin:
  email: ${ADMIN_EMAIL}
  password: ${ADMIN_PASSWORD}

EOF
