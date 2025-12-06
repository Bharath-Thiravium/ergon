<?php
/**
 * Followup Routes Configuration
 * Unified routing to prevent conflicts between modal and page-based followups
 */

return [
    // Modal-based followup (from contacts)
    'contacts.followups.create' => [
        'path' => '/ergon/contacts/followups/create',
        'view' => 'contact_followups/create.php',
        'type' => 'modal',
        'description' => 'Create followup from contact modal'
    ],
    
    'contacts.followups.store' => [
        'path' => '/ergon/contacts/followups/store',
        'controller' => 'ContactFollowupController@store',
        'method' => 'POST',
        'type' => 'api'
    ],
    
    'contacts.followups.view' => [
        'path' => '/ergon/contacts/followups/view/:id',
        'controller' => 'ContactFollowupController@view',
        'type' => 'page'
    ],
    
    // Page-based followup (standalone)
    'followups.create' => [
        'path' => '/ergon/followups/create',
        'view' => 'followups/create.php',
        'type' => 'page',
        'description' => 'Create standalone followup'
    ],
    
    'followups.store' => [
        'path' => '/ergon/followups/store',
        'controller' => 'FollowupController@store',
        'method' => 'POST',
        'type' => 'api'
    ],
    
    'followups.list' => [
        'path' => '/ergon/followups',
        'controller' => 'FollowupController@index',
        'type' => 'page'
    ]
];
?>
