'use strict';

var gameid;
var dbTagList;
var memTagList = [];

export function initTagTable(gameId, tagList, isAdmin) {
    gameid = gameId;
    dbTagList = tagList;

    addEventListeners(isAdmin);
    dispTags();
}

function addEventListeners(isAdmin) {
    const buttons = [
        ['#myTagList_edit', () => {editTags();}],
        ['#tagEditor .viewgame__cancel a', () => {closeTags('tagEditor');}],
        ['form[data-tag-button="add"]', () => {addTags();}, 'submit'],
        ['#viewgame-add-tags-button', () => {addTags();}],
        ['#viewgame-save-tags-button', () => {saveTags();}],
        ['#viewgame-save-tags-button-delete', () => {saveTagsDelete();}],
        ['#tagDeletor .viewgame__cancel a', () => {closeTags('tagDeletor');}],
    ];
    if (isAdmin) {
        buttons.push(['#myTagList_delete', () => {deleteTags();}]);
    }
    for (const [selector, handler, listener] of buttons) {
        document.querySelectorAll(selector).forEach(e => {
            e.addEventListener(listener ? listener : 'click', (ev) => {
                ev.preventDefault();
                handler();
            });
        });
    }
}

function doTagCkBox(id, stat)
{
    const i = Number(id.substr(5));
    const t = memTagList[i];
    t.tagcnt += (stat ? 1 : -1);
    t.gamecnt += (t.isNew ? (stat ? 1 : -1) : 0);
    t.isMine = stat ? 1 : 0;
    const ce = document.getElementById("tagCnt" + i);
    const tagcnt = t.tagcnt;
    ce.innerHTML = (t.isNew || tagcnt < 2  ? "" :
                    "(x" + t.tagcnt + ")");
    ce.title = `${tagcnt} member${tagcnt > 1 ? "s have" : " has"} tagged this game with "${t.tag}"`;
}


function dispTagTable(tableID, lst, editor, deleteTags)
{
    var tbl = document.getElementById(tableID);
    tbl.innerHTML = "";
    for (const [i, tag] of Object.entries(lst)) {
        var t = encodeHTML(tag.tag);
        var cell = document.createElement("div");
        tbl.appendChild(cell);
        let s;
        const ck = "tagCk" + i;
        if (editor) {
            const deleteCls = deleteTags ? ' ckdelete' : '';
            s = `<label class="cklabel" data-ck='${i}'>`
                + `<input type="checkbox" class="ckbox${deleteCls}" id="ckBox${ck}"><div class="ckboxImg" aria-hidden="true"></div> `
                + `<span>${t}</span></label>&nbsp;<span class=details id="tagCnt${i}">`;
        }
        else
            s = `<span class=details title="Search for games tagged with ${t}">`
                + `<a href=\"search?searchfor=tag:${encodeURIComponent(tag.tag)}">${t}</a>&nbsp;`;

        if (!tag.isNew)
        {
            if (editor)
            {
                s += `<span title="${tag.tagcnt} member`
                     + (tag.tagcnt > 1 ? "s have" : " has")
                     + ` tagged this game with &#34;${t}&#34;">`
                     + (tag.tagcnt > 1 ? "(x" + tag.tagcnt + ")" : "")
                     + "</span></span>";
            }
            else
            {
                s += `<span title="${tag.gamecnt} game`
                     + (tag.gamecnt > 1 ? "s have" : " has")
                     + ` this tag">(${tag.gamecnt})</span></span>`;
            }
        }
        cell.innerHTML = s;
        cell.querySelectorAll('.cklabel').forEach((cklabel) => {
            var ck = "tagCk" + cklabel.dataset.ck;
            cklabel.addEventListener('change', (event) => {
                event.preventDefault();
                if (deleteTags) {
                    var tag = lst[cklabel.dataset.ck].tag;
                    deleteTag(tag);
                } else {
                    ckboxClick(ck, doTagCkBox);
                }
            });
        });

        if (editor)
            ckboxGetObj(ck).checked = tag.isMine;
    }
    if (editor && lst.length == 0) {
        tbl.insertRow(0).insertCell(0).innerHTML =
            "<i>This game doesn't have any tags yet.</i>";
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
    for (const t of dbTagList) {
        if (t.isMine) {
            if (s != "")
                s += ", ";
            s += encodeHTML(t.tag);
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
    for (const [i, t] of Object.entries(memTagList))
    {
        if (t.tag == tag)
        {
            var index = i;
            break;
        }
    }

    memTagList.splice(index, 1);
    dispDeleteTags();
}

function rememberTagList() {
    memTagList = dbTagList.map((t) => {
        return {tag: t.tag, tagcnt: t.tagcnt, gamecnt: t.gamecnt,
                isMine: t.isMine};
    });
}

function editTags()
{
    rememberTagList();

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
    });
}

function deleteTags()
{
    rememberTagList();

    document.getElementById("tagDeletor").style.display = "initial";
    dispDeleteTags();
}


function closeTags(id)
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
        {"id": gameid, "tags": tags}, true);
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
        {"id": gameid, "tags": tags}, true);
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

function addTags()
{
    var fld = document.getElementById("myTagFld");
    if (!fld.value.trim())
        return;
    for (let s of fld.value.split(","))
    {
        s = s.trim();
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
    memTagList.sort(
        ({tag: a}, {tag: b}) => a.toLowerCase().localeCompare(b.toLowerCase()));
    dispEditTags();
    fld.value = "";
    fld.focus();
}
