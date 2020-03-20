#!/bin/bash
[[ -n $DEBUG ]] && set -x
# Lo script viene eseguito ogni volta che si registra una modifica in consul
# nel sottoalbero di $consul_prefix 
# si è reso necessario perché per ogni tenant bisogna creare un file diverso
# contenente il prefisso specifico di quel tenant

config_tmpl=app/config/templates/config_prod.yml.tmpl
tmp_config_path=/tmp/tenants
final_config_path=${TENANTS_CONFIG_PATH:?'La variable TENANTS_CONFIG_PATH deve essere valorizzata'}

for dir in $tmp_config_path $final_config_path; do
        [[ ! -d $dir ]] && mkdir -p $dir
done

consul_prefix=$CONSUL_PREFIX
# prendo la lista dei tenants configurati sotto il prefisso
# nota bene lo '/' alla fine del prefisso, senza di quello non restituisce
# il sottoalbero. Viene restituita una cosa tipo:
# prefix/item1/
# prefix/item2/
# prefix/item3/
#
# [ rimosso: dalla lista viene rimossa la prima riga contenente il prefisso stesso (sed 1d)]
# e il prefisso, in modo da lasciare solo il nome della folder
tenants=$(consul kv get -keys ${consul_prefix}/ | sed "s#${consul_prefix}/##" | sed 's#/$##')

if [[ -n $DEBUG ]]; then
	echo "Tenants: ${tenants}"
	consul_template_logs='-log-level=debug'
        rsync_quiet=''
else
        rsync_quiet='--quiet'
	consul_template_logs='-log-level=info'
fi


# pre ogni tenant si prepara il file di configurazione da usare per renderizzare
# quello definitivo
for tenant in $tenants; do
        echo "==> Configuring tenant ${tenant}..."

        current_env=$ENV
        if [[ $ENV != 'DEV' ]]; then
                tenant_env=$(consul kv get -keys ${consul_prefix}/${tenant}/config/env)
                if [[ $tenant_env == "dev" ]];
                        current_env=DEV
                fi
        fi

	template_param="-template ${config_tmpl}:$tmp_config_path/${tenant}/config_prod.yml"
	TENANT_TREE=${consul_prefix}/${tenant} \
	TENANT=${tenant} \
        ENV=$current_env \
        consul-template -once $consul_template_logs $template_param 2>&1
done

if [[ $CLEANUP_TENANTS_PATH == 'true' ]]; then
        delete=--delete-after
else
        delete=''
fi

rsync ${rsync_quiet} -avz ${delete} ${tmp_config_path}/ ${final_config_path}/

echo "==> Finished tenants configuration written in ${final_config_path}!"

