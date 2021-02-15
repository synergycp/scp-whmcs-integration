#!/usr/bin/env sh

docker run --rm -v $(pwd)/:/root -w /root epcallan/php7-testing-phpunit:7.2-phpunit7 /root/bin/setup-and-run-tests.sh
