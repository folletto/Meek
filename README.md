Meek 0.2
========

**Humble template engine for small websites**

  <http://intenseminimalism.com>  
  _Copyright (C) 2011, Erin Casali_  
  _Licensed under BSD Opensource License._  



WHAT IS MEEK
------------

It's a humble & lightweight PHP engine to build small websites.
Meek maps directly URL to files and templates with a neat fallback system.




HOW TO USE MEEK
------------

1. Download or git clone git@github.com:folletto/Meek.git
2. Edit .htaccess
3. Write your templates inside templates/ folder.
4. Write your content in pages/ folder.
5. (optional) Write any constant variable in cfg.json.




MEEK URL MAPPING
----------------

If we imagine Meek installed at http://example.org/app/, in its simplest form the
URL mapping does this:

* URL: http://example.org/app/path/to/file
* Loads: pages/path.to.file.php
* With the template from: templates/tpl.path.to.file.php

Simple right?

It does also a couple other tricks:

1. PAGES AUTO-FALLBACK  
If pages/path.to.file.php is missing, it will try to load, in order:  
pages/path.to.php  
pages/path.php  
pages/404.php  
In this way you can build a kind of controller that reads the next URL token from 
the pre-parsed array at: $this->virtual_uri and load the named item.

2. TEMPLATES AUTO-FALLBACK  
If templates/tpl.path.to.file.php is missing, it will try to load (like pages), in order:  
templates/tpl.path.to.php  
templates/tpl.path.php  
templates/tpl.php  
In this way you can control very well what's going to appear on each page.
You can even create a templates/tpl.api.php template that returns JSON or XML and build
a simple API.





BUGS AND FEEDBACK
-----------------

Submit your bugs here: http://github.com/Folletto/Meek/issues  
Follow me on Twitter: http://twitter.com/Folletto



CHANGELOG
---------

* 0.2
  * Markdown support for pages

* 0.1
  * First prototype

