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

/**
 ** Plugin that places the button
 */
class PlgButtonContentTemplaterHelper
{
	var $params = null;

	public function __construct(&$params)
	{
		$this->params = $params;
	}

	/**
	 * Display the button
	 *
	 * @return array A two element array of ( imageName, textToInsert )
	 */
	public function render($editor)
	{
		if (JFactory::getApplication()->isSite() && !$this->params->enable_frontend)
		{
			return false;
		}

		require_once JPATH_PLUGINS . '/system/contenttemplater/helpers/buttons.php';
		$class = new PlgSystemContentTemplaterHelperButtons($this->params, $editor);

		$data = $class->get();

		if (empty($data))
		{
			return false;
		}

		JHtml::_('bootstrap.framework');
		JHtml::_('bootstrap.popover');

		RLFunctions::script('regularlabs/script.min.js');
		RLFunctions::script('contenttemplater/script.min.js', '6.2.6');

		RLFunctions::stylesheet('regularlabs/style.min.css');
		RLFunctions::stylesheet('contenttemplater/button.min.css', '6.2.6');

		$name = 'rl_ct_button-' . $this->getEditorId($editor);

		$buttons = array();

		foreach ($data as $button)
		{
			$btn = new JObject;

			$btn->modal   = $button->modal;
			$btn->class   = $button->class;
			$btn->text    = $button->text;
			$btn->name    = $button->name . ' ' . $name;
			$btn->link    = $button->link;
			$btn->onclick = $button->onclick ? $button->onclick . 'return false;' : '';
			$btn->options = $button->options;

			$buttons[] = $btn;
		}

		if (JVERSION >= 3.5)
		{
			return $buttons;
		}

		$button          = new JObject;
		$button->name    = 'contenttemplater';
		$button->options = $this->getHTML($buttons);

		return $button;
	}

	private function getEditorId($editor)
	{
		$editor = JFilterOutput::stringURLSafe($editor);
		$editor = str_replace('-', '_', $editor);


		return $editor;
	}

	private function getHTML($buttons)
	{
		$html = array();

		foreach ($buttons as $button)
		{
			$html[] = $this->getButtonHTML($button);
		}

		return
			'" style="display:none;" class="btn"></a>'
			. implode('', $html)
			. '<a style="display:none;" class="btn';
	}

	private function getButtonHTML($button)
	{
		$text = '<span class="icon-' . $button->name . '"></span> ' . $button->text;

		$class = 'btn';

		if ($button->modal)
		{
			JHTML::_('behavior.modal', 'a.modal-button');
			$class .= ' modal-button';
		}

		return '<a class="' . $class . '" href="' . $button->link . '" onclick="' . str_replace('"', '\\"', $button->onclick) . '">' . $text . '</a>';
	}
}
