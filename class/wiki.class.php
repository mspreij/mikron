<?php
/**
* Thing that handles bunches, created while refactoring. I think the first non-vendor class in here?
* I need to think up names still.
* 
*/

class Wiki
{
    
    public $action = 'view';
    public $page = '';
    public $template = 'templates/mikron.php';
    public $stylesheets = ['markdown.css'];
    
    protected $db;
    protected $allowedit = true;
    
    protected $actions = [
        'view',
        'versions',
        'edit',
        'store',
        'search',
        'last_modified',
        'install',
    ];
    
    
    // == Constructor ==
    function __construct($db, $page, $action)
    {
        $this->db = $db;
        $this->action = $action;
        if (!valid_page($page)) {
            $this->html = "Invalid page name";
            $this->action = "";
            $this->page = "__invalid__";
        }else{
            $this->page = $page;
        }
        foreach (settings() as $key => $value) {
            $this->$key = $value;
        }
        $this->url = "//".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    }
    
    // handle_action()
    public function handle_action()
    {
        switch ($this->action) {
            case 'view':
                list($title, $body) = $this->view();
                break;
            case 'edit':
                list($title, $body) = $this->edit();
                break;
            case 'store':
                list($title, $body) = $this->store();
                break;
            case 'versions':
                list($title, $body) = $this->versions();
                break;
            case 'versions':
                $this->body = $this->versions();
                break;
            
            default:
                $title = "Huh? ($this->page)";
                $body = "I have no idea what you want..";
                break;
        }
        $this->title = $title;
        $this->body = $body;
    }
    
    // output
    public function output($print=true)
    {
        ob_start();
        require_once $this->template;
        $html = ob_get_clean();
        if (! $print) return $html;
        echo $html;
    }
    
