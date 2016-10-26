<?php
/**
 * @package         Tooltips
 * @version         6.0.2
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

$xmlfile = __DIR__ . '/fields.xml';
?>
<div class="reglab-overlay"></div>

<div class="header">
	<h1 class="page-title">
		<span class="icon-reglab icon-tooltips"></span>
		<?php echo JText::_('INSERT_TOOLTIP'); ?>
	</h1>
</div>

<div class="subhead">
	<div class="container-fluid">
		<div class="btn-toolbar" id="toolbar">
			<div class="btn-wrapper" id="toolbar-apply">
				<button onclick="if(RegularLabsTooltipsPopup.insertText()){window.parent.SqueezeBox.close();}" class="btn btn-small btn-success">
					<span class="icon-apply icon-white"></span> <?php echo JText::_('RL_INSERT') ?>
				</button>
			</div>
			<div class="btn-wrapper" id="toolbar-cancel">
				<button onclick="if(confirm('<?php echo JText::_('RL_ARE_YOU_SURE'); ?>')){window.parent.SqueezeBox.close();}" class="btn btn-small">
					<span class="icon-cancel "></span> <?php echo JText::_('JCANCEL') ?>
				</button>
			</div>

			<?php if (JFactory::getApplication()->isAdmin() && JFactory::getUser()->authorise('core.admin', 1)) : ?>
				<div class="btn-wrapper" id="toolbar-options">
					<button onclick="window.open('index.php?option=com_plugins&filter_folder=system&filter_search=tooltips');" class="btn btn-small">
						<span class="icon-options"></span> <?php echo JText::_('JOPTIONS') ?>
					</button>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="container-fluid container-main">
	<form action="index.php" id="tooltipsForm" method="post">
		<?php
		$form = new JForm('tooltip', array('control' => 'tooltip'));
		$form->loadFile($xmlfile, 1, '//config');

		echo $form->renderFieldset('params');
		?>
	</form>
</div>
