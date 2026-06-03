# Mark Holiday Button - Visual Layout Reference

## Current Layout Implementation

### 1. Desktop View (1200px+)

```
╔══════════════════════════════════════════════════════════════════════╗
║ Attendance Management                                                ║
║ Track employee attendance and working hours                          ║
╠══════════════════════════════════════════════════════════════════════╣
║                                                                      ║
║  [📅 2024-01-15] [Today ▼] [📅 Mark Holiday] [🕰️ Clock In/Out]    ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
```

**Layout Properties:**
- Display: flex
- Align-items: center
- Gap: 0.75rem - 1rem between elements
- Flex-wrap: wrap
- All buttons aligned to same baseline

**Button Heights:** 40px (all)
**Button Styling:**
- Mark Holiday: Orange gradient background
- Clock In/Out: Blue primary background
- Date Input: Bordered input field
- Filter: Standard dropdown

---

### 2. Tablet View (768px - 1024px)

```
╔═════════════════════════════════════════╗
║ Attendance Management                  ║
║ Track employee attendance...           ║
╠═════════════════════════════════════════╣
║                                        ║
║  [📅 2024-01-15] [Today ▼]             ║
║  [📅 Mark Holiday] [🕰️ Clock In/Out] ║
║                                        ║
╚═════════════════════════════════════════╝
```

**Layout Properties:**
- Flex-direction: row with wrap
- Button height: 36px
- Font size: slightly reduced
- Padding: reduced spacing
- Width: responsive to container

---

### 3. Mobile View (<768px)

```
╔═══════════════════════════════════════╗
║ Attendance Management                ║
║ Track employee attendance...          ║
╠═══════════════════════════════════════╣
║                                      ║
║ ┌─────────────────────────────────┐  ║
║ │ [📅 Date Picker]                │  ║
║ └─────────────────────────────────┘  ║
║                                      ║
║ ┌─────────────────────────────────┐  ║
║ │ [Today ▼]                       │  ║
║ └─────────────────────────────────┘  ║
║                                      ║
║ ┌─────────────────────────────────┐  ║
║ │ [📅 Mark Holiday]               │  ║
║ └─────────────────────────────────┘  ║
║                                      ║
║ ┌─────────────────────────────────┐  ║
║ │ [🕰️ Clock In/Out]               │  ║
║ └─────────────────────────────────┘  ║
║                                      ║
╚═══════════════════════════════════════╝
```

**Layout Properties:**
- Flex-direction: column
- Width: 100% for all children
- Button height: 36px
- Font size: 0.8rem - 0.875rem
- Gap: 0.5rem between elements
- Padding: minimal

---

## Mark Holiday Button - Detailed Design

### Button States

#### Normal State
```
┌─────────────────────────────┐
│ 📅  Mark Holiday            │
└─────────────────────────────┘
Background: Linear-gradient(135deg, #f59e0b 0%, #f97316 100%)
Border: 1px solid #ea580c
Color: white
Height: 40px
Padding: 0.625rem 1.25rem
Border-radius: 6px
Box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2)
```

#### Hover State
```
┌─────────────────────────────┐
│ 📅  Mark Holiday            │  ↑ slight lift
└─────────────────────────────┘
Background: Linear-gradient(135deg, #f97316 0%, #f59e0b 100%)
Box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3)
Transform: translateY(-1px)
```

#### Active/Click State
```
┌─────────────────────────────┐
│ 📅  Mark Holiday            │  pressed down
└─────────────────────────────┘
Transform: translateY(0)
Box-shadow: 0 1px 3px rgba(245, 158, 11, 0.2)
```

#### Focus State
```
┌─────────────────────────────┐
│ 📅  Mark Holiday            │
└─────────────────────────────┘
Outline: 2px solid #f59e0b
Outline-offset: 2px
```

---

## Holiday Modal - Layout

