<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('paygw_tdb_settings', '', get_string('pluginname_desc', 'paygw_tdb')));

    $settings->add(new \admin_setting_configcheckbox(
        'paygw_tdb/sandbox',
        get_string('sandbox', 'paygw_tdb'),
        get_string('sandbox_help', 'paygw_tdb'),
        false
    ));

    $settings->add(new \admin_setting_configtextarea(
        'paygw_tdb/tdb_public_cert',
        get_string('tdb_public_cert', 'paygw_tdb'),
        get_string('tdb_public_cert_help', 'paygw_tdb'),
        false
    ));

    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_tdb');
}
