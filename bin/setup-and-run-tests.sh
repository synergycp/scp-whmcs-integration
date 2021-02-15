#!/usr/bin/env sh

./bin/install.sh || exit $?
./bin/test.sh || exit $?