<?php

use core_payment\helper;
use paygw_tdb\tdb_helper;

require_once(__DIR__ . '/../../../config.php');

global $OUTPUT;

$xmlmsg = $_POST["xmlmsg"];
if (empty($xmlmsg)) {
    echo $OUTPUT->box(get_string('paymentloading', 'paygw_tdb'), 'generalbox', 'notice');
} else {
    try {
        #Get Public Certificate from File
        $cert = get_config('paygw_tdb', 'tdb_public_cert');
        $pubKey = openssl_pkey_get_public($cert);

        #Load Signed XML
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($xmlmsg);

        #Get DigestValue
        $sigDigest = $xmlDoc->documentElement->getElementsByTagName("DigestValue")->item(0)->nodeValue;
        $signedInfo = $xmlDoc->getElementsByTagName("SignedInfo")->item(0)->C14N(true, true);

        #Get SignatureValue
        $signature = base64_decode($xmlDoc->documentElement->getElementsByTagName("SignatureValue")->item(0)->nodeValue);

        #Check Certificate
        $ok = openssl_verify($signedInfo, $signature, $pubKey, OPENSSL_ALGO_SHA1);
        if ($ok != 1) {
            error_log('Invalid signature');
            echo $OUTPUT->box(get_string('paymentfailure', 'paygw_tdb'), 'generalbox', 'notice');
            exit(1);
        }

        #Remove Signature from XML
        $elm = $xmlDoc->documentElement->getElementsByTagName("Signature")->item(0);
        $xmlDoc->documentElement->removeChild($elm);

        #Generate Digest Value from XML Data
        $xmlDigest = base64_encode(sha1($xmlDoc->documentElement->C14N(), true));

        #Check Generate Digest
        if ($sigDigest != $xmlDigest) {
            error_log('Invalid XML Data');
            echo $OUTPUT->box(get_string('paymentfailure', 'paygw_tdb'), 'generalbox', 'notice');
            exit(1);
        }

        $orderid = $xmlDoc->getElementsByTagName("ShopOrderId")->item(0)->nodeValue;
        $orderstatus = $xmlDoc->getElementsByTagName("OrderStatus")->item(0)->nodeValue;
        $responsecode = $xmlDoc->getElementsByTagName("ResponseCode")->item(0)->nodeValue;
        if (empty($orderstatus) || empty($orderid) || empty($responsecode)) {
            error_log('Invalid response');
            echo $OUTPUT->box(get_string('paymentfailure', 'paygw_tdb'), 'generalbox', 'notice');
            exit(1);
        }
        error_log('TDB response: ' . print_r(array(
            'orderid' => $orderid,
            'orderstatus' => $orderstatus,
            'responsecode' => $responsecode,
        ), true));

        $order = tdb_helper::set_payment((int) $orderid, $orderstatus, $responsecode, $xmlmsg);
        if (empty($order)) {
            error_log('Unknown order by id ' . $orderid);
            echo $OUTPUT->box(get_string('paymentfailure', 'paygw_tdb'), 'generalbox', 'notice');
            exit(1);
        }

        if ($orderstatus == tdb_helper::TDB_ORDER_STATUS_APPROVED) {
            echo $OUTPUT->box(get_string('paymentsuccessful', 'paygw_tdb'), 'generalbox', 'notice');
        } else {
            echo $OUTPUT->box(get_string('paymentfailure', 'paygw_tdb'), 'generalbox', 'notice');
        }
    } catch (Exception $ex) {
        error_log(print_r($ex, TRUE));
        echo $OUTPUT->box(get_string('paymentfailure', 'paygw_tdb'), 'generalbox', 'notice');
    }
}
