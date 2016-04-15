<?php
/**
 * @package         CDN for Joomla!
 * @version         5.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/protect.php';

RLFunctions::loadLanguage('plg_system_cdnforjoomla');

/**
 * Plugin that replaces media urls with CDN urls
 */
class PlgSystemCDNforJoomlaHelper
{
	var $params = null;
	var $pass   = false;

	public function __construct(&$params)
	{
		$this->params = $params;

		$hascdn = preg_replace(array('#^.*\://#', '#/$#'), '', $this->params->cdn);

		// return if cdn field has no value
		if (!$hascdn)
		{
			return;
		}


		$this->params->tag_attribs = $this->getSearchTagAttributes();

		$this->initSetList();

		if (empty($this->params->sets))
		{
			return;
		}

		$this->pass = true;
	}

	private function initSetList()
	{
		$this->params->sets = array();

		$nr_of_sets = 1;
		for ($i = 1; $i <= $nr_of_sets; $i++)
		{
			$this->params->sets[] = $this->initSetParams($i);
		}

		$this->removeEmptySets();
	}

	private function removeEmptySets()
	{
		foreach ($this->params->sets as $i => $set)
		{
			if (!empty($set) && !empty($set->searches))
			{
				continue;
			}

			unset($this->params->sets[$i]);
		}
	}

	private function initSetParams($setid = 1)
	{
		$setid = ($setid <= 1) ? '' : '_' . (int) $setid;

		if ($setid && (!isset($this->params->{'use_extra' . $setid}) || !$this->params->{'use_extra' . $setid}))
		{
			return false;
		}

		$filetypes = $this->getFileTypes($this->params->{'filetypes' . $setid});

		if (empty($filetypes))
		{
			return false;
		}

		$params = new stdClass;

		$params->cdn = preg_replace('#/$#', '', $this->params->{'cdn' . $setid});

		$params->protocol = 'http://';

		$params->filetypes         = $filetypes;
		$params->ignorefiles       = $this->getFileTypes($this->params->{'ignorefiles' . $setid});
		$params->enable_in_scripts = $this->params->{'enable_in_scripts' . $setid};


		$root         = preg_replace(array('#^/#', '#/$#'), '', $this->params->{'root' . $setid}) . '/';
		$params->root = preg_replace('#^/#', '', $root);

		$params->searches    = array();
		$params->js_searches = array();

		$this->params->set = $params;

		$this->setFiletypeSearches();
		$this->setCdnPaths();

		return $this->params->set;
	}

	public function getFileTypes($filetypes)
	{
		return explode(',',
			str_replace(
				array("\n", '\n', ' ', ',.'),
				array(',', ',', '', ','),
				$filetypes
			)
		);
	}


	public function onAfterRender()
	{
		if (!$this->pass)
		{
			return;
		}

		$html = JFactory::getApplication()->getBody();

		$this->replace($html);
		$this->cleanLeftoverJunk($html);

		JFactory::getApplication()->setBody($html);
	}

	private function replace(&$string)
	{
		if (is_array($string))
		{
			$this->replaceInList($string);

			return;
		}

		$string_array = $this->protectString($string);
		foreach ($string_array as $i => &$substring)
		{
			if ($i % 2)
			{
				continue;
			}

			$string_array[$i] = $this->replaceBySetList($substring);
		}

		$string = implode('', $string_array);
	}

	private function replaceInList(&$array)
	{
		foreach ($array as &$val)
		{
			$this->replace($val);
		}
	}

	private function replaceBySetList($string)
	{
		foreach ($this->params->sets as $set)
		{
			$this->params->set = $set;
			$this->replaceBySet($string);
		}

		return $string;
	}

	private function replaceBySet(&$string)
	{
		$this->replaceBySearchList($string, $this->params->set->searches);

		if (!empty($this->params->set->enable_in_scripts) && strpos($string, '<script') !== false)
		{
			$this->replaceInJavascript($string);
		}
	}

	private function replaceInJavascript(&$string)
	{
		$regex = '#<script(?:\s+(language|type)\s*=[^>]*)?>.*?</script>#si';

		preg_match_all($regex, $string, $stringparts, PREG_SET_ORDER);

		if (empty($stringparts))
		{
			return;
		}

		foreach ($stringparts as $stringpart)
		{
			$this->replaceInJavascriptStringPart($string, $stringpart);
		}
	}

	private function replaceInJavascriptStringPart(&$string, $stringpart)
	{
		$newstr = $stringpart['0'];

		if (!$this->replaceBySearchList($newstr, $this->params->set->js_searches))
		{
			return;
		}

		$string = str_replace($stringpart['0'], $newstr, $string);
	}

	private function replaceBySearchList(&$string, &$searches)
	{
		$changed = 0;

		foreach ($searches as $search)
		{
			$changed = $this->replaceBySearch($string, $search);
		}

		return $changed;
	}

	private function replaceBySearch(&$string, &$search)
	{
		preg_match_all($search, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return false;
		}

		$changed = false;

		foreach ($matches as $match)
		{
			list($file, $query) = $this->getFileParts($match['3']);

			if (!$file || $this->fileIsIgnored($file))
			{
				continue;
			}


			$file = $this->getCdnUrl($file)
				. '/' . $this->addQueryToFile($file, $query);

			$string = str_replace(
				$match['0'],
				$match['1'] . $file . $match['4'],
				$string
			);

			$changed = true;
		}

		return $changed;
	}


