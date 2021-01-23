// copyright 2007, 2009 Michael J. Roberts

// silentMode - don't pop up an error alert on a request failure
function xmlSend(url, statusSpanID, cbFunc, content, silentMode)
{
    var req;
    var tracker;

    if (statusSpanID)
        document.getElementById(statusSpanID).innerHTML = "";
    
    if (window.XMLHttpRequest)
        req = new XMLHttpRequest();
    else if (window.ActiveXObject)
        req = new ActiveXObject("Microsoft.XMLHTTP");
    if (req)
    {
        tracker = new Object();
        tracker.request = req;
        tracker.statusSpanID = statusSpanID;
        tracker.cbFunc = cbFunc;
        tracker.silentMode = silentMode;
        req.onreadystatechange = function() { xmlReqEvent(tracker); };
        if (content == null) {
            req.open("GET", url + "&xml", true);
            req.send(content);
        } else {
            req.open("POST", url, true);
            req.setRequestHeader("Content-Type",
                                 "application/x-www-form-urlencoded");
            req.send(content + "&xml=1");
        }
    }
    else
    {
        window.open(
            url, "IFDBRequest",
            'width=400,height=400,left=10,top=10,scrollbars=1,resizable=1');
    }
}

function xmlReqEvent(tracker)
{
    var req = tracker.request;

    if (req.readyState == 4)
    {
        var msgspan = (tracker.statusSpanID
                       ? document.getElementById(tracker.statusSpanID)
                       : null);
        var resp = req.responseXML.documentElement;
        if (req.status == 200 && resp != null)
        {
            if (msgspan)
            {
                var lbl = resp.getElementsByTagName('label');
                if (lbl && lbl.length > 0)
                    msgspan.innerHTML = lbl[0].firstChild.data;
            }
            
            var errmsg = resp.getElementsByTagName('error');
            if (errmsg && errmsg[0] && errmsg[0].firstChild)
                alert(errmsg[0].firstChild.data);
        }
        else
        {
            if (msgspan)
                msgspan.innerHTML = "Not Saved";
            if (!tracker.silentMode)
                alert("An error occurred sending the update to the server. "
                       + "(" + req.status + ") "
                       + "Please try again later.");
        }
        if (tracker.cbFunc)
            tracker.cbFunc(req.responseXML.documentElement);
    }
}

function xmlChildText(parent, name)
{
    var child = parent.getElementsByTagName(name);
    return (child && child.length && child[0].firstChild
            ? child[0].firstChild.data
            : null);
}
