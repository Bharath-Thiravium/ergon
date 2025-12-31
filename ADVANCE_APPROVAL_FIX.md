# Advance Approval JSON Error Fix

## Issue Description
When attempting to approve advances in the Owner & Admin panel, the system was showing the error:
```
Error: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

This indicated that the application was receiving an HTML response instead of a valid JSON response.

## Root Cause Analysis
The issue was caused by:
1. **Insufficient authentication/authorization checks** in the approval endpoint
2. **Inconsistent AJAX request detection** leading to HTML responses instead of JSON
3. **Missing proper error handling** that could cause redirects or error pages to be returned
4. **Frontend not sending proper headers** to indicate AJAX requests

## Fixes Implemented

### 1. Enhanced AdvanceController.php
- **Improved authentication checks**: Added early authentication validation with proper JSON responses for AJAX requests
- **Enhanced authorization**: Added role-based access control (only admin/owner can approve)
- **Better AJAX detection**: Created `isAjaxRequest()` method to properly detect AJAX requests
- **Consistent JSON responses**: Ensured all AJAX requests receive JSON responses, never HTML
- **Enhanced error logging**: Added detailed logging for debugging approval issues

### 2. Updated Base Controller.php
- **Added `isAjaxRequest()` method**: Detects AJAX requests using multiple indicators:
  - `X-Requested-With: XMLHttpRequest` header
  - `Content-Type: application/json` header
  - `Accept: application/json` header

### 3. Improved Frontend JavaScript
- **Enhanced AJAX requests**: Added proper headers to all fetch requests:
  ```javascript
  headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
      'Content-Type': 'application/json' // for GET requests
  }
  ```
- **Better error handling**: Added response validation to check for JSON content type
- **Improved user feedback**: Enhanced error messages and console logging

### 4. Fixed API Endpoints
- **advance.php**: Rewritten with proper JSON response handling and output buffering
- **projects.php**: Enhanced with consistent JSON responses and error handling

### 5. Added Debugging Tools
- **test_approval.php**: Test script to verify approval functionality
- **Enhanced logging**: Detailed error logging in `storage/advance_errors.log`

## Key Changes Made

### Authentication Flow
```
1. Check if user is logged in → Return 401 JSON if not
2. Check if user has admin/owner role → Return 403 JSON if not
3. Validate advance ID → Return 400 JSON if invalid
4. Process request → Return appropriate JSON response
```

### AJAX Request Detection
```php
protected function isAjaxRequest() {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}
```

### Frontend Headers
```javascript
fetch(url, {
    method: 'POST',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    },
    body: formData,
    credentials: 'same-origin'
})
```

## Testing
1. **Run test_approval.php** to verify the approval endpoint returns valid JSON
2. **Check advance_errors.log** for any logged errors
3. **Test the approval flow** in the advances module

## Files Modified
- `app/controllers/AdvanceController.php` - Enhanced approval method
- `app/core/Controller.php` - Added isAjaxRequest() method
- `views/advances/index.php` - Improved JavaScript AJAX handling
- `api/advance.php` - Rewritten for proper JSON responses
- `api/projects.php` - Enhanced JSON response handling

## Expected Result
After these fixes:
- ✅ Approval requests will always return valid JSON
- ✅ Authentication errors return proper 401 JSON responses
- ✅ Authorization errors return proper 403 JSON responses
- ✅ Server errors return proper 500 JSON responses with details
- ✅ Frontend handles all response types gracefully
- ✅ No more "Unexpected token '<'" errors

## Cleanup
After testing is complete:
- Delete `test_approval.php`
- Monitor `storage/advance_errors.log` for any remaining issues
