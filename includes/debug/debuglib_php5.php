<?php
#############################################################
## Name          : debuglib for PHP5
## Author        : Thomas Schler <code at atomar dot de>
## Version       : beta
## Last changed  : 15.09.2008 11:28:38
## Revision      : 14
############################################################

/*
 * Copyright (C) 2004-2008 by Thomas Schler
 * Written by Thomas Schler <debuglib@atomar.de>
 * All Rights Reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
*/


if (!defined('USE_DEBUGLIB')) define('USE_DEBUGLIB', TRUE);

if (USE_DEBUGLIB):
	
	$GLOBALS['DbugL_Globals'] = array();
	$GLOBALS['DbugL_Globals']['microtime_start'] = microtime(TRUE);
	$GLOBALS['DbugL_Globals']['initial_globals_count'] = count($GLOBALS);
		
	class DbugL {

		public static $alt_parameter_names = array(
			'trim_tabs' => array('trim', 't'),
			'label'     => array('l'),
			'max_y'     => array('y'),
		);
		
		public static function help($return_mode = FALSE) {
			$html = '
				<style type="text/css">
					div.DbugL_help         {
						font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; font-size:8pt; border:1px dashed black;
						width:800px;
						padding:5px;
					}
					
					div.DbugL_help h5 {
						display:inline;
						font-size:10pt;
						border-bottom:1px dotted black;
					}
					
					div.DbugL_help th, div.DbugL_help td {
						vertical-align:top;
						text-align:left;
					}
				</style>
			
				<div class="DbugL_help">
					<h5>print_a(mixed input[, string option_string])</h5><br />
					<br />
					option_string must be in the css like syntax:<br />
					<br />
					eg. "max_y:5;window:1;label:my_array"<br />
					<br />
					possible options:<br />

					<table>
						<tr><th>return:</th>                    <td>(0|1)</td>      <td>do not print the output and instead return it as a string</td></tr>
						<tr><th>help:</th>                      <td>(1)</td>        <td> show this text</td></tr>
						<tr><th>label:</th>                     <td>(string)</td>   <td> draw a fieldset/legend around the output</td></tr>
						<tr><th>max_y:</th>                     <td>(1-n)</td>      <td>maximum number of items on the same level. [...]</td></tr>
						<tr><th>pickle:</th>                    <td>(0|1)</td>      <td>print a serialized representation of the array instead of printing it as a table</td></tr>
						<tr><th>trim_tabs:</th>                 <td>(0-n)</td>      <td>trim the leading tabs in multiline strings and pad with n tabs</td></tr>
						<tr><th>window:</th>      							<td>(string)</td>   <td>the output should open in a new window (javascript), the parameter is also the title for the window</td></tr>
						<tr><th>avoid@:</th>                    <td>(0|1)</td>      <td>if a key starts with the character "@", assume it is a recursive reference and don\'t follow it</td></tr>
					</table>
					<br />
					
					<h5>show_vars([string option_string])</h5><br />
					<br />
					prints all superglobals like $_GET, $_POST, $_SESSION etc. in a big table<br />
					good for printing at the bottom of a page<br />
					<br />
					options are the same as for print_a<br />
					<br />
					+ the following options:<br />
					verbose: also show $_SERVER and $_ENV<br />
					<br />
					<!--
					<h5>print_mysql_result(resource mysql_result[, bool return_mode])</h5><br />
					<br />
					prints a mysql query result as a table<br />
					<br />
					-->

					<h5>script_runtime([bool return_mode])</h5><br />
					<br />
					prints the passed time since the start of the script (or the last script_runtime call) in seconds<br />
					<br />
					
					<h5>pre(string string[, string option_string])</h5><br />
					<br />
					print a string so the whitespaces are visible in html<br />
					<br />

					option_string must be in the css like syntax:<br />
					<br />
					eg. "r:1;trim_tabs:0;"<br />
					<br />
					possible options:<br />

					<table>
						<tr><th>fancy:</th>     <td>(0|1)</td> <td>use fancy formatting, show tabs etc.</td></tr>
						<tr><th>r:</th>         <td>(0|1)</td> <td>return the output instead of printing it</td></tr>
						<tr><th>trim_tabs:</th> <td>(0-n)</td> <td>tab_padding: remove all leading tabs without removing the indentions, <br />then pad the block with n tabs (can be 0)</td></tr>
					</table>
					<br />
					
					You can disable the output of all the functions in a production enviroment using:<br />
					<strong>&lt;?php define(\'USE_DEBUGLIB\', FALSE); ?&gt;</strong>
				</div>
			';
			
			if($return_mode) {
				return $html;
			} else {
				print $html;
			}
			
		}

		const runtime_precision  = 6;

		public $default_options = array(
			'label'               => NULL,
			'window'              => NULL,
			'max_y'               => 50,
			'test_for_recursions' => FALSE,
			'show_objects'        => TRUE,
			'trim_tabs'           => NULL,
			'avoid@'              => FALSE,
			'return'              => FALSE,
			'pickle'              => FALSE,
		);
		
		public $options = array();
		
		public $element_counter = 0;
		public $window_html;
		public $color_cache  = array();
		public $open_windows = array();
		
		
		const object_key_marker = '<:~!OBJECT_KEY!~:>';
		const color_step_width = 15;
		
		const key_bg_color_default     = '00128F';
		const key_bg_color_array       = '00128F';
		const key_bg_color_object      = '60008F';
		const key_bg_color_object_data = '60008F';
		
		public $special_keys = array();

		const css = '
			<style type="text/css" media="screen">

				*.DbugL                { font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; }
				
				pre.DbugL              { display:inline; background:#F1F1F1; font-size:8pt; }
				div.DbugL              { margin-bottom:5px; }
				
				div.DbugL_pre         { font-size:8pt; font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; margin-bottom:10px; }
				
				span.DbugL_multi       { background:#F3F4FE; }
				span.DbugL_outer_space { color:red; }
				span.DbugL_tabs        { color:#CCCCCC; }
				
				fieldset.DbugL_normal  { width:10px; border:1px solid black; padding:2px; }
				fieldset.DbugL_pickled { width:100%; border:1px solid black; padding:2px; }
				legend.DbugL           { font-size:9pt; font-weight:bold; color:black; }
				div.DbugL_runtime      { font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; font-size:9pt; font-weight:normal; color:black; background:yellow; }
				span.DbugL_Type_other  { font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; font-size:8pt; background:#ECEDFE; color:red;}
				span.DbugL_Value_other { font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; font-size:8pt; white-space:nowrap; color:black;}
                                                
				table.DbugL                       { font-size:8pt;}
				table.DbugL th                    { background:#1E32C8; color:white; text-align:left; padding-left:2px; padding-right:2px; font-weight:normal; }
				table.DbugL td                    { background:#DEDEEF; }
				                                       
				table.DbugL th.key_string         { color:white; }
				table.DbugL th.key_number         { color:green; }
				table.DbugL th.key_array          { color:white; font-weight:bold;}
				table.DbugL th.key_object         { color:white; font-weight:bold; }
				                                       
				table.DbugL td.value              { padding-left:1px; }
				table.DbugL td.value_bool_true    { color:#5BA800; }
				table.DbugL td.value_bool_false   { color:#D90062; }
				table.DbugL td.value_string       { color:black; }
				table.DbugL td.value_integer      { color:green; }
				table.DbugL td.value_double       { color:blue; }
				table.DbugL td.value_null         { color:darkorange; }
				table.DbugL td.value_empty_array  { color:darkorange; }
				table.DbugL td.value_empty_string { color:darkorange; }
				table.DbugL td.value_skipped      { color:#666666; }
				                                        
				div.DbugL_SG                { color:black; font-weight:bold; font-size:9pt; }
				table.DbugL_SG              { width:100%; font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif;  font-size:8pt; }
				table.DbugL_SG td	          { padding:2px; }
				table.DbugL_SG td.globals   { background:#7ACCC8; }
				table.DbugL_SG td.get       { background:#7DA7D9; }
				table.DbugL_SG td.post      { background:#F49AC1; }
				table.DbugL_SG td.files     { background:#82CA9C; }
				table.DbugL_SG td.session   { background:#FCDB26; }
				table.DbugL_SG td.cookie    { background:#A67C52; }
				table.DbugL_SG td.server    { background:#A186BE; }
				table.DbugL_SG td.env       { background:#7ACCC8; }
				                              
				div.DbugL_js_hr { width:100%; border-bottom:1px dashed black; margin:10px 0px 10px 0px; }
			</style>
			
			<style type="text/css" media="print">
				table.DbugL_Show_vars {
					display:none;
					visibility:invisible;
				}
			</style>
		';
		
		public function __construct() {
			
			$this->window_html = '
				<html>
					<head>
						'.self::css.'
						<script type="text/javascript">
							//<![CDATA[

							function append_html(html) {
								document.getElementById("content").innerHTML = document.getElementById("content").innerHTML + html;
							}

							//]]>
						<\/script>
					</head>
					<body>
						<div id="content"></div>
					</body>
				</html>
			';
		}
		
		public function set_options($options) {
			$this->options = array_merge($this->default_options, self::parse_options($options));
		}
		
		public static function get_type($value) {
			return gettype($value);
		}

		public static function trim_leading_tabs( $string, $tab_padding = NULL ) {
			/* remove whitespace lines at start of the string */
			$string = preg_replace('/^\s*\n/', '', $string);
			/* remove whitespace at end of the string */
			$string = preg_replace('/\s*$/', '', $string);
			
			# kleinste Anzahl von frenden TABS z鄣len
			preg_match_all('/^\t+/', $string, $matches);
			$minTabCount = strlen(@min($matches[0]));
			
			# und entfernen
			$string = preg_replace('/^\t{'.$minTabCount.','.$minTabCount.'}/m', (isset($tab_padding) ? str_repeat("\t", $tab_padding) : ''), $string);

			return $string;
		}
			
		public static function _handle_whitespace( $string ) {
			$string = str_replace(' ', '&nbsp;', $string);
			$string = preg_replace(array('/&nbsp;$/', '/^&nbsp;/'), '<span class="DbugL_outer_space">_</span>', $string); # mark spaces at the start/end of the string with red underscores
			$string = str_replace("\t", '<span class="DbugL_tabs">&nbsp;&nbsp;&raquo;</span>', $string); # replace tabulators with '  ?
			return $string;
		}
		
		// format strings for output to html
		public static function format_string($string, $trim_tabs = NULL) {
			
			$string = htmlspecialchars($string);
			
			$is_multiline = strpos($string, "\n") !== FALSE;
			if($is_multiline && isset($trim_tabs)) {
				$string = self::trim_leading_tabs($string, $trim_tabs);
			}

			$string = self::_handle_whitespace($string);
			$string = nl2br($string);
			if($is_multiline) {
				$string = '<span class="DbugL_multi">'.$string.'</span>';
			}

			return $string;
		}
		
		// parse the options string in css syntax
		public static function parse_options($options_string = NULL) {

			$options = array();
			
			$alt_parameter_mapping = array();
			foreach(self::$alt_parameter_names as $parameter_name => $alt_names) {
				$alt_parameter_mapping[$parameter_name] = $parameter_name;
				foreach($alt_names as $alt_name) {
					$alt_parameter_mapping[$alt_name] = $parameter_name;
				}
			}
			
			if(!$options_string) return $options;

			if(strstr($options_string, ':')) {
				$pairs = explode(';', $options_string);
				
				for($i=0;$i< count($pairs);$i++) {
					$pair = trim($pairs[$i]);
					if($pair == '') continue;
					list($option, $value) = explode(':', $pair);
					
					if(isset($alt_parameter_mapping[$option])) {
						$options[$alt_parameter_mapping[$option]] = $value;
					} else {
						$options[$option] = $value;
					}

				}
			}
			return $options;
		}

		public function _block_s($class = 'null') { return '<table cellpadding="0" cellspacing="1" class="DbugL">'; }
		public function _block_e()                { return '</table>'; }
		
		public function _row_s  ($class = 'null') { return '<tr>'; }
		public function _row_e  ()                { return '</tr>'; }
		
		public function _key_s  ($bg_color, $class, $value) {
			$value_type = self::get_type($value);
			if($value_type == 'array'  && count($value) == 0) $value_type = 'array (empty)';
			if($value_type == 'string' && $value == '')       $value_type = 'string (empty)';
			return '<th '.(isset($class) ? 'class="'.$class.'"' : '').' style="background:#'.$bg_color.'" title="'.$value_type.'">';
		}
		public function _key_e  ()                { return '</th>'; }
		
		public function _value_s($class = 'null') { return '<td class="value '.$class.'">'; }
		public function _value_e()                { return '</td>'; }

		public function _make_key_bg_color($base_color, $iter) {
			
			// lighten up the key background color with each iteration
			if(!isset($this->color_cache[$base_color][$iter])) {
				if( $iter ) {
					for($i=0; $i<6; $i+=2) {
						$component = substr( $base_color, $i, 2 );
						$component = hexdec( $component );
						( $component += self::color_step_width * $iter ) > 255 and $component = 255;
						isset($tmp_key_bg_color) or $tmp_key_bg_color = '';
						$tmp_key_bg_color .= sprintf( "%02X", $component );
					}
					$key_bg_color = $tmp_key_bg_color;
				}
				
				$this->color_cache[$base_color][$iter] = $key_bg_color; // save the color so we dont have to compute it again for this iteration
			}

			return $this->color_cache[$base_color][$iter];
		}
		
		// format the key
		public function _format_key($key, $value, $iter, $special_type = NULL) {
			
			$this->element_counter++;
			
			$key_type   = self::get_type($key);
			$value_type = self::get_type($value);
			
			if(strpos($key, self::object_key_marker) !== FALSE) {
				$key = str_replace(self::object_key_marker, '', $key);
				$value_type = 'OBJECT_DATA';
			}
			
			switch($value_type) {
				
				case 'array':
					return self::_key_s($this->_make_key_bg_color(self::key_bg_color_array,   $iter), 'key_array', $value). $key .self::_key_e();
					break;

				case 'object':
					return self::_key_s($this->_make_key_bg_color(self::key_bg_color_object,  $iter), 'key_object', $value).'<span title="Class: '.(get_class($value)).'">'.$key .'</span>'.self::_key_e();
					break;

				case 'OBJECT_DATA':
					return self::_key_s($this->_make_key_bg_color(self::key_bg_color_object_data,  $iter), 'key_object_data', $value). $key .self::_key_e();
					break;

				default:
				
					return self::_key_s($this->_make_key_bg_color(self::key_bg_color_default, $iter), NULL, $value).self::format_string($key, $this->options['trim_tabs']).self::_key_e();
					break;
			}
				
			return self::_key_s('key_string').$key.self::_key_e();
		}

		public function _format_value($value) {

			$value_type = self::get_type($value);
			
			switch($value_type) {

				case 'boolean':
					if( $value == TRUE ) {
						return self::_value_s('value_bool_true'). 'TRUE' .self::_value_e();
					} else {
						return self::_value_s('value_bool_false'). 'FALSE' .self::_value_e();
					}
					break;
				
				case 'string':
					if($value == '') {
						return self::_value_s('value_empty_string')."''".self::_value_e();
					} else {
						return self::_value_s('value_string'). self::format_string($value, $this->options['trim_tabs']). self::_value_e();
					}
					break;
					
				case 'integer':
					return self::_value_s('value_integer'). $value .self::_value_e();
					break;

				case 'double':
					return self::_value_s('value_double'). $value .self::_value_e();
					break;

				case 'NULL':
					return self::_value_s('value_null'). 'NULL' .self::_value_e();
					break;
					
				case 'array':
					return self::_value_s('value_empty_array'). '[]' .self::_value_e();
					break;

				case 'object':
					return self::_value_s('value_empty_array'). '[]' .self::_value_e();
					break;
			}
		}
		
		public function print_a($input, &$html, $iter = 0) {

			$iter++;
			
			$input_type = self::get_type($input);
			
			// input was neither an array nor an object
			if(! in_array($input_type, array('array', 'object'))) {
				if($input_type == 'resource') {
					$html = '<span nowrap="nowrap" class="DbugL_Value_other"><span class="DbugL_Type_other">('.$input_type.')</span> '.$input.'</span>';	
				} else {
					$html = '<span nowrap="nowrap" class="DbugL_Value_other"><span class="DbugL_Type_other">('.$input_type.')</span> '.self::format_string($input, $this->options['trim_tabs']).'</span>';	
				}
				return;
			}

			$html .= self::_block_s();
			
			$loop_i = 0;
			foreach($input as $key => $value) {
				$html .= self::_row_s();
				
				if($loop_i > $this->options['max_y']) {
					$html .= $this->_format_key('...', $value, $iter);
					$html .= self::_value_s('value_skipped').'<span title="you can change this setting in the debuglib script (max_per_level)">['. (count($input) - $loop_i).'&nbsp;skipped]</span>' .self::_value_e();
					break;
				}
				
				$html .= $this->_format_key($key, $value, $iter);
				
				if($this->options['avoid@'] == '1' && $key[0] == '@') {
					$html .= $this->_format_value('Recursion');
				} elseif(is_array($value) && !empty($value)) {
					
					$html .= self::_value_s();
					$this->print_a($value, $html, $iter);
					$html .= self::_value_e();
					
				} elseif(is_object($value)) {

					$html .= self::_value_s();
					if($this->options['show_objects']) {
						$this->print_a($value, $html, $iter);
					} else {
						$html .= '<span title="not shown due to the option &quot;show_objects:0&quot;">...</span>';
					}
					$html .= self::_value_e();

				} else {
					
					$html .= $this->_format_value($value);
					
				}
				
				$html .= self::_row_e();
	
				$loop_i++;
			}
			
			$html .= self::_block_e();
			
		}
	
		public function js_for_popup($html) {
			
			$title = $this->options['window'];
			$window_name = 'DbugL_'.md5($_SERVER['HTTP_HOST']).'_'.$title;
			
			$print_css = in_array($window_name, $this->open_windows) ? FALSE : TRUE;
			
			$this->open_windows[] = $window_name;
			
			$debugwindow_origin = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
			
			$html = self::escape_js($html);
			
   		return '
				<script type="text/javascript">
					
					//<![CDATA[
					
					if(!'.$window_name.') {

						var '.$window_name.' = window.open("", "W'.$window_name.'", "menubar=no,scrollbars=yes,resizable=yes,width=640,height=480");
						var the_window = '.$window_name.';
						
						var the_document = the_window.document;
						
						with (the_document) {
							open();
							write("'.self::escape_js($this->window_html).'");
							close();
							title = "'.$title.' Debugwindow for : http://'.$debugwindow_origin.'";
						}
						
					} else {
						var the_document = '.$window_name.'.document;
						the_window.append_html("<div class=\"DbugL_js_hr\"><div>");
					}
					
					the_window.append_html("'.$html.'");
					the_window.focus();
					
					//]]>
					
				</script>
	    ';
		
		}
		
		public function escape_js($string) {
			$string = str_replace(array("\r\n", "\n", "\r"), '\n', $string);
			$string = str_replace('"', '\"', $string);
			return $string;
		}
		
		public function get_html($html) {
			if(isset($this->options['label'])) {
				$html = '<fieldset class="'.($this->options['pickle'] == TRUE ? 'DbugL_pickled' : 'DbugL_normal').'"><legend class="DbugL">'.$this->options['label'].'</legend>'.$html.'</fieldset>';
			}
			$html = '<div class="DbugL">'.$html.'</div>';
			return $html;
		}
		
	} // DbugL

	function pre($string, $options_string = NULL) {
		$options = DbugL::parse_options($options_string);
		
		if(isset($options['trim_tabs'])) {
			$string = DbugL::trim_leading_tabs($string, $options['trim_tabs']);
		}
		
		if(!isset($options['fancy']) || isset($options['fancy']) && $options['fancy'] == '0') {
			$string = htmlspecialchars($string);
			$html = '<pre class="DbugL">'.$string.'</pre><br />';
		} else {
			$html = '<div class="DbugL_pre"><span>'.DbugL::format_string($string).'</span></div>';
		}
		
		if(isset($options['return']) && $options['return'] == '1') {
			return $html;
		} else {
			print $html;
		}
	}

	function script_runtime($return_mode = FALSE) {
		$now_time = microtime(TRUE);
		if(isset($GLOBALS['DbugL_Globals']['last_microtime'])) {
			$step_time = $now_time - $GLOBALS['DbugL_Globals']['last_microtime'];
		}
		$GLOBALS['DbugL_Globals']['last_microtime'] = $now_time;
		
		$elapsed_time = sprintf('%01.'.DbugL::runtime_precision.'f', $now_time - $GLOBALS['DbugL_Globals']['microtime_start']);
	
		$html = '<div class="DbugL_runtime">time: '.$elapsed_time.(isset($step_time) ? ' (since last: '.sprintf('%01.'.DbugL::runtime_precision.'f', $step_time).')' : '').'</div>';
		
		if($return_mode) {
			return $html;
		} else {
			print $html;
		}
	}
	
	function script_globals() {
		$varcount = 0;
		$script_globals = array();
	
		foreach($GLOBALS as $variable_name => $variable_value) {
			if(++$varcount > $GLOBALS['DbugL_Globals']['initial_globals_count']) {
				
				/* die wollen wir nicht! */
				if ($variable_name != 'HTTP_SESSION_VARS' && $variable_name != '_SESSION') {
					$script_globals[$variable_name] = $variable_value;
				}
				
			}
		}
		
		unset($GLOBALS['DbugL_Globals']['initial_globals_count']);
		return $script_globals;
	}	

	// the interface function for Debuglib::_print_a()
	function print_a($input, $options_string = NULL) {
		
		static $first_call = TRUE;
		
		static $DbugL;
		
		if(!$DbugL) $DbugL = new DbugL;
		
		$DbugL->set_options($options_string);
		
		$html = '';
		
		// use print_r() to check for a recursion in the structure
		if($DbugL->options['test_for_recursions'] && strpos(print_r($input, 1), '*RECURSION*') !== FALSE) {
			$html = 'RECURSION detected!';
		} else {
			$DbugL->print_a($input, $html); 
		}
		
		// open a window for the output?
		if(isset($DbugL->options['window'])){
			
			if($DbugL->options['pickle'] == TRUE) {
				
				$pickled_input = serialize($input);
				$pickled_input = str_replace("'", "\\\'", $pickled_input );
				$pickled_input = '<textarea style="width:100%;height:200px;">' . $pickled_input .'</textarea>';
				$html = $DbugL->js_for_popup($DbugL->get_html($pickled_input));
				
			} else {
				$html = $DbugL->js_for_popup($DbugL->get_html($html));
			}
		} else {
			$html = ($first_call ? DbugL::css : '').$DbugL->get_html($html);
			$first_call = FALSE;
		}
		
		if(isset($DbugL->options['help'])) {
			$html = DbugL::help(@$DbugL->options['return']);
		}
		
		if(@$DbugL->options['return'] == '1') {
			return $html;
		} else {
			print $html;
		}
		
	}
	
	// call print_a() and commit suicide
	function die_a($input, $options_string = NULL) {
		print_a($input, $options_string);
		die;
	}

	function show_vars($options_string = NULL) {

		$options = DbugL::parse_options($options_string);
		
		$print_a_options = $options_string.';return:1;';
		
		$superglobals = array(
			'Script $GLOBALS' => script_globals(),
			'$_GET'           => $_GET,
			'$_POST'          => $_POST,
			'$_FILES'         => $_FILES,
			'$_SESSION'       => $_SESSION,
			'$_COOKIE'        => $_COOKIE,
		);
			
		
		if(isset($options['verbose']) && $options['verbose'] == '1') {
			$superglobals['$_SERVER'] = $_SERVER;
			$superglobals['$_ENV']    = $_ENV;
		}
		
		$html = script_runtime(TRUE);
		$html .= '<table class="DbugL_SG" cellpadding="0" cellspacing="0">';

		foreach($superglobals as $name => $reference) {
			if(empty($reference)) continue;
			$class_name = $name == 'Script $GLOBALS' ? 'globals' : strtolower(str_replace('$_', '', $name));
			$html .= '<tr><td class="'.$class_name.'"><div class="DbugL_SG">'.$name.'</div>';
			$html .= print_a($reference, $print_a_options);
			$html .= '</td></tr>';
		}
		
		$html .= '</table>';
		
		if(@$options['return'] == '1') {
			return $html;
		} else {
			print $html;
		}
			
	}


	// prints out a mysql result.. work in progress..
	function print_mysql_result($mysql_result, $return_mode = FALSE) {
		
		if(!$mysql_result || mysql_num_rows($mysql_result) < 1) return;

		$field_count = mysql_num_fields($mysql_result);
		
		$tables = array();
		
		for($i=0; $i<$field_count; $i++) {
			if(isset($tables[mysql_field_table($mysql_result, $i)])) {
				$tables[mysql_field_table($mysql_result, $i)]++;
			} else {
				$tables[mysql_field_table($mysql_result, $i)] = 1;
			}
		}
		
		$html = '
			<style type="text/css">
				table.DbugL_PR           { font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif; background:black; margin-bottom:10px; }
				table.DbugL_PR th.t_name { font-size:9pt; font-weight:bold; color:white; }
				table.DbugL_PR th.f_name { font-size:7pt; font-weight:bold; color:white; }
				table.DbugL_PR td        { padding-left:2px;font-size:7pt; white-space:nowrap; vertical-align:top; } 
			</style>
			<script type="text/javascript">
				//<![CDATA[

				var DbugL_last_id;
				function DbugL_highlight(id) {
					if(DbugL_last_id) {
						DbugL_last_id.style.color = "#000000";
						DbugL_last_id.style.textDecoration = "none";
					}
					var highlight_td;
					highlight_td = document.getElementById(id);
					highlight_td.style.color ="#FF0000";
					highlight_td.style.textDecoration = "underline";
					DbugL_last_id = highlight_td;
				}

				//]]>
			</script>
		';
	
		$html .= '<table class="DbugL_PR" cellspacing="1" cellpadding="1">';
		$html .= '<tr>';
		foreach($tables as $tableName => $tableCount) {
			@$col == '#006F05' ? $col = '#00A607' : $col = '#006F05';
			$html .= '<th colspan="'.$tableCount.'" class="t_name" style="background:'.$col.';">'.$tableName.'</th>';
		}
		$html .= '</tr>';
		
		$html .= '<tr>';
		for($i=0;$i < mysql_num_fields($mysql_result);$i++) {
			$field = mysql_field_name($mysql_result, $i);
			$col == '#0054A6' ? $col = '#003471' : $col = '#0054A6';
			$html .= '<th style="background:'.$col.';" class="f_name">'.$field.'</th>';
		}
		$html .= '</tr>';
	
		mysql_data_seek($mysql_result, 0);
		
		$toggle = FALSE;
		$pointer = 0;

		$table_id = str_replace('.', '', microtime(TRUE));
		while($db_row = mysql_fetch_array($mysql_result, MYSQL_NUM)) {
			$pointer++;
			if($toggle) {
				$col1 = "#E6E6E6";
				$col2 = "#DADADA";
			} else {
				$col1 = "#E1F0FF";
				$col2 = "#DAE8F7";
			}
			
			$toggle = !$toggle;
			$id = 'DbugL_'.$table_id.'_'.$pointer;
			$html .= '<tr id="'.$id.'" onMouseDown="DbugL_highlight(\''.$id.'\');">';
			foreach($db_row as $i => $value) {
				$col == $col1 ? $col = $col2 : $col = $col1;
				$flags = mysql_field_flags($mysql_result, $i);
				$primary_flag = strpos($flags, 'primary_key') !== FALSE;
				$html .= '<td style="background:'.$col.';'.($primary_flag ? 'font-weight:bold;' : '').'" nowrap="nowrap">'.nl2br($value).'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		mysql_data_seek($mysql_result, 0);
		
		if($return_mode) {
			return $html;
		} else {
			print $html;
		}
	}
	
	
else:

	function pre() {}
	function print_a() {}
	function die_a() {}
	function script_runtime() {}
	function show_vars() {}
	
endif;
?>