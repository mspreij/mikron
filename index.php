<?php
ini_set('display_errors', '1');
error_reporting(-1);
date_default_timezone_set("UTC"); 

require_once 'inc/auth.inc.php';

define('TAB_LENGTH', 2);
$sitetitle  = "Mikron";
$dbpath     = 'data';
$dbfile     = "$dbpath/mikron.db";
$allowedit  = true;
$editurl    = ""; // use if $allowedit is false to put a link to an editable URL
$users = array(
    '82.73.172.82'  => 'Area 61',
);

$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

if ($url{strlen($url)-1} == '?') $url = substr($url, 0, strlen($url)-1);


/** -- Functions -------------------------
 * 
 * getparam($name,$defval="")        -- _GET/_POST foo (srslywtfbbq)
 * startswith($string, $search)      -- .. you got me!
 * str_split_unicode($str, $l = 0)   -- maybe not in use yet, for better parsing someday.
 * wiki_parse_cmd($cmd)              -- handles Wiki commands, called from wiki2html()
 * wiki2html($code)                  -- main parser; handles strong, em, few others. Calls wiki_parse_cmd() for [[...]]
 * valid_page($page)                 -- checks pagename characters are A-Z0-9_
 * post_process($html)               -- runs after wiki2html, before echoing $html in View mode
 * pre_store_processing($string)     -- converts leading spaces to tabs, trims superfluous trailing whitespace
 * 
 * 
**/

//_____________________________
// getparam($name,$defval="") /
function getparam($name,$defval="") {
	$value = "";
	if (isset($_GET[$name])) $value = $_GET[$name];
	if (($value == "" || !$value) && isset($_POST[$name])) $value = $_POST[$name];
	if ($value == "" || !$value)
		$value = $defval;
	elseif (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	return $value;
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

//_______________________
// wiki_parse_cmd($cmd) /
function wiki_parse_cmd($cmd) {
	global $heads, $db, $url;
	if (strlen($cmd) == 1 and $cmd != '/') return $cmd;
	if (startswith($cmd, "http:") || startswith($cmd, "https:") ||
		startswith($cmd, "ftp:") || startswith($cmd, "mailto:") ||
		startswith($cmd, "news:") || startswith($cmd, "irc:") || startswith($cmd, "magnet:")) {
		if (strchr($cmd, " ") === false) {
			return "<a href='".htmlspecialchars($cmd)."' target='_blank'>".htmlspecialchars($cmd)."</a>";
		} else {
			return "<a href='".htmlspecialchars(substr($cmd, 0, strpos($cmd, " ")))."' target='_blank'>".htmlspecialchars(substr($cmd, strpos($cmd, " ") + 1, strlen($cmd)))."</a>";
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
		if (valid_page($cmd)) {
			$page = $cmd;
			$ftitle = "";
		} else {
			$page = substr($cmd, 0, strpos($cmd, ":"));
			$ftitle = substr($cmd, strpos($cmd, ":") + 1, strlen($cmd));
		}
		// $row = sqlite_array_query($db, "SELECT title FROM pages WHERE name='".sqlite_escape_string($page)."' ORDER BY time DESC LIMIT 1", SQLITE_ASSOC);
		$res = $db->query("SELECT title FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC LIMIT 1");
		$row = $res->fetchArray(SQLITE3_ASSOC);
		if ($row) {
			// $row = $row[0];
			if ($ftitle == "") {
				$pagetitle = htmlspecialchars($row['title']);
				if ($pagetitle == "") $pagetitle = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
			} else {
				$pagetitle = htmlspecialchars($ftitle);
			}
			return "<a class='knownpageref' href='".$url."?a=view&p=$page'>".$pagetitle."</a>";
		} else {
			if ($ftitle == "")
				return "<a class='unknownpageref' href='".$url."?a=edit&p=$page'>".strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)))."</a>";
			else
				return "<a class='unknownpageref' href='".$url."?a=edit&p=$page'>".htmlspecialchars($ftitle)."</a>";
		}
	}
	return "Unknown wiki command <tt>".htmlspecialchars($cmd)."</tt>";
}

