<?php
/**
 * @package         Tabs
 * @version         6.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

if (JFactory::getUser()->get('guest'))
{
	JError::raiseError(403, JText::_("ALERTNOTAUTH"));
}

require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
$parameters = RLParameters::getInstance();
$params     = $parameters->getPluginParams('tabs');

if (JFactory::getApplication()->isSite() && !$params->enable_frontend)
{
	JError::raiseError(403, JText::_("ALERTNOTAUTH"));
}

$class = new PlgButtonTabsPopup($params);
$class->render();

class PlgButtonTabsPopup
{
	var $params = null;

	function __construct(&$params)
	{
		$this->params = $params;
	}

	function render()
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

		jimport('joomla.filesystem.file');

		// Load plugin language
		RLFunctions::loadLanguage('plg_system_regularlabs');
		RLFunctions::loadLanguage('plg_editors-xtd_tabs');
		RLFunctions::loadLanguage('plg_system_tabs');

		RLFunctions::script('regularlabs/script.min.js', '16.5.10919');
		RLFunctions::stylesheet('regularlabs/popup.min.css', '16.5.10919');
		RLFunctions::stylesheet('regularlabs/style.min.css', '16.5.10919');

		// Tag character start and end
		list($tag_start, $tag_end) = explode('.', $this->params->tag_characters);

		$script = "
			var tabs_tag_open = '" . preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_open) . "';
			var tabs_tag_close = '" . preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_close) . "';
			var tabs_tag_delimiter = '" . (($this->params->tag_delimiter == '=') ? '=' : ' ') . "';
			var tabs_tag_characters = ['" . $tag_start . "', '" . $tag_end . "'];
			var tabs_editorname = '" . JFactory::getApplication()->input->getString('name', 'text') . "';
			var tabs_content_placeholder = '" . JText::_('TAB_TEXT', true) . "';
			var tabs_error_empty_title = '" . JText::_('TAB_ERROR_EMPTY_TITLE', true) . "';
			var tabs_max_count = " . (int) $this->params->button_max_count . ";
		";
		JFactory::getDocument()->addScriptDeclaration($script);

		RLFunctions::script('tabs/popup.min.js', '6.0.3');
		RLFunctions::stylesheet('tabs/popup.min.css', '6.0.3');

		echo $this->getHTML();
	}

	function getHTML()
	{
		ob_start();
		include __DIR__ . '/popup.tmpl.php';
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}
