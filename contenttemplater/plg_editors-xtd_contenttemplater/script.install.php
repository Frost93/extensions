<?php
/**
 * @package         Content Templater
 * @version         6.2.6
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgEditorsXtdContentTemplaterInstallerScript extends PlgEditorsXtdContentTemplaterInstallerScriptHelper
{
	public $name           = 'CONTENT_TEMPLATER';
	public $alias          = 'contenttemplater';
	public $extension_type = 'plugin';
	public $plugin_folder  = 'editors-xtd';

	public function uninstall($adapter)
	{
		$this->uninstallComponent($this->extname);
		$this->uninstallPlugin($this->extname, 'system');
	}
}