//___________________
// wiki2html($code) /
function wiki2html($code) {
	global $heads, $printable;
	$heads = array();
	$html = "";
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
		if ($code{$head} == '[' && $code{$head + 1} == '[') {
			$head += 2;
			$cmd = "";
			while ($head < $len) {
				if ($code{$head} == ']' && $code{$head + 1} == ']') {
					$head += 2;
					break;
				}
				$cmd .= $code{$head++};
			}
			$html .= $text.wiki_parse_cmd($cmd);
			$text = "";
			continue;
		}
		// Bold
		if ($code{$head} == '*' && $code{$head + 1} == '*') {
			$strong_on = !$strong_on;
			$html .= $text;
			$text = "";
			$html .= $strong_on ? "<strong class='content'>" : "</strong>";
			$head += 2;
			continue;
		}
		// Italic (emphasis)
		if ($code{$head} == '\'' && $code{$head + 1} == '\'') {
			$em_on = !$em_on;
			$html .= $text;
			$text = "";
			$html .= $em_on ? "<em class='content'>" : "</em>";
			$head += 2;
			continue;
		}
		// Italic (emphasis)
		if ($code{$head} == '_' && $code{$head + 1} == '_') {
			$ul_on = !$ul_on;
			$html .= $text;
			$text = "";
			$html .= $ul_on ? "<u>" : "</u>";
			$head += 2;
			continue;
		}
		// Superscript
		if ($code{$head} == '^' && $code{$head + 1} == '^') {
			$sup_on = !$sup_on;
			$html .= $text;
			$text = "";
			$html .= $sup_on ? "<sup class='content'>" : "</sup>";
			$head += 2;
			continue;
		}
		// Subscript
		if ($code{$head} == ',' && $code{$head + 1} == ',') {
			$sub_on = !$sub_on;
			$html .= $text;
			$text = "";
			$html .= $sub_on ? "<sub class='content'>" : "</sub>";
			$head += 2;
			continue;
		}
		// Strikethrough
		if ($code{$head} == '~' && $code{$head + 1} == '~') {
			$strike_on = ! $strike_on;
			$html .= $text;
			$text = "";
			$html .= $strike_on ? "<strike class='content'>" : "</strike>";
			$head += 2;
			continue;
		}
		// Code (preformatted)
		if ($code{$head} == '%' && $code{$head + 1} == '%') {
			$code_on = ! $code_on;
			$html .= $text;
			$text = "";
			$html .= $code_on ? "<span class='code'>" : "</span>";
			$head += 2;
			continue;
		}
		
		if ($code{$head} == "\n") {
			$html .= "$text\n";
			if (trim($text == "")) {
				$html .= "</p><p class='content'>";
			}else{
				$text = "";
			}
		} else {
			if ($code{$head} != "\r") {
				// $text .= htmlspecialchars($code{$head});
				$text .= ($tmp = htmlspecialchars($code{$head})) ? $tmp : $code[$head]; // "fixes" UTF-8
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

//____________________
// valid_page($page) /
function valid_page($page) {
	for ($i=0; $i<strlen($page); $i++) {
		if (strchr("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_", $page{$i}) === false) // *cough*regex*cough*
			return false;
	}
	return true;
}

//______________________
// post_process($html) /
function post_process($html) {
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
	// convert leading tabs to spaces
	foreach(explode("\n", $string) as $line) {
		$tabs = strspn($line, "\t");
		$output .= str_repeat(' ', $tabs * TAB_LENGTH) . substr($line, $tabs) . "\n";
	}
	// one trailing linebreak
	return rtrim($output)."\n";
}


if (!($db = new SQLite3($dbfile))) {
	die($db->lastErrorMsg());
}

$a     = getparam("a", "view");
$page  = getparam("p", "Welcome");
$html  = "";
$title = $page;

$page = strtoupper($page);
if (!valid_page($page)) {
    $html = "Invalid page name";
    $a = "";
}

if ($a == "install") {
    if (! $db->query("CREATE TABLE pages (time INT, name VARCHAR(255), title VARCHAR(255), content TEXT, ip varchar(64))")) die($db->lastErrorMsg());
    // Add Mikron Syntax page
    $page = <<<SYNTAXCODE
This page describes the syntax that Mikron understands for formatting pages.
If it looks weird, try [[html:<a href='?a=view&p=MIKRON_SYNTAX&mikron'>here</a>]].
When making major edits to this page, consider updating the original source version of it in index.php.

[[h1:Paragraphs]]A single paragraph can span multiple lines as long as there isn't an empty line between them.

[[h1:Text formatting]]
[[*]][[*]]Bold text[[*]][[*]] - **bold text**
[[']][[']]Emphasized text[[']][[']] (these are two single quotes) - ''emphasized text''
[[_]][[_]]Underline[[_]][[_]] - __underlined__
[[^]][[^]]Superscript[[^]][[^]] - ^^superscript^^
[[,]][[,]]Subscript[[,]][[,]] - ,,subscript,,
[[~]][[~]]Strikethrough[[~]][[~]] - ~~strikethrough~~
[[%]][[%]]Code/monospaced[[%]][[%]] - %%Code/monospaced iiiWWW%%
---- on a line by itself (no leading/trailing whitespace) to insert a horizontal rule <hr>
[[[]][red]]Lorem Ipsum[[[]][/]]: [[red]]Lorem Ipsum[[/]]. Bunch of color names are available (try and see), 3 or 6 character hex codes are also supported with leading '#'.

[[h1:Headings]]
[[[]][h1:blarg]] for level 1 heading blarg

[[[]][h2:blerg]] for level 2 heading blerg

[[[]][h3:blorg]] for level 3 heading blorg

[[[]][h4:blirg]] for level 4 heading blirg

[[h1:blarg]]
[[h2:blerg]]
[[h3:blorg]]
[[h4:blirg]]

With four or more headings Mikron creates a table of contents.

[[h1:Commands]]
By placing text in [[[]][ and ]] you can use Mikron commands. These are links to other pages, URLs or special instructions to Mikron.

[[h2:Escapes]]
If a command is a single character, then this character is inserted at the place of the command. So to use the [[[]] character type [[[]][[[]][[[]]]].

[[h2:Links]]
Mikron pages use ALL UPPERCASE NAMES and can contain only english letters and the underscore character (ie. the valid set of characters is ABCDEFGHIJKLMNOPQRSTUVWXYZ_). Using a command which is a valid page name creates a link to that page. For example [[[]][FOO]] creates a link to the FOO page. If FOO has a title specified the FOO's title is displayed for the link's page, otherwise the Foo text will be used (that is the page's name with the first letter as uppercase and the rest as lowercase). To override the title use a colon after the page name, like [[[]][FOO:the Foo page]].

To create a link to a webpage, ftp site or email just use the URL as the command. For example [[[]][http://runtimelegend.com]] creates a link to [[http://runtimelegend.com]]. To use a title put a space (not a colon) after the URL like [[[]][http://runtimelegend.com Runtime Legend]] (for [[http://runtimelegend.com Runtime Legend]]). Mikron understands URLs beginning with http:, https:, ftp:, mailto:, news: and irc:. And magnet:. And possibly more in the future!

Interestingly, [[[]][http:/localpath/file.html]] will also work, for local links, the href will look like http:/localpath/file.html but the browser will cope (chrome anyway) (currently).

[[h2:Images]]
Use [[[]][img:url]] to load an image from the given url. The image will be inserted as a standalone block. To align the image with the text left or right use [[[]][limg:url]] (for left) or [[[]][rimg:url]] (right).

[[h2:Snippets]]
[[#]]time# will be replaced by a timestamp, like [2013-01-09 16:09:08].

[[h2:HTML]]
To insert raw HTML use [[[]][html:...]].
SYNTAXCODE;
    $db->query("INSERT INTO pages (time,name,title,content) VALUES (".time().", 'MIKRON_SYNTAX', 'MIKRON SYNTAX', '".$db->escapeString($page)."')");
    
    // Add Welcome page
    $page = <<<SYNTAXCODE
[[h2:Welcome to the Mikron Wiki!]]
Mikron is a simple wiki system written in PHP.
It is a (currently) standalone single-file script that only needs SQLite (v3) to operate and almost zero setup (a directory where the web server has write access to store the SQLite database file).
If you got this thing from Github, there should be a README.md file with more info.

There isn't much to see here - ''yet''!
SYNTAXCODE;
    $db->query("INSERT INTO pages (time,name,title,content) VALUES (".time().", 'WELCOME', 'Welcome', '".$db->escapeString($page)."')");
    $html = "Install commands issued, hope for the best.<br>
        <a href='./'>Try it out!</a>";
}

// === Printable =========================
if ($a == "printable") {
    $a = "view";
    $printable = true;
} else {
    $printable = false;
}

/*
	$a Values
*/

// === View ==============================
if ($a == "view") {
	$time = getparam("t");
	if ($time == "") {
		$res = $db->query("SELECT datetime(time, 'unixepoch') as lastedit,title,content FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC LIMIT 1");
	}else{
		$res = $db->query("SELECT datetime(time, 'unixepoch') as lastedit,title,content FROM pages WHERE name='".$db->escapeString($page)."' AND time=".intval($time, 10));
	}
    if (! $res) echo '<div style="color: red;">No result, you might need to run <a href="./?a=install">install</a> at this point.</div>';
	$row = $res->fetchArray(SQLITE3_ASSOC);
	if ($row === false) {
		$html = "Page <span class='pagename'>$page</span> not found. <a href='?a=edit&p=$page'>Create it</a>!";
	}else{
		// $row = $row[0];
		$title = htmlspecialchars($row['title']);
		if ($title == "") $title = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
		if ($time != "") $html .= "<div class='contentwarning'>You are looking at an older edit of this page. For the latest version <a href='".$url."?a=view&p=$page'>click here</a>.</div>";
		$html .= wiki2html($row['content']);
		$html = post_process($html);
		if ($html == "") $html = "No content";
		if ($time != "") $html .= "<div class='contentwarning'>To open the editor for this page using the content from this version <a href='".$url."?a=edit&p=$page&t=$time'>click here</a>.</div>";
		$html .= "<div class='lastedit'>Last edit at ".$row['lastedit']." UTC</div>";
	}
}

// === Versions ==========================
if ($a == "versions") {
	// $title = ucwords($page)." (all)";
	$title = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
	$res = $db->query("SELECT datetime(time, 'unixepoch') as lastedit,time,title,content FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC");
	while($row = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
	if ($rows === false) {
		$html = "Page <span class='pagename'>$page</span> not found. <a href='?a=edit&p=$page'>Create it</a>!";
	} else {
		$html .= "Known edits of this page:<ul>";
		$first = true;
		foreach ($rows as $row) {
			$html .= "<li><a href='".$url."?a=view&p=$page";
			if (!$first) $html .= "&t=".$row['time']."'>"; else $html .= "'>";
			$pagetitle = htmlspecialchars($row['title']);
			if ($pagetitle == "") $pagetitle = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
			$html .= $pagetitle."</a> at ".$row['lastedit'];
			if ($first) {
				$first = false;
				$html .= " (current)";
			}
			$html .= "</li>";
		}
		$html .= "</ul>";
	}
}

// === Edit ==============================
if ($a == "edit") {
	if (!$allowedit) {
		$html = "Editing is disabled.";
	} else {
		$time = getparam("t");
		if ($time == "") {
			$res=$db->query("SELECT title,content FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC LIMIT 1");
		}else{
			$res=$db->query("SELECT title,content FROM pages WHERE name='".$db->escapeString($page)."' AND time=".intval($time, 10));
		}
		$row = $res->fetchArray(SQLITE3_ASSOC);
		if ($row === false) {
			$pagetitle = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
			$content = "Type the content for the '$title' page here";
		} else {
			// $row = $row[0];
			$pagetitle = htmlspecialchars($row['title'], ENT_QUOTES);
			if ($pagetitle == "") $pagetitle = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
			$content = $row['content'];
			if ($content == "") $content = "Type the content for the '$pagetitle' page here";
		}
		
		$title = "Edit $pagetitle";
		
		$html = "<form action='$url' method='post'><input type='hidden' name='a' value='store'><input type='hidden' name='p' value='$page'>".
			"<strong>Title</strong> <input type='text' name='title' maxlength='255' value='$pagetitle'><br>".
		"<strong>Content</strong> (<a href='".$url."?a=view&p=MIKRON_SYNTAX&mikron'>Mikron Syntax</a>)<br><textarea style='width: 100%; height: 500px' name='content' wrap='soft'>".trim(htmlspecialchars($content))."</textarea>".
		"<div class='submitcontainer'><input type='submit' value='Save page'><input type='reset' value='Reset form'></div></form>";
		if ($time != "") $html .= "<div class='contentwarning'>Please note that this form will not edit the previous version but will create a new one!</div>";
	}
}

// === Store =============================
if ($a == "store") {
	if (!$allowedit) {
		$html = "Editing is disabled";
	} else {
		$pagetitle = getparam("title", strtoupper($page{0}).strtolower(substr($page, 1, strlen($page))));
		$content = getparam("content");
		if ($content == "") {
			$r=$db->query("DELETE FROM pages WHERE name='".$db->escapeString($page)."'");
		}else{
			$content = pre_store_processing($content);
			$ip = $_SERVER['REMOTE_ADDR'];
			$r=$db->query("INSERT INTO pages (time, name, title, content, ip) VALUES (".
				time().", '".
				$db->escapeString($page)."', '".
				$db->escapeString($pagetitle)."', '".
				$db->escapeString($content)."', '".
				$db->escapeString($ip)."')");
		}
		if ($r === false) {
			$html = "Failed to save $page";
		}else{
			header("Location: ".$url."?a=view&p=$page");
			die();
		}
	}
}

// === Search ============================
if ($a == 'search') {
	$title    = "Search results";
	$html     = '<div id="search_results">';
	$q        = trim($_GET['q']);
	$q_length = strlen($q);
	if (! $q_length) {
		header('Location: http://hermes/mikron/?a=search&q=fnord');
		die();
	}
	$preview_size = 200; // total length of content preview [parts] per found page
	$q_esc = $db->escapeString($q);
	$sql = 
	 "SELECT     datetime(p.time, 'unixepoch', 'localtime') as lastedit, p.name, p.title AS link_title, p.content,
	             (LENGTH(p.content)-LENGTH(REPLACE(LOWER(p.content), LOWER('$q_esc'), '')))/LENGTH('$q_esc') AS occurrences
	  FROM       pages AS p
	  INNER JOIN (
	    SELECT MAX(time) AS max_time, name FROM pages GROUP BY name
	    ) AS l
	    ON      (p.time = l.max_time AND p.name = l.name)
	  WHERE      p.title   LIKE '%$q_esc%'
	    OR       p.content LIKE '%$q_esc%'
	  GROUP BY   p.name
	  ORDER BY   occurrences DESC
	  LIMIT 10";
	$res = $db->query($sql);
	while ($row = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
	if (! empty($rows)) {
		$html .= "Found ". count($rows) ." page".(count($rows)==1?'':'s').":<hr>\n";
		foreach ($rows as $row) {
			extract($row);
			$lastedit = date('H:i:s - l, j F', strtotime($row['lastedit']));
			$link_title = preg_replace("/(".preg_quote($q).")/i", '<span class="highlight">$1</span>', $link_title);
			$content = strip_tags(wiki2html($content));
			$content_length = strlen($content);
			if (stripos($content, $q) === false) {
				// $q must've been found in title only, just show the start of the page.
				$preview = substr($content, 0, $preview_size).(strlen($content) > $preview_size ? '...':'');
				$count = '&lt;- '. substr_count(strtolower($link_title), strtolower($q));
			}else{
				// try and make a nice content preview, lifting out the parts containing the query string and highlighting them
				$count = substr_count(strtolower($content), strtolower($q));
				$preview_parts = min($count, 4);
				$padding = round($preview_size/($preview_parts*2));
				$last_offset = $preview_end = 0;
				$preview = '';
				for ($i=0; $i < $preview_parts; $i++) { 
					$found_at = stripos($content, $q, $last_offset);
					$last_offset = $found_at + $q_length;
					if ($i and ($found_at < ($preview_end + $padding))) {
						// merge this part with the previous
						$old_end = $preview_end;
						$preview_end = min($found_at + $q_length + $padding, $content_length);
						$preview = substr($preview, 0, -3) . substr($content, $old_end, $preview_end - $old_end) . ($preview_end < $content_length ? '...' : '');
					}else{
						$preview_end = min($found_at + $q_length + $padding, $content_length);
						$preview .= ($found_at-$padding > 0 ? '...':'').substr($content, max($found_at-$padding, 0), $q_length + ($padding*2)) . ($preview_end < $content_length ? '...' : '');
					}
				}
				$preview = preg_replace("/(".preg_quote($q).")/i", '<span class="highlight">$1</span>', $preview);
			}
			// .. and add it to output:
			$html .= 
			 "<div class='result' data-name='".htmlspecialchars($name, ENT_QUOTES)."'>
			    <a class='title' href='./?a=view&amp;p=".rawurlencode($name)."'>$link_title</a>: $count ".($count != $occurrences ? "($occurrences)":'')."
			    <div class='preview'>$preview <span class='last_edited'>$lastedit</span></div>
			  </div>\n";
		}
		// googlinks
	}else{
		$html .= 'No pages found.';
	}
	$html .= "</div>";
	
}

// === Last Modified =====================
if ($a == 'last_modified') {
	$sql = 
	 "SELECT     datetime(MAX(time), 'unixepoch', 'localtime') AS lastedit, name, title AS link_title, ip
	  FROM       pages
	  WHERE      name NOT IN ('NEMO')
	  GROUP BY   name
	  ORDER BY   lastedit DESC
	  LIMIT      10";
	$res = $db->query($sql);
	while ($row = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
	$title = "Last modified pages:";
	$html .= "<table id='last_modified'>\n<tr><td>Title</td>\n<td>Last edited</td>\n<td>By</td></tr>\n";
	foreach ($rows as $row) {
		extract($row);
		$lastedit = date('H:i:s - l, j F', strtotime($row['lastedit']));
		if (isset($users[$ip])) $ip = $users[$ip];
		$html .= "<tr><td>\n<a href='?a=view&amp;p=".rawurlencode($name)."'>".$link_title."</a></td><td>$lastedit\n</td><td>$ip\n</td></tr>\n\n";
	}
	$html .= "</table>\n";
}

// --- /End of $a values -----------------


if ($html != "") {
    ?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title><?php print $title." - ".$sitetitle ?></title>
	<?php if ($printable): ?>
		<link rel="stylesheet" href="css/printable.css" type="text/css" media="screen,print" title="no title" charset="utf-8">
	<?php else: ?>
		<link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="utf-8">
	<?php endif; ?>
	<script src='js/jquery.min.js' type='text/javascript'></script>
	<script src="js/keyboardjs.js" type="text/javascript" charset="utf-8"></script>
	<script src="js/script.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="css/mikron.css" type="text/css" media="screen">
	<link rel="shortcut icon" href="css/favicon.ico" />
</head>

<body data-page="<?php echo htmlspecialchars($page); ?>">

<div>
<div id="sidebar">
	<div id="sitetitle"><small><?php echo $sitetitle ?></small></div>
	<a class="sidelink"  href="<?php echo $url ?>?a=view&p=Welcome">Go to the welcome page</a>
	<?php if ($page != "") { ?>
		<a class="sidelink" href="<?php echo $url ?>?a=printable&p=<?php echo $page ?>">Printable version</a>
		<?php if ($allowedit) { ?>
		<a class="sidelink" href="<?php echo $url ?>?a=edit&p=<?php echo $page ?>">Edit this page</a>
		<?php } else if ($editurl != "") { ?>
		<a class="sidelink" href="<?php echo $editurl ?>">Edit the wiki</a>
		<?php } ?>
		<a class="sidelink" href="<?php echo $url ?>?a=versions&p=<?php echo $page ?>">Older edits of this page</a>
	<?php } ?>
	<a class="sidelink" href="<?php echo $url ?>?a=last_modified">See last changes</a>
	<div class='shortcutKeyList'>
		Keyboard shortcuts:<br>
		- <span class='shortcutKeys'>I</span>ndex page<br>
		- <span class='shortcutKeys'>S</span>earch<br>
		- <span class='shortcutKeys'>E</span>dit<br>
		- <span class='shortcutKeys'>L</span>ast changes<br>
		- <span class='shortcutKeys'>H</span>istory for this page<br>
		- <span class='shortcutKeys'>Esc</span>ape = cancel editing<br>
		- <span class='shortcutKeys'>0-9</span> jump to nth link<br>
		- <span class='shortcutKeys'>Alt</span> show link numbers
	</div>
	<div class="padding">
		You: <?php echo $_SERVER['REMOTE_ADDR']; ?>
	</div>
	
	
</div>

<div id="content">
<div id="pagetitle"><small><?php print $title ?></small></div>
<div id="pagecontent"><?php print $html ?><div>

</div>
</div>

<script type="text/javascript" charset="utf-8">
	// focus on textarea when editing
	var ta = document.getElementsByTagName('textarea');
	if (typeof ta[0] != 'undefined') ta[0].focus(); // (*damn* JS is picky..)
</script>

</body>
</html>
<?php
}

/* --- Log -------------------------------

[2013-02-20 14:37:01] Keyboard link number shortcuts, they show up with Alt key
[2013-01-21 11:30:56] UTF8 should work now.
[2013-01-18 13:33:42] Fixed shortcuts getting in the way of command/meta key combo's
[2013-01-17 11:55:32] Search result divs clickable (aka: "FINE. JQUERY. THERE.")
                      Fixed substring count SQL thinger (REPLACE is case sensitive)
[2013-01-17 10:33:22] uncommented 'auth' include line (why was this commented out again?)
[2013-01-16 15:56:19] spent precious hours on implementing search, with pretty highlighted results.
[2013-01-09 17:14:21] added #time# to pre_store_processing()
                      added a second ---- > <hr> replace in post_process()
[2012-12-17 16:21:10] added "last_modified" view, storing & showing ip address for this too

Todo: use str_split_unicode(), rewrite parser to run through UTF characters instead of bytes.
Todo: search: $count is set several times seperately, should be a sum. Also, there's "occurrences" in the result rows (they're sorted by that), could use that too -> still have to check the title too, though (in php).
      - click from search results, if q in content, highlight it (url: "foo..&highlight=$q#result1") and add id=result1, or <a>, whatever works.
      - prettify search preview, strip_tags should leave spaces in between words or something; maybe str_replace('<', ' <', $preview) ?
      - search for 'index', the PHP5 preview is too short for some reason..
Todo: move <head> style to css file again, and merge last_modified results / search results a little maybe?
Todo: possibly cache rendered content, simplifies showing search results and maybe other stuffs.

--------------------------------------- */
