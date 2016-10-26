<?php
/**
 * @package         Content Templater
 * @version         6.2.6
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemContentTemplaterHelperItems
{
	var $params = null;
	var $items  = null;

	public function __construct(&$params, $items = null)
	{
		$this->params = $params;

		if (!is_null($items))
		{
			$this->items = $items;
		}

	}

	public function getItems()
	{
		if (!is_null($this->items))
		{
			return $this->items;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_contenttemplater/models/list.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_contenttemplater/models/item.php';

		$list = new ContentTemplaterModelList;
		$list->setState('limit', 0);
		$list->setState('limitstart', 0);
		$items = $list->getItems(true);

		$item_model = new ContentTemplaterModelItem;

		$this->items = array();

		foreach ($items as $i => $item)
		{
			// not enabled if: not published
			if (!$item->published)
			{
				continue;
			}

			$item = $item_model->getItem($item->id, false, false, true);

			$this->items[] = $item;
		}

		return $this->items;
	}

	public function getButtonItems()
	{
		$items = $this->getItems();

		foreach ($items as $i => $item)
		{
			if (!$this->passChecks($item, 'button'))
			{
				unset($items[$i]);
			}
		}

		return array_values($items);
	}


	private function passChecks(&$item, $type = 'button')
	{
		if (!$item->{$type . '_enabled'})
		{
			return false;
		}

		// not enabled if: not active in this area (frontend/backend)
		if (
			(JFactory::getApplication()->isAdmin() && $item->{$type . '_enable_in_frontend'} == 2)
			|| (JFactory::getApplication()->isSite() && $item->{$type . '_enable_in_frontend'} == 0)
		)
		{
			return false;
		}



		return true;
	}
}
