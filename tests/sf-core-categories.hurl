
GET https://servizi.comune.bugliano.pi.it/lang/api/categories

HTTP/1.1 200



POST https://servizi.comune.bugliano.pi.it/lang/api/auth
{
  "username": "{{admin_user}}",
  "password": "{{admin_pass}}"
}

HTTP/1.1 200
[Asserts]
jsonpath "$.token" != null
[Captures]
token: jsonpath "$.token"



POST https://servizi.comune.bugliano.pi.it/lang/api/categories
Authorization: Bearer {{token}}
{
  "name": "Marvin Test",
  "description": "Created during test, to be removed at the end"
}

HTTP/1.1 201
[Asserts]
jsonpath "$.id" matches /^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/
jsonpath "$.parent_id" == null
jsonpath "$.slug" matches "^marvin-test-\\d$"
jsonpath "$.description" == "Created during test, to be removed at the end"
[Captures]
temp_category_id: jsonpath "$.id"



GET https://servizi.comune.bugliano.pi.it/lang/api/categories/{{temp_category_id}}

HTTP/1.1 200
[Asserts]
jsonpath "$.id" matches /^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/ 
jsonpath "$.parent_id" == null
jsonpath "$.slug" == "marvin-test"
jsonpath "$.description" == "Created during test, to be removed at the end"



DELETE https://servizi.comune.bugliano.pi.it/lang/api/categories/{{temp_category_id}}
Authorization: Bearer {{token}}

HTTP/1.1 204
