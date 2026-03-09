# Community CPT - WordPress Plugin

A powerful WordPress plugin that creates a hierarchical Custom Post Type for communities and locations with grid listings, Ajax search, Divi Builder integration, and related posts functionality.

## Features

### Core Functionality
- **Hierarchical Post Type** - Support for 3-level nested hierarchy (e.g., State → City → Neighborhood)
- **Grid Listings** - Responsive grid display with 2, 3, or 4 column layouts
- **Ajax Search** - Real-time search filtering without page reload
- **Ajax Pagination** - Smooth pagination with configurable items per page
- **Lazy Loading** - Images load on scroll using Intersection Observer
- **Transient Caching** - Performance optimization with automatic cache invalidation

### Divi Integration
- Full Divi Builder support on community posts
- All Divi modules available for content creation
- Automatic fullwidth layout (no sidebar)
- Post meta automatically hidden on frontend
- Shortcode wizard button in Divi editor

### Related Posts
- Automatic "Communities near [Location]" section on detail pages
- Smart fallback: siblings → cousins → random communities
- Customizable title and post selection
- 4x2 grid layout (8 communities, 4 columns)

### Admin Features
- **Custom Admin Columns** - Thumbnail, parent, hierarchy level, children count
- **Duplicate Post** - One-click duplication with all meta preserved
- **CSV Import/Export** - Bulk operations with image URL sideloading
- **Archive Settings** - Configure archive page title, content, and layout
- **Conditional Meta Boxes** - Top/bottom content fields only on listing pages

### Shortcodes
- `[community_grid]` - Display community grid with search and pagination
- `[community_related]` - Show related communities section
- `[community_breadcrumb]` - Schema.org compliant breadcrumb navigation

## Installation

1. Download or clone this repository
2. Upload to `/wp-content/plugins/community-cpt/`
3. Activate through WordPress admin → Plugins
4. Go to Communities → Settings to configure

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Divi Theme (optional, for builder integration)

## Configuration

### Plugin Settings
Navigate to **Communities → Settings** to configure:

| Setting | Description | Default |
|---------|-------------|---------|
| Pagination Mode | all / ajax / numbered | all |
| Items Per Page | Posts per grid page | 20 |
| Default Columns | Grid columns (2-4) | 3 |
| Show Search | Enable search input | Yes |
| Related Count | Related posts to show | 8 |
| Excerpt Length | Card excerpt characters | 120 |
| Card Style | default / compact | default |
| Lazy Load Images | Enable lazy loading | Yes |
| Cache Duration | Transient cache seconds | 3600 |

### Archive Settings
Navigate to **Communities → Archive Settings** to configure:
- Archive page title
- Content above/below grid
- Number of columns

## Shortcode Usage

### Community Grid
```
[community_grid]
[community_grid parent="123" columns="4" per_page="12"]
[community_grid show_search="false" pagination="numbered"]
```

**Attributes:**
| Attribute | Description | Default |
|-----------|-------------|---------|
| parent | Parent post ID (0 for top-level) | current post or 0 |
| columns | Grid columns (2-4) | 3 |
| per_page | Items per page | 20 |
| show_search | Show search input | true |
| pagination | all / ajax / numbered | all |
| style | default / compact | default |

### Community Related
```
[community_related]
[community_related title="Nearby Communities" limit="6" columns="3"]
```

**Attributes:**
| Attribute | Description | Default |
|-----------|-------------|---------|
| title | Section heading | "Communities near [Post Title]" |
| limit | Number of posts | 8 |
| columns | Grid columns (2-4) | 4 |

### Community Breadcrumb
```
[community_breadcrumb]
[community_breadcrumb home_text="Home" separator="→"]
```

**Attributes:**
| Attribute | Description | Default |
|-----------|-------------|---------|
| home_text | Home link text | "Home" |
| separator | Breadcrumb separator | "›" |

## Page Types

### Listing Pages
Communities with children automatically display as listing pages:
- Shows child communities in a grid
- Optional top/bottom content areas (via meta box)
- Grid inherits from post's children

### Detail Pages
Communities without children display as detail pages:
- Full Divi Builder content
- Automatic related communities section at bottom
- Fullwidth layout, no sidebar

## CSV Import/Export

### Export
1. Go to **Communities → Import/Export**
2. Click "Export Communities"
3. CSV includes: ID, Title, Slug, Parent, Excerpt, Featured Image URL, Status

### Import
1. Prepare CSV with columns: `title`, `slug`, `parent_slug`, `excerpt`, `image_url`, `status`
2. Go to **Communities → Import/Export**
3. Upload CSV file
4. Images are automatically sideloaded to Media Library

## Custom Meta Fields

| Meta Key | Description |
|----------|-------------|
| `_community_grid_excerpt` | Custom excerpt for grid cards |
| `_community_top_content` | Content above grid (listing pages) |
| `_community_bottom_content` | Content below grid (listing pages) |
| `_community_related_title` | Custom related section title |
| `_community_related_posts` | Array of manually selected related post IDs |

## CSS Customization

The plugin uses CSS custom properties for easy theming:

```css
:root {
    --community-primary: #1B4F72;
    --community-secondary: #D4A017;
    --community-text: #2C3E50;
    --community-text-light: #7F8C8D;
    --community-bg: #FFFFFF;
    --community-card-bg: #FFFFFF;
    --community-card-radius: 8px;
    --community-card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    --community-card-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
    --community-grid-gap: 30px;
    --community-transition: 0.3s ease;
}
```

## Hooks & Filters

### Actions
- `community_cpt_before_grid` - Before grid output
- `community_cpt_after_grid` - After grid output

### Filters
- `community_cpt_grid_query_args` - Modify grid WP_Query arguments
- `community_cpt_card_classes` - Filter card CSS classes
- `community_cpt_excerpt_length` - Filter excerpt character length

## File Structure

```
community-cpt/
├── community-cpt.php          # Main plugin file
├── assets/
│   ├── css/
│   │   ├── community-grid.css    # Frontend styles
│   │   └── community-admin.css   # Admin styles
│   └── js/
│       ├── community-grid.js     # Frontend scripts
│       ├── community-admin.js    # Admin scripts
│       └── community-shortcode-wizard.js
├── includes/
│   ├── class-community-cpt.php           # CPT registration
│   ├── class-community-meta.php          # Meta boxes
│   ├── class-community-shortcodes.php    # Shortcode handlers
│   ├── class-community-ajax.php          # Ajax handlers
│   ├── class-community-breadcrumb.php    # Breadcrumb functionality
│   ├── class-community-related.php       # Related posts logic
│   ├── class-community-settings.php      # Settings page
│   ├── class-community-divi.php          # Divi integration
│   ├── class-community-admin-columns.php # Admin columns
│   ├── class-community-duplicate.php     # Duplicate functionality
│   ├── class-community-import-export.php # CSV import/export
│   ├── class-community-shortcode-wizard.php
│   └── class-community-archive-settings.php
├── templates/
│   ├── archive-community.php    # Archive template
│   └── partials/
│       ├── grid-wrapper.php     # Grid container
│       ├── grid-card.php        # Individual card
│       ├── pagination.php       # Pagination controls
│       └── related-grid.php     # Related posts grid
└── languages/
    └── community-cpt.pot        # Translation template
```

## Changelog

### 1.0.0
- Initial release
- Hierarchical CPT with 3-level support
- Grid listings with Ajax search/pagination
- Divi Builder integration
- Related posts with smart fallback
- CSV Import/Export
- Admin columns and duplicate functionality

## License

GPL v2 or later

## Author

RealtySoft BV
