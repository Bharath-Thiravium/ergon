<?php
/**
 * ERGON Holiday Management Routes
 * Add to main router or include in routes configuration
 */

return [
    // Holiday management routes
    '/holiday/create' => ['HolidayController', 'create', ['POST']],
    '/holiday/update' => ['HolidayController', 'update', ['POST']],
    '/holiday/delete' => ['HolidayController', 'delete', ['POST']],
    '/holiday/get' => ['HolidayController', 'get', ['GET']],
    '/holiday/today' => ['HolidayController', 'today', ['GET']],
    '/holiday/upcoming' => ['HolidayController', 'upcoming', ['GET']],
    '/holiday/calendar' => ['HolidayController', 'calendar', ['GET']],
    '/holiday/verify-attendance' => ['HolidayController', 'verifyAttendance', ['GET']],
    '/holidays' => ['HolidayController', 'index', ['GET']],
];
?>
