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
		list($options, $categories) = $this->getOptions($item->items);

		$layout = new JLayoutFile('list', dirname(__DIR__) . '/layouts');

		return $layout->render(array(
			'id'      => 'contenttemplater-list-' . $this->editor . '-' . $item->id,
			'options' => $options,
			'categories' => $categories,
		));
	}

	public function getContentHtmlModal($item)
	{
		$filter_category = JFactory::getApplication()->input->get('catid');

		list($options, $categories) = $this->getOptions($item->items, true, $filter_category);

		$layout = new JLayoutFile('modal', dirname(__DIR__) . '/layouts');

		return $layout->render(array(
			'form_id'    => 'contenttemplater-modal-' . $this->editor . '-' . $item->id,
			'options'    => $options,
			'categories' => $categories,
			'footer'     => $this->getContentHtmlModalFooter(),
		));
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

		$layout = new JLayoutFile('modal_footer', dirname(__DIR__) . '/layouts');

		return $layout->render('');
	}

	private function getOptions($items, $is_modal = false, $filter_category = null)
	{
		$options    = array();
		$categories = array();

		if (empty($items))
		{
			return array($options, $categories);
		}

		$onclick = ($is_modal ? 'parent.' : '')
			. 'ContentTemplater.loadTemplate([:ID:], \'' . $this->editor . '\', false, ' . ($is_modal ? 'true' : 'false') . ');';
		if ($this->params->show_confirm)
		{
			$onclick = 'if( confirm(\'' . sprintf(JText::_('CT_ARE_YOU_SURE', true), '\n') . '\') ) { ' . $onclick . ' };';
		}

		$previous_category = '';

		foreach ($items as $item)
		{
			if ($item->category != $previous_category)
			{
				$categories[] = $item->category;
			}

			if ($filter_category && $filter_category != $item->category)
			{
				continue;
			}

			if ($this->params->display_categories == 'titled' && $item->category != $previous_category)
			{
				$options[] = '<span><strong>' . $item->category . '</strong></span>';
			}

			$image = $this->getItemImage($item->image);

			$layout = new JLayoutFile('option', dirname(__DIR__) . '/layouts');

			$options[] = $layout->render(array(
				'text'        => $item->text,
				'description' => $item->description,
				'onclick'     => str_replace('[:ID:]', $item->id, $onclick) . ';return false;',
				'image'       => $image,
			));

			$previous_category = $item->category;
		}

		$categories = array_unique($categories);
		asort($categories);

		return array($options, $categories);
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
