<?php
/**
 * @package         Components Anywhere
 * @version         3.0.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemComponentsAnywhereInstallerScript extends PlgSystemComponentsAnywhereInstallerScriptHelper
{
	public $name           = 'COMPONENTS_ANYWHERE';
	public $alias          = 'componentsanywhere';
	public $extension_type = 'plugin';
}
