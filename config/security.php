<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web-triggered maintenance commands
    |--------------------------------------------------------------------------
    |
    | When false (the default), the admin panel cannot run `migrate` or
    | `composer` commands over HTTP. Run those from SSH / the hosting panel
    | instead. Only enable temporarily, on a trusted environment.
    |
    */

    'web_maintenance_commands' => (bool) env('ALLOW_WEB_MAINTENANCE_COMMANDS', false),

    /*
    |--------------------------------------------------------------------------
    | Web-triggered .env editing
    |--------------------------------------------------------------------------
    |
    | When false (the default), admin settings forms save their values to the
    | `settings` table only and never rewrite the .env file. Update .env on
    | the server manually when a value there needs to change.
    |
    */

    'web_env_editing' => (bool) env('ALLOW_WEB_ENV_EDITING', false),

];
