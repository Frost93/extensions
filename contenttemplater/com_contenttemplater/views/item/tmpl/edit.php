<?php
/**
 * @package         Content Templater
 * @version         6.2.6
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');
require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

RLFunctions::loadLanguage('com_content', JPATH_ADMINISTRATOR);

$user    = JFactory::getUser();
$contact = new stdClass;

$db         = JFactory::getDbo();
$table_name = $db->getPrefix() . $this->config->contact_table;

if (in_array($table_name, $db->getTableList()))
{
	$query = 'SHOW FIELDS FROM ' . $db->quoteName($table_name);
	$db->setQuery($query);
	$columns = $db->loadColumn();

	if (in_array('misc', $columns))
	{
		$query = $db->getQuery(true)
			->select('c.misc')
			->from('#__' . $this->config->contact_table . ' as c')
			->where('c.user_id = ' . (int) $user->id);
		$db->setQuery($query);
		$contact = $db->loadObject();
	}
}

RLFunctions::script('regularlabs/script.min.js');
RLFunctions::stylesheet('regularlabs/style.min.css');
?>

<form action="<?php echo JRoute::_('index.php?option=com_contenttemplater'); ?>" method="post"
      name="adminForm" id="item-form" class="form-validate form-horizontal">
	<div class="row-fluid">
		<div class="span9 span-md-8">
			<ul class="nav nav-tabs">
				<li class="active">
					<a href="#editor" data-toggle="tab"><?php echo JText::_('RL_CONTENT'); ?></a>
				</li>
				<li>
					<a href="#contentsettings" data-toggle="tab"><?php echo JText::_('CT_CONTENT_SETTINGS'); ?></a>
				</li>
				<li>
					<a href="#publishing" data-toggle="tab"><?php echo JText::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></a>
				</li>
				<li>
					<a href="#assignments" data-toggle="tab"><?php echo JText::_('RL_PUBLISHING_ASSIGNMENTS'); ?></a>
				</li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="editor">
					<?php echo $this->render($this->item->form, '-content'); ?>
					<div class="row-fluid">
						<fieldset>
							<legend><?php echo JText::_('CT_DYNAMIC_TAGS'); ?></legend>
							<p><?php echo JText::_('CT_DYNAMIC_TAGS_DESC'); ?></p>

							<table class="table table-striped">
								<thead>
									<tr>
										<th><?php echo JText::_('CT_SYNTAX'); ?></th>
										<th class="left">
											<span><?php echo JText::_('JGLOBAL_DESCRIPTION'); ?></span></th>
										<th class="left">
											<span><?php echo JText::_('CT_OUTPUT_EXAMPLE'); ?></span></th>
										<th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td style="font-family:monospace">[[user:id]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_USER_ID'); ?></td>
										<td><?php echo $user->id; ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[user:username]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_USER_USERNAME'); ?></td>
										<td><?php echo $user->username; ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[user:name]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_USER_NAME'); ?></td>
										<td><?php echo $user->name; ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[user:...]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_USER_OTHER'); ?></td>
										<td><?php echo isset($contact->misc) ? $contact->misc : ''; ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[date:...]]</td>
										<td><?php echo JText::sprintf('CT_DYNAMIC_TAG_DATE', '<a rel="{handler: \'iframe\', size:{x:window.getSize().x-100, y: window.getSize().y-100}}" href="http://www.php.net/manual/function.strftime.php" class="modal">', '</a>', '<span style="font-family:monospace">[[date: %A, %d %B %Y]]</span>'); ?></td>

										<td><?php echo strftime('%A, %d %B %Y'); ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[random:...-...]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_RANDOM'); ?></td>
										<td><?php echo rand(0, 100); ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[text:MY_STRING]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_TEXT'); ?></td>
										<td><?php echo JText::_('CT_MY_STRING'); ?></td>
									</tr>
									<tr>
										<td style="font-family:monospace">[[template:...]]</td>
										<td><?php echo JText::_('CT_DYNAMIC_TAG_TEMPLATE'); ?></td>
										<td>
											<em><?php echo JText::_('RL_ONLY_AVAILABLE_IN_PRO'); ?></em>
										</td>
									</tr>
								</tbody>
							</table>
						</fieldset>
					</div>
				</div>

				<div class="tab-pane" id="contentsettings">
					<?php echo $this->render($this->item->form, '-content-settings'); ?>
					<ul class="nav nav-tabs">
						<li class="active">
							<a href="#content-general" data-toggle="tab"><?php echo JText::_('COM_CONTENT_ARTICLE_DETAILS'); ?></a>
						</li>
						<li>
							<a href="#content-publishing" data-toggle="tab"><?php echo JText::_('COM_CONTENT_FIELDSET_PUBLISHING'); ?></a>
						</li>
						<li>
							<a href="#content-images" data-toggle="tab"><?php echo JText::_('COM_CONTENT_FIELD_IMAGE_OPTIONS'); ?></a>
						</li>
						<li>
							<a href="#content-basic" data-toggle="tab"><?php echo JText::_('COM_CONTENT_ATTRIBS_FIELDSET_LABEL'); ?></a>
						</li>
						<li>
							<a href="#content-editorconfig" data-toggle="tab"><?php echo JText::_('COM_CONTENT_SLIDER_EDITOR_CONFIG'); ?></a>
						</li>
						<li>
							<a href="#content-customfields" data-toggle="tab"><?php echo JText::_('CT_CUSTOM_FIELDS'); ?></a>
						</li>
					</ul>

					<div class="tab-content">
						<div class="tab-pane active" id="content-general">
							<div class="row-fluid">
								<div class="span8 span-md-12 span-lg-12">
									<fieldset>
										<?php echo $this->render($this->item->form, '-content-general-left'); ?>
									</fieldset>
								</div>
								<div class="span4 span-md-12 span-lg-12">
									<fieldset class="form-vertical">
										<?php echo $this->render($this->item->form, '-content-general-right'); ?>
									</fieldset>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="content-publishing">
							<div class="row-fluid">
								<div class="span6 span-md-12 span-lg-12">
									<fieldset>
										<?php echo $this->render($this->item->form, '-content-publishing-left'); ?>
									</fieldset>
								</div>
								<div class="span6 span-md-12 span-lg-12">
									<fieldset>
										<?php echo $this->render($this->item->form, '-content-publishing-right'); ?>
									</fieldset>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="content-images">
							<div class="row-fluid">
								<div class="span6 span-md-12 span-lg-12">
									<fieldset>
										<?php echo $this->render($this->item->form, '-content-images'); ?>
									</fieldset>
								</div>
								<div class="span6 span-md-12 span-lg-12">
									<fieldset>
										<?php echo $this->render($this->item->form, '-content-urls'); ?>
									</fieldset>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="content-basic">
							<fieldset>
								<?php echo $this->render($this->item->form, '-content-basic'); ?>
							</fieldset>
						</div>
						<div class="tab-pane" id="content-editorconfig">
							<fieldset>
								<?php echo $this->render($this->item->form, '-content-editorconfig'); ?>
							</fieldset>
						</div>
						<div class="tab-pane" id="content-customfields">
							<fieldset>
								<?php echo $this->render($this->item->form, '-content-customfields'); ?>
							</fieldset>
						</div>
					</div>
				</div>

				<div class="tab-pane" id="publishing">
					<div class="row-fluid">
						<div class="span6 span-md-12 span-lg-12">
							<fieldset>
								<legend><?php echo JText::_('CT_EDITOR_BUTTON_LIST'); ?></legend>
								<?php echo $this->render($this->item->form, 'publishing-button'); ?>
							</fieldset>
						</div>
						<div class="span6 span-md-12 span-lg-12">
							<fieldset>
								<legend><?php echo JText::_('CT_LOAD_BY_DEFAULT'); ?></legend>
								<?php echo $this->render($this->item->form, 'publishing-load'); ?>
							</fieldset>

							<fieldset>
								<legend><?php echo JText::_('CT_LOAD_BY_URL'); ?></legend>
								<?php echo $this->render($this->item->form, 'publishing-url'); ?>
							</fieldset>
						</div>
					</div>
				</div>

				<div class="tab-pane" id="assignments">
					<fieldset>
						<?php echo $this->render($this->item->form, 'assignments'); ?>
					</fieldset>
				</div>
			</div>
		</div>
		<div class="span3 span-md-4 form-vertical">
			<h4><?php echo JText::_('JDETAILS'); ?></h4>
			<hr>
			<fieldset>
				<?php echo $this->render($this->item->form, 'details'); ?>
			</fieldset>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>">
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
			alert("<?php echo JText::_('CT_THE_ITEM_MUST_HAVE_A_NAME', true); ?>");
		} else {
			Joomla.submitform(task, f);
		}
	}
</script>
