<?php
/**
 * @package         Modals
 * @version         7.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemModalsHelperReplace
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemModalsHelpers::getInstance();
		$this->params  = $this->helpers->getParams();

		$this->params->tag = trim($this->params->tag);

		// Tag character start and end
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		// Break/paragraph start and end tags
		$this->params->breaks_start = RLTags::getRegexSurroundingTagPre();
		$this->params->breaks_end   = RLTags::getRegexSurroundingTagPost();
		$breaks_start               = $this->params->breaks_start;
		$breaks_end                 = $this->params->breaks_end;
		$spaces                     = RLTags::getRegexSpaces();
		$spaces_none                = RLTags::getRegexSpaces('*');
		$inside_tag                 = RLTags::getRegexInsideTag();

		$a_tag        = RLTags::getRegexTags('a', false, false);
		$spans_images = RLTags::getRegexTags(array('span', 'i', 'img'));
		$any_text = '[^<>]*';

		$this->params->regex = '#'
			. '(?P<start_pre>' . $breaks_start . ')'
			. $tag_start . $this->params->tag . $spaces . '(?P<data>' . $inside_tag . ')' . $tag_end
			. '(?P<start_post>' . $breaks_end . ')'

			. '(?P<pre>' . $breaks_start . ')'
			. '(?P<text>.*?)'
			. '(?P<post>' . $breaks_end . ')'

			. '(?P<end_pre>' . $breaks_start . ')'
			. $tag_start . '\/' . $this->params->tag . $tag_end
			. '(?P<end_post>' . $breaks_end . ')'
			. '#s';

		$this->params->regex_inlink = '#'
			. '(?P<link_start>' . $a_tag . ')'
			. '(?P<pre>' . $any_text . ')'

			. '(?P<image_pre>(?:' . $spans_images . $any_text . '){0,6})'

			. $tag_start . $this->params->tag . $spaces_none . '(?P<data>' . $inside_tag . ')' . $tag_end

			. '(?P<text>.*?)'

			. $tag_start . '\/' . $this->params->tag . $tag_end

			. '(?P<image_post>(?:' . $any_text . $spans_images . '){0,6})'

			. '(?P<post>' . $any_text . ')'
			. '(?P<link_end></a>)'
			. '#s';

		$this->params->regex_link = '#'
			. $a_tag
			. '#s';

	}

	public function replace(&$string, $area = 'article', $context = '')
	{
		if ($area == 'article')
		{
			return;
		}

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

			if (
				strpos($string_check, $this->params->tag_character_start . $this->params->tag) === false
			)
			{
				return;
			}
		}

		RLProtect::removeFromHtmlTagAttributes(
			$string, array(
				$this->params->tag,
			)
		);

		// allow in component?
		if (RLProtect::isRestrictedComponent(isset($this->params->disabled_components) ? $this->params->disabled_components : array(), $area))
		{

			$this->helpers->get('protect')->protect($string);

			$string = preg_replace($this->params->regex, '\4', $string);

			RLProtect::unprotect($string);

			return;
		}

		$this->helpers->get('protect')->protect($string);

		// Handle content inside the iframed modal
		if (JFactory::getApplication()->input->getInt('ml', 0) && JFactory::getApplication()->input->getInt('iframe', 0))
		{
			$this->replaceInsideModal($string);

			RLProtect::unprotect($string);

			return;
		}

		$this->replaceLinks($string);

		// tag syntax inside links
		$this->replaceTagSyntaxInsideLinks($string);

		list($pre_string, $string, $post_string) = RLText::getContentContainingSearches(
			$string,
			array(
				$this->params->tag_character_start . $this->params->tag,
			),
			array(
				$this->params->tag_character_start . '/' . $this->params->tag . '}',
			)
		);

		// tag syntax
		$this->replaceTagSyntax($string);

		$string = $pre_string . $string . $post_string;


		RLProtect::unprotect($string);
	}

	// add ml to internal links
	private function replaceInsideModal(&$string)
	{
		$this->replaceTagSyntax($string);

		preg_match_all($this->params->regex_link, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			// get the link attributes
			$attributes = $this->helpers->get('link')->getLinkAttributeList($match['0']);

			// ignore if the link has no href or is an anchor or has a target
			if (empty($attributes->href) || $attributes->href['0'] != '#' || isset($attributes->target))
			{
				continue;
			}

			// ignore if link is external or an image
			if ($this->helpers->get('file')->isExternal($attributes->href) || $this->helpers->get('file')->isMedia($attributes->href))
			{
				continue;
			}

			$href = $attributes->href;
			$this->helpers->get('scripts')->addTmpl($attributes->href, 1);
			$this->replaceOnce('href="' . $href . '"', 'href="' . $attributes->href . '"', $string);
		}
	}

	private function replaceTagSyntaxInsideLinks(&$string)
	{
		preg_match_all($this->params->regex_inlink, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$content = trim($match['image_pre'] . $match['text'] . $match['image_post']);

			list($link, $extra) = $this->helpers->get('link')->getLink($match['data'], $match['link_start'], $content);
			$link .= '</a>';

			$this->replaceOnce($match['0'], $link, $string, $extra);
		}
	}

	private function replaceTagSyntax(&$string)
	{
		preg_match_all($this->params->regex, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$tags = RLTags::cleanSurroundingTags(
				array(
					'end_pre'    => $match['end_pre'],
					'start_post' => $match['start_post'],
				)
			);
			$tags = RLTags::cleanSurroundingTags(
				array(
					'end_pre'    => $tags['end_pre'],
					'pre'        => $match['pre'],
					'post'       => $match['post'],
					'start_post' => $tags['start_post'],
				),
				array('p')
			);

			list($link, $extra) = $this->helpers->get('link')->getLink($match['data'], '', trim($tags['pre'] . $match['text'] . $tags['post']));

			$html = $match['start_pre'] . $tags['start_post']
				. $link . '</a>'
				. $tags['end_pre'] . $match['end_post'];

			$this->replaceOnce($match['0'], $html, $string, $extra);
		}
	}

	private function replaceLinks(&$string)
	{
		if (
			(
				empty($this->params->classnames)
				&& !preg_match('#class\s*=\s*(?:"[^"]*|\'[^\']*)(?:' . implode('|', $this->params->classnames) . ')#s', $string)
			)
		)
		{
			return;
		}

		preg_match_all($this->params->regex_link, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$this->replaceLink($string, $match);
		}
	}

	private function replaceLink(&$string, $match)
	{
		// get the link attributes
		$attributes = $this->helpers->get('link')->getLinkAttributeList($match['0']);

		if (!$this->helpers->get('pass')->passLinkChecks($attributes))
		{
			return;
		}

		$data       = array();
		$isexternal = $this->helpers->get('file')->isExternal($attributes->href);
		$ismedia    = $this->helpers->get('file')->isMedia($attributes->href);
		$iframe     = $this->helpers->get('file')->isIframe($attributes->href, $data);

		// Force/overrule certain data values
		if ($iframe || ($isexternal && !$ismedia))
		{
			// use iframe mode for external urls
			$data['iframe'] = 'true';
			$this->helpers->get('data')->setDataWidthHeight($data, $isexternal);
		}

		$attributes->class = !empty($attributes->class) ? $attributes->class . ' ' . $this->params->class : $this->params->class;
		$link              = $this->helpers->get('link')->buildLink($attributes, $data);

		$this->replaceOnce($match['0'], $link, $string);
	}


	private function replaceOnce($search, $replace, &$string, $extra = '')
	{
		if (!$extra
			|| !preg_match('#' . preg_quote($search, '#') . '(?P<post>.*?</(?:div|p)>)#', $string, $match)
		)
		{
			$string = RLText::strReplaceOnce($search, $replace . $extra, $string);

			return;
		}

		// Place the extra div stuff behind the first ending div/p tag
		$string = RLText::strReplaceOnce(
			$match['0'],
			$replace . $match['post'] . $extra,
			$string
		);
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
