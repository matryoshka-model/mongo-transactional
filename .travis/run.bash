#!/usr/bin/env bash

mkdir -p $HOME/logs

declare -a mongo_ext=("1.6.9")

echo "> UPDATING: pecl"
pecl channel-update pecl.php.net > $HOME/logs/common.log
echo "> UNINSTALLING: (travis-ci) mongo"
pecl uninstall mongo > $HOME/logs/common.log

for version in "${mongo_ext[@]}"
do
    echo -e "\n"
    echo "> INSTALLING: mongo-ext ${version}"
    yes "no" | pecl install "mongo-${version}" > $HOME/logs/mong-${version}.log 2>&1

    echo "> INSTALLING: dependencies"
    composer install --quiet
    echo "> RUN: test against mongo-ext ${version}"
    vendor/bin/phpunit
done