<?php
/**
 * @package         Email Protector
 * @version         3.0.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemEmailProtectorInstallerScript extends PlgSystemEmailProtectorInstallerScriptHelper
{
	public $name           = 'EMAIL_PROTECTOR';
	public $alias          = 'emailprotector';
	public $extension_type = 'plugin';

	function onAfterInstall()
	{
		$this->disableCoreEmailCloaker();
	}

	function disableCoreEmailCloaker()
	{
		// Disable the core Email Cloaking plugin
		$query = $this->db->getQuery(true)
			->update('#__extensions as e')
			->set('e.enabled = 0')
			->where('e.name = ' . $this->db->quote('plg_content_emailcloak'));
		$this->db->setQuery($query);
		$this->db->execute();

		JFactory::getCache()->clean('_system');
	}
}
