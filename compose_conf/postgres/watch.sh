#!/bin/bash

keyprefix=${CONSUL_PREFIX:-'sdc/stanzadelcittadino.it/tenants'}

consul watch -type keyprefix -prefix "$keyprefix" /bin/tenants-db.sh