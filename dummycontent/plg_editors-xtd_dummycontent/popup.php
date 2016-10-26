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

require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
$parameters = RLParameters::getInstance();
$params     = $parameters->getPluginParams('dummycontent');

if (JFactory::getApplication()->isSite())
{
	if (!$params->enable_frontend)
	{
		JError::raiseError(403, JText::_("ALERTNOTAUTH"));
	}
}

$class = new PlgButtonDummyContentPopup;
$class->render($params);

class PlgButtonDummyContentPopup
{
	var $params  = null;
	var $helpers = array();

	function render(&$params)
	{
		$this->params = $params;

		if (JFactory::getApplication()->input->getInt('generate_content'))
		{
			$this->generateContent();

			return;
		}
		$this->showForm();
	}

	function generateContent()
	{
		require_once JPATH_PLUGINS . '/system/dummycontent/helpers/helpers.php';
		$this->helpers = PlgSystemDummyContentHelpers::getInstance($this->params);

		$type  = JFactory::getApplication()->input->getCmd('type', $this->params->type);
		$count = JFactory::getApplication()->input->getInt('count', isset($this->params->{$type . '_count'}) ? $this->params->{$type . '_count'} : 5);


		switch ($type)
		{
			case 'paragraphs':
				$text = $this->helpers->get('text')->paragraphs((int) $count);
				break;
			case 'sentences':
				$text = $this->helpers->get('text')->sentences((int) $count);
				break;
			case 'words':
				$text = $this->helpers->get('text')->words((int) $count);
				break;
			case 'list':
				$type = JFactory::getApplication()->input->getCmd('list_type', $this->params->list_type);
				$text = $this->helpers->get('text')->alist((int) $count, $type);
				break;
			case 'title':
				$text = $this->helpers->get('text')->title((int) $count);
				break;
			case 'email':
				$text = $this->helpers->get('text')->email();
				break;
			case 'image':
				$text = $this->generateImage();
				break;
			case 'kitchenSink':
			default:
				$text = $this->helpers->get('text')->kitchenSink();
				break;
		}

		echo $text;
		die();
	}

	function generateImage()
	{
		$options          = new stdClass;
		$options->width   = JFactory::getApplication()->input->getCmd('width', $this->params->image_width);
		$options->height  = JFactory::getApplication()->input->getCmd('height', $this->params->image_height);
		$options->service = JFactory::getApplication()->input->getCmd('image_service', $this->params->image_service);

		return $this->helpers->get('image')->render($options);
	}

