<?php
/**
 * @package         Sliders
 * @version         6.0.2
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemSlidersHelperReplace
{
	var $helpers = array();
	var $params  = null;
	var $context = '';

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemSlidersHelpers::getInstance();
		$this->params  = $this->helpers->getParams();

		// Tag character start and end
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		// Break/paragraph start and end tags
		$this->params->breaks_start = RLTags::getRegexSurroundingTagPre(array('div', 'p', 'span', 'h[0-6]'));
		$this->params->breaks_end   = RLTags::getRegexSurroundingTagPost(array('div', 'p', 'span', 'h[0-6]'));
		$breaks_start               = $this->params->breaks_start;
		$breaks_end                 = $this->params->breaks_end;
		$inside_tag                 = RLTags::getRegexInsideTag();

		$this->params->tag_delimiter = ($this->params->tag_delimiter == 'space') ? RLTags::getRegexSpaces() : '=';
		$delimiter                   = $this->params->tag_delimiter;
		$sub_id                      = '(?:-[a-zA-Z0-9-_]+)?';

		$this->params->regex = '#'
			. '(?P<pre>' . $breaks_start . ')'
			. $tag_start . '(?P<tag>'
			. $this->params->tag_open . 's?' . '(?P<setid>' . $sub_id . ')' . $delimiter . '(?P<data>' . $inside_tag . ')'
			. '|/' . $this->params->tag_close . $sub_id
			. ')' . $tag_end
			. '(?P<post>' . $breaks_end . ')'
			. '#s';

		$this->params->regex_end = '#'
			. '(?P<pre>' . $breaks_start . ')'
			. $tag_start . '/' . $this->params->tag_close . $sub_id . $tag_end
			. '(?P<post>' . $breaks_end . ')'
			. '#s';

		$this->params->regex_link = '#'
			. $tag_start . $this->params->tag_link . $sub_id . $delimiter . '(?P<id>' . $inside_tag . ')' . $tag_end
			. '(?P<text>.*?)'
			. $tag_start . '/' . $this->params->tag_link . $tag_end
			. '#s';

		$this->ids      = array();
		$this->matches  = array();
		$this->allitems = array();
		$this->setcount = 0;

		$this->setMainParameters();
	}

	private function setMainParameters()
	{
		$this->mainclass = trim('rl_sliders nn_sliders accordion panel-group ' . $this->params->mainclass);
	}

	public function replaceTags(&$string, $area = 'article', $context = '')
	{
		if (!is_string($string) || $string == '')
		{
			return;
		}

		$this->context = $context;

		// Check if tags are in the text snippet used for the search component
		if (strpos($context, 'com_search.') === 0)
		{
			$limit = explode('.', $context, 2);
			$limit = (int) array_pop($limit);

			$string_check = substr($string, 0, $limit);

			if (
				strpos($string_check, $this->params->tag_character_start . $this->params->tag_open) === false
				&& strpos($string_check, $this->params->tag_character_start . $this->params->tag_link) === false
			)
			{
				return;
			}
		}

		// allow in component?
		if (RLProtect::isRestrictedComponent(isset($this->params->disabled_components) ? $this->params->disabled_components : array(), $area))
		{

			$this->helpers->get('protect')->protect($string);

			$this->handlePrintPage($string);

			RLProtect::unprotect($string);

			return;
		}

		if (
			strpos($string, $this->params->tag_character_start . $this->params->tag_open) === false
			&& strpos($string, $this->params->tag_character_start . $this->params->tag_link) === false
		)
		{
			// Links with #slider-name or &slider=slider-name
			$this->replaceLinks($string);

			return;
		}

		$this->helpers->get('protect')->protect($string);

		list($pre_string, $string, $post_string) = RLText::getContentContainingSearches(
			$string,
			array(
				$this->params->tag_character_start . $this->params->tag_open,
				$this->params->tag_character_start . $this->params->tag_link,
			),
			array(
				$this->params->tag_character_start . '/' . $this->params->tag_close . $this->params->tag_character_end,
				$this->params->tag_character_start . '/' . $this->params->tag_link . $this->params->tag_character_end,
			)
		);

		if (JFactory::getApplication()->input->getInt('print', 0))
		{
			// Replace syntax with general html on print pages
			$this->handlePrintPage($string);

			$string = $pre_string . $string . $post_string;

			RLProtect::unprotect($string);

			return;
		}

		$sets = $this->getSets($string);
		$this->initSets($sets);

		// Tag syntax: {slider ...}
		$this->replaceSyntax($string, $sets);

		// Closing tag: {/slider}
		$this->replaceClosingTag($string);

		// Links with #slider-name or &slider=slider-name
		$this->replaceLinks($string);

		// Link tag {sliderlink ...}
		$this->replaceLinkTag($string);

		$string = $pre_string . $string . $post_string;

		RLProtect::unprotect($string);
	}

	private function handlePrintPage(&$string)
	{
		if (substr($this->params->regex, -1) != 'u' && @preg_match($this->params->regex . 'u', $string))
		{
			$this->params->regex .= 'u';
		}

		preg_match_all($this->params->regex, $string, $matches, PREG_SET_ORDER);

		if (!empty($matches))
		{
			foreach ($matches as $match)
			{
				$tag = RLText::cleanTitle($match['data'], false, false);
				$this->setTagValues($item, $tag);

				$title = isset($item->title) ? trim($item->title) : 'Slider';

				$title = preg_replace('#<\?h[0-9](\s[^>]* )?>#', '', $title);

				$replace = '<' . $this->params->title_tag . ' class="rl_sliders-title nn_sliders-title">'
					. $title
					. '</' . $this->params->title_tag . '>';
				$string  = str_replace($match['0'], $replace, $string);
			}
		}

		preg_match_all($this->params->regex_end, $string, $matches, PREG_SET_ORDER);

		if (!empty($matches))
		{
			foreach ($matches as $match)
			{
				$string = str_replace($match['0'], '', $string);
			}
		}

		if (substr($this->params->regex_link, -1) != 'u' && @preg_match($this->params->regex_link . 'u', $string))
		{
			$this->params->regex_link .= 'u';
		}

		preg_match_all($this->params->regex_link, $string, $matches, PREG_SET_ORDER);

		if (!empty($matches))
		{
			foreach ($matches as $match)
			{
				$href   = RLText::getURI($match['id']);
				$link   = '<a href="' . $href . '">' . $match['text'] . '</a>';
				$string = str_replace($match['0'], $link, $string);
			}
		}
	}

	public function getSets(&$string, $only_basic_details = false)
	{
		if (substr($this->params->regex, -1) != 'u' && @preg_match($this->params->regex . 'u', $string))
		{
			$this->params->regex .= 'u';
		}

		preg_match_all($this->params->regex, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return array();
		}

		$sets   = array();
		$setids = array();


		foreach ($matches as $match)
		{
			if (substr($match['tag'], 0, 1) == '/')
			{

				array_pop($setids);
				continue;
			}

			end($setids);

			$item = new stdClass;

			// Set the values from the tag
			$tag = RLText::cleanTitle($match['data'], false, false);
			$this->setTagValues($item, $tag);

			if ($only_basic_details)
			{
				if (!isset($sets['basic']))
				{
					$sets['basic'] = array();
				}

				$sets['basic'][] = $item;
				continue;
			}

			$item->orig  = $match['0'];
			$item->setid = trim(str_replace('-', '_', $match['setid']));

			if (empty($setids) || current($setids) != $item->setid)
			{
				$this->setcount++;
				$setids[$this->setcount . '.'] = $item->setid;
			}

			$item->set = str_replace('__', '_', array_search($item->setid, array_reverse($setids)) . $item->setid);

			if (!isset($sets[$item->set]))
			{
				$sets[$item->set] = array();
			}

			list($item->pre, $item->post) = RLTags::cleanSurroundingTags(
				array($match['pre'], $match['post']),
				array('div', 'p', 'span', 'h[0-6]')
			);


			$sets[$item->set][] = $item;
		}


		return $sets;
	}

	private function getParent(&$sets, $item, $prev_item, $setid, $prev_setid)
	{
		if (!$prev_item)
		{
			return '';
		}

		if (count($sets[$item->set]))
		{
			$last_item = end($sets[$item->set]);
			reset($sets[$item->set]);

			return $last_item->parent;
		}

		if ($prev_setid != $setid)
		{
			$sets[$prev_item->set][$prev_item->id]->children[] = $item->set;

			return $prev_item->set . $prev_item->id;
		}

		return '';
	}


	private function initSets(&$sets)
	{
		$urlitem   = JFactory::getApplication()->input->get('slider');
		$itemcount = 0;

		foreach ($sets as $set_id => $items)
		{
			$opened_by_default = '';

			foreach ($items as $i => $item)
			{
				$item->title      = isset($item->title) ? trim($item->title) : 'Slider';
				$item->title_full = $item->title;

				if (isset($item->{'title-opened'}) || isset($item->{'title-closed'}))
				{
					$title_closed = isset($item->{'title-closed'}) ? $item->{'title-closed'} : $item->title;
					$title_opened = isset($item->{'title-opened'}) ? $item->{'title-opened'} : $item->title;

					// Set main title to the title-opened, otherwise to title-closed
					$item->title = $title_opened ?: ($title_closed ?: $item->title);

					// place the title-opened and title-closed in css controlled spans
					$item->title_full = '<span class="rl_sliders-title-inactive nn_sliders-title-inactive">' . $title_closed . '</span>'
						. '<span class="rl_sliders-title-active nn_sliders-title-active">' . $title_opened . '</span>';
				}

				$item->haslink = preg_match('#<a [^>]*>.*?</a>#usi', $item->title);

				$item->title = RLText::cleanTitle($item->title, true);
				$item->title = $item->title ?: RLText::getAttribute('title', $item->title_full);
				$item->title = $item->title ?: RLText::getAttribute('alt', $item->title_full);

				$item->alias = RLText::createAlias(isset($item->alias) ? $item->alias : $item->title);
				$item->alias = $item->alias ?: 'slider';

				$item->id    = $this->createId($item->alias);
				$item->set   = (int) $set_id;
				$item->count = $i + 1;


				$set_keys = array(
					'class', 'title_tag', 'onclick',
				);
				foreach ($set_keys as $key)
				{
					$item->{$key} = isset($item->{$key})
						? $item->{$key}
						: (isset($this->params->{$key}) ? $this->params->{$key} : '');
				}

				$item->matches   = RLText::createUrlMatches(array($item->id, $item->title));
				$item->matches[] = ++$itemcount . '';
				$item->matches[] = $item->set . '.' . ($i + 1);
				$item->matches[] = $item->set . '-' . ($i + 1);

				$item->matches = array_unique($item->matches);
				$item->matches = array_diff($item->matches, $this->matches);
				$this->matches = array_merge($this->matches, $item->matches);

				if ($this->itemIsOpen($item, $urlitem, $i == 0))
				{
					$opened_by_default = $i;
				}

				// Will be set after all items are checked based on the $opened_by_default id
				$item->open = false;

				$sets[$set_id][$i] = $item;
				$this->allitems[]  = $item;
			}

			$this->setOpenItem($sets[$set_id], $opened_by_default);
		}
	}

	private function itemIsOpen($item, $urlitem, $is_first = false)
	{

		if ($item->haslink)
		{
			return false;
		}

		if (!empty($item->close))
		{
			return false;
		}

		if (isset($item->open))
		{
			return $item->open;
		}

		if ($urlitem && in_array($urlitem, $item->matches))
		{
			return true;
		}

		if ($is_first)
		{
			return true;
		}

		return false;
	}

	private function setOpenItem(&$items, $opened_by_default = '')
	{
		if ($opened_by_default === '')
		{
			return;
		}

		$opened_by_default = (int) $opened_by_default;

		while ($items[$opened_by_default]->haslink)
		{
			$opened_by_default++;
		}

		if (!isset($items[$opened_by_default]))
		{
			return;
		}

		$items[$opened_by_default]->open = true;
	}

	private function setTagValues(&$item, $string)
	{
		$values = $this->getTagValues($string);

		$item = (object) array_merge((array) $item, (array) $values);
	}

	private function getTagValues($string)
	{
		// Fix some different old syntaxes
		$string = str_replace(
			array(
				'|alias:',
			),
			array(
				'|alias=',
			),
			$string
		);

		$known_boolean_keys = array(
			'open', 'active', 'opened', 'default',
			'close', 'inactive', 'closed',
			'scroll', 'noscroll',
			'nooutline', 'outline_handles', 'outline_content', 'color_inactive_handles',
		);

		// Get the values from the tag
		$values = RLTags::getValuesFromString($string, 'title', $known_boolean_keys);

		$key_aliases = array(
			'title'        => array('name'),
			'title-opened' => array('title-open', 'title-active'),
			'title-closed' => array('title-close', 'title-inactive'),
			'open'         => array('active', 'opened', 'default'),
			'close'        => array('inactive', 'closed'),
			'access'       => array('accesslevels', 'accesslevel'),
			'usergroup'    => array('usergroups'),
		);

		RLTags::replaceKeyAliases($values, $key_aliases);

		if (!empty($values->close))
		{
			$values->open = false;
			unset($values->close);
		}

		return $values;
	}

	private function replaceSyntax(&$string, $sets)
	{
		if (!preg_match($this->params->regex_end, $string))
		{
			return;
		}

		foreach ($sets as $items)
		{
			$this->replaceSyntaxItemList($string, $items);
		}
	}

	private function replaceSyntaxItemList(&$string, $items)
	{
		$first = key($items);
		end($items);

		foreach ($items as $i => &$item)
		{
			$this->replaceSyntaxItem($string, $item, $items, ($i == $first));
		}
	}

	private function replaceSyntaxItem(&$string, $item, $items, $first = 0)
	{
		$s = '#' . preg_quote($item->orig, '#') . '#';
		if (@preg_match($s . 'u', $string))
		{
			$s .= 'u';
		}

		if (!preg_match($s, $string, $match))
		{
			return;
		}

		$html   = array();
		$html[] = $item->post;
		$html[] = $item->pre;

		$html[] = $this->getPreHtml($items, $first);

		if (!in_array($this->context, array('com_search.search', 'com_finder.indexer')))
		{
			$html[] = '<div class="' . $this->getItemClass($item) . '">';
			$html[] = '<a id="rl_sliders-scrollto_' . $item->id . '" class="anchor rl_sliders-scroll nn_sliders-scroll"></a>';
			$html[] = '<div class="accordion-heading panel-heading">';
			$html[] = $this->getSliderTitle($item, $items);
			$html[] = '</div>';

			$body_class = 'accordion-body rl_sliders-body nn_sliders-body collapse';
			if ($item->open)
			{
				$body_class .= ' in';
			}

			$html[] = '<div class="' . $body_class . '" id="' . $item->id . '">';
			$html[] = '<div class="accordion-inner panel-body">';
		}

		$html[] = '<' . $item->title_tag . ' class="rl_sliders-title nn_sliders-title">'
			. preg_replace('#<\?h[0-9](\s[^>]* )?>#', '', $item->title_full)
			. '</' . $item->title_tag . '>';

		$html = implode("\n", $html);

		$string = RLText::strReplaceOnce($match['0'], $html, $string);
	}

	private function getItemClass($item)
	{
		$class = array('accordion-group panel rl_sliders-group nn_sliders-group');

		if ($item->open)
		{
			$class[] = 'active';
		}

		if (!empty($item->mode))
		{
			$class[] = $item->mode == 'hover' ? 'hover' : 'click';
		}

		$class[] = trim($item->class);

		return trim(implode(' ', $class));
	}

	private function getPreHtml($items, $first = 0)
	{
		if (!$first)
		{
			return '</div></div></div>';
		}

		$class = $this->mainclass;

		if (!empty($items['0']->mainclass))
		{
			$class .= ' ' . $items['0']->mainclass;
		}

		return '<div class="' . $class . '" id="set-rl_sliders-' . $items['0']->set . '">'
		. '<a id="rl_sliders-scrollto_' . $items['0']->set . '" class="anchor rl_sliders-scroll nn_sliders-scroll"></a>';
	}

	private function getSliderTitle($item, $items)
	{
		if ($item->haslink)
		{
			return $item->title_full;
		}

		$class = 'accordion-toggle rl_sliders-toggle nn_sliders-toggle';

		$onclick = '';

		$html   = array();
		$html[] = '<a href="' . RLText::getURI($item->id) . '" class="' . $class . '"' . $onclick
			. ' data-toggle="collapse" data-id="' . $item->id . '" data-parent="#set-rl_sliders-' . $items['0']->set . '"'
			. '><span class="rl_sliders-toggle-inner nn_sliders-toggle-inner">';
		$html[] = $item->title_full;
		$html[] = '</span></a>';

		return implode("\n", $html);
	}

	private function replaceClosingTag(&$string)
	{
		preg_match_all($this->params->regex_end, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$html = '</div></div></div></div>';

			list($pre, $post) = RLTags::cleanSurroundingTags(array($match['pre'], $match['post']));
			$html = $pre . $html . $post;

			$string = RLText::strReplaceOnce($match['0'], $html, $string);
		}
	}

	private function replaceLinks(&$string)
	{
		// Links with #slider-name
		$this->replaceAnchorLinks($string);
		// Links with &slider=slider-name
		$this->replaceUrlLinks($string);
	}

	private function replaceAnchorLinks(&$string)
	{
		preg_match_all(
			'#(?P<link><a\s[^>]*href="(?P<url>([^"]*)?)\#(?P<id>[^"]*)"[^>]*>)(?P<text>.*?)</a>#si',
			$string,
			$matches,
			PREG_SET_ORDER
		);

		if (empty($matches))
		{
			return;
		}

		$this->replaceLinksMatches($string, $matches);
	}

	private function replaceUrlLinks(&$string)
	{
		preg_match_all(
			'#(?P<link><a\s[^>]*href="(?P<url>[^"]*)(?:\?|&(?:amp;)?)tab=(?P<id>[^"\#&]*)(?:\#[^"]*)?"[^>]*>)(?P<text>.*?)</a>#si',
			$string,
			$matches,
			PREG_SET_ORDER
		);

		if (empty($matches))
		{
			return;
		}

		$this->replaceLinksMatches($string, $matches);
	}

	private function replaceLinksMatches(&$string, $matches)
	{
		$uri            = JUri::getInstance();
		$current_urls   = array();
		$current_urls[] = $uri->toString(array('path'));
		$current_urls[] = $uri->toString(array('scheme', 'host', 'path'));
		$current_urls[] = $uri->toString(array('scheme', 'host', 'port', 'path'));

		foreach ($matches as $match)
		{
			$link = $match['link'];

			if (
				strpos($link, 'data-toggle=') !== false
				|| strpos($link, 'onclick=') !== false
				|| strpos($link, 'rl_sliders-link') !== false
				|| strpos($link, 'rl_tabs-toggle-sm') !== false
				|| strpos($link, 'rl_tabs-link') !== false
			)
			{
				continue;
			}

			$url = $match['url'];
			if (strpos($url, 'index.php/') === 0)
			{
				$url = '/' . $url;
			}

			if (strpos($url, 'index.php') === 0)
			{
				$url = JRoute::_($url);
			}

			if ($url != '' && !in_array($url, $current_urls))
			{
				continue;
			}

			$id = $match['id'];

			if (!$this->stringHasItem($string, $id))
			{
				// This is a link to a normal anchor or other element on the page
				// Remove the prepending obsolete url and leave the hash
				// $string = str_replace('href="' . $match['url'] . '#' . $id . '"', 'href="#' . $id . '"', $string);

				continue;
			}

			$attribs = $this->getLinkAttributes($id);

			// Combine attributes with original
			$attribs = RLText::combineAttributes($link, $attribs);

			$html = '<a ' . $attribs . '><span class="rl_sliders-link-inner nn_sliders-link-inner">' . $match['text'] . '</span></a>';

			$string = str_replace($match['0'], $html, $string);
		}
	}

	private function replaceLinkTag(&$string)
	{
		if (substr($this->params->regex_link, -1) != 'u' && @preg_match($this->params->regex_link . 'u', $string))
		{
			$this->params->regex_link .= 'u';
		}

		preg_match_all($this->params->regex_link, $string, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$this->replaceLinkTagMatch($string, $match);
		}
	}

	private function replaceLinkTagMatch(&$string, $match)
	{
		$id = RLText::createAlias($match['id']);

		if (!$this->stringHasItem($string, $id))
		{
			$id = $this->findItemByMatch($match['id']);
		}

		if (!$this->stringHasItem($string, $id))
		{
			$html = '<a href="' . RLText::getURI($id) . '">' . $match['text'] . '</a>';

			$string = RLText::strReplaceOnce($match['0'], $html, $string);

			return;
		}

		$html = '<a ' . $this->getLinkAttributes($id) . '>'
			. '<span class="rl_sliders-link-inner nn_sliders-link-inner">' . $match['text'] . '</span>'
			. '</a>';

		$string = RLText::strReplaceOnce($match['0'], $html, $string);
	}

	private function findItemByMatch($id)
	{
		foreach ($this->allitems as $item)
		{
			if (!in_array($id, $item->matches))
			{
				continue;
			}

			return $item->id;
		}

		return $id;
	}

	private function getLinkAttributes($id)
	{
		return 'href="' . RLText::getURI($id) . '"'
		. ' class="rl_sliders-link rl_sliders-link-' . $id . ' nn_sliders-link nn_sliders-link-' . $id . '"'
		. ' data-id="' . $id . '"';
	}

	private function stringHasItem(&$string, $id)
	{
		return (strpos($string, 'data-toggle="collapse" data-id="' . $id . '"') !== false);
	}

	private function createId($alias)
	{
		$id = $alias;

		$i = 1;
		while (in_array($id, $this->ids))
		{
			$id = $alias . '-' . ++$i;
		}

		$this->ids[] = $id;

		return $id;
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
