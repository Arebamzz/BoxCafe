<?php

require_once __DIR__ . '/../config/app.php';

/** Independent connections preserve existing close() calls. */
function box_database(): mysqli
{
    return new mysqli(
        box_config('db_host'),
        box_config('db_user'),
        box_config('db_password'),
        box_config('db_name'),
        (int) box_config('db_port')
    );
}