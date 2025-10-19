# ScooterCam Weather Snips

**Version:** 1.0.0  
**WordPress Plugin**

## Description

Curated weather forecast text snippets with a visual admin management interface. Display random, personality-filled weather commentary throughout your site.

## Features

- ✅ Random weather snippet display
- ✅ Visual admin interface for managing snips
- ✅ Add, edit, and delete snippets easily
- ✅ No database required (JSON file storage)
- ✅ Flexible shortcode with customization options
- ✅ Real-time editing with inline forms

## Installation

1. Upload the `scootercam-snips` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress 'Plugins' menu
3. Go to **Weather Snips** in the WordPress admin menu
4. Start adding your weather snips!

## Usage

### Basic Shortcode

```
[scootercam-snip]
```

or

```
[scootercam-snips]
```

### Shortcode Attributes

**Custom CSS Class:**
```
[scootercam-snip class="forecast-box"]
```

**Custom HTML Wrapper** (default is `<p>`):
```
[scootercam-snip wrapper="div"]
```

**Add Prefix Text:**
```
[scootercam-snip prefix="Today's Forecast: "]
```

**Add Suffix Text:**
```
[scootercam-snip suffix=" - Updated hourly"]
```

**Custom Inline Styles:**
```
[scootercam-snip style="color: blue; font-weight: bold;"]
```

**Combined Example:**
```
[scootercam-snip wrapper="div" class="weather-commentary" prefix="📢 "]
```

## Admin Interface

### Accessing the Manager

Navigate to: **WordPress Admin → Weather Snips**

### Managing Snips

**Add New Snip:**
1. Type your weather commentary in the text area
2. Click "Add Snip"
3. Done!

**Edit Existing Snip:**
1. Click "Edit" on any snip
2. Modify the text
3. Click "Save"

**Delete Snip:**
1. Click "Delete" on any snip
2. Confirm deletion
3. Snip removed

### Snip Count

The admin page displays total number of available snips at the top.

## Data Storage

Snips are stored in: `/home/scootercam/public_html/wx/snips.json`

**Format:**
```json
{
  "forecasts": [
    "Perfect beach weather ahead!",
    "Grab an umbrella, rain's coming.",
    "Bundle up, it's going to be chilly!"
  ]
}
```

## Examples of Good Snips

- "Perfect sailing conditions expected!"
- "Beach day weather incoming ☀️"
- "Small craft advisory - stay ashore"
- "Fog rolling in, drive carefully"
- "Ideal conditions for outdoor activities"

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Write permissions for `/home/scootercam/public_html/wx/` directory

## Permissions

Requires `manage_options` capability (Administrator role) to access the admin interface.

## Technical Details

- Uses AJAX for smooth inline editing
- jQuery-based admin interface
- Automatic sanitization of user input
- Nonce verification for security
- Responsive admin design

## Troubleshooting

**"Error saving snip"**  
Check file permissions on `/home/scootercam/public_html/wx/snips.json` - should be writable by web server.

**No snips displaying**  
Ensure you've added at least one snip in the admin interface.

## License

GPL v2 or later

## Author

ScooterCam  
https://scootercam.com

## Changelog

### 1.0.0
- Initial release
- Admin management interface
- Random snip display
- Flexible shortcode options
- JSON file storage