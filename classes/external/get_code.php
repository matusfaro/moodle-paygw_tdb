<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This class contains a list of webservice functions related to the tdb payment gateway.
 *
 * @package    paygw_tdb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_tdb\external;

use paygw_tdb\tdb_helper;
use core_payment\helper;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class get_code
 * @copyright 2021 Catalyst IT
 * @package paygw_tdb
 */
class get_code extends external_api
{

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
            'description' => new external_value(PARAM_TEXT, 'The description of the payment'),
        ]);
    }

    /**
     * Returns the tdb qr code.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param string $description
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid, string $description): array
    {
        global $CFG;

        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'description' => $description
        ]);

        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('tdb');

        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);
        $order = tdb_helper::get_unprocessed_order($component, $paymentarea, $itemid);

        $config_gateway = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, 'tdb');
        if ($order) {
            if (tdb_helper::check_payment($order)) {
                tdb_helper::process_payment($order);
                // This order has already been paid - prevent them from paying again.
                return [
                    'error' => \html_writer::div(get_string('payment_already_processed', 'paygw_tdb'))
                ];
            }
        }

        if (empty($order)) {
            $order = tdb_helper::create_order($component, $paymentarea, $itemid, $payable->get_account_id(), $cost, $description);
        }

        return [
            'showIFrame' => true,
            'merchantid' => $config_gateway->merchantid,
            'PurchaseAmount' => (int)($cost * 100),
            'ReturnURLApprove' => $CFG->wwwroot . '/payment/gateway/tdb/tdb-redirect.php',
            'ReturnURLDecline' => $CFG->wwwroot . '/payment/gateway/tdb/tdb-redirect.php',
            'Currency' => 496, // MNT as per ISO 4217
            'OrderID' => $order->id,
            'VisualAmount' => $cost,
            'initsrc' => $CFG->wwwroot . '/payment/gateway/tdb/tdb-redirect.php',
            'postsrc' => get_config('paygw_tdb', 'sandbox')
                ? 'https://202.131.226.94:6330/shopindex.jsp'
                : 'https://www.bankcard.mn:443/shopindex.jsp',
            'uniqid' => uniqid(),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure
    {
        return new external_single_structure([
            'error' => new external_value(PARAM_RAW, 'User facing error message', VALUE_OPTIONAL),
            'showIFrame' => new external_value(PARAM_BOOL, 'Whether to show IFrame', VALUE_REQUIRED),
            'merchantid' => new external_value(PARAM_RAW, 'User facing error message', VALUE_OPTIONAL),
            'PurchaseAmount' => new external_value(PARAM_INT, 'Purchase amount multiplied by 100', VALUE_OPTIONAL),
            'ReturnURLApprove' => new external_value(PARAM_RAW, 'Return URL for approved transaction', VALUE_OPTIONAL),
            'ReturnURLDecline' => new external_value(PARAM_RAW, 'Return URL for declined transaction', VALUE_OPTIONAL),
            'Currency' => new external_value(PARAM_RAW, 'Currency', VALUE_OPTIONAL),
            'OrderID' => new external_value(PARAM_RAW, 'Order ID', VALUE_OPTIONAL),
            'VisualAmount' => new external_value(PARAM_RAW, 'Visual amount', VALUE_OPTIONAL),
            'initsrc' => new external_value(PARAM_RAW, 'Initial IFrame src attribute', VALUE_OPTIONAL),
            'postsrc' => new external_value(PARAM_RAW, 'Redirect url for IFrame', VALUE_OPTIONAL),
            'uniqid' => new external_value(PARAM_RAW, 'Unique ID for this form', VALUE_OPTIONAL),
        ]);
    }
}
