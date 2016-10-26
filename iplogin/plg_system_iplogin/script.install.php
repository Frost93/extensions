<?php
/**
 * @package         IP Login
 * @version         3.0.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemIPLoginInstallerScript extends PlgSystemIPLoginInstallerScriptHelper
{
	public $name           = 'IP_LOGIN';
	public $alias          = 'iplogin';
	public $extension_type = 'plugin';
}
