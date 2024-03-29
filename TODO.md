This is *messy*, I tried to categorize it but there will be outdated, duplicate and overlapping todo items.

Legend: `-` todo; `*` doing; `!` important/priority; `?` maybe do; `w` waiting for/on $something; `x` won't do; `v|V` done'ish.; `√` Done!  
Indented todos are part of the parent todo. Later comments (usually for `x`) start with a `>`. I use CommonMark and Markdown interchangeably.

**Technical (..somewhat; well, it was. maybe.):**

- `*` refactor (yeah, again?): outsource the fetch-page-parse things to a class that splits it up in methods so it's easier to hook things in, and like implement sub-page tags.
- `-` look at https://github.com/victorstanciu/Wikitten/ just for fun
- `-` a few todos here concern settings/prefs in wiki pages vs hardcoded, maybe just add a table "settings" with name=value pairs for whatevers. like for example the IP -> name map, or the shortcuts -> pages.
  - `-` also look at session preferences, possibly for different modifier keys for shortcuts
- `?` can/should the sidebar and the stylesheets be wiki'd as well?
- `-` never mind the shortcut keys.. (additional prefs table? same db)
- `*` better (?) idea: make an array/object in JS that's more easily editable that handles the thing. This is temporary.
- `?` also show the shortcut key (if any) next to the title for the current page - and possibly in the links, too.
- `*` stop editing these effing todos and start doing them


**Bugs/nice-to-fix:**

- `v-ish` this alt-key thing...
  - `v` make it so the links are numbered automatically, but the numbers are hidden and only show up on `Alt`-down.
  - `V` while you're at it, ~~make it configurable (on Windows `Alt` will focus the menu), or at least define a var for which key to use which can be re-set somehow, down the road.~~ `>` it now checks the OS it runs on and uses `ctrl/alt` accordingly.
  - `V` ~~currently the thing that adds the numbers (see parent) to `[[FOOBAR]]`-style links doesn't work for CommonMark-style links like `<https://example.com>`. *That* can be fixed by moving the whole thing to front-end again..~~
    - `v` moved most of the logic to the frontend but did mess up some of the css... numberLinks fontsize is now a bit bigger...  
  - `v-ish` ~~and yes, all that should remove the "Undefined variable: linkCounterHTML" warning..~~ didn't test this but disabling the variable should have fixed this!
- `V` clicking non-wiki links opens a new window, `[alt|ctrl]-<num>` doesn't because JS. It can be re-written to open a new window, but that's a security thing that you need to grant as end-user, and the browser can remember. Put that in the readme or something, when it's added. And just for kicks, see if JS can check whether or not it worked, and show a message explaining if it didn't.
- escape in edit-mode shouldn't throw up dialog when nothing was changed, or when the changes were undone.
- `x` currently ../inc/site.inc.php is required.. originally for auth on cloud. so that needs fixing.  
  `>` renamed to `app.inc.php` and moved to our own `inc` dir.
