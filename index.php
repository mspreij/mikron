<?php
$auth_file     = 'inc/auth.inc.php';
$settings_file = 'inc/settings.inc.php';
if (file_exists($auth_file)) {
    require_once $auth_file;
}else{
    echo "Auth file missing, copy and edit *.sample file in inc/. See comments in that file for more info.";
    die();
}
// defaults
$sitetitle   = "Mikron";
$dbfile      = "data/mikron.db";
$formats     = ['markdown', 'mikron'];
$stylesheets = [];
$users       = [
    '127.0.0.1' => 'A. Utho',
];
// customization
if (file_exists($settings_file))  require_once $settings_file;
if (! defined('TAB_LENGTH'))      define('TAB_LENGTH', 2);

$allowedit  = true;
$editurl    = ""; // use if $allowedit is false to put a link to an editable URL

$url = "//".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

if ($url{strlen($url)-1} == '?') $url = substr($url, 0, strlen($url)-1);

require_once 'inc/app.inc.php';

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
    if (! $db->query("CREATE TABLE pages (time INT, name VARCHAR(255), title VARCHAR(255), format VARCHAR(255), content TEXT, ip varchar(64))")) die($db->lastErrorMsg());
    // Add Mikron Syntax page
    $page = file_get_contents('inc/syntax_template.txt');
    $db->query("INSERT INTO pages (time,name,title,content) VALUES (".time().", 'MIKRON_SYNTAX', 'MIKRON SYNTAX', '".$db->escapeString($page)."')");
    
    // Add Welcome page
    $page = file_get_contents('inc/welcome_template.txt');
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
		$res = $db->query("SELECT datetime(time, 'unixepoch') as lastedit, title, format, content FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC LIMIT 1");
	}else{
		$res = $db->query("SELECT datetime(time, 'unixepoch') as lastedit, title, format, content FROM pages WHERE name='".$db->escapeString($page)."' AND time=".intval($time, 10));
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
		$html .= wiki2html($row['content'], $row['format']);
		if ($row['format'] == 'mikron') {
		    $html = post_process($html);
        }
		if ($row['format'] == 'markdown') {
		    $stylesheets[] = 'markdown.css';
        }
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
			$res=$db->query("SELECT title, content, format FROM pages WHERE name='".$db->escapeString($page)."' ORDER BY time DESC LIMIT 1");
		}else{
			$res=$db->query("SELECT title, content, format FROM pages WHERE name='".$db->escapeString($page)."' AND time=".intval($time, 10));
		}
        $format = 'markdown';
		$row = $res->fetchArray(SQLITE3_ASSOC);
		if ($row === false) {
			$pagetitle = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
			$content = "Type the content for the '$title' page here";
		} else {
			// $row = $row[0];
			$pagetitle = htmlspecialchars($row['title'], ENT_QUOTES);
			if ($pagetitle == "") $pagetitle = strtoupper($page{0}).strtolower(substr($page, 1, strlen($page)));
			$content = $row['content'];
			$format  = $row['format'];
			if ($content == "") $content = "Type the content for the '$pagetitle' page here";
		}
		
		$title = "Edit $pagetitle";
		
		$html = "
        <form action='$url' method='post'>
            <input type='hidden' name='a' value='store'>
            <input type='hidden' name='p' value='$page'>
            <strong>Title</strong> <input type='text' name='title' maxlength='255' value='$pagetitle'><br>
            <strong>Content</strong> (<a href='".$url."?a=view&p=MIKRON_SYNTAX&mikron'>Mikron Syntax</a>)
                ".selectList('format', $formats, $format, ['usekeys'=>0,'return'=>1])."
                <br>
            <textarea style='width: 100%; height: 500px' id='editTextarea' name='content' wrap='soft'>".trim(htmlspecialchars($content))."</textarea>
            <div class='submitcontainer'>
                <input type='submit' value='Save page'>
                <input type='reset' value='Reset form'>
            </div>
        </form>";
		if ($time != "") $html .= "<div class='contentwarning'>Please note that this form will not edit the previous version but will create a new one!</div>";
	}
}

// === Store =============================
if ($a == "store") {
	if (!$allowedit) {
		$html = "Editing is disabled";
	} else {
		$pagetitle = getparam("title", strtoupper($page{0}).strtolower(substr($page, 1, strlen($page))));
		$content   = getparam("content");
		$format    = getparam("format");
		if ($content == "") {
			$r=$db->query("DELETE FROM pages WHERE name='".$db->escapeString($page)."'"); // deletes history as well. maybe look into?
		}else{
			$content = pre_store_processing($content);
			$ip = $_SERVER['REMOTE_ADDR'];
			$r=$db->query("INSERT INTO pages (time, name, format, title, content, ip) VALUES (".
				time().", '".
				$db->escapeString($page)."', '".
				$db->escapeString($format)."', '".
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
		header("Location: $url?a=search&q=fnord");
		die();
	}
	$preview_size = 200; // total length of content preview [parts] per found page
	$q_esc = $db->escapeString($q);
	$sql = 
	 "SELECT     datetime(p.time, 'unixepoch', 'localtime') as lastedit, p.name, p.title AS link_title, p.content, p.format,
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
			$link_title = preg_replace("/(".preg_quote($q, '/').")/i", '<span class="highlight">$1</span>', $link_title);
			$content = strip_tags(wiki2html($content, $format ?: 'markdown'));
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
				$preview = preg_replace("/(".preg_quote($q, '/').")/i", '<span class="highlight">$1</span>', $preview);
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
    <?php
    if ($printable) {
        echo '<link rel="stylesheet" href="css/printable.css" type="text/css" media="screen,print">';
    } else {
        echo '<link rel="stylesheet" href="css/style.css" type="text/css" media="screen">';
    }
    echo "\n";
    foreach ($stylesheets as $stylesheet) {
        echo '    <link rel="stylesheet" href="css/'.$stylesheet.'">'."\n";
    }
    ?>
    <script src='js/jquery.min.js' type='text/javascript'></script>
    <script src="js/keyboard.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/script.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="css/mikron.css" type="text/css" media="screen">
    <link rel="shortcut icon" href="css/favicon.ico"/>
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
		- <span class='shortcutKeys'>Ctrl</span> show link numbers
	</div>
	<div class="padding">
		You: <?php echo $_SERVER['REMOTE_ADDR']; ?>
	</div>
</div><!-- /sidebar -->

<div id="content">
    <div id="pagetitle"><?php print $title ?></div>
    <div id="pagecontent"><?php print $html ?><div>
</div>

</div>

<script type="text/javascript" charset="utf-8">
	// focus on textarea when editing
	var ta = document.getElementById('editTextarea');
	if (ta && typeof ta.tagName !== 'undefined') ta.focus();
</script>

</body>
</html>
<?php
}

/* --- Log -------------------------------

Todo: use str_split_unicode(), rewrite parser to run through UTF characters instead of bytes.
Todo: search: $count is set several times seperately, should be a sum. Also, there's "occurrences" in the result rows (they're sorted by that), could use that too -> still have to check the title too, though (in php).
    - click from search results, if q in content, highlight it (url: "foo..&highlight=$q#result1") and add id=result1, or <a>, whatever works.
    - prettify search preview, strip_tags should leave spaces in between words or something; maybe str_replace('<', ' <', $preview) ?
    - search for 'index', the PHP5 preview is too short for some reason..
Todo: move <head> style to css file again, and merge last_modified results / search results a little maybe?
Todo: possibly cache rendered content, simplifies showing search results and maybe other stuffs.

--------------------------------------- */
