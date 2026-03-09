/**
 * Community Shortcode Wizard JavaScript
 *
 * Handles the shortcode wizard modal for Divi/classic editor.
 * jQuery is acceptable here (admin context).
 *
 * @package Community_CPT
 */

jQuery(document).ready(function($) {
	'use strict';

	var $overlay = $('#community-wizard-overlay');
	var $modal = $overlay.find('.community-wizard-modal');
	var focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
	var firstFocusable;
	var lastFocusable;

	/**
	 * Open the wizard modal.
	 */
	function openModal() {
		$overlay.show();

		// Setup focus trap.
		var focusable = $modal.find(focusableElements).filter(':visible');
		firstFocusable = focusable.first();
		lastFocusable = focusable.last();

		// Focus first element.
		firstFocusable.focus();

		// Prevent body scroll.
		$('body').css('overflow', 'hidden');

		// Update preview.
		updatePreview();
	}

	/**
	 * Close the wizard modal.
	 */
	function closeModal() {
		$overlay.hide();
		$('body').css('overflow', '');

		// Return focus to button.
		$('.community-shortcode-wizard-btn').focus();
	}

	/**
	 * Update the shortcode preview.
	 */
	function updatePreview() {
		var type = $('input[name="shortcode_type"]:checked').val();
		var shortcode = '[' + type;
		var attrs = [];

		if (type === 'community_grid') {
			var parentId = $('#wizard_parent_id').val();
			var columns = $('#wizard_columns').val();
			var perPage = $('#wizard_per_page').val();
			var showSearch = $('#wizard_show_search').is(':checked');
			var orderby = $('#wizard_orderby').val();
			var order = $('#wizard_order').val();
			var style = $('#wizard_style').val();

			if (parentId) {
				attrs.push('parent_id="' + parentId + '"');
			}
			if (columns !== '3') {
				attrs.push('columns="' + columns + '"');
			}
			if (perPage !== '0') {
				attrs.push('per_page="' + perPage + '"');
			}
			if (!showSearch) {
				attrs.push('show_search="false"');
			}
			if (orderby !== 'menu_order') {
				attrs.push('orderby="' + orderby + '"');
			}
			if (order !== 'ASC') {
				attrs.push('order="' + order + '"');
			}
			if (style !== 'default') {
				attrs.push('style="' + style + '"');
			}
		} else if (type === 'community_related') {
			var relatedTitle = $('#wizard_related_title').val();
			var relatedLimit = $('#wizard_related_limit').val();
			var relatedColumns = $('#wizard_related_columns').val();

			if (relatedTitle) {
				attrs.push('title="' + relatedTitle + '"');
			}
			if (relatedLimit !== '8') {
				attrs.push('limit="' + relatedLimit + '"');
			}
			if (relatedColumns !== '4') {
				attrs.push('columns="' + relatedColumns + '"');
			}
		}

		if (attrs.length > 0) {
			shortcode += ' ' + attrs.join(' ');
		}

		shortcode += ']';

		$('#wizard_preview').text(shortcode);
	}

	/**
	 * Insert shortcode into editor.
	 */
	function insertShortcode() {
		var shortcode = $('#wizard_preview').text();

		// Try to insert into editor.
		if (typeof window.send_to_editor === 'function') {
			window.send_to_editor(shortcode);
		} else if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
			wp.media.editor.insert(shortcode);
		} else {
			// Fallback: copy to clipboard.
			var temp = $('<textarea>');
			$('body').append(temp);
			temp.val(shortcode).select();
			document.execCommand('copy');
			temp.remove();
			alert('Shortcode copied to clipboard: ' + shortcode);
		}

		closeModal();
	}

	/**
	 * Toggle field visibility based on shortcode type.
	 */
	function toggleFields() {
		var type = $('input[name="shortcode_type"]:checked').val();

		$('.community-wizard-grid-options').toggle(type === 'community_grid');
		$('.community-wizard-related-options').toggle(type === 'community_related');

		updatePreview();
	}

	// Event: Open modal button.
	$(document).on('click', '.community-shortcode-wizard-btn', function(e) {
		e.preventDefault();
		openModal();
	});

	// Event: Close modal.
	$overlay.on('click', '.community-wizard-close, .community-wizard-cancel', function(e) {
		e.preventDefault();
		closeModal();
	});

	// Event: Click outside modal.
	$overlay.on('click', function(e) {
		if (e.target === this) {
			closeModal();
		}
	});

	// Event: Escape key.
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $overlay.is(':visible')) {
			closeModal();
		}
	});

	// Event: Focus trap.
	$modal.on('keydown', function(e) {
		if (e.key !== 'Tab') {
			return;
		}

		if (e.shiftKey) {
			if (document.activeElement === firstFocusable[0]) {
				e.preventDefault();
				lastFocusable.focus();
			}
		} else {
			if (document.activeElement === lastFocusable[0]) {
				e.preventDefault();
				firstFocusable.focus();
			}
		}
	});

	// Event: Insert shortcode.
	$overlay.on('click', '.community-wizard-insert', function(e) {
		e.preventDefault();
		insertShortcode();
	});

	// Event: Shortcode type change.
	$('input[name="shortcode_type"]').on('change', toggleFields);

	// Event: Any field change.
	$modal.on('change input', 'input, select', function() {
		updatePreview();
	});

	// Initialize Select2 for parent community.
	if (typeof $.fn.select2 === 'function') {
		$('#wizard_parent_id').select2({
			ajax: {
				url: communityWizard.ajaxUrl,
				dataType: 'json',
				delay: 300,
				data: function(params) {
					return {
						q: params.term,
						action: 'community_search_posts',
						nonce: communityWizard.nonce
					};
				},
				processResults: function(data) {
					return {
						results: data
					};
				},
				cache: true
			},
			placeholder: communityWizard.i18n.currentPost,
			minimumInputLength: 2,
			allowClear: true,
			width: '100%',
			dropdownParent: $modal
		});
	}

});
