<?php
/**
 * @package         Dummy Content
 * @version         3.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/protect.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/tags.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/text.php';

RLFunctions::loadLanguage('plg_system_dummycontent');

/**
 * Plugin that places dummy texts
 */
class PlgSystemDummyContentHelper
{
	var $option  = '';
	var $params  = null;
	var $helpers = array();

	public function __construct(&$params)
	{
		$this->option = JFactory::getApplication()->input->get('option');

		$this->params = $params;

		$this->params->tag = trim($this->params->tag);

		// Tag character start and end
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		// Break/paragraph start and end tags
		$this->params->breaks_start = RLTags::getRegexSurroundingTagPre();
		$this->params->breaks_end   = RLTags::getRegexSurroundingTagPost();
		$breaks_start               = $this->params->breaks_start;
		$breaks_end                 = $this->params->breaks_end;
		$inside_tag                 = RLTags::getRegexInsideTag();
		$spaces                     = RLTags::getRegexSpaces();

		$this->params->regex = '#'
			. '(?P<pre>' . $breaks_start . ')'
			. $tag_start . $this->params->tag . '(?:' . $spaces . '(?P<data>' . $inside_tag . '))?' . $tag_end
			. '(?P<post>' . $breaks_end . ')'
			. '#s';

		$this->params->protected_tags = array(
			$this->params->tag_character_start . $this->params->tag,
		);

		require_once __DIR__ . '/helpers/helpers.php';
		$this->helpers = PlgSystemDummyContentHelpers::getInstance($params);
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

		if (strpos($html, $this->params->tag_character_start . $this->params->tag) === false)
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

			if (strpos($string_check, $this->params->tag_character_start . $this->params->tag) === false)
			{
				return;
			}
		}

		if (strpos($string, $this->params->tag_character_start . $this->params->tag) === false)
		{
			return;
		}

		// allow in component?
		if (RLProtect::isRestrictedComponent(isset($this->params->disabled_components) ? $this->params->disabled_components : array(), $area))
		{

			$this->protect($string);

			$string = preg_replace($this->params->regex, '', $string);

			RLProtect::unprotect($string);

			return;
		}

		$this->protect($string);

		$this->replace($string);

		RLProtect::unprotect($string);
	}

	private function replace(&$string)
	{
		list($pre_string, $string, $post_string) = RLText::getContentContainingSearches(
			$string,
			array(
				$this->params->tag_character_start . $this->params->tag,
			),
			null, 200, 500
		);

		if ($string == '')
		{
			$string = $pre_string . $string . $post_string;

			return;
		}

		preg_match_all($this->params->regex, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			$string = $pre_string . $string . $post_string;

			return;
		}

		foreach ($matches as $match)
		{
			$options = $this->getOptions($match['data']);
			$text    = $this->generate($options);

			list($pre, $post) = RLTags::cleanSurroundingTags(array($match['pre'], $match['post']));

			$string = RLText::strReplaceOnce($match['0'], $pre . $text . $post, $string);
		}

		$string = $pre_string . $string . $post_string;
	}

	private function getOptions($string = '')
	{
		$options = new stdClass;

		$string = trim(str_replace(array('&nbsp;', '&#160;'), '', $string));
		if ($string == '')
		{
			return $options;
		}

		$string = explode('|', $string);
		foreach ($string as $sub_string)
		{
			$key = $sub_string;
			$val = 1;
			if (strpos($sub_string, '=') !== false)
			{
				list($key, $val) = explode('=', $sub_string, 2);
			}

			switch ($key)
			{
				case 'i' :
				case 'img' :
				case 'images' :
					$key = 'image';
					break;

				case 'p' :
				case 'paragraph' :
					$key = 'paragraphs';
					break;

				case 's' :
				case 'sentence' :
					$key = 'sentences';
					break;

				case 'word' :
				case 'w' :
					$key = 'words';
					break;

				case 'l' :
				case 'lists' :
					$key = 'list';
					break;

				case 't' :
				case 'titles' :
					$key = 'title';
					break;

				case 'e' :
				case 'emails' :
					$key = 'email';
					break;

				case 'k' :
				case 'ks' :
				case 'kitchen' :
				case 'sink' :
					$key = 'kitchensink';
					break;
			}

			$options->{$key} = $val;
		}

		return $options;
	}

	private function generate($options = '')
	{
		if (isset($options->image))
		{
			return $this->generateImage($options);
		}

		return $this->generateText($options);
	}

	private function generateImage($options = '')
	{
		return $this->helpers->get('image')->render($options);
	}

	private function generateText($options = '')
	{

		switch (true)
		{
			case (isset($options->kitchensink)) :
				$text = $this->helpers->get('text')->kitchenSink();
				break;
			case (isset($options->paragraphs)) :
				$text = $this->helpers->get('text')->paragraphs((int) $options->paragraphs);
				break;
			case (isset($options->sentences)) :
				$text = $this->helpers->get('text')->sentences((int) $options->sentences);
				break;
			case (isset($options->words)) :
				$text = $this->helpers->get('text')->words((int) $options->words);
				break;
			case (isset($options->list)) :
				$type = isset($options->list_type) ? $options->list_type : '';
				$text = $this->helpers->get('text')->alist((int) $options->list, $type);
				break;
			case (isset($options->title)) :
				$text = $this->helpers->get('text')->title((int) $options->title);
				break;
			case (isset($options->email)) :
				$text = $this->helpers->get('text')->email();
				break;
			case ($this->params->type == 'list') :
				$text = $this->helpers->get('text')->alist((int) $this->params->list_count, $this->params->list_type);
				break;
			default :
				$type = method_exists('PlgSystemDummyContentHelperText', $this->params->type) ? $this->params->type : 'paragraphs';

				$count = isset($this->params->{$type . '_count'}) ? $this->params->{$type . '_count'} : 0;

				$text = $this->helpers->get('text')->{$type}((int) $count);
				break;
		}

		return $text;
	}

	private function protect(&$string)
	{
		RLProtect::protectFields($string);
		RLProtect::protectSourcerer($string);
	}

	private function protectTags(&$string)
	{
		RLProtect::protectTags($string, $this->params->protected_tags, false);
	}

	private function unprotectTags(&$string)
	{
		RLProtect::unprotectTags($string, $this->params->protected_tags, false);
	}

	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	private function cleanLeftoverJunk(&$string)
	{
		$this->unprotectTags($string);

		RLProtect::removeFromHtmlTagContent($string, $this->params->protected_tags, false);
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
