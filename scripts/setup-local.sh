#!/usr/bin/env bash
set -euo pipefail

ADMIN_EMAIL="${ADMIN_EMAIL:-admin@frotacerta.local}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-ChangeMe123!}"

docker compose up -d postgres redis
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan fleet:bootstrap \
  --email="${ADMIN_EMAIL}" \
  --password="${ADMIN_PASSWORD}"
docker compose run --rm app php artisan test

cat <<EOF

FrotaCerta local setup completed.

Admin:
  email: ${ADMIN_EMAIL}
  password: ${ADMIN_PASSWORD}

Run the server:
  docker compose run --rm --service-ports app php artisan serve --host=0.0.0.0 --port=8000

Open:
  http://localhost:8000
  http://localhost:8000/api/health

EOF
