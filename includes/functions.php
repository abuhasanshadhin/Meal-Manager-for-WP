<?php

require '../../../../wp-config.php';
require '../../../../wp-load.php';

$mm_db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

function mm_base_url($qs = '')
{
    return 'admin.php?page=meal-manager&mm-action=' . $qs;
}