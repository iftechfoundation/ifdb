# Register, activate via adminops, then exercise write paths as the smoke user.

# --- Register ---
POST {{base_url}}/newuser
Content-Type: application/x-www-form-urlencoded
[FormParams]
userid: {{email}}
userid2: {{email}}
password: {{password}}
password2: {{password}}
dname: {{username}}
g-recaptcha-response: test
HTTP 200
[Asserts]
xpath "normalize-space(//h1)" == "Registration Confirmed"

# --- Admin login ---
POST {{base_url}}/login
Content-Type: application/x-www-form-urlencoded
[FormParams]
userid: ifdbadmin@ifdb.org
password: secret
persist: 1
HTTP 200

# --- Find smoke user ---
GET {{base_url}}/adminops?finduser={{email}}
HTTP 200
[Captures]
user_id: regex "adminops\\?user=([a-z0-9]+)"
[Asserts]
body contains "{{email}}"
body contains "adminops?user="

# --- Activate smoke user ---
POST {{base_url}}/adminops
Content-Type: application/x-www-form-urlencoded
[FormParams]
user: {{user_id}}
acctstat: A
prostat:
sandbox: 0
apply: Apply
HTTP 200
[Asserts]
body contains "The user record has been updated in the database."
body contains "id=\"acctstat-A\" value=\"A\""

# --- Smoke user login ---
POST {{base_url}}/login
Content-Type: application/x-www-form-urlencoded
[FormParams]
userid: {{email}}
password: {{password}}
persist: 1
dest: personal
HTTP 301
[Asserts]
header "Location" contains "personal"

# --- Personal page ---
GET {{base_url}}/personal
HTTP 200
[Asserts]
xpath "normalize-space(//title)" contains "{{username}}"

# --- Create game via putific ---
POST {{base_url}}/putific
[MultipartFormData]
username: {{email}}
password: {{password}}
requireIFID: no
ifiction: file,{{ifiction_path}}; application/xml
HTTP 200
[Captures]
game_id: xpath "string(//*[local-name()='tuid'])"
[Asserts]
xpath "count(//*[local-name()='ok'])" == 1
xpath "count(//*[local-name()='created'])" == 1

# --- Review preview ---
POST {{base_url}}/review?id={{game_id}}
Content-Type: application/x-www-form-urlencoded
[FormParams]
rating: 5
summary: {{review_summary}}
reviewbody: {{review_body}}
tags:
preview: Preview
HTTP 200
[Asserts]
xpath "normalize-space(//title)" == "Preview"

# --- Review publish ---
POST {{base_url}}/review?id={{game_id}}
Content-Type: application/x-www-form-urlencoded
[FormParams]
rating: 5
summary: {{review_summary}}
reviewbody: {{review_body}}
tags:
save: Publish!
HTTP 301
[Asserts]
header "Location" contains "viewgame?id={{game_id}}"

# --- Estimated play time ---
POST {{base_url}}/settime
Content-Type: application/x-www-form-urlencoded
[FormParams]
game: {{game_id}}
newtime: 90
note: hurl smoke
HTTP 200
[Asserts]
jsonpath "$.label" == "Saved"

# --- Create poll ---
POST {{base_url}}/poll
Content-Type: application/x-www-form-urlencoded
[FormParams]
title: {{poll_title}}
desc: {{poll_desc}}
keywords: hurl smoke
anonymous: public
newPollGo: Create It!
HTTP 200
[Captures]
poll_id: regex "poll\\?id=([a-z0-9]+)"
[Asserts]
xpath "normalize-space(//h1)" == "New poll created!"

# --- Poll vote ---
GET {{base_url}}/poll?submitVote&id={{poll_id}}&gameID={{game_id}}&quickQuote=Great&voteDesc=Hurl%20smoke%20vote
HTTP 200
[Asserts]
body contains "Thanks for voting!"

# --- List preview ---
POST {{base_url}}/editlist?id=new
Content-Type: application/x-www-form-urlencoded
[FormParams]
title: {{list_title}}
desc: {{list_desc}}
keywords: hurl
tuid0: {{game_id}}
comments0: First game on the list
preview: Preview
HTTP 200
[Asserts]
xpath "normalize-space(//title)" == "Preview List"

# --- List publish ---
POST {{base_url}}/editlist?id=new
Content-Type: application/x-www-form-urlencoded
[FormParams]
title: {{list_title}}
desc: {{list_desc}}
keywords: hurl
tuid0: {{game_id}}
comments0: First game on the list
publish: Publish
HTTP 301
[Captures]
list_id: regex "viewlist\\?id=([a-z0-9]+)"
[Asserts]
header "Location" matches /viewlist\?id=[a-z0-9]+/

# --- Create competition ---
POST {{base_url}}/editcomp?id=new
Content-Type: application/x-www-form-urlencoded
[FormParams]
id: new
title: {{comp_title}}
series:
desc: {{comp_desc}}
keywords: hurl
url:
organizersExt: Hurl Organizer
judgesExt:
qualopen:
qualclose:
awarddate:
pagevsn: 1
divid0: -2
divname0: Main
divdesc0: Default division
gameid0_0: {{game_id}}
gameplace0_0: 1st Place
gamediv0_0: -2
gametitle0_0: {{game_title}}
gameauthor0_0: {{author}}
save: Save Changes
HTTP 301
[Captures]
comp_id: regex "viewcomp\\?id=([a-z0-9]+)"
[Asserts]
header "Location" matches /viewcomp\?id=[a-z0-9]+/

# --- Tag game ---
POST {{base_url}}/taggame
Authorization: Basic {{basic_auth}}
Content-Type: application/json
{
  "id": "{{game_id}}",
  "tags": ["hurl-smoke", "parser"]
}
HTTP 200
[Asserts]
jsonpath "$.label" == "Saved"
jsonpath "$.tags[*].name" contains "hurl-smoke"

# --- Capture review id ---
GET {{base_url}}/viewgame?id={{game_id}}
HTTP 200
[Captures]
review_id: regex "review=([0-9]+)"
[Asserts]
body contains "{{review_summary}}"
body contains "hurl-smoke"

# --- Helpful vote ---
POST {{base_url}}/reviewvote
Content-Type: application/x-www-form-urlencoded
[FormParams]
id: {{review_id}}
vote: Y
HTTP 200
[Asserts]
jsonpath "$.label" contains "Vote Recorded"

# --- Review comment ---
POST {{base_url}}/reviewcomment?review={{review_id}}
Content-Type: application/x-www-form-urlencoded
[FormParams]
review: {{review_id}}
text: {{comment_body}}
submit: Save Changes
submitBtn: Save Changes
g-recaptcha-response: test
HTTP 200
[Asserts]
body contains "Your comment has been recorded."
