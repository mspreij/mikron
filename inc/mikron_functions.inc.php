<?php
use League\CommonMark\CommonMarkConverter;

/** -- Functions -------------------------
* 
* getparam($name,$defval="")               -- _GET/_POST foo (srslywtfbbq)
* startswith($string, $search)             -- .. you got me!
* str_split_unicode($str, $l = 0)          -- maybe not in use yet, for better parsing someday.
* wiki2html($code, $converter='markdown')  -- outsources the code to the given parser. handler. thing.
* mikron2html($code)                       -- old parser; handles strong, em, few others. Calls wiki_parse_cmd() for [[...]]
* wiki_parse_cmd($cmd)                     -- handles Wiki commands, called from mikron2html()
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

//_______________________________
// startswith($string, $search) /
function startswith($string, $search) {
    return (strncmp($string, $search, strlen($search)) == 0);
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

//__________________________________________
// wiki2html($code, $converter='markdown') /
function wiki2html($code, $converter='markdown') {
    switch ($converter) {
        case 'markdown':
        case 'commonmark':
            $converter = new CommonMarkConverter();
            $code = $converter->convertToHtml($code);
            $code = postProcessMarkdown($code);
            $code = preg_replace_callback('/\[\[(.*?)\]\]/', 'wiki_parse_cmd_array', $code);
            return $code;
            break;
        case 'mikron':
            return mikron2html($code);
        default:
            return "<h1>unconverted!</h1>" . $code;
            break;
    }
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

function XX_handleMikronTags($code) {
    $html = '';
    $len = strlen($code);
    $head = 0;
    $text = '';
    while ($head < $len) {
        if ($code[$head] == '[' && $code[$head + 1] == '[') {
            $head += 2;
            $cmd = "";
            while ($head < $len) {
                if ($code[$head] == ']' && $code[$head + 1] == ']') {
                    $head += 2;
                    break;
                }
                $cmd .= $code[$head++];
            }
            $html .= $text.wiki_parse_cmd($cmd);
            $text = "";
        }
        if ($code[$head] == "\n") {
            $html .= "$text\n";
            if (trim($text == "")) {
                $html .= "</p><p class='content'>";
            }else{
                $text = "";
            }
        } else {
            $text .= $code[$head];
        }
        $head++;
    }
    return $html.$text;
}

//_____________________
// mikron2html($code) /
function mikron2html($code) {
    global $heads, $printable;
    $heads = array();
    $len = strlen($code);
    $code = $code.' ';
    $head = 0;
    $strong_on = false;
    $em_on     = false;
    $ul_on     = false;
    $sup_on    = false;
    $sub_on    = false;
    $strike_on = false;
    $code_on   = false; // not to be confused with $code
    $text = "";
    $html = "<p>";
    while ($head < $len) {
        if ($code[$head] == '[' && $code[$head + 1] == '[') {
            $head += 2;
            $cmd = "";
            while ($head < $len) {
                if ($code[$head] == ']' && $code[$head + 1] == ']') {
                    $head += 2;
                    break;
                }
                $cmd .= $code[$head++];
            }
            $html .= $text.wiki_parse_cmd($cmd);
            $text = "";
            continue;
        }
        // Bold
        if ($code[$head] == '*' && $code[$head + 1] == '*') {
            $strong_on = !$strong_on;
            $html .= $text;
            $text = "";
            $html .= $strong_on ? "<strong class='content'>" : "</strong>";
            $head += 2;
            continue;
        }
        // Italic (emphasis)
        if ($code[$head] == '\'' && $code[$head + 1] == '\'') {
            $em_on = !$em_on;
            $html .= $text;
            $text = "";
            $html .= $em_on ? "<em class='content'>" : "</em>";
            $head += 2;
            continue;
        }
        // Italic (emphasis)
        if ($code[$head] == '_' && $code[$head + 1] == '_') {
            $ul_on = !$ul_on;
            $html .= $text;
            $text = "";
            $html .= $ul_on ? "<u>" : "</u>";
            $head += 2;
            continue;
        }
        // Superscript
        if ($code[$head] == '^' && $code[$head + 1] == '^') {
            $sup_on = !$sup_on;
            $html .= $text;
            $text = "";
            $html .= $sup_on ? "<sup class='content'>" : "</sup>";
            $head += 2;
            continue;
        }
        // Subscript
        if ($code[$head] == ',' && $code[$head + 1] == ',') {
            $sub_on = !$sub_on;
            $html .= $text;
            $text = "";
            $html .= $sub_on ? "<sub class='content'>" : "</sub>";
            $head += 2;
            continue;
        }
        // Strikethrough
        if ($code[$head] == '~' && $code[$head + 1] == '~') {
            $strike_on = ! $strike_on;
            $html .= $text;
            $text = "";
            $html .= $strike_on ? "<strike class='content'>" : "</strike>";
            $head += 2;
            continue;
        }
        // Code (preformatted)
        if ($code[$head] == '%' && $code[$head + 1] == '%') {
            $code_on = ! $code_on;
            $html .= $text;
            $text = "";
            $html .= $code_on ? "<span class='code'>" : "</span>";
            $head += 2;
            continue;
        }
		
        if ($code[$head] == "\n") {
            $html .= "$text\n";
            if (trim($text == "")) {
                $html .= "</p><p class='content'>";
            }else{
                $text = "";
            }
        } else {
            if ($code[$head] != "\r") {
                // $text .= htmlspecialchars($code[$head]);
                $text .= ($tmp = htmlspecialchars($code[$head])) ? $tmp : $code[$head]; // "fixes" UTF-8
            }
        }
        $head++;
    }
	
    $html .= $text."</p>";
	
    if (!$printable) {
        if (count($heads) > 3) {
            $toc = "<div class='toc'>";
            foreach ($heads as $hid=>$head) {
                $toc .= "<a class='toc' href='#head".$hid."'>";
                for ($i=1; $i<$head['level']; $i++)
                    $toc .= "&nbsp;&nbsp;&nbsp;";
                $toc .= $head['title']."</a>";
            }
            $toc .= "</div>";
            $html = $toc.$html;
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
    if (startswith($cmd, "http:") || startswith($cmd, "https:") || // todo: if substr($cmd, 0, strpos($cmd, ':')) in_array $protocols, do..
        startswith($cmd, "ftp:") || startswith($cmd, "mailto:") ||
        startswith($cmd, "news:") || startswith($cmd, "irc:") || startswith($cmd, "magnet:")) {
        $linkCounterHTML = '';//"<span class='numberlink'> ".++$linkCounter."</span>";
        if (strchr($cmd, " ") === false) {
            return "<a href='".htmlspecialchars($cmd)."' target='_blank'>".htmlspecialchars($cmd)."</a>$linkCounterHTML";
        } else {
            return "<a href='".htmlspecialchars(substr($cmd, 0, strpos($cmd, " ")))."' target='_blank'>".htmlspecialchars(substr($cmd, strpos($cmd, " ") + 1, strlen($cmd)))."</a>$linkCounterHTML";
        }
    }
    
    // check <h1> .. <h4>
    for ($i=1; $i <= 4; $i++) { 
        if (startswith($cmd, "h$i:")) {
            $hid = count($heads);
            $heads[$hid] = array();
            $heads[$hid]['title'] = substr($cmd, 3, strlen($cmd));
            $heads[$hid]['level'] = $i;
            return "<a name='head".$hid."'></a><h$i class='content'>".$heads[$hid]['title']."</h$i>";
        }
    }
    if (startswith($cmd, "img:"))
        return "<img class='contentimg' src='".htmlspecialchars(substr($cmd, 4, strlen($cmd))). "'>";
    if (startswith($cmd, "limg:"))
        return "<img class='contentlimg' src='".htmlspecialchars(substr($cmd, 5, strlen($cmd))). "'>";
    if (startswith($cmd, "rimg:"))
        return "<img class='contentrimg' src='".htmlspecialchars(substr($cmd, 5, strlen($cmd))). "'>";
    if (startswith($cmd, "html:"))
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

//______________________
// post_process($html) /
function post_process($html) {
    $html = trim($html);
    // return data as-is, this was useful for the syntax help page
    if (isset($_GET['mikron'])) return $html;
    $html = str_replace("\n", "<br>\n", $html); // linebreaks, damnit!
    $out = '';
    // leading space/tabs = code
    foreach(explode("\n", $html) as $line) {
        $len = strspn($line, ' ');
        if ($len or $line[0]=='$') {
            $line = "<span class='code'>$line</span>";
        }else{
            // fnord
        }
        $out .= "$line\n";
    }
    // hackity hack
    $out = str_replace("<p class='content'>----<br>\n", "<p class='content'><hr>\n", $out);
    $out = str_replace("\n----<br>\n", "\n<hr>\n", $out); // if it directly follows some other text, there's no paragraph tag
    return $out;
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

