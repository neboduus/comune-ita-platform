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



POST https://servizi.comune.bugliano.pi.it/lang/api/folders
Authorization: Bearer {{token}}
{
  "owner": "1c340b05-0808-4dbe-ad12-81ebbf3b6abf",
  "title": "Marvin wallpapers for Vittorino",
  "description": "Created during test, to be removed at the end by Marvin"
}

HTTP/1.1 201
[Asserts]
jsonpath "$.id" != null
jsonpath "$.owner" == "1c340b05-0808-4dbe-ad12-81ebbf3b6abf"
jsonpath "$.title" == "Marvin wallpapers for Vittorino"
jsonpath "$.description" == "Created during test, to be removed at the end by Marvin"
jsonpath "$.created_at" != null
jsonpath "$.updated_at" != null
[Captures]
temp_id: jsonpath "$.id"



GET https://servizi.comune.bugliano.pi.it/lang/api/folders/{{temp_id}}
Authorization: Bearer {{token}}

HTTP/1.1 200
[Asserts]
jsonpath "$.id" == {{temp_id}}
jsonpath "$.owner" == "1c340b05-0808-4dbe-ad12-81ebbf3b6abf"
jsonpath "$.title" == "Marvin wallpapers for Vittorino"
jsonpath "$.description" == "Created during test, to be removed at the end by Marvin"
jsonpath "$.created_at" != null
jsonpath "$.updated_at" != null


GET https://servizi.comune.bugliano.pi.it/lang/api/folders
Authorization: Bearer {{token}}

HTTP/1.1 200



DELETE https://servizi.comune.bugliano.pi.it/lang/api/folders/{{temp_id}}
Authorization: Bearer {{token}}

HTTP/1.1 204

