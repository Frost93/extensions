<?php
/**
 * @package         Snippets
 * @version         5.0.4
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

RLFunctions::loadLanguage('com_content', JPATH_ADMINISTRATOR);

RLFunctions::script('regularlabs/script.min.js');
RLFunctions::stylesheet('regularlabs/style.min.css');
?>

<form action="<?php echo JRoute::_('index.php?option=com_snippets&id=' . ( int ) $this->item->id); ?>" method="post"
      name="adminForm" id="item-form" class="form-validate form-horizontal">

	<div class="row-fluid">
		<div class="span12">
			<?php echo $this->render($this->item->form, 'details', JText::_('JDETAILS')); ?>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<?php echo $this->render($this->item->form, '-content', JText::_('RL_CONTENT')); ?>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo JHtml::_('form.token'); ?>
</form>

<script language="javascript" type="text/javascript">
	Joomla.submitbutton = function(task) {
		var f = document.getElementById('item-form');
		if (task == 'item.cancel') {
			Joomla.submitform(task, f);
			return;
		}

		// do field validation
		if (f['jform[name]'].value.trim() == "") {
			alert("<?php echo JText::_('SNP_THE_ITEM_MUST_HAVE_A_NAME', true); ?>");
		} else if (f['jform[alias]'].value.trim() == "") {
			alert("<?php echo JText::_('SNP_THE_ITEM_MUST_HAVE_AN_ID', true); ?>");
		} else {
			Joomla.submitform(task, f);
		}
	}
</script>
