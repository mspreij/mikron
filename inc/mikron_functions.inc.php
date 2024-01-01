<?php
use League\CommonMark\CommonMarkConverter;

/** -- Functions -------------------------
* 
* getparam($name, $defval="")              -- _GET/_POST foo (srslywtfbbq)
* str_split_unicode($str, $l = 0)          -- maybe not in use yet, for better parsing someday.
* wiki2html($code, $converter='markdown')  -- outsources the code to the given parser. handler. thing.
* valid_page($page)                        -- checks pagename characters are A-Z0-9_
* post_process($html)                      -- runs after wiki2html, before echoing $html in View mode
* pre_store_processing($string)            -- converts leading spaces to tabs, trims superfluous trailing whitespace
* 
* 
**/

//______________________________
// getparam($name, $defval="") /
function getparam($name, $defval="") {
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $defval;
}

//__________________________________
// str_split_unicode($str, $l = 0) /
function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

//___________________
// wiki2html($code) /
function wiki2html($code) {
    $converter = new CommonMarkConverter();
    $code = $converter->convertToHtml($code);
    $code = postProcessMarkdown($code);
    $code = preg_replace_callback('/\[\[(.*?)\]\]/', 'wiki_parse_cmd_array', $code);
    return $code;
}

//_____________________________
// postProcessMarkdown($html) /  -- temp function to convert code blocks to textareas for easier copy/pasting. this could/should be a plugin one day
function postProcessMarkdown($html) {
    global $heads;
    $heads = array();
    $loopBust = 0;
    $codeTagStart = '<pre><code class="language-textarea">';
    $codeTagEnd = '</code></pre>';
    while (is_numeric($pos = strpos($html, $codeTagStart))) {
        $content = substr($html, $pos + strlen($codeTagStart), $contentLength = (strpos($html, $codeTagEnd, $pos) - ($pos + strlen($codeTagStart))));
        $trailing = substr($html, $pos + strlen($codeTagStart) + $contentLength + strlen($codeTagEnd));
        $html = substr($html, 0, $pos).'<textarea readonly class="selectOnFocus" rows="'.(count(explode("\n", $content))-1).'">'.trim($content).'</textarea>'.$trailing;
        if ($loopBust++ > 100) {
            $html .= '<script>alert("either there are too many textareas on this page or wiki2html() got into a bloody loop again")</script>';
            break;
        }
    }
    return $html;
}

function wiki_parse_cmd_array($array) {
    return wiki_parse_cmd($array[1]);
}

