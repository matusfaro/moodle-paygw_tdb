<?php

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');

$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_ALPHANUMEXT);

require_login(null, false);

$successurl = new moodle_url('/');
$courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
if (method_exists('\core_payment\helper', 'get_success_url')) {
    // This is a 3.11 or higher site, we can get the url from the api.
    $successurl = helper::get_success_url($component, $paymentarea, $itemid);
} else if ($component == 'enrol_fee' && $paymentarea == 'fee') {
    require_once($CFG->dirroot . '/course/lib.php');
    // Moodle 3.10 site - try to work out the correct course to redirect this person to on payment.
    if (!empty($courseid)) {
        $successurl = course_get_url($courseid);
    }
}
$message = '';
// This is a bit hacky - would be good to rewrite to use helper for 3.11 and higher.
if (
    !empty($courseid) && $component == 'enrol_fee' && $paymentarea == 'fee' &&
    is_enrolled(context_course::instance($courseid))
) {
    $message = get_string('paymentsuccessful', 'paygw_tdb');
}
redirect($successurl, $message);
