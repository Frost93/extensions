<?php
/**
 * @package         Articles Anywhere
 * @version         5.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemArticlesAnywhereHelperItems
{
	var     $helpers              = array();
	var     $params               = null;
	private $aid                  = null;
	private $data                 = null;
	private $content_items        = array();
	private $content_items_to_ids = array();
	private $current_article      = null;
	private $message              = null;

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemArticlesAnywhereHelpers::getInstance();
		$this->params  = $this->helpers->getParams();

		$this->aid = JFactory::getUser()->getAuthorisedViewLevels();
	}

	public function _($matches, $message)
	{
		$this->message = $message;

		$items = array();
		foreach ($matches as $match)
		{
			$item = $this->getItem($match);

			$items[] = $item;
		}

		$this->setContentItemData($items);

		return $items;
	}

	private function setContentItemData(&$items)
	{
		$this->storeSingleContentItems($items);

		$this->setContentItems($items);
	}

	private function setContentItems(&$items)
	{
		foreach ($items as $item)
		{
			$this->data = $item;

			$output = array();

			foreach ($item->sets as &$set)
			{
				$output = array_merge($output, $this->getHtmlOutput($set));
			}

			$item->output = $output;
		}

		return $items;
	}

	private function getHtmlOutput()
	{
		if (empty($this->data->sets))
		{
			return '';
		}

		if ($this->message != '')
		{
			if ($this->params->place_comments)
			{
				return $this->params->comment_start . $this->params->message_start
				. $this->message
				. $this->params->message_end . $this->params->comment_end;
			}

			return '';
		}

		$output = array();

		foreach ($this->data->sets as $item)
		{
			$content_items = $this->getContentItemsBySet($item);

			if (empty($content_items))
			{
				continue;
			}

			foreach ($content_items as $content_item)
			{
				if (empty($content_item))
				{
					continue;
				}

				$html = $this->getOutput($item, $content_item);

				list($start_div, $end_div) = $this->getDivTags();

				// Don't add surrounding divs if there is nothing to output (not only comments)
				if (preg_match('#^\s*(<\!--[^>]*-->)*\s*$#', $html))
				{
					$start_div = array('pre' => '', 'post' => '');
					$end_div   = array('pre' => '', 'post' => '');
				}

				$html =
					$this->data->start_post
					. $start_div['pre']
					. $start_div['post']
					. $this->data->pre

					. $html

					. $this->data->post
					. $end_div['pre']
					. $end_div['post']
					. $this->data->end_pre;

				$output[] = $html;
			}
		}

		return $output;
	}

	private function getOutput($item, $content_item, $firstpass = false)
	{
		list($tag_start, $tag_end) = $this->getDataTagCharacters(true);

		if ($firstpass)
		{
			// first pass: search for normal tags and tags around tags
			$regex  = '#' . $tag_start . '(/?[a-z0-9][^' . $tag_end . ']*?|/?[a-z0-9](?:[^' . $tag_start . ']*?' . $tag_start . '[^' . $tag_end . ']*?' . $tag_end . ')*[^' . $tag_end . ']*?)' . $tag_end . '#si';
			$output = $this->data->content;
		}
		else
		{
			// do second pass
			$output = $this->getOutput($item, $content_item, true);

			$regex_close = '#' . $tag_start . '/' . $this->helpers->_('replace')->params->tags . $tag_end . '#si';
			if (preg_match($regex_close, $output))
			{
				return $output;
			}

			// second pass: only search for normal tags
			$regex = '#' . $tag_start . '(/?[a-z0-9][^' . $tag_end . ']*?)' . $tag_end . '#si';
		}

		preg_match_all($regex, $this->data->content, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return $this->data->content;
		}

		if (empty($content_item))
		{
			return '<!-- ' . JText::_('AA_ACCESS_TO_ARTICLE_DENIED') . ' -->';
		}

		$this->prepareContentItem($item, $content_item, $output);

		$helper = 'datatags';

		$this->helpers->_($helper)->handleIfStatements($output, $content_item);

		preg_match_all($regex, $output, $matches, PREG_SET_ORDER);

		if (empty($matches))
		{
			return $output;
		}

		$this->helpers->_($helper)->replaceTags($output, $matches, $content_item);

		return $output;
	}

	private function prepareContentItem($item, $content_item, $output)
	{
		self::addParams(
			$content_item,
			json_decode(
				isset($content_item->attribs)
					? $content_item->attribs
					: $content_item->params
			)
		);

		if (isset($content_item->images))
		{
			self::addParams($content_item, json_decode($content_item->images));
		}

		if (isset($content_item->urls))
		{
			self::addParams($content_item, json_decode($content_item->urls));
		}


		if (strpos($output, 'tag') !== false)
		{
			$method = 'addTags';
			self::$method($content_item);
		}
	}

	public function addTags(&$content_item)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('tags.title'))
			->from($db->quoteName('#__tags', 'tags'))
			->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'xref')
				. ' ON ' . $db->quoteName('xref.tag_id') . ' = ' . $db->quoteName('tags.id'))
			->where($db->quoteName('xref.content_item_id') . ' = ' . (int) $content_item->id)
			->where($db->quoteName('tags.published') . ' = 1');

		$db->setQuery($query);

		$content_item->tags = $db->loadColumn();
	}

	public function addTagsK2(&$content_item)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('t.name'))
			->from($db->quoteName('#__k2_tags', 'tags'))
			->join('LEFT', $db->quoteName('#__k2_tags_xref', 'xref')
				. ' ON ' . $db->quoteName('xref.tagID') . ' = ' . $db->quoteName('tags.id'))
			->where($db->quoteName('xref.itemID') . ' = ' . (int) $content_item->id)
			->where($db->quoteName('tags.published') . ' = 1');

		$db->setQuery($query);

		$content_item->tags = $db->loadColumn();
	}

	private function getContentItemsBySet($data)
	{
		if (!empty($data->current))
		{
			return array($this->getCurrentArticle($data));
		}


		if (empty($data->ids))
		{
			return;
		}

		return $this->getSingleContentItems($data);
	}

	private function storeSingleContentItems($items)
	{
		$database_ids = array();

		foreach ($items as $item)
		{
			foreach ($item->sets as $data)
			{
				if (empty($data->id) || !empty($data->current) || isset($data->featured) || !empty($data->is_category) || !empty($data->is_tag))
				{
					continue;
				}

				if (!isset($database_ids[$data->type]))
				{
					$database_ids[$data->type] = array();
				}

				$database_ids[$data->type][] = $data->id;
			}
		}

		$this->storeSingleContentItemsFromDatabase($database_ids);
	}

	private function storeSingleContentItemsFromDatabase($database_ids)
	{
		if (empty($database_ids))
		{
			return;
		}

		foreach ($database_ids as $type => $ids)
		{
			$table = 'content';

			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->select('CONCAT("' . $type . '_", ' . $db->quoteName('id') . ') AS type_id')
				->select($db->quoteName('access') . ' IN (' . implode(', ', $this->aid) . ') as has_access')
				->from($db->quoteName('#__' . $table));

			$conditions = array();

			$ids = array_unique($ids);
			foreach ($ids as $id)
			{
				if (isset($this->content_items_to_ids[$type . ' ' . $id]))
				{
					continue;
				}

				$where = $db->quoteName('title') . ' = ' . $db->quote(RLText::html_entity_decoder($id));
				$where .= ' OR ' . $db->quoteName('alias') . ' = ' . $db->quote(RLText::html_entity_decoder($id));

				if (is_numeric($id))
				{
					$where .= ' OR ' . $db->quoteName('id') . ' = ' . $id;
				}

				$conditions[] = $where;
			}

			if (empty($conditions))
			{
				continue;
			}

			$query->where('((' . implode(') OR (', $conditions) . '))');

			$temp = (object) array('type' => $type);
			$this->setQueryConditions($query, $temp);

			$db->setQuery($query);

			$content_items       = $db->loadObjectList('type_id');
			$this->content_items = array_merge($this->content_items, $content_items);

			foreach ($content_items as $type_id => $content_item)
			{
				$this->content_items_to_ids[$type . '_' . $content_item->alias] = $type . '_' . $content_item->id;
				$this->content_items_to_ids[$type . '_' . $content_item->title] = $type . '_' . $content_item->id;
			}
		}
	}

	public function getQueryIdLists($ids)
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		$db = JFactory::getDbo();

		$numeric      = array();
		$not_nummeric = array();
		$likes        = array();

		foreach ($ids as &$id)
		{
			if (is_numeric($id))
			{
				$numeric[] = $id;
				continue;
			}

			if (strpos($id, '*') !== false)
			{
				$likes[] = $db->quote(str_replace('*', '%', $id));
				continue;
			}
			$not_nummeric[] = $db->quote($id);
		}

		return array($numeric, $not_nummeric, $likes);
	}

	private function getCategoryIds($item)
	{
		list($ids, $titles, $likes) = $this->getQueryIdLists($item->categories);

		if (empty($titles) && empty($likes))
		{
			return $ids;
		}

		$table = 'content';
		$title = 'title';

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->clear()
			->select($db->quoteName('id'))
			->from($db->quoteName('#__' . $table));

		if ($table == 'content')
		{
			$query->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'));
		}

		if (!empty($titles))
		{
			$wheres[] = $db->quoteName($title) . ' IN (' . implode(',', $titles) . ')';
			$wheres[] = $db->quoteName('alias') . ' IN (' . implode(',', $titles) . ')';

			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		if (!empty($likes))
		{
			$wheres = array();
			foreach ($likes as $like)
			{
				$wheres[] = $db->quoteName($title) . ' LIKE ' . $like;
				$wheres[] = $db->quoteName('alias') . ' LIKE ' . $like;
			}
			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		$ignore_language = isset($item->ignore_language) ? $item->ignore_language : $this->params->ignore_language;
		$ignore_state    = isset($item->ignore_state) ? $item->ignore_state : $this->params->ignore_state;
		$ignore_access   = isset($item->ignore_access) ? $item->ignore_access : $this->params->ignore_access;

		if (!$ignore_language)
		{
			$query->where($db->quoteName('language') . ' IN (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}

		if (!$ignore_state)
		{
			$where = $db->quoteName('published') . ' = 1';

			$query->where($where);
		}

		if (!$ignore_access)
		{
			$query->where($db->quoteName('access') . ' IN (' . implode(', ', $this->aid) . ')');
		}

		$db->setQuery($query);

		return array_merge($ids, $db->loadColumn());
	}

	private function getCategoryItems($item)
	{
		if (empty($item->categories))
		{
			return false;
		}

		$cat_ids = $this->getCategoryIds($item);

		if (empty($cat_ids))
		{
			return false;
		}

		$limit  = isset($item->limit) ? $item->limit : ((int) $this->params->limit ? $this->params->limit : 1000);
		$offset = isset($item->offset) ? $item->offset : 0;

		$table = 'content';

		$db = JFactory::getDbo();

		$query = $this->getContentItemQuery($item);

		$query->where($db->quoteName('catid') . ' IN (' . implode(',', $cat_ids) . ')');

		if (!empty($item->is_tag))
		{
			$item_ids   = $this->getTagItemIds($item);
			$item_ids[] = 0;

			$query->where($db->quoteName('id') . ' IN (' . implode(',', $item_ids) . ')');
		}

		$db->setQuery($query, (int) $offset, (int) $limit);

		return $db->loadObjectList();
	}

	private function getTagIds($item)
	{
		list($ids, $titles, $likes) = $this->getQueryIdLists($item->tags);

		if (empty($titles) && empty($likes))
		{
			return $ids;
		}

		$table = 'content';
		$title = 'title';
		$alias = 'alias';

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->clear()
			->select($db->quoteName('id'))
			->from($db->quoteName('#__' . $table));

		if (!empty($titles))
		{
			$wheres[] = $db->quoteName($title) . ' IN (' . implode(',', $titles) . ')';
			$wheres[] = $db->quoteName($alias) . ' IN (' . implode(',', $titles) . ')';

			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		if (!empty($likes))
		{
			$wheres = array();
			foreach ($likes as $like)
			{
				$wheres[] = $db->quoteName($title) . ' LIKE ' . $like;
				$wheres[] = $db->quoteName($alias) . ' LIKE ' . $like;
			}
			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		$ignore_language = isset($item->ignore_language) ? $item->ignore_language : $this->params->ignore_language;
		$ignore_state    = isset($item->ignore_state) ? $item->ignore_state : $this->params->ignore_state;
		$ignore_access   = isset($item->ignore_access) ? $item->ignore_access : $this->params->ignore_access;

		if (!$ignore_language && $item->type != 'k2')
		{
			$query->where($db->quoteName('language') . ' IN (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}

		if (!$ignore_state)
		{
			$query->where($db->quoteName('published') . ' = 1');
		}

		if (!$ignore_access && $item->type != 'k2')
		{
			$query->where($db->quoteName('access') . ' IN (' . implode(', ', $this->aid) . ')');
		}

		$db->setQuery($query);

		return array_merge($ids, $db->loadColumn());
	}

	private function getTagItemIds($item, $is_category = false)
	{
		$tag_ids = $this->getTagIds($item);

		if (empty($tag_ids))
		{
			return array();
		}

		$db = JFactory::getDbo();

		$type  = $is_category ? 'com_content.category' : 'com_content.article';
		$query = $db->getQuery(true)
			->select($db->quoteName('content_item_id'))
			->from($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote($type))
			->where($db->quoteName('tag_id') . ' IN (' . implode(',', $tag_ids) . ')');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	private function getTagItems($item)
	{
		if (empty($item->tags))
		{
			return false;
		}

		$item_ids = $this->getTagItemIds($item);

		if (empty($item_ids))
		{
			return false;
		}

		$limit  = isset($item->limit) ? $item->limit : ((int) $this->params->limit ? $this->params->limit : 1000);
		$offset = isset($item->offset) ? $item->offset : 0;

		$table = 'content';

		$db = JFactory::getDbo();

		$query = $this->getContentItemQuery($item);

		$query->where($db->quoteName('id') . ' IN (' . implode(',', $item_ids) . ')');

		$db->setQuery($query, (int) $offset, (int) $limit);

		return $db->loadObjectList();
	}

	private function getFeaturedItems($item)
	{
		$db = JFactory::getDbo();

		$query = $this->getContentItemQuery($item);

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	private function getSingleContentItems($item)
	{
		$items = array();

		foreach ($item->ids as $id)
		{
			$items[] = $this->getSingleContentItem($item, $id);
		}

		return $items;
	}

	private function getSingleContentItem($item, $id)
	{
		if ($stored = $this->getSingleContentItemStored($item, $id))
		{
			return $stored;
		}

		$db = JFactory::getDbo();

		$query = $this->getContentItemQuery($item);

		list($ids, $titles, $likes) = $this->getQueryIdLists($id);

		$title = 'title';

		if (!empty($ids))
		{
			$wheres[] = $db->quoteName($title) . ' IN (' . implode(',', $ids) . ')';
			$wheres[] = $db->quoteName('alias') . ' IN (' . implode(',', $ids) . ')';
			$wheres[] = $db->quoteName('id') . ' IN (' . implode(',', $ids) . ')';

			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		if (!empty($titles))
		{
			$wheres[] = $db->quoteName($title) . ' IN (' . implode(',', $titles) . ')';
			$wheres[] = $db->quoteName('alias') . ' IN (' . implode(',', $titles) . ')';

			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		if (!empty($likes))
		{
			$wheres = array();
			foreach ($likes as $like)
			{
				$wheres[] = $db->quoteName($title) . ' LIKE ' . $like;
				$wheres[] = $db->quoteName('alias') . ' LIKE ' . $like;
			}
			$query->where('(' . implode(' OR ', $wheres) . ')');
		}

		$db->setQuery($query, 0, 1);

		return $db->loadObject();
	}

	private function getSingleContentItemStored($item, $id)
	{
		if (!$stored = $this->getSingleItemFromStoredItems($item, $id))
		{
			return false;
		}

		$featured        = isset($item->featured) ? $item->featured : false;
		$ignore_language = isset($item->ignore_language) ? $item->ignore_language : $this->params->ignore_language;
		$ignore_state    = isset($item->ignore_state) ? $item->ignore_state : $this->params->ignore_state;
		$ignore_access   = isset($item->ignore_access) ? $item->ignore_access : $this->params->ignore_access;


		if (!$ignore_language && !in_array($stored->language, array(JFactory::getLanguage()->getTag(), '*')))
		{
			return false;
		}

		if (!$ignore_state)
		{
			$state = 'state';

			if (!$stored->{$state})
			{
				return false;
			}

			$db       = JFactory::getDbo();
			$jnow     = JFactory::getDate();
			$now      = $jnow->toSql();
			$nullDate = $db->getNullDate();

			if (
				$stored->publish_up > $now || ($stored->publish_down != $nullDate && $stored->publish_down < $now)
			)
			{
				return false;
			}
		}

		if (!$ignore_access && !in_array($stored->access, $this->aid))
		{
			return false;
		}

		return $stored;
	}

	private function getSingleItemFromStoredItems($item, $id = 0)
	{
		$id = $id ?: $item->id;

		$type_id = $item->type . '_' . $id;

		if (isset($this->content_items[$id]))
		{
			return $this->content_items[$id];
		}

		if (isset($this->content_items_to_ids[$type_id]))
		{
			return $this->content_items[$this->content_items_to_ids[$type_id]];
		}

		return false;
	}

	private function getContentItemQuery($item)
	{
		$table = 'content';

		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->select($db->quoteName('access') . ' IN (' . implode(', ', $this->aid) . ') as has_access')
			->from($db->quoteName('#__' . $table));

		$this->setQueryConditions($query, $item);

		return $query;
	}

	private function setQueryConditions(&$query, $item)
	{
		$db = JFactory::getDbo();

		$ignore_language    = isset($item->ignore_language) ? $item->ignore_language : $this->params->ignore_language;
		$ignore_state       = isset($item->ignore_state) ? $item->ignore_state : $this->params->ignore_state;
		$ignore_access      = isset($item->ignore_access) ? $item->ignore_access : $this->params->ignore_access;
		$ordering           = isset($item->ordering) ? $item->ordering : $this->params->ordering;
		$ordering_direction = isset($item->ordering_direction) ? $item->ordering_direction : $this->params->ordering_direction;


		if (!$ignore_language)
		{
			$query->where($db->quoteName('language') . ' IN (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}

		if (!$ignore_state)
		{
			$jnow     = JFactory::getDate();
			$now      = $jnow->toSql();
			$nullDate = $db->getNullDate();

			$where = $db->quoteName('state') . ' = 1';

			$query->where($where)
				->where('( ' . $db->quoteName('publish_up') . ' <= ' . $db->quote($now) . ' )')
				->where('( ' . $db->quoteName('publish_down') . ' = ' . $db->quote($nullDate) . ' OR ' . $db->quoteName('publish_down') . ' >= ' . $db->quote($now) . ' )');
		}

		if (!$ignore_access)
		{
			$query->where($db->quoteName('access') . ' IN (' . implode(', ', $this->aid) . ')');
		}

		if ($ordering == 'random')
		{
			$query->order('RAND()');

			return;
		}

		if (strpos($ordering, ' ') !== false)
		{
			list($ordering, $ordering_direction) = explode(' ', $ordering, 2);
		}

		$query->order($db->quoteName($ordering) . ' ' . $ordering_direction);
	}

	private function getItem($data)
	{
		$sets = $this->getTagValues($data['id']);

		if (empty($sets))
		{
			return null;
		}

		return (object) array(
			'original_string' => $data['0'],
			'tag'             => $data['tag'],
			'content'         => $data['html'],
			'start_pre'       => $data['start_pre'],
			'end_pre'         => $data['end_pre'],
			'start_post'      => $data['start_post'],
			'end_post'        => $data['end_post'],
			'start_div'       => $data['start_div'],
			'end_div'         => $data['end_div'],
			'pre'             => $data['pre'],
			'post'            => $data['post'],
			'sets'            => $sets,
		);
	}

	private function getTagValues($string)
	{
		if (strpos($string, '="') == false)
		{
			$string = $this->convertTagToNewSyntax($string);
		}

		$parts = $string;

		$known_boolean_keys = array(
			'ignore_language', 'ignore_access', 'ignore_state',
		);

		list($tag_start, $tag_end) = $this->getDataTagCharacters();

		$sets = array();

		foreach ($parts as $string)
		{
			// Get the values from the tag
			$set = RLTags::getValuesFromString($string, 'id', $known_boolean_keys);

			$key_aliases = array(
				'id'                 => array('ids', 'article', 'articles', 'item', 'items', 'title', 'alias'),
			);

			RLTags::replaceKeyAliases($set, $key_aliases);

			$type = 'joomla';
			$set->type = $type;

			$set->ids = array();


			if (!isset($set->id))
			{
				$set->id = 'current';
			}

			$set->ids = array($set->id);
			foreach ($set->ids as $id)
			{
				if (in_array($id, array('current', 'self', $tag_start . 'id' . $tag_end, $tag_start . 'title' . $tag_end, $tag_start . 'alias' . $tag_end), true))
				{
					$set->current = true;
					$id           = '';
				}

				$set->id = $id;
			}

			$sets[] = clone $set;
		}

		return $sets;
	}

	private function getIdsFromString($ids)
	{
		RLTags::protectSpecialChars($ids);
		$ids = explode(',', $ids);

		foreach ($ids as &$id)
		{
			RLTags::unprotectSpecialChars($id);
		}

		return $ids;
	}

	private function getCurrentArticle($item)
	{
		if (isset($this->helpers->_('replace')->current_article->id))
		{
			return $this->helpers->_('replace')->current_article;
		}

		$id = $this->getCurrentArticleId($item);

		if (empty($id))
		{
			return;
		}

		$item->id = $id;

		return $this->getSingleContentItem($item);
	}

	private function getCurrentArticleId($item)
	{
		if (isset($this->current_article->link) && preg_match('#&(?:amp;)?id=([0-9]*)#', $this->current_article->link, $match))
		{
			return $match['1'];
		}

		if ($item->type == 'joomla' && $this->params->option == 'com_content' && JFactory::getApplication()->input->get('view') == 'article')
		{
			return JFactory::getApplication()->input->getInt('id', 0);
		}


		return 0;
	}

	private function convertTagToNewSyntax($string)
	{
		RLTags::protectSpecialChars($string);

		if (strpos($string, '|') == false && strpos($string, ':') == false)
		{
			RLTags::unprotectSpecialChars($string);

			return $string;
		}

		RLTags::protectSpecialChars($string);

		$sets = explode('|', $string);

		foreach ($sets as &$set)
		{
			if (strpos($set, ':') == false)
			{
				continue;
			}

			$parts = explode(':', $set);

			$attributes   = array();
			$attributes[] = 'id="' . array_pop($parts) . '"';

			foreach ($parts as $part)
			{
				switch (true)
				{
					case ($part == 'k2'):
						$attributes[] = 'k2="1"';
						break;

					case ($part == 'cat'):
						$attributes[] = 'is_category="1"';
						break;

					case ($part == 'tag'):
						$attributes[] = 'is_tag="1"';
						break;

					case (is_numeric($part)):
					case (preg_match('#^([0-9]+)-([0-9]+)$#', $part)):
						$attributes[] = 'limit="' . $part . '"';
						break;

					default:
						$attributes[] = 'ordering="' . trim($part) . '"';
						break;
				}
			}

			$set = implode(' ', $attributes);
		}

		$string = implode(' && ', $sets);

		return $string;
	}

	private function addParams(&$article, $params)
	{
		if (!$params
			|| (!is_object($params) && !is_array($params))
		)
		{
			return;
		}

		foreach ($params as $key => $val)
		{
			if (isset($article->{$key}))
			{
				continue;
			}

			$article->{$key} = $val;
		}
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

	public function getDataTagCharacters($quote = false)
	{
		if (!isset($this->params->tag_character_data_start))
		{
			list($this->params->tag_character_data_start, $this->params->tag_character_data_end) = explode('.', $this->params->tag_characters_data);
		}

		$start = $this->params->tag_character_data_start;
		$end   = $this->params->tag_character_data_end;

		if ($quote)
		{
			$start = preg_quote($start, '#');
			$end   = preg_quote($end, '#');
		}

		return array($start, $end);
	}

	public function getDivTags()
	{
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		return RLTags::getDivTags($this->data->start_div, $this->data->end_div, $tag_start, $tag_end);
	}
}
