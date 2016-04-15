<?php
/**
 * @package         Advanced Template Manager
 * @version         2.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class Com_AdvancedTemplatesInstallerScript extends Com_AdvancedTemplatesInstallerScriptHelper
{
	public $name           = 'ADVANCED_TEMPLATE_MANAGER';
	public $alias          = 'advancedtemplatemanager';
	public $extname        = 'advancedtemplates';
	public $extension_type = 'component';

	public function uninstall($adapter)
	{
		$this->uninstallPlugin($this->extname, $folder = 'system');
	}

	public function onBeforeInstall()
	{
		// Fix incorrectly formed versions because of issues in old packager
		$this->fixFileVersions(
			array(
				JPATH_ADMINISTRATOR . '/components/com_advancedtemplates/advancedtemplates.xml',
				JPATH_PLUGINS . '/system/advancedtemplates/advancedtemplates.xml',
			)
		);
	}

	public function onAfterInstall()
	{
		$this->createTable();
		$this->removeAdminMenu();
		$this->removeFrontendComponentFromDB();
		$this->deleteOldFiles();
	}

	public function createTable()
	{
		// main table
		$query = "CREATE TABLE IF NOT EXISTS `#__advancedtemplates` (
			`styleid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
			`params` TEXT NOT NULL,
			PRIMARY KEY (`styleid`)
		) DEFAULT CHARSET=utf8;";
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function removeAdminMenu()
	{
		// hide admin menu
		$query = $this->db->getQuery(true)
			->delete('#__menu')
			->where($this->db->quoteName('path') . ' = ' . $this->db->quote('advancedtemplates'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
			->where($this->db->quoteName('client_id') . ' = 1');
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function removeFrontendComponentFromDB()
	{
		// remove frontend component from extensions table
		$query = $this->db->getQuery(true)
			->delete('#__extensions')
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_advancedtemplates'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
			->where($this->db->quoteName('client_id') . ' = 0');
		$this->db->setQuery($query);
		$this->db->execute();

		JFactory::getCache()->clean('_system');
	}

	private function deleteOldFiles()
	{
		JFile::delete(
			array(
				JPATH_ADMINISTRATOR . '/components/com_advancedtemplates/script.advancedtemplates.php',
			)
		);

		$this->deleteFolders(
			array(
				JPATH_SITE . '/components/com_advancedtemplates',
			)
		);
	}

}
