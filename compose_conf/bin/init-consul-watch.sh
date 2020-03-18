if [[ -n $CONSUL_PREFIX ]]; then
    consul watch -type keyprefix -prefix ${CONSUL_PREFIX} /bin/tenants-config.sh &
fi
