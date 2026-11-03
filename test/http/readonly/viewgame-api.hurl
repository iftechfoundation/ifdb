GET {{base_url}}/viewgame?json&id={{game_id}}
HTTP 200
[Asserts]
jsonpath "$.bibliographic.title" == "{{game_title}}"
jsonpath "$.bibliographic.author" == "{{author}}"
jsonpath "$.ifdb.tuid" == "{{game_id}}"
jsonpath "$.ifdb.starRating" == 5
jsonpath "$.ifdb.playTimeInMinutes" == 90
jsonpath "$.ifdb.tags[*].name" contains "hurl-smoke"
