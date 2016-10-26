<?php
/**
 * @package         Conditional Content
 * @version         1.0.0
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
class PlgButtonConditionalContentHelper
{
	public function __construct(&$params)
	{
		$this->params = $params;
	}

	/**
	 * Display the button
	 *
	 * @return array A two element array of ( imageName, textToInsert )
	 */
	function render($name)
	{
		$button = new JObject;

		if (JFactory::getUser()->get('guest'))
		{
			return $button;
		}

		if (JFactory::getApplication()->isSite() && !$this->params->enable_frontend)
		{
			return $button;
		}

		return $this->renderButton($name);
	}

	private function renderButton($name)
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

		RLFunctions::stylesheet('regularlabs/style.min.css');

		$button = new JObject;

		$icon = 'reglab icon-conditionalcontent';
		$link = 'index.php?rl_qp=1'
			. '&folder=plugins.editors-xtd.conditionalcontent'
			. '&file=popup.php'
			. '&name=' . $name;

		$button->modal   = true;
		$button->class   = 'btn';
		$button->link    = $link;
		$button->text    = $this->getButtonText();
		$button->name    = $icon;
		$button->options = "{handler: 'iframe', size: {x: Math.min(window.getSize().x-100, 1200), y: window.getSize().y-100}}";

		return $button;
	}

	private function getButtonText()
	{
		$text_ini = strtoupper(str_replace(' ', '_', $this->params->button_text));
		$text     = JText::_($text_ini);
		if ($text == $text_ini)
		{
			$text = JText::_($this->params->button_text);
		}

		return trim($text);
	}
}
