GET {{base_url}}/search?json&game&searchfor={{game_title_encoded}}
HTTP 200
[Asserts]
jsonpath "$.games[*].tuid" contains "{{game_id}}"
jsonpath "$.games[*].title" contains "{{game_title}}"

GET {{base_url}}/search?json&poll&searchfor={{poll_title_encoded}}
HTTP 200
[Asserts]
jsonpath "$.polls[*].tuid" contains "{{poll_id}}"
jsonpath "$.polls[*].title" contains "{{poll_title}}"

GET {{base_url}}/search?json&list&searchfor={{list_title_encoded}}
HTTP 200
[Asserts]
jsonpath "$.lists[*].tuid" contains "{{list_id}}"
jsonpath "$.lists[*].title" contains "{{list_title}}"

GET {{base_url}}/search?json&comp&searchfor={{comp_title_encoded}}
HTTP 200
[Asserts]
jsonpath "$.competitions[*].tuid" contains "{{comp_id}}"
jsonpath "$.competitions[*].title" contains "{{comp_title}}"

GET {{base_url}}/search?json&member&searchfor={{username}}
HTTP 200
[Asserts]
jsonpath "$.members[*].tuid" contains "{{user_id}}"
jsonpath "$.members[*].name" contains "{{username}}"

GET {{base_url}}/search?json&tag&searchfor=hurl-smoke
HTTP 200
[Asserts]
jsonpath "$.tags[*].id" contains "hurl-smoke"
