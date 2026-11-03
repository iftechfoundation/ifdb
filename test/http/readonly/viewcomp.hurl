GET {{base_url}}/viewcomp?id={{comp_id}}
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "{{comp_title}}"
body contains "{{comp_title}}"
body contains "{{comp_desc}}"
body contains "{{game_title}}"
body contains "viewgame?id={{game_id}}"
