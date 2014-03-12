<?php

/**
 * Handling of errors
 *
 * PHP versions 4 and 5
 *
 * LICENSE: VIKO is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * VIKO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * VIKO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307  USA
 *
 * @package    VIKO
 * @author     Rene Saarsoo <nene@triin.net>
 * @copyright  2001-2007 VIKO team and contributors
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */


/**
 * Handles errors
 *
 */
class ErrorHandler
{

    /**
     * Handles any error
     */
    function General ( $error_nr, $message, $file, $line  )
    {
        switch ($errno) {
            case E_USER_ERROR:
                echo "Error: $message in $file at line $line.\n";
                exit(1);
            break;
            case E_WARNING:
            case E_USER_WARNING:
                echo "Warning: $message in $file at line $line.\n";
            break;
            case E_NOTICE:
            case E_USER_NOTICE:
                echo "Notice: $message in $file at line $line.\n";
            break;
            default:
                echo "Unknown error type: [$errno] $errstr in $file at line $line.\n";
            break;
        }
    }


}

set_error_handler("ErrorHandler::General");


?>
