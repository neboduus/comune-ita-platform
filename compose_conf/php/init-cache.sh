#!/usr/bin/env bash

echo "Clear cache"
php bin/console cache:clear

echo "Install asset"
php bin/console asset:install

echo "Warmup"
php bin/console cache:warmup
