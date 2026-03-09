# Community CPT - WordPress Plugin

A hierarchical Custom Post Type plugin for managing community/location pages with grid listings, Ajax search, Divi Builder integration, and related posts.

## Table of Contents

1. [Installation](#installation)
2. [Quick Start](#quick-start)
3. [Creating Your Hierarchy](#creating-your-hierarchy)
4. [Shortcodes](#shortcodes)
5. [Admin Features](#admin-features)
6. [Settings](#settings)
7. [Hooks & Filters](#hooks--filters)
8. [FAQ](#faq)

---

## Installation

1. Upload the `community-cpt` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Settings → Permalinks** and click "Save Changes" to flush rewrite rules

---

## Quick Start

1. Go to **Communities** in the admin sidebar
2. Create a parent community (e.g., "Costa del Sol")
3. Create child communities under it (e.g., "Marbella", "Estepona")
4. Edit the parent, add `[community_grid]` shortcode
5. View the parent page - children appear as a card grid

---

## Creating Your Hierarchy

The plugin supports **3 levels of nesting**:

```
Level 1: Costa del Sol                    → /community/costa-del-sol/
  Level 2: Marbella                       → /community/costa-del-sol/marbella/
    Level 3: Golden Mile                  → /community/costa-del-sol/marbella/golden-mile/
    Level 3: Puerto Banus                 → /community/costa-del-sol/marbella/puerto-banus/
  Level 2: Estepona (no children)         → /community/costa-del-sol/estepona/
```

### How to Set Parent

When creating/editing a community post:
1. Look for the **Page Attributes** meta box (right sidebar)
2. Select the parent from the **Parent** dropdown
3. Optionally set **Menu Order** for custom sorting

### Auto-Detection Behavior

| Condition | Page Type | What Happens |
|-----------|-----------|--------------|
| Post has children | Listing Page | Shows grid of children |
| Post has no children | Detail Page | Shows full content + related posts |

This is determined dynamically - no manual setting required.

---

## Shortcodes

### 1. Community Grid

Displays child communities as a responsive card grid.

```
[community_grid]
```

#### Attributes

| Attribute | Description | Options | Default |
|-----------|-------------|---------|---------|
| `parent_id` | Override parent (use specific post ID) | Any post ID | Current post |
| `columns` | Number of grid columns | 2, 3, 4 | 3 |
| `per_page` | Posts per page (0 = show all) | 0-100 | From settings |
| `show_search` | Show search input | true, false | true |
| `orderby` | Sort field | menu_order, title, date | menu_order |
| `order` | Sort direction | ASC, DESC | ASC |
| `style` | Card style | default, compact | default |

#### Examples

```
// Basic - show all children of current post
[community_grid]

// 4 columns, sorted by title
[community_grid columns="4" orderby="title"]

// Paginated with 12 per page, no search
[community_grid per_page="12" show_search="false"]

// Show children of a specific post
[community_grid parent_id="123"]

// Compact style (no excerpts)
[community_grid style="compact" columns="4"]
```

---

### 2. Community Breadcrumb

Displays a breadcrumb navigation trail with Schema.org markup.

```
[community_breadcrumb]
```

#### Output Example

```
Home › Costa del Sol › Marbella › Golden Mile
```

- All ancestors are linked
- Current page is plain text
- Includes Schema.org BreadcrumbList markup for SEO

---

### 3. Community Related

Displays related community posts. Auto-appended on detail pages, but can be manually placed.

```
[community_related]
```

#### Attributes

| Attribute | Description | Options | Default |
|-----------|-------------|---------|---------|
| `title` | Section heading | Any text | "Properties near {post_title}" |
| `limit` | Number of posts | 1-6 | 3 |
| `columns` | Grid columns | 2, 3 | 3 |

#### Examples

```
// Default - 3 related posts
[community_related]

// Custom title and limit
[community_related title="Nearby Communities" limit="4"]

// 2 columns layout
[community_related columns="2" limit="4"]
```

#### Selection Logic

Related posts are selected in this priority order:

1. **Manually selected** - Posts chosen in the "Related Communities" meta box
2. **Siblings** - Other children of the same parent
3. **Cousins** - Children of posts that share the same grandparent

---

## Admin Features

### Custom Admin Columns

The Communities list table shows:

| Column | Description |
|--------|-------------|
| Thumbnail | 60x40px featured image |
| Parent | Link to parent post (or "—" for root) |
| Level | L1, L2, or L3 badge with color |
| Children | Count of direct children (linked to filtered view) |
| Order | Menu order value |

### Duplicate Post

Click **Duplicate** in the row actions to create a copy:
- Title appended with "(Copy)"
- Status set to "Draft"
- All meta fields copied
- Featured image copied
- Menu order incremented by 1

### Import / Export

Located at **Communities → Import / Export**

#### Export

- Downloads all communities as CSV
- Includes: ID, title, slug, parent_slug, status, menu_order, excerpt, meta fields, featured image URL

#### Import

- Upload a CSV file to create/update communities
- Must contain `title` and `slug` columns minimum
- Parent relationships resolved by slug
- Featured images downloaded from URLs
- Use the export file as a template

### Shortcode Wizard

When editing a community post:
1. Click the **Community Grid** button in the editor toolbar
2. Select shortcode type and configure options
3. Live preview updates as you change settings
4. Click **Insert Shortcode** to add to content

---

## Settings

Go to **Settings → Community CPT**

### Grid Display

| Setting | Description |
|---------|-------------|
| Pagination Mode | "Show all" or "Paginated" |
| Posts Per Page | Number per page when paginated (1-100) |
| Default Columns | 2, 3, or 4 |
| Card Style | Default (image + title + excerpt) or Compact (image + title only) |
| Excerpt Length | Character limit for card excerpts (50-300) |

### Search

| Setting | Description |
|---------|-------------|
| Show Search by Default | Display search input above grids |

### Related Posts

| Setting | Description |
|---------|-------------|
| Related Posts Count | Number of related posts to display (1-6) |

### Performance

| Setting | Description |
|---------|-------------|
| Lazy Loading | Use Intersection Observer for images |
| Cache Duration | 1 hour, 6 hours, 12 hours, 24 hours, or No cache |

### Tools

| Button | Action |
|--------|--------|
| Flush Permalinks | Regenerate rewrite rules |
| Clear All Cache | Delete all community grid transients |

---

## Meta Fields

Each community post has these custom fields:

### Community Settings (main meta box)

| Field | Description |
|-------|-------------|
| Grid Card Excerpt | Short description for grid cards (overrides WP excerpt) |
| Top Content | HTML rendered above the grid on listing pages |
| Bottom Content | HTML rendered below the grid on listing pages |

### Related Communities (sidebar meta box)

| Field | Description |
|-------|-------------|
| Related Section Title | Custom heading (default: "Properties near {title}") |
| Related Communities | Multi-select to manually choose related posts |

---

## Hooks & Filters

### Filters

```php
// Customize grid card HTML
add_filter( 'community_grid_card_html', function( $html, $post, $atts ) {
    return $html;
}, 10, 3 );

// Modify grid query arguments
add_filter( 'community_grid_query_args', function( $args, $atts ) {
    return $args;
}, 10, 2 );

// Modify related posts query
add_filter( 'community_related_query_args', function( $args, $post_id ) {
    return $args;
}, 10, 2 );

// Change breadcrumb separator (default: " › ")
add_filter( 'community_breadcrumb_separator', function( $separator ) {
    return ' / ';
} );

// Modify breadcrumb items
add_filter( 'community_breadcrumb_items', function( $items, $post_id ) {
    return $items;
}, 10, 2 );

// Override excerpt length per post
add_filter( 'community_card_excerpt_length', function( $length, $post_id ) {
    return $length;
}, 10, 2 );

// Override cache duration
add_filter( 'community_cache_expiration', function( $seconds ) {
    return 7200; // 2 hours
} );

// Customize related card HTML
add_filter( 'community_related_card_html', function( $html, $post ) {
    return $html;
}, 10, 2 );

// Customize no results message
add_filter( 'community_grid_no_results_html', function( $html, $atts ) {
    return '<p>No communities available.</p>';
}, 10, 2 );

// Override lazy load placeholder
add_filter( 'community_lazy_placeholder', function( $url ) {
    return 'https://example.com/placeholder.svg';
} );
```

### Actions

```php
// Before grid output
add_action( 'community_before_grid', function( $atts, $query ) {
    // Do something before the grid
}, 10, 2 );

// After grid output
add_action( 'community_after_grid', function( $atts, $query ) {
    // Do something after the grid
}, 10, 2 );

// Before related section
add_action( 'community_before_related', function( $post_id, $related_posts ) {
    // Do something before related posts
}, 10, 2 );

// After related section
add_action( 'community_after_related', function( $post_id, $related_posts ) {
    // Do something after related posts
}, 10, 2 );

// After post duplication
add_action( 'community_post_duplicated', function( $new_post_id, $original_post_id ) {
    // Do something with the duplicated post
}, 10, 2 );

// After CSV row import
add_action( 'community_import_row', function( $row_data, $post_id, $action ) {
    // $action is 'created' or 'updated'
}, 10, 3 );

// After cache is cleared
add_action( 'community_cache_cleared', function() {
    // Do something after cache clear
} );
```

---

## CSS Customization

The plugin uses CSS custom properties for easy theming. Add to your theme's CSS or Divi custom CSS:

```css
:root {
    --community-primary: #1B4F72;
    --community-secondary: #D4A017;
    --community-text: #2C3E50;
    --community-text-light: #7F8C8D;
    --community-card-radius: 8px;
    --community-card-shadow: 0 2px 8px rgba(0,0,0,0.08);
    --community-card-shadow-hover: 0 8px 24px rgba(0,0,0,0.12);
    --community-grid-gap: 30px;
    --community-transition: 0.3s ease;
}
```

---

## FAQ

### The grid is not showing any posts

- Make sure the parent post has published children
- Check that the shortcode is on a community post (or specify `parent_id`)
- Verify children have "Publish" status

### Permalinks are showing 404

- Go to **Settings → Permalinks** and click "Save Changes"
- Or use the "Flush Permalinks" button in plugin settings

### How do I change the URL slug from /community/?

The slug is hardcoded. To change it, edit `includes/class-community-cpt.php` and modify the `rewrite` array in `register_post_type()`.

### Can I use this without Divi?

Yes! The plugin works with any theme. Divi integration is optional and only enables the Divi Builder on community posts.

### How do I add a community to the menu?

Go to **Appearance → Menus**, enable "Communities" in Screen Options, then add communities like pages.

### Images are not lazy loading

- Check that "Enable Lazy Loading Images" is enabled in settings
- Verify JavaScript is not being blocked
- Check browser console for errors

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Divi Theme 4.0+ (optional, for Divi Builder support)

---

## Support

For issues or feature requests, contact the plugin author or submit a ticket.

---

## Changelog

### 1.0.0
- Initial release
- Hierarchical CPT with 3-level nesting
- Grid shortcode with Ajax search and pagination
- Breadcrumb shortcode with Schema.org markup
- Related posts with automatic sibling/cousin fallback
- Settings page with grid, search, and performance options
- Admin columns for hierarchy visualization
- Duplicate post functionality
- CSV import/export
- Shortcode wizard for Divi
- Lazy loading with Intersection Observer
- Transient caching with auto-invalidation
