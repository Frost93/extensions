<?php
/**
 * @package         Cache Cleaner
 * @version         5.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class ModCacheCleaner
{
	function __construct()
	{
		// Load plugin parameters
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
		$parameters   = RLParameters::getInstance();
		$this->params = $parameters->getPluginParams('cachecleaner');
	}

	function render()
	{
		if (!isset($this->params->display_link))
		{
			return;
		}

		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

		// load the admin language file
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
		RLFunctions::loadLanguage('mod_cachecleaner');

		$script = "
			var cachecleaner_base = '" . JUri::base(true) . "';
			var cachecleaner_root = '" . JUri::root() . "';
			var cachecleaner_msg_clean = '" . addslashes(html_entity_decode(JText::_('CC_CLEANING_CACHE'))) . "';
			var cachecleaner_msg_inactive = '" . addslashes(html_entity_decode(JText::sprintf('CC_SYSTEM_PLUGIN_NOT_ENABLED', '<a href=&quot;index.php?option=com_plugins&filter_type=system&filter_folder=system&search=cache cleaner&filter_search=cache cleaner&quot;>', '</a>'))) . "';
			var cachecleaner_msg_failure = '" . addslashes(html_entity_decode(JText::_('CC_CACHE_COULD_NOT_BE_CLEANED'))) . "';";
		JFactory::getDocument()->addScriptDeclaration($script);

		RLFunctions::script('regularlabs/script.min.js', '16.4.23089');
		RLFunctions::script('cachecleaner/script.min.js', '5.0.0');
		RLFunctions::stylesheet('regularlabs/style.min.css', '16.4.23089');
		RLFunctions::stylesheet('cachecleaner/style.min.css', '5.0.0');

		$text_ini = strtoupper(str_replace(' ', '_', $this->params->icon_text));
		$text     = JText::_($text_ini);
		if ($text == $text_ini)
		{
			$text = JText::_($this->params->icon_text);
		}

		if ($this->params->display_toolbar_button)
		{
			// Generate html for toolbar button
			$html    = array();
			$html[]  = '<a href="javascript:;" onclick="return false;"  class="btn btn-small cachecleaner_link">';
			$html[]  = '<span class="icon-reglab icon-cachecleaner"></span> ';
			$html[]  = $text;
			$html[]  = '</a>';
			$toolbar = JToolBar::getInstance('toolbar');
			$toolbar->appendButton('Custom', implode('', $html));
		}

		// Generate html for status link
		$html   = array();
		$html[] = '<div class="btn-group cachecleaner">';
		$html[] = '<a href="javascript:;" onclick="return false;" class="cachecleaner_link">';

		if ($this->params->display_link != 'text')
		{
			$html[] = '<span class="icon-reglab icon-cachecleaner"></span> ';
		}

		if ($this->params->display_link != 'icon')
		{
			$html[] = $text;
		}

		$html[] = '</a>';
		$html[] = '</div>';

		echo implode('', $html);
	}
}