//_______________________
// wiki_parse_cmd($cmd) /
function wiki_parse_cmd($cmd) {
    global $heads, $db, $url;
    static $linkCounter = 0;
    if (strlen($cmd) == 1 and $cmd != '/') return $cmd;
    if (str_starts_with($cmd, "http:") || str_starts_with($cmd, "https:") || // todo: if substr($cmd, 0, strpos($cmd, ':')) in_array $protocols, do..
        str_starts_with($cmd, "ftp:")  || str_starts_with($cmd, "mailto:") ||
        str_starts_with($cmd, "news:") || str_starts_with($cmd, "irc:") || str_starts_with($cmd, "magnet:")) {
        $linkCounterHTML = '';//"<span class='numberlink'> ".++$linkCounter."</span>";
        if (strchr($cmd, " ") === false) {
            return "<a href='".htmlspecialchars($cmd)."' target='_blank'>".htmlspecialchars($cmd)."</a>$linkCounterHTML";
        } else {
            return "<a href='".htmlspecialchars(substr($cmd, 0, strpos($cmd, " ")))."' target='_blank'>".htmlspecialchars(substr($cmd, strpos($cmd, " ") + 1, strlen($cmd)))."</a>$linkCounterHTML";
        }
    }
    
    // check <h1> .. <h4>
    for ($i=1; $i <= 4; $i++) { 
        if (str_starts_with($cmd, "h$i:")) {
            $hid = count($heads);
            $heads[$hid] = array();
            $heads[$hid]['title'] = substr($cmd, 3, strlen($cmd));
            $heads[$hid]['level'] = $i;
            return "<a name='head".$hid."'></a><h$i class='content'>".$heads[$hid]['title']."</h$i>";
        }
    }
    if (str_starts_with($cmd, "img:"))
        return "<img class='contentimg' src='".htmlspecialchars(substr($cmd, 4, strlen($cmd))). "'>";
    if (str_starts_with($cmd, "limg:"))
        return "<img class='contentlimg' src='".htmlspecialchars(substr($cmd, 5, strlen($cmd))). "'>";
    if (str_starts_with($cmd, "rimg:"))
        return "<img class='contentrimg' src='".htmlspecialchars(substr($cmd, 5, strlen($cmd))). "'>";
    if (str_starts_with($cmd, "html:"))
        return substr($cmd, 5, strlen($cmd));
    // colors!
    $colors = explode(' ', 'red green blue purple yellow teal navy fuchsia pink orange brown gray silver gold');
    if (in_array($cmd, $colors) or preg_match('/#([A-Fa-f0-9]{3}){1,2}/', $cmd)) {
        return "<span style='color: $cmd;'>";
    }
    if ($cmd == '/') {
        return "</span>";
    }
    // wiki links
    if (valid_page($cmd) || (strchr($cmd, ":") !== false && valid_page(substr($cmd, 0, strpos($cmd, ":"))))) {
        $linkCounterHTML = '';//"<span class='numberlink'> ".++$linkCounter."</span>";
        if (valid_page($cmd)) {
            $page = $cmd;
            $ftitle = "";
        } else {
            $page = substr($cmd, 0, strpos($cmd, ":"));
            $ftitle = substr($cmd, strpos($cmd, ":") + 1, strlen($cmd));
        }
        // $row = sqlite_array_query($db, "SELECT title FROM pages WHERE name='".sqlite_escape_string($page)."' ORDER BY time DESC LIMIT 1", SQLITE_ASSOC);
        $res = $db->query("SELECT title FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC LIMIT 1"); // todo: uh.. select all in one go and cache it?
        $row = $res->fetchArray(SQLITE3_ASSOC); // todo: why is this not a wrapper
        if ($row) {
            if ($ftitle == "") {
                $pagetitle = htmlspecialchars($row['title']);
                if ($pagetitle == "") $pagetitle = strtoupper($page[0]).strtolower(substr($page, 1, strlen($page)));
            } else {
                $pagetitle = htmlspecialchars($ftitle);
            }
            return "<a class='knownpageref' href='".$url."?a=view&p=$page'>".$pagetitle."</a>$linkCounterHTML";
        } else {
            if ($ftitle == "")
                return "<a class='unknownpageref' href='".$url."?a=edit&p=$page'>".strtoupper($page[0]).strtolower(substr($page, 1, strlen($page)))."</a>$linkCounterHTML";
            else
                return "<a class='unknownpageref' href='".$url."?a=edit&p=$page'>".htmlspecialchars($ftitle)."</a>$linkCounterHTML";
        }
    }
    return "Unknown wiki command <tt>".htmlspecialchars($cmd)."</tt>";
}

//____________________
// valid_page($page) /
function valid_page($page) {
    for ($i=0; $i<strlen($page); $i++) {
        if (strchr("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_", $page[$i]) === false) // *cough*regex*cough*
            return false;
    }
    return true;
}

//________________________________
// pre_store_processing($string) /
function pre_store_processing($string) {
    $output = '';
    // Timestamp
    $string = str_replace('#time#', date('[Y-m-d H:i:s]'), $string);
    // convert leading tabs to spaces. why are we doing this again?
    foreach(explode("\n", $string) as $line) {
        $tabs = strspn($line, "\t");
        $output .= str_repeat(' ', $tabs * TAB_LENGTH) . substr($line, $tabs) . "\n";
    }
    // one trailing linebreak
    return rtrim($output)."\n";
}

function printDebug($var, $ignore, $die=false)
{
    var_export($var);
    if ($die) die();
}

