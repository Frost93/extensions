<?php
/**
 * @package         Modals
 * @version         8.1.4
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemModalsHelperPass
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemModalsHelpers::getInstance();
		$this->params  = $this->helpers->getParams();
	}

	public function passLinkChecks($attributes)
	{
		// return if the link has no href
		if (empty($attributes->href))
		{
			return false;
		}

		// return if the link already has the Modals main class
		if (!empty($attributes->class) && in_array($this->params->class, explode(' ', $attributes->class)))
		{
			return false;
		}

		// return if url is in ignore list
		if ($this->urlIgnored($attributes->href))
		{
			return false;
		}

		// check for classnames, external sites and target blanks
		if (
			$this->passClassnames($attributes)
		)
		{
			return true;
		}


		return false;
	}

	public function urlIgnored($url)
	{
		if (empty($this->params->exclude_urls))
		{
			return false;
		}

		$exclude_urls = explode(',', str_replace(array('\n', ' '), array(',', ''), $this->params->exclude_urls));

		foreach ($exclude_urls as $exclude)
		{
			if ($exclude && (strpos($url, $exclude) !== false || strpos(htmlentities($url), $exclude) !== false))
			{
				return true;
			}
		}

		return false;
	}

	public function passClassnames($attributes)
	{
		if (empty($attributes->class) || empty($this->params->classnames))
		{
			return false;
		}

		$classnames = str_replace($this->params->class, '', $attributes->class);

		return $this->arrayInArray($classnames, $this->params->classnames);
	}

	private function arrayInArray($needles, $haystack)
	{
		if (!is_array($needles))
		{
			$needles = explode(' ', trim($needles));
		}
		if (!is_array($haystack))
		{
			$haystack = explode(' ', trim($haystack));
		}

		// Check
		return (boolean) array_intersect($haystack, $needles);
	}

	private function passURLs($url)
	{
		foreach ($this->params->urls as $param_url)
		{
			if ($this->passURL($url, $param_url))
			{
				return true;
			}
		}

		return false;
	}

	private function passURL($url, $param_url)
	{
		$url = trim($url);
		if (empty($url))
		{
			return false;
		}

		$param_url = trim($param_url);
		if (empty($param_url))
		{
			return false;
		}

		$urls = array($url, RLText::html_entity_decoder($url));

		foreach ($urls as $url)
		{
			if ($this->params->urls_regex && $this->passURLRegex($url, $param_url))
			{
				return true;
			}

			if ($this->params->urls_regex)
			{
				continue;
			}

			if (strpos($url, $param_url) !== false)
			{
				return true;
			}
		}

		return false;
	}

	private function passURLRegex($url, $param_url)
	{
		$url_part  = str_replace(array('#', '&amp;'), array('\#', '(&amp;|&)'), $param_url);
		$param_url = '#' . $url_part . '#si';

		if (!@preg_match($param_url . 'u', $url)
			&& !@preg_match($param_url, $url)
		)
		{
			return false;
		}

		return true;
	}

}
