GET {{base_url}}/personal
[Cookies]
IFDBSessionID: {{remember_cookie}}
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "{{username}}"

# The restored session works without the cookie
GET {{base_url}}/personal
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "{{username}}"

GET {{base_url}}/logout
HTTP 200

GET {{base_url}}/personal
[Cookies]
IFDBSessionID: {{remember_cookie}}
HTTP 301
