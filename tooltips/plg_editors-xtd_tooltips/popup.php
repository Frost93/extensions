<?php
/**
 * @package         Tooltips
 * @version         6.0.2
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
$params     = $parameters->getPluginParams('tooltips');

if (JFactory::getApplication()->isSite() && !$params->enable_frontend)
{
	JError::raiseError(403, JText::_("ALERTNOTAUTH"));
}

$class = new PlgButtonTooltipsPopup($params);
$class->render();

class PlgButtonTooltipsPopup
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
		RLFunctions::loadLanguage('plg_editors-xtd_tooltips');
		RLFunctions::loadLanguage('plg_system_tooltips');

		RLFunctions::script('regularlabs/script.min.js');
		RLFunctions::stylesheet('regularlabs/popup.min.css');
		RLFunctions::stylesheet('regularlabs/style.min.css');

		// Tag character start and end
		list($tag_start, $tag_end) = explode('.', $this->params->tag_characters);

		$script = "
			var tooltips_tag = '" . preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag) . "';
			var tooltips_tag_characters = ['" . $tag_start . "', '" . $tag_end . "'];
			var tooltips_editorname = '" . JFactory::getApplication()->input->getString('name', 'text') . "';
			var tooltips_text_placeholder = '" . JText::_('TT_TEXT', true) . "';
			var tooltips_error_empty_image = '" . JText::_('TT_ERROR_EMPTY_IMAGE', true) . "';
			var tooltips_error_empty_text = '" . JText::_('TT_ERROR_EMPTY_TEXT', true) . "';
		";
		JFactory::getDocument()->addScriptDeclaration($script);

		RLFunctions::script('tooltips/popup.min.js', '6.0.2');

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
