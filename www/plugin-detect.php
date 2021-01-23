<?php


function detectPlugin()
{
    $pluginType = false;

    // get the browser ID string
    $uaStr = strtolower($_SERVER['HTTP_USER_AGENT']);

    if (isset($_REQUEST['useragentoverride']))
        $uaStr = $_REQUEST['useragentoverride'];

    // Apply the usual Web heuristics for guessing at the browser type
    // based on the User Agent ID string.  UA strings aren't even nearly
    // standardized, so the best we can do is look for some substrings
    // that tend to identify the various browsers.  Since some of the
    // substrings are small and common words, the chance of a false
    // positive is non-negligible.  To reduce this problem, start with
    // the most differentiated tests (i.e., the longer and less common
    // words that you'd be less likely to find accidentally) and work
    // toward the riskier tests.
    
    // Check for Zoom on Macintosh
    if (strpos($uaStr, "uk.org.logicalshift.zoom") !== false) {

        // it's a modern Zoom on MacOS
        $pluginType = "MacintoshZoom";

        // get the version number and release status
        if (preg_match(
            "/uk\.org\.logicalshift\.zoom\/([0-9.]+)\/([a-zA-Z0-9]+)/",
            $uaStr, $match)) {

            // check the version number
            $vsn = explode(".", $match[1]);
            if ($vsn[0] > "1"
                || ($vsn[0] == "1" && $vsn[1] > "1")
                || ($vsn[0] == "1" && $vsn[1] == "1" && $vsn[2] >= "2")) {
                // 1.1.2 or later - Play Now compatible
            } else {
                // older than 1.1.2 - not Play Now compatible
                $pluginType .= ".Old";
            }

            // special case: 1.1.2/beta - or any 1.1.2 prior to /release -
            // is an OLD version: it had a bug recognizing the auto-install
            // info, so we don't want to show Play Now
            if ($vsn[0] == "1" && $vsn[1] == "1" && $vsn[2] == "2"
                && $match[2] != "release")
                $pluginType .= ".Old";

            // check the release status
            if ($match[2] == "development")
                $pluginType .= ".Dev";
        } else {
            // there's no version/status suffix, so it's an old version
            $pluginType .= ".Old";
        }

    } else if (strpos($uaStr, "msie") !== false
               && strpos($uaStr, "win") !== false) {

        // it's IE on Windows
        $pluginType = "WindowsIE";

        // generate script code to determine if the ActiveX is installed

?>
<script type="text/vbscript">
function detectMetaInstaller()
   fExists = 0
   if scriptEngineMajorVersion > 1 then
      on error resume next
      fExists = IsObject(CreateObject("IfdbTadsOrg.IfdbMetaInstaller.1"))
   end if
   detectMetaInstaller = fExists
end function
</script>
<?php

    } else if (strpos($uaStr, "win") !== false) {

        // Windows, but not IE - most other browsers are compatible with
        // the Mozilla plug-in, so assume we're compatible
        $pluginType = "WindowsMozilla";

        // generate script code to check if the Mozilla plug-in is installed

?>
<script type="text/javascript">
<!--
var mozPluginType = "application/x-ifdb-metainstaller-plugin";
function detectMetaInstaller()
{
    var pluginCnt = navigator.plugins.length;
    for (var i = 0 ; i < pluginCnt ; ++i) {
        var p = navigator.plugins[i];
        try {
            for (var j = 0 ; j < p.length ; ++j) {
                var m = p.item(j);
                if (m.type == mozPluginType)
                    return true;
            }
        } catch (e) {
            if (p.filename == "npIfdbMeta.dll"
                && p.name.indexOf("IFDB Meta Installer") != -1)
                return true;
        }
    }
    return false;
}
//-->
</script>
<?php

    }

    if (strpos(get_req_data('debug'), 'plugin-detect') !== false)
        echo "[debug: plugin detected=$pluginType]<br>";

    // return the plug-in type we found, if any
    return $pluginType;
}

?>