#!/usr/bin/env python3

# This library requires the "requests" library
# https://pypi.org/project/requests/
# because python doesn't have a built-in way of handling multipart/form-data

# run it like this:
# python3 -m venv putific-venv
# source putific-venv/bin/activate
# pip3 install requests
# python3 python-putific.py

import requests
import time

# to test, consider setting up the ifdb dev environment
# then use localhost:8080 instead
# but beware, per the README, the local dev environment doesn't allow you to upload images, so you won't see the cover art

url = 'https://ifdb.org/putific'
# url = 'http://localhost:8080/putific'

username = 'ifdbadmin@ifdb.org'
password = 'secret'

xml = """<?xml version="1.0" encoding="UTF-8"?>
<ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
<story>
<bibliographic>
<title>Test %s</title>
<author>Test Author</author>
</bibliographic>
</story>
</ifindex>
""" % time.time()

links = """<?xml version="1.0" encoding="UTF-8"?>
<downloads xmlns="http://ifdb.org/api/xmlns"><links>
<link>
<url>http://www.ifarchive.org/if-archive/games/palm/ACgames.zip</url>
<title>ACgames.zip</title>
<desc>converted to PalmOS .prc file</desc>
<isGame/>
<format>executable</format>
<os>PalmOS.PalmOS-LoRes</os>
<compression>zip</compression>
<compressedPrimary>PHOTOPIA.PRC</compressedPrimary>
</link>
</links></downloads>
"""

body = (
    ('username', (None, username)),
    ('password', (None, password)),
    ('ifiction', ('ifiction.xml', xml, 'text/xml')),
    ('links', ('links.xml', links, 'text/xml')),
    ('coverart', ('cover.png', open('cover.png', 'rb'), 'image/png')),
    ('requireIFID', (None, 'no')),
)

#print(requests.Request('POST', url, files=body).prepare().body.decode('utf8'))

r = requests.post(url, files=body)
print(r.text)
