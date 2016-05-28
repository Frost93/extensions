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

class PlgSystemGeoIPInstallerScript extends PlgSystemGeoIPInstallerScriptHelper
{
	public $name           = 'GeoIP';
	public $alias          = 'geoip';
	public $extension_type = 'plugin';

	public function onAfterInstall()
	{
		$this->deleteFolders(
			array(
				JPATH_LIBRARIES . '/geoip/geoip2',
				JPATH_LIBRARIES . '/geoip/maxmind',
			)
		);

		require_once JPATH_PLUGINS . '/system/geoip/helpers/updater.php';
		$updater = new GeoIPUpdater;

		if ($error = $updater->update('City', true))
		{
			JFactory::getApplication()->enqueueMessage(
				JText::_('GEO_MESSAGE_' . $error), 'error'
			);
		}

		if ($last_date = $updater->getVersion())
		{
			JFactory::getApplication()->enqueueMessage(
				JText::sprintf('GEO_MESSAGE_UPDATED_TO', JHtml::_('date', $last_date, JText::_('DATE_FORMAT_LC3')))
			);
		}
	}

	public function uninstall($adapter)
	{
		$this->deleteFolders(
			array(
				JPATH_LIBRARIES . '/geoip',
			)
		);
	}
}
