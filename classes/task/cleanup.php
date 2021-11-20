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
 * Gateway cleanup, check if remaining orders are paid, and if not, delete them to clean up.
 *
 * @package    paygw_tdb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_tdb\task;

defined('MOODLE_INTERNAL') || die();

use paygw_tdb\tdb_helper;
use paygw_tdb\controller;
use core_payment\helper;

class cleanup extends \core\task\scheduled_task
{
    /**
     * Returns the name of this task.
     */
    public function get_name()
    {
        // Shown in admin screens.
        return get_string('cleanup', 'paygw_tdb');
    }

    /**
     * Executes task.
     */
    public function execute()
    {
        global $DB;

        // Get old expired orders.
        $orders = $DB->get_recordset_select(
            'paygw_tdb',
            'status = ? AND timemodified < ?',
            [tdb_helper::ORDER_STATUS_PENDING, (time() - (HOURSECS))]
        );
        foreach ($orders as $order) {
            $DB->delete_records('paygw_tdb', ['id' => $order->id]);
        }
        $orders->close();
    }
}
