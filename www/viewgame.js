var memTagList = [];
function encodeHTML(str)
{
    return str.replace(/&/g, "&amp;")
        .replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function doTagCkBox(id, stat)
{
    var i = parseInt(id.substr(5));
    memTagList[i].tagcnt += (stat ? 1 : -1);
    memTagList[i].gamecnt += (memTagList[i].isNew ? (stat ? 1 : -1) : 0);
    memTagList[i].isMine = stat ? 1 : 0;
    var ce = document.getElementById("tagCnt" + i);
    var ct = memTagList[i].tagcnt;
    ce.innerHTML = (memTagList[i].isNew || ct < 2  ? "" :
                    "(x" + memTagList[i].tagcnt + ")");
    ce.title = ct + " member" + (ct > 1 ? "s have" : " has")
               + " tagged this game with \"" + memTagList[i].tag + "\"";
}
function dispTagTable(tableID, lst, editor, deleteTags)
{
    var tbl = document.getElementById(tableID);
    tbl.innerHTML = "";
    var row = null, rownum = 0;
    for (var i = 0 ; i < lst.length ; i++) {
        var t = encodeHTML(lst[i].tag);
        var u = encodeURI8859(lst[i].tag);
        var ct = lst[i].tagcnt;
        var cg = lst[i].gamecnt;
        var m = lst[i].isMine;
        var n = lst[i].isNew;
        var cell = document.createElement("div");
        tbl.appendChild(cell);
        var s;
        const ck = "tagCk" + i;
        if (editor) {
            const deleteCls = deleteTags ? ' ckdelete' : '';
            s = `<label class="cklabel" data-ck='${i}'>`
                + `<input type="checkbox" class="ckbox${deleteCls}" id="ckBox${ck}"><div class="ckboxImg" aria-hidden="true"></div> `
                + `<span>${t}</span></label>&nbsp;<span class=details id="tagCnt${i}">`;
        }
        else
            s = "<span class=details title=\"Search for games tagged with "
                + t.replace(/"/g, "&#34;")
                + "\"><a href=\"search?searchfor=tag:"
                + u + "\">"
                + t + "</a>&nbsp;";

        if (!n)
        {
            if (editor)
            {
                s += "<span title=\"" + ct + " member"
                     + (ct > 1 ? "s have" : " has")
                     + " tagged this game with &#34;" + t
                     + "&#34;\">"
                     + (ct > 1 ? "(x" + ct + ")" : "")
                     + "</span></span>";
            }
            else
            {
                s += "<span title=\"" + cg + " game"
                     + (cg > 1 ? "s have" : " has") + " this tag\">(" + cg
                     + ")</span></span>";
            }
        }
        cell.innerHTML = s;
        cell.querySelectorAll('.cklabel').forEach(function (cklabel) {
            var ck = "tagCk" + cklabel.dataset.ck;
            cklabel.addEventListener('change', function (event) {
                event.preventDefault();
                if (deleteTags) {
                    var tag = lst[cklabel.dataset.ck].tag;
                    deleteTag(tag);
                } else {
                    ckboxClick(ck, doTagCkBox);
                }
            });
        })

        if (editor)
            ckboxGetObj(ck).checked = m;
    }
    if (editor && lst.length == 0) {
        tbl.insertRow(0).insertCell(0).innerHTML =
            "<i>This game doesn't have any tags yet.</i>"
    }
}
function dispTags()
{
    var pre = document.getElementById("tagPre");
    if (dbTagList.length == 0)
        pre.innerHTML = "There are no tags associated with this game yet - "
                        + "you can be the first to tag it.";
    else
        pre.innerHTML =
            "The following tags are associated with this game. Click on a tag "
            + "to search for other games with the same tag. ";
    dispTagTable("tagTable", dbTagList, false, false);

    var s = "";
    for (var i = 0 ; i < dbTagList.length ; i++) {
        var t = dbTagList[i];
        if (t.isMine) {
            if (s != "")
                s += ", ";
            s += encodeHTML(t.tag)
        }
    }
    if (s == "")
        s = "(None)";
    var tl = document.getElementById("myTagList");
    if (tl != null)
        tl.innerHTML = s;
}

function dispEditTags()
{
    document.getElementById("tagStatusSpan").innerHTML = "";
    dispTagTable("editTagTable", memTagList, true, false);
}

function dispDeleteTags()
{
    document.getElementById("tagStatusSpan").innerHTML = "";
    dispTagTable("deleteTagTable", memTagList, true, true);
}


function deleteTag(tag)
{
    for (var j = 0 ; j < memTagList.length ; j++)
    {
        if (memTagList[j].tag == tag)
        {
            var index = j;
            break;
        }
    }

    memTagList.splice (index, 1)
    dispDeleteTags();


}

function editTags()
{
    memTagList = [];
    for (var i = 0 ; i < dbTagList.length ; ++i) {
        var t = dbTagList[i];
        memTagList[i] = {tag: t.tag, tagcnt: t.tagcnt, gamecnt: t.gamecnt,
                         isMine: t.isMine};
    }

    document.getElementById("tagEditor").style.display = "initial";
    dispEditTags();
    const tagInputElem = document.getElementById('myTagFld');
    tagInputElem.focus();
    fetch('/showtags?datalist=1').then(r=>r.ok ? r.text() : null).then(text => {
        if (!text) return;
        const datalist = document.createElement('datalist');
        document.body.appendChild(datalist);
        datalist.outerHTML = text;
        tagInputElem.setAttribute('list', 'tags-list');
    })
}

function deleteTags()
{
    memTagList = [];
    for (var i = 0 ; i < dbTagList.length ; ++i) {
        var t = dbTagList[i];
        memTagList[i] = {tag: t.tag, tagcnt: t.tagcnt, gamecnt: t.gamecnt,
                         isMine: t.isMine};
    }

    document.getElementById("tagDeletor").style.display = "initial";
    dispDeleteTags();
}


function closeTags(id="tagEditor")
{
    document.getElementById(id).style.display = "none";
}

function saveTags()
{
    addTags();
    dbTagList = [];
    const tags = [];
    for (const t of memTagList)
    {
        if (t.tagcnt != 0)
            dbTagList.push(t);
        if (t.isMine)
            tags.push(t.tag);
    }
    dispTags();
    closeTags("tagEditor");
    jsonSend("taggame", "tagStatusSpan", cbSaveTags,
        {"id": "<?php echo $id ?>", "tags": tags}, true);
}

function saveTagsDelete()
{
    const tags = [];
    for (const t of dbTagList) {
        if (memTagList.find((m) => m.tag == t.tag) === undefined) {
            tags.push(t.tag);
        }
    }

    dbTagList = memTagList;

    dispTags();
    closeTags("tagDeletor");
    jsonSend("taggamedelete", "tagStatusSpan", cbSaveTags,
        {"id": "<?php echo $id ?>", "tags": tags}, true);
}


function cbSaveTags(resp)
{
    if (!resp) {
        alert("There was an error saving tags.");
        return;
    }
    if (resp.error) {
        alert(resp.error);
        return;
    }
    for (const tag of resp.tags)
    {
        for (const memTag of memTagList)
        {
            if (memTag.tag.toLowerCase() == tag.name.toLowerCase())
            {
                memTag.gamecnt = tag.gamecnt;
                memTag.tagcnt = tag.tagcnt;
                memTag.isNew = false;
                break;
            }
        }
    }

    dispTags();
}
function trim(str) { return str.replace(/^\s+|\s+$/g, ''); }
function tagSorter(a, b)
{
    a = a.tag.toLowerCase();
    b = b.tag.toLowerCase();
    return (a > b ? 1 : a < b ? -1 : 0);
}
function addTags()
{
    var fld = document.getElementById("myTagFld");
    if (trim(fld.value) == "")
        return;
    var lst = fld.value.split(",");
    for (var i = 0 ; i < lst.length ; i++)
    {
        var s = trim(lst[i]);
        if (s == "")
            continue;
        var j;
        for (j = 0 ; j < memTagList.length ; j++) {
            var t = memTagList[j];
            if (t.tag == s) {
                if (!t.isMine) {
                    t.tagcnt += 1;
                    t.gamecnt += 1;
                    t.isMine = 1;
                }
                break;
            }
        }
        if (j == memTagList.length)
            memTagList[j] = {tag: s, tagcnt: 1, gamecnt: 1, isMine: 1, isNew: true};
    }
    memTagList.sort(tagSorter);
    dispEditTags();
    fld.value = "";
    fld.focus();
}

dispTags();
