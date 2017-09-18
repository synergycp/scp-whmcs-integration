#!/usr/bin/env bash

tar --transform 's|^|/synergycp/|' -zcvf /scp/install.synergycp.com/bm/integration/whmcs.tgz *
