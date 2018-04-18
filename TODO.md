This is *messy*, I tried to categorize it but there will be outdated, duplicate and overlapping todo items.

**Technical:**

- split up index.php a little, and refactor. The installation/init code (and/or syntax template) at least can go in separate file(s), esp when adding prefs/session tables
- there is a crazy notion that Markdown - or commonMark, http://commonmark.org - would work, but we'd have to hack in wiki-style links and colors.. post-process? That could actually work, with the double-square bracket style. All the current formatting options could be left to markdown(/cm). Figure out a composer version of that, maybe possibly, in case this ever goes github. While you're at it, get a markdown-editor textarea (cmd/ctrl-B for bold etc), that doesn't interfere with the current shortcut keys thing (it shouldn't).
  - also check what Drawlang is up to wrt parsing
- look at https://github.com/victorstanciu/Wikitten/ just for fun
- a few todos here concern settings/prefs in wiki pages vs hardcoded, maybe just add a table "settings" with name=value pairs for whatevers. like for example the IP -> name map, or the shortcuts -> pages.
- also look at session preferences, possibly for different modifier keys for shortcuts if that makes 'm work better for Chrome (say)

- can/should the sidebar and the stylesheets be wiki'd as well?
- never mind the shortcut keys.. (additional prefs table? same db)

- √ better idea: make an array/object in JS that's more easily editable that handles the thing.
- make the shortcut keys object a non-repo file, just have a shortcutkeys.sample.js file (in the repo) that can be loaded if shortcutkeys.js doesn't exist.
  - orrrrrrrrrrrrrr... *page settings* (a tab or something in the edit mode). Which can include a shortcut?
- ? also show the shortcut key (if any) next to the title for the current page - and possibly in the links, too.


**Bugs:**

- this alt-key thing - make it so the links are numbered automatically, but the numbers are hidden and only show up on Alt-down not fixed at Fargeau, that just takes a different approach
- escape in edit-mode shouldn't throw up dialog when nothing was changed, or when the changes were undone.
- currently ../inc/site.inc.php is required.. originally for auth on cloud. so that needs fixing.
- make history (key 'h') check that we're a=view[ing] a regular page instead of say, search results, history itself, last-changed, etc.
- does the sqlite table template need fixing with the IP varchar(64) (or really whatever) field? this is fixed somewhere

- alt on chromebook sticks. alt on osx/chrome is funky; alt-s (accidentally) will throw up the search dialog (if there are links on the page), and again for every key after, after dismissing the initial one, until a new page is loaded somehow
- effing chromebook triggers last link on '0' (zero) key. if that's by design, fuck it, leave it in.


**Settings:**

Things that need to go in a settings thing. File/table/whatever.

- wiki name
- IP->name map
- shortcuts->pages map
- css? maybe just link/sidebar colors.. or use the unversioned css files thing that we were planning
- toggles for custom'y features so's people can switch stuff they don't use Off.


**Functionality / features:**

- NB: some things here (also some not yet here) should become plugins rather than core functionality
- friendly urls for nicer bookmarks, http://domain/path/$TITLE or something. maybe even a nicely cased version that will be uppercased by the script. router. thing.
- make it auto-include js/css assets from 1 or 2 gitignore'd directories
- make it so you can add pages without having to come from a link ('n' for new?)
- Ajax:
  - make a Save action that doesn't leave edit mode, somehow. Ctrl-enter? Maybe even handle_ajax()?
  - search: if zero results, stay on the same page, throw up alert (or new prompt) or something
  - search: jump to a page by typing/autocompleting the title (filtering out sensitive/non-public pages); means replacing prompt by something modal'y
- make <tab> jump to the first link in the content, not the sidebar
- alt showing numbers can show same number for same link if it appears multiple times
- keyboard shortcut modifier that opens links in a new tab; alt is tricky in ChromeOS.. two-step shortcuts like in GMail?
- new page property: markup that was used, selectlist on edit. Do the technical/refactoring first


**History mode:**

- next/previous links
- diff on content page (background color? on alt?)
- show ip/username as in Last Changes page
- on edit: show warning if page is already being edited elsewhere, + name & ip
- mikron syntax could jump up in an overlay (overlay.css, kinda like print.css?)
- what links here?


**Random:**
- github link somewhere in the sidebar?

**Housekeeping:**

- remove history older than 50 (say) versions.


***Future:***

- 404 page?
- checkboxes that save state via Ajax for todo lists (field 'metadata' or something?)
- hooks, for...
- plugins! which could be single files that, when active, are included 1-3 times with '?js' or '?css' in the url. Pro: single file. Con: messy code. BUT SINGLE FILE! Come to think of it regular custom .js and .css files could go in the same directory and be included normally, in the right spots. Plugin filenames would be foo.php or foo.js.php or foo.js.css.php, depending. Make a nice little sample template.
- show shortcut keys next to links to those pages, WIKI[w] or something.. possibly instead of the alt-numbers
- tabbed pages, somehow? templates? something making it easier to customize layout or manage larger projects on the single page? Some kind of templating tags for common elements like ToC or ..?
- when the parser has settled down, store rendered pages and update (or remove cache, whatever) on save
- √ how does one delete a page? by simply removing the content \o/
  - (so there is no undo there ^, then?)
- analysis page: "broken" links, unlinked pages, most changes, oldest, ..
- also after technical/refactoring: encryption? password thing on access, used as decrypt key. this will mess with the search though.. http://bristolcrypto.blogspot.nl/2013/11/how-to-search-on-encrypted-data-in.html
- fuck it. filemanager (+table), upload and/or link files in edit mode.
