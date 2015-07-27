#!/usr/bin/env bash

mkdir -p $HOME/logs

declare -a mongo_ext=("1.6.9")

echo "> UPDATING: pecl"
pecl channel-update pecl.php.net > $HOME/logs/common.log
echo "> UNINSTALLING: (travis-ci) mongo-ext"
pecl uninstall mongo > $HOME/logs/common.log

cat $HOME/logs/commong.log

for version in "${mongo_ext[@]}"
do
    echo -e "\n"
    echo "> INSTALLING: mongo-ext ${version}"
    yes "no" | pecl install "mongo-${version}" > $HOME/logs/mong-${version}.log 2>&1

    cat $HOME/logs/mong-${version}.log

    echo "> INSTALLING: dependencies"
    composer install --quiet
    echo "> RUN: test against mongo-ext ${version}"
    php vendor/bin/phpunit
done