<?php
/**
 * @package         Dummy Content
 * @version         3.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Plugin that places the button
 */
class PlgButtonDummyContentHelper
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

		if (JFactory::getApplication()->isSite() && !$this->params->enable_frontend)
		{
			return $button;
		}

		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

		RLFunctions::loadLanguage('plg_editors-xtd_dummycontent');

		RLFunctions::stylesheet('regularlabs/style.min.css');

		$icon = 'reglab icon-dummycontent';
		$link = 'index.php?rl_qp=1'
			. '&folder=plugins.editors-xtd.dummycontent'
			. '&file=popup.php'
			. '&name=' . $name;

		$text_ini = strtoupper(str_replace(' ', '_', $this->params->button_text));
		$text     = JText::_($text_ini);
		if ($text == $text_ini)
		{
			$text = JText::_($this->params->button_text);
		}

		$button->modal = true;
		$button->class = 'btn';
		$button->link  = $link;
		$button->text  = trim($text);
		$button->name  = $icon;
		$button->options = "{handler: 'iframe', size: {x:500, y:460}}";

		return $button;
	}
}
