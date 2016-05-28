<?php
/**
 * @package         Content Templater
 * @version         6.0.1
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
class PlgButtonContentTemplater extends JPlugin
{
	/**
	 * Display the button
	 */
	function onDisplay($name)
	{
		jimport('joomla.filesystem.file');

		// return if component is not installed
		if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_contenttemplater/models/list.php'))
		{
			return;
		}

		// return if system plugin is not installed
		if (!JFile::exists(JPATH_PLUGINS . '/system/contenttemplater/helpers/items.php'))
		{
			return;
		}

		// return if Regular Labs Library plugin is not installed
		if (!JFile::exists(JPATH_PLUGINS . '/system/regularlabs/regularlabs.php'))
		{
			return;
		}

		// Load component parameters
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
		$parameters = RLParameters::getInstance();
		$params     = $parameters->getComponentParams('contenttemplater');

		if ((JFactory::getApplication()->isAdmin() && $params->enable_frontend == 2)
			|| (JFactory::getApplication()->isSite() && $params->enable_frontend == 0)
		)
		{
			return;
		}

		require_once JPATH_PLUGINS . '/system/contenttemplater/helpers/items.php';
		$helper = new PlgSystemContentTemplaterHelperItems($params);
		$items  = $helper->getItems();

		if (empty($items))
		{
			return;
		}

		// load the admin language file
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
		RLFunctions::loadLanguage('plg_' . $this->_type . '_' . $this->_name);

		// Include the Helper
		require_once JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/helper.php';
		$class  = get_class($this) . 'Helper';
		$helper = new $class($params);

		return $helper->render($name);
	}
}
