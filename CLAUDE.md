# The Sunroom Experts - WordPress Theme

## IMPORTANT: READ THIS FIRST

**Current Status:** The quote calculator form is BROKEN and needs to be fixed.

**Goal:** Simple 2-page lead generation site:
1. Homepage with hero section + CTA button
2. Quote calculator form at `/get-your-quote`
3. WordPress admin to manage leads

**Approach:** Use STATIC HTML for the frontend. Avoid WordPress block patterns/FSE complexity.

---

## Essential Files (DO NOT DELETE)

### Backend (Working)
- `functions.php` - Calculator AJAX handler, admin panel, email notifications, database
- `style.css` - Required by WordPress (keep minimal)
- `theme.json` - Theme configuration

### Calculator Assets
- `assets/js/sunroom-calculator.js` - Calculator frontend logic
- `assets/css/sunroom-calculator.css` - Calculator styling (well-organized with CSS variables)

### Database
- Table: `wp_sunroom_quotes` - Already created, stores all submissions

---

## Files to IGNORE (Legacy/Unused)

These were from failed attempts at WordPress FSE/block patterns:
- `patterns/` folder - Block patterns (caused issues)
- `templates/` folder - FSE templates
- `parts/` folder - Header/footer parts
- `assets/css/hero-section.css` - Failed CSS override attempt

---

## Priority Tasks for New Chat

### 1. FIX the calculator form
- Go to `/get-your-quote/` and test
- Check browser console for JavaScript errors
- Verify AJAX endpoint is working
- The form should: select room type → enter dimensions → show price → collect contact info → submit

### 2. CREATE simple static homepage
- Simple HTML with hero section
- "Get Your Quote" button linking to `/get-your-quote/`
- Before/after images (in WordPress media library)
- Trust badges

### 3. VERIFY admin panel works
- WordPress Admin → Sunroom Quotes
- Should show all submissions with status management

---

## Technical Details

### Pricing Structure
- Screen Only: $100-130 per linear foot
- 3-Season Eze Breeze: $200-300 per linear foot
- Glass: $600-800 per linear foot

### Calculator Flow
1. User selects room type
2. Enters wall dimensions (3 walls)
3. Sees price estimate
4. Fills contact form
5. Submits → saves to database + sends emails

### Email Recipient
`caison@thesunroomexperts.com` (set in functions.php line 647)

### AJAX Endpoints (in functions.php)
- `tse_submit_quote` - Handles form submission
- Nonce: `tse_calculator_nonce`

---

## DO NOT

- Use WordPress block patterns for the homepage
- Spend time on CSS hover effects or animations
- Try to fix the FSE/block editor issues
- Create complex template structures

## DO

- Keep it simple - static HTML where possible
- Focus on functionality over polish
- Test the form end-to-end before styling
- Use the existing CSS in `sunroom-calculator.css` (it's well-organized)

---

**Last Updated:** December 4, 2024
**Status:** Calculator broken, needs debugging
