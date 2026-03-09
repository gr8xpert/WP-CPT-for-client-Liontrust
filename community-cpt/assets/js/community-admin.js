/**
 * Community Admin JavaScript
 *
 * Handles Select2 for related posts and other admin functionality.
 * jQuery is acceptable here (WordPress admin already loads it).
 *
 * @package Community_CPT
 */

jQuery(document).ready(function($) {
	'use strict';

	/**
	 * Initialize Select2 on related posts field.
	 */
	var $relatedField = $('#community_related_posts');

	if ($relatedField.length) {
		$relatedField.select2({
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				delay: 300,
				data: function(params) {
					return {
						q: params.term,
						action: 'community_search_posts',
						nonce: communityAdmin.nonce,
						exclude: communityAdmin.postId
					};
				},
				processResults: function(data) {
					return {
						results: data
					};
				},
				cache: true
			},
			placeholder: 'Search and select communities...',
			minimumInputLength: 2,
			allowClear: true,
			multiple: true,
			width: '100%'
		});

		// Make selected items sortable.
		var $select2Container = $relatedField.next('.select2-container');
		var $selection = $select2Container.find('.select2-selection__rendered');

		if ($selection.length && $.fn.sortable) {
			$selection.sortable({
				containment: 'parent',
				items: '.select2-selection__choice',
				tolerance: 'pointer',
				stop: function() {
					// Reorder the underlying select options to match drag order.
					var sortedIds = [];
					$selection.find('.select2-selection__choice').each(function() {
						var $choice = $(this);
						var data = $choice.data('data');
						if (data && data.id) {
							sortedIds.push(data.id);
						}
					});

					// Rebuild select options in new order.
					if (sortedIds.length > 0) {
						$relatedField.val(sortedIds).trigger('change.select2');
					}
				}
			});
		}
	}

	/**
	 * Clear all button handler.
	 */
	$('#community-clear-related').on('click', function(e) {
		e.preventDefault();
		$relatedField.val(null).trigger('change');
	});

});
