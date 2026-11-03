GET {{base_url}}/search?searchfor={{game_title_encoded}}
HTTP 200
[Asserts]
body contains "{{game_title}}"
body contains "viewgame?id={{game_id}}"

GET {{base_url}}/search?searchfor={{poll_title_encoded}}&poll
HTTP 200
[Asserts]
body contains "{{poll_title}}"
body contains "poll?id={{poll_id}}"

GET {{base_url}}/search?searchfor={{list_title_encoded}}&list
HTTP 200
[Asserts]
body contains "{{list_title}}"
body contains "viewlist?id={{list_id}}"

GET {{base_url}}/search?searchfor={{comp_title_encoded}}&comp
HTTP 200
[Asserts]
body contains "{{comp_title}}"
body contains "viewcomp?id={{comp_id}}"

GET {{base_url}}/search?searchfor={{username}}&member
HTTP 301
[Asserts]
header "Location" contains "showuser?id={{user_id}}"

GET {{base_url}}/search?searchfor=hurl-smoke&tag
HTTP 200
[Asserts]
body contains "hurl-smoke"
body contains "tag:hurl-smoke"
