# MPRO Console Log

[![Version](https://img.shields.io/badge/version-1.1.1-2271b1.svg)](https://moghadam.pro/mpro-plugins/)
[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-21759b.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

A lightweight WordPress plugin for creating animated, console-style log streams with organic timing, configurable motion, live preview, theme-palette colors, and shortcode-based placement.

MPRO Console Log is designed for portfolio hero sections, product showcases, landing pages, technical presentations, and any interface that benefits from a subtle stream of system-like activity.

## Features

- Native WordPress admin interface under the **MPRO** menu.
- Live preview beside the settings panel.
- One log entry per line with optional categories.
- Organic, breathing, burst, and random timing rhythms.
- Multiple ease-in-out animation presets.
- Random easing on each new line.
- Configurable interval, timing variation, movement duration, and maximum visible lines.
- Adjustable width, height, font size, line height, and text opacity.
- Text colors loaded directly from the active WordPress theme palette.
- `currentColor` inheritance for automatic light/dark compatibility.
- Optional timestamps, categories, fade mask, and continuous looping.
- Shortcode overrides for individual instances.
- Support for multiple console instances on the same page.
- Elementor-compatible through the Shortcode widget.
- Pauses when outside the viewport or when the browser tab is hidden.
- Respects `prefers-reduced-motion`.
- No JavaScript framework or external frontend dependency.

## Requirements

- WordPress 6.2 or newer
- PHP 7.4 or newer
- A modern browser with JavaScript enabled

## Installation

### Install from a ZIP file

1. Download the latest plugin ZIP.
2. In WordPress, open **Plugins → Add New Plugin**.
3. Select **Upload Plugin**.
4. Upload the ZIP file and select **Install Now**.
5. Activate **MPRO Console Log**.
6. Open **MPRO → Console Log** to configure the plugin.

### Install manually

1. Copy the `mpro-console-log` folder into:

   ```text
   wp-content/plugins/
   ```

2. Activate the plugin from the WordPress Plugins screen.
3. Open **MPRO → Console Log**.

## Quick Start

Add the basic shortcode wherever you want the console stream to appear:

```text
[mpro_console]
```

In Elementor, place it inside the **Shortcode** widget.

## Writing Log Entries

Enter one log entry per line in the plugin settings.

A plain line becomes a message:

```text
Loading selected projects…
```

Use the first pipe character (`|`) to add a category:

```text
INIT|Booting portfolio interface…
SYSTEM|Loading design tokens and components…
DATA|Connecting to selected product archive…
UX|Mapping journeys, states and edge cases…
SUCCESS|Interface ready.
```

The category is optional. When enabled, it appears before the message.

## Admin Settings

### Content

| Setting | Description |
| --- | --- |
| Log entries | One message per line, optionally using `CATEGORY|Message`. |
| Maximum visible lines | Limits the number of lines retained in the console. Supported range: `2–200`. |

### Timing and Motion

| Setting | Description |
| --- | --- |
| Base interval | Base delay between new lines. Supported range: `80–10000 ms`. |
| Timing variation | Controls how far delays may vary from the base interval. Supported range: `0–95%`. |
| Scroll duration | Duration of the upward movement. Supported range: `0–5000 ms`. |
| Rhythm | Defines the pattern used to calculate delays. |
| Easing | Defines the movement curve for added and shifted lines. |

Available rhythms:

- `organic` — balanced irregular variation
- `breathing` — smooth wave-like timing
- `bursts` — quick groups followed by pauses
- `random` — highly irregular timing

Available easing values:

- `ease-in-out`
- `ease-in-out-sine`
- `ease-in-out-quad`
- `ease-in-out-cubic`
- `ease-in-out-quart`
- `ease-in-out-quint`
- `ease-in-out-back`
- `random`

### Appearance

| Setting | Description |
| --- | --- |
| Width | Default console width. |
| Height | Default console height. |
| Font size | Monospace text size. |
| Line height | Supported range: `1–3`. |
| Theme text color | Uses a color from the active theme palette or inherits `currentColor`. |
| Text opacity | Supported range: `0.05–1`. |
| Show timestamp | Adds the current time to every generated line. |
| Show category | Displays categories parsed from `CATEGORY|Message`. |
| Fade older lines | Applies a top-to-bottom fade mask. |
| Loop continuously | Restarts the log sequence after the final entry. |

Supported CSS size units are:

```text
px, %, rem, em, vw, vh, vmin, vmax
```

The `width` setting also accepts `auto`. Unitless numeric values are treated as pixels.

## Shortcode Reference

```text
[mpro_console]
```

All attributes are optional. When omitted, the saved plugin settings are used.

| Attribute | Description | Example |
| --- | --- | --- |
| `width` | Console width | `520px`, `100%`, `40vw`, `auto` |
| `height` | Console height | `280px`, `35vh` |
| `font_size` | Text size | `12px`, `0.75rem` |
| `line_height` | Numeric line-height from `1–3` | `1.5` |
| `color` | Active theme palette slug or `inherit` | `contrast`, `primary`, `inherit` |
| `max_lines` | Maximum retained lines from `2–200` | `30` |
| `interval` | Base line interval in milliseconds | `420` |
| `variation` | Timing variation percentage from `0–95` | `70` |
| `scroll_duration` | Movement duration in milliseconds | `520` |
| `rhythm` | Timing rhythm | `organic`, `breathing`, `bursts`, `random` |
| `easing` | Movement easing | `ease-in-out-cubic`, `random` |
| `timestamp` | Show or hide timestamps | `true`, `false`, `1`, `0` |
| `category` | Show or hide categories | `true`, `false`, `1`, `0` |
| `mask` | Enable or disable the fade mask | `true`, `false`, `1`, `0` |
| `loop` | Enable or disable continuous looping | `true`, `false`, `1`, `0` |
| `class` | Adds one or more custom CSS classes | `hero-console` |

Boolean attributes treat `0`, `false`, `off`, and `no` as false.

## Shortcode Examples

### Change the size

```text
[mpro_console width="520px" height="280px" font_size="12px"]
```

### Create a faster, irregular stream

```text
[mpro_console interval="420" variation="70" scroll_duration="520" easing="random" rhythm="bursts"]
```

### Use a theme palette color

```text
[mpro_console color="contrast"]
```

The value passed to `color` must match a color slug from the active WordPress theme palette.

### Inherit the surrounding text color

```text
[mpro_console color="inherit"]
```

This uses `currentColor`, making the console follow the text color of its parent section. It is the recommended option for sites with automatic light and dark modes.

### Hide timestamps and categories

```text
[mpro_console timestamp="false" category="false"]
```

### Stop after the final entry

```text
[mpro_console loop="false"]
```

### Add a custom class

```text
[mpro_console class="hero-console compact-console"]
```

### Provide instance-specific content

The enclosing shortcode can contain a custom log sequence. These lines override the globally saved entries for that instance only.

```text
[mpro_console]
INIT|Starting custom stream…
DATA|Loading selected case studies…
SUCCESS|Portfolio is ready.
[/mpro_console]
```

## Theme Palette and Dark/Light Modes

The plugin reads the active theme palette through the WordPress global settings API.

When a theme color is selected, the frontend uses the corresponding WordPress preset variable:

```css
var(--wp--preset--color--contrast)
```

A fallback color from the active palette is also included.

For automatic dark/light behavior, select **Inherit from placement** or use:

```text
[mpro_console color="inherit"]
```

The console will then use `currentColor` and follow the color defined by its parent container, Elementor section, block, or theme style.

## Elementor Usage

1. Edit the page with Elementor.
2. Add the **Shortcode** widget.
3. Paste `[mpro_console]` or a customized shortcode.
4. Control the console dimensions through shortcode attributes or its parent container.
5. For automatic light/dark behavior, make sure the parent section defines the intended text color and use `color="inherit"`.

## Accessibility

- The console container uses `aria-live="polite"`.
- The plugin respects the browser's `prefers-reduced-motion` setting.
- When reduced motion is enabled, line transitions are disabled.
- Text is rendered as real DOM content rather than a canvas image.

Because the stream is decorative in many layouts, review its accessibility role in the context of your page and avoid placing essential information only inside the animated log.

## Performance

- The frontend is built with vanilla JavaScript.
- Log generation pauses when the console leaves the viewport.
- Animation pauses when the browser tab becomes hidden.
- Only the configured maximum number of lines remains in the DOM.
- Multiple shortcode instances can run independently on one page.

## Developer API

### Promotional URL filter

The MPRO Plugins link can be changed without editing the plugin:

```php
add_filter( 'mpro_console_log_promo_url', function ( $url ) {
    return 'https://example.com/plugins/';
} );
```

### Custom styling

Use the shortcode `class` attribute:

```text
[mpro_console class="my-console"]
```

Then target the instance in your theme or custom CSS:

```css
.my-console {
    max-width: 48rem;
}

.my-console .mpro-console-log__category {
    font-weight: 700;
}
```

The root component exposes these CSS custom properties:

```css
--mpro-console-width
--mpro-console-height
--mpro-console-font-size
--mpro-console-line-height
--mpro-console-color
--mpro-console-opacity
```

## File Structure

```text
mpro-console-log/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── mpro-console-log.php
├── readme.txt
├── README.md
└── uninstall.php
```

## Uninstallation

Deleting the plugin from the WordPress Plugins screen runs `uninstall.php` and removes the saved `mpro_console_log_settings` option.

## Frequently Asked Questions

### Does it work with Elementor?

Yes. Use Elementor's Shortcode widget.

### Can I place more than one console on a page?

Yes. Every shortcode instance receives a unique ID and runs independently.

### Can every instance use different content?

Yes. Put custom lines inside the enclosing shortcode.

### Can it automatically adapt to dark and light mode?

Yes. Use the inherited color option. The console then follows `currentColor` from its parent container.

### Why does a selected theme color disappear after changing themes?

Color selection is based on the active theme's palette slugs. When the new theme does not contain the same slug, the plugin safely falls back to `inherit`.

### Can I use a raw HEX color in the shortcode?

No. The `color` attribute accepts active theme palette slugs or `inherit`. This keeps the component aligned with the site's design tokens.

### Can I disable motion?

Set `scroll_duration="0"` for an individual instance. The plugin also automatically disables transitions for visitors who prefer reduced motion.

## Changelog

### 1.1.1

- Corrected the MPRO Plugins URL to `https://moghadam.pro/mpro-plugins/`.
- Removed a duplicated **Show category** control from the admin settings.
- Added a complete GitHub README.

### 1.1.0

- Updated the settings interface to use native WordPress admin styles.
- Replaced the custom color picker with colors from the active theme palette.
- Added inherited text color support for automatic light/dark adaptation.
- Added plugin version and MPRO Plugins links to the Plugins screen and settings page.
- Added the `color` shortcode attribute.

### 1.0.0

- Initial release.

## License

MPRO Console Log is licensed under the GNU General Public License v2.0 or later.

## Author

Created by [Sayid Moghadam](https://moghadam.pro/).

More MPRO plugins and project information are available at:

**https://moghadam.pro/mpro-plugins/**
