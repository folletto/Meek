<?php
/*
 * Meek
 * last update: 2011-04-10
 * 
 * ==========================================================================================
 * 
 * BSD License:
 * 
 * Copyright (c) 2011 by Davide S. Casali <folletto AT gmail DOT com>
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are 
 * permitted provided that the following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of 
 *   conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of 
 *   conditions and the following disclaimer in the documentation and/or other materials 
 *   provided with the distribution.
 * - Neither the name of "Meek" nor the names of its contributors may be used to 
 *   endorse or promote products derived from this software without specific prior written 
 *   permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES 
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT 
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE 
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * 
 * ==========================================================================================
 * 
 * USAGE:
 * Place the Meek folder in the root and add an 'index.php' file with it with one PHP line:
 *   <?php require 'Meek/Meek.php'; ?>
 * 
 * A typical Meek app should have a folder structure like:
 *   index.php
 *   Meek/
 *     Meek.php
 *   pages/
 *     404.php
 *     index.php
 *     some.sub.page.php
 *   templates/
 *     tpl.php
 *     tpl.some.sub.php
 * 
 * Where:
 * 'index.php'  contains just the include line <?php require 'Meek/Meek.php'; ?>.
 * 'pages/'     files are the app pages, and have names that are going to match URLs:
 *              'http://example.org/some/sub/page' will match 'pages/some.sub.page.php'
 *              The only two exceptions are 'index.php' (the root) and '404.php' (file not found).
 * 'templates/' contains the template, where only 'tpl.*.php' files are going to be read.
 *              Templates' filenames will match, in a hierarchy, 'pages/' filenames:
 *              'pages/some.sub.page.php' will load 'templates/tpl.some.sub.page.php'.
 *              If the above is missing, the fallback template 'templates/tpl.some.sub.php' will be loaded.
 *              If the above is missing too, then 'templates/tpl.some.php' will be loaded.
 *              If the above is missing again, then 'templates/tpl.php' will be loaded.
 *              'templates/tpl.php' is the top level template.
 *              Standard PHP includes are allowed and you can add to the folder files
 *              that doesn't begin with 'tpl.' to support them.
 */


class Meek {
  
  var $CFG = "cfg.json";
  var $PAGES = "pages";
  var $TEMPLATES = "templates";
  
  var $web_root = "";
  var $local_root = ""; // Script root, ie: "./"
  var $virtual_uri = null; // Virtual URI, ie: "http://example.org/apppath/virtual/path?moo=42" => array("virtual", "path")
  
  var $template = null; // Active template path, ie: "../templates/tpl.sub.php"
  var $page = null;
  var $partials = array();
  
  var $cfg = array();
  var $db = array();
  
  
  function Meek($root = "./") {
    /****************************************************************************************************
     * Initialize
     */
    // ****** Normalize
    $this->PAGES = rtrim($this->PAGES, "/") . "/";
    $this->TEMPLATES = rtrim($this->TEMPLATES, "/") . "/";
    
    $this->local_root = rtrim($root, "/") . "/";
    $this->web_root = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
    
    // ****** Read configuration file
    if (file_exists($this->local_root . $this->CFG)) {
      $this->cfg = json_decode(file_get_contents($this->local_root . $this->CFG), true);
    }
    
    // ****** Initialize Virtual URI
    $this->virtual_uri = $this->virtual_uri();
    
    // ****** Select
    $this->template = $this->select_template($this->local_root . $this->TEMPLATES, $this->virtual_uri);
    $this->page = $this->select_page($this->local_root . $this->PAGES, $this->virtual_uri);
    
    // ****** Render
    $this->page();
  }
  
