<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Primary Admin Email
    |--------------------------------------------------------------------------
    |
    | If set, only this email can access admin console routes.
    | Leave empty to fallback to the first is_admin user.
    |
    */
    'admin_email' => env('ADMIN_EMAIL', ''),
];
