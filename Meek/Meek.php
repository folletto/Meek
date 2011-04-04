<?php
/*
 * Meek
 * last update: 2011-04-03
 * 
 * Copyright (C) 2010 by Davide S. Casali <folletto AT gmail DOT com>
 * Licensed under BSD License.
 * 
 */


class Meek {
  
  var $CFG = "cfg.json";
  var $PAGES = "pages";
  var $TEMPLATES = "templates";
  
  var $self_root = ""; // Script root, ie: "./"
  var $virtual_uri = null; // Virtual URI, ie: "http://example.org/apppath/virtual/path?moo=42" => array("virtual", "path")
  
  var $template = null; // Active template path, ie: "../templates/tpl.sub.php"
  var $page = null; 
  
  var $cfg = array();
  var $db = array();
  
  
  function Meek($root = "./") {
    /****************************************************************************************************
     * Initialize
     */
    $this->self_root = rtrim($root, "/") . "/";
    
    // Read configuration file
    if (file_exists($this->self_root . $this->CFG)) {
      $this->cfg = json_decode(file_get_contents($this->self_root . $this->CFG), true);
    }
    
    // Initialize Virtual URI
    $this->virtual_uri();
    
    // Select
    $this->select_template($this->self_root . $this->TEMPLATES . "/");
    $this->select_page($this->self_root . $this->PAGES. "/");
    
    // Render
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
		if (!is_array($this->virtual_uri)) {
			// ****** Prepare the variables to be matched
			$self = str_replace(" ", "%20", rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/');	// relative self path
			$uri = $_SERVER['REQUEST_URI'];						// user requested URI
			
			// *** Split path from query
			$relevant = str_replace(':' . $self, '', ':' . $uri);		// get just the 'fake' part (remove $self from $uri)
			@list($path, $query) = explode('?', $relevant);	// split 'fake' part from query
			
			// ****** Prepare the array
			if (strlen($path) > 0) {
			  $this->virtual_uri = explode('/', $path);
		  } else {
		    $this->virtual_uri = array('index');
		  }
		}
		
		return $this->virtual_uri;
	}
	function select_template($templates) {
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
  	if (is_dir($templates)) {
  	  if (is_array($this->virtual_uri) && sizeof($this->virtual_uri) > 0) {
  			// ****** Read template hierarchy
  			for ($i = sizeof($this->virtual_uri); $i > 0; $i--) {
  				$name = join('.', array_slice($this->virtual_uri, 0, $i));
  				$path = $templates . 'tpl.' . $name . '.php';

  				if (file_exists($path)) {
  				  // ****** Got it!
  				  $this->template = $path;
  			  }
  		  }
  		}
  		if (!$this->template) {
  		  // ****** If no Virtual URI was set or no matching template was found
  		  $this->template = $templates . 'tpl.php';
  		}
  	}
		
		return $this->template;
	}
	function select_page($pages) {
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
  	if (is_dir($pages)) {
  	  if (is_array($this->virtual_uri) && sizeof($this->virtual_uri) > 0) {
    		// ****** Read pages hierarchy
    		for ($i = sizeof($this->virtual_uri); $i > 0; $i--) {
    			$name = join('.', array_slice($this->virtual_uri, 0, $i));
    			$path = $pages . $name . '.php';

    			if (file_exists($path)) {
    			  // ****** Got it!
    			  $this->page = $path;
    			  break;
    		  }
    	  }
    	  
    	  if (!$this->page) {
    	    // ****** The matching page wasn't found
    	    $this->page = $pages . '404.php';
    	  }
  		} else {
  		  // ****** Open default
  		  $this->page = $pages . 'index.php';
  		}
  	}
		
		return $this->page;
	}
	
	// RENDER
	function page() {
	  /****************************************************************************************************
  	 * Renders the current page
  	 * 
  	 * @return	output of the function
  	 */
  	extract($this->cfg);
  	
  	$partials = $this->read_partials(file_get_contents($this->page), "body");
  	extract($partials);
  	// eval() partials?
  	
  	// get template, explode simpletags <?tag> after php inclusion run (WHAT??? ARE YOU KIDDING???!?)
  	include $this->template;
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
	function render() {
	  /****************************************************************************************************
  	 * Partials Array Renderer
  	 * Renderer for the render() loop.
  	 * 
  	 * @param	partial name
  	 * @param	item array
  	 */
		// *** Preparing some variables in order to be usable easily in the page
		$root = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
		// *** Of course, also the user array must be extract()ed
		extract($item);
		
		// *** Evaluates the string
		$code = $this->partials[$partial];
		$code = $this->context->filter('template', $code);
		
		// partials are HTML mainly, so we close the php tags before evaluating.
		eval(' ?' . '>' . $code . '<' . '?php ');
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
		
		ob_flush();
		
		$args = func_get_args();
		array_shift($args);
		
		call_user_func_array($fx, $args);
		
		$out = ob_get_contents();
		ob_clean();
		
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