    // === View ==========================
    protected function view()
    {
        $time = getparam("t");
        if ($time == "") {
            $res = $this->db->query("SELECT datetime(time, 'unixepoch') as lastedit, title, format, content FROM pages WHERE name='".$this->db->escapeString($this->page)."' ORDER BY time DESC LIMIT 1");
        }else{
            $res = $this->db->query("SELECT datetime(time, 'unixepoch') as lastedit, title, format, content FROM pages WHERE name='".$this->db->escapeString($this->page)."' AND time=".intval($time, 10));
        }
        if (! $res) echo '<div style="color: red;">No result, you might need to run <a href="./?a=install">install</a> at this point.</div>';
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === false) {
            $body = "Page <span class='pagename'>$this->page</span> not found. <a href='?a=edit&p=$this->page'>Create it</a>!";
        }else{
            // $row = $row[0];
            $title = htmlspecialchars($row['title']);
            if ($title == "") $title = strtoupper($this->page[0]).strtolower(substr($this->page, 1, strlen($this->page)));
            if ($time != "") $body = "<div class='contentwarning'>You are looking at an older edit of this page. For the latest version <a href='".$this->url."?a=view&p=$this->page'>click here</a>.</div>";
            $body = wiki2html($row['content'], $row['format']);
            if ($row['format'] == 'mikron') {
                $body = post_process($body);
            }
            if ($row['format'] == 'markdown') {
                $stylesheets[] = 'markdown.css';
            }
            if ($body == "") $body = "No content";
            if ($time != "") $body .= "<div class='contentwarning'>To open the editor for this page using the content from this version <a href='".$this->url."?a=edit&p=$this->page&t=$time'>click here</a>.</div>";
            $body .= "<div class='lastedit'>Last edit at ".$row['lastedit']." UTC</div>";
        }
        $body .= "<hr><br>v import settings into wiki class somehow.. bunch of separate global variables right now, turn into array somewhere? ini/yml/thing file? PHP more flexible..<br>
        - then continue handling the actions<br>
          v saving ('store')<br>
          - viewing older versions<br>
        - init various other wiki properties that appear in multiple actions and the template<br>
        - pull queries into methods for now; prefix db_ or something, should be nicer for plugins too";
        return [$title, $body];
    }

    // === Versions ======================
    protected function versions() {
        $title = strtoupper($this->page[0]).strtolower(substr($this->page, 1, strlen($this->page)));
        $res = $this->db->query("SELECT datetime(time, 'unixepoch') as lastedit, time, title, content FROM pages WHERE name='".$this->db->escapeString($this->page)."' ORDER BY time DESC");
        $rows = [];
        while($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        if ($rows === []) {
            $body = "Page <span class='pagename'>$this->page</span> not found. <a href='?a=edit&p=$this->page'>Create it</a>!";
        } else {
            $body = "Known edits of this page:<ul>";
            $first = true;
            $title = $rows[0]['title'];
            foreach ($rows as $row) {
                $link_title = $row['title'];
                $body .= "<li><a href='".$this->url."?a=view&p=$this->page";
                if (!$first) $body .= "&t=".$row['time']."'>"; else $body .= "'>";
                $link_title = htmlspecialchars($link_title);
                if ($link_title == "") $link_title = strtoupper($this->page[0]).strtolower(substr($this->page, 1, strlen($this->page)));
                $body .= $link_title."</a> at ".$row['lastedit'];
                if ($first) {
                    $first = false;
                    $body .= " (current)";
                }
                $body .= "</li>";
            }
            $body .= "</ul>";
        }
        return [$title, $body];
    }
    
    // === Edit ==============================
    protected function edit($row=[]) {
        if (!$this->allowedit) {
            $body = "Editing is disabled.";
        } else {
            if (isset($row['title']) && isset($row['content']) /* && isset($row['format']) */ ) {
                // twiddle thumbs..
            }else{
                // fetch
                $time = getparam("t");
                if ($time == "") {
                    $res=$this->db->query("SELECT title, content, format FROM pages WHERE name='".$this->db->escapeString($this->page)."' ORDER BY time DESC LIMIT 1");
                }else{
                    $res=$this->db->query("SELECT title, content, format FROM pages WHERE name='".$this->db->escapeString($this->page)."' AND time=".intval($time, 10));
                }
                $row = $res->fetchArray(SQLITE3_ASSOC);
            }
            if (! $row) {
                $pagetitle = $title = ucfirst(strtolower($this->page));
                $content = "Type the content for the '$title' page here";
                $format = "markdown";
            } else {
                $title = $row['title'];
                $pagetitle = htmlspecialchars($title, ENT_QUOTES);
                if ($pagetitle == "" or 1) $pagetitle = ucfirst(strtolower($this->page));
                $content = $row['content'];
                $format  = $row['format'];
                if ($content == "") $content = "Type the content for the '$pagetitle' page here";
            }
            $warn_err = isset($row['error']) ? $row['error'] : '';
            $body = "
                $warn_err
                <form action='$this->url' method='post'>
                <input type='hidden' name='a' value='store'>
                <input type='hidden' name='p' value='$this->page'>
                <strong>Title</strong> <input type='text' name='title' maxlength='255' value='$pagetitle'><br>
                <strong>Content</strong> ".($format === 'mikron' ? styled("Warning: mikron formatting, will change to markdown upon saving", 'red'):'')."
                <br>
                <textarea style='width: 100%; height: 500px' name='content' id='editTextarea' wrap='soft'>".trim(htmlspecialchars($content))."</textarea>
                <div class='submitcontainer'>
                <input type='submit' value='Save page'>
                <input type='reset' value='Reset form'>
                </div>
                </form>";
            if ($time != "") {
                $body .= "<div class='contentwarning'>Please note that this form will not edit the previous version but will create a new one!</div>";
            }
        }
        return [$title, $body];
    }
    
    // === Store =========================
    protected function store() {
        $pagetitle = getparam("title", strtoupper($this->page[0]).strtolower(substr($this->page, 1, strlen($this->page))));
        $content   = getparam("content");
        $format    = getparam("format");
        if ($content === "") {
            $r=$this->db->query("DELETE FROM pages WHERE name='".$this->db->escapeString($this->page)."'"); // deletes history as well. it's a feature!
        }else{
            $content = pre_store_processing($content);
            $ip = $_SERVER['REMOTE_ADDR'];
            // todo: welke masochist heeft dit gelayout. gebruik een array met join() ofzo?
            $res = $this->db->query("INSERT INTO pages (time, name, format, title, content, ip) VALUES (".
                time().", '".
                $this->db->escapeString($this->page)."', '".
                $this->db->escapeString($format)."', '".
                $this->db->escapeString($pagetitle)."', '".
                $this->db->escapeString($content)."', '".
                $this->db->escapeString($ip)."')");
        }
        if ($res === false) {
            $body = "Failed to save $this->page";
        }else{
            header("Location: ".$this->url."?a=view&p=$this->page");
            die();
        }
    }
    
    // === Search ========================
    protected function search() {
        $title    = "Search results";
        $html     = '<div id="search_results">';
        $q        = trim($_GET['q']);
        $q_length = strlen($q);
        $lpp      = 10; // links per page for pagination
        if (! $q_length) {
            header("Location: $this->url"); // this allows ?a=search&q=%s bookmarks
            die();
        }
        $preview_size = 200; // total length of content preview [parts] per found page
        $q_esc = $this->db->escapeString($q);
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
        ORDER BY   occurrences DESC"; // no limit: there isn't any gain (according to some dude online 15 years ago), just select everything and paginate in PHP.
        
        $res = $db->query($sql);
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
        $totalFound = count($rows);
        $page = (int) @$_GET['page'];
        $rows = array_slice($rows, $page * $lpp, $lpp);
        
        if (! empty($rows)) {
            $html .= "Found $totalFound page".($totalFound==1?'':'s').":<hr>\n";
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
            if ($totalFound > $lpp) $html .= '<br>'. googlinks($lpp, $totalFound);
        }else{
            $html .= 'No pages found.';
        }
        $html .= "</div>";
        
    }
    
    // === Last Modified =====================
    protected function last_modified() {
        $sql = 
            "SELECT     datetime(MAX(time), 'unixepoch', 'localtime') AS lastedit, name, title AS link_title, ip
             FROM       pages
             WHERE      name NOT IN ('SECRET PAGES TODO ADD THESE')
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
    
    protected function install() {
        if (! $this->db->query("CREATE TABLE pages (time INT, name VARCHAR(255), title VARCHAR(255), format VARCHAR(255), content TEXT, ip varchar(64))")) die($this->db->lastErrorMsg());
        // Add Mikron Syntax page
        $page = file_get_contents('inc/syntax_template.txt');
        $db->query("INSERT INTO pages (time,name,title,content) VALUES (".time().", 'MIKRON_SYNTAX', 'MIKRON SYNTAX', '".$db->escapeString($page)."')");
        
        // Add Welcome page
        $page = file_get_contents('inc/welcome_template.txt');
        $db->query("INSERT INTO pages (time,name,title,content) VALUES (".time().", 'WELCOME', 'Welcome', '".$db->escapeString($page)."')");
        $html = "Install commands issued, hope for the best.<br>
            <a href='./'>Try it out!</a>";
    }
    
}