	function showForm()
	{
		// load the admin language file
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
		RLFunctions::loadLanguage('plg_system_regularlabs');
		RLFunctions::loadLanguage('plg_editors-xtd_dummycontent');
		RLFunctions::loadLanguage('plg_system_dummycontent');

		RLFunctions::script('regularlabs/script.min.js');
		RLFunctions::stylesheet('regularlabs/popup.min.css');
		RLFunctions::stylesheet('regularlabs/style.min.css');
		?>
		<div class="header">
			<div class="container-fluid">
				<h1 class="page-title">
					<span class="icon-reglab icon-dummycontent"></span>
					<?php echo JText::_('DUMMY_CONTENT'); ?>
				</h1>
			</div>
		</div>

		<div class="subhead">
			<div class="container-fluid">
				<div class="row-fluid">
					<div class="btn-toolbar" id="toolbar">
						<div class="btn-wrapper">
							<div class="btn-group" id="toolbar-apply">
								<button href="#" onclick="RegularLabsDummyContent.insertContent();return false;" class="btn btn-small btn-success">
									<span class="icon-reglab icon-dummycontent"></span> <?php echo JText::_('DC_INSERT_CONTENT') ?>
								</button>
							</div>

							<div class="btn-group" id="toolbar-apply">
								<button href="#" onclick="RegularLabsDummyContent.insertTag();return false;" class="btn btn-small btn-primary">
									{...} <?php echo JText::_('DC_INSERT_TAG') ?>
								</button>
							</div>

							<div class="btn-group" id="toolbar-cancel">
								<button href="#" onclick="window.parent.SqueezeBox.close();" class="btn btn-small">
									<span class="icon-cancel "></span> <?php echo JText::_('JCANCEL') ?>
								</button>
							</div>

							<?php if (JFactory::getApplication()->isAdmin() && JFactory::getUser()->authorise('core.admin', 1)) : ?>
								<div class="btn-wrapper" id="toolbar-options">
									<button onclick="window.open('index.php?option=com_plugins&filter_folder=system&filter_search=dummy content');"
									        class="btn btn-small">
										<span class="icon-options"></span> <?php echo JText::_('JOPTIONS') ?>
									</button>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div style="margin-bottom: 20px"></div>

		<div class="container-fluid container-main">
			<form action="" method="post" name="adminForm" id="adminForm">

				<div class="form-vertical">
					<div class="control-group">
						<label id="type-lbl" for="type" class="control-label">
							<?php echo JText::_('DC_INSERT'); ?>
						</label>

						<div class="controls">
							<fieldset id="type" class="radio">
								<input type="radio" id="type_kitchenSink" class="toggler" name="type"
								       value="kitchenSink" <?php echo $this->params->type == 'kitchenSink' ? 'checked="checked"' : ''; ?>>
								<label for="type_kitchenSink"><?php echo JText::_('DC_KITCHENSINK'); ?></label>

								<input type="radio" id="type_paragraphs" class="toggler" name="type"
								       value="paragraphs" <?php echo $this->params->type == 'paragraphs' ? 'checked="checked"' : ''; ?>>
								<label for="type_paragraphs"><?php echo JText::_('DC_PARAGRAPHS'); ?></label>

								<input type="radio" id="type_sentences" class="toggler" name="type"
								       value="sentences" <?php echo $this->params->type == 'sentences' ? 'checked="checked"' : ''; ?>>
								<label for="type_sentences"><?php echo JText::_('DC_SENTENCES'); ?></label>

								<input type="radio" id="type_words" class="toggler" name="type"
								       value="words" <?php echo $this->params->type == 'words' ? 'checked="checked"' : ''; ?>>
								<label for="type_words"><?php echo JText::_('DC_WORDS'); ?></label>

								<input type="radio" id="type_list" class="toggler" name="type"
								       value="list" <?php echo $this->params->type == 'list' ? 'checked="checked"' : ''; ?>>
								<label for="type_list"><?php echo JText::_('DC_LIST'); ?></label>

								<input type="radio" id="type_title" class="toggler" name="type" value="title">
								<label for="type_title"><?php echo JText::_('JGLOBAL_TITLE'); ?></label>

								<input type="radio" id="type_email" class="toggler" name="type" value="email">
								<label for="type_email"><?php echo JText::_('JGLOBAL_EMAIL'); ?></label>

							</fieldset>
						</div>
					</div>

					<div rel="type_paragraphs" class="toggle_div" style="display:none;">
						<div class="control-group">
							<label id="paragraphs_count-lbl" for="paragraphs_count" class="control-label">
								<?php echo JText::_('DC_PARAGRAPHS_COUNT'); ?>
							</label>

							<div class="controls">
								<select name="paragraphs_count" id="paragraphs_count" class="input-mini">
									<option
										value="1" <?php echo $this->params->paragraphs_count == 1 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J1'); ?></option>
									<option
										value="2" <?php echo $this->params->paragraphs_count == 2 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J2'); ?></option>
									<option
										value="3" <?php echo $this->params->paragraphs_count == 3 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J3'); ?></option>
									<option
										value="4" <?php echo $this->params->paragraphs_count == 4 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J4'); ?></option>
									<option
										value="5" <?php echo $this->params->paragraphs_count == 5 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J5'); ?></option>
									<option
										value="6" <?php echo $this->params->paragraphs_count == 6 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J6'); ?></option>
									<option
										value="7" <?php echo $this->params->paragraphs_count == 7 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J7'); ?></option>
									<option
										value="8" <?php echo $this->params->paragraphs_count == 8 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J8'); ?></option>
									<option
										value="9" <?php echo $this->params->paragraphs_count == 9 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J9'); ?></option>
									<option
										value="10" <?php echo $this->params->paragraphs_count == 10 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J10'); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div rel="type_sentences" class="toggle_div" style="display:none;">
						<div class="control-group">
							<label id="paragraphs_count-lbl" for="sentences_count" class="control-label">
								<?php echo JText::_('DC_SENTENCES_COUNT'); ?>
							</label>

							<div class="controls">
								<input type="text" name="sentences_count" id="sentences_count" class="input-mini"
								       value="<?php echo $this->params->sentences_count; ?>">
							</div>
						</div>
					</div>

					<div rel="type_words" class="toggle_div" style="display:none;">
						<div class="control-group">
							<label id="paragraphs_count-lbl" for="words_count" class="control-label">
								<?php echo JText::_('DC_WORDS_COUNT'); ?>
							</label>

							<div class="controls">
								<input type="text" name="words_count" id="words_count" class="input-mini" value="<?php echo $this->params->words_count; ?>">
							</div>
						</div>
					</div>

					<div rel="type_list" class="toggle_div" style="display:none;">
						<div class="control-group">
							<label id="list_count-lbl" for="words_count" class="control-label">
								<?php echo JText::_('DC_LIST_ITEM_COUNT'); ?>
							</label>

							<div class="controls">
								<select name="list_count" id="list_count">
									<option
										value="0" <?php echo !$this->params->list_count ? 'selected="selected"' : ''; ?>><?php echo JText::_('RL_RANDOM'); ?></option>
									<option
										value="2" <?php echo $this->params->list_count == 2 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J2'); ?></option>
									<option
										value="3" <?php echo $this->params->list_count == 3 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J3'); ?></option>
									<option
										value="4" <?php echo $this->params->list_count == 4 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J4'); ?></option>
									<option
										value="5" <?php echo $this->params->list_count == 5 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J5'); ?></option>
									<option
										value="6" <?php echo $this->params->list_count == 6 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J6'); ?></option>
									<option
										value="7" <?php echo $this->params->list_count == 7 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J7'); ?></option>
									<option
										value="8" <?php echo $this->params->list_count == 8 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J8'); ?></option>
									<option
										value="9" <?php echo $this->params->list_count == 9 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J9'); ?></option>
									<option
										value="10" <?php echo $this->params->list_count == 10 ? 'selected="selected"' : ''; ?>><?php echo JText::_('J10'); ?></option>
								</select>
							</div>
						</div>
						<div class="control-group">
							<label id="list_type-lbl" for="words_count" class="control-label">
								<?php echo JText::_('DC_LISTTYPE'); ?>
							</label>

							<div class="controls">
								<select name="list_type" id="list_type">
									<option
										value="" <?php echo !$this->params->list_type ? 'selected="selected"' : ''; ?>><?php echo JText::_('RL_RANDOM'); ?></option>
									<option
										value="ol" <?php echo $this->params->list_type == 'ol' ? 'selected="selected"' : ''; ?>><?php echo JText::_('DC_ORDERED'); ?></option>
									<option
										value="ul" <?php echo $this->params->list_type == 'ul' ? 'selected="selected"' : ''; ?>><?php echo JText::_('DC_UNORDERED'); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div rel="type_title" class="toggle_div" style="display:none;">
						<div class="control-group">
							<label id="paragraphs_count-lbl" for="title_count" class="control-label">
								<?php echo JText::_('DC_TITLE_WORD_COUNT'); ?>
							</label>

							<div class="controls">
								<input type="text" name="title_count" id="title_count" class="input-mini" value="5">
							</div>
						</div>
					</div>

				</div>

				<div style="clear:both;"></div>

				<input type="hidden" name="name" value="<?php echo JFactory::getApplication()->input->getString('name', 'text'); ?>">
			</form>
		</div>

		<script type="text/javascript">
			(function($) {
				RegularLabsDummyContent = {
					insertContent: function() {
						var params = [];
						params.push('type=' + $('input[name="type"]:checked').val());

						switch ($('input[name="type"]:checked').val()) {
							case 'paragraphs':
								params.push('count=' + $('[name="paragraphs_count"]').val());
								break;
							case 'sentences':
								params.push('count=' + $('[name="sentences_count"]').val());
								break;
							case 'words':
								params.push('count=' + $('[name="words_count"]').val());
								break;
							case 'list':
								params.push('count=' + $('[name="list_count"]').val());
								params.push('list_type=' + $('[name="list_type"]').val());
								break;
							case 'title':
								params.push('count=' + $('[name="title_count"]').val());
								break;
						}


						var url = 'index.php?rl_qp=1&folder=plugins.editors-xtd.dummycontent&file=popup.php&generate_content=1&' + params.join('&');
						RegularLabsScripts.loadajax(url, 'window.parent.jInsertEditorText( data, \'<?php echo JFactory::getApplication()->input->getString('name', 'text'); ?>\' );window.parent.SqueezeBox.close();');

					},

					insertTag: function() {
						var params = [];

						switch ($('input[name="type"]:checked').val()) {
							case 'kitchenSink':
								if ('<?php echo $this->params->type; ?>' != 'kitchen') {
									params.push('kitchen');
								}
								break;
							case 'paragraphs':
								if ('<?php echo $this->params->type; ?>' != 'paragraphs' || $('[name="paragraphs_count"]').val() != <?php echo $this->params->paragraphs_count; ?>) {
									params.push('p=' + $('[name="paragraphs_count"]').val());
								}
								break;
							case 'sentences':
								if ('<?php echo $this->params->type; ?>' != 'sentences' || $('[name="sentences_count"]').val() != <?php echo $this->params->sentences_count; ?>) {
									params.push('s=' + $('[name="sentences_count"]').val());
								}
								break;
							case 'words':
								if ('<?php echo $this->params->type; ?>' != 'words' || $('[name="words_count"]').val() != <?php echo $this->params->words_count; ?>) {
									params.push('w=' + $('[name="words_count"]').val());
								}
								break;
							case 'list':
								if ('<?php echo $this->params->type; ?>' != 'list' || $('[name="list_count"]').val() != <?php echo $this->params->list_count; ?>) {
									params.push('l=' + $('[name="words_count"]').val());
								}
								if ('<?php echo $this->params->type; ?>' != 'list' || $('[name="list_type"]').val() != '<?php echo $this->params->list_type; ?>') {
									params.push('type=' + $('[name="list_type"]').val());
								}
								break;
							case 'title':
								params.push('title');
								break;
							case 'email':
								params.push('email');
								break;
						}


						str = '{' + ('<?php echo $this->params->tag; ?> ' + params.join('|')).trim() + '}';

						window.parent.jInsertEditorText(str, '<?php echo JFactory::getApplication()->input->getString('name', 'text'); ?>');
						window.parent.SqueezeBox.close();
					},

					initDivs: function() {
						$('.toggler').click(function() {
							RegularLabsDummyContent.toggleDivs();
						});
						RegularLabsDummyContent.toggleDivs();
					},

					toggleDivs: function() {
						$('div.toggle_div').each(function(i, el) {
							el = $(el);
							if (el.attr('rel').substr(0, 4) == 'not_') {
								if ($('#' + el.attr('rel').substr(4) + ':checked').val()) {
									el.slideUp();
								} else {
									el.slideDown();
								}
							} else {
								if ($('#' + el.attr('rel') + ':checked').val()) {
									el.slideDown();
								} else {
									el.slideUp();
								}
							}
						});
					}
				}

				$(document).ready(function() {
					RegularLabsDummyContent.initDivs();
				});
			})
			(jQuery);
		</script>
		<?php
	}
}
