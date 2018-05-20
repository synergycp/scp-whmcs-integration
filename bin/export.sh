#!/usr/bin/env bash

OUTFILE=/scp/install.synergycp.com/bm/integration/whmcs/synergycp.zip
zip -r "$OUTFILE" . -x ".git*" ".idea/*" "bin/*"
