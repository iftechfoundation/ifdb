GET {{base_url}}/
HTTP 200
[Asserts]
body contains "{{game_title}}"
body contains "{{poll_title}}"
body contains "{{list_title}}"
body contains "{{comp_title}}"
body contains "{{username}}"
body contains "{{review_summary}}"
