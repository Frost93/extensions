<?php
/**
 * @package         GeoIp
 * @version         1.0.0
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
		// only in html
		if (JFactory::getDocument()->getType() != 'html'
			|| !JFactory::getApplication()->isAdmin()
			|| !JFactory::getApplication()->input->getInt('geoip_update')
		)
		{
			return;
		}

		include __DIR__ . '/helpers/update.php';
	}
}
