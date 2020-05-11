#!/bin/bash

[[ TRACE=1 ]] && set -x


export LD_LIBRARY_PATH=/opt/shibboleth/lib64:${LD_LIBRARY_PATH}

_SERVER_NAME=${SERVER_NAME:-"stanzadelcittadino.localtest.me"}
_ENTITY_ID=${ENTITY_ID:-"stanzadelcittadino.localtest.me"}
_PROJECT_URL=${PROJECT_BASE_URL:-"stanzadelcittadino.localtest.me"}
_ORGANIZATION=${ORGANIZATION:-"A Company Making Everything (A.C.M.E)"}


#
# setup TLS certificates
#
TLS_CERT="/etc/pki/tls/certs/server.crt"
TLS_KEY="/etc/pki/tls/private/server.key"
if [ ! -f ${TLS_CERT} ] && [ ! -f ${TLS_KEY} ]; then
    openssl req -x509 -nodes -days 3650 \
        -newkey rsa:2048 -keyout ${TLS_KEY} \
        -out ${TLS_CERT} \
        -subj "/CN=${_SERVER_NAME}"
fi


#
# setup SAML certificates
#

#SAML_CERT_DIR="/opt/shibboleth-sp/certs"
SAML_CERT_DIR="/etc/shibboleth"
SAML_CERT="${SAML_CERT_DIR}/sp-cert.pem"
SAML_KEY="${SAML_CERT_DIR}/sp-key.pem"
SAML_META_CERT="${SAML_CERT_DIR}/sp-meta-cert.pem"
SAML_META_KEY="${SAML_CERT_DIR}/sp-meta-key.pem"

pushd /etc/shibboleth
if [ ! -f ${SAML_CERT} ] && [ ! -f ${SAML_KEY} ]
then
    ./keygen.sh -f \
        -h "${_ORGANIZATION} - SAML Signature" \
        -o ${SAML_CERT_DIR}
fi

if [ ! -f ${SAML_META_CERT} ] && [ ! -f ${SAML_META_KEY} ]
then
    ./keygen.sh -f \
        -h "${_ORGANIZATION} - SAML Metadata Signature" \
        -o ${SAML_CERT_DIR} \
        -n "sp-meta"
fi
popd


#
# Try to generate dynamic metadata
#
pushd /opt/shibboleth-sp/metadata
sed \
    -e "s|%ENTITY_ID%|${_ENTITY_ID}|g" \
    -e "s|%PROJECT_URL%|${_PROJECT_URL}|g" \
    metadata.tpl > metadata.xml
popd


#
# killing existing shibd (if any)
#
shibd_pid=`pgrep shibd`
if [ ${shibd_pid} ]; then
    echo "Killing Shibboleth daemon (${shibd_pid})"
    kill -9 ${shibd_pid}
    rm -vf /var/run/shibboleth/*
fi

#
# run shibd
#
/usr/sbin/shibd -f

#
# killing existing httpd (if any)
#
httpd_pid=`pgrep httpd`
if [ ${httpd_pid} ]; then
    echo "Killing httpd daemon (${httpd_pid})"
    kill -9 ${httpd_pid}

fi


#
# run httpd
#
exec apachectl -DFOREGROUND
