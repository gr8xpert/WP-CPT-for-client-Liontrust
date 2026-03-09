# Community CPT WordPress Plugin — Build Instructions

> **Purpose**: This document is the complete build specification for a WordPress plugin called `community-cpt`. It registers a hierarchical Custom Post Type with 3-level nesting, shortcode-based grid rendering, Ajax search/pagination, Divi Builder integration, breadcrumbs, and a related posts system.
>
> **Read this entire file before writing any code.** Build phase by phase in order. Do not skip sections.

---

## Table of Contents

1. [Plugin Overview](#1-plugin-overview)
2. [File Structure](#2-file-structure)
3. [Phase 1 — Foundation](#3-phase-1--foundation)
4. [Phase 2 — Grid System & Templates](#4-phase-2--grid-system--templates)
5. [Phase 3 — Ajax Search & Pagination](#5-phase-3--ajax-search--pagination)
6. [Phase 4 — Related Posts & Breadcrumbs](#6-phase-4--related-posts--breadcrumbs)
7. [Phase 5 — Settings Page](#7-phase-5--settings-page)
8. [Phase 6 — Admin Enhancements](#8-phase-6--admin-enhancements)
9. [Phase 7 — CSV Import/Export Tool](#9-phase-7--csv-importexport-tool)
10. [Phase 8 — Performance & Lazy Loading](#10-phase-8--performance--lazy-loading)
11. [Phase 9 — Shortcode Wizard for Divi](#11-phase-9--shortcode-wizard-for-divi)
12. [CSS Specification](#12-css-specification)
13. [JavaScript Specification](#13-javascript-specification)
14. [Hooks & Filters Reference](#14-hooks--filters-reference)
15. [Security Checklist](#15-security-checklist)
16. [Testing Checklist](#16-testing-checklist)

---

## 1. Plugin Overview

### What This Plugin Does

- Registers a **hierarchical CPT** called `community` with slug `/community/`
- Supports **3 levels** of nesting: Parent → Sub-Parent → Detail
- **Auto-detects** whether a post is a listing (has children) or a detail page (no children)
- Renders child post grids via the `[community_grid]` shortcode placed inside Divi Builder
- Provides `[community_breadcrumb]` and `[community_related]` shortcodes
- Ajax-powered search and pagination on listing grids
- Full Divi Builder compatibility on all community posts
- Custom meta fields for additional content and related post selection
- Admin settings page for global defaults
- CSV import/export for bulk content management
- Lazy loading images for performance on large grids
- Shortcode wizard modal inside Divi editor
- Duplicate post functionality
- Custom admin columns showing hierarchy, children count, and thumbnails

### Hierarchy Example

```
Level 1: Popular Communities in Costa del Sol     → /community/popular-communities/
  Level 2: Elviria Beachside (has children)       → /community/popular-communities/elviria-beachside/
    Level 3: Alanda Club Marbella                 → /community/popular-communities/elviria-beachside/alanda-club-marbella/
    Level 3: White Pearl Beach                    → /community/popular-communities/elviria-beachside/white-pearl-beach/
  Level 2: Alcazaba Beach (no children = detail)  → /community/popular-communities/alcazaba-beach/
```

### Key Behaviour Rule

- If a community post **has children** → it is a **listing page** (shows grid of children)
- If a community post **has no children** → it is a **detail page** (shows full Divi content)
- This is determined dynamically at render time, not stored as a setting

---

## 2. File Structure

Create this exact structure inside the plugin folder `community-cpt/`:

```
community-cpt/
├── community-cpt.php                          # Main plugin file, bootstrapper
├── includes/
│   ├── class-community-cpt.php                # CPT registration & permalink config
│   ├── class-community-meta.php               # Meta boxes & custom fields
│   ├── class-community-shortcodes.php         # [community_grid] shortcode handler
│   ├── class-community-ajax.php               # Ajax search & pagination endpoints
│   ├── class-community-breadcrumb.php         # [community_breadcrumb] shortcode
│   ├── class-community-related.php            # [community_related] shortcode & logic
│   ├── class-community-settings.php           # Settings page under Settings menu
│   ├── class-community-divi.php               # Divi Builder integration
│   ├── class-community-admin-columns.php      # Custom admin list table columns
│   ├── class-community-duplicate.php          # Duplicate post functionality
│   ├── class-community-import-export.php      # CSV import/export tool
│   └── class-community-shortcode-wizard.php   # Shortcode builder modal for Divi
├── templates/
│   └── partials/
│       ├── grid-card.php                      # Single grid card markup
│       ├── grid-wrapper.php                   # Grid container + search input
│       ├── pagination.php                     # Pagination markup
│       └── related-grid.php                   # Related posts grid markup
├── assets/
│   ├── css/
│   │   ├── community-grid.css                 # Frontend grid styles
│   │   └── community-admin.css                # Admin meta box + settings styles
│   └── js/
│       ├── community-grid.js                  # Ajax search + pagination + client filter + lazy load
│       ├── community-admin.js                 # Admin related-posts selector (Select2)
│       └── community-shortcode-wizard.js      # Shortcode wizard modal logic
└── languages/
    └── community-cpt.pot                      # Translation template
```

---

## 3. Phase 1 — Foundation

### 3.1 Main Plugin File: `community-cpt.php`

```php
<?php
/**
 * Plugin Name: Community CPT
 * Description: Hierarchical community/location CPT with grid listings, Ajax search, Divi integration, and related posts.
 * Version: 1.0.0
 * Author: RealtySoft BV
 * Text Domain: community-cpt
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('COMMUNITY_CPT_VERSION', '1.0.0');
define('COMMUNITY_CPT_PATH', plugin_dir_path(__FILE__));
define('COMMUNITY_CPT_URL', plugin_dir_url(__FILE__));
define('COMMUNITY_CPT_BASENAME', plugin_basename(__FILE__));
```

**Requirements for this file:**

- Load all class files from `includes/` using `require_once`
- Instantiate each class on the `plugins_loaded` hook at priority 10
- Register activation hook that calls `flush_rewrite_rules()`
- Register deactivation hook that calls `flush_rewrite_rules()`
- Load text domain on `init` hook for i18n
- Enqueue frontend assets conditionally (only on `community` CPT pages or pages with shortcodes)
- Enqueue admin assets only on `community` post edit screens and the plugin settings page

### 3.2 CPT Registration: `includes/class-community-cpt.php`

Create a class `Community_CPT` that registers the post type on the `init` hook.

**CPT Configuration (use these exact values):**

```php
$args = array(
    'labels'             => $labels,
    'public'             => true,
    'hierarchical'       => true,
    'has_archive'        => false,
    'show_in_rest'       => true,
    'menu_icon'          => 'dashicons-location-alt',
    'menu_position'      => 20,
    'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields'),
    'rewrite'            => array(
        'slug'       => 'community',
        'with_front' => false,
        'hierarchical' => true,  // CRITICAL — enables nested URL slugs
    ),
    'capability_type'    => 'page',
    'show_in_nav_menus'  => true,
);
```

**Labels — generate the full set:**

```php
$labels = array(
    'name'               => __('Communities', 'community-cpt'),
    'singular_name'      => __('Community', 'community-cpt'),
    'add_new'            => __('Add New Community', 'community-cpt'),
    'add_new_item'       => __('Add New Community', 'community-cpt'),
    'edit_item'          => __('Edit Community', 'community-cpt'),
    'new_item'           => __('New Community', 'community-cpt'),
    'view_item'          => __('View Community', 'community-cpt'),
    'search_items'       => __('Search Communities', 'community-cpt'),
    'not_found'          => __('No communities found', 'community-cpt'),
    'not_found_in_trash' => __('No communities found in trash', 'community-cpt'),
    'parent_item_colon'  => __('Parent Community:', 'community-cpt'),
    'all_items'          => __('All Communities', 'community-cpt'),
    'menu_name'          => __('Communities', 'community-cpt'),
);
```

### 3.3 Divi Integration: `includes/class-community-divi.php`

Create a class `Community_Divi` that:

1. Hooks into `et_builder_post_types` filter to add `'community'` to Divi's supported post types
2. Hooks into `et_builder_module_post_types` filter to make all Divi modules available on community posts
3. Adds the community CPT to Divi's post type integration option if not already present

```php
class Community_Divi {

    public function __construct() {
        add_filter('et_builder_post_types', array($this, 'add_post_type'));
        add_filter('et_builder_module_post_types', array($this, 'add_module_post_type'));
    }

    public function add_post_type($post_types) {
        if (!in_array('community', $post_types)) {
            $post_types[] = 'community';
        }
        return $post_types;
    }

    public function add_module_post_type($post_types) {
        if (!in_array('community', $post_types)) {
            $post_types[] = 'community';
        }
        return $post_types;
    }
}
```

---

## 4. Phase 2 — Grid System & Templates

### 4.1 Meta Fields: `includes/class-community-meta.php`

Create a class `Community_Meta` that registers meta boxes on the `community` post editor.

**Meta box: "Community Settings"** — displayed on all community posts.

Fields to register:

| Meta Key | Field Type | Description |
|----------|-----------|-------------|
| `_community_top_content` | wp_editor (WYSIWYG) | HTML content rendered ABOVE the child grid on listing pages. Supplements Divi content. |
| `_community_bottom_content` | wp_editor (WYSIWYG) | HTML content rendered BELOW the child grid on listing pages. Supplements Divi content. |
| `_community_grid_excerpt` | textarea | Short description shown on grid cards. Falls back to WP excerpt if empty. |
| `_community_related_posts` | multi-select (post IDs) | Manually selected related community posts. If empty, falls back to sibling posts. Uses Select2 for searchable UI. |
| `_community_related_title` | text input | Custom heading for the related section. Default: "Properties near {post_title}" |

**Save handler requirements:**

- Verify nonce: `community_meta_nonce`
- Check `current_user_can('edit_post', $post_id)`
- Skip on autosave (`defined('DOING_AUTOSAVE') && DOING_AUTOSAVE`)
- Sanitize text fields with `sanitize_text_field()`
- Sanitize WYSIWYG fields with `wp_kses_post()`
- Sanitize related posts array: `array_map('absint', ...)`
- Use `update_post_meta()` / `delete_post_meta()` as needed

**Related Posts field UI (Admin):**

- Use Select2 library (enqueue from CDN: `https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css` and JS equivalent)
- The select field should query community posts via Ajax (use `wp_ajax_community_search_posts` action)
- Show currently selected posts as sortable tags (drag to reorder)
- Include a "Clear all" button to reset to automatic (sibling) mode
- Display a helper text: "Leave empty to automatically show sibling communities"

### 4.2 Shortcode: `includes/class-community-shortcodes.php`

Create a class `Community_Shortcodes` that registers `[community_grid]`.

**Shortcode attributes:**

| Attribute | Default | Options | Description |
|-----------|---------|---------|-------------|
| `parent_id` | current post ID | Any post ID | Override which parent's children to show |
| `columns` | from settings (default 3) | 2, 3, 4 | Grid columns on desktop |
| `per_page` | from settings | 0 = all, or any number | Items per page. 0 means no pagination. |
| `show_search` | from settings (default true) | true, false | Show/hide Ajax search input |
| `orderby` | menu_order | menu_order, title, date | Sort field |
| `order` | ASC | ASC, DESC | Sort direction |
| `style` | default | default, compact | Grid card style variation |

**Shortcode render logic:**

```
1. Parse and validate attributes (whitelist values, cast types)
2. Determine parent_id (use current post if not specified)
3. Build WP_Query args:
   - post_type = 'community'
   - post_parent = $parent_id
   - post_status = 'publish'
   - orderby = $orderby
   - order = $order
   - posts_per_page = ($per_page == 0) ? -1 : $per_page
   - paged = get_query_var('paged', 1)  // only if paginated
4. Apply filter: community_grid_query_args
5. Run query
6. Generate output using template partials
7. Return output (NOT echo — shortcodes must return)
```

**Important:** The shortcode MUST use `ob_start()` / `ob_get_clean()` to capture template output and return it.

### 4.3 Template Partials

#### `templates/partials/grid-wrapper.php`

This receives variables: `$posts`, `$atts` (shortcode attributes), `$query` (WP_Query object), `$settings` (plugin settings).

```html
<div class="community-grid-wrapper" 
     data-parent-id="{parent_id}" 
     data-per-page="{per_page}" 
     data-columns="{columns}"
     data-style="{style}"
     data-nonce="{wp_nonce}">
    
    <!-- Search input (if show_search is true) -->
    <div class="community-grid-search">
        <div class="community-search-input-wrap">
            <svg class="community-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" 
                   class="community-search-input" 
                   placeholder="Search for a community..." 
                   aria-label="Search communities">
            <button class="community-search-clear" aria-label="Clear search" style="display:none;">&times;</button>
        </div>
    </div>

    <!-- Grid -->
    <div class="community-grid community-grid-cols-{columns}">
        <!-- Loop: include grid-card.php for each post -->
    </div>

    <!-- Pagination (if applicable) -->
    <!-- include pagination.php -->

    <!-- Loading overlay -->
    <div class="community-grid-loading" style="display:none;">
        <div class="community-spinner"></div>
    </div>
</div>
```

#### `templates/partials/grid-card.php`

This receives `$post` (WP_Post object) and `$atts` (shortcode attributes).

```html
<article class="community-grid-card" data-title="{lowercase title}" data-excerpt="{lowercase excerpt}">
    <a href="{permalink}" class="community-card-link" aria-label="View {title}">
        <div class="community-card-image-wrap">
            <!-- If lazy loading enabled: use data-src with placeholder -->
            <!-- If lazy loading disabled: use normal src -->
            <img src="{featured_image_url_or_placeholder}" 
                 data-src="{featured_image_url if lazy}"
                 alt="{title}" 
                 class="community-card-image {community-lazy if lazy}"
                 width="400" 
                 height="250">
        </div>
        <div class="community-card-content">
            <h3 class="community-card-title">{title}</h3>
            <p class="community-card-excerpt">{excerpt, truncated to setting length}</p>
        </div>
    </a>
</article>
```

**Excerpt logic:**
1. Check `_community_grid_excerpt` meta field first
2. If empty, fall back to `get_the_excerpt()`
3. If still empty, use `wp_trim_words(get_the_content(), 20)`
4. Truncate to the character limit from settings (default 120)
5. Apply filter: `community_card_excerpt_length`

**Featured image fallback:**
- If no featured image is set, use a default placeholder. Generate a simple SVG placeholder inline with the community title text, or use a CSS-based placeholder with a generic icon. Do NOT leave a broken image tag.

Apply filter `community_grid_card_html` to the full card HTML before output, passing `$html`, `$post`, and `$atts`.

#### `templates/partials/pagination.php`

This receives `$query` (WP_Query) and `$atts`.

Only render if `per_page > 0` and `$query->max_num_pages > 1`.

```html
<nav class="community-pagination" aria-label="Community pagination" data-total-pages="{max_num_pages}" data-current-page="{current_page}">
    <button class="community-page-btn community-page-prev" data-page="{prev}" aria-label="Previous page" {disabled if page 1}>&lsaquo; Previous</button>
    
    <!-- Page numbers — show smart pagination:
         If <= 7 pages: show all
         If > 7: show 1, 2, ... current-1, current, current+1, ... last-1, last
         Use "..." as ellipsis (non-clickable span) -->
    <button class="community-page-btn community-page-num active" data-page="1">1</button>
    <span class="community-page-ellipsis">&hellip;</span>
    <button class="community-page-btn community-page-num" data-page="5">5</button>
    <!-- etc -->
    
    <button class="community-page-btn community-page-next" data-page="{next}" aria-label="Next page" {disabled if last page}>Next &rsaquo;</button>
</nav>
```

Pagination must be rendered as buttons (not links) since page changes are handled via Ajax.

### 4.4 Template Routing

In `class-community-cpt.php`, add a `single_template` filter:

```php
add_filter('single_template', array($this, 'route_template'));
```

**Routing logic:**

The plugin does NOT use custom template files — Divi's single template handles rendering. Instead, the plugin uses `the_content` filter to inject content dynamically:

**Auto-append related posts on detail pages:**

Hook into `the_content` filter (priority 20, after Divi):

```
1. Check if is_singular('community') and in the main query
2. Check if current post has NO children (= detail page)
3. Check if the content does NOT already contain [community_related]
4. If all true, append do_shortcode('[community_related]') to the content
5. Return modified content
```

**Auto-prepend/append meta content on listing pages:**

Hook into `the_content` filter (priority 15):

```
1. Check if is_singular('community') and in the main query
2. Check if current post HAS children (= listing page)
3. Get _community_top_content and _community_bottom_content meta
4. Prepend top content before the_content if not empty
5. Append bottom content after the_content if not empty
6. Return modified content
```

**Helper function to check if a post has children:**

```php
function community_post_has_children($post_id) {
    $cache_key = 'community_has_children_' . $post_id;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached === 'yes';
    }
    
    $children = get_children(array(
        'post_parent' => $post_id,
        'post_type'   => 'community',
        'post_status' => 'publish',
        'numberposts' => 1,
    ));
    
    $has_children = !empty($children);
    set_transient($cache_key, $has_children ? 'yes' : 'no', HOUR_IN_SECONDS);
    
    return $has_children;
}
```

---

## 5. Phase 3 — Ajax Search & Pagination

### 5.1 Ajax Handler: `includes/class-community-ajax.php`

Create a class `Community_Ajax` that registers two Ajax actions.

**Action 1: `community_grid_search`**

Handles both search filtering and pagination. Available to logged-in and non-logged-in users.

```php
add_action('wp_ajax_community_grid_search', array($this, 'handle_search'));
add_action('wp_ajax_nopriv_community_grid_search', array($this, 'handle_search'));
```

**Parameters (from `$_POST`):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `nonce` | string | Yes | Security nonce |
| `parent_id` | int | Yes | Parent post ID |
| `search` | string | No | Search query string |
| `page` | int | No | Page number (default 1) |
| `per_page` | int | No | Items per page (default from settings) |
| `columns` | int | No | Columns for grid HTML |
| `orderby` | string | No | Sort field |
| `order` | string | No | Sort direction |

**Handler logic:**

```
1. Verify nonce with wp_verify_nonce($nonce, 'community_grid_nonce')
2. Sanitize all inputs:
   - parent_id: absint()
   - search: sanitize_text_field()
   - page: absint(), min 1
   - per_page: absint()
   - columns: absint(), whitelist to 2/3/4
   - orderby: whitelist to menu_order/title/date
   - order: whitelist to ASC/DESC
3. Build WP_Query:
   - post_type = 'community'
   - post_parent = $parent_id
   - post_status = 'publish'
   - posts_per_page = $per_page (or -1 if 0)
   - paged = $page
   - orderby = $orderby
   - order = $order
   - IF $search is not empty:
     - Add 's' => $search to query args
4. Apply filter: community_grid_query_args
5. Run query
6. Generate grid HTML using template partials (ob_start/ob_get_clean)
7. Generate pagination HTML
8. Return JSON:
   {
     "success": true,
     "data": {
       "html": "<grid cards HTML>",
       "pagination": "<pagination HTML>",
       "total_pages": N,
       "current_page": N,
       "total_posts": N,
       "found_posts": N
     }
   }
```

**Action 2: `community_search_posts`** (Admin only — for Select2 related posts field)

```php
add_action('wp_ajax_community_search_posts', array($this, 'search_posts_for_select'));
```

**Handler logic:**

```
1. Verify nonce
2. Get search term from $_GET['q']
3. Query community posts matching search term (by title)
4. Exclude current post if post_id is provided
5. Return JSON array: [{ "id": 123, "text": "Post Title (Parent Name)" }, ...]
```

### 5.2 Transient Caching

Cache grid query results to reduce database load:

```
Cache key format: "community_grid_{parent_id}_{page}_{per_page}_{orderby}_{order}_{search_md5}"
Expiration: from settings (default 1 hour), filterable via community_cache_expiration
```

**Cache invalidation — hook into these actions to delete transients:**

- `save_post_community` — when any community post is saved
- `trashed_post` — when a community post is trashed
- `untrashed_post` — when a community post is restored
- `deleted_post` — when a community post is permanently deleted

Use `$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_community_grid_%' OR option_name LIKE '_transient_timeout_community_grid_%'")` for bulk invalidation.

Also clear the "has children" cache on these same hooks:
`$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_community_has_children_%' OR option_name LIKE '_transient_timeout_community_has_children_%'")`

Fire the `community_cache_cleared` action after bulk invalidation.

### 5.3 Frontend JavaScript Localization

In the main plugin file, when enqueueing `community-grid.js`, pass data via `wp_localize_script()`:

```php
wp_localize_script('community-grid', 'communityGrid', array(
    'ajaxUrl'    => admin_url('admin-ajax.php'),
    'nonce'      => wp_create_nonce('community_grid_nonce'),
    'i18n'       => array(
        'searching'  => __('Searching...', 'community-cpt'),
        'noResults'  => __('No communities found.', 'community-cpt'),
        'loading'    => __('Loading...', 'community-cpt'),
        'error'      => __('Something went wrong. Please try again.', 'community-cpt'),
    ),
));
```

---

## 6. Phase 4 — Related Posts & Breadcrumbs

### 6.1 Related Posts: `includes/class-community-related.php`

Create a class `Community_Related` that registers `[community_related]`.

**Shortcode attributes:**

| Attribute | Default | Options | Description |
|-----------|---------|---------|-------------|
| `title` | auto-generated | Any text | Heading text. Default: "Properties near {post_title}" |
| `limit` | from settings (default 3) | 1–6 | Max related posts to show |
| `columns` | 3 | 2, 3 | Grid columns |

**Selection logic (implement in this exact priority order):**

```
1. Get current post ID
2. Check _community_related_posts meta field
3. IF meta has post IDs:
   a. Query those specific posts (post__in, preserve order with orderby => post__in)
   b. Filter out trashed/draft posts
   c. If valid posts found, use them
4. IF meta is empty OR no valid posts:
   a. Get current post's parent ID
   b. Query siblings: post_parent = $parent_id, exclude current post
   c. Order by menu_order ASC
   d. Limit to $limit
5. IF no siblings found (only child):
   a. Get grandparent ID (parent of parent)
   b. Query cousins: all posts whose parent has the same grandparent, exclude current post
   c. Limit to $limit
6. IF still no results, return empty string (no output)
```

**Get the related section title:**
1. Check `_community_related_title` meta field
2. If empty, use "Properties near {post_title}"
3. Apply `esc_html()` to the output

**Template: `templates/partials/related-grid.php`**

```html
<section class="community-related-wrapper">
    <h2 class="community-related-title">{title}</h2>
    <div class="community-related-grid community-grid-cols-{columns}">
        <!-- For each related post: -->
        <article class="community-related-card">
            <a href="{permalink}" class="community-related-card-link">
                <div class="community-related-card-image-wrap">
                    <img src="{featured_image}" alt="{title}" loading="lazy" width="400" height="250">
                </div>
                <div class="community-related-card-content">
                    <h3 class="community-related-card-title">{title}</h3>
                    <span class="community-related-card-btn">Explore</span>
                </div>
            </a>
        </article>
    </div>
</section>
```

Apply filter `community_related_query_args` to the WP_Query args before execution.
Apply filter `community_related_card_html` to each card's HTML, passing `$html` and `$post`.
Fire action `community_before_related` before the section, passing `$post_id` and `$related_posts`.
Fire action `community_after_related` after the section, passing `$post_id` and `$related_posts`.

### 6.2 Breadcrumbs: `includes/class-community-breadcrumb.php`

Create a class `Community_Breadcrumb` that registers `[community_breadcrumb]`.

**No attributes needed** — it auto-detects the current post's position in the hierarchy.

**Render logic:**

```
1. Get current post
2. Build ancestors array by walking up post_parent chain
3. Reverse to get root-first order
4. Build items array:
   - Item 1: Home (link to site root)
   - Items 2..N-1: Each ancestor (linked)
   - Item N: Current post (no link, aria-current="page")
5. Apply filter: community_breadcrumb_items ($items, $post_id)
6. Get separator (default " › "), apply filter: community_breadcrumb_separator
7. Render with Schema.org BreadcrumbList markup
```

**Output HTML structure:**

```html
<nav class="community-breadcrumb" aria-label="Breadcrumb">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="/">
                <span itemprop="name">Home</span>
            </a>
            <meta itemprop="position" content="1">
        </li>
        <li class="community-breadcrumb-separator" aria-hidden="true"> › </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="/community/popular-communities/">
                <span itemprop="name">Popular Communities</span>
            </a>
            <meta itemprop="position" content="2">
        </li>
        <li class="community-breadcrumb-separator" aria-hidden="true"> › </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name" aria-current="page">Alcazaba Beach</span>
            <meta itemprop="position" content="3">
        </li>
    </ol>
</nav>
```

---

## 7. Phase 5 — Settings Page

### 7.1 Settings: `includes/class-community-settings.php`

Create a class `Community_Settings` that adds a settings page under **Settings → Community CPT**.

**Settings are stored as a single option:** `community_cpt_settings`

**Default values:**

```php
$defaults = array(
    'pagination_mode'    => 'all',      // 'all' or 'paginated'
    'per_page'           => 20,
    'default_columns'    => 3,
    'show_search'        => true,
    'related_count'      => 3,
    'excerpt_length'     => 120,
    'card_style'         => 'default',  // 'default' or 'compact'
    'lazy_load_images'   => true,
    'cache_duration'     => 3600,       // seconds
);
```

**Settings page sections:**

**Section 1: Grid Display**
- Pagination Mode: radio buttons — "Show all (no pagination)" / "Paginated"
- Posts Per Page: number input (only shown/relevant when paginated mode is selected). Min 1, max 100.
- Default Grid Columns: select — 2, 3, 4
- Card Style: select — "Default (image + title + excerpt)" / "Compact (image + title only)"
- Card Excerpt Length: number input (characters). Min 50, max 300.

**Section 2: Search**
- Show Search by Default: checkbox

**Section 3: Related Posts**
- Related Posts Count: number input. Min 1, max 6.

**Section 4: Performance**
- Enable Lazy Loading Images: checkbox
- Cache Duration: select — "1 hour", "6 hours", "12 hours", "24 hours", "No cache"

**Section 5: Tools**
- Flush Permalinks: button that triggers `flush_rewrite_rules()` via Ajax and shows success message
- Clear All Cache: button that deletes all community transients via Ajax and shows success message

**Helper function to retrieve a setting (define globally):**

```php
function community_cpt_get_setting($key, $default = null) {
    $settings = get_option('community_cpt_settings', array());
    $defaults = array(
        'pagination_mode'    => 'all',
        'per_page'           => 20,
        'default_columns'    => 3,
        'show_search'        => true,
        'related_count'      => 3,
        'excerpt_length'     => 120,
        'card_style'         => 'default',
        'lazy_load_images'   => true,
        'cache_duration'     => 3600,
    );
    
    if ($default === null) {
        $default = isset($defaults[$key]) ? $defaults[$key] : '';
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}
```

---

## 8. Phase 6 — Admin Enhancements

### 8.1 Custom Admin Columns: `includes/class-community-admin-columns.php`

Add custom columns to the community post list table in wp-admin.

**Columns to display (in this order):**

| Column | Content |
|--------|---------|
| Thumbnail | 60×40px featured image thumbnail |
| Title | (default WordPress column) |
| Parent | Parent post title (linked to parent's edit screen), or "—" if root level |
| Level | Hierarchy level: 1, 2, or 3 (with visual indicator) |
| Children | Count of direct published children. If > 0, show as a link to filtered list: `edit.php?post_type=community&post_parent={id}` |
| Menu Order | The menu_order value (for understanding sort position) |
| Date | (default WordPress column) |

**Implementation hooks:**

```php
add_filter('manage_community_posts_columns', array($this, 'add_columns'));
add_action('manage_community_posts_custom_column', array($this, 'render_column'), 10, 2);
add_filter('manage_edit-community_sortable_columns', array($this, 'sortable_columns'));
```

**Make "Parent" and "Menu Order" sortable columns.**

**Calculate hierarchy level:**

```php
function get_hierarchy_level($post_id) {
    $level = 1;
    $parent = wp_get_post_parent_id($post_id);
    while ($parent) {
        $level++;
        $parent = wp_get_post_parent_id($parent);
    }
    return $level;
}
```

**Visual level indicator:** Show level as "L1", "L2", "L3" with distinct background colors:
- L1: blue badge
- L2: green badge
- L3: orange badge

### 8.2 Duplicate Post: `includes/class-community-duplicate.php`

Add a "Duplicate" action link in the community post list table row actions.

**Implementation:**

```php
add_filter('post_row_actions', array($this, 'add_duplicate_link'), 10, 2);
add_action('admin_action_duplicate_community', array($this, 'handle_duplicate'));
```

**Duplicate link URL format:**
```
admin_url('admin.php?action=duplicate_community&post=' . $post->ID . '&_wpnonce=' . $nonce)
```

**Duplicate logic:**

```
1. Verify nonce with wp_verify_nonce()
2. Verify capability: current_user_can('edit_posts')
3. Get the original post by ID
4. Verify it is a 'community' post type
5. Create new post with wp_insert_post():
   - post_title = original title + " (Copy)"
   - post_type = 'community'
   - post_status = 'draft'
   - post_parent = same as original
   - post_content = same as original
   - post_excerpt = same as original
   - menu_order = original menu_order + 1
6. Copy ALL post meta from original to new post:
   - Get all meta with get_post_meta($original_id)
   - Loop and add each to new post with add_post_meta()
   - Skip internal WP meta keys starting with '_edit_' or '_wp_'
7. Copy featured image: set_post_thumbnail($new_id, get_post_thumbnail_id($original_id))
8. Fire action: community_post_duplicated($new_post_id, $original_post_id)
9. Redirect to the new post's edit screen with wp_redirect()
```

---

## 9. Phase 7 — CSV Import/Export Tool

### 9.1 Import/Export: `includes/class-community-import-export.php`

Add a sub-page under the Communities admin menu: **Communities → Import/Export**

```php
add_action('admin_menu', array($this, 'add_submenu_page'));

public function add_submenu_page() {
    add_submenu_page(
        'edit.php?post_type=community',
        __('Import / Export Communities', 'community-cpt'),
        __('Import / Export', 'community-cpt'),
        'edit_posts',
        'community-import-export',
        array($this, 'render_page')
    );
}
```

#### Export Functionality

**Export button** generates a CSV file download with these columns:

| Column | Description |
|--------|-------------|
| `id` | Post ID |
| `title` | Post title |
| `slug` | Post slug |
| `parent_slug` | Parent post's slug (empty if root) |
| `status` | Post status (publish, draft, etc.) |
| `menu_order` | Menu order number |
| `excerpt` | Post excerpt |
| `grid_excerpt` | `_community_grid_excerpt` meta value |
| `related_title` | `_community_related_title` meta value |
| `related_post_ids` | `_community_related_posts` as pipe-separated IDs (e.g., "12|45|78") |
| `featured_image_url` | Full URL of featured image |

**Export logic:**

```
1. Verify nonce and capability
2. Query all community posts (post_status = any, orderby = menu_order, order = ASC, posts_per_page = -1)
3. Set headers for CSV download:
   - Content-Type: text/csv; charset=utf-8
   - Content-Disposition: attachment; filename="communities-export-{Y-m-d}.csv"
   - Pragma: no-cache
4. Open php://output with fopen
5. Write UTF-8 BOM for Excel compatibility: "\xEF\xBB\xBF"
6. Write header row with fputcsv()
7. Loop posts and write data rows with fputcsv()
8. Exit after output (wp_die)
```

#### Import Functionality

**Import form** with file upload field and submit button.

**Import logic:**

```
1. Verify nonce and capability (edit_posts)
2. Validate uploaded file:
   - Check $_FILES for errors
   - Verify file extension is .csv
   - Verify file size < 5MB
   - Verify MIME type with wp_check_filetype()
3. Parse CSV with fgetcsv()
4. Read and validate header row — must contain at minimum: title, slug
5. Initialize counters: $created = 0, $updated = 0, $errors = 0
6. Initialize $messages array for per-row status
7. For each data row:
   a. Skip empty rows
   b. Extract fields by column name
   c. Determine if update or create:
      - If 'id' is present and matches an existing community post → UPDATE
      - Otherwise → CREATE
   d. Resolve parent_slug:
      - If parent_slug is not empty, find community post with that slug
      - Use get_page_by_path($parent_slug, OBJECT, 'community')
      - If found, set post_parent to that post's ID
      - If not found, log warning and set parent to 0
   e. Prepare post data array
   f. INSERT or UPDATE with wp_insert_post() / wp_update_post()
   g. Update meta fields: _community_grid_excerpt, _community_related_title
   h. Handle related_post_ids: explode by pipe, array_map absint, update _community_related_posts
   i. Handle featured_image_url:
      - If URL is provided and post doesn't already have that image
      - Use media_sideload_image($url, $post_id, $title, 'id') to download and attach
      - Set as featured image with set_post_thumbnail()
      - Wrap in try/catch — image download failures should not halt import
   j. Fire action: community_import_row($row_data, $post_id, $action)
   k. Log result: created/updated/error + message
   l. Increment counters
8. After all rows processed:
   - Flush rewrite rules
   - Clear all community transients
   - Display results summary
```

**Import UI should show:**

- File upload input (accept=".csv")
- "Import" button
- After import: summary table with columns: Row #, Title, Action (Created/Updated/Skipped), Message
- Warning messages highlighted in yellow for non-critical issues (e.g., parent not found)
- Error messages highlighted in red for failures
- Success count highlighted in green

---

## 10. Phase 8 — Performance & Lazy Loading

### 10.1 Intersection Observer Lazy Loading

Implemented in `community-grid.js`. Only active when the `lazy_load_images` setting is enabled.

**PHP side (in grid-card.php):**

When lazy loading is enabled:
- Set `src` to a tiny SVG placeholder: `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='250' viewBox='0 0 400 250'%3E%3Crect fill='%23f0f0f0' width='400' height='250'/%3E%3C/svg%3E`
- Store the real image URL in `data-src` attribute
- Add class `community-lazy` to the `<img>`

When lazy loading is disabled:
- Set `src` to the real image URL directly
- No `data-src` attribute
- No `community-lazy` class

**JS side (in community-grid.js):**

```javascript
function initLazyLoad(wrapper) {
    const lazyImages = wrapper.querySelectorAll('.community-lazy');
    if (!lazyImages.length) return;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.addEventListener('load', function() {
                        img.classList.add('community-lazy-loaded');
                    });
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });

        lazyImages.forEach(function(img) { observer.observe(img); });
    } else {
        // Fallback: load all immediately
        lazyImages.forEach(function(img) {
            img.src = img.dataset.src;
            img.classList.add('community-lazy-loaded');
        });
    }
}
```

**Must re-initialize lazy loading after Ajax content loads** — call `initLazyLoad(wrapper)` in the Ajax success callback.

### 10.2 Additional Performance Measures

**Conditional asset loading:**
```php
function should_load_assets() {
    if (is_singular('community')) return true;
    
    global $post;
    if ($post && (
        has_shortcode($post->post_content, 'community_grid') ||
        has_shortcode($post->post_content, 'community_related') ||
        has_shortcode($post->post_content, 'community_breadcrumb')
    )) return true;
    
    return false;
}
```

Only enqueue `community-grid.css` and `community-grid.js` when this returns true.

**Responsive images:**
Use `wp_get_attachment_image()` instead of raw `<img>` tags to automatically get `srcset` and `sizes` attributes. This lets browsers load appropriately sized images for each viewport.

**Minified assets:**
- Check `SCRIPT_DEBUG` constant
- If false (production): load `.min.css` and `.min.js`
- If true (development): load unminified versions
- Create minified versions during development (or instruct the developer to create them before deployment)

---

## 11. Phase 9 — Shortcode Wizard for Divi

### 11.1 Shortcode Wizard: `includes/class-community-shortcode-wizard.php`

Add a button in the WordPress classic editor toolbar that opens a modal for building community shortcodes visually.

**Add the wizard button:**

```php
add_action('media_buttons', array($this, 'add_wizard_button'), 15);
```

**Button HTML:**

```html
<button type="button" class="button community-shortcode-wizard-btn" title="Insert Community Shortcode">
    <span class="dashicons dashicons-screenoptions" style="vertical-align: text-bottom; margin-right: 4px;"></span>
    Community Grid
</button>
```

**Enqueue wizard assets only on community edit screens:**

```php
add_action('admin_enqueue_scripts', array($this, 'enqueue_wizard_assets'));

public function enqueue_wizard_assets($hook) {
    global $post_type;
    if ($post_type !== 'community') return;
    if (!in_array($hook, array('post.php', 'post-new.php'))) return;
    
    wp_enqueue_script('community-shortcode-wizard', ...);
    wp_enqueue_style('community-admin', ...);
}
```

**Modal fields (rendered by JS):**

| Field | Type | Shown For | Options |
|-------|------|-----------|---------|
| Shortcode Type | Radio buttons | Always | [community_grid], [community_related], [community_breadcrumb] |
| Parent Community | Select2 dropdown (Ajax search) | [community_grid] only | All community posts |
| Columns | Select | [community_grid], [community_related] | 2, 3, 4 |
| Posts Per Page | Number input | [community_grid] only | 0 = all, or custom |
| Show Search | Checkbox | [community_grid] only | — |
| Order By | Select | [community_grid] only | Menu Order, Title, Date |
| Order Direction | Select | [community_grid] only | ASC, DESC |
| Card Style | Select | [community_grid] only | Default, Compact |
| Related Title | Text input | [community_related] only | Custom title text |
| Related Limit | Number input | [community_related] only | 1–6 |

**Modal behaviour:**

1. Clicking the "Community Grid" button creates and shows the modal overlay
2. Fields dynamically show/hide based on selected Shortcode Type
3. A live preview of the shortcode string updates at the bottom as fields change
4. "Insert Shortcode" button inserts the built shortcode at the cursor position in the editor (use `wp.media.editor.insert()` or `send_to_editor()`)
5. "Cancel" button closes the modal without inserting
6. Escape key closes the modal
7. Clicking outside the modal content area closes it
8. Focus is trapped within the modal when open (Tab cycles through fields, not underlying page)
9. ARIA attributes: `role="dialog"`, `aria-modal="true"`, `aria-label="Insert Community Shortcode"`

**Shortcode preview format examples:**

```
[community_grid columns="3" per_page="12" show_search="true" orderby="title" order="ASC"]
[community_related title="Nearby Communities" limit="4" columns="2"]
[community_breadcrumb]
```

---

## 12. CSS Specification

### 12.1 File: `assets/css/community-grid.css`

All frontend styles scoped under `.community-grid-wrapper`, `.community-related-wrapper`, or `.community-breadcrumb` to avoid Divi conflicts.

**CSS Custom Properties (for easy theming via Divi custom CSS):**

```css
:root {
    --community-primary: #1B4F72;
    --community-secondary: #D4A017;
    --community-text: #2C3E50;
    --community-text-light: #7F8C8D;
    --community-bg: #FFFFFF;
    --community-card-bg: #FFFFFF;
    --community-card-radius: 8px;
    --community-card-shadow: 0 2px 8px rgba(0,0,0,0.08);
    --community-card-shadow-hover: 0 8px 24px rgba(0,0,0,0.12);
    --community-grid-gap: 30px;
    --community-transition: 0.3s ease;
    --community-font-family: inherit;
}
```

**Complete CSS rules to implement:**

```css
/* === GRID LAYOUT === */
.community-grid-wrapper { position: relative; }

.community-grid {
    display: grid;
    gap: var(--community-grid-gap);
    margin: 30px 0;
}
.community-grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
.community-grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
.community-grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 980px) {
    .community-grid-cols-3, .community-grid-cols-4 { grid-template-columns: repeat(2, 1fr); }
    .community-grid { gap: 20px; }
}
@media (max-width: 767px) {
    .community-grid { grid-template-columns: 1fr; gap: 15px; }
}

/* === GRID CARD === */
.community-grid-card {
    background: var(--community-card-bg);
    border-radius: var(--community-card-radius);
    overflow: hidden;
    box-shadow: var(--community-card-shadow);
    transition: box-shadow var(--community-transition), transform var(--community-transition);
}
.community-grid-card:hover {
    box-shadow: var(--community-card-shadow-hover);
    transform: translateY(-2px);
}
.community-card-link { text-decoration: none; color: inherit; display: block; }
.community-card-link:focus { outline: 2px solid var(--community-primary); outline-offset: 2px; }
.community-card-image-wrap { overflow: hidden; aspect-ratio: 16 / 10; background: #f0f0f0; }
.community-card-image {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform var(--community-transition);
}
.community-grid-card:hover .community-card-image { transform: scale(1.05); }
.community-card-content { padding: 16px 20px 20px; }
.community-card-title {
    font-size: 1.1rem; font-weight: 600; color: var(--community-text);
    margin: 0 0 8px; font-family: var(--community-font-family);
}
.community-card-excerpt {
    font-size: 0.9rem; color: var(--community-text-light); line-height: 1.5; margin: 0;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}

/* Compact card style */
.community-grid-wrapper[data-style="compact"] .community-card-content { padding: 12px 16px 16px; }
.community-grid-wrapper[data-style="compact"] .community-card-excerpt { display: none; }

/* === SEARCH INPUT === */
.community-grid-search { margin-bottom: 24px; max-width: 600px; }
.community-search-input-wrap { position: relative; display: flex; align-items: center; }
.community-search-icon {
    position: absolute; left: 12px; width: 18px; height: 18px;
    color: var(--community-text-light); pointer-events: none;
}
.community-search-input {
    width: 100%; padding: 12px 40px; border: none; border-bottom: 2px solid #e0e0e0;
    font-size: 1rem; color: var(--community-text); background: transparent;
    transition: border-color var(--community-transition); font-family: var(--community-font-family);
    box-shadow: none; /* override Divi */
}
.community-search-input:focus { outline: none; border-bottom-color: var(--community-primary); }
.community-search-input::placeholder { color: var(--community-text-light); }
.community-search-clear {
    position: absolute; right: 8px; background: none; border: none;
    font-size: 1.4rem; color: var(--community-text-light); cursor: pointer; padding: 4px 8px; line-height: 1;
}
.community-search-clear:hover { color: var(--community-text); }

/* === PAGINATION === */
.community-pagination {
    display: flex; justify-content: center; align-items: center;
    gap: 6px; margin-top: 30px; flex-wrap: wrap;
}
.community-page-btn {
    padding: 8px 14px; border: 1px solid #e0e0e0; background: var(--community-bg);
    color: var(--community-text); border-radius: 4px; cursor: pointer;
    font-size: 0.9rem; transition: all var(--community-transition);
}
.community-page-btn:hover { border-color: var(--community-primary); color: var(--community-primary); }
.community-page-btn.active { background: var(--community-primary); color: #fff; border-color: var(--community-primary); }
.community-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.community-page-ellipsis { padding: 8px 4px; color: var(--community-text-light); }

/* === LOADING OVERLAY === */
.community-grid-loading {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255,255,255,0.7); display: flex; align-items: center;
    justify-content: center; z-index: 10; border-radius: var(--community-card-radius);
}
.community-spinner {
    width: 36px; height: 36px; border: 3px solid #e0e0e0;
    border-top-color: var(--community-primary); border-radius: 50%;
    animation: community-spin 0.8s linear infinite;
}
@keyframes community-spin { to { transform: rotate(360deg); } }

/* === NO RESULTS === */
.community-grid-no-results {
    grid-column: 1 / -1; text-align: center; padding: 40px 20px;
    color: var(--community-text-light); font-size: 1rem;
}

/* === LAZY LOADING === */
.community-card-image.community-lazy { opacity: 0; transition: opacity 0.3s ease; }
.community-card-image.community-lazy-loaded { opacity: 1; }

/* === BREADCRUMB === */
.community-breadcrumb ol {
    list-style: none; padding: 0; margin: 0 0 20px;
    display: flex; flex-wrap: wrap; align-items: center; font-size: 0.9rem;
}
.community-breadcrumb li { display: inline-flex; align-items: center; }
.community-breadcrumb a { color: var(--community-primary); text-decoration: none; }
.community-breadcrumb a:hover { text-decoration: underline; }
.community-breadcrumb-separator { margin: 0 8px; color: var(--community-text-light); }
.community-breadcrumb [aria-current="page"] { color: var(--community-text-light); }

/* === RELATED POSTS === */
.community-related-wrapper { margin-top: 50px; padding-top: 30px; border-top: 1px solid #e0e0e0; }
.community-related-title {
    font-size: 1.4rem; color: var(--community-text); margin-bottom: 24px;
    font-family: var(--community-font-family);
}
.community-related-grid { display: grid; gap: var(--community-grid-gap); }
.community-related-grid.community-grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
.community-related-grid.community-grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 767px) { .community-related-grid { grid-template-columns: 1fr; } }

.community-related-card {
    background: var(--community-card-bg); border-radius: var(--community-card-radius);
    overflow: hidden; box-shadow: var(--community-card-shadow);
    transition: box-shadow var(--community-transition), transform var(--community-transition);
}
.community-related-card:hover { box-shadow: var(--community-card-shadow-hover); transform: translateY(-2px); }
.community-related-card-link { text-decoration: none; color: inherit; display: block; }
.community-related-card-image-wrap { overflow: hidden; aspect-ratio: 16 / 10; }
.community-related-card-image-wrap img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform var(--community-transition);
}
.community-related-card:hover img { transform: scale(1.05); }
.community-related-card-content { padding: 16px 20px 20px; text-align: center; }
.community-related-card-title { font-size: 1.05rem; font-weight: 600; margin: 0 0 12px; color: var(--community-text); }
.community-related-card-btn {
    display: inline-block; padding: 8px 24px; background: var(--community-primary);
    color: #fff; border-radius: 4px; font-size: 0.85rem; font-weight: 500;
    transition: background var(--community-transition);
}
.community-related-card:hover .community-related-card-btn { background: var(--community-secondary); }
```

### 12.2 File: `assets/css/community-admin.css`

Style the admin meta boxes, settings page, import/export page, and shortcode wizard modal. Follow WordPress admin design patterns. Use `.community-admin-*` class prefix for all custom admin styles.

**Key admin styles to include:**

```css
/* Settings page */
.community-settings-wrap { max-width: 800px; }
.community-settings-section { margin-bottom: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; }
.community-settings-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }

/* Import/Export page */
.community-import-results { margin-top: 20px; }
.community-import-results .created { color: #46b450; }
.community-import-results .updated { color: #00a0d2; }
.community-import-results .error { color: #dc3232; }
.community-import-results .warning { color: #ffb900; }

/* Admin column badges */
.community-level-badge {
    display: inline-block; padding: 2px 8px; border-radius: 3px;
    font-size: 12px; font-weight: 600; color: #fff;
}
.community-level-badge.level-1 { background: #0073aa; }
.community-level-badge.level-2 { background: #46b450; }
.community-level-badge.level-3 { background: #f56e28; }

/* Shortcode wizard modal */
.community-wizard-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6); z-index: 100100; display: flex;
    align-items: center; justify-content: center;
}
.community-wizard-modal {
    background: #fff; border-radius: 4px; width: 550px; max-width: 90vw;
    max-height: 80vh; overflow-y: auto; box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}
.community-wizard-header {
    padding: 16px 20px; border-bottom: 1px solid #ddd; display: flex;
    justify-content: space-between; align-items: center;
}
.community-wizard-header h2 { margin: 0; font-size: 16px; }
.community-wizard-body { padding: 20px; }
.community-wizard-footer {
    padding: 16px 20px; border-top: 1px solid #ddd; text-align: right;
}
.community-wizard-preview {
    margin-top: 16px; padding: 12px; background: #f6f7f7; border: 1px solid #ddd;
    border-radius: 3px; font-family: monospace; font-size: 13px; word-break: break-all;
}
.community-wizard-field { margin-bottom: 16px; }
.community-wizard-field label { display: block; margin-bottom: 4px; font-weight: 600; }
.community-wizard-field select,
.community-wizard-field input { width: 100%; }
```

---

## 13. JavaScript Specification

### 13.1 File: `assets/js/community-grid.js`

Main frontend JavaScript. **Vanilla JavaScript only — no jQuery dependency.**

Full implementation spec provided in the Ajax Search section (Phase 3) and Lazy Loading section (Phase 8). The file must contain these functions:

1. `initGrid(wrapper)` — Initialize a single grid wrapper (search, pagination, lazy load)
2. `clientFilter(grid, query)` — Client-side instant filtering for no-pagination mode
3. `ajaxSearch(wrapper, config, search, page)` — Server-side Ajax search and pagination
4. `initLazyLoad(wrapper)` — Intersection Observer lazy loading setup

All wrapped in an IIFE to avoid global scope pollution. Initialize on `DOMContentLoaded`.

### 13.2 File: `assets/js/community-admin.js`

Admin JavaScript for the meta boxes. **jQuery is acceptable here** (WordPress admin already loads it).

```javascript
jQuery(document).ready(function($) {
    // Initialize Select2 on related posts field
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
                        exclude: communityAdmin.postId,
                    };
                },
                processResults: function(data) {
                    return { results: data };
                },
            },
            placeholder: 'Search and select communities...',
            minimumInputLength: 2,
            allowClear: true,
            multiple: true,
        });

        // Make selected items sortable
        $relatedField.next('.select2-container').find('.select2-selection__rendered').sortable({
            containment: 'parent',
            stop: function() {
                // Reorder the underlying select options to match drag order
                var sortedIds = [];
                $(this).find('.select2-selection__choice').each(function() {
                    sortedIds.push($(this).data('data').id);
                });
                // Rebuild select options in new order
                $relatedField.val(sortedIds).trigger('change');
            }
        });
    }

    // Clear all button
    $('#community-clear-related').on('click', function(e) {
        e.preventDefault();
        $relatedField.val(null).trigger('change');
    });
});
```

Localize with:
```php
wp_localize_script('community-admin', 'communityAdmin', array(
    'nonce'  => wp_create_nonce('community_admin_nonce'),
    'postId' => get_the_ID(),
));
```

### 13.3 File: `assets/js/community-shortcode-wizard.js`

Shortcode wizard modal logic. jQuery acceptable (admin context).

Must implement:
1. Button click handler to create and show modal
2. Dynamic field visibility based on shortcode type selection
3. Real-time shortcode preview string builder
4. Insert shortcode into editor on "Insert" click
5. Modal close on Cancel / Escape / outside click
6. Focus trap within modal (Tab cycles through fields)
7. Select2 initialization on parent community field (Ajax search)

---

## 14. Hooks & Filters Reference

The plugin must provide ALL of these hooks. Document each with a PHPDoc comment in the code.

### Filters

| Hook | Parameters | Purpose |
|------|-----------|---------|
| `community_grid_card_html` | `$html, $post, $atts` | Customize grid card HTML per card |
| `community_grid_query_args` | `$args, $atts` | Modify WP_Query args for the grid |
| `community_related_query_args` | `$args, $post_id` | Modify related posts query |
| `community_breadcrumb_separator` | `$separator` | Change breadcrumb separator (default " › ") |
| `community_breadcrumb_items` | `$items, $post_id` | Modify breadcrumb items array |
| `community_card_excerpt_length` | `$length, $post_id` | Override excerpt character limit per post |
| `community_cache_expiration` | `$seconds` | Override transient cache duration |
| `community_related_card_html` | `$html, $post` | Customize related post card HTML |
| `community_grid_no_results_html` | `$html, $atts` | Customize no-results message |
| `community_lazy_placeholder` | `$placeholder_url` | Override lazy load placeholder image |

### Actions

| Hook | Parameters | Purpose |
|------|-----------|---------|
| `community_before_grid` | `$atts, $query` | Fire before grid output |
| `community_after_grid` | `$atts, $query` | Fire after grid output |
| `community_before_related` | `$post_id, $related_posts` | Fire before related section |
| `community_after_related` | `$post_id, $related_posts` | Fire after related section |
| `community_post_duplicated` | `$new_post_id, $original_post_id` | Fire after post duplication |
| `community_import_row` | `$row_data, $post_id, $action` | Fire after each import row |
| `community_cache_cleared` | (none) | Fire after all transients are cleared |

---

## 15. Security Checklist

Implement ALL of these in every relevant file. Do not skip any.

- [ ] **Nonce verification** on every Ajax endpoint and form submission. Use `wp_verify_nonce()`. Return `wp_send_json_error()` on failure.
- [ ] **Capability checks** on all admin operations: `current_user_can('edit_posts')` for general, `current_user_can('edit_post', $post_id)` for specific posts.
- [ ] **Input sanitization** on all user input:
  - Text fields: `sanitize_text_field()`
  - WYSIWYG content: `wp_kses_post()`
  - Integers: `absint()`
  - URLs: `esc_url()`
  - Arrays of IDs: `array_map('absint', (array) $input)`
  - Search strings: `sanitize_text_field()`
- [ ] **Output escaping** on all rendered content:
  - Text: `esc_html()`
  - Attributes: `esc_attr()`
  - URLs: `esc_url()`
  - HTML content from meta: `wp_kses_post()`
  - Translated strings: `esc_html__()`, `esc_attr__()`
- [ ] **SQL injection prevention**: Always use `$wpdb->prepare()` for custom SQL. Never concatenate user input.
- [ ] **Direct file access prevention**: Every PHP file starts with `defined('ABSPATH') || exit;`
- [ ] **CSRF protection**: All forms include `wp_nonce_field()`. All Ajax calls send and verify nonces.
- [ ] **File upload validation** (import): Check MIME type, extension, and size. Use `wp_check_filetype()`.
- [ ] **Whitelist attribute values**: Shortcode attributes like `orderby`, `order`, `columns` must be validated against allowed values. Reject anything not in the whitelist.
- [ ] **Prevent open redirect**: All `wp_redirect()` calls use `admin_url()` or `get_edit_post_link()` — never user-supplied URLs.

---

## 16. Testing Checklist

After building each phase, verify everything works:

### Phase 1 — Foundation
- [ ] Plugin activates without errors or warnings
- [ ] "Communities" menu appears in admin sidebar with location icon
- [ ] New community posts can be created
- [ ] Parent community dropdown appears in "Page Attributes" meta box
- [ ] Divi Builder loads on community post edit screens
- [ ] Divi modules are available (text, image, etc.)
- [ ] Permalink works: `/community/post-slug/`
- [ ] Nested permalink works: `/community/parent-slug/child-slug/`
- [ ] 3-level nested URL works: `/community/grandparent/parent/child/`
- [ ] Visiting non-existent community URL shows 404

### Phase 2 — Grid System
- [ ] `[community_grid]` placed in Divi text module renders child posts as grid
- [ ] Grid shows correct column count (test 2, 3, 4)
- [ ] Cards display featured image, title, excerpt
- [ ] Clicking a card navigates to the child community post
- [ ] Cards with no featured image show placeholder (no broken images)
- [ ] Custom `_community_grid_excerpt` meta overrides WP excerpt on card
- [ ] Custom `_community_top_content` renders above grid
- [ ] Custom `_community_bottom_content` renders below grid
- [ ] Post with children auto-detects as listing page
- [ ] Post without children auto-detects as detail page
- [ ] Adding a child post to a "detail" post converts it to a listing page dynamically
- [ ] Responsive: 3 cols → 2 cols on tablet → 1 col on mobile
- [ ] Grid with 0 children shows appropriate empty state

### Phase 3 — Ajax Search & Pagination
- [ ] Search input visible above grid when `show_search="true"`
- [ ] Search input hidden when `show_search="false"`
- [ ] No-pagination mode: typing filters cards instantly (client-side)
- [ ] No-pagination mode: partial text matches work (e.g., "alca" matches "Alcazaba")
- [ ] Clear button (×) appears when text entered
- [ ] Clear button resets search and shows all cards
- [ ] "No communities found" message for empty search results
- [ ] Paginated mode: pagination buttons appear below grid
- [ ] Paginated mode: clicking page number loads new content via Ajax
- [ ] Paginated mode: search queries ALL posts, not just current page
- [ ] Loading spinner appears during Ajax requests
- [ ] Spinner disappears after content loads
- [ ] Page scrolls to grid top after pagination click
- [ ] Previous/Next buttons disable correctly on first/last pages
- [ ] Smart pagination ellipsis works for 8+ pages

### Phase 4 — Related Posts & Breadcrumbs
- [ ] `[community_related]` renders on detail pages (auto-appended)
- [ ] Manually selected related posts appear in selected order
- [ ] Empty related meta falls back to sibling posts automatically
- [ ] Only-child post falls back to cousin posts
- [ ] Related section title defaults to "Properties near {post_title}"
- [ ] Custom `_community_related_title` overrides default title
- [ ] "Explore" button on related cards styled correctly
- [ ] Related cards link to correct community posts
- [ ] `[community_breadcrumb]` displays correct hierarchy path
- [ ] All ancestor links in breadcrumb navigate correctly
- [ ] Current page shows as plain text (no link) in breadcrumb
- [ ] Home link points to site root
- [ ] Schema.org BreadcrumbList markup present in HTML source
- [ ] Breadcrumb renders correctly at all 3 levels

### Phase 5 — Settings
- [ ] Settings page appears at Settings → Community CPT
- [ ] All settings save correctly on form submission
- [ ] Settings persist after save (reload and verify)
- [ ] Default values apply before first save
- [ ] Pagination mode toggle works
- [ ] Flush Permalinks button triggers flush and shows success
- [ ] Clear Cache button clears transients and shows success
- [ ] Invalid input values are rejected (e.g., per_page = -5)

### Phase 6 — Admin Enhancements
- [ ] Custom columns visible in Communities list table
- [ ] Thumbnail column shows 60×40 image
- [ ] Parent column shows linked parent name (or "—" for root)
- [ ] Level column shows correct L1/L2/L3 badge with color
- [ ] Children column shows count, linked to filtered view if > 0
- [ ] "Duplicate" action link appears in post row actions
- [ ] Clicking Duplicate creates draft copy with "(Copy)" in title
- [ ] Duplicated post has same parent, content, meta, featured image
- [ ] Duplicated post has menu_order incremented by 1

### Phase 7 — Import/Export
- [ ] Import/Export submenu appears under Communities menu
- [ ] Export button downloads a .csv file
- [ ] CSV contains correct headers and all community data
- [ ] CSV opens correctly in Excel (UTF-8 BOM present)
- [ ] Import with new posts creates them correctly
- [ ] Import with existing IDs updates them correctly
- [ ] Parent relationships resolve by slug
- [ ] Warning shown for unresolved parent slugs
- [ ] Featured images download and attach from URL
- [ ] Results summary shows accurate counts
- [ ] Invalid CSV file shows error message
- [ ] Large CSV (100+ rows) processes without timeout

### Phase 8 — Performance
- [ ] Lazy loading: images use placeholder until scrolled into view
- [ ] Lazy loading: images fade in when loaded
- [ ] Lazy loading: works after Ajax content load (re-initialized)
- [ ] CSS/JS files only load on community-related pages
- [ ] CSS/JS do NOT load on unrelated pages (check page source)
- [ ] Transient cache created on first grid load (check wp_options table)
- [ ] Cache invalidated when community post is saved
- [ ] Cache invalidated when community post is trashed

### Phase 9 — Shortcode Wizard
- [ ] "Community Grid" button appears in editor toolbar
- [ ] Clicking button opens modal overlay
- [ ] Fields show/hide correctly based on shortcode type
- [ ] Shortcode preview updates live as fields change
- [ ] "Insert" button inserts shortcode into editor content
- [ ] Modal closes on Cancel button
- [ ] Modal closes on Escape key
- [ ] Modal closes when clicking outside
- [ ] Focus trapped within modal (Tab doesn't reach background)
- [ ] Parent community Select2 searches posts via Ajax

### Integration & Cross-cutting
- [ ] Plugin works with Divi theme active
- [ ] Plugin does not cause PHP errors/warnings with WP_DEBUG enabled
- [ ] Plugin does not break other admin pages
- [ ] All Ajax endpoints return proper JSON responses
- [ ] Failed Ajax requests show user-friendly error messages
- [ ] CSS does not conflict with Divi styles (check card fonts, spacing, colors)
- [ ] JS does not conflict with Divi scripts (check console for errors)
- [ ] All strings are translation-ready (wrapped in __() or esc_html__())
- [ ] All user-facing HTML is accessible (ARIA labels, keyboard navigation)

---

## Implementation Notes

### Code Standards
- Follow WordPress PHP Coding Standards (tabs for indentation, Yoda conditions, etc.)
- Use PHPDoc comments on all classes, methods, filters, and actions
- Prefix all functions, classes, and constants with `community_` or `Community_`
- Use `__()` and `esc_html__()` for all user-facing strings (i18n ready)
- No jQuery dependency in frontend JS — vanilla JavaScript only
- jQuery is acceptable in admin JS (WordPress admin already loads it)

### Compatibility
- WordPress: 6.0+
- PHP: 7.4+
- Divi Theme: 4.0+
- Browsers: Chrome, Firefox, Safari, Edge (last 2 versions)

### Error Handling
- All Ajax handlers must return `wp_send_json_success()` or `wp_send_json_error()`
- Template partials must handle missing data gracefully (no PHP errors/warnings)
- Import should never halt on a single row error — log it and continue
- Settings page should validate and sanitize all input

### Build Order

Build sequentially. Test each phase before moving to the next.

```
Phase 1 → test → commit
Phase 2 → test → commit
Phase 3 → test → commit
Phase 4 → test → commit
Phase 5 → test → commit
Phase 6 → test → commit
Phase 7 → test → commit
Phase 8 → test → commit
Phase 9 → test → commit
Final integration test → fix issues → final commit
```
