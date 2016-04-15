<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         6.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class Com_RegularLabsManagerInstallerScript extends Com_RegularLabsManagerInstallerScriptHelper
{
	public $name           = 'REGULAR_LABS_EXTENSION_MANAGER';
	public $alias          = 'extensionmanager';
	public $extname        = 'regularlabsmanager';
	public $extension_type = 'component';

	public function onAfterInstall()
	{
		// Check if old NoNumber Extension Manager is still installed
		if (!JFolder::exists(JPATH_ADMINISTRATOR . '/components/com_nonumbermanager'))
		{
			return;
		}

		$this->copyParamsFromNoNumberExtensionManager();
		$this->preUninstallNoNumberExtensionManager();
	}

	private function copyParamsFromNoNumberExtensionManager()
	{
		$data = JComponentHelper::getComponent('com_regularlabsmanager');
		$data = json_decode(json_encode($data), true);

		if (!empty($data['params']))
		{
			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from('#__extensions')
			->where($db->quoteName('element') . ' = ' . $db->quote('com_nonumbermanager'));
		$db->setQuery($query);
		$params = $db->loadResult();

		if (empty($params))
		{
			return;
		}

		$data['params'] = $params;

		$table = JTable::getInstance('extension');

		// Load the previous Data
		if (!$table->load($data['id']))
		{
			return;
		}

		unset($data['id']);

		$table->bind($data) && $table->check() && $table->store();
	}

	private function preUninstallNoNumberExtensionManager()
	{
		// Copy uninstall file
		JFile::copy(__DIR__ . '/nonumbermanager.php', JPATH_ADMINISTRATOR . '/components/com_nonumbermanager/nonumbermanager.php');

		// Remove the version number in the xml file so that the NoNumber Framework requirement check ignores this
		$contents = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_nonumbermanager/nonumbermanager.xml');
		$contents = preg_replace('#@version.*\n#', '', $contents);
		file_put_contents(JPATH_ADMINISTRATOR . '/components/com_nonumbermanager/nonumbermanager.xml', $contents);

		// Remove admin menu item
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete('#__menu')
			->where($db->quoteName('alias') . ' = ' . $db->quote('com-nonumbermanager'));

		$db->setQuery($query);
		$db->execute();
	}
}
