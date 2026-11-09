#!/usr/bin/env bash
set -e

./vendor/bin/phpunit
(cd Scp && ./vendor/bin/phpunit)
