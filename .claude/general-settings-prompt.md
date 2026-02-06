# Prompt for General Settings Tab

Copy and paste this in a new chat:

---

I have an admin settings page at `http://localhost/gmb_leads/admin/settings` with two tabs:
1. Email Settings (already complete)
2. General Settings (needs to be completed)

**Current Status:**
- File: `resources/views/admin/settings/index.blade.php`
- Controller: `app/Http/Controllers/Admin/SettingsController.php`
- Routes: Already configured in `routes/web.php` under `admin.settings.*`
- Database: `settings` table already exists with migration

**What I need in General Settings tab:**

## 1. **Application Settings**
- Site Name (text input)
- Site Description/Tagline (textarea)
- Site Logo Upload (file upload)
- Favicon Upload (file upload)
- Contact Email (email input)
- Contact Phone (text input)
- Support Email (email input)

## 2. **Business Settings**
- Default Country (dropdown)
- Default Currency (dropdown with options: USD, EUR, GBP, etc.)
- Currency Symbol Position (before/after amount)
- Timezone (dropdown with timezones)
- Date Format (dropdown: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD)
- Time Format (12-hour / 24-hour)

## 3. **API Settings**
- Google Maps API Key (password input with show/hide toggle)
- Google Places API Key (password input with show/hide toggle)
- API Rate Limit (number input - requests per minute)
- Enable API Logging (toggle switch)
- API Key verification buttons for both

## 4. **System Settings**
- Maintenance Mode (toggle switch - enable/disable site)
- Maintenance Message (textarea - shown when site is in maintenance)
- Allow User Registration (toggle switch)
- Email Verification Required (toggle switch)
- Default User Role (dropdown: user, admin)
- Session Timeout (number input in minutes)

## 5. **Search Settings**
- Max Search Results Per Page (number input)
- Default Search Radius (number input in km/miles)
- Enable Search History (toggle switch)
- Auto-save Search Results (toggle switch)
- Max Saved Leads Per User (number input)

## 6. **Notification Settings**
- Enable Email Notifications (toggle switch)
- Enable SMS Notifications (toggle switch)
- Notify Admin on New Registration (toggle switch)
- Notify Admin on New Subscription (toggle switch)
- Notify Admin on Payment Received (toggle switch)

## 7. **Cache & Performance**
- Enable Cache (toggle switch)
- Cache Duration (number input in minutes)
- Clear Cache Button
- Optimize Database Button

## Requirements:
- Use Tailwind CSS (already used in admin panel)
- Follow the same design pattern as Email Settings tab
- Save all settings to the `settings` table
- Use the existing `Setting` model's `get()` and `set()` methods
- Add validation for all inputs
- Show success/error messages
- Add a "Save General Settings" button at the bottom
- Settings should be grouped in separate cards like Email Settings
- Use the same color scheme (primary-500, primary-600, etc.)

## Technical Details:
- Controller method: `updateGeneralSettings()` in `SettingsController.php`
- Route name: `admin.settings.general.update`
- HTTP method: PUT
- All settings should be stored with group = 'general'
- Add proper validation rules
- Return back with success/error message

## Additional Features:
- Logo/Favicon upload should use Laravel storage
- Add preview for uploaded images
- Add "Reset to Default" button for each section
- Add tooltips/help text for complex settings
- Make API key fields have verify buttons like Email Settings

**Current file structure:**
```
resources/views/admin/settings/
  └── index.blade.php (contains both tabs)

app/Http/Controllers/Admin/
  └── SettingsController.php

app/Models/
  └── Setting.php (has get/set methods)
```

Please create the complete General Settings tab implementation with all the above features.
