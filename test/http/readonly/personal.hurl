POST {{base_url}}/login
Content-Type: application/x-www-form-urlencoded
[FormParams]
userid: {{email}}
password: {{password}}
persist: 1
dest: personal
HTTP 301

GET {{base_url}}/personal
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "{{username}}"
body contains "{{game_title}}"
body contains "{{poll_title}}"
body contains "{{list_title}}"
body contains "Your Reviews"
body contains "viewgame?id={{game_id}}&review={{review_id}}"
