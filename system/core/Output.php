<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Output Class
 *
 * Responsible for sending final output to browser
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Output
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/output.html
 */
class CI_Output {

	/**
	 * Current output string
	 *
	 * @var string
	 * @access 	protected
	 */
	protected $final_output;
	/**
	 * Cache expiration time
	 *
	 * @var int
	 * @access 	protected
	 */
	protected $cache_expiration	= 0;
	/**
	 * List of server headers
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $headers			= array();
	/**
	 * List of mime types
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $mime_types		= array();
	/**
	 * Determines wether profiler is enabled
	 *
	 * @var book
	 * @access 	protected
	 */
	protected $enable_profiler	= FALSE;
	/**
	 * Determines if output compression is enabled
	 *
	 * @var bool
	 * @access 	protected
	 */
	protected $_zlib_oc			= FALSE;
	/**
	 * List of profiler sections
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $_profiler_sections = array();
	/**
	 * Whether or not to parse variables like {elapsed_time} and {memory_usage}
	 *
	 * @var bool
	 * @access 	protected
	 */
	protected $parse_exec_vars	= TRUE;

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		$this->_zlib_oc = @ini_get('zlib.output_compression');

		// Get mime types for later
		if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
		{
		    include APPPATH.'config/'.ENVIRONMENT.'/mimes.php';
		}
		else
		{
			include APPPATH.'config/mimes.php';
		}


		$this->mime_types = $mimes;

