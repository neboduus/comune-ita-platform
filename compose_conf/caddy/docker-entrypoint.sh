#!/bin/sh
# 
# original Dockerfile set only a CMD
# https://github.com/caddyserver/caddy-docker/blob/master/Dockerfile.tmpl

umask 0002

if [ -n "$PHPFPM_HOST" ]; then
  echo "Customizing PHP-FPM host from php:9000 to $PHPFPM_HOST"

  sed -i "s/php:9000/${PHPFPM_HOST}/" /etc/caddy/Caddyfile
  if [ $? -gt 0 ]; then
    echo "Error customizing Caddyfile"
  fi

fi

exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile

