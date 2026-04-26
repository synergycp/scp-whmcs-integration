#!/usr/bin/env bash
set -euo pipefail

: "${AWS_ACCESS_KEY_ID:?AWS_ACCESS_KEY_ID (R2 access key) is required}"
: "${AWS_SECRET_ACCESS_KEY:?AWS_SECRET_ACCESS_KEY (R2 secret key) is required}"
: "${CLOUDFLARE_ACCOUNT_ID:?CLOUDFLARE_ACCOUNT_ID is required}"
R2_BUCKET="${R2_BUCKET:-distribution}"
export AWS_DEFAULT_REGION="${AWS_DEFAULT_REGION:-auto}"
export AWS_EC2_METADATA_DISABLED=true

bin/docker-exec rm -rf vendor Scp/vendor
bin/docker-exec composer install --no-dev
bin/docker-exec bash -c "cd Scp; composer install --no-dev"

OUTDIR="$(mktemp -d)"
trap 'rm -rf "$OUTDIR"' EXIT
OUTFILE="$OUTDIR/synergycp.zip"

zip -r "$OUTFILE" . -x "*.git*" "*.idea/*" "bin/*" ".composer/*" "*CLAUDE.md" "*.claude/*"

aws s3 cp "$OUTFILE" \
  "s3://${R2_BUCKET}/bm/integration/whmcs/synergycp.zip" \
  --endpoint-url "https://${CLOUDFLARE_ACCOUNT_ID}.r2.cloudflarestorage.com" \
  --content-type application/zip \
  --no-progress

echo "Uploaded: s3://${R2_BUCKET}/bm/integration/whmcs/synergycp.zip"