		log_message('debug', "Output Class Initialized");
	}

	// --------------------------------------------------------------------

	/**
	 * Get Output
	 *
	 * Returns the current output string
	 *
	 * @access	public
	 * @return	string
	 */
	function get_output()
	{
		return $this->final_output;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Output
	 *
	 * Sets the output string
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_output($output)
	{
		$this->final_output = $output;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Append Output
	 *
	 * Appends data onto the output string
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function append_output($output)
	{
		if ($this->final_output == '')
		{
			$this->final_output = $output;
		}
		else
		{
			$this->final_output .= $output;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Header
	 *
	 * Lets you set a server header which will be outputted with the final display.
	 *
	 * Note:  If a file is cached, headers will not be sent.  We need to figure out
	 * how to permit header data to be saved with the cache data...
	 *
	 * @access	public
	 * @param	string
	 * @param 	bool
	 * @return	void
	 */
	function set_header($header, $replace = TRUE)
	{
		// If zlib.output_compression is enabled it will compress the output,
		// but it will not modify the content-length header to compensate for
		// the reduction, causing the browser to hang waiting for more data.
		// We'll just skip content-length in those cases.

		if ($this->_zlib_oc && strncasecmp($header, 'content-length', 14) == 0)
		{
			return;
		}

		$this->headers[] = array($header, $replace);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Content Type Header
	 *
	 * @access	public
	 * @param	string	extension of the file we're outputting
	 * @return	void
	 */
	function set_content_type($mime_type)
	{
		if (strpos($mime_type, '/') === FALSE)
		{
			$extension = ltrim($mime_type, '.');

			// Is this extension supported?
			if (isset($this->mime_types[$extension]))
			{
				$mime_type =& $this->mime_types[$extension];

				if (is_array($mime_type))
				{
					$mime_type = current($mime_type);
				}
			}
		}

		$header = 'Content-Type: '.$mime_type;

		$this->headers[] = array($header, TRUE);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set HTTP Status Header
	 * moved to Common procedural functions in 1.7.2
	 *
	 * @access	public
	 * @param	int		the status code
	 * @param	string
	 * @return	void
	 */
	function set_status_header($code = 200, $text = '')
	{
		set_status_header($code, $text);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Enable/disable Profiler
	 *
	 * @access	public
	 * @param	bool
	 * @return	void
	 */
	function enable_profiler($val = TRUE)
	{
		$this->enable_profiler = (is_bool($val)) ? $val : TRUE;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Profiler Sections
	 *
	 * Allows override of default / config settings for Profiler section display
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function set_profiler_sections($sections)
	{
		foreach ($sections as $section => $enable)
		{
			$this->_profiler_sections[$section] = ($enable !== FALSE) ? TRUE : FALSE;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Cache
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	function cache($time)
	{
		$this->cache_expiration = ( ! is_numeric($time)) ? 0 : $time;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Output
	 *
	 * All "view" data is automatically put into this variable by the controller class:
	 *
	 * $this->final_output
	 *
	 * This function sends the finalized output data to the browser along
	 * with any server headers and profile data.  It also stops the
	 * benchmark timer so the page rendering speed and memory usage can be shown.
	 *
	 * @access	public
	 * @param 	string
	 * @return	mixed
	 */
	function _display($output = '')
	{
		// Note:  We use globals because we can't use $CI =& get_instance()
		// since this function is sometimes called by the caching mechanism,
		// which happens before the CI super object is available.
		global $BM, $CFG;

		// Grab the super object if we can.
		if (class_exists('CI_Controller'))
		{
			$CI =& get_instance();
		}

		// --------------------------------------------------------------------

		// Set the output data
		if ($output == '')
		{
			$output =& $this->final_output;
		}

		// --------------------------------------------------------------------

		// Do we need to write a cache file?  Only if the controller does not have its
		// own _output() method and we are not dealing with a cache file, which we
		// can determine by the existence of the $CI object above
		if ($this->cache_expiration > 0 && isset($CI) && ! method_exists($CI, '_output'))
		{
			$this->_write_cache($output);
		}

		// --------------------------------------------------------------------

		// Parse out the elapsed time and memory usage,
		// then swap the pseudo-variables with the data

		$elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');

		if ($this->parse_exec_vars === TRUE)
		{
			$memory	 = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';

			$output = str_replace('{elapsed_time}', $elapsed, $output);
			$output = str_replace('{memory_usage}', $memory, $output);
		}

		// --------------------------------------------------------------------

		// Is compression requested?
		if ($CFG->item('compress_output') === TRUE && $this->_zlib_oc == FALSE)
		{
			if (extension_loaded('zlib'))
			{
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
				{
					ob_start('ob_gzhandler');
				}
			}
		}

		// --------------------------------------------------------------------

		// Are there any server headers to send?
		if (count($this->headers) > 0)
		{
			foreach ($this->headers as $header)
			{
				@header($header[0], $header[1]);
			}
		}

		// --------------------------------------------------------------------

		// Does the $CI object exist?
		// If not we know we are dealing with a cache file so we'll
		// simply echo out the data and exit.
		if ( ! isset($CI))
		{
			echo $output;
			log_message('debug', "Final output sent to browser");
			log_message('debug', "Total execution time: ".$elapsed);
			return TRUE;
		}

		// --------------------------------------------------------------------

		// Do we need to generate profile data?
		// If so, load the Profile class and run it.
		if ($this->enable_profiler == TRUE)
		{
			$CI->load->library('profiler');

			if ( ! empty($this->_profiler_sections))
			{
				$CI->profiler->set_sections($this->_profiler_sections);
			}

			// If the output data contains closing </body> and </html> tags
			// we will remove them and add them back after we insert the profile data
			if (preg_match("|</body>.*?</html>|is", $output))
			{
				$output  = preg_replace("|</body>.*?</html>|is", '', $output);
				$output .= $CI->profiler->run();
				$output .= '</body></html>';
			}
			else
			{
				$output .= $CI->profiler->run();
			}
		}

		// --------------------------------------------------------------------

		// Does the controller contain a function named _output()?
		// If so send the output there.  Otherwise, echo it.
		if (method_exists($CI, '_output'))
		{
			$CI->_output($output);
		}
		else
		{
			echo $output;  // Send it to the browser!
		}

		log_message('debug', "Final output sent to browser");
		log_message('debug', "Total execution time: ".$elapsed);
	}

	// --------------------------------------------------------------------

	/**
	 * Write a Cache File
	 *
	 * @access	public
	 * @param 	string
	 * @return	void
	 */
	function _write_cache($output)
	{
		$CI =& get_instance();
		$path = $CI->config->item('cache_path');

		$cache_path = ($path == '') ? APPPATH.'cache/' : $path;

		if ( ! is_dir($cache_path) OR ! is_really_writable($cache_path))
		{
			log_message('error', "Unable to write cache file: ".$cache_path);
			return;
		}

		$uri =	$CI->config->item('base_url').
				$CI->config->item('index_page').
				$CI->uri->uri_string();

		$cache_path .= md5($uri);

		if ( ! $fp = @fopen($cache_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			log_message('error', "Unable to write cache file: ".$cache_path);
			return;
		}

		$expire = time() + ($this->cache_expiration * 60);

		if (flock($fp, LOCK_EX))
		{
			fwrite($fp, $expire.'TS--->'.$output);
			flock($fp, LOCK_UN);
		}
		else
		{
			log_message('error', "Unable to secure a file lock for file at: ".$cache_path);
			return;
		}
		fclose($fp);
		@chmod($cache_path, FILE_WRITE_MODE);

		log_message('debug', "Cache file written: ".$cache_path);

		// Send HTTP cache-control headers to browser to match file cache settings.
		$this->set_cache_header($_SERVER['REQUEST_TIME'], $expire);
	}

	// --------------------------------------------------------------------

	/**
	 * Update/serve a cached file
	 *
	 * @access	public
	 * @param 	object	config class
	 * @param 	object	uri class
	 * @return	void
	 */
	function _display_cache(&$CFG, &$URI)
	{
		$cache_path = ($CFG->item('cache_path') === '') ? APPPATH.'cache/' : $CFG->item('cache_path');

		// Build the file path. The file name is an MD5 hash of the full URI
		$uri =	$CFG->item('base_url').$CFG->item('index_page').$URI->uri_string;
		$filepath = $cache_path.md5($uri);

		if ( ! @file_exists($filepath) OR ! $fp = @fopen($filepath, FOPEN_READ))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$cache = (filesize($filepath) > 0) ? fread($fp, filesize($filepath)) : '';

		flock($fp, LOCK_UN);
		fclose($fp);

		// Look for embedded serialized file info.
		if ( ! preg_match('/^(.*)ENDCI--->/', $cache, $match))
		{
			return FALSE;
		}

		$cache_info = unserialize($match[1]);
		$expire = $cache_info['expire'];

		$last_modified = filemtime($cache_path);

		// Has the file expired?
		if ($_SERVER['REQUEST_TIME'] >= $expire && is_really_writable($cache_path))
		{
			// If so we'll delete it.
			@unlink($filepath);
			log_message('debug', 'Cache file has expired. File deleted.');
			return FALSE;
		}
		else
		{
			// Or else send the HTTP cache control headers.
			$this->set_cache_header($last_modified, $expire);
		}

		// Add headers from cache file.
		foreach ($cache_info['headers'] as $header)
		{
			$this->set_header($header[0], $header[1]);
		}

		// Display the cache
		$this->_display(substr($cache, strlen($match[0])));
		log_message('debug', 'Cache file is current. Sending it to browser.');
		return TRUE;
	}

	/**
	 * Set Cache Header
	 *
	 * Set the HTTP headers to match the server-side file cache settings
	 * in order to reduce bandwidth.
	 *
	 * @param	int	$last_modified	Timestamp of when the page was last modified
	 * @param	int	$expiration	Timestamp of when should the requested page expire from cache
	 * @return	void
	 */
	public function set_cache_header($last_modified, $expiration)
	{
		$max_age = $expiration - $_SERVER['REQUEST_TIME'];

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $last_modified <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			$this->set_status_header(304);
			exit;
		}
		else
		{
			header('Pragma: public');
			header('Cache-Control: max-age=' . $max_age . ', public');
			header('Expires: '.gmdate('D, d M Y H:i:s', $expiration).' GMT');
			header('Last-modified: '.gmdate('D, d M Y H:i:s', $last_modified).' GMT');
		}
	}
	// --------------------------------------------------------------------

	/**
	 * Minify
	 *
	 * Reduce excessive size of HTML/CSS/JavaScript content.
	 *
	 * @param	string	$output	Output to minify
	 * @param	string	$type	Output content MIME type
	 * @return	string	Minified output
	 */
	public function minify($output, $type = 'text/html')
	{
		switch ($type)
		{
			case 'text/html':

				if (($size_before = strlen($output)) === 0)
				{
					return '';
				}

				// Find all the <pre>,<code>,<textarea>, and <javascript> tags
				// We'll want to return them to this unprocessed state later.
				preg_match_all('{<pre.+</pre>}msU', $output, $pres_clean);
				preg_match_all('{<code.+</code>}msU', $output, $codes_clean);
				preg_match_all('{<textarea.+</textarea>}msU', $output, $textareas_clean);
				preg_match_all('{<script.+</script>}msU', $output, $javascript_clean);

				// Minify the CSS in all the <style> tags.
				preg_match_all('{<style.+</style>}msU', $output, $style_clean);
				foreach ($style_clean[0] as $s)
				{
					$output = str_replace($s, $this->_minify_script_style($s, TRUE), $output);
				}

				// Minify the javascript in <script> tags.
				foreach ($javascript_clean[0] as $s)
				{
					$javascript_mini[] = $this->_minify_script_style($s, TRUE);
				}

				// Replace multiple spaces with a single space.
				$output = preg_replace('!\s{2,}!', ' ', $output);

				// Remove comments (non-MSIE conditionals)
				$output = preg_replace('{\s*<!--[^\[<>].*(?<!!)-->\s*}msU', '', $output);

				// Remove spaces around block-level elements.
				$output = preg_replace('/\s*(<\/?(html|head|title|meta|script|link|style|body|table|thead|tbody|tfoot|tr|th|td|h[1-6]|div|p|br)[^>]*>)\s*/is', '$1', $output);

				// Replace mangled <pre> etc. tags with unprocessed ones.

				if ( ! empty($pres_clean))
				{
					preg_match_all('{<pre.+</pre>}msU', $output, $pres_messed);
					$output = str_replace($pres_messed[0], $pres_clean[0], $output);
				}

				if ( ! empty($codes_clean))
				{
					preg_match_all('{<code.+</code>}msU', $output, $codes_messed);
					$output = str_replace($codes_messed[0], $codes_clean[0], $output);
				}

				if ( ! empty($textareas_clean))
				{
					preg_match_all('{<textarea.+</textarea>}msU', $output, $textareas_messed);
					$output = str_replace($textareas_messed[0], $textareas_clean[0], $output);
				}

				if (isset($javascript_mini))
				{
					preg_match_all('{<script.+</script>}msU', $output, $javascript_messed);
					$output = str_replace($javascript_messed[0], $javascript_mini, $output);
				}

				$size_removed = $size_before - strlen($output);
				$savings_percent = round(($size_removed / $size_before * 100));

				log_message('debug', 'Minifier shaved '.($size_removed / 1000).'KB ('.$savings_percent.'%) off final HTML output.');

			break;

			case 'text/css':
			case 'text/javascript':

				$output = $this->_minify_script_style($output);

			break;

			default: break;
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Minify Style and Script
	 *
	 * Reduce excessive size of CSS/JavaScript content.  To remove spaces this
	 * script walks the string as an array and determines if the pointer is inside
	 * a string created by single quotes or double quotes.  spaces inside those
	 * strings are not stripped.  Opening and closing tags are severed from
	 * the string initially and saved without stripping whitespace to preserve
	 * the tags and any associated properties if tags are present
	 *
	 * Minification logic/workflow is similar to methods used by Douglas Crockford
	 * in JSMIN. http://www.crockford.com/javascript/jsmin.html
	 *
	 * KNOWN ISSUE: ending a line with a closing parenthesis ')' and no semicolon
	 * where there should be one will break the Javascript. New lines after a
	 * closing parenthesis are not recognized by the script. For best results
	 * be sure to terminate lines with a semicolon when appropriate.
	 *
	 * @param	string	$output		Output to minify
	 * @param	bool	$has_tags	Specify if the output has style or script tags
	 * @return	string	Minified output
	 */
	protected function _minify_script_style($output, $has_tags = FALSE)
	{
		// We only need this if there are tags in the file
		if ($has_tags === TRUE)
		{
			// Remove opening tag and save for later
			$pos = strpos($output, '>') + 1;
			$open_tag = substr($output, 0, $pos);
			$output = substr_replace($output, '', 0, $pos);

			// Remove closing tag and save it for later
			$end_pos = strlen($output);
			$pos = strpos($output, '</');
			$closing_tag = substr($output, $pos, $end_pos);
			$output = substr_replace($output, '', $pos);
		}

		// Remove CSS comments
		$output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!i', '', $output);

		// Remove spaces around curly brackets, colons,
		// semi-colons, parenthesis, commas
		$output = preg_replace('!\s*(:|;|,|}|{|\(|\))\s*!i', '$1', $output);

		// Replace tabs with spaces
		// Replace carriage returns & multiple new lines with single new line
		// and trim any leading or trailing whitespace
		$output = trim(preg_replace(array('/\t+/', '/\r/', '/\n+/'), array(' ', "\n", "\n"), $output));

		// Remove spaces when safe to do so.
		$in_string = $in_dstring = $prev = FALSE;
		$array_output = str_split($output);
		foreach ($array_output as $key => $value)
		{
			if ($in_string === FALSE && $in_dstring === FALSE)
			{
				if ($value === ' ')
				{
					// Get the next element in the array for comparisons
					$next = $array_output[$key + 1];

					// Strip spaces preceded/followed by a non-ASCII character
					// or not preceded/followed by an alphanumeric
					// or not preceded/followed \ $ and _
					if ((preg_match('/^[\x20-\x7f]*$/D', $next) OR preg_match('/^[\x20-\x7f]*$/D', $prev))
						&& ( ! ctype_alnum($next) OR ! ctype_alnum($prev))
						&& ! in_array($next, array('\\', '_', '$'), TRUE)
						&& ! in_array($prev, array('\\', '_', '$'), TRUE)
					)
					{
						unset($array_output[$key]);
					}
				}
				else
				{
					// Save this value as previous for the next iteration
					// if it is not a blank space
					$prev = $value;
				}
			}

			if ($value === "'")
			{
				$in_string = ! $in_string;
			}
			elseif ($value === '"')
			{
				$in_dstring = ! $in_dstring;
			}
		}

		// Put the string back together after spaces have been stripped
		$output = implode($array_output);

		// Remove new line characters unless previous or next character is
		// printable or Non-ASCII
		preg_match_all('/[\n]/', $output, $lf, PREG_OFFSET_CAPTURE);
		$removed_lf = 0;
		foreach ($lf as $feed_position)
		{
			foreach ($feed_position as $position)
			{
				$position = $position[1] - $removed_lf;
				$next = $output[$position + 1];
				$prev = $output[$position - 1];
				if ( ! ctype_print($next) && ! ctype_print($prev)
					&& ! preg_match('/^[\x20-\x7f]*$/D', $next)
					&& ! preg_match('/^[\x20-\x7f]*$/D', $prev)
				)
				{
					$output = substr_replace($output, '', $position, 1);
					$removed_lf++;
				}
			}
		}

		// Put the opening and closing tags back if applicable
		return isset($open_tag)
			? $open_tag.$output.$closing_tag
			: $output;
	}
}
// END Output Class

/* End of file Output.php */
/* Location: ./system/core/Output.php */