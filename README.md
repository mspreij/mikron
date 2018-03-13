# Mikron wiki

This is a simple, php/sqlite-based wiki, with very little configuration, intended for small, simple wiki usage.


### Install


Mikron by default does some rudimentary auth, which sits in `inc/auth.inc.php`. For a fresh install you have to create this file by copying or renaming it from
`inc/auth.inc.php.sample`, and editing it as you see fit. It is recommended to leave *some* form of authentication in place, unless it's a local-access-only wiki.

By default records are saved to `data/mikron.db`. The server needs write access to this `data` directory.  
You may need to create the directory (I may need to add it to the repo), with eg `mkdir data` and then giving it write access with `chmod go+w data`.  
When visiting the wiki for the first time, it will offer an 'install' link, which should create the database file.  

If this fails, check that your install of PHP includes SQLite3 (enabled by default since PHP 5.3.0), and that the server does have write access to the `data` directory.


### Usage

Mostly this is reading and clicking the links in the sidebar, and the buttons below the editing form.  
Syntax rules are linked from the editing form, or can be found at  
http://yourdomain/wikipath/?a=view&p=MIKRON_SYNTAX&mikron


### Syntax examples / quickstart

*to follow..*


### History

Once upon a time, I was the "lead dev" at a *very* silly startup, and needed a simple, php-based wiki system, to store bunches of random stuff like urls, git howto's, code conventions, credentials and account data etc.  
A quick Google search brought me to https://github.com/badsector/mikron - which, alas, no longer exists. But it served as the base for this version.

I added some keyboard shortcuts, a few parsing rules, search, and there's many more features "planned" (hey, who knows).

Thank you, original author, for doing the hard work, and letting me build on it.
<br>
<br>

MSpreij (<mspreij@gmail.com>), 2018-03-13
