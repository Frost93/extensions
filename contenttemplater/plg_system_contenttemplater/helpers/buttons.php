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

class PlgSystemContentTemplaterHelperButtons
{
	var $params = null;
	var $editor = null;
	var $items  = array();

	public function __construct(&$params, $editor)
	{
		$this->params = $params;
		$this->editor = $editor;

		require_once __DIR__ . '/items.php';
		$helper      = new PlgSystemContentTemplaterHelperItems($this->params);
		$this->items = $helper->getButtonItems();
	}

	public function get()
	{
		$buttons = array();

		if (!empty($this->items))
		{
			$data = $this->getData();

			foreach ($data as $item)
			{
				// button has no items and is not a standalone
				if (empty($item->items) && empty($item->id))
				{
					continue;
				}

				$this->setButtonData($item);
				$buttons[] = $item;
			}
		}


		return $buttons;
	}

	private function getData()
	{
		$text_ini = strtoupper(str_replace(' ', '_', $this->params->button_text));
		$text     = JText::_($text_ini);
		if ($text == $text_ini)
		{
			$text = JText::_($this->params->button_text);
		}

		$main     = $this->getButtonObject($text);
		$separate = array();
		$grouped  = array();

		foreach ($this->items as $item)
		{
			if ($item->button_separate)
			{
				$button     = $this->getButtonObject(
					$item->button_name ?: $item->name,
					$item->id,
					$item->button_image
				);
				$separate[] = $button;

				continue;
			}


			$main->items[] = $this->getButtonData($item);
		}

		return array_merge(array($main), array_merge($separate, $grouped));
	}

	private function getButtonObject($text, $id = 0, $image = '')
	{
		return (object) array(
			'modal'   => false,
			'class'   => 'btn',
			'link'    => '#',
			'text'    => $text,
			'name'    => '',
			'onclick' => '',
			'options' => '',
			'id'      => $id,
			'image'   => $image,
			'items'   => array(),
		);
	}

	private function getButtonData($item)
	{
		return (object) array(
			'id'          => $item->id,
			'text'        => $item->name,
			'image'       => $item->button_image,
			'description' => $item->description,
			'category'    => $item->category,
		);
	}

	private function setButtonData(&$item)
	{
		$item->name = $this->getIconClass($item->image);

		if (empty($item->items))
		{
			$this->setButtonDataSeparate($item);

			return;
		}
		if ($this->params->open_in_modal == 1
			|| ($this->params->open_in_modal == 2 && count($item->items) >= $this->params->switch_to_modal)
		)
		{
			$this->setButtonDataModal($item);

			return;
		}

		$this->setButtonDataList($item);
	}

	private function setButtonDataModal(&$item)
	{
		$item->modal   = true;
		$item->link    = 'index.php?rl_qp=1&folder=plugins.editors-xtd.contenttemplater&file=popup.php&id=' . $item->id . '&editor=' . $this->editor;
		$item->options = "{handler: 'iframe', size: {x:500, y:600}}";
	}

	private function setButtonDataList(&$item)
	{
		$item->onclick = 'ContentTemplater.showList(\'' . $item->id . '\', \'' . $this->editor . '\');';
	}

	private function setButtonDataSeparate(&$item)
	{
		$onclick = 'ContentTemplater.loadTemplate(\'' . $item->id . '\', \'' . $this->editor . '\');';

		if ($this->params->show_confirm)
		{
			$onclick = 'if(confirm(\'' . sprintf(JText::_('CT_ARE_YOU_SURE', true), '\n') . '\')){' . $onclick . '};';
		}

		$item->onclick = 'try{IeCursorFix();}catch(e){} ' . $onclick;
	}

	private function getIconClass($image)
	{
		// convert image to icon class
		$icon = str_replace('.png', '', $image);

		if ($icon == -1 || $icon == '')
		{
			return $this->params->button_icon ? 'reglab icon-contenttemplater' : '';
		}

		return $icon;
	}
}
