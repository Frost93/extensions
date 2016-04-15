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

/**
 * Module that cleans cache
 */

// return if Regular Labs Library plugin is not installed
jimport('joomla.filesystem.file');
if (!JFile::exists(JPATH_PLUGINS . '/system/regularlabs/regularlabs.php'))
{
	return;
}

// return if Regular Labs Library plugin is not enabled
$regularlabs = JPluginHelper::getPlugin('system', 'regularlabs');
if (!isset($regularlabs->name))
{
	return;
}

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$helper = new ModCacheCleaner;
$helper->render();
