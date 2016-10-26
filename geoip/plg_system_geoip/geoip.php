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

class PlgSystemGeoIP extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		// only in admin if geoip_update=1 is found in the url
		if (!JFactory::getApplication()->isAdmin()
			|| !JFactory::getApplication()->input->getInt('geoip_update')
		)
		{
			return;
		}

		include __DIR__ . '/helpers/update.php';
	}
}
