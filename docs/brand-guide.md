# Bilskyen Brand Guide - Color System

This document outlines the complete color system used throughout the Bilskyen application. Use this guide when redesigning colors in the admin panel or any other part of the application.

## Primary Color

### Main Primary Color
- **Hex**: `#004aad`
- **Usage**: Main brand color used for navigation bars, footers, primary buttons, links, and key UI elements
- **CSS Variable**: `--primary: #004aad`
- **OKLCH**: `oklch(0.35 0.15 260)` (for reference)

### Primary Foreground
- **OKLCH**: `oklch(0.985 0 0)` (near white)
- **CSS Variable**: `--primary-foreground: oklch(0.985 0 0)`
- **Usage**: Text and icons on primary color backgrounds

### Primary Color Variations

#### Darker Primary (Button Variant)
- **Hex**: `#002d6b`
- **Usage**: Darker variant for buttons that need more prominence (e.g., Login button, user menu avatar)
- **Example**: Login button, user avatar background, hover states

#### Medium Dark Primary
- **Hex**: `#003a8d`
- **Usage**: Intermediate dark variant (if needed for gradients or hover states)

## CSS Color Variables

The application uses a comprehensive set of CSS variables defined in the layout files. All colors are defined in `:root` and can be accessed via CSS variables.

### Core Colors

```css
--primary: #004aad
--primary-foreground: oklch(0.985 0 0)
```

### Background Colors

```css
--background: oklch(1 0 0)              /* Pure white */
--card: oklch(1 0 0)                    /* Card backgrounds */
--popover: oklch(1 0 0)                 /* Popover/dropdown backgrounds */
--muted: oklch(0.97 0 0)                /* Muted backgrounds (slightly off-white) */
--accent: oklch(0.97 0 0)               /* Accent backgrounds */
```

### Foreground (Text) Colors

```css
--foreground: oklch(0.145 0 0)          /* Main text color (near black) */
--card-foreground: oklch(0.145 0 0)     /* Text on cards */
--popover-foreground: oklch(0.145 0 0)  /* Text in popovers */
--muted-foreground: oklch(0.556 0 0)    /* Muted/secondary text */
--accent-foreground: oklch(0.145 0 0)    /* Text on accent backgrounds */
```

### Secondary Colors

```css
--secondary: oklch(0.97 0 0)
--secondary-foreground: oklch(0.145 0 0)
```

### Destructive (Error) Colors

```css
--destructive: oklch(0.577 0.245 27.325)  /* Red for errors/destructive actions */
--destructive-foreground: (typically white)
```

### Border and Input Colors

```css
--border: oklch(0.922 0 0)               /* Border color (light gray) */
--input: oklch(0.922 0 0)                /* Input border color */
--ring: oklch(0.708 0 0)                 /* Focus ring color */
```

### Sidebar Colors (Dealer Panel)

```css
--sidebar: oklch(0.985 0 0)              /* Sidebar background */
--sidebar-foreground: oklch(0.145 0 0)   /* Sidebar text */
--sidebar-primary: #004aad               /* Sidebar primary accent */
--sidebar-primary-foreground: oklch(0.985 0 0)
--sidebar-accent: oklch(0.97 0 0)
--sidebar-accent-foreground: oklch(0.145 0 0)
```

### Border Radius

```css
--radius: 0.5rem                         /* Base border radius */
```

## Color Usage by Component

### Navigation Bar
- **Background**: `bg-primary` (`#004aad`)
- **Text**: `text-white` or `text-primary-foreground`
- **Logo**: White logo on primary background

### Footer
- **Background**: `bg-primary` (`#004aad`)
- **Text**: `text-white`
- **Links**: `text-white` with `hover:text-white/80`
- **Borders**: `border-white/20`

### Buttons

#### Primary Button (Light Style)
```html
<button class="bg-background border border-border px-4 text-sm font-medium text-foreground shadow-sm">
```
- **Background**: White (`bg-background`)
- **Border**: Light gray (`border-border`)
- **Text**: Dark (`text-foreground`)
- **Usage**: "Sell Your Car" button (light, prominent)

#### Primary Button (Dark Style)
```html
<button class="bg-[#002d6b] px-4 text-sm font-semibold text-white shadow-md">
```
- **Background**: `#002d6b` (darker primary)
- **Text**: White
- **Usage**: "Login" button, user avatar

#### Submit Button
```html
<button class="bg-primary text-primary-foreground">
```
- **Background**: `#004aad`
- **Text**: White

### Form Elements

#### Input Fields
- **Background**: `bg-background` (white)
- **Border**: `border-input` (light gray)
- **Focus Border**: Primary color
- **Text**: `text-foreground` (dark)

#### Select Dropdowns
- Same styling as input fields

#### Textareas
- Same styling as input fields

### Cards and Sections

#### Standard Card
- **Background**: `bg-card` (white)
- **Border**: `border-border` (light gray)
- **Text**: `text-card-foreground` (dark)

#### Muted Sections
- **Background**: `bg-muted` (slightly off-white)
- **Text**: `text-foreground` or `text-muted-foreground`

#### Primary Sections (e.g., Pricing, Listing Information)
- **Background**: `bg-primary` (`#004aad`)
- **Text**: `text-primary-foreground` (white)

### Success Messages
- **Background**: `bg-primary/10` (10% opacity primary)
- **Border**: `border-primary/50` (50% opacity primary)
- **Text**: `text-primary`