  // INITIALIZATION SEQUENCE
  function virtual_uri() {
	  /****************************************************************************************************
  	 * Parse the URI to get the virtual path.
  	 * Finds the real part of the path and splits the 'control' name from the other parameters.
  	 * The query string is as always accessible via $_GET.
  	 * 
  	 * @return	parsed virtual URI array
  	 */
  	$out = '';
  	
		// ****** Prepare the variables to be matched
		$self = str_replace(" ", "%20", rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/');	// relative self path
		$uri = $_SERVER['REQUEST_URI'];						// user requested URI
		
		// *** Split path from query
		$relevant = str_replace(':' . $self, '', ':' . $uri); // get just the 'fake' part (remove $self from $uri)
		@list($path, $query) = explode('?', $relevant);	// split 'fake' part from query
		
		// ****** Prepare the array
		if (strlen($path) > 0) {
		  $out = explode('/', $path);
	  } else {
	    $out = array('index');
	  }
		
		return $out;
	}
	function select_template($templates, $virtual_uri) {
	  /****************************************************************************************************
  	 * Select the active template file from implicit file hierarchy.
  	 * Hierarchy:
  	 *    tpl.php
  	 *    tpl.sub.php
  	 *    tpl.sub.sub.php
  	 * where all the "sub" parts are from the Virtual URI items
  	 *
  	 * @param	  templates folder
  	 * @return  choosen template path
  	 */
  	$out = '';
  	
  	if (is_dir($templates)) {
  	  if (is_array($virtual_uri) && sizeof($virtual_uri) > 0) {
  			// ****** Read template hierarchy
  			for ($i = sizeof($virtual_uri); $i > 0; $i--) {
  				$name = join('.', array_slice($virtual_uri, 0, $i));
  				$path = $templates . 'tpl.' . $name . '.php';

  				if (file_exists($path)) {
  				  // ****** Got it!
  				  $out = $path;
  				  break;
  			  }
  		  }
  		}
  		if (!$out) {
  		  // ****** If no Virtual URI was set or no matching template was found
  		  $out = $templates . 'tpl.php';
  		}
  	}
		
		return $out;
	}
	function select_page($pages, $virtual_uri) {
	  /****************************************************************************************************
  	 * Select the active page file from implicit file hierarchy.
  	 * Hierarchy:
  	 *    index.php
  	 *    sub.php
  	 *    sub.sub.php
  	 * where all the "sub" parts are from the Virtual URI items.
  	 * In case of failure, it defaults to 404.
  	 *
  	 * @param	  pages folder
  	 * @return  choosen page path
  	 */
  	$out = '';
  	
  	if (is_dir($pages)) {
  	  if (is_array($virtual_uri) && sizeof($virtual_uri) > 0) {
    		// ****** Read pages hierarchy
    		for ($i = sizeof($virtual_uri); $i > 0; $i--) {
    			$name = join('.', array_slice($virtual_uri, 0, $i));
    			$path = $pages . $name . '.php';

    			if (file_exists($path)) {
    			  // ****** Got it!
    			  $out = $path;
    			  break;
    		  }
    	  }
    	  
    	  if (!$out) {
    	    // ****** The matching page wasn't found
    	    $out = $pages . '404.php';
    	  }
  		} else {
  		  // ****** Open default
  		  $out = $pages . 'index.php';
  		}
  	}
		
		return $out;
	}
	function page() {
	  /****************************************************************************************************
  	 * Renders the current page
  	 * 
  	 * @return	output of the function
  	 */
  	// *** Getting the string in order to post-process its html
  	$page = $this->getOutputOf(array($this, "renderer")); // $this->renderer();
    
    // *** Process
    $page = $this->filter_template($page);
    
    // *** Output
    echo $page;
	}
	
	// RENDER
	function renderer($str = '') {
	  /****************************************************************************************************
  	 * Rendering function.
  	 * If mode and item are empty, then it renders the current page with template.
  	 * 
  	 * NOTE: this function is tricky because we need to perfom output buffering and setting common 
  	 *       variables once. Don't try to split it unless you understand this.
  	 * 
  	 * @param		mode
  	 * @param   partial string to be parsed and rendered || path to the template
  	 */
  	// ****** Environment initialization
	  $root = $this->web_root;
  	extract($this->cfg);
  	
  	// ****** Output block
	  if ($str) {
	    // ******************************************************************************************
  	  // (2) STRING RENDERING
  	  
  	  extract($this->partials); // make previous partials available to following ones
  	  
  	  eval(' ?' . '>' . $str); // reopening the php tag seems not required...
  	  
	  } else {
	    // ******************************************************************************************
	    // (1) TEMPLATE RENDERING
	    
	    $this->partials = $this->read_partials(file_get_contents($this->page), "body");
    	foreach ($this->partials as $key => $partial) {
    	  // Using variable-from-string $$key in order to make the item directly accessible from the page
    	  $$key = $this->getOutputOf(array($this, "renderer"), $partial); // $this->renderer($partial);
    	  $this->partials[$key] = $$key;
    	}
      
  	  include $this->template;
  	  
    }
	}
	function read_partials($string, $read_header = null) {
	  /****************************************************************************************************
  	 * Read the string and convert its content into a partials template array
  	 *
  	 * @param	text string
  	 * @return	array-ized partials ('partial' => 'template block')
  	 */
		$out = array();
		
		$partial_name = $read_header;
		$partial_content = '';
		
		$lines = preg_split('/\n/', $string);
		
		foreach ($lines as $line) {
			if (preg_match('/^:(\w+)\s*.*$/i', $line, $matches) > 0) {
				// ****** Partial
				// *** Close old partial
				if ($partial_name !== null) {
					$out[$partial_name] = $partial_content;
					$partial_content = "";
				}
				
				// *** Start new partial
				$partial_name = $matches[1];
			} else {
				// ****** Fill content
				if ($partial_name !== null && $line !== '')
					$partial_content .= $line . "\n";
			}
		}
		
		// *** Close last partial
		if ($partial_name !== null) {
			$out[$partial_name] = $partial_content;
		}
		
		return $out;
	}
	function filter_template($out) {
	  /****************************************************************************************************
  	 * Template filter to allow path addressing working.
  	 * This function converts relative URLs to live app URLs.
  	 * 
  	 * @param	input text
  	 * @return	output relativized text
  	 */
		// ****** Prepare
		$root = $this->web_root;
		$templates_uri = $this->web_root . $this->TEMPLATES;
		
		// ****** Smart variables parsing
		/*$out = preg_replace('/\<\$(\w+)\>/', '<?php echo \$$1; ?>', $out); /**/
		
		// ****** Relativize link, img and scripts
		$out = preg_replace('/<link(.*)href="(?!http)\/?(.*)"/i', '<link$1href="' . $templates_uri . '$2"', $out);
		$out = preg_replace('/<img(.*)src="(?!http)\/?(.*)"/i', '<img$1src="' . $root . '$2"', $out);
		$out = preg_replace('/<script(.*)src="(?!http)\/?(.*)"/i', '<script$1src="' . $root . '$2"', $out);
		
		// ****** Relativize pages navigation
		$out = preg_replace('/<a(.*)href="(?!http:|https:|mailto:|tel:|skype:)\/?(.*)"/i', '<a$1href="' . $root . '$2"', $out);
		//$out = preg_replace('/<form(.*)action="((?!http).*)"/i', '<form$1action="' . $path . '$2"', $out);
		
		return $out;
	}
	
	// SUPPORT
	function getOutputOf($fx) {
	  /****************************************************************************************************
  	 * Returns any output from the called function.
  	 * 
  	 * @param		function to have the output redirected to a file
  	 * @return	output of the function
  	 */
		$out = '';
		
		if (ob_start()) { // BEGIN nested buffer		
  		$args = func_get_args();
  		array_shift($args); // remove the function name to be called from the array
		  
  		call_user_func_array($fx, $args);
		  
  		$out = ob_get_contents();
  		
  		ob_end_clean(); // END nested buffer
	  }
		
		return $out;
	}
	function _dbg($text, $return = false) {
	  /****************************************************************************************************
  	 * Debug Box
  	 *
  	 * @param	text to be displayed
  	 */
		$out = print_r($text, true);
		
		$out = str_replace("\n", '<br/>', $out);
		$out = str_replace(' ', '&nbsp;', $out);
		
		$out = '<div class="GooDebugBox" style="
			font-family: Courier New;
			font-size: 12px;
			margin: 2em;
			padding: 1em;
			background: #fcfafa;
			border: 2px dotted #ffe0e0;
			">' . $out . '</div>';
		
		if (!$return)
			echo $out;
		
		return $out;
	}
}

// Emet!
$meek = new Meek();

?>