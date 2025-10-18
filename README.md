# 🗺️ Branch Manager - WordPress Plugin

A comprehensive WordPress plugin for managing and displaying company branches with interactive maps, filtering, and location-based features.

![Version](https://img.shields.io/badge/version-1.4-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-brightgreen)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-GPL--2.0-orange)

## 📋 Features

### Core Functionality
- ✅ **Custom Post Type** - Dedicated "Branches" post type for easy management
- 🌍 **Interactive Map** - Leaflet-powered map with clickable markers
- 🏢 **Branch Cards** - Beautiful 3-column responsive grid layout
- 🔍 **Advanced Filtering** - Search by name/address and filter by country/city
- 📍 **Geolocation** - Add latitude/longitude coordinates for precise positioning
- 🎨 **Modal Popups** - Detailed branch information in elegant modals
- 📱 **Fully Responsive** - Optimized for desktop, tablet, and mobile devices

### Taxonomies
- 🌎 **Countries** - Organize branches by country
- 🏙️ **Cities** - Categorize branches by city
- 🔗 **Hierarchical** - Support for parent-child relationships

### User Interface
- 🔎 **Real-time Search** - Instant filtering as you type
- 🎯 **Smart Selectors** - Dropdown filters for country and city
- 📄 **Pagination** - Configurable number of branches per page
- 🗺️ **Toggle Map** - Show/hide map with a single click
- 🔗 **Deep Linking** - Shareable URLs with pre-selected filters
- 💜 **Custom Styling** - Beautiful purple (#330A48) theme

### Technical Features
- ⚡ **Performance Optimized** - Efficient queries and lazy loading
- 🔒 **Security First** - Sanitized inputs and escaped outputs
- 🎨 **No Layout Conflicts** - Isolated styles prevent theme conflicts
- 🌐 **SEO Friendly** - Proper heading structure and semantic HTML
- ♿ **Accessible** - ARIA labels and keyboard navigation support

## 📦 Installation

### Method 1: Upload via WordPress Admin

1. Download the plugin ZIP file
2. Go to **WordPress Admin** → **Plugins** → **Add New**
3. Click **Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Activate the plugin

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `branch-manager` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin** → **Plugins**
4. Activate **Branch Manager**

### Method 3: Git Clone

```bash
cd wp-content/plugins/
git clone https://github.com/yourusername/branch-manager.git
```

## 🚀 Quick Start

### 1. Add Your First Branch

1. Go to **WordPress Admin** → **Branches** → **Add New**
2. Enter the branch name (e.g., "Manila Office")
3. Fill in the branch details:
   - **Contact Number**: Phone number
   - **Address**: Full street address
   - **Latitude**: GPS latitude (e.g., 14.5995)
   - **Longitude**: GPS longitude (e.g., 120.9842)
4. Upload a **Featured Image**
5. Select **Country** and **City** from the right sidebar
6. Click **Publish**

### 2. Add Countries and Cities

#### Add Countries
1. Go to **Branches** → **Countries**
2. Add countries like "Philippines", "Singapore", etc.

#### Add Cities
1. Go to **Branches** → **Cities**
2. Add cities like "Manila", "Quezon City", etc.

### 3. Display Branches on a Page

1. Create or edit a page
2. Add the shortcode:

```
[branch_map]
```

3. Publish the page

## 🎯 Shortcode Usage

### Basic Shortcode

```
[branch_map]
```

### Shortcode Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `per_page` | `9` | Number of branches per page |
| `show_map` | `true` | Show map by default (`true`/`false`) |

### Examples

**Show 6 branches per page:**
```
[branch_map per_page="6"]
```

**Hide map by default:**
```
[branch_map show_map="false"]
```

**Custom configuration:**
```
[branch_map per_page="12" show_map="false"]
```

## 🔗 URL Parameters

Share filtered views with direct links:

### Filter by Country
```
https://yoursite.com/branches/?country=5
```

### Filter by City
```
https://yoursite.com/branches/?city=12
```

### Combined Filters
```
https://yoursite.com/branches/?country=5&city=12
```

### With Pagination
```
https://yoursite.com/branches/?country=5&branch_page=2
```

## 🎨 Customization

### Custom Styles

Add custom CSS to your theme's `style.css` or through **Appearance** → **Customize** → **Additional CSS**:

```css
/* Change primary color */
.branch-card {
    background: #your-color !important;
}

/* Adjust grid columns */
.branch-grid {
    grid-template-columns: repeat(4, 1fr) !important;
}

/* Custom card hover effect */
.branch-card:hover {
    transform: scale(1.05) !important;
}
```

### Modify Grid Layout

To change from 3 columns to 4 columns, add:

```css
.branch-grid {
    grid-template-columns: repeat(4, 1fr);
}
```

### Change Card Height

```css
.branch-image {
    height: 250px; /* Default is 200px */
}
```

## 📱 Responsive Breakpoints

The plugin uses these responsive breakpoints:

- **Desktop**: 3 columns (> 992px)
- **Tablet**: 2 columns (577px - 992px)
- **Mobile**: 1 column (< 576px)

## 🛠️ Technical Details

### File Structure

```
branch-manager/
├── assets/
│   ├── leaflet.css          # Leaflet map styles
│   ├── leaflet.js           # Leaflet map library
│   └── images/              # Map marker icons
├── includes/
│   ├── post-type.php        # Custom post type & taxonomies
│   └── shortcode.php        # Shortcode & frontend display
├── .github/
│   └── workflows/
│       └── deploy.yml       # Auto-deployment workflow
├── branch-manager.php       # Main plugin file
├── .gitignore              # Git ignore rules
└── README.md               # This file
```

### Dependencies

- **Leaflet.js** v1.9.4 - Open-source map library
- **Micromodal.js** v0.4.10 - Accessible modal dialogs
- **OpenStreetMap** - Free map tiles

### Browser Support

- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile browsers

## 🔧 Development

### Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

### Local Development Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/branch-manager.git
cd branch-manager
```

2. Install in WordPress:
```bash
cp -r branch-manager /path/to/wordpress/wp-content/plugins/
```

3. Activate the plugin in WordPress admin

### Auto-Deployment

This plugin uses GitHub Actions for automatic FTP deployment:

1. Fork this repository
2. Add GitHub Secrets:
   - `FTP_SERVER` - Your FTP hostname
   - `FTP_USERNAME` - Your FTP username
   - `FTP_PASSWORD` - Your FTP password
3. Push to `main` branch to trigger deployment

## 📊 Database Schema

### Post Meta Keys

| Meta Key | Description | Example |
|----------|-------------|---------|
| `_branch_contact` | Contact number | `+63 2 1234 5678` |
| `_branch_address` | Full address | `123 Main St, Manila` |
| `_branch_lat` | Latitude | `14.5995` |
| `_branch_lng` | Longitude | `120.9842` |

### Taxonomies

| Taxonomy | Slug | Hierarchical |
|----------|------|--------------|
| Countries | `branch_country` | Yes |
| Cities | `branch_city` | Yes |

## 🐛 Troubleshooting

### Map Not Showing

**Problem**: Map container is empty
**Solution**: Check if latitude/longitude are set for branches

### Markers Not Appearing

**Problem**: No markers on the map
**Solution**: Ensure branches have valid lat/lng coordinates

### Filtering Not Working

**Problem**: Dropdowns don't filter results
**Solution**: Assign countries/cities to branches in WordPress admin

### Pagination Issues

**Problem**: Pagination links not working
**Solution**: Go to **Settings** → **Permalinks** and click **Save Changes**

### Styling Conflicts

**Problem**: Layout looks broken
**Solution**: Check for CSS conflicts with your theme. Add `!important` to plugin styles if needed.

## 📝 Changelog

### Version 1.4 (Current)
- ✨ Added pagination support
- ✨ Map toggle functionality
- ✨ URL parameter filtering
- ✨ Deep linking support
- 🎨 Improved mobile responsiveness
- 🐛 Fixed modal z-index issues

### Version 1.3
- ✨ Added country and city taxonomies
- ✨ Search functionality
- ✨ Modal popups for branch details
- 🎨 3-column grid layout

### Version 1.2
- ✨ Added interactive map
- ✨ Custom branch post type
- 🎨 Basic grid display

### Version 1.0
- 🎉 Initial release

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use meaningful variable and function names
- Add comments for complex logic
- Test on multiple browsers and devices

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
Branch Manager - WordPress Plugin
Copyright (C) 2024 Modern Chameleon Digital

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Modern Chameleon Digital**
- Website: [https://modernchameleonph.com](https://modernchameleonph.com)
- GitHub: [@modernchameleonph](https://github.com/modernchameleonph)

## 🙏 Credits

- [Leaflet](https://leafletjs.com/) - Open-source map library
- [OpenStreetMap](https://www.openstreetmap.org/) - Free map data
- [Micromodal](https://micromodal.vercel.app/) - Accessible modals

## 📞 Support

For support, please:

1. Check the [Troubleshooting](#-troubleshooting) section
2. Search [existing issues](https://github.com/yourusername/branch-manager/issues)
3. Create a [new issue](https://github.com/yourusername/branch-manager/issues/new) if needed

## 🗺️ Roadmap

- [ ] Import/Export branches via CSV
- [ ] Google Maps integration option
- [ ] Custom map marker icons
- [ ] Branch hours and status (open/closed)
- [ ] Distance calculator
- [ ] Multi-language support (WPML/Polylang)
- [ ] REST API endpoints
- [ ] Gutenberg blocks
- [ ] Elementor widget

## ⭐ Show Your Support

If you find this plugin helpful, please:
- ⭐ Star this repository
- 🐛 Report bugs
- 💡 Suggest new features
- 🔀 Submit pull requests

---

Made with ❤️ by [Modern Chameleon Digital](https://modernchameleonph.com)