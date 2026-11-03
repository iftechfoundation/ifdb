GET {{base_url}}/viewgame?id={{game_id}}
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "{{game_title}}"
body contains "{{game_title}}"
body contains "{{author}}"
body contains "{{review_summary}}"
body contains "{{review_body}}"
body contains "hurl-smoke"
body contains "{{list_title}}"

# Comments live on the review permalink, not the main game page.
GET {{base_url}}/viewgame?id={{game_id}}&review={{review_id}}
HTTP 200
[Asserts]
body contains "{{comment_body}}"
body contains "{{review_summary}}"
