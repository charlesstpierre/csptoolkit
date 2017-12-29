<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Is Developper?
 * 
 * Verify if current user is the developper based on his email address.
 * 
 * @since 1.0.0
 * @global object $current_user
 * @return boolean
 */
function is_dev() {
    global $current_user;

    if (CSP_IS_DEBUG || $current_user->user_email === DEVELOPPER_EMAIL) {
        return true;
    } else {
        return false;
    }
}

/**
 * Debug function
 * 
 * Output to screen the value of the $var variable. If $dumps, uses var_dump instead of print_r
 * 
 * @since 1.0.0
 * @param mixed $var Any variable or scalar
 * @param boolean $dump Use var_dump instead of print_r
 * @see print_r(), var_dump()
 *
 */
function debug($var, $dump = false) {
    if (is_dev()) {
        if (true === WP_DEBUG) {
            echo '<pre class="alert">';
            if ($dump) {
                var_dump($var);
            } else {
                print_r($var);
            }
            echo '</pre>';
        }
    }
}

/**
 * Debug to debug.log
 * 
 * Writes to wp-content/debug.log the value of the $var variable.
 * 
 * @param mixed $var Any variable or scalar
 */
function debug_log($var) {
    if (is_dev() && true === WP_DEBUG) {
        if (is_array($var) || is_object($var)) {
            error_log(var_export($var, true));
        } else {
            error_log($var);
        }
    }
}

/**
 * Output webmaster tools site verifications
 * 
 * Writes the meta tags for both Google Site Verification and Microsoft Ownership Verification
 *  * 
 * @uses filter wp_head
 * @since 1.0.0
 */
function csp_output_webmaster_tools_site_verification() {
    if (defined('GOOGLE_SITE_VERIFICATION_CODE')) {
        echo '<meta name="google-site-verification" content="' . GOOGLE_SITE_VERIFICATION_CODE . '" />';
    }
    if (defined('MICROSOFT_OWNERSHIP_VERIFICATION_CODE')) {
        echo '<meta name="msvalidate.01" content="' . MICROSOFT_OWNERSHIP_VERIFICATION_CODE . '" />';
    }
}

add_action('wp_head', 'csp_output_webmaster_tools_site_verification');


/**
 * Compress and delete log files
 */
function csp_compress_log_files() {
    $logs = array(
        'debug' => WP_CONTENT_DIR . '/debug.log',
        'security' => WP_CONTENT_DIR . '/security.log',
        '404' => WP_CONTENT_DIR . '/404.log'
    );
    foreach($logs as $log=>$file){
        if ( file_exists($file) && filesize($file) > 1e+7 ){
            $gzfile = WP_CONTENT_DIR . '/'.$log.'-'.date('Y-m-d-G\hi\ms').'.log.gz';
            $fp = gzopen($gzfile,'w9');
            gzwrite($fp, file_get_contents($file));
            gzclose($fp);
            unlink($file);
        }
    }
}
add_action('wp_dashboard_setup','csp_compress_log_files');




function csp_manual_maintenance() {
    global $pagenow;
    if (
            defined('IN_MAINTENANCE')
            && IN_MAINTENANCE
            && $pagenow !== 'wp-login.php'
            && !is_user_logged_in()
            && !current_user_can('manage_options')
        ){
        header( 'HTTP/1.1 Service Unavailable', true, 503);
        header( 'Content-Type: text/html; charset=utf-8' );
        
        if ( file_exists( WP_CONTENT_DIR . '/maintenance.php') ){
            require_once( WP_CONTENT_DIR . '/maintenance.php' );
            die();
        }
        //else
        
	wp_load_translations_early();

	$protocol = wp_get_server_protocol();
	header( "$protocol 503 Service Unavailable", true, 503 );
	header( 'Content-Type: text/html; charset=utf-8' );
?>
	<!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml"<?php if ( is_rtl() ) echo ' dir="rtl"'; ?>>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php _e( 'Maintenance' ); ?></title>

	</head>
	<body>
		<h1><?php _e( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' ); ?></h1>
	</body>
	</html>
<?php        
        die();
    }
    
}
add_action('plugins_loaded','csp_manual_maintenance');