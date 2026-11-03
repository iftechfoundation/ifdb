POST {{base_url}}/login
Content-Type: application/x-www-form-urlencoded
[FormParams]
userid: {{email}}
password: {{password}}
persist: 1
dest: allupdates
HTTP 301

GET {{base_url}}/allupdates
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "Catalog Updates"
body contains "{{game_title}}"
body contains "viewgame?id={{game_id}}"
