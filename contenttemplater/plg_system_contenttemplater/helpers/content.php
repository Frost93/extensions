<?php
/**
 * @package         Content Templater
 * @version         6.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemContentTemplaterHelperContent
{
	var $params  = null;
	var $editors = array();
	var $editor  = '[:CT-EDITOR:]';
	var $items   = array();
	var $content = null;

	public function __construct(&$params, $editors)
	{
		$this->params  = $params;
		$this->editors = $editors;

		require_once __DIR__ . '/items.php';
		$helper      = new PlgSystemContentTemplaterHelperItems($this->params);
		$this->items = $helper->getItems();
	}

	public function get()
	{
		if (empty($this->items))
		{
			return;
		}

		require_once __DIR__ . '/buttons.php';
		$helper = new PlgSystemContentTemplaterHelperButtons($this->params, $this->editor);
		$data   = $helper->get();

		$content = array();

		foreach ($data as $item)
		{
			if (empty($item->items) || $item->modal)
			{
				continue;
			}

			$content[] = $this->getContentHtmlList($item);
		}

		$content = implode('', $content);

		$contents = array();
		foreach ($this->editors as $editor)
		{
			$contents[] = str_replace($this->editor, $editor, $content);
		}

		return implode('', $contents);
	}

	private function getContentHtmlList($item)
	{
		$options = $this->getOptions($item->items);

		return
			'<div id="contenttemplater-list-' . $this->editor . '-' . $item->id . '" class="contenttemplater-list">'
			. '<ul role="menu" class="dropdown-menu">'
			. '<li>' . implode('</li><li>', $options) . '</li>'
			. '</ul>'
			. '</div>';
	}

	public function getContentHtmlModal($item)
	{
		$options = $this->getOptions($item->items, true);

		return
			'<div id="contenttemplater-modal-' . $this->editor . '-' . $item->id . '" tabindex="-1"  class="contenttemplater-modal">' . '<h3>' . JText::_('INSERT_TEMPLATE') . '</h3>'
			. '<div class="row-fluid">'
			. '<ul class="list list-striped"><li>' . implode('</li><li>', $options) . '</li></ul>'
			. '</div>'
			. $this->getContentHtmlModalFooter()
			. '</div>';
	}

	private function getContentHtmlModalFooter()
	{
		if (JFactory::getApplication()->isSite())
		{
			return '';
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_contenttemplater/helpers/helper.php';
		$canDo = ContentTemplaterHelper::getActions();
		if (!$canDo->get('core.create'))
		{
			return '';
		}

		return
			'<a target="_blank" href="index.php?option=com_contenttemplater&view=item&layout=edit" class="btn">'
			. '<span class="icon-save-new"></span> '
			. JText::_('CT_CREATE_NEW_TEMPLATE')
			. '</a>'

			. ' '

			. '<a target="_blank" href="index.php?option=com_contenttemplater" class="btn">'
			. '<span class="icon-reglab icon-contenttemplater"></span> '
			. JText::_('CT_MANAGE_TEMPLATES')
			. '</a>';
	}

	private function getOptions($items, $is_modal = false)
	{
		if (empty($items))
		{
			return array();
		}

		$options = array();

		$onclick = ($is_modal ? 'parent.' : '')
			. 'ContentTemplater.loadTemplate([:ID:], \'' . $this->editor . '\', false, ' . ($is_modal ? 'true' : 'false') . ');';
		if ($this->params->show_confirm)
		{
			$onclick = 'if( confirm(\'' . sprintf(JText::_('CT_ARE_YOU_SURE', true), '\n') . '\') ) { ' . $onclick . ' };';
		}

		$previous_category = '';

		foreach ($items as $item)
		{
			if ($this->params->display_categories == 'titled' && $item->category != $previous_category)
			{
				$options[] = '<span><strong>' . $item->category . '</strong></span>';
			}

			$image = $this->getItemImage($item->image);

			$options[] = '<a class="hasPopover" data-trigger="hover"'
				. ' title="' . $item->text . '" data-content="' . $item->description . '"'
				. ' href="javascript:;" onclick="' . str_replace('[:ID:]', $item->id, $onclick) . ';return false;"'
				. '>'
				. $image . $item->text
				. '</a>';

			$previous_category = $item->category;
		}

		return $options;
	}

	private function getItemImage($image)
	{
		// convert image to icon class
		$icon = str_replace('.png', '', $image);

		if (empty($icon) || $icon == -1)
		{
			return '';
		}

		return '<span class="icon-' . $icon . '"></span> ';
	}
}