### Modal Overlay
```
┌────────────────────────────────────────────────────────────────┐
│                    Background: rgba(0,0,0,0.5)                │
│                    (50% opacity dark overlay)                  │
│                                                                │
│          ┌──────────────────────────────────────────┐          │
│          │ 📅  Mark Holiday              [Close ×]  │          │
│          ├──────────────────────────────────────────┤          │
│          │                                          │          │
│          │ Holiday Date                            │          │
│          │ [📅 Date Input Field]                  │          │
│          │                                          │          │
│          │ Holiday Name                            │          │
│          │ [Holiday Name Input]                   │          │
│          │                                          │          │
│          │ Holiday Type                            │          │
│          │ [Select Type ▼]                        │          │
│          │  • National Holiday                     │          │
│          │  • Festival                             │          │
│          │  • Company Holiday                      │          │
│          │  • Emergency Holiday                    │          │
│          │  • Other                                │          │
│          │                                          │          │
│          │ Description (Optional)                  │          │
│          │ [                              ]        │          │
│          │ [                              ]        │          │
│          │ [                              ]        │          │
│          │                                          │          │
│          │ ☑ Apply to All Employees              │          │
│          │                                          │          │
│          ├──────────────────────────────────────────┤          │
│          │              [Cancel] [Save Holiday]    │          │
│          └──────────────────────────────────────────┘          │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### Modal Dimensions

**Desktop:**
- Width: 500px
- Max-width: 90vw
- Max-height: 90vh
- Border-radius: 8px

**Tablet/Mobile:**
- Width: 95vw
- Auto height (content-driven)
- Full-screen on small devices

---

## Color Palette

### Button Colors
```
Primary Orange (Mark Holiday):
  Normal:     #f59e0b  (Amber-400)
  Hover:      #f97316  (Orange-500)
  Border:     #ea580c  (Orange-600)
  Shadow:     rgba(245, 158, 11, 0.2)

Secondary Blue (Clock In/Out):
  Normal:     #3b82f6  (Blue-500)
  Hover:      #2563eb  (Blue-600)
  Border:     #1d4ed8  (Blue-700)
```

### Modal Colors
```
Background:         #ffffff  (White)
Text Primary:       #1f2937  (Gray-800)
Text Secondary:     #374151  (Gray-700)
Border:             #e5e7eb  (Gray-200)
Header BG:          #f9fafb  (Gray-50)
Footer BG:          #f9fafb  (Gray-50)
Input Focus:        #f59e0b  (Orange-400)
Focus Shadow:       rgba(245, 158, 11, 0.1)
```

---

## Form Field Specifications

### Holiday Date
```
Type: HTML5 Date Input
Format: YYYY-MM-DD
Required: Yes
Placeholder: (none - uses browser picker)
Icon: 📅 (from browser)
Height: 36-40px
```

### Holiday Name
```
Type: Text Input
Placeholder: "e.g., New Year"
Required: Yes
Min-length: 1 character
Max-length: 255 characters (suggested)
Height: 36-40px
```

### Holiday Type
```
Type: Select Dropdown
Required: Yes
Default: "Select Type"
Options:
  1. National Holiday
  2. Festival
  3. Company Holiday
  4. Emergency Holiday
  5. Other
Height: 36-40px
```

### Description
```
Type: Textarea
Rows: 3
Placeholder: "Add any additional details..."
Required: No
Max-length: 1000 characters (suggested)
Resizable: vertical only
```

### Apply to All Employees
```
Type: Checkbox
Default: Checked (yes)
Label: "Apply to All Employees"
Position: Left of label (standard)
```

---

## Animation Timeline

### Modal Open
```
Duration: 0.3s
Easing: ease

1. Overlay Fade-in: 0.2s ease
   Opacity: 0 → 1

2. Modal Slide-up: 0.3s ease
   Transform: translateY(20px) → translateY(0)
   Opacity: 0 → 1
   (Runs simultaneously)
```

### Modal Close
```
Duration: 0.2s
Easing: ease

