<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title><?php print htmlents($this->title.($this->sitetitle ? " - ".$this->sitetitle : '')); ?></title>
    <?php
    echo '<link rel="stylesheet" href="css/style.css" type="text/css" media="screen">';
    echo "\n";
    foreach ($this->stylesheets as $stylesheet) {
        echo '    <link rel="stylesheet" href="css/'.$stylesheet.'">'."\n";
    }
    ?>
    <script src='js/jquery.min.js' type='text/javascript'></script>
    <script src="js/keyboard.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/script.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="css/mikron.css" type="text/css" media="screen">
    <link rel="shortcut icon" href="css/favicon.ico"/>
</head>

<body data-page="<?php echo htmlents($this->page); ?>">

<div>
    <div id="sidebar">
        <div id="sitetitle"><small><?php echo $this->sitetitle; ?></small></div>
        <a class="sidelink"  href="<?php echo $this->url; ?>?a=view&p=Welcome">Go to the welcome page</a>
        <?php if ($this->page != "") { ?>
            <a class="sidelink" href="<?php echo $this->url ?>?a=printable&p=<?php echo $this->page ?>">Printable version</a>
            <a class="sidelink" href="<?php echo $this->url ?>?a=edit&p=<?php echo $this->page ?>">Edit this page</a>
            <a class="sidelink" href="<?php echo $this->url ?>?a=versions&p=<?php echo $this->page ?>">Older edits of this page</a>
        <?php } ?>
        <a class="sidelink" href="<?php echo $this->url ?>?a=last_modified">See last changes</a>
        <div class="shortcutKeyList" style="display: none;">
            Keyboard shortcuts:<br>
            - <span class='shortcutKeys'>I</span>ndex page<br>
            - <span class='shortcutKeys'>S</span>earch<br>
            - <span class='shortcutKeys'>E</span>dit<br>
            - <span class='shortcutKeys'>L</span>ast changes<br>
            - <span class='shortcutKeys'>H</span>istory for this page<br>
            - <span class='shortcutKeys'>Esc</span>ape = cancel editing<br>
            - <span class='shortcutKeys'>0-9</span> jump to nth link<br>
            - <span class='shortcutKeys' id='linkNumbersKey'>Ctrl</span> show link numbers<br>
            - <span class='shortcutKeys'>left/right</span> previous/next in search results
        </div>
        <div class="padding">
            You: <?php echo $_SERVER['REMOTE_ADDR']; ?>
        </div>
    </div><!-- /sidebar -->
    
    <div id="content">
        <div id="pagetitle"><?php print $this->title; ?></div>
        <div id="pagecontent"><?php print $this->body; ?></div>
    </div>
</div>

<script type="text/javascript" charset="utf-8">
    // focus on textarea when editing
    var ta = document.getElementById('editTextarea');
    if (ta && typeof ta.tagName !== 'undefined') ta.focus();
</script>

</body>
</html>
