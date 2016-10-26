<?php
/**
 * @package         Conditional Content
 * @version         1.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemConditionalContentInstallerScript extends PlgSystemConditionalContentInstallerScriptHelper
{
	public $name           = 'CONDITIONAL_CONTENT';
	public $alias          = 'conditionalcontent';
	public $extension_type = 'plugin';

	public function uninstall($adapter)
	{
		$this->uninstallPlugin($this->extname, 'editors-xtd');
	}
}
