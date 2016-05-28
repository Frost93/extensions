/**
 * @package         Tabs
 * @version         6.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

var RegularLabsTabsPopup = null;

(function($) {
	"use strict";

	$(document).ready(function() {
		setTimeout(function() {
			RegularLabsTabsPopup.init();
		}, 1000);
	});

	RegularLabsTabsPopup = {
		texts   : [
			'alias', 'mainclass', 'class',
		],
		booleans: [
			'color_inactive_handles', 'outline_handles', 'outline_content',
		],

		init: function() {
			var self = this;

			$('input[name$="[open]"][value="1"]').click(function() {
				self.setDefault(this);
			});

			var has_default = false;
			$('input[name$="[open]"][value="1"]').each(function($i, el) {
				if ($(el).attr('checked')) {
					$('.' + $(el).parent().attr('id') + '_icon').show();
					has_default = true;
				}
			});

			if (!has_default) {
				this.setRadioOption('tab_1[open]', 1);
				$('.' + $('input[name="tab_1[open]"][value="1"]').parent().attr('id') + '_icon').show();
			}

			this.fillFromSelection();

			$('.reglab-overlay').css('cursor', '').fadeOut();
		},

		setDefault: function(el) {
			$('.' + $(el).parent().attr('id') + '_icon').show();
			this.closeOtherDefaults(el);
		},

		closeOtherDefaults: function(el) {
			var self = this;
			var $el  = $(el);

			$('input[name$="[open]"][value="1"]').each(function($i, input) {
				if ($(input).attr('name') == $el.attr('name')) {
					return;
				}

				$('.' + $(input).parent().attr('id') + '_icon').hide();
				self.setRadioOption($(input).attr('name'), 0);
			});
		},

		fillFromSelection: function() {
			var self      = this;
			var selection = this.getSelection();

			if (!selection) {
				return;
			}

			var url = 'index.php?rl_qp=1&folder=plugins.editors-xtd.tabs&file=data.php&format=json&selection=' + encodeURIComponent(selection);

			RegularLabsScripts.loadajax(
				url,
				'RegularLabsTabsPopup.fillFromData(data)', 'RegularLabsTabsPopup.fillFromData(null)',
				'', 5, 'json'
			);
		},

		fillFromData: function(data) {
			var self = this;
			var form = document.getElementById('tabsForm');

			$.each(data, function(i, item) {
				var id    = 'tab_' + (i + 1);
				var extra = [];

				$.each(item, function(key, value) {
					if (key == 'content') {
						$('#' + id + '_content').html(value).show();
						return;
					}

					var input = $('input[name="' + id + '[' + key + ']"],select[name="' + id + '[' + key + ']"]');

					if (input.length < 1) {
						extra.push(key + '="' + (value + '').replace(/"/g, '\\"') + '"');
						return;
					}

					if (typeof(value) === "boolean") {
						value = value ? '1' : '0';
					}

					switch (true) {
						case (input.attr('type') == 'radio'):
							self.setRadioOption(input.attr('name'), value);
							break;

						case (input.prop('tagName') == 'SELECT'):
							self.setSelectOption(input.attr('name'), value);
							break;

						default:
							if (key == 'slideshow') {
								self.setRadioOption('tab_1[slideshow_radio]', 1);
								value = value == 1 ? '' : value;
							}

							form[$(input).attr('id')].value = value;
							break;
					}

				});

				if (extra.length) {
					form[id + '_extra'].value = extra.join(' ');
				}
			});
		},

		getSelection: function() {
			var editor_textarea = window.parent.document.getElementById(tabs_editorname);
			if (!editor_textarea) {
				return false;
			}

			var iframes = editor_textarea.parentNode.getElementsByTagName('iframe');
			if (!iframes.length) {
				return false;
			}

			var editor_frame  = iframes[0];
			var contentWindow = editor_frame.contentWindow;
			var selection     = '';

			if (typeof contentWindow.getSelection != "undefined") {
				var sel = contentWindow.getSelection();
				if (sel.rangeCount) {
					var container = contentWindow.document.createElement("div");
					var len       = sel.rangeCount;
					for (var i = 0; i < len; ++i) {
						container.appendChild(sel.getRangeAt(i).cloneContents());
					}
					selection = container.innerHTML;
				}
			} else if (typeof contentWindow.document.selection != "undefined") {
				if (contentWindow.document.selection.type == "Text") {
					selection = contentWindow.document.selection.createRange().htmlText;
				}
			}

			return selection;
		},

		insertText: function() {
			var self = this;
			var form = document.getElementById('tabsForm');

			var html      = '';
			var nested_id = $('input[name="tab_1[nested]"][value="1"]').attr('checked') ? '-' + form['tab_1[nested_id]'].value.trim() : '';

			if (form['tab_1[title]'].value.trim() == '') {
				alert(window['tabs_error_empty_title']);

				return false;
			}

			var first = true;
			$('.tab-pane').each(function($i, el) {
				var parameters = {};

				var title = form[el.id + '[title]'].value.trim();

				if (title == '') {
					return;
				}

				parameters.title = title;

				var open_yes = $('input[name="' + el.id + '[open]"][value="1"]');

				if (!first && open_yes.attr('checked')) {
					parameters.open = true;
				}


				$.each(self.texts, function(b, name) {
					var field_name = el.id + '[' + name + ']';
					var text_field = $('input[name="' + field_name + '"]');

					if (text_field.val()) {
						parameters[name] = text_field.val();
					}
				});

				$.each(self.booleans, function(b, name) {
					var field_name = el.id + '[' + name + ']';

					var input_default = $('input[name="' + field_name + '"][value=""]');
					var input_no      = $('input[name="' + field_name + '"][value="0"]');
					var input_yes     = $('input[name="' + field_name + '"][value="1"]');

					if (input_default.attr('checked')) {
						return;
					}

					if (input_default.length && input_no.attr('checked')) {
						parameters[name] = 'false';
						return;
					}

					if (input_yes.attr('checked')) {
						parameters[name] = 'true';
					}
				});


				var content = $('#' + el.id + '_content').html().trim();

				if (content == '') {
					content = tabs_content_placeholder;
				}

				var params = [];
				if (Object.keys(parameters).length < 2) {
					params.push(self.escape(parameters.title));
				} else {
					for (var key in parameters) {
						params.push(key + '="' + self.escape(parameters[key]) + '"');
					}
				}


				html += '<p>' + tabs_tag_characters[0] + tabs_tag_open + nested_id + tabs_tag_delimiter;
				html += params.join(' ');
				html += tabs_tag_characters[1] + '</p>';
				html += content ? content : '<p></p>';

				first = false;
			});

			if (html == '') {
				alert(window['tabs_error_empty_title']);

				return false;
			}

			html += '<p>' + tabs_tag_characters[0] + '/' + tabs_tag_close + nested_id + tabs_tag_characters[1] + '</p>';

			window.parent.jInsertEditorText(html, tabs_editorname);

			return true;
		},

		setRadioOption: function(name, value) {
			var inputs = $('input[name="' + name + '"]');
			var input  = $('input[name="' + name + '"][value="' + value + '"]');

			$('label[for="' + input.attr('id') + '"]').click();
			inputs.attr('checked', false);
			input.attr('checked', true).click();
		},

		setSelectOption: function(name, value) {
			var self = this;

			var select = $('select[name="' + name + '"]');
			var option = $('select[name="' + name + '"] option[value="' + value + '"]');

			if (!option.length) {
				var options = $('select[name="' + name + '"] option');
				$.each(options, function() {
					var text = self.toSimpleValue($(this).text());

					if (self.toSimpleValue(value) != text) {
						return;
					}

					option = $(this);
					return false;
				});
			}

			if (!option.length) {
				return;
			}

			select.attr('value', value).click();
			option.attr('selected', true).click();
		},

		toTextValue: function(str) {
			return (str + '').replace(/^[\s-]*/, '').trim();
		},

		toSimpleValue: function(str) {
			return (str + '').toLowerCase().replace(/[^0-9a-z]/g, '').trim();
		},

		preg_quote: function(str) {
			return (str + '').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!<>\|\:])/g, '\\$1');
		},

		escape: function(str) {
			return (str + '').replace(/([\"])/g, '\\$1');
		}
	}
})
(jQuery);
