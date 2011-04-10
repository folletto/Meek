<?php
/*
 * Meek
 * last update: 2011-04-03
 * 
 * Copyright (C) 2010 by Davide S. Casali <folletto AT gmail DOT com>
 * Licensed under BSD License.
 * 
 */
?>

:h1
Me Testing Meek!

:body
Some environment variables initialised by Meek:

<ul>
  <li>$root: <?php echo $root; ?> </li>
  <li>$title: <?php echo $title; ?> </li>
  <li>$h1: <?php echo $h1; ?> </li>
</ul>

<ul>
  <li>Rewrite doesn't affect HTTP(S): urls: <a href="http://github.com/Folletto/Meek">test</a></li>
  <li>Rewrite doesn't affect MAILTO: urls: <a href="mailto:test@example.org">test</a></li>
</ul>