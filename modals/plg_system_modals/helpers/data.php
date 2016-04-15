<?php
/**
 * @package         Modals
 * @version         7.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemModalsHelperData
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemModalsHelpers::getInstance();
		$this->params  = $this->helpers->getParams();

	}

	public function setDataWidthHeight(&$data, $isexternal)
	{
		$this->setDataAxis($data, $isexternal, 'width');
		$this->setDataAxis($data, $isexternal, 'height');
	}

	public function setDataAxis(&$data, $isexternal, $axis = 'width')
	{
		if (!empty($data[$axis]))
		{
			return;
		}

		if ($isexternal)
		{
			$data[$axis] = $this->params->{'external' . $axis} ?: $this->params->{$axis} ?: '95%';

			return;
		}

		$data[$axis] = $this->params->{$axis} ?: $this->params->{'external' . $axis} ?: '95%';
	}


	public function flattenAttributeList($attributes)
	{
		$string = '';
		foreach ($attributes as $key => $val)
		{
			$key = trim($key);
			$val = trim($val);

			if ($key == '' || $val == '')
			{
				continue;
			}

			$string .= ' ' . $key . '="' . $val . '"';
		}

		return $string;
	}

	public function flattenDataAttributeList(&$dat)
	{
		if (isset($dat['width']))
		{
			unset($dat['externalWidth']);
		}

		if (isset($dat['height']))
		{
			unset($dat['externalHeight']);
		}

		$data = array();
		foreach ($dat as $key => $val)
		{
			if (!$str = $this->flattenDataAttribute($key, $val))
			{
				continue;
			}
			$data[] = $str;
		}

		return empty($data) ? '' : ' ' . implode(' ', $data);
	}

	public function flattenDataAttribute($key, $val)
	{
		if ($key == '' || $val == '')
		{
			return false;
		}

		if (strpos($key, 'title_') !== false || strpos($key, 'description_') !== false)
		{
			return false;
		}

		$key = $key == 'externalWidth' ? 'width' : $key;
		$key = $key == 'externalHeight' ? 'height' : $key;

		if (!in_array($key, array('title', 'iframe', 'class', 'classname'))
			&& strpos($key, 'width') === false
			&& strpos($key, 'height') === false
		)
		{
			return false;
		}

		$val = str_replace('"', '&quot;', $val);


		if (($key == 'width' || $key == 'height') && strpos($val, '%') === false)
		{
			// set param to innerWidth/innerHeight if value of width/height is a percentage
			return 'data-modal-inner-' . $key . '="' . $val . '"';
		}

		if (in_array(strtolower($key), $this->params->paramNamesLowercase))
		{
			// fix use of lowercase params that should contain uppercase letters
			$key = $this->params->paramNamesCamelcase[array_search(strtolower($key), $this->params->paramNamesLowercase)];
			$key = strtolower(preg_replace('#([A-Z])#', '-\1', $key));
		}

		return 'data-modal-' . $key . '="' . $val . '"';
	}

}
