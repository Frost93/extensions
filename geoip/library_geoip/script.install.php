<?php
/**
 * @package         GeoIp
 * @version         1.2.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class GeoIPInstallerScript extends GeoIPInstallerScriptHelper
{
	public $name           = 'GeoIP';
	public $alias          = 'geoip';
	public $extension_type = 'library';
}
