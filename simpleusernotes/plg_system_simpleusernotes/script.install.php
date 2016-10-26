<?php
/**
 * @package         Simple User Notes
 * @version         0.1.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemSimpleUserNotesInstallerScript extends PlgSystemSimpleUserNotesInstallerScriptHelper
{
	public $name           = 'SIMPLE_USER_NOTES';
	public $alias          = 'simpleusernotes';
	public $extension_type = 'plugin';
}
