<?php
/**
 * @package         ReReplacer
 * @version         7.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Item Table
 */
class ReReplacerTableItem extends JTable
{
	/**
	 * Constructor
	 *
	 * @param    object    Database object
	 *
	 * @return    void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__rereplacer', 'id', $db);
	}

	/**
	 * Overloaded check function
	 *
	 * @return boolean
	 */
	public function check()
	{
		$this->name   = trim($this->name);
		$this->search = trim($this->search);

		// Check for valid name
		if (empty($this->name))
		{
			$this->setError(JText::_('RR_THE_ITEM_MUST_HAVE_A_NAME'));

			return false;
		}

		// Check for valid search
		if (strpos($this->params, '"use_xml":"1"') !== false)
		{
			if (strpos($this->params, '"xml":""') !== false)
			{
				$this->setError(JText::_('RR_THE_ITEM_MUST_HAVE_AN_XML_FILE'));

				return false;
			}

			return true;
		}

		if (trim($this->search) == '')
		{
			$this->setError(JText::_('RR_THE_ITEM_MUST_HAVE_SOMETHING_TO_SEARCH_FOR'));

			return false;
		}

		if (strlen(trim($this->search)) < 3)
		{
			$this->setError(JText::sprintf('RR_THE_SEARCH_STRING_SHOULD_BE_LONGER', 2));

			return false;
		}

		return true;
	}
}
