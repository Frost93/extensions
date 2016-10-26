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

class JFormFieldGeoIP_Version extends JFormField
{
	protected $type = 'Version';

	protected function getInput()
	{
		$file = JPATH_LIBRARIES . '/geoip/GeoLite2-City.mmdb.date.txt';

		if (!is_file($file)
			|| !$last_date = file_get_contents($file)
		)
		{
			return '';
		}

		return '<span class="label">' . JHtml::_('date', $last_date, JText::_('DATE_FORMAT_LC3')) . '</span>';
	}
}
