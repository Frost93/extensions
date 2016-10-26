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

class PlgSystemModalsHelperLink
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemModalsHelpers::getInstance();
		$this->params  = $this->helpers->getParams();
	}

	public function buildLink($attributes, $data, $content = '')
	{

		$this->setVideoUrl($attributes, $data);

		$isexternal = $this->helpers->get('file')->isExternal($attributes->href);
		$ismedia    = $this->helpers->get('file')->isMedia($attributes->href);
		$isiframe   = $this->helpers->get('file')->isIframe($attributes->href, $data);

		if ($ismedia)
		{
			$auto_titles = isset($data['title']) ? 0 : (isset($data['auto_titles']) ? $data['auto_titles'] : $this->params->auto_titles);
			$title_case  = isset($data['title_case']) ? $data['title_case'] : $this->params->title_case;
			if ($auto_titles)
			{
				$data['title'] = $this->helpers->get('file')->getTitle($attributes->href, $title_case);
			}

		}
		unset($data['auto_titles']);

		// Force/overrule certain data values
		if ($isiframe || ($isexternal && !$ismedia))
		{
			// use iframe mode for external urls
			$data['iframe'] = 'true';
			$this->helpers->get('data')->setDataWidthHeight($data, $isexternal);
		}

		if ($attributes->href && $attributes->href['0'] != '#' && !$isexternal && !$ismedia)
		{
			$this->helpers->get('scripts')->addTmpl($attributes->href, $isiframe);
		}


		if (empty($data['title']) && empty($attributes->{'data-modal-title'}))
		{
			$data['classname'] = (isset($data['classname']) ? $data['classname'] . ' ' : '') . 'no_title';
			$data['title']     = '';
		}

		if (!empty($data['autoclose']) && $this->params->countdown)
		{
			$data['title'] .= '<div class="countdown"></div>';
		}

		return
			'<a'
			. $this->helpers->get('data')->flattenAttributeList($attributes)
			. $this->helpers->get('data')->flattenDataAttributeList($data)
			. '>'
			. $content;
	}

	public function getLink($string, $link = '', $content = '')
	{
		list($attributes, $data, $extra) = $this->getLinkData($string, $link);

		return array($this->buildLink($attributes, $data, $content), $extra);
	}

	public function getLinkData($string, $link = '')
	{
		$attributes = $this->prepareLinkAttributeList($link);

		RLTags::protectSpecialChars($string);

		$is_old_syntax =
			(strpos($string, '|') !== false)
			|| (strpos($string, '"') === false && strpos($string, '&quot;') === false);

		if ($is_old_syntax)
		{
			// Replace open attribute with open=1
			$string = preg_replace('#(^|\|)open($|\|)#', '\1open=1\2', $string);

			// Add empty url attribute to beginning if no url/href attribute is there,
			// to prevent issues with grabbing values from old syntax
			if (preg_match('#^([a-z]+)=#s', $string, $match))
			{
				if ($match['1'] != 'url' && $match['1'] != 'href')
				{
					$string = 'url=|' . $string;
				}
			}
		}

		RLTags::unprotectSpecialChars($string);

		$known_boolean_keys = array(
			'openOnce', 'inline', 'iframe',
			'auto_titles',
		);

		// Get the values from the tag
		$tag = RLTags::getValuesFromString($string, 'url', $known_boolean_keys);

		$key_aliases = array(
			'url'     => array('href', 'link', 'image', 'src'),
			'gallery' => array('galery', 'images'),
		);

		RLTags::replaceKeyAliases($tag, $key_aliases);

		if (!empty($tag->url))
		{
			$attributes->href = $this->cleanUrl($tag->url);
		}
		unset($tag->url);

		if (!empty($tag->target))
		{
			$attributes->target = $tag->target;
		}
		unset($tag->target);

		$extra = '';


		$attributes->id = !empty($tag->id) ? $tag->id : '';
		unset($tag->id);

		$attributes->class .= !empty($tag->class) ? ' ' . $tag->class : '';
		unset($tag->class);

		if (!empty($tag->title))
		{
			$tag->title = $this->translateString($tag->title);
		}

		if (!empty($tag->description))
		{
			$tag->description = $this->translateString($tag->description);
		}

		// move onSomething params to attributes, except the modal callbacks
		$callbacks = array('onopen', 'onload', 'oncomplete', 'oncleanup', 'onclosed');
		foreach ($tag as $key => $val)
		{
			if (
				substr($key, 0, 2) == 'on'
				&& !in_array(strtolower($key), $callbacks)
				&& is_string($val)
			)
			{
				$attributes->{$key} = $val;
				unset($tag->{$key});
			}
		}

		$data = array();


		// set data by values set in tag
		foreach ($tag as $key => $val)
		{
			$data[strtolower($key)] = $val;
		}

		return array($attributes, $data, $extra);
	}

	private function translateString($string = '')
	{
		if (empty($string) || !preg_match('#^[A-Z][A-Z0-9_]+$#', $string))
		{
			return $string;
		}

		return JText::_($string);
	}

	private function cleanUrl($url)
	{
		return preg_replace('#<a[^>]*>(.*?)</a>#si', '\1', $url);
	}

	private function setVideoUrl(&$attributes, &$data)
	{

		$attributes->href = $this->fixVideoUrl($attributes->href);
	}

	private function fixVideoUrl($url)
	{
		switch (true)
		{
			case(
				strpos($url, 'youtu.be') !== false
				|| strpos($url, 'youtube.com') !== false
			) :
				return $this->fixUrlYoutube($url);

			case(
				strpos($url, 'vimeo.com') !== false
			) :
				return $this->fixUrlVimeo($url);
		}

		return $url;
	}

	private function fixUrlYoutube($url)
	{
		if (!preg_match(
			'#(?:^youtube=|youtu\.be/?|youtube\.com/embed/?|youtube\.com\/watch\?v=)([^/&\?]+)(?:\?|&amp;|&)?(.*)$#i',
			trim($url),
			$parts
		)
		)
		{
			return $url;
		}

		$url = 'https://www.youtube.com/embed/' . $parts['1'] . '?' . $parts['2'];

		if (strpos($parts['2'], 'wmode=transparent') !== false)
		{
			return $url;
		}

		return $url . '&wmode=transparent';
	}

	private function fixUrlVimeo($url)
	{
		if (!preg_match(
			'#(?:^vimeo=|vimeo\.com/(?:video/)?)([0-9]+)(.*)$#i',
			trim($url),
			$parts
		)
		)
		{
			return $url;
		}

		return
			'https://player.vimeo.com/video/'
			. $parts['1']
			. $parts['2'];
	}

	private function prepareLinkAttributeList($link)
	{
		$attributes        = new stdClass;
		$attributes->href  = '';
		$attributes->class = $this->params->class;
		$attributes->id    = '';

		if (!$link)
		{
			return $attributes;
		}

		$link_attributes = $this->getLinkAttributeList(trim($link));

		foreach ($link_attributes as $key => $val)
		{
			$key = trim($key);
			$val = trim($val);

			if ($key == '' || $val == '')
			{
				continue;
			}

			if ($key == 'class')
			{
				$attributes->{$key} = trim($attributes->{$key} . ' ' . $val);
				continue;
			}

			$attributes->{$key} = $val;
		}

		return $attributes;
	}

	public function getLinkAttributeList($string)
	{
		$attributes = new stdClass;

		if (!$string)
		{
			return $attributes;
		}

		preg_match_all('#([a-z0-9_-]+)\s*=\s*(?:"(.*?)"|\'(.*?)\')#si', $string, $params, PREG_SET_ORDER);

		if (empty($params))
		{
			return $attributes;
		}

		foreach ($params as $param)
		{
			$attributes->{$param['1']} = isset($param['3']) ? $param['3'] : $param['2'];
		}

		return $attributes;
	}

}
