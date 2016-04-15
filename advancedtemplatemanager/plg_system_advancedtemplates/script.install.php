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

class PlgSystemAdvancedTemplatesInstallerScript extends PlgSystemAdvancedTemplatesInstallerScriptHelper
{
	public $name           = 'ADVANCED_TEMPLATE_MANAGER';
	public $alias          = 'advancedtemplatemanager';
	public $extname        = 'advancedtemplates';
	public $extension_type = 'plugin';

	public function uninstall($adapter)
	{
		$this->uninstallComponent($this->extname);
	}

	public function onAfterInstall()
	{
		$this->setPluginOrdering();
	}

	private function setPluginOrdering()
	{
		$query = $this->db->getQuery(true)
			->update('#__extensions')
			->set($this->db->quoteName('ordering') . ' = 1')
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('advancedtemplates'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
		$this->db->setQuery($query);
		$this->db->execute();

		JFactory::getCache()->clean('_system');
	}
}
