var $page;

$(function(){
    
    $page = $('body').data('page');
    
    // Search results clickable
    $('#search_results .result').on('click', function() {
        var _this = $(this);
        location.href = './?a=view&p=' + _this.data('name');
    });
    
    // arrow keys for prev/next in search pagination (for starters)
    if ($('#googlinks').length > 0) {
        keyboardJS.on('right', function(e) {
            if (e.metaKey || e.ctrlKey) return;
            let href = $('#googlinks a:contains("next")').attr('href');
            if (href) location.href = href;
        });
        
        keyboardJS.on('left', function(e) {
            if (e.metaKey || e.ctrlKey) return;
            let href = $('#googlinks a:contains("previous")').attr('href');
            if (href) location.href = href;
        });
    }
    $('#linkNumbersKey').text( (getOS() === 'macOS') ? 'Alt' : 'Ctrl');
    
    setNumberLinksForUrls();
});

function setNumberLinksForUrls(){
    let pagePart = document.getElementById('pagecontent');
    let els = pagePart.getElementsByTagName('a');

    let template = '<span class="numberlink" style="display: none;"> {num}</span>';
    var x = 0;
    for (let i = 0; i < els.length; i++) {
        var link = els.item(i);
        if (link.href == '') continue;
        var tmp = template.replace("{num}", ++x);
        link.innerHTML += tmp;
    }
}


if (location.href.indexOf('a=edit')==-1) { // *not* editing, catch edit & index shortcuts
    
    // e: edit current page
    keyboardJS.on('e', function(e) {
        // using a setTimeout callback for the "redirect"; FF remembers JS state which would confuse the script when using backbutton
        // (https://github.com/RobertWHurst/KeyboardJS/issues/42)
        // might be time to take another look at this because it was ages ago and I think another version of keyboardJS came out anyway?
        var new_location = '';
        if (location.href.indexOf('a=view') > 0) {
            new_location = location.href.replace('a=view', 'a=edit');
        }else{
            new_location += (location.href.indexOf('?')>0 ? '&' : '?') + 'a=edit';
        }
        setTimeout(function() {
            location.href = new_location;
        }, 50); // this starts to fail if it's lower than about 40, for some strange reason.. (thank you for your patience!)
    });
    
    
    $(function(){
    
    var shortcutList = {
      "i": './?a=view',
      "h": './?a=versions&p='+$('body').data('page'),
      "l": './?a=last_modified',
      // "w": './?a=view&p=WIKI',
      // "u": './?a=view&p=URLS',
      // "p": './?a=view&p=PROJECTS',
      // "t": './?a=view&p=TODO'
    };
    
    for (var shortcut in shortcutList) {
      
      keyboardJS.on(shortcut, (function(shortcut) {
        var target = shortcutList[shortcut];
        return function(e) {
          if (e.metaKey || e.ctrlKey) return; // allow default browser shortcuts
          setTimeout(function() {
            location.href = target;
          }, 50);
        };
       })(shortcut)
      );
      
    }
    
    });
    
    
    // s: search popup
    keyboardJS.on('s', function(e) {
        if (e.metaKey || e.ctrlKey) return;
        var query = prompt("Search for:");
        if (query) {
            location.href = './?a=search&q='+encodeURIComponent(query.trim());
        }
    });

    // alt key: Show link keyboard-shortcut numbers
    keyboardJS.on((getOS() === 'macOS') ? 'alt' : 'ctrl', function(e) {
        $('.numberlink, .shortcutKeyList').show();
    }, function(e) {
        $('.numberlink, .shortcutKeyList').hide();
    });

    // Jump to link
    keyboardJS.on(['1','2','3','4','5','6','7','8','9','0'], function(e) {
        var links;
        var keys = '';
        var next_key_delay;
        return function(e) {
            if (e.metaKey || e.ctrlKey) return; // Cmd-S = save
            var key = e.which-48;
            if (! links) links = $('#pagecontent a[href]').not('.toc');
            if (keys.length) {
                console.log('Current keys: ' + keys);
                console.log('Clearing timeout for new key '+key);
                clearTimeout(next_key_delay);
                links.eq(keys-1).css({fontWeight: 'normal'});
            }
            keys = keys + '' + key;
            // todo: this fixes a bug (? https://github.com/RobertWHurst/KeyboardJS/issues/56) in keyboardjs, but restricts to < 100 links
            if (keys.length > 2) keys = keys.slice(0, 2);
            var link = links.eq(keys-1);
            if (! link.length) {
                console.log('unknown link'); // link not found, reset keys and return
                keys = '';
                return;
            }
            links.eq(keys-1).css({fontWeight: 'bold'}); // make chosen link bold
            console.log('Setting delay for link nr '+keys+' ...');
            next_key_delay = setTimeout(function() {
                console.log('and jumping');
                var use_keys = keys;
                keys = '';
                // link.trigger('click'); // why doesn't this work?

                // Changed the location.href to window.open, just so we can open links in a new tab;
                var linkTarget = link.attr("target");
                var windowName = (linkTarget == undefined || linkTarget == false) ? "_self": "_blank";
                window.open(link.attr('href'), windowName);

                // document.location.href = link.attr('href');
                link.css({fontWeight: 'normal'}); // un-bold link (in case people hit the backbutton)
            }, 400);
        };
    }());
    
    $(function(){
    
    $('.selectOnFocus').on('focus', function () {
        $(this).select();
    });
    
    });
    
    // any other things for non-editing go here..
    
}else{ // User is editing
    
    $(function(){ // THIS MAKES IT WORK OK
        // Resize the editing textarea to take up space available for it
        var nonTextareaVertical = $('#editTextarea').offset().top + 50; // 50px for the save/reset buttons
        $('#editTextarea').height($('body').height() - nonTextareaVertical);
        $(window).on('resize', function() {
            $('#editTextarea').height($('body').height() - nonTextareaVertical);
        }); // (you can maybe probably do this with CSS? but then this works without having to tear more hair out)
    });
    
    // esc: bail
    keyboardJS.on('esc', function() {
        if (confirm('Lose changes?')) {
            location.href = location.href.replace('a=edit', 'a=view');;
        }
    });
}

// https://stackoverflow.com/a/38241481/126584
function getOS() {
    var userAgent = window.navigator.userAgent,
        platform = window.navigator.platform,
        macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
        windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
        iosPlatforms = ['iPhone', 'iPad', 'iPod'],
        os = null;

    if (macosPlatforms.indexOf(platform) !== -1) {
        os = 'macOS';
    } else if (iosPlatforms.indexOf(platform) !== -1) {
        os = 'iOS';
    } else if (windowsPlatforms.indexOf(platform) !== -1) {
        os = 'Windows';
    } else if (/Android/.test(userAgent)) {
        os = 'Android';
    } else if (!os && /Linux/.test(platform)) {
        os = 'Linux';
    }

    return os;
}
