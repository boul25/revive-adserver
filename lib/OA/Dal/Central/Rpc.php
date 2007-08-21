<?php

/*
+---------------------------------------------------------------------------+
| Openads v2.5                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

require_once MAX_PATH.'/lib/OA.php';
require_once 'XML/RPC.php';


define('OA_DAL_CENTRAL_PROTOCOL_VERSION', 3);

define('OA_DAL_CENTRAL_AUTH_NONE',    0);
define('OA_DAL_CENTRAL_AUTH_SSO',     1);
define('OA_DAL_CENTRAL_AUTH_CAPTCHA', 2);


/**
 * OAP to OAC communication class
 *
 */
class OA_Dal_Central_Rpc
{
    /**
     * @var XML_RPC_Client
     */
    var $oXml;

    /**
     * Class constructor
     *
     * @return OA_Dal_Central_Rpc
     */
    function OA_Dal_Central_Rpc()
    {
        $this->oXml = new XML_RPC_Client(
            '/oap/',
            'https://oac.openads.org'
        );
    }

    /**
     * A method to perform a call to the OAC XML-RPC server
     *
     * @param string $methodName The RPC method name
     * @param int    $authType   Type of required authentication, see constants
     * @param array  $aParams    Array of XML_RPC_Values
     * @return mixed The returned value or PEAR_Error on error
     */
    function call($methodName, $authType, $aParams = null)
    {
        $aPref = $GLOBALS['_MAX']['PREF'];

        $oMsg = new XML_RPC_Message('oac.'.$methodName);

        $aHeader = array(
            'protocolVersion'   => OA_DAL_CENTRAL_PROTOCOL_VERSION,
            'platformHash'      => OA_Dal_ApplicationVariables::get('platform_hash')
        );

        if ($authType & OA_DAL_CENTRAL_AUTH_SSO) {
            $aHeader['ssoUsername'] = $aPref['sso_admin'];
            $aHeader['ssoPassword'] = $aPref['sso_password'];
        }
        if ($authType & OA_DAL_CENTRAL_AUTH_CAPTCHA) {
            $aHeader['ssoCaptcha']  = isset($_REQUEST['captcha']) ? $_REQUEST['captcha'] : '';
        }

        $oMsg->addParam(XML_RPC_encode($aHeader));

        if (is_array($aParams)) {
            foreach ($aParams as $oParam) {
                $oMsg->addParam($oParam);
            }
        }

        $oResponse = $this->oXml->send($oMsg);

        if ($oResponse->faultCode()) {
            return new PEAR_Error($oResponse->faultString(), $oResponse->faultCode());
        }

        return XML_RPC_decode($oResponse->value());
    }

    /**
     * Wrapper to the call method using no authentication
     *
     * @see call()
     *
     * @param string $methodName The RPC method name
     * @param array  $aParams    Array of XML_RPC_Values
     * @return mixed The returned value or PEAR_Error on error
     */
    function callNoAuth($methodName, $aParams = null)
    {
        return $this->call($methodName, OA_DAL_CENTRAL_AUTH_NONE, $aParams);
    }

    /**
     * Wrapper to the call method using SSO authentication
     *
     * @see call()
     *
     * @param string $methodName The RPC method name
     * @param array  $aParams    Array of XML_RPC_Values
     * @return mixed The returned value or PEAR_Error on error
     */
    function callSso($methodName, $aParams = null)
    {
        return $this->call($methodName, OA_DAL_CENTRAL_AUTH_SSO, $aParams);
    }

    /**
     * Wrapper to the call method using Capthca authentication
     *
     * @see call()
     *
     * @param string $methodName The RPC method name
     * @param array  $aParams    Array of XML_RPC_Values
     * @return mixed The returned value or PEAR_Error on error
     */
    function callCaptcha($methodName, $aParams = null)
    {
        return $this->call($methodName, OA_DAL_CENTRAL_AUTH_CAPTCHA, $aParams);
    }

    /**
     * Wrapper to the call method using SSO + Capthca authentication
     *
     * @see call()
     *
     * @param string $methodName The RPC method name
     * @param array  $aParams    Array of XML_RPC_Values
     * @return mixed The returned value or PEAR_Error on error
     */
    function callCaptchaSso($methodName, $aParams = null)
    {
        return $this->call($methodName, OA_DAL_CENTRAL_AUTH_SSO | OA_DAL_CENTRAL_AUTH_CAPTCHA, $aParams);
    }

    /**
     * A static method to convert UTC XML-RPC dateTime.iso8601 to local time
     *
     * @static
     *
     * @param string $dateTime DateTime.iso8601 formatted data
     * @return Date A PEAR::Date instance
     */
    function utcToDate($dateTime)
    {
        return new Date(strtotime($dateTime) + date('Z', $dateTime));
    }
}



?>