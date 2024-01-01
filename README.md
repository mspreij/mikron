# Mikron wiki

This is a simple, php/sqlite-based wiki, with very little configuration, intended for small, simple wiki usage.  
It is very much ~~under development~~ not done yet, has a 50+ item todo list, and requires some dependency stuffs, still.


### Requirements
- PHP 8.\*, probably, because that's what I'm now hacking with.  
- SQLite 3
I have it running under Apache, Nginx, Caddy and the PHP CLI server (had to enable some sqlite/pdo dlls in the php.ini file though).


### QUICK Start
AKA "I just need it running for dev stuff and don't care about explanations."
- copy/rename `inc/settings.inc.php` and `inc/auth.inc.php` from their \*.sample files
- create data directory, either chmod it 777 or chown it to what runs the webserver (often www-data)
- run `composer install`
- visit the page in a browser and follow the install link, this should create the database file and tables

### Install

1. There are two files you need to copy/rename and edit as needed: `inc/settings.inc.php.sample` and `inc/auth.inc.php.sample`. `inc/settings.inc.php` contains various defaults like stylesheets and site title that you can customize. `inc/auth.inc.php` contains some rudimentary auth code. It is recommended to leave *some* form of authentication in place, unless it's a local-access-only wiki.
2. By default records are saved to `data/mikron.db`. The server needs write access to this `data` directory. If I haven't yet added it to the repo, create it. You can give the server write access by changing the owner of the directory to the user the server runs as, for example  
`chown www-data data/`  
You can find out what that user is, by running  
`ps aux | egrep '(httpd|apache|nginx)'`  
It should sit in the first column of the output and should not be 'root'.  
If all else fails you can give it write access with `chmod g+w data` or even `chmod go+w data`. I hope someone figures out the best way and lets me know >.>

3. This is where you run `composer install` in your shell, probably.
4. Now when visiting the wiki for the first time, it will offer an 'install' link, which should create the database file.

If this fails, check that your install of PHP includes SQLite3 (enabled by default since PHP 5.3.0), and that the server does have write access to the `data` directory. And uh, file an issue or something, hopefully it's fixable.


### Usage

Mostly this is reading and clicking the links in the sidebar, and the buttons below the editing form.  
Syntax rules are linked from the editing form, or can be found at  
http://yourdomain/wikipath/?a=view&p=MIKRON_SYNTAX&mikron  
PS. if that looks like a big messy blob, go to edit mode and change the format (little select above the textarea) to "mikron". Sorry about that.

### Syntax examples / quickstart

*to follow..*


### History

Once upon a time, I was the "lead dev" at a *very* silly startup, and needed a simple, php-based wiki system, to store bunches of random stuff like urls, git howto's, code conventions, credentials and account data etc.  
A quick Google search brought me to https://github.com/badsector/mikron - which, alas, no longer exists. But it served as the base for this version.

I added some keyboard shortcuts, a few parsing rules, search, and there's many more features "planned" (hey, who knows).

Thank you, original author, for doing the hard work, and letting me build on it.
<br>
<br>

MSpreij (<mspreij@gmail.com>)