Overlay Fade-out: 0.2s ease
Opacity: 1 → 0
Remove from DOM after animation
```

### Button Interactions
```
Hover Effect: 0.2s ease
- Scale: 1.0 → 1.0 (no scale)
- TranslateY: 0 → -1px
- Box-shadow: subtle → enhanced
- Background gradient: reversed

Active/Press: immediate
- TranslateY: 0px
- Box-shadow: minimal
```

---

## Responsive Breakpoints

### Breakpoint 1: Laptop/Desktop
```
Min-width: 1025px

.attendance-toolbar {
  flex-direction: row;
  gap: 1rem;
  width: 100%;
}

.attendance-toolbar__left {
  display: flex;
  gap: 0.75rem;
}

Button height: 40px
Font size: 0.95rem
Padding: 0.625rem 1.25rem
```

### Breakpoint 2: Tablet
```
Max-width: 1024px
Min-width: 769px

.attendance-toolbar {
  flex-direction: row;
  gap: 0.75rem;
  width: 100%;
}

.attendance-toolbar__left {
  flex: 1;
  min-width: 150px;
}

.attendance-toolbar__right {
  flex: 1;
  min-width: 150px;
}

Button height: 36px
Font size: 0.875rem
Padding: 0.5rem 0.75rem
```

### Breakpoint 3: Mobile
```
Max-width: 768px
Min-width: 0px

.attendance-toolbar {
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}

.attendance-toolbar__left,
.attendance-toolbar__right {
  width: 100%;
}

.form-input,
.btn {
  width: 100%;
}

Button height: 36px
Font size: 0.8rem - 0.875rem
Padding: 0.5rem
```

### Breakpoint 4: Small Mobile
```
Max-width: 480px

All elements: 100% width
Buttons: stacked vertically
Gap: 0.5rem
Button height: 36px
Font size: 0.8rem
Padding: 0.5rem
```

---

## Accessibility Features

### Keyboard Navigation
```
Tab: Move through form fields
Shift+Tab: Move backwards through fields
Enter: Submit form (from buttons)
Escape: Close modal (native browser behavior)
```

### ARIA Labels
```html
<button aria-label="Close">×</button>
<input id="holiday-date" />
<label for="holiday-date">Holiday Date:</label>
```

### Focus Indicators
```
All interactive elements have visible focus indicators
Focus color: #f59e0b (orange)
Outline width: 2px
Outline offset: 2px
```

### Screen Reader Support
```
Semantic HTML used:
- <button> for buttons
- <input> for form fields
- <label> for field labels
- Headings for modal title
- Form grouping with <fieldset> (where applicable)
```

---

## Cross-Browser Compatibility

### Supported Browsers
```
✅ Chrome 90+
✅ Firefox 88+
✅ Safari 14+
✅ Edge 90+
✅ Mobile Chrome
✅ Mobile Safari
```

### CSS Features Used
```
✅ Flexbox (IE 11+)
✅ CSS Grid (not used)
✅ CSS Gradients (IE 10+)
✅ CSS Transforms (IE 10+)
✅ CSS Animations (IE 10+)
✅ CSS Custom Properties (not used)
```

### JavaScript Features
```
✅ Fetch API (IE not supported, use fallback)
✅ FormData (IE 10+)
✅ Template Literals (ES6)
✅ Arrow Functions (not used)
✅ Const/Let (ES6)
```

---

## Print Styling

The modal should not print:
```css
@media print {
  .holiday-modal-overlay {
    display: none !important;
  }
}
```

The button should not print:
```css
@media print {
  .attendance-toolbar {
    display: none;
  }
}
```

---

## Summary

This visual layout reference documents:
- ✅ Desktop, tablet, and mobile layouts
- ✅ Button design and states
- ✅ Modal structure and styling
- ✅ Color palette and specifications
- ✅ Form field details
- ✅ Animation timelines
- ✅ Responsive breakpoints
- ✅ Accessibility features
- ✅ Browser compatibility
- ✅ Print considerations

All specifications ensure a professional, responsive, and accessible user interface.
