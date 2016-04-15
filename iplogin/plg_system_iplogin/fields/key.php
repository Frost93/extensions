<?php
/**
 * @package         IP Login
 * @version         3.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class JFormFieldIPLogin_Key extends JFormField
{
	protected $type = 'Key';

	protected function getInput()
	{
		$this->value = trim($this->value);

		if ($this->value == '')
		{
			$this->value = $this->generateKey();
		}

		return '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value . '">';
	}

	protected function generateKey()
	{
		$chars = str_split('abcdefhjkmnpqrtuvwxy3478');
		$key   = '';

		for ($i = 0; $i < 8; $i++)
		{
			$c = $chars[array_rand($chars)];

			$key .= rand(0, 1) ? $c : strtoupper($c);
		}

		return $key;
	}
}
