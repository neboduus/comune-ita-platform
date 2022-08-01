#!/usr/bin/env bash
#
# Solo 1 tenant è supportato
#

[[ $DEBUG ]] && set -x


if [[ $INSTANCE_OVERRIDE == 'true' ]]; then

  echo "==> Override of instances is active: instances.yml will be overriden with environment variables"

  if [[ -z $APP_ENV ]]; then
    echo "  APP_ENV not set, assuming prod for security reason"
    APP_ENV=prod
  fi

  instance_file="config/instances_${APP_ENV}.yml"

  available_names='
codice_meccanografico
identifier
app_locales
database_name
login_route
deda_login_client_id
deda_login_secret
cas_login_url
cas_validation_url
single_logout_url'


  # check required parameters
  if [[ -z $INSTANCE_address ]]; then
    echo "  Missing instance address, cannot continue without a variable INSTANCE_address"
    missing_values=true
  else
    # ensure lowercase address, http is case-insensitive
    INSTANCE_address=$(echo $INSTANCE_address | tr '[:upper:]' '[:lower:]')
  fi
  if [[ -z $INSTANCE_identifier ]]; then
    echo "  Missing instance identifier, cannot continue without a variable INSTANCE_identifier"
    missing_values=true
  fi
  if [[ -z $INSTANCE_database ]]; then
    echo "  Missing instance database name, cannot continue without a variable INSTANCE_database"
    missing_values=true
  fi
  if [[ -z $INSTANCE_codice_meccanografico ]]; then
    echo "  Missing codice_meccanografico, cannot continue without a variable INSTANCE_codice_meccanografico"
    missing_values=true
  fi

  [[ $missing_values == 'true' ]] && exit 1

  # start file override (pay attention to the single '>')
  echo "instances:" > $instance_file
  echo "  $INSTANCE_address:" >> $instance_file

  # override other variables if defined
  for name in $available_names; do
    declare varname=INSTANCE_${name}
    if [[ -n ${!varname} ]]; then
      echo "    $name: ${!varname}" >> $instance_file
    fi
  done

  # add default values, if needed
  if [[ -z $INSTANCE_login_route ]]; then
      echo "    login_route: login_pat" >> $instance_file
  fi
  if [[ -z $INSTANCE_protocollo ]]; then
      echo "    protocollo: dummy" >> $instance_file
  fi

fi

echo "  Configuration successful, written file $instance_file, enjoy the platform at https://${INSTANCE_address}"
