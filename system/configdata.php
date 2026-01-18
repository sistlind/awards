<?php
$config_default = array(
    'Optionen' => array(
        'role_enabled'    => 0,
        'leader_checked'  => 1,
        'default_category'=> 0,
        'debug_enabled'   => 0,
        'show_all_default'=> 0,
        'profile_tab_enabled' => 0
    ),
    'Plugininformationen' => array(
        'version' => $plugin_version ?? '5.0.0',
        'stand'   => $plugin_stand ?? ''
    )
);
