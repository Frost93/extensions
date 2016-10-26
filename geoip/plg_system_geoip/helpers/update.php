<?php
/**
 * @package         GeoIp
 * @version         1.2.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
RLFunctions::loadLanguage('plg_system_geoip');

require_once __DIR__ . '/updater.php';
$updater = new GeoIPUpdater;

$force = JFactory::getApplication()->input->getInt('force');

if ($message = $updater->update('City', $force))
{
	$prefix = strpos($message, 'ERROR') === false ? '+' : '';;
	$message = $prefix . JText::_('GEO_MESSAGE_' . $message);

	die($message);
}

if (!$last_date = $updater->getVersion())
{
	die();
}

$message = '+' . JText::sprintf('GEO_MESSAGE_UPDATED_TO', JHtml::_('date', $last_date, JText::_('DATE_FORMAT_LC3')));

die($message);
