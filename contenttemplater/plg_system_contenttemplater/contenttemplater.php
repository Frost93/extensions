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

/**
 * System Plugin that places a Content Templater code block into the text
 */
class PlgSystemContentTemplater extends JPlugin
{
	private $_alias       = 'contenttemplater';
	private $_title       = 'CONTENT_TEMPLATER';
	private $_lang_prefix = 'CT';

	private $_init   = false;
	private $_helper = null;

	public function onAfterRoute()
	{
		$this->getHelper();
	}

	public function onAfterDispatch()
	{
		if (!$this->getHelper())
		{
			return;
		}

		$this->_helper->onAfterDispatch();
	}

	/*
	 * Below methods are general functions used in most of the NoNumber extensions
	 * The reason these are not placed in the Regular Labs Library files is that they also
	 * need to be used when the Regular Labs Library is not installed
	 */

	/**
	 * Create the helper object
	 *
	 * @return object The plugins helper object
	 */
	private function getHelper()
	{
		// Already initialized, so return
		if ($this->_init)
		{
			return $this->_helper;
		}

		$this->_init = true;

		// only in html
		if (JFactory::getDocument()->getType() != 'html')
		{
			return false;
		}

		if (!$this->isFrameworkEnabled())
		{
			return false;
		}

		// return if component is not installed
		if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_contenttemplater/models/list.php'))
		{
			return false;
		}

		// return if editor button is not installed
		if (!JFile::exists(JPATH_PLUGINS . '/editors-xtd/contenttemplater/contenttemplater.php'))
		{
			return;
		}

		require_once JPATH_LIBRARIES . '/regularlabs/helpers/protect.php';

		if (RLProtect::isProtectedPage($this->_alias))
		{
			return false;
		}

		// Load component parameters
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
		$params = RLParameters::getInstance()->getComponentParams('contenttemplater');

		if ((JFactory::getApplication()->isAdmin() && $params->enable_frontend == 2)
			|| (JFactory::getApplication()->isSite() && $params->enable_frontend == 0)
		)
		{
			return false;
		}

		require_once JPATH_LIBRARIES . '/regularlabs/helpers/helper.php';
		$this->_helper = RLHelper::getPluginHelper($this, $params);

		return $this->_helper;
	}

	/**
	 * Check if the Regular Labs Library is enabled
	 *
	 * @return bool
	 */
	private function isFrameworkEnabled()
	{
		// Return false if Regular Labs Library is not installed
		if (!$this->isFrameworkInstalled())
		{
			return false;
		}

		$regularlabs = JPluginHelper::getPlugin('system', 'regularlabs');
		if (!isset($regularlabs->name))
		{
			$this->throwError($this->_lang_prefix . '_REGULAR_LABS_LIBRARY_NOT_ENABLED');

			return false;
		}

		return true;
	}

	/**
	 * Check if the Regular Labs Library is installed
	 *
	 * @return bool
	 */
	private function isFrameworkInstalled()
	{
		jimport('joomla.filesystem.file');

		if (!JFile::exists(JPATH_PLUGINS . '/system/regularlabs/regularlabs.php'))
		{
			$this->throwError($this->_lang_prefix . '_REGULAR_LABS_LIBRARY_NOT_INSTALLED');

			return false;
		}

		return true;
	}

	/**
	 * Place an error in the message queue
	 */
	private function throwError($text)
	{
		// Return if page is not an admin page or the admin login page
		if (
			!JFactory::getApplication()->isAdmin()
			|| JFactory::getUser()->get('guest')
		)
		{
			return;
		}

		// load the admin language file
		JFactory::getLanguage()->load('plg_' . $this->_type . '_' . $this->_name, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name);

		$text = JText::_($text) . ' ' . JText::sprintf($this->_lang_prefix . '_EXTENSION_CAN_NOT_FUNCTION', JText::_($this->_title));

		// Check if message is not already in queue
		$messagequeue = JFactory::getApplication()->getMessageQueue();
		foreach ($messagequeue as $message)
		{
			if ($message['message'] == $text)
			{
				return;
			}
		}

		JFactory::getApplication()->enqueueMessage($text, 'error');
	}
}
