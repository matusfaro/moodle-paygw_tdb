<?php

namespace paygw_tdb;

use core_payment\helper;
use moodle_url;
use html_writer;
use paygw_tdb\nativepay;

defined('MOODLE_INTERNAL') || die();

/**
 * Class tdb_helper
 * @package paygw_tdb
 * @copyright 2021 Catalyst IT
 */
class tdb_helper
{
    public const ORDER_STATUS_PENDING = 'NEW';
    public const ORDER_STATUS_PAID = 'PAID';
    public const ORDER_STATUS_DECLINED = 'DECLINED';

    public const TDB_ORDER_STATUS_APPROVED = 'APPROVED';
    public const TDB_ORDER_STATUS_DECLINED = 'DECLINED';

    /**
     * Get an unprocessed order record - if one already exists - return it.
     *
     * @param string $component
     * @param string $paymentarea
     * @param string $itemid
     * @return false|\stdClass
     */
    public static function get_unprocessed_order($component, $paymentarea, $itemid)
    {
        global $USER, $DB;

        $existingorder = $DB->get_record('paygw_tdb', [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'userid' => $USER->id,
            'status' => self::ORDER_STATUS_PENDING
        ]);
        if ($existingorder) {
            return $existingorder;
        }
        return false;
    }

    /**
     * Create a new order.
     *
     * @param string $component
     * @param string $paymentarea
     * @param integer $itemid
     * @param string $accountid
     * @return \stdClass
     */
    public static function create_order($component, $paymentarea, $itemid, $accountid, $cost, $description)
    {
        global $USER, $DB;

        $neworder = new \stdClass();
        $neworder->component = $component;
        $neworder->paymentarea = $paymentarea;
        $neworder->itemid = $itemid;
        $neworder->amount = $cost;
        $neworder->userid = $USER->id;
        $neworder->accountid = $accountid;
        $neworder->status = self::ORDER_STATUS_PENDING;
        $neworder->timecreated = time();
        $neworder->timemodified = time();
        $neworder->modified = $neworder->timecreated;
        $id = $DB->insert_record('paygw_tdb', $neworder);
        $neworder->id = $id;

        return $neworder;
    }

    /**
     * Get an unprocessed order record - if one already exists - return it.
     *
     * @param string $orderid
     * @param string $orderstatus
     * @param string $responsecode
     * @param string $response
     * @return false|\stdClass
     */
    public static function set_payment($orderid, $orderstatus, $responsecode, $response)
    {
        global $USER, $DB;

        $existingorder = $DB->get_record('paygw_tdb', [
            'id' => $orderid,
        ]);
        if (!$existingorder) {
            return false;
        }

        // If approved, let process_payment transition 'status' to PAID
        if ($orderstatus != self::TDB_ORDER_STATUS_APPROVED) {
            $existingorder->status = self::ORDER_STATUS_DECLINED;
        }

        $existingorder->tdborderstatus = $orderstatus;
        $existingorder->tdbresponsecode = $responsecode;
        $existingorder->tdbresponse = $response;
        $DB->update_record('paygw_tdb', $existingorder);

        return $existingorder;
    }

    /**
     * Check tdb to see if this order has been paid.
     *
     * @param string $responseCode
     * @return boolean
     */
    public static function check_payment($order)
    {
        error_log('check_payment: ' . print_r($order, TRUE));
        return $order->tdborderstatus == self::TDB_ORDER_STATUS_APPROVED;
    }

    /**
     * Process payment and deliver the order.
     * @param \stdClass $order
     * @return array
     * @throws \coding_exception
     */
    public static function process_payment($order)
    {
        global $DB;
        $payable = helper::get_payable($order->component, $order->paymentarea, $order->itemid);
        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), helper::get_gateway_surcharge('tdb'));
        $message = '';
        try {
            $paymentid = helper::save_payment(
                $payable->get_account_id(),
                $order->component,
                $order->paymentarea,
                $order->itemid,
                (int) $order->userid,
                $cost,
                $payable->get_currency(),
                'tdb'
            );

            // Store tdb extra information.
            $order->paymentid = $paymentid;
            $order->timemodified = time();
            $order->status = self::ORDER_STATUS_PAID;

            $DB->update_record('paygw_tdb', $order);

            helper::deliver_order($order->component, $order->paymentarea, $order->itemid, $paymentid, (int) $order->userid);
            $success = true;
        } catch (\Exception $e) {
            debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $message = get_string('internalerror', 'paygw_tdb');
            $success = false;
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * Generate a unique order id based on timecreated and order->id field.
     *
     * @param \stdClass $order - the order record from paygw_tdb table.
     * @return string
     */
    protected static function get_orderid($order)
    {
        return $order->timecreated . '_' . $order->id;
    }
}
