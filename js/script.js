/*
Handle some keyboard shortcuts.
  "e" for edit
  "i" for index page
  "esc" to cancel editing

*/

$(function(){
  
  var spare_height = $('body').height() - $('textarea').height();
  
  $(window).on('resize', function() {
    var window_height = $(this).height();
    $('textarea').height(window_height - spare_height);
  });
  
  
  // Search results clickable
  $('#search_results .result').on('click', function() {
    var _this = $(this);
    location.href = './?a=view&p=' + _this.data('name');
  });
  
});


if (location.href.indexOf('a=edit')==-1) { // *not* editing, catch edit & index shortcuts
  
  // e: edit current page
  KeyboardJS.on('e', function(e) {
    // using a setTimeout callback for the "redirect"; FF remembers JS state which would confuse the script when using backbutton
    // (https://github.com/RobertWHurst/KeyboardJS/issues/42)
    var new_location;
    if (location.href.indexOf('a=view') > 0) {
      new_location = location.href.replace('a=view', 'a=edit');
    }else{
      new_location += (location.href.indexOf('?')>0 ? '&' : '?') + 'a=edit';
    }
    setTimeout(function() {
      location.href = new_location;
    }, 50); // this starts to fail if it's lower than about 40, for some strange reason.. (thank you for your patience!)
  });
  
  // i: go to index page
  KeyboardJS.on('i', function(e) {
    if (e.metaKey) return; // Cmd-I = page info
    setTimeout(function() {
      location.href = './?a=view';
    }, 50); // I've seen this work. I've also seen it not work.
  });
  
  // l: go to Last Modified page
  KeyboardJS.on('l', function(e) {
    if (e.metaKey) return; // Cmd-L = jump to location field
    setTimeout(function() {
      location.href = './?a=last_modified';
    }, 50);
  });
  
  // c: go to Contact page
  KeyboardJS.on('c', function(e) {
    if (e.metaKey) return; // Cmd-C = copy
    setTimeout(function() {
      location.href = './?a=view&p=CONTACT';
    }, 50);
  });
  
  // n: go to Nemo page (yarr)
  KeyboardJS.on('n', function(e) {
    if (e.metaKey) return; // Cmd-N = new window
    setTimeout(function() {
      location.href = './?a=view&p=NEMO';
    }, 50);
  });
  
  // s: search popup
  KeyboardJS.on('s', function(e) {
    if (e.metaKey) return; // Cmd-S = save
    var query = prompt("Search for:");
    if (query) {
      location.href = './?a=search&q='+encodeURIComponent(query.trim());
    }
  });
  
  // alt key: Show link keyboard-shortcut numbers
  KeyboardJS.on('alt', function(e) {
    $('#pagecontent a[href]').not('.toc').each(function(i, e) {
      $(e).html($(e).html()+'<span class="numberlink"> '+(i+1)+'</span>');
    });
  }, function(e) {
    $('#pagecontent a[href]').not('.toc').each(function(i, e) {
      $('span', e).last().remove();
    });
  });
  
  // Jump to link
  KeyboardJS.on('1,2,3,4,5,6,7,8,9,0', function(e) {
    var links;
    var keys = '';
    var next_key_delay;
    return function(e) {
      if (e.metaKey) return; // Cmd-S = save
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
        document.location.href = link.attr('href');
        link.css({fontWeight: 'normal'}); // un-bold link (in case people hit the backbutton)
      }, 400);
    };
  }());
  
  // any other shortcuts for non-editing go here..
  
}else{ // User is editing, catch escape shortcut
  // esc: bail
  KeyboardJS.on('esc', function() {
    if (confirm('Lose changes?')) {
      location.href = location.href.replace('a=edit', 'a=view');;
    }
  });
}
