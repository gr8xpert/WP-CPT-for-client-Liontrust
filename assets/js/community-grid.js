/**
 * Community Grid Frontend JavaScript
 *
 * Handles Ajax search, pagination, client-side filtering, and lazy loading.
 * Vanilla JavaScript only - no jQuery dependency.
 *
 * @package Community_CPT
 */

(function() {
	'use strict';

	/**
	 * Initialize all grid wrappers on page load.
	 */
	function init() {
		var wrappers = document.querySelectorAll('.community-grid-wrapper');
		wrappers.forEach(function(wrapper) {
			initGrid(wrapper);
		});
	}

	/**
	 * Initialize a single grid wrapper.
	 *
	 * @param {HTMLElement} wrapper The grid wrapper element.
	 */
	function initGrid(wrapper) {
		var config = {
			parentId: wrapper.dataset.parentId,
			perPage: parseInt(wrapper.dataset.perPage, 10) || 0,
			columns: parseInt(wrapper.dataset.columns, 10) || 3,
			style: wrapper.dataset.style || 'default',
			orderby: wrapper.dataset.orderby || 'menu_order',
			order: wrapper.dataset.order || 'ASC',
			nonce: wrapper.dataset.nonce
		};

		var searchInput = wrapper.querySelector('.community-search-input');
		var clearBtn = wrapper.querySelector('.community-search-clear');
		var grid = wrapper.querySelector('.community-grid');

		// Initialize lazy loading.
		initLazyLoad(wrapper);

		// Setup search functionality.
		if (searchInput) {
			var searchTimeout;

			searchInput.addEventListener('input', function() {
				var query = this.value.trim();

				// Show/hide clear button.
				if (clearBtn) {
					clearBtn.style.display = query.length > 0 ? 'block' : 'none';
				}

				// Debounce search.
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(function() {
					if (config.perPage === 0) {
						// Client-side filtering for non-paginated grids.
						clientFilter(grid, query);
					} else {
						// Server-side search for paginated grids.
						ajaxSearch(wrapper, config, query, 1);
					}
				}, 300);
			});

			// Clear button handler.
			if (clearBtn) {
				clearBtn.addEventListener('click', function() {
					searchInput.value = '';
					this.style.display = 'none';

					if (config.perPage === 0) {
						clientFilter(grid, '');
					} else {
						ajaxSearch(wrapper, config, '', 1);
					}
				});
			}
		}

		// Setup pagination.
		initPagination(wrapper, config);
	}

	/**
	 * Client-side filtering for non-paginated grids.
	 *
	 * @param {HTMLElement} grid  The grid element.
	 * @param {string}      query Search query.
	 */
	function clientFilter(grid, query) {
		var cards = grid.querySelectorAll('.community-grid-card');
		var noResults = grid.querySelector('.community-grid-no-results');
		var visibleCount = 0;

		query = query.toLowerCase();

		cards.forEach(function(card) {
			var title = card.dataset.title || '';
			var excerpt = card.dataset.excerpt || '';
			var matches = query === '' ||
				title.indexOf(query) !== -1 ||
				excerpt.indexOf(query) !== -1;

			card.style.display = matches ? '' : 'none';
			if (matches) {
				visibleCount++;
			}
		});

		// Handle no results message.
		if (visibleCount === 0 && query !== '') {
			if (!noResults) {
				noResults = document.createElement('p');
				noResults.className = 'community-grid-no-results';
				noResults.textContent = communityGrid.i18n.noResults;
				grid.appendChild(noResults);
			}
			noResults.style.display = '';
		} else if (noResults) {
			noResults.style.display = 'none';
		}
	}

	/**
	 * Server-side Ajax search and pagination.
	 *
	 * @param {HTMLElement} wrapper The grid wrapper element.
	 * @param {Object}      config  Grid configuration.
	 * @param {string}      search  Search query.
	 * @param {number}      page    Page number.
	 */
	function ajaxSearch(wrapper, config, search, page) {
		var grid = wrapper.querySelector('.community-grid');
		var pagination = wrapper.querySelector('.community-pagination');
		var loading = wrapper.querySelector('.community-grid-loading');

		// Show loading overlay.
		if (loading) {
			loading.style.display = 'flex';
		}

		// Build form data.
		var formData = new FormData();
		formData.append('action', 'community_grid_search');
		formData.append('nonce', config.nonce);
		formData.append('parent_id', config.parentId);
		formData.append('search', search);
		formData.append('page', page);
		formData.append('per_page', config.perPage);
		formData.append('columns', config.columns);
		formData.append('orderby', config.orderby);
		formData.append('order', config.order);
		formData.append('style', config.style);

		// Make Ajax request.
		fetch(communityGrid.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(data) {
			if (data.success) {
				// Update grid content.
				grid.innerHTML = data.data.html;

				// Update pagination.
				if (pagination) {
					pagination.outerHTML = data.data.pagination;
				} else if (data.data.pagination) {
					grid.insertAdjacentHTML('afterend', data.data.pagination);
				}

				// Re-initialize pagination handlers.
				initPagination(wrapper, config);

				// Re-initialize lazy loading.
				initLazyLoad(wrapper);

				// Scroll to top of grid.
				wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
			} else {
				console.error('Community grid search error:', data.data.message);
			}
		})
		.catch(function(error) {
			console.error('Community grid fetch error:', error);
		})
		.finally(function() {
			// Hide loading overlay.
			if (loading) {
				loading.style.display = 'none';
			}
		});
	}

	/**
	 * Initialize pagination button handlers.
	 *
	 * @param {HTMLElement} wrapper The grid wrapper element.
	 * @param {Object}      config  Grid configuration.
	 */
	function initPagination(wrapper, config) {
		var pagination = wrapper.querySelector('.community-pagination');
		if (!pagination) {
			return;
		}

		var searchInput = wrapper.querySelector('.community-search-input');
		var buttons = pagination.querySelectorAll('.community-page-btn');

		buttons.forEach(function(button) {
			button.addEventListener('click', function() {
				if (this.disabled) {
					return;
				}

				var page = parseInt(this.dataset.page, 10);
				var search = searchInput ? searchInput.value.trim() : '';

				ajaxSearch(wrapper, config, search, page);
			});
		});
	}

	/**
	 * Initialize lazy loading with Intersection Observer.
	 *
	 * @param {HTMLElement} wrapper The grid wrapper element.
	 */
	function initLazyLoad(wrapper) {
		var lazyImages = wrapper.querySelectorAll('.community-lazy');

		if (!lazyImages.length) {
			return;
		}

		if ('IntersectionObserver' in window) {
			var observer = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						var img = entry.target;
						img.src = img.dataset.src;
						img.addEventListener('load', function() {
							img.classList.add('community-lazy-loaded');
						});
						observer.unobserve(img);
					}
				});
			}, {
				rootMargin: '200px'
			});

			lazyImages.forEach(function(img) {
				observer.observe(img);
			});
		} else {
			// Fallback: load all immediately.
			lazyImages.forEach(function(img) {
				img.src = img.dataset.src;
				img.classList.add('community-lazy-loaded');
			});
		}
	}

	// Initialize on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