### Error Messages
- **Background**: `bg-red-50` or `oklch(0.95 0.1 27)`
- **Border**: `border-red-500` or `oklch(0.8 0.2 27)`
- **Text**: `text-red-800` or `oklch(0.4 0.2 27)`

### Image Overlays
- **Gradient**: `linear-gradient(to bottom, transparent 0%, rgba(0, 74, 173, 0.6) 100%)`
- **Info Background**: `rgba(0, 74, 173, 0.7)`

### Loading Overlays
- **Background**: `rgba(0, 74, 173, 0.3)` (light mode)
- **Background**: `rgba(0, 74, 173, 0.5)` (dark mode - if needed)

## Tailwind CSS Classes

### Primary Color Classes
- `bg-primary` - Primary background (`#004aad`)
- `text-primary` - Primary text color
- `border-primary` - Primary border color
- `bg-primary/10` - 10% opacity primary background
- `bg-primary/50` - 50% opacity primary background
- `border-primary/50` - 50% opacity primary border
- `text-primary-foreground` - White text on primary

### Background Classes
- `bg-background` - Main background (white)
- `bg-card` - Card background (white)
- `bg-muted` - Muted background (off-white)
- `bg-accent` - Accent background (off-white)

### Text Classes
- `text-foreground` - Main text (dark)
- `text-muted-foreground` - Muted text (gray)
- `text-white` - White text
- `text-primary-foreground` - White text (for primary backgrounds)

### Border Classes
- `border-border` - Standard border (light gray)
- `border-input` - Input border (light gray)

## Color Conversion Reference

### Hex to RGB
- `#004aad` = `rgb(0, 74, 173)`
- `#002d6b` = `rgb(0, 45, 107)`
- `#003a8d` = `rgb(0, 58, 141)`

### Hex to RGBA (for opacity)
- `rgba(0, 74, 173, 0.1)` - 10% opacity
- `rgba(0, 74, 173, 0.3)` - 30% opacity
- `rgba(0, 74, 173, 0.5)` - 50% opacity
- `rgba(0, 74, 173, 0.6)` - 60% opacity
- `rgba(0, 74, 173, 0.7)` - 70% opacity

## Implementation Guidelines

### When to Use Primary Color
1. **Navigation bars and headers** - Always use `#004aad`
2. **Footers** - Always use `#004aad`
3. **Primary action buttons** - Use `#004aad` or `#002d6b` for darker variant
4. **Links and interactive elements** - Use primary color
5. **Section headers** - Use primary background with white text
6. **Success indicators** - Use primary color with low opacity backgrounds

### When NOT to Use Primary Color
1. **Body text** - Use `--foreground` (dark)
2. **Secondary buttons** - Use white background with border
3. **Error states** - Use red/destructive colors
4. **Muted content** - Use muted colors

### Contrast Requirements
- **Primary background with text**: Always use white (`text-primary-foreground` or `text-white`)
- **White background with text**: Use dark (`text-foreground`)
- **Muted background with text**: Use `text-foreground` or `text-muted-foreground`

## File Locations

### Main Layout Files
- `resources/views/layouts/app.blade.php` - Main CSS variables
- `resources/views/layouts/dealer.blade.php` - Dealer panel CSS variables (includes sidebar colors)
- `resources/views/layouts/auth.blade.php` - Auth pages CSS variables

### Component Files
- `resources/views/components/navbar.blade.php` - Navigation bar
- `resources/views/components/footer.blade.php` - Footer
- `resources/views/components/user-auth-status.blade.php` - User menu and buttons

### Page-Specific Styles
- `resources/views/sell-your-car.blade.php` - Custom styles for sell your car page

## Quick Reference

### Most Common Color Combinations

1. **Primary Button**
   - Background: `#004aad`
   - Text: White

2. **Secondary Button**
   - Background: White
   - Border: Light gray
   - Text: Dark

3. **Dark Button**
   - Background: `#002d6b`
   - Text: White

4. **Primary Section**
   - Background: `#004aad`
   - Text: White

5. **Success Message**
   - Background: `rgba(0, 74, 173, 0.1)`
   - Border: `rgba(0, 74, 173, 0.5)`
   - Text: `#004aad`

## Notes for Admin Panel Redesign

When implementing these colors in the admin panel:

1. **Use CSS variables** - Reference `--primary`, `--primary-foreground`, etc. for consistency
2. **Maintain contrast** - Always ensure text is readable on backgrounds
3. **Use opacity variants** - For overlays and subtle backgrounds, use rgba with opacity
4. **Follow the hierarchy** - Primary color for main actions, muted colors for secondary elements
5. **Test accessibility** - Ensure all color combinations meet WCAG contrast requirements

## Color Palette Summary

| Color Name | Hex Code | Usage |
|------------|----------|-------|
| Primary | `#004aad` | Main brand color |
| Primary Dark | `#002d6b` | Darker button variant |
| Primary Medium | `#003a8d` | Intermediate variant |
| White | `#ffffff` | Backgrounds, text on primary |
| Dark Text | `oklch(0.145 0 0)` | Main text color |
| Muted Text | `oklch(0.556 0 0)` | Secondary text |
| Light Gray | `oklch(0.922 0 0)` | Borders, inputs |
| Muted BG | `oklch(0.97 0 0)` | Muted backgrounds |

---

**Last Updated**: Based on current implementation as of the latest changes
**Maintained By**: Development Team