	private function getFileParts($file)
	{
		$file = trim($file);

		if (!$file)
		{
			return array(null, null);
		}

		if (strpos($file, '?') === false)
		{
			return array($file, null);
		}

		list($file, $query) = explode('?', $file, 2);
		$query = explode('&', $query);

		return array($file, $query);
	}

	private function addQueryToFile($file, $query = array())
	{
		$file = trim($file);

		if (empty($query))
		{
			return $file;
		}

		return $file . '?' . implode('&', $query);
	}

	private function fileIsIgnored($file)
	{
		foreach ($this->params->set->ignorefiles as $ignore)
		{
			if ($ignore && (strpos($file, $ignore) !== false || strpos(htmlentities($file), $ignore) !== false))
			{
				return true;
			}
		}

		return false;
	}

	/*
	 * Searches are replaced by:
	 * '\1http(s)://' . $this->params->cdn . '/\3\4'
	 * \2 is used to reference the possible starting quote
	 */
	private function setFiletypeSearches()
	{
		if (empty($this->params->set->filetypes))
		{
			return;
		}

		$urls = $this->getUrlList();

		foreach ($urls as $url)
		{
			$this->setSearchesByUrl($url);
		}
	}

	/*
	 * Searches are replaced by:
	 * '\1http(s)://' . $this->params->cdn . '/\3\4'
	 * \2 is used to reference the possible starting quote
	 */
	private function getUrlList()
	{
		// Domain url or root path
		$url = preg_quote(str_replace('https://', 'http://', JUri::root()), '#');
		$url .= '|' . preg_quote(str_replace(array('http://', 'https://'), '//', JUri::root()), '#');

		if (JUri::root(1))
		{
			$url .= '|' . preg_quote(JUri::root(1) . '/', '#');
		}

		$filetypes = implode('|', $this->params->set->filetypes);
		$root      = preg_quote($this->params->set->root, '#');

		$urls = array();

		// Absolute path
		$urls[] = '(?:' . $url . ')' . $root . '([^ \?QUOTES]+\.(?:' . $filetypes . ')(?:\?[^QUOTES]*)?)';
		// Relative path
		$urls[] = 'LSLASH' . $root . '([a-z0-9-_]+/[^ \?QUOTES]+\.(?:' . $filetypes . ')(?:\?[^QUOTES]*)?)';
		// Relative path - file in root
		$urls[] = 'LSLASH' . $root . '([a-z0-9-_]+[^ \?\/QUOTES]+\.(?:' . $filetypes . ')(?:\?[^QUOTES]*)?)';

		return $urls;
	}

	private function setSearchesByUrl($url)
	{

		$url_regex = '\s*' . str_replace('QUOTES', '"\'', $url) . '\s*';

		if ($this->params->set->enable_in_scripts)
		{
			$url_regex_js                     = str_replace('LSLASH', '', $url_regex);
			$this->params->set->js_searches[] = '#((["\']))' . $url_regex_js . '(["\'])#i'; // "..."
		}

		$url_regex                 = str_replace('LSLASH', '/?', $url_regex);
		$url_regex_can_have_spaces = str_replace('[^ ', '[^', $url_regex);

		$this->params->set->searches[] = '#((?:' . $this->params->tag_attribs . ')\s*(["\']))' . $url_regex_can_have_spaces . '(\2)#i'; // attrib="..."
		$this->params->set->searches[] = '#((?:' . $this->params->tag_attribs . ')())' . $url_regex . '([\s|>])#i'; // attrib=...
		$this->params->set->searches[] = '#(url\(\s*((?:["\'])?))' . $url_regex_can_have_spaces . '(\2\s*\))#i'; // url(...) or url("...")

		// add ')' to the no quote checks
		$url_regex                     = '\s*' . str_replace('QUOTES', '"\'\)', $url) . '\s*';
		$this->params->set->searches[] = '#(url\(\s*())' . $url_regex . '(\s*\))#i'; // url(...)
	}

	private function getSearchTagAttributes()
	{
		$attributes = array(
			'href=',
			'src=',
			'data-src=',
			'data-srcset=',
			'data-original=',
			'longdesc=',
			'poster=',
			'@import',
			'name="movie" value=',
			'property="og:image" content=',
			'TileImage" content=',
			'rel="{\'link\':',
		);

		return str_replace(array('"', '=', ' '), array('["\']?', '\s*=\s*', '\s+'), implode('|', $attributes));
	}

	private function getCdnUrl($file)
	{
		$cdns = $this->params->set->cdns;


		return $this->params->set->protocol . $cdns['0'];
	}

	private function setCdnPaths()
	{
		$this->params->set->cdns = explode(',', $this->params->set->cdn);
		foreach ($this->params->set->cdns as $i => $cdn)
		{
			$cdn = preg_replace('#^.*\://#', '', trim($cdn));
			$this->params->set->cdns[$i] = $cdn;
		}
	}


	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	private function cleanLeftoverJunk(&$string)
	{
		$string = str_replace(array('{nocdn}', '{/nocdn}'), '', $string);
	}

	private function protectString($string)
	{
		if (RLProtect::isEditPage())
		{
			$string = preg_replace('#(<' . 'form [^>]*(id|name)="(adminForm|postform)".*?</form>)#si', '{nocdn}\1{/nocdn}', $string);
		}

		if (strpos($string, '{nocdn}') === false || strpos($string, '{/nocdn}') === false)
		{
			$string = str_replace(array('{nocdn}', '{/nocdn}'), '', $string);

			return array($string);
		}

		$string = str_replace(array('{nocdn}', '{/nocdn}'), '[[CDN_SPLIT]]', $string);

		return explode('[[CDN_SPLIT]]', $string);
	}
}
