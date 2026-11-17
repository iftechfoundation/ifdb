GET {{base_url}}/personal
HTTP 301
[Asserts]
header "Location" startsWith "login?dest=%2Fpersonal"

GET {{base_url}}/editgame?id={{game_id}}
HTTP 301
[Asserts]
header "Location" == "login?dest=%2Feditgame%3Fid%3D{{game_id}}"

GET {{base_url}}/editprofile
HTTP 301

POST {{base_url}}/setrating
[FormParams]
game: {{game_id}}
rating: 3
HTTP 401

POST {{base_url}}/login
[FormParams]
userid: {{email}}
password: {{password}}
HTTP 200

GET {{base_url}}/editprofile
HTTP 200
