<?php
/**
 * @package         What? Nothing!
 * @version         11.0.2
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemWhatNothingInstallerScript extends PlgSystemWhatNothingInstallerScriptHelper
{
	public $name           = 'WHAT_NOTHING';
	public $alias          = 'whatnothing';
	public $extension_type = 'plugin';

	public function onAfterInstall()
	{
		// Uninstall old plugin (with a minus in alias)
		$this->uninstallPlugin('what-nothing', 'system', false);
		if (JFolder::exists(JPATH_SITE . '/media/what-nothing'))
		{
			JFolder::delete(JPATH_SITE . '/media/what-nothing');
		}
	}
}
