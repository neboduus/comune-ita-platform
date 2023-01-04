GET https://servizi.comune.bugliano.pi.it/lang/api/categories

HTTP/1.1 200



GET https://servizi.comune.bugliano.pi.it/lang/api/services

HTTP/1.1 200



GET https://servizi.comune.bugliano.pi.it/lang/api/calendars

HTTP/1.1 200



GET https://servizi.comune.bugliano.pi.it/lang/api/geographic-areas

HTTP/1.1 200



GET https://servizi.comune.bugliano.pi.it/lang/api/services-groups

HTTP/1.1 200


GET https://servizi.comune.bugliano.pi.it/lang/api/tenants/info

HTTP/1.1 200
[Asserts]
jsonpath "$.id" matches /^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/
jsonpath "$.name" == "Comune di Bugliano"

