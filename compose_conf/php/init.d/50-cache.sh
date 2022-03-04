#!/bin/bash

[[ ${DEBUG} == 1 ]] && set -x


if [[ ${SYMFONY_ENV} != 'dev' ]]; then

  echo "==> Cache warmup"
  for instance in $(./bin/tenants); do
     echo " * $instance"
     php bin/console --no-interaction --instance $instance cache:warmup
  done

fi
