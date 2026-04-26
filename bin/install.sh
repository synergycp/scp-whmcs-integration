#!/usr/bin/env sh

composer install --no-dev
cd Scp
composer install --no-dev
cd ..
