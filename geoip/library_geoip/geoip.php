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

require __DIR__ . '/vendor/autoload.php';

use GeoIp2\Database\Reader;

class GeoIp
{
	var $ip      = '';
	var $reader  = null;
	var $records = array();
	var $data    = array();

	public function __construct($ip = '')
	{
		$this->ip = $ip ?: $_SERVER['REMOTE_ADDR'];

		if ($this->ip == '127.0.0.1')
		{
			$this->ip = '';
		}
	}

	public function get($fields = null, $force_new = false)
	{
		if (!$fields = $this->initFields($fields))
		{
			return false;
		}

		$record = $this->getRecordData();

		if (empty($record))
		{
			return false;
		}

		$data = array();

		foreach ($fields as $field)
		{
			if (!$force_new && isset($this->data[$field]))
			{
				$data[$field] = $this->data[$field];
				continue;
			}

			switch ($field)
			{
				case'continentCode':
					$data[$field] = $record->continent->code;
					break;
				case 'continent':
					$data[$field] = $record->continent->name;
					break;
				case 'countryCode':
					$data[$field] = $record->country->isoCode;
					break;
				case 'country':
					$data[$field] = $record->country->name;
					break;
				case 'regionCode':
					$data[$field] = $record->mostSpecificSubdivision->isoCode;
					break;
				case 'region':
					$data[$field] = $record->mostSpecificSubdivision->name;
					break;
				case 'regionCodes':
					$data[$field] = array();
					foreach ($record->subdivisions as $region)
					{
						$data[$field][] = $region->isoCode;
					}
					break;
				case 'regions':
					$data[$field] = array();
					foreach ($record->subdivisions as $region)
					{
						$data[$field][] = $region->name;
					}
					break;
				case 'postalCode':
					$data[$field] = $record->postal->code;
					break;
				case 'city':
					$data[$field] = $record->city->name;
					break;
				case 'latitude':
					$data[$field] = $record->location->latitude;
					break;
				case 'longitude':
					$data[$field] = $record->location->longitude;
					break;
				case 'ip':
					$data[$field] = $record->traits->ipAddress;
					break;
			}
		}

		$this->data = array_merge($this->data, $data);

		return (object) $this->data;
	}

	public function getReader()
	{
		if (!is_null($this->reader))
		{
			return $this->reader;
		}

		$this->reader = new Reader(__DIR__ . '/GeoLite2-City.mmdb');

		return $this->reader;
	}

	public function getRecordData()
	{
		if (empty($this->ip))
		{
			return false;
		}

		if (isset($this->records[$this->ip]))
		{
			return $this->records[$this->ip];
		}

		$this->records[$this->ip] = $this->getReader()->city($this->ip);

		return $this->records[$this->ip];
	}

	private function initFields($fields = null)
	{
		if (is_null($fields))
		{
			return array('continentCode', 'countryCode', 'regionCodes', 'postalCode');
		}

		if (is_string($fields))
		{
			return array($fields);
		}

		if (!is_array($fields))
		{
			return false;
		}

		return $fields;
	}
}
