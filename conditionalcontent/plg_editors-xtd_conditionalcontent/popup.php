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

if (JFactory::getUser()->get('guest'))
{
	JError::raiseError(403, JText::_("ALERTNOTAUTH"));
}

require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
$parameters = RLParameters::getInstance();
$params     = $parameters->getPluginParams('conditionalcontent');

if (JFactory::getApplication()->isSite() && !$params->enable_frontend)
{
	JError::raiseError(403, JText::_("ALERTNOTAUTH"));
}

$class = new PlgButtonConditionalContentPopup($params);
$class->render();

class PlgButtonConditionalContentPopup
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
		RLFunctions::loadLanguage('plg_editors-xtd_conditionalcontent');
		RLFunctions::loadLanguage('plg_system_conditionalcontent');

		RLFunctions::script('regularlabs/script.min.js');
		RLFunctions::script('regularlabs/form.min.js');
		RLFunctions::stylesheet('regularlabs/popup.min.css');
		RLFunctions::stylesheet('regularlabs/style.min.css');

		JHtml::_('formbehavior.chosen', 'select');

		// Tag character start and end
		list($tag_start, $tag_end) = explode('.', $this->params->tag_characters);

		$script = "
			var conditionalcontent_tag_show = '" . preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_show) . "';
			var conditionalcontent_tag_hide = '" . preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_hide) . "';
			var conditionalcontent_content = '" . JText::_('COC_CONTENT_TEXT') . "';
			var conditionalcontent_alternative = '" . JText::_('COC_ALTERNATIVE_CONTENT_TEXT') . "';
			var conditionalcontent_tag_characters = ['" . $tag_start . "', '" . $tag_end . "'];
			var conditionalcontent_editorname = '" . JFactory::getApplication()->input->getString('name', 'text') . "';
		";
		JFactory::getDocument()->addScriptDeclaration($script);

		RLFunctions::script('conditionalcontent/popup.min.js', '1.0.0');

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
