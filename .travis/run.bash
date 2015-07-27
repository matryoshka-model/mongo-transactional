#!/usr/bin/env bash

mkdir -p $HOME/logs

declare -a mongo_ext=("1.4.5" "1.5.0" "1.5.1" "1.5.2" "1.5.3" "1.5.3" "1.5.5" "1.5.6" "1.5.7" "1.5.8" "1.6.0" "1.6.1" "1.6.2" "1.6.3" "1.6.4" "1.6.5" "1.6.6" "1.6.7" "1.6.8" "1.6.9")

echo "> UPDATING: pecl"
pecl channel-update pecl.php.net > $HOME/logs/common.log
echo "> UNINSTALLING: (travis-ci) mongo-ext"
pecl uninstall mongo > $HOME/logs/common.log

for version in "${mongo_ext[@]}"
do
    echo -e "\n"
    echo "> INSTALLING: mongo-ext ${version}"
    yes "no" | pecl install "mongo-${version}" > $HOME/logs/mong-${version}.log 2>&1

    echo "> INSTALLING: dependencies"
    composer install --quiet
    echo "> RUN: test against mongo-ext ${version}"
    php vendor/bin/phpunit
done