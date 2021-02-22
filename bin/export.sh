#!/usr/bin/env bash

bin/docker-exec rm -rf vendor Scp/vendor || exit $?
bin/docker-exec composer install --no-dev || exit $?
bin/docker-exec bash -c "cd Scp; composer install --no-dev" || exit $?
OUTFILE=/scp/install.synergycp.com/bm/integration/whmcs/synergycp.zip
exec zip -r "$OUTFILE" . -x ".git*" ".idea/*" "bin/*" ".composer/*"
