<?php
/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

class SessionManager
{
   	function sessionStart($name, $limit = 0, $path = '/', $domain = null, $secure = null)
	{
		// Set the cookie name
		session_name($name);

		// Set SSL level
		$https = isset($secure) ? $secure : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

		// Set session cookie options
		if (version_compare(PHP_VERSION, '5.2', '<')) // avoid failure on older php versions
			session_set_cookie_params($limit, $path, $domain, $https);
		else
			session_set_cookie_params($limit, $path, $domain, $https, true);

		session_start();

		// Make sure the session hasn't expired, and destroy it if it has
		if ($this->validateSession())
		{
			// Check to see if the session is new or a hijacking attempt
			if(!$this->preventHijacking())
			{
				// Reset session data and regenerate id
				$_SESSION = array();
				$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
				$this->regenerateSession();

			// Give a 5% chance of the session id changing on any request
			}
			elseif (rand(1, 100) <= 5)
			{
				$this->regenerateSession();
			}
		}
		else
		{
			$_SESSION = array();
			session_destroy();
			session_start();
		}
	}

	function preventHijacking()
	{
		if (!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent']))
			return false;

		if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
			return false;

		if ( $_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
			return false;

		return true;
	}

	function regenerateSession()
	{
		// If this session is obsolete it means there already is a new id
		if (isset($_SESSION['OBSOLETE']) && ($_SESSION['OBSOLETE'] == true))
			return;

		// Set current session to expire in 10 seconds
		$_SESSION['OBSOLETE'] = true;
		$_SESSION['EXPIRES'] = time() + 10000;

		// Create new session without destroying the old one
		session_regenerate_id();
		// Grab current session ID and close both sessions to allow other scripts to use them
		$newSession = session_id();
		session_write_close();
		// Set session ID to the new one, and start it back up again

		session_id($newSession);
		session_start();
		
		// Now we unset the obsolete and expiration values for the session we want to keep
		unset($_SESSION['OBSOLETE']);
		unset($_SESSION['EXPIRES']);
	}

	function validateSession()
	{
		if (isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']) )
			return false;

		if (isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time())
			return false;

		return true;
	}
}

function output_html($text)
{
	global $before_box, $Ajax, $messages;
	// Fatal errors are not send to error_handler,
	// so we must check the output
	if ($text && preg_match('/\bFatal error(<.*?>)?:(.*)/i', $text, $m)) {
		$Ajax->aCommands = array();  // Don't update page via ajax on errors
		$text = preg_replace('/\bFatal error(<.*?>)?:(.*)/i','', $text);
		$messages[] = array(E_ERROR, $m[2], null, null);
	}
	$Ajax->run();
	return  in_ajax() ? fmt_errors() : ($before_box.fmt_errors().$text);
}
//----------------------------------------------------------------------------------------

function kill_login()
{
	session_unset();
	session_destroy();
}
//----------------------------------------------------------------------------------------

function login_fail()
{
	global $path_to_root;
	
	header("HTTP/1.1 401 Authorization Required");
	echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
	echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";

	echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
	echo "<br><a href='$path_to_root/index.php'>" . _("Try again") . "</a>";
	echo "</center>";

	kill_login();
	die();
}

function check_faillog()
{
	global $login_delay, $login_faillog, $login_max_attempts;

	$user = $_SESSION["wa_current_user"]->user;

	if (@$login_delay && (@$login_faillog[$user][$_SERVER['REMOTE_ADDR']] >= @$login_max_attempts) && (time() < $login_faillog[$user]['last'] + $login_delay))
		return true;

	return false;
}
/*
	Simple brute force attack detection is performed before connection to company database is open. Therefore access counters have to be stored in file.
	Login attempts counter is created for every new user IP, which partialy prevent DOS attacks.
*/
function write_login_filelog($login, $result)
{
	global $login_faillog, $login_max_attempts, $path_to_root;

	$user = $_SESSION["wa_current_user"]->user;

	$ip = $_SERVER['REMOTE_ADDR'];

	if (!isset($login_faillog[$user][$ip]) || $result) // init or reset on successfull login
		$login_faillog[$user] = array($ip => 0, 'last' => '');

 	if (!$result)
	{
		if ($login_faillog[$user][$ip] < @$login_max_attempts) {

 			$login_faillog[$user][$ip]++;
 		} else {
 			$login_faillog[$user][$ip] = 0; // comment out to restart counter only after successfull login.
	 		error_log(sprintf(_("Brute force attack on account '%s' detected. Access for non-logged users temporarily blocked."	), $login));
	 	}
 		$login_faillog[$user]['last'] = time();
	}

	$msg = "<?php\n";
	$msg .= "/*\n";
	$msg .= "Login attempts info.\n";
	$msg .= "*/\n";
	$msg .= "\$login_faillog = " .var_export($login_faillog, true). ";\n";

	$filename = $path_to_root."/tmp/faillog.php";

	if ((!file_exists($filename) && is_writable($path_to_root)) || is_writable($filename))
	{
		file_put_contents($filename, $msg);
	}
}

//----------------------------------------------------------------------------------------

function check_page_security($page_security)
{
	global $SysPrefs;
	
	$msg = '';
	
	if (!$_SESSION["wa_current_user"]->check_user_access())
	{
		// notification after upgrade from pre-2.2 version
		$msg = $_SESSION["wa_current_user"]->old_db ?
			 _("Security settings have not been defined for your user account.")
				. "<br>" . _("Please contact your system administrator.")	
			: _("Please remove \$security_groups and \$security_headings arrays from config.php file!");
	} elseif (!$_SESSION['SysPrefs']->db_ok && !$_SESSION["wa_current_user"]->can_access('SA_SOFTWAREUPGRADE')) {
		$msg = _('Access to application has been blocked until database upgrade is completed by system administrator.');
	}
	
	if ($msg){
		display_error($msg);
		end_page(@$_REQUEST['popup']);
		kill_login();
		exit;
	}

	if (!$_SESSION["wa_current_user"]->can_access_page($page_security))
	{

		echo "<center><br><br><br><b>";
		echo _("The security settings on your account do not permit you to access this function");
		echo "</b>";
		echo "<br><br><br><br></center>";
		end_page(@$_REQUEST['popup']);
		exit;
	}
	if (!$_SESSION['SysPrefs']->db_ok 
		&& !in_array($page_security, array('SA_SOFTWAREUPGRADE', 'SA_OPEN', 'SA_BACKUP')))
	{
		display_error(_('System is blocked after source upgrade until database is updated on System/Software Upgrade page'));
		end_page();
		exit;
	}

}
/*
	Helper function for setting page security level depeding on 
	GET start variable and/or some value stored in session variable.
	Before the call $page_security should be set to default page_security value.
*/
function set_page_security($value=null, $trans = array(), $gtrans = array())
{
	global $page_security;

	// first check is this is not start page call
	foreach($gtrans as $key => $area)
		if (isset($_GET[$key])) {
			$page_security = $area;
			return;
		}

	// then check session value
	if (isset($trans[$value])) {
		$page_security = $trans[$value];
		return;
	}
}

//-----------------------------------------------------------------------------
//	Removing magic quotes from nested arrays/variables
//
function strip_quotes($data)
{
	if(get_magic_quotes_gpc()) {
		if(is_array($data)) {
			foreach($data as $k => $v) {
				$data[$k] = strip_quotes($data[$k]);
			}
		} else
			return stripslashes($data);
	}
	return $data;
}

function html_cleanup(&$parms)
{
	foreach($parms as $name => $value) {
//		$value = @html_entity_decode($value, ENT_QUOTES, $_SESSION['language']->encoding);
		if (is_array($value))
			html_cleanup($parms[$name]);
		else
			$parms[$name] = @htmlspecialchars($value, ENT_QUOTES, $_SESSION['language']->encoding);
	}
	reset($parms); // needed for direct key() usage later throughout the sources
}

//============================================================================
//
//
function login_timeout()
{
	// skip timeout on logout page
	if ($_SESSION["wa_current_user"]->logged) {
		$tout = $_SESSION["wa_current_user"]->timeout;
		if ($tout && (time() > $_SESSION["wa_current_user"]->last_act + $tout))
		{
			$_SESSION["wa_current_user"]->logged = false;
		}
		$_SESSION["wa_current_user"]->last_act = time();
	}
}
//============================================================================
if (!isset($path_to_root))
{
	$path_to_root = ".";
}

//----------------------------------------------------------------------------------------
// set to reasonable values if not set in config file (pre-2.3.12 installations)

if ((!isset($login_delay)) || ($login_delay < 0))
    $login_delay = 10;

if ((!isset($login_max_attempts)) || ($login_max_attempts < 0))
    $login_max_attempts = 3; 


// Prevent register_globals vulnerability
if (isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
	die("Restricted access");

include_once($path_to_root . "/includes/errors.inc");
// colect all error msgs
set_error_handler('error_handler' /*, errtypes */);

include_once($path_to_root . "/includes/current_user.inc");
include_once($path_to_root . "/frontaccounting.php");
include_once($path_to_root . "/admin/db/security_db.inc");
include_once($path_to_root . "/includes/lang/language.php");
include_once($path_to_root . "/config_db.php");
include_once($path_to_root . "/includes/ajax.inc");
include_once($path_to_root . "/includes/ui/ui_msgs.inc");
include_once($path_to_root . "/includes/prefs/sysprefs.inc");

include_once($path_to_root . "/includes/hooks.inc");
//
// include all extensions hook files.
//
foreach ($installed_extensions as $ext)
{
	if (file_exists($path_to_root.'/'.$ext['path'].'/hooks.php'))
		include_once($path_to_root.'/'.$ext['path'].'/hooks.php');
}

/*
	Uncomment the setting below when using FA on shared hosting
	to avoid unexpeced session timeouts.
	Make sure this directory exists and is writable!
*/
// ini_set('session.save_path', dirname(__FILE__).'/../tmp/');

ini_set('session.gc_maxlifetime', 36000); // 10hrs

$Session_manager = new SessionManager();
$Session_manager->sessionStart('FA'.md5(dirname(__FILE__)));

// this is to fix the "back-do-you-want-to-refresh" issue - thanx PHPFreaks
header("Cache-control: private");

include_once($path_to_root . "/config.php");
get_text_init();

if ($login_delay > 0)
	@include_once($path_to_root . "/tmp/faillog.php");

// Page Initialisation
if (!isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language')) 
{
	$l = array_search_value($dflt_lang, $installed_languages,  'code');
	$_SESSION['language'] = new language($l['name'], $l['code'], $l['encoding'],
	 (isset($l['rtl']) && $l['rtl'] === true) ? 'rtl' : 'ltr');
}

$_SESSION['language']->set_language($_SESSION['language']->code);


include_once($path_to_root . "/includes/access_levels.inc");
include_once($path_to_root . "/version.php");
include_once($path_to_root . "/includes/main.inc");

// Ajax communication object
$Ajax = new Ajax();

// js/php validation rules container
$Validate = array();
// bindings for editors
$Editors = array();
// page help. Currently help for function keys.
$Pagehelp = array();

$Refs = new references();

// intercept all output to destroy it in case of ajax call
register_shutdown_function('end_flush');
ob_start('output_html',0);

if (!isset($_SESSION["wa_current_user"]))
	$_SESSION["wa_current_user"] = new current_user();

html_cleanup($_GET);
html_cleanup($_POST);
html_cleanup($_REQUEST);
html_cleanup($_SERVER);

// logout.php is the only page we should have always 
// accessable regardless of access level and current login status.
if (strstr($_SERVER['PHP_SELF'], 'logout.php') == false){

	login_timeout();

	if (!$_SESSION["wa_current_user"]->old_db)
		include_once($path_to_root . '/company/'.user_company().'/installed_extensions.php');

	install_hooks();

	if (!$_SESSION["wa_current_user"]->logged_in())
	{
		// Show login screen
		if (!isset($_POST["user_name_entry_field"]) or $_POST["user_name_entry_field"] == "")
		{
			// strip ajax marker from uri, to force synchronous page reload
			$_SESSION['timeout'] = array( 'uri'=>preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s',
					'', @htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, $_SESSION['language']->encoding)), 
				'post' => $_POST);

			include($path_to_root . "/access/login.php");
			if (in_ajax())
				$Ajax->activate('_page_body');
			exit;
		} else {
			if (isset($_POST["company_login_nickname"]) && !isset($_POST["company_login_name"])) {
				for ($i = 0; $i < count($db_connections); $i++) {
					if ($db_connections[$i]["name"] == $_POST["company_login_nickname"]) {
						$_POST["company_login_name"] = $i;
						unset($_POST["company_login_nickname"]);
						break 1; // cannot pass variables to break from PHP v5.4 onwards
					}
				}
			}
			$succeed = isset($db_connections[$_POST["company_login_name"]]) &&
				$_SESSION["wa_current_user"]->login($_POST["company_login_name"],
				$_POST["user_name_entry_field"], $_POST["password"]);
			// select full vs fallback ui mode on login
			$_SESSION["wa_current_user"]->ui_mode = $_POST['ui_mode'];
			if (!$succeed)
			{
			// Incorrect password
				login_fail();
			}
			elseif(isset($_SESSION['timeout']) && !$_SESSION['timeout']['post'])
			{
				// in case of GET request redirect to avoid confirmation dialog 
				// after return from menu option
				header("HTTP/1.1 303 See Other");
				header("Location: ".$_SESSION['timeout']['uri']);
				exit();
			}
			$lang = &$_SESSION['language'];
			$lang->set_language($_SESSION['language']->code);
		}
	} else
		set_global_connection();

	if (!isset($_SESSION["App"])) {
		$_SESSION["App"] = new front_accounting();
		$_SESSION["App"]->init();
	}
}

$SysPrefs = &$_SESSION['SysPrefs'];

// POST vars cleanup needed for direct reuse.
// We quote all values later with db_escape() before db update.
$_POST = strip_quotes($_POST);

?>