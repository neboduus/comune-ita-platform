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
jsonpath "$.id" == "60e35f02-1509-408c-b101-3b1a28109329"
jsonpath "$.name" == "Comune di Bugliano"

