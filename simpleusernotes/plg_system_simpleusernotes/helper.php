<?php
/**
 * @package         Simple User Notes
 * @version         0.1.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

RLFunctions::loadLanguage('plg_system_simpleusernotes');

/**
 * Plugin to log in automatically by IP address
 */
class PlgSystemSimpleUserNotesHelper
{
	var $ip        = null;
	var $url_query = null;
	var $url_keys  = null;

	public function __construct(&$params)
	{
		JFormHelper::addFieldPath(__DIR__ . '/fields');

		$this->params = $params;
	}

	public function addFieldToUserForm($form)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form.
		$name = $form->getName();
		if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
		{
			return true;
		}

		// load the admin language file
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
		RLFunctions::loadLanguage('plg_system_simpleusernotes');

		// Add the registration fields to the form.
		JForm::addFormPath(__DIR__ . '/form');
		$form->loadFile('notes', false);

		return true;
	}

	public function removeCoreUserNoteLinks()
	{
		if (!JFactory::getApplication()->isAdmin())
		{
			return;
		}

		$body = JFactory::getApplication()->getBody();

		// Remove menu items
		$body = preg_replace(
			'#<li class="divider"><span></span></li>'
			. '\s*<li class="dropdown-submenu"><a class="dropdown-toggle menu-user-note".*?categories-com-users.*?</ul>\s*</li>#s',
			'',
			$body
		);

		if (JFactory::getApplication()->input->get('option') != 'com_users')
		{
			JFactory::getApplication()->setBody($body);

			return;
		}

		// Remove sidebar items
		$body = preg_replace(
			'#<li>\s*<a href="index\.php\?(option=com_users&amp;view=notes|option=com_categories&amp;extension=com_users)">.*?</li>#s',
			'',
			$body
		);

		if (JFactory::getApplication()->input->get('view', 'users') != 'users')
		{
			JFactory::getApplication()->setBody($body);

			return;
		}

		// Remove user notes buttons
		$body = preg_replace(
			'#<div class="btn-group">\s*<a href="[^"]*option=com_users&amp;(view=notes|task=note).*?</div>#s',
			'',
			$body
		);

		JFactory::getApplication()->setBody($body);
	}
}
