'use strict';

class TagTable {
    constructor(gameid, tagList) {
        this.gameid = gameid;
        this.dbTagList = tagList;
        this.memTagList = [];
    }

    addEventListeners(isAdmin) {
        const buttons = [
            ['#myTagList_edit', () => {this.editTags();}],
            ['.viewgame__tagEditorContainer .viewgame__cancel a', () => {this.closeTags();}],
            ['form[data-tag-button="add"]', () => {this.addTags();}, 'submit'],
            ['#viewgame-add-tags-button', () => {this.addTags();}],
            ['#viewgame-save-tags-button', () => {this.saveTags();}],
            ['#viewgame-save-tags-button-delete', () => {this.saveTagsDelete();}],
            ['#tagDeletor .viewgame__cancel a', () => {this.closeTags('tagDeletor');}],
        ];
        if (isAdmin) {
            buttons.push(['#myTagList_delete', () => {this.deleteTags();}]);
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

    doTagCkBox(id, stat)
    {
        const i = Number(id.substr(5));
        const t = this.memTagList[i];
        t.tagcnt += (stat ? 1 : -1);
        t.gamecnt += (t.isNew ? (stat ? 1 : -1) : 0);
        t.isMine = stat ? 1 : 0;
        const ce = document.getElementById("tagCnt" + i);
        const tagcnt = t.tagcnt;
        ce.innerHTML = (t.isNew || tagcnt < 2  ? "" :
                        "(x" + t.tagcnt + ")");
        ce.title = `${tagcnt} member${tagcnt > 1 ? "s have" : " has"} tagged this game with "${t.tag}"`;
    }


    dispTagTable(tableID, lst, editor, deleteTags)
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
                        this.deleteTag(tag);
                    } else {
                        ckboxClick(ck, this.doTagCkBox.bind(this));
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

    dispTags()
    {
        var pre = document.getElementById("tagPre");
        if (this.dbTagList.length == 0)
            pre.innerHTML = "There are no tags associated with this game yet - "
                            + "you can be the first to tag it.";
        else
            pre.innerHTML =
                "The following tags are associated with this game. Click on a tag "
                + "to search for other games with the same tag. ";
        this.dispTagTable("tagTable", this.dbTagList, false, false);

        var s = "";
        for (const t of this.dbTagList) {
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

    dispEditTags()
    {
        document.getElementById("tagStatusSpan").innerHTML = "";
        this.dispTagTable("editTagTable", this.memTagList, true, false);
    }

    dispDeleteTags()
    {
        document.getElementById("tagStatusSpan").innerHTML = "";
        this.dispTagTable("deleteTagTable", this.memTagList, true, true);
    }

    deleteTag(tag)
    {
        for (const [i, t] in Object.entries(this.memTagList))
        {
            if (t.tag == tag)
            {
                var index = i;
                break;
            }
        }

        this.memTagList.splice (index, 1);
        this.dispDeleteTags();
    }

    rememberTagList() {
        this.memTagList = this.dbTagList.map((t) => {
            return {tag: t.tag, tagcnt: t.tagcnt, gamecnt: t.gamecnt,
                    isMine: t.isMine};
        });
    }

    editTags()
    {
        this.rememberTagList();

        document.getElementById("tagEditor").style.display = "initial";
        this.dispEditTags();
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

    deleteTags()
    {
        this.rememberTagList();

        document.getElementById("tagDeletor").style.display = "initial";
        this.dispDeleteTags();
    }


    closeTags(id="tagEditor")
    {
        document.getElementById(id).style.display = "none";
    }

    saveTags()
    {
        this.addTags();
        this.dbTagList = [];

        const tags = [];
        for (const t of this.memTagList)
        {
            if (t.tagcnt != 0)
                this.dbTagList.push(t);
            if (t.isMine)
                tags.push(t.tag);
        }
        this.dispTags();
        this.closeTags("tagEditor");
        jsonSend("taggame", "tagStatusSpan", this.cbSaveTags.bind(this),
            {"id": this.gameid, "tags": tags}, true);
    }

    saveTagsDelete()
    {
        const tags = [];
        for (const t of this.dbTagList) {
            if (this.memTagList.find((m) => m.tag == t.tag) === undefined) {
                tags.push(t.tag);
            }
        }

        this.dbTagList = this.memTagList;

        this.dispTags();
        this.closeTags("tagDeletor");

        jsonSend("taggamedelete", "tagStatusSpan", this.cbSaveTags.bind(this),
            {"id": this.gameid, "tags": tags}, true);
    }


    cbSaveTags(resp)
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
            for (const memTag of this.memTagList)
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

        this.dispTags();
    }

    addTags()
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
            for (j = 0 ; j < this.memTagList.length ; j++) {
                var t = this.memTagList[j];
                if (t.tag == s) {
                    if (!t.isMine) {
                        t.tagcnt += 1;
                        t.gamecnt += 1;
                        t.isMine = 1;
                    }
                    break;
                }
            }
            if (j == this.memTagList.length)
                this.memTagList[j] = {tag: s, tagcnt: 1, gamecnt: 1, isMine: 1, isNew: true};
        }
        this.memTagList.sort(
            ({tag: a}, {tag: b}) => a.toLowerCase().localeCompare(b.toLowerCase()));
        this.dispEditTags();
        fld.value = "";
        fld.focus();
    }
}
