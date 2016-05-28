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
 * Component Helper
 */
class ContentTemplaterHelper
{
	public static $extension = 'com_contenttemplater';

	/**
	 * Configure the Itembar.
	 *
	 * @param    string    The name of the active view.
	 */
	public static function addSubmenu($vName)
	{
		// No submenu for this component.
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return    JObject
	 */
	public static function getActions()
	{
		$user      = JFactory::getUser();
		$result    = new JObject;
		$assetName = 'com_contenttemplater';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete',
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Determines if the plugin for Content Templater to work is enabled.
	 *
	 * @return    boolean
	 */
	public static function isEnabled()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quote('enabled'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
			->where($db->quoteName('element') . ' = ' . $db->quote('contenttemplater'));

		$db->setQuery($query);

		return (boolean) $db->loadResult();
	}
}
