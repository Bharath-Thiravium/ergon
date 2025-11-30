# SVG Chart Parameters Guide

## Arc Path Parameters (Doughnut/Pie Charts)

### `createArc(start, end, r)`
- **start**: Starting angle in degrees (-90 = top of circle)
- **end**: Ending angle in degrees (270 = full circle)
- **r**: Radius of the arc (35 = outer ring, 32 = medium, 33 = inner)

### Arc Path Output: `M x1 y1 A r r 0 large 1 x2 y2`
- **M**: Move to starting point
- **A**: Arc command
- **r r**: Radius X and Y (same for circle)
- **0**: X-axis rotation (0 = no rotation)
- **large**: Large arc flag (0 = small arc, 1 = large arc)
- **1**: Sweep flag (1 = clockwise)
- **x2 y2**: End point coordinates

## SVG Element Attributes

### Path (Doughnut/Pie Segments)
- **d**: Path definition (arc coordinates)
- **stroke**: Color of the line (#F59E0B = orange)
- **stroke-width**: Thickness of line (8 = thick, 7 = medium)
- **fill**: Interior fill (none = transparent)
- **stroke-linecap**: Line ending style (round = smooth)
- **data-idx**: Index for tooltip mapping (0, 1, 2...)
- **data-opacity**: Default opacity (0.9 = 90% visible)
- **opacity**: Current opacity (changes on hover)

### Circle (Center dot)
- **cx**: Center X coordinate (100 = middle)
- **cy**: Center Y coordinate (60 = middle)
- **r**: Radius (18 = small circle)
- **fill**: Background color

### Text (Center value)
- **x, y**: Position coordinates
- **text-anchor**: Alignment (middle = centered)
- **font-size**: Text size (12 = small)
- **font-weight**: Bold level (bold = 700)
- **fill**: Text color

### Polyline (Line Chart)
- **points**: Comma-separated x,y coordinates
- **stroke**: Line color
- **stroke-width**: Line thickness
- **fill**: Interior fill (none = no fill)
- **stroke-linecap**: Line ending (round = smooth)
- **stroke-linejoin**: Corner style (round = smooth)

### Rect (Bar Chart)
- **x, y**: Top-left position
- **width**: Bar width (15 = narrow)
- **height**: Bar height (calculated from data)
- **fill**: Bar color (#4F46E5 = indigo)
- **opacity**: Transparency (0.85 = 85% visible)
- **rx**: Corner radius (1 = slightly rounded)

### Circle (Line Chart Points)
- **cx, cy**: Center coordinates
- **r**: Radius (3 = small, 6 = large on hover)
- **fill**: Dot color
- **opacity**: Transparency

## Coordinate System

### SVG Viewbox: `0 0 200 120`
- **Width**: 200 units
- **Height**: 120 units
- **Center**: (100, 60)
- **Top**: y = 0
- **Bottom**: y = 120

### Common Positions
- **Center circle**: cx="100" cy="60"
- **Chart baseline**: y="95"
- **Top of chart**: y="10"

## Data Calculations

### Doughnut Chart Arc Angles
```
percentage = (value / total) * 360
startAngle = -90 (top)
endAngle = startAngle + percentage
```

### Bar Chart Heights
```
height = (value / maxValue) * 55
y = 95 - height
```

### Line Chart Points
```
x = 15 + (index * 45)
y = 95 - (value / maxValue) * 60
```

## Tooltip Mapping

### data-idx Attribute
- Maps SVG element to tooltip array index
- Example: `data-idx="0"` → tooltips[0]
- Used by `attachTooltips()` function

### Tooltip Data Format
```javascript
tooltips = [
  "Pending: 15",
  "Placed: 42",
  "Rejected: 8"
]
```

## Color Palette

- **#F59E0B**: Amber/Orange (Pending, Watch)
- **#10B981**: Green (Placed, Current, Paid)
- **#EF4444**: Red (Rejected, Overdue, Critical)
- **#0EA5E9**: Blue (Concern)
- **#8B5CF6**: Purple (Others)
- **#4F46E5**: Indigo (Payments)

## Animation/Interaction

### Hover Effects
- **opacity**: Changes from 0.9 to 1.0 (brighter)
- **stroke-width**: Increases by 2 (thicker line)
- **r**: Changes from 4 to 6 (larger dot)

### Tooltip Display
- Shows on mouseenter
- Hides after 1500ms
- Positioned at cursor (pageX, pageY)
