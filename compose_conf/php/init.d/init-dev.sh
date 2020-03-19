#!/bin/bash

if [[ $SYMFONY_ENV == 'dev' ]]; then
  echo "==> SYMFONY_ENV=dev, using 'app_dev.php'"
  if [[ -f web/app_dev.php ]]; then
    mv -v web/app.php web/app_prod.php
    mv -v web/app_dev.php web/app.php
  fi
else
  if [[ -f web/app_prod.php ]]; then
    echo "==> SYMFONY_ENV!=dev, restoring 'app.php'"
    mv -v web/app.php web/app_dev.php
    mv -v web/app_prod.php web/app.php
  fi
fi

