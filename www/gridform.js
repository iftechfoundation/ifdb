// Copyright 2007, 2009 Michael J Roberts

function gfGenForm(modelVar, newRowIdx)
{
    var i, j;
    var model = eval(modelVar);
    var vals = eval(model.vals);
    var fields = model.fields;
    var d = document.getElementById(model.name);
    var s = "<table>";
    var ctlvalign = (model.controlVertAlign || "middle");

    if (model.rowhead && vals.length != 0)
    {
        s += "<tr " + model.rowheadattr + "><th></th><th>"
             + model.rowhead
             + "</th></tr>";
    }

    if (model.emptylabel && vals.length == 0)
        s += "<tr><td></td><td>" + model.emptylabel + "</td></tr>";

    for (i = 0 ; i < vals.length ; ++i)
    {
        var txt;

        if (model.rowfunc)
        {
            txt = model.rowfunc(i);
        }
        else
        {
            var val = vals[i];
            var txt = model.rowtpl;

            if (typeof txt == 'function')
                txt = txt(i);

            for (j = 0 ; j < txt.length ; ++j)
            {
                function substParam(synthfunc)
                {
                    var nxtsegofs = j+2;
                    var chzero = '0'.charCodeAt(0);
                    var fieldnum = txt.charCodeAt(j+1) - chzero;
                    while (txt.charAt(nxtsegofs).match(/[0-9]/)) {
                        fieldnum *= 10;
                        fieldnum += txt.charCodeAt(nxtsegofs) - chzero;
                        nxtsegofs += 1;
                    }
                    var newtxt = synthfunc(fieldnum - 1);
                    txt = txt.substr(0, j) + newtxt + txt.substr(nxtsegofs);
                    j += newtxt.length - 1;
                }

                var c;
                switch (c = txt.charAt(j))
                {
                case '#':
                    if (txt.charAt(j+1).match(/[1-9]/))
                        substParam(function(n) {
                            var id = "\"" + fields[n] + i + "\"";
                            return "NAME=" + id + " ID=" + id + " VALUE=\""
                                + (val[n] == null ? "" :
                                   val[n].replace(/"/g, "&#34;"))
                                + "\"";
                        });
                    else if (txt.charAt(j+1) == '#')
                        substParam(function(n) { return "" + i; });
                    else if (txt.charAt(j+1) == 'R')
                        substParam(function(n) { return "" + (i+1) });
                    else if (txt.charAt(j+1) == 'N')
                        substParam(function(n) { return "" + vals.length });
                    break;

                case '$':
                    if (txt.charAt(j+1) == 'H' && txt.charAt(j+2).match(/[1-9]/))
                    {
                        ++j;
                        substParam(function(n) { return val[n]; });
                    }
                    if (txt.charAt(j+1).match(/[1-9]/))
                        substParam(function(n) {
                            return val[n].replace(/"/g, "&#34;");
                        });
                    break;

                case '@':
                    if (txt.charAt(j+1).match(/[1-9]/))
                        substParam(function(n) {
                            var id = "\"" + fields[n] + i + "\"";
                            return "NAME=" + id + " ID=" + id;
                        });
                    break;
                }
            }
        }

        s += "<tr><td valign=\"" + ctlvalign + "\"><nobr>";

        if (i > 0)
            s += "<a href=\"needjs\" title=\"Move up\" "
                 + "onmouseover=\"javascript:window.status='Move up';return true;\" "
                 + "onmouseout=\"javascript:window.status='';\" "
                 + "onclick=\"javascript:gfMoveRow('" + modelVar + "',"
                 + i + ",-1);return false;\">"
                 + "<img src=\"/img/blank.gif\" class=\"grid-move-up\"></a> ";
        else
            s += "<img src=\"/img/blank.gif\" class=\"grid-move-blank\"> "

        if (i + 1 < vals.length)
            s += "<a href=\"needjs\" title=\"Move down\" "
                 + "onmouseover=\"javascript:window.status='Move down';return true;\" "
                 + "onmouseout=\"javascript:window.status='';\" "
                 + "onclick=\"javascript:gfMoveRow('" + modelVar + "',"
                 + i + ",1);return false\">"
                 + "<img src=\"/img/blank.gif\" class=\"grid-move-down\"></a> ";
        else
            s += "<img src=\"/img/blank.gif\" class=\"grid-move-blank\"> ";

        s += "</nobr></td><td>" + txt + "</td><td valign=\"" + ctlvalign + "\">";

        if (model.allowRemove == null || model.allowRemove(i))
            s += "<a href=\"needjs\" title=\"Remove\" "
                 + "onmouseover=\"javascript:window.status='Remove';"
                 + "return true;\" "
                 + "onmouseout=\"javascript:window.status='';\" "
                 + "onkeypress=\"javascript:return gfDelRowBtnKey("
                 + "event,'" + modelVar + "'," + i + ");\" "
                 + "onclick=\"javascript:gfPostDelRow('" + modelVar + "',"
                 + i + ");return false\">"
                 + "<img src=\"/img/blank.gif\" class=\"grid-remove-button\"></a> ";

        s += "</td></tr>";
    }

    s += "<tr><td></td><td>"
         + "<a href=\"#\" title=\"Add a new item\" "
         + "onmouseover=\"javascript:window.status='Add a new item';return true;\" "
         + "onmouseout=\"javascript:window.status='';\" "
         + "onclick=\"javascript:gfInsRow('" + modelVar + "',"
         + i + ");return false;\"><img src=\"/img/blank.gif\" class=\""
         + model.addbutton + "\"></a>"
         + (model.addExtra ? model.addExtra : "")
         + "</td></tr></table>";

    d.innerHTML = s;

    if (model.popups)
    {
        for (var p in model.popups)
        {
            p = model.popups[p];
            for (j = 0 ; j < model.fields.length ; ++j)
            {
                if (model.fields[j] == p)
                    break;
            }
            for (i = 0 ; i < vals.length ; ++i)
            {
                var ele = document.getElementById(p + i);
                ele.value = vals[i][j];
                if (ele.selectedIndex < 0)
                    ele.selectedIndex = 0;
            }
        }
    }
}

function gfReloadVals(modelVar)
{
    var model = eval(modelVar);
    var vals = eval(model.vals);
    var fields = model.fields;
    var valcnt = vals.length;
    var row, fieldnum;

    for (row = 0 ; row < valcnt ; ++row)
    {
        for (fieldnum = 0 ; fieldnum < fields.length ; ++fieldnum)
        {
            var fid = fields[fieldnum] + row;
            f = document.getElementById(fid);
            if (f != null)
                vals[row][fieldnum] = f.value;
        }
    }
}

function gfInsRow(modelVar, n, newrow)
{
    gfReloadVals(modelVar);
    var model = eval(modelVar), vals = eval(model.vals), i;
    var nrv = model.newRowVals;

    if (typeof(nrv) == "function")
        nrv = nrv(n);

    if (!newrow)
    {
        newrow = [];
        for (i = 0 ; i < model.fields.length ; ++i)
            newrow.push(nrv ? nrv[i] : "");
    }

    vals.splice(n, 0, newrow);
    gfGenForm(modelVar, n);

    if (model.onAddRow)
        model.onAddRow(n);
}

function gfDelRowBtnKey(event, modelVar, n)
{
    var event = (event ? event : window.event);
    var ch = (window.event || event.keyCode ? event.keyCode : event.which);
    if (ch == 13 || ch == 10 || ch == 32)
    {
        gfPostDelRow(modelVar, n);
        return false;
    }
    return true;
}

var gfDelRowQueue = [];
function gfPostDelRow(modelVar, n)
{
    if (gfDelRowQueue.length == 0)
    {
        gfDelRowQueue.push(n);
        setTimeout(function() {
            gfDelRowQueue.pop();
            gfDelRow(modelVar, n);
        }, 1);
    }
}

function gfDelRow(modelVar, n)
{
    var model = eval(modelVar);
    var conf = model.confirmRemove || function(rownum) {
        return confirm("Do you really want to delete this row?");
    };

    if (!conf(n))
        return;

    gfReloadVals(modelVar);
    var model = eval(modelVar), vals = eval(model.vals), i;
    vals.splice(n, 1);
    gfGenForm(modelVar);
}

function gfMoveRow(modelVar, n, dir)
{
    gfReloadVals(modelVar);
    var model = eval(modelVar), vals = eval(model.vals);

    var row = vals[n];
    vals.splice(n, 1);
    vals.splice(n + dir, 0, row);

    gfGenForm(modelVar);
}

function gfSort(modelVar, vals, sortFunc)
{
    gfReloadVals(modelVar);
    vals.sort(sortFunc);
    gfGenForm(modelVar);
}
