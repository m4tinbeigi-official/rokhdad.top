#!/usr/bin/env sh
set -eu

DOMAIN="${DOMAIN:-rokhdad.top}"

curl -fsS "https://${DOMAIN}/" >/dev/null
curl -fsS "https://${DOMAIN}/api/health" >/dev/null

echo "Rokhdad healthcheck passed for ${DOMAIN}"

