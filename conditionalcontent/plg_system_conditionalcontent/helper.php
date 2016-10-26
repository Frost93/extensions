<?php
/**
 * @package         Conditional Content
 * @version         1.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

// Load common functions
require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/tags.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/text.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/protect.php';

RLFunctions::loadLanguage('plg_system_conditionalcontent');

/**
 * Plugin that replaces stuff
 */
class PlgSystemConditionalContentHelper
{
	var $params = null;

	public function __construct(&$params)
	{
		$this->params = $params;

		$this->params->tag_show = trim($this->params->tag_show);
		$this->params->tag_show = preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_show);

		$this->params->tag_hide = trim($this->params->tag_hide);
		$this->params->tag_hide = preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_hide);

		// Tag character start and end
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		$inside_tag = RLTags::getRegexInsideTag();
		$spaces     = RLTags::getRegexSpaces();

		$this->params->regex = '#'
			. $tag_start . '(?P<tag>' . preg_quote($this->params->tag_show, '#') . '|' . preg_quote($this->params->tag_hide, '#') . ')'
			. '(?P<data>(?:' . $spaces . '|<)' . $inside_tag . ')' . $tag_end
			. '(?P<content>.*?)'
			. $tag_start . '/\1' . $tag_end
			. '#s';

		$this->params->protected_tags = array(
			$this->params->tag_character_start . $this->params->tag_show,
			$this->params->tag_character_start . $this->params->tag_hide,
		);

		$this->assignment_types = array(
			'menuitems',
			'homepage',
			'date',
			'accesslevels',
			'usergrouplevels',
			'devices',
		);;
	}

	public function onContentPrepare(&$article, $context, $params)
	{
		$area    = isset($article->created_by) ? 'articles' : 'other';
		$context = (($params instanceof JRegistry) && $params->get('rl_search')) ? 'com_search.' . $params->get('readmore_limit') : $context;

		RLHelper::processArticle($article, $context, $this, 'replaceTags', array($area, $context));
	}

	public function onAfterDispatch()
	{
		// only in html
		if (JFactory::getDocument()->getType() !== 'html' && !RLFunctions::isFeed())
		{
			return;
		}

		if (!$buffer = RLFunctions::getComponentBuffer())
		{
			return;
		}

		if (strpos($buffer, $this->params->tag_character_start . $this->params->tag_show) === false
			&& strpos($buffer, $this->params->tag_character_start . $this->params->tag_hide) === false
		)
		{
			return;
		}

		$this->replaceTags($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html' && !RLFunctions::isFeed())
		{
			return;
		}

		$html = JFactory::getApplication()->getBody();
		if ($html == '')
		{
			return;
		}

		if (strpos($html, $this->params->tag_character_start . $this->params->tag_show) === false
			&& strpos($html, $this->params->tag_character_start . $this->params->tag_hide) === false
		)
		{
			$this->cleanLeftoverJunk($html);

			JFactory::getApplication()->setBody($html);

			return;
		}

		// only do stuff in body
		list($pre, $body, $post) = RLText::getBody($html);
		$this->replaceTags($body, 'body');
		$html = $pre . $body . $post;

		$this->cleanLeftoverJunk($html);

		JFactory::getApplication()->setBody($html);
	}

	public function replaceTags(&$string, $area = 'article', $context = '')
	{
		if (!is_string($string) || $string == '')
		{
			return;
		}

		// Check if tags are in the text snippet used for the search component
		if (strpos($context, 'com_search.') === 0)
		{
			$limit = explode('.', $context, 2);
			$limit = (int) array_pop($limit);

			$string_check = substr($string, 0, $limit);

			if (strpos($string_check, $this->params->tag_character_start . $this->params->tag_show) === false
				&& strpos($string_check, $this->params->tag_character_start . $this->params->tag_hide) === false
			)
			{
				return;
			}
		}

		if (strpos($string, $this->params->tag_character_start . $this->params->tag_show) === false
			&& strpos($string, $this->params->tag_character_start . $this->params->tag_hide) === false
		)
		{
			return;
		}

		// allow in component?
		if (RLProtect::isRestrictedComponent(isset($this->params->disabled_components) ? $this->params->disabled_components : array(), $area))
		{

			$this->protect($string);

			if (substr($this->params->regex, -1) != 'u' && @preg_match($this->params->regex . 'u', $string))
			{
				$this->params->regex .= 'u';
			}

			$string = preg_replace($this->params->regex, '\2', $string);

			RLProtect::unprotect($string);

			return;
		}

		$this->protect($string);

		list($pre_string, $string, $post_string) = RLText::getContentContainingSearches(
			$string,
			array(
				$this->params->tag_character_start . $this->params->tag_show,
				$this->params->tag_character_start . $this->params->tag_hide,
			),
			array(
				$this->params->tag_character_start . '/' . $this->params->tag_show . $this->params->tag_character_end,
				$this->params->tag_character_start . '/' . $this->params->tag_hide . $this->params->tag_character_end,
			)
		);

		if (substr($this->params->regex, -1) != 'u' && @preg_match($this->params->regex . 'u', $string))
		{
			$this->params->regex .= 'u';
		}

		preg_match_all($this->params->regex, $string, $matches, PREG_SET_ORDER);

		foreach ($matches as $match)
		{
			$this->replaceTag($string, $match);
		}

		$string = $pre_string . $string . $post_string;

		RLProtect::unprotect($string);
	}

	private function replaceTag(&$string, $match)
	{
		$attributes = $this->getTagValues($match['data']);

		$has_access = $this->hasAccess($attributes);
		$has_access = $match['tag'] == 'hide' ? !$has_access : $has_access;

		$content = $this->getContent($match['tag'], $has_access, $match['content'], (isset($attributes->else) ? $attributes->else : ''));

		$string = str_replace($match['0'], $content, $string);
	}

	private function getContent($type, $has_access, $string, $else_string = '')
	{
		$else_tag = $this->params->tag_character_start
			. ($type == 'hide' ? $this->params->tag_hide : $this->params->tag_show) . '-else'
			. $this->params->tag_character_end;

		if (strpos($string, $else_tag) !== false)
		{
			list($string, $else_string) = explode($else_tag, $string, 2);
		}

		if (!$has_access)
		{
			return $else_string;
		}

		return $string;
	}

	private function getTagValues($string)
	{
		$known_boolean_keys = array();

		// Get the values from the tag
		return RLTags::getValuesFromString($string, 'title', $known_boolean_keys, true);
	}

	private function hasAccess($attributes)
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/assignments.php';
		$assignments_helper = new RLAssignmentsHelper;

		$assignments = $assignments_helper->getAssignmentsFromTagAttributes($attributes, $this->assignment_types);

		$matching_method = isset($attributes->matching_method) && strtolower($attributes->matching_method) == 'any'
			? 'or' : 'and';

		return $assignments_helper->passAll($assignments, $matching_method);
	}

	private function protect(&$string)
	{
		RLProtect::protectFields($string);
		RLProtect::protectSourcerer($string);
	}

	private function protectTags(&$string)
	{
		RLProtect::protectTags($string, $this->params->protected_tags);
	}

	private function unprotectTags(&$string)
	{
		RLProtect::unprotectTags($string, $this->params->protected_tags);
	}

	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	private function cleanLeftoverJunk(&$string)
	{
		$this->unprotectTags($string);
	}

	public function getTagCharacters($quote = false)
	{
		if (!isset($this->params->tag_character_start))
		{
			list($this->params->tag_character_start, $this->params->tag_character_end) = explode('.', $this->params->tag_characters);
		}

		$start = $this->params->tag_character_start;
		$end   = $this->params->tag_character_end;

		if ($quote)
		{
			$start = preg_quote($start, '#');
			$end   = preg_quote($end, '#');
		}

		return array($start, $end);
	}
}