- make history (key 'h') check that we're a=view[ing] a regular page instead of say, search results, history itself, last-changed, etc.
- `?` does the sqlite table template need fixing with the IP varchar(64) (or really whatever) field? this is fixed *somewhere*
- `x` effing chromebook triggers last link on '0' (zero) key. if that's by design, fuck it, leave it in. `>` I'm calling this a feature, now.
- `-` currently CommonMark parsing is followed by handling `[[...]]` tags, which works, but messed up bash code examples which also use `[[`. Disabling that also disables the possibility to color some parts in said example :-( As an in-between solution, fix the [[-processor to simply ignore [[ followed by whitespace. Or, possibly, just ignore any invalid [[ things like `[[foo bar]]` where foo is a command that doesn't allow spaces, or simply not a valid command at all - atm it replaces this with 'Unknown wiki command' while often also messing up the rest of the line.


**Global settings:**

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
- make it so you can add pages without having to come from a link ('n' for new? js prompt? Ajax check for page name?) (keep link, JS should be optional)
- allow one to rename a page (the actual name, not the title) (and the history? -> what about renaming to existing page?)
- Ajax:
  - make a Save action that doesn't leave edit mode, somehow. Ctrl-enter? Maybe even handle_ajax()?
  - search: if zero results, stay on the same page, throw up alert (or new prompt) or something
    - initial search just returns a count, if > 0 redirect to search results page which does the actual links-rendering and such
  - search: jump to a page by typing/autocompleting the title (filtering out sensitive/non-public pages); means replacing prompt by something modal'y
- Search:
  - advanced search: title/body, date, $field (plugins could hook into this)
  - show value again if no matches, see also Ajax->Search above
  - show search results that have an exact title match first, then partial title matches, then the rest (by position of key in contents?)
  - maybe next/previous links for results? if that's not in there already? who wrote this thing!
- `-` get a markdown-editor textarea (cmd/ctrl-B for bold etc), that doesn't interfere with the current shortcut keys thing (it shouldn't). If ALL ELSE FAILS one could always hack in a ajax preview type thing. With jQuery. Or yanno, leave it for later.
- keyboard shortcut modifier that opens links in a new tab; alt is tricky in ChromeOS.. two-step shortcuts like in GMail? Shift?
- `v` new page property: markup that was used, selectlist on edit. Do the technical/refactoring first. This is a temp thing, moving to some markdown flavor (commonmark atm).
- link to #id/tag/a-name on page, somehow allow adding those easily too (MarkDown have anything? post-process again?)
  - `w` possibly even link to return **only** that part of the page, for uh.. reasons... something friendly urls or another get-param. move this to future when the parent is finished.
- simply hilight all links, like with background. maybe this'd be a plugin.
- allow one to include *another* page in the current page with a special tag; it would be subtly bordered or something, and there'd be links to the original/edit mode. This requires the main parser code to have been rewritten properly to class/methods. And should probably be a toggle in prefs to dis/allow it? Make sure it won't nest o.o or simply only include up to n levels deep. OR BOTH. Plugin?
- on edit: show warning if page is already being edited elsewhere, + name & ip
- `-` **page settings** (a tab or something in the edit mode)
  - `-` kb shortcut to go to this page
  - `-` if page settings, also a default custom content for any pages created from this page (nice for linking back). or a 'link back' button, which would paste in that content. which could be configurable in *main* settings.
  - `-` custom js/css includes per page?
  - `-` hook for plugins, of course
  - `?` would it be madness to enable/disable plugins on per-page basis?
- "what links here?"


**History mode:**

- detail view
  - next/previous links (including if possible datetime, user, update comment < requires new column)
  - show date for current shown more prominent
  - show IP/username, datetime modified, stuff. syntax mode? size?
  - diff-view of content (background color? on alt?) <https://github.com/paulgb/simplediff>
    - alternatively, show a collapsible patch at the top of the page?
  - to allow searching in diff (like in git log -p) maybe keep the diff with the previous version around, in the current page record
- list view
  - show date, ip/username as in Last Changes page
  - show size of content in bytes


**Pretty / UI**

- submit etc buttons change display on focus
- make `<tab>` jump to the first link in the content, not the sidebar
- alt showing numbers can show same number for same link if it appears multiple times
- mikron/markdown syntax could jump up in an overlay (overlay.css, kinda like print.css?)


**Random:**

- save counter column
- github link somewhere in the sidebar?
- https://tiddlywiki.com/ steal ideas \o/
- the hooks thing: they'll be all over the place, sort them out into a tree-like structure maybe, also easier to document
- backup db functionality (plugin?), since wiki has write perms already in data/
- default dark/light themes with easier tweaking; kinda like SublimeText prefs maybe? user-settings overrule hardcoded defaults
- fix favicon(s?)

**Housekeeping:**

- remove history older than 50 (say) versions. Should be config option.
  - or: treat older saves as backup snapshots for pruning: keep oldest, then every nth version until more recent, then every version. or like yearly/monthly/every.
    if diffs are also saved (for searching in them), re-create these for the relevant records
- allow removing history for a certain page or deleting certain history items ;>.>


***Future:***

- 404 page?
- API (needs looking at auth thing) so you can use different clients, and/or edit from CLI.
  - this might well impact plugins too o.o allow plugins to add/edit API calls?
- tags or labels on pages? requires more tables..
- checkboxes that save state via Ajax for todo lists (field 'metadata' or something?)
  - or eeeven, checkboxes with multiple states for deluxe todo lists (todo, doing, done, waiting/low-prio, won't-do, prio, unclear).  
    This probably needs a tag-type thing that 1. knows what list-options it has 2. can save/"hold" those 3. is not overly long to type.
- hooks, for...
- plugins! which could be single files that, when active, are included 1-3 times with '?js' or '?css' in the url. Pro: single file. Con: messy code. BUT SINGLE FILE! Come to think of it regular custom .js and .css files could go in the same directory and be included normally, in the right spots. Plugin filenames would be foo.php or foo.js.php or foo.js.css.php, depending. Make a nice little sample template. More complicated plugins would have their own directory and could sit in the same plugins dir.
- show shortcut keys next to links to those pages, WIKI[w] or something.. possibly instead of the alt-numbers
- prevent people overwriting eachother's work while editing at the same time - send an md5 of the current content along, if it has changed upon save, someone else also edited and saved. do smart things somehow? or lock records while editing? try keep it non-easily-breaking o.o
- is it possible to edit part of a page? this would be nice for really long pages where you spot a typo /somewhere in the middle/..
- tabbed pages, somehow? templates? something making it easier to customize layout or manage larger projects on the single page? Some kind of templating tags for common elements like ToC or ..?
- when the parser has settled down, store rendered pages and update (or remove cache, whatever) on save
- `√` how does one delete a page? by simply removing the content \o/
  - (so there is no undo there ^, then?)
- analysis page: "broken" links, unlinked pages, most changes, oldest, ..
- also after technical/refactoring: encryption? password thing on access, used as decrypt key. this will mess with the search though.. http://bristolcrypto.blogspot.nl/2013/11/how-to-search-on-encrypted-data-in.html
- fuck it. filemanager (+table), upload and/or link files in edit mode. Uploading a file in edit mode could create an entry for it in the files table, and link it to the page in some link table. So one file could be attached to 0, 1 or many pages. Same for images (and sounds, yadda).
  - for an implicit file link, a tag could be defined (plugin?) that links the file with a little inline "preview" thing (icon/type/size).
    - bonus points: lightbox type thing for images (plugin)
- subscribe to page change notifications: depends on user accounts
- below tags: how in God's name do we keep stuff from overwriting the other's output? maybe the result of markdown needs to be re-parsed into an AST of sorts.. >.>
  - tag: [[noParse]] text that should not be touched (could be html, could be .. random stuff) [[/noParse]]
  - tag: [[parser:xizzy]] text that will be parsed not with the main page's format, but with xizzy [[/parser]]
  - tag: [[expr:someCallback]] data, stuff [[/expr]]
- if plugins, then some interface for them to add preference tabs to settings page. thing. which will exist at some point.
- offline version/synching.


**Plugin ideas**
- Syntax blocks: [[code:js]] ... [[/code]] or something like that to syntax-hilight the contents. https://highlightjs.org/ or something similar?
- Menu: make it so you can define an actual menu with submenu items in the lefthand menubar. Could be JSON/YAML type thing, simple 2-dim array with page names. Highlight current. Probably only show when the current page is one of its items.
