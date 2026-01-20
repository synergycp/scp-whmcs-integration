#!/usr/bin/env bash

bin/docker-exec rm -rf vendor Scp/vendor || exit $?
bin/docker-exec composer install --no-dev || exit $?
bin/docker-exec bash -c "cd Scp; composer install --no-dev" || exit $?
OUTFILE=/scp/install.synergycp.com/bm/integration/whmcs/synergycp.zip
rm -f "$OUTFILE"
zip -r "$OUTFILE" . -x "*.git*" "*.idea/*" "bin/*" ".composer/*" "*CLAUDE.md" "*.claude/*" || exit $?
echo "Output: $OUTFILE"
