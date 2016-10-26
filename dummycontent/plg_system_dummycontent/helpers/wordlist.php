<?php
/**
 * @package         Dummy Content
 * @version         3.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemDummyContentHelperWordlist
{
	var $list       = array();
	var $type       = 'lorem';
	var $issentence = false;

	public function setType($type)
	{


		return;
	}

	public function getList()
	{
		if (isset($this->list[$this->type]))
		{
			return $this->list[$this->type];
		}

		$path  = dirname(dirname(__FILE__)) . '/wordlists/';
		$words = file_get_contents($path . $this->type . '.txt');
		$words = trim(preg_replace('#(^|\n)\/\/ [^\n]*#s', '', $words));

		$this->list[$this->type] = explode("\n", $words);

		return $this->list[$this->type];
	}

	public function isSentenceList()
	{
		return $this->issentence;
	}
}
