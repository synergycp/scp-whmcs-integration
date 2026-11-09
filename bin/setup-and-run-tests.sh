#!/usr/bin/env sh
# Install dependencies including dev tools, then run the test suites.
set -e

composer install --no-interaction --no-progress
(cd Scp && composer install --no-interaction --no-progress)

./bin/test.sh
