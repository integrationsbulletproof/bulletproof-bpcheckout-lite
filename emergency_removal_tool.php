<?php
/*
This script can be removed from your live environment safely. 
It is used for recovery in the event of a critical error in WordPress linked to the BulletProof Plugin. 
The execution of this script is restricted to the BulletProof support and requires enabling by their servers. 
The script is self-contained and not linked with WordPress. 
Please note that it can only run in the Bulletproof plugin directory. Using it in any other folder will break your WordPress.

Workflow:
1) Verify that the plugin is still present and has not been previously altered by the emergency removal tool.
2) The script will request an authorization code from the BulletProof server for emergency removal.
3) Using the POST method, the script will receive a token specifically authorized by the BulletProof server.
4) If the received token matches the authorization code, the main file of the plugin will be renamed to .bak.
TODO 5) Recursively remove all files of the plugin located under the plugin folder.

Once all the steps in the workflow have been completed, the BulletProof plugin was deactivated and not linked with any error showed by Wordpress.

Important note: After execute this removal tool , if you want to install again the plugin you will require to manually remove the folder wp-content/plugins/bulletproof-checkout-lite with any file manager (cPanel or Wordpress File Manager)

*/


set_time_limit(0);
error_reporting(E_ALL);
include_once  'includes/Input.php';
// Cons and Variables
define('BULLETPROOF_CHECKOUT_API_BASE_URL', 'https://bulletproofcheckout.net/API/endpoints/');
define('BULLETPROOF_MAIN_PLUGIN_FILE', 'bulletproof-checkout-lite.php');
define('BULLETPROOF_REMOVAL_TOOL_ENDPOINT', 'tools/removal_token');


$msg = "";  // Any success message will be stored here
$msg_error = ""; // Any failure message will be stored here

// Function for POST data and receive a response
function search_url_using_cURL_POST($url, $postdata, $header)
{
    if ($url != "") {

        ini_set("allow_url_fopen", true);

        // searching using curl
        $ch = curl_init();
        if ($ch === false) {
            //throw new Exception('failed to initialize cURL');
            return "";
        }
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        // new curl params
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);  // only max 60 seconds to timeout
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        //  curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //   curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // curl_setopt($ch, CURLOPT_POST,TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response_received = curl_exec($ch);

        curl_close($ch);

        if ($response_received === false) {
            return "";
        }
        return  $response_received;
    } else {
        return "";
    }
}

function search_url_using_cURL_GET($url, $disable_ssl_check = false)
{
    ini_set("allow_url_fopen", true);
    // searching using curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    // new curl params
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($disable_ssl_check) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response_received = curl_exec($ch);
    curl_close($ch);
    return  $response_received;
}

// Displays a Bootstrap Alert
function show_alert($msg, $alert_type)
{
    echo "<div class='alert " . $alert_type . "' role='alert'>" . $msg . "</div>";
}


function get_ip()
{
    if (php_sapi_name() != "cli") {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {

            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                return trim(reset($ips));
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER['REMOTE_ADDR'];
        } else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    return '';
}


if (php_sapi_name() != "cli") {
    if ((isset($_SERVER['HTTP_HOST'])) && ($_SERVER['HTTP_HOST'] != "localhost") && ($_SERVER['HTTP_HOST'] != "")) {
        if (file_exists(BULLETPROOF_MAIN_PLUGIN_FILE)) {
            // check if a backup file already exists
            if (strpos(BULLETPROOF_MAIN_PLUGIN_FILE, ".") > 0) {
                $file_parts = explode(".", BULLETPROOF_MAIN_PLUGIN_FILE);
                $backup_filename = $file_parts[0] . ".bak";
                $backup_file_exist = false;
                if (file_exists($backup_filename)) {
                    $backup_file_exist = true;
                    // backup file are removed forced
                    try {
                        unlink($backup_filename);
                        $backup_file_exist = false;
                    } catch (Exception $e) {
                    }
                }
                if (!$backup_file_exist) {
                    $code_received = Input::fetch("token");

                    if ($code_received != "") {
                        // Request an authorization code from the BulletProof servers
                        $authorization_code_json = search_url_using_cURL_GET(BULLETPROOF_CHECKOUT_API_BASE_URL . BULLETPROOF_REMOVAL_TOOL_ENDPOINT."/?domain=".$_SERVER['HTTP_HOST'], false);
                        if ($authorization_code_json != "") {
                            $auth_decoded = json_decode($authorization_code_json, true);
                            if ($auth_decoded != "") {
                                if ((isset($auth_decoded['status'])) && ($auth_decoded['status'] != "")) {
                                    if (strtoupper($auth_decoded['status']) == "OK") {
                                        if ((isset($auth_decoded['token'])) && ($auth_decoded['token'] != "")) {
                                            $authorization_code = $auth_decoded['token'];
                                            if (md5($authorization_code) == $code_received) {
                                                // will proceed to rename the plugin main file
                                                if (copy(BULLETPROOF_MAIN_PLUGIN_FILE, $backup_filename)) {
                                                    // remove the original file 
                                                    try {
                                                        unlink(BULLETPROOF_MAIN_PLUGIN_FILE);
                                                        $msg = "Removal tool finished succesfully";
                                                    } catch (Exception $e) {
                                                        $msg_error = "Plugin main file cannot be removed, probably because you dont have permissions in the server folder.";
                                                    }
                                                } else {
                                                    $msg_error = "The tool can not create the backup file, the main file will not be removed";
                                                }
                                            } else {
                                                $msg_error = "Invalid token received";
                                            }
                                        } else {
                                            $msg_error = "Invalid token received, please contact the Gateway Support Team";
                                        }
                                    } else {
                                        if ((isset($auth_decoded['message'])) && ($auth_decoded['message'] != "")) {
                                            $msg_error = $auth_decoded['message'];
                                        }
                                    }
                                } else {
                                    $msg_error = "Invalid data received from the gateway server, please try in some minutes or contact the Gateway Support Team.";
                                }
                            } else {
                                var_dump($authorization_code_json);
                                $msg_error = "Invalid data received from the gateway server, please try in some minutes or contact the Gateway Support Team";
                            }
                        } else {
                            $msg_error = "No response received from the BulletProof server, please try in some minutes or contact the Gateway Support Team";
                        }
                    } else {
                        $msg_error = "This tool requires an authorization code for your IP Address (".get_ip().") provided by the Gateway Support Team. Please contact them to obtain the code.";
                    }
                } else {
                    $msg_error = "Plugin backup file cannot be removed. Perhaps there are no permissions for the folder.";
                }
            }
        } else {
            $msg_error = "Plugin file does not exist, maybe was already removed";
        }
    } else {
        if ((isset($_SERVER['HTTP_HOST'])) && ($_SERVER['HTTP_HOST'] == "localhost")) {
            $msg_error = "This tool can not run in a localhost";
        } else {
            $msg_error = "The site firewall is not showing the HTTP_HOST which is requried by this tool";
        }
    }
} else {
    echo "This script can not run via terminal";
    die();
    exit;
}



echo "<html>";
echo "<head>";
// Link Bootstrap Library for the UI
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css' integrity='sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==' crossorigin='anonymous' referrerpolicy='no-referrer' />";
echo "</head>";
echo "<body>";

if ($msg_error != "") {
    show_alert($msg_error, "alert-danger");
} else {
    if ($msg != "") {
        show_alert($msg, "alert-success");
    } else {
        show_alert("The removal tool finished unexpectly", "alert-warning");
    }
}

echo "</body>";
echo "</html>";
