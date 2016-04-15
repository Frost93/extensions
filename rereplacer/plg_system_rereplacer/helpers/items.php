<?php
/**
 * @package         ReReplacer
 * @version         7.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/text.php';

class PlgSystemReReplacerHelperItems
{
	var $helpers       = array();
	var $items         = array();
	var $sourcerer_tag = '';

	public function __construct()
	{
		$sourcerer_params = RLParameters::getInstance()->getPluginParams('sourcerer');
		if (!empty($sourcerer_params) && isset($sourcerer_params->syntax_word))
		{
			$this->sourcerer_tag = trim($sourcerer_params->syntax_word);
		}

	}

	public function getItemList($area = 'articles')
	{
		if (isset($this->items[$area]))
		{
			return $this->items[$area];
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('r.*')
			->from('#__rereplacer AS r')
			->where('r.published = 1');
		$where = 'r.area = ' . $db->quote($area);
		$query->where('(' . $where . ')')
			->order('r.ordering, r.id');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$items = array();

		if (empty($rows))
		{
			$this->items[$area] = $items;

			return $this->items[$area];
		}

		foreach ($rows as $row)
		{
			if (!$item = $this->getItem($row, $area))
			{
				continue;
			}

			if (is_array($item))
			{
				$items = array_merge($items, $item);
				continue;
			}

			$items[] = $item;
		}

		if ($area != 'articles')
		{
			$this->filterItemList($items);
		}

		$this->items[$area] = $items;

		return $this->items[$area];
	}

	private function getItem($row, $area = 'articles')
	{
		if (!((substr($row->params, 0, 1) != '{') && (substr($row->params, -1, 1) != '}')))
		{
			$row->params = RLText::html_entity_decoder($row->params);
		}

		$item = RLParameters::getInstance()->getParams($row->params, JPATH_ADMINISTRATOR . '/components/com_rereplacer/item_params.xml');

		unset($row->params);
		foreach ($row as $key => $param)
		{
			$item->{$key} = $param;
		}

		if (
			strlen($item->search) < 3
		)
		{
			return false;
		}

		$this->prepareString($item->search);
		$this->prepareReplaceString($item->replace);

			return $item;
	}

	private function prepareString(&$string)
	{
		if (!is_string($string))
		{
			$string = '';

			return;
		}
	}

	private function prepareReplaceString(&$string)
	{
		$this->prepareString($string);

		if (!$this->sourcerer_tag || $string == '')
		{
			return;
		}

		// fix usage of non-protected {source} tags
		$string = str_replace('{' . $this->sourcerer_tag . '}', '{' . $this->sourcerer_tag . ' 0}', $string);
	}

	public function filterItemList(&$items, $article = 0)
	{
		foreach ($items as $key => &$item)
		{
			if (
				(JFactory::getApplication()->isAdmin() && $item->enable_in_admin == 0)
				|| (JFactory::getApplication()->isSite() && $item->enable_in_admin == 2)
			)
			{
				unset($items[$key]);
				continue;
			}


			if (!$item)
			{
				unset($items[$key]);
			}
		}
	}
}
