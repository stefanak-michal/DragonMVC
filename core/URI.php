<?php

namespace core;

/**
 * URI Class
 *
 * Parses URIs and determines routing
 * Taken and edited from CodeIgniter
 */
class URI
{

	/**
	 * Current uri string
	 *
	 * @var string
	 */
	private $uri_string;

	/**
	 * Get the URI String
	 */
	public function fetchUriString()
	{
        // Is the request coming from the command line?
        if (php_sapi_name() == 'cli' or defined('STDIN'))
        {
            $this->setUriString($this->parseCliArgs());
            return;
        }

        // Let's try the REQUEST_URI first, this will work in most situations
        if ($uri = $this->detectUri())
        {
            $this->setUriString($uri);
            return;
        }

        // Is there a PATH_INFO variable?
        // Note: some servers seem to have trouble with getenv() so we'll test it two ways
        $path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : getenv('PATH_INFO');
        if (trim($path, '/') != '' && $path != "/".SELF)
        {
            $this->setUriString($path);
            return;
        }

        // No PATH_INFO?... What about QUERY_STRING?
        $path =  (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : getenv('QUERY_STRING');
        if (trim($path, '/') != '')
        {
            $this->setUriString($path);
            return;
        }

        // As a last ditch effort lets try using the $_GET array
        if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '')
        {
            $this->setUriString(key($_GET));
            return;
        }

        // We've exhausted all our options...
        $this->uri_string = '';
	}

	/**
	 * Set the URI String
	 *
	 * @param string $str
	 */
	public function setUriString(string $str)
	{
		// If the URI contains only a slash we'll kill it
		$this->uri_string = ($str == '/') ? '' : $str;
	}

	/**
	 * Detects the URI
	 *
	 * This function will detect the URI automatically and fix the query string
	 * if necessary.
	 *
	 * @return string
	 */
	private function detectUri(): string
	{
		if ( ! isset($_SERVER['REQUEST_URI']) OR ! isset($_SERVER['SCRIPT_NAME']))
		{
			return '';
		}

		$uri = $_SERVER['REQUEST_URI'];
		if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
		{
			$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		}
		elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
		{
			$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		}

		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING server var and $_GET array.
		if (strncmp($uri, '?/', 2) === 0)
		{
			$uri = substr($uri, 2);
		}
		$parts = preg_split('#\?#i', $uri, 2);
		$uri = $parts[0];
		if (isset($parts[1]))
		{
			$_SERVER['QUERY_STRING'] = $parts[1];
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		}
		else
		{
			$_SERVER['QUERY_STRING'] = '';
			$_GET = array();
		}

		if ($uri == '/' || empty($uri))
		{
			return '/';
		}

		$uri = parse_url($uri, PHP_URL_PATH);

		// Do some final cleaning of the URI and return it
		return str_replace(array('//', '../'), '/', trim($uri, '/'));
	}

	/**
	 * Parse cli arguments
	 * Take each command line argument and assume it is a URI segment.
	 *
	 * @return string
	 */
	private function parseCliArgs(): string
	{
		$args = array_slice($_SERVER['argv'], 1);

		return $args ? '/' . implode('/', $args) : '';
	}
    
	/**
	 * Fetch the entire URI string
	 *
	 * @return string
	 */
    public function __toString(): string
    {
        return $this->uri_string;
    }

}
