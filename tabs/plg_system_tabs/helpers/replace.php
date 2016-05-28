<?php
/**
 * @package         Tabs
 * @version         6.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemTabsHelperReplace
{
	var $helpers = array();
	var $params  = null;
	var $context = '';

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemTabsHelpers::getInstance();
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
		if (!$this->params->alignment)
		{
			$this->params->alignment = JFactory::getLanguage()->isRTL() ? 'right' : 'left';
		}
		$this->params->alignment = 'align_' . $this->params->alignment;

		$positioning = 'top';
		$this->params->positioning = $positioning;

		$this->mainclass = trim('rl_tabs nn_tabs ' . $this->params->mainclass);

		$this->params->use_responsive_view = false;
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
			// Links with #tab-name or &tab=tab-name
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

		// Tag syntax: {tab ...}
		$this->replaceSyntax($string, $sets);

		// Closing tag: {/tab}
		$this->replaceClosingTag($string);

		// Links with #tab-name or &tab=tab-name
		$this->replaceLinks($string);

		// Link tag {tablink ...}
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

				$title = isset($item->title) ? trim($item->title) : 'Tab';

				$id    = RLText::cleanTitle($title, true);
				$title = preg_replace('#<\?h[0-9](\s[^>]* )?>#', '', $title);

				$replace = '<' . $this->params->title_tag . ' class="rl_tabs-title nn_tabs-title">'
					. '<a id="anchor-' . $id . '" class="anchor"></a>'
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
		$urlitem   = JFactory::getApplication()->input->get('tab');
		$itemcount = 0;

		foreach ($sets as $set_id => $items)
		{
			$opened_by_default = 0;

			foreach ($items as $i => $item)
			{
				$item->title      = isset($item->title) ? trim($item->title) : 'Tab';
				$item->title_full = $item->title;

				if (isset($item->{'title-opened'}) || isset($item->{'title-closed'}))
				{
					$title_closed = isset($item->{'title-closed'}) ? $item->{'title-closed'} : $item->title;
					$title_opened = isset($item->{'title-opened'}) ? $item->{'title-opened'} : $item->title;

					// Set main title to the title-opened, otherwise to title-closed
					$item->title = $title_opened ?: ($title_closed ?: $item->title);

					// place the title-opened and title-closed in css controlled spans
					$item->title_full = '<span class="rl_tabs-title-inactive nn_tabs-title-inactive">' . $title_closed . '</span>'
						. '<span class="rl_tabs-title-active nn_tabs-title-active">' . $title_opened . '</span>';
				}

				$item->haslink = preg_match('#<a [^>]*>.*?</a>#usi', $item->title);

				$item->title = RLText::cleanTitle($item->title, true);
				$item->title = $item->title ?: RLText::getAttribute('title', $item->title_full);
				$item->title = $item->title ?: RLText::getAttribute('alt', $item->title_full);

				$item->alias = RLText::createAlias(isset($item->alias) ? $item->alias : $item->title);
				$item->alias = $item->alias ?: 'tab';

				$item->id    = $this->createId($item->alias);
				$item->set   = (int) $set_id;
				$item->count = $i + 1;


				$set_keys = array(
					'class', 'open', 'title_tag', 'onclick',
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

	private function setOpenItem(&$items, $opened_by_default = 0)
	{
		$opened_by_default = (int) $opened_by_default;

		while (isset($items[$opened_by_default]) && $items[$opened_by_default]->haslink)
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

		RLTags::protectSpecialChars($string);

		$is_old = (strpos($string, '|') !== false);

		if ($is_old)
		{
			// Fix some different old syntaxes
			$string = str_replace(
				array(
					'|alias:',
					'|align_',
				),
				array(
					'|alias=',
					'|align=',
				),
				$string
			);
		}

		RLTags::unprotectSpecialChars($string);

		$known_boolean_keys = array(
			'open', 'active', 'opened', 'default',
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
			'access'       => array('accesslevels', 'accesslevel'),
			'usergroup'    => array('usergroups'),
			'position'     => array('positioning'),
			'align'        => array('alignment'),
		);

		RLTags::replaceKeyAliases($values, $key_aliases);

		if ($is_old)
		{
			$this->setPositionFromOldClasses($values);
		}

		return $values;
	}

	private function setPositionFromOldClasses(&$values)
	{
		if (empty($values->class) || !empty($values->position))
		{
			return;
		}

		$classes   = explode(' ', $values->class);
		$positions = array('top', 'bottom', 'left', 'right');
		$found     = array_intersect($classes, $positions);

		if (empty($found))
		{
			return;
		}

		$position = array_shift($found);

		$classes = array_diff($classes, array($position));

		$values->class    = implode(' ', $classes);
		$values->position = $position;
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

		if (!in_array($this->context, array('com_search.search', 'com_finder.indexer')))
		{
			$html[] = $this->getPreHtml($item, $items, $first);
		}

		$class = $this->getItemClass($item, 'tab-pane rl_tabs-pane nn_tabs-pane');

		$html[] = '<div class="' . trim($class) . '" id="' . $item->id . '"'
			. ' role="tabpanel" aria-labelledby="tab-' . $item->id . '" aria-hidden="' . ($item->open ? 'false' : 'true') . '">';

		if (!$item->haslink)
		{
			$class = 'anchor';
			$html[] = '<' . $this->params->title_tag . ' class="rl_tabs-title nn_tabs-title">'
				. '<a id="anchor-' . $item->id . '" class="' . $class . '"></a>'
				. $item->title . '</' . $item->title_tag . '>';
		}

		$html   = implode("\n", $html);
		$string = RLText::strReplaceOnce($match['0'], $html, $string);
	}

	private function getPreHtml($item, $items, $first = 0)
	{
		if (!$first)
		{
			return '</div>';
		}

		$class = $this->getMainClasses($item);


		$html[] = '<div class="' . trim($class) . '">';
		$html[] = $this->getNav($items);
		$html[] = '<div class="tab-content">';

		return implode("\n", $html);
	}

	private function getMainClasses($item)
	{
		$classes = array($this->mainclass);

		if (!empty($item->mainclass))
		{
			$classes[] = $item->mainclass;
		}

		if (!empty($item->nooutline))
		{
			$item->outline_handles = false;
			$item->outline_content = false;
		}

		if (!empty($item->outline_handles) || !empty($item->outline_content))
		{
			$item->nooutline = false;
		}

		$settings = array(
			'nooutline',
			'outline_handles',
			'outline_content',
			'color_inactive_handles',
		);
		$this->addClassesBySettings($item, $classes, $settings);

		$align = isset($item->align) ? 'align_' . $item->align : $this->params->alignment;
		$position = 'top';

		$classes[] = $position;
		$classes[] = $align;

		$classes = array_diff($classes, array(''));

		return trim(implode(' ', $classes));
	}

	private function getItemClass($item, $mainclass = 'rl_tabs-tab nn_tabs-tab')
	{
		$class = array($mainclass);

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

	private function addClassesBySettings($item, &$classes, $settings = '')
	{
		foreach ($settings as $setting)
		{
			$this->addClassBySetting($item, $classes, $setting);
		}
	}

	private function addClassBySetting($item, &$classes, $setting = '')
	{
		if (
			(empty($item->{$setting}) && empty($this->params->{$setting}))
			|| (isset($item->{$setting}) && !$item->{$setting})
		)
		{
			return;
		}

		$classes[] = $setting;
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
			$html = '</div></div></div>';


			list($pre, $post) = RLTags::cleanSurroundingTags(array($match['pre'], $match['post']));

			$html = $pre . $html . $post;

			$string = RLText::strReplaceOnce($match['0'], $html, $string);
		}
	}

	private function replaceLinks(&$string)
	{
		// Links with #tab-name
		$this->replaceAnchorLinks($string);
		// Links with &tab=tab-name
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
				|| strpos($link, 'rl_tabs-toggle-sm') !== false
				|| strpos($link, 'rl_tabs-link') !== false
				|| strpos($link, 'rl_sliders-link') !== false
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

			$html = '<a ' . $attribs . '><span class="rl_tabs-link-inner nn_tabs-link-inner">' . $match['text'] . '</span></a>';

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
			. '<span class="rl_tabs-link-inner nn_tabs-link-inner">' . $match['text'] . '</span>'
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
		. ' class="rl_tabs-link rl_tabs-link-' . $id . ' nn_tabs-link nn_tabs-link-' . $id . '"'
		. ' data-id="' . $id . '"';
	}

	private function stringHasItem(&$string, $id)
	{
		return (strpos($string, 'data-toggle="tab" data-id="' . $id . '"') !== false);
	}

	private function getNav(&$items)
	{
		$html = array();

		$ul_extra = '';

		// Nav for non-mobile view
		$html[] = '<a id="rl_tabs-scrollto_' . $items['0']->set . '" class="anchor rl_tabs-scroll nn_tabs-scroll"></a>';
		$html[] = '<ul class="nav nav-tabs" id="set-rl_tabs-' . $items['0']->set . '" role="tablist"' . $ul_extra . '>';
		foreach ($items as $item)
		{
			$html[] = '<li class="' . $this->getItemClass($item) . '"'
				. ' role="presentation">';

			if ($item->haslink)
			{
				$html[] = $item->title_full;
				$html[] = '</li>';
				continue;
			}

			$class = 'rl_tabs-toggle nn_tabs-toggle';

			$onclick = '';

			$html[] = '<a href="#' . $item->id . '" class="' . $class . '"' . $onclick
				. ' id="tab-' . $item->id . '"'
				. ' data-toggle="tab" data-id="' . $item->id . '"'
				. ' role="tab" aria-controls="' . $item->id . '" aria-selected="' . ($item->open ? 'true' : 'false') . '"'
				. '>'
				. '<span class="rl_tabs-toggle-inner nn_tabs-toggle-inner">'
				. $item->title_full
				. '</span></a>';
			$html[] = '</li>';
		}
		$html[] = '</ul>';

		return implode("\n", $html);
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
