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

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemCacheCleanerInstallerScript extends PlgSystemCacheCleanerInstallerScriptHelper
{
	public $name           = 'CACHE_CLEANER';
	public $alias          = 'cachecleaner';
	public $extension_type = 'plugin';

	public function uninstall($adapter)
	{
		$this->uninstallModule($this->extname);
	}

	public function onAfterInstall()
	{
		$this->fixOldParams();
	}

	public function fixOldParams()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('params'))
			->from('#__extensions')
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('cachecleaner'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
		$this->db->setQuery($query);
		$params = $this->db->loadResult();

		if (empty($params))
		{
			return;
		}

		$params = json_decode();

		if (empty($params))
		{
			return;
		}

		if (isset($params->clean_folders_selection))
		{
			return;
		}

		$params->clean_tmp = isset($params->clean_tmp) ? 2 : 0;

		if (!empty($params->clean_folders))
		{
			$params->clean_folders_selection = $params->clean_folders;
			$params->clean_folders           = 2;
		}

		if (isset($params->auto_save_clean_folders))
		{
			$params->clean_tmp     = isset($params->clean_tmp) ? 1 : 0;
			$params->clean_folders = isset($params->clean_folders) ? 1 : 0;
		}

		unset($params->auto_save_clean_folders);


		$query->clear()
			->update('#__extensions')
			->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('cachecleaner'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
		$this->db->setQuery($query);
		$this->db->execute();

		JFactory::getCache()->clean('_system');
	}
}
