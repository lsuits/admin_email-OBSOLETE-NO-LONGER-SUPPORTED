<?php

$capabilities = array(
    'block/admin_email:addinstance' => array(
        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);

?>
