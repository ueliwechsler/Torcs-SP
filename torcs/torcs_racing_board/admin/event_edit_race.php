<?php

/*
	copyright   : (C) 2004 Bernhard Wymann
	email       : berniw@bluewin.ch
	version     : $Id$

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
*/

	session_start();
	$path_to_root = '../';
	require_once($path_to_root . 'secrets/configuration.php');
	require_once($path_to_root . 'lib/functions.php');
	require_once($path_to_root . 'lib/classes.php');
	require_once($path_to_root . 'lib/template.inc');
	require_once($path_to_root . 'lib/functions_validate.php');
	require_once($path_to_root . 'lib/functions_event.php');


	if (!isset($_SESSION['uid']) ) {
		session_defaults();
	}

	$db = mysql_connect($db_host, $db_user, $db_passwd) or die;
	mysql_select_db($db_name, $db) or die;
	$user_tablename = $db_prefix . TBL_USERS;
	$stats_hitcount_tablename = $db_prefix . TBL_HITCOUNT;
	$stats_sessioncount_tablename = $db_prefix . TBL_SESSIONCOUNT;
	$stats_tablename = $db_prefix . TBL_STATS;
	$loginlog_tablename = $db_prefix . TBL_LOGIN_LOG;
	$event_tablename = $db_prefix . TBL_EVENT;
	$race_tablename = $db_prefix . TBL_RACE;
	$track_tablename = $db_prefix . TBL_TRACK;
	$version_tablename = $db_prefix . TBL_VERSION;

	countSession(session_id(), $stats_sessioncount_tablename, $stats_tablename);
	countHit($_SERVER['PHP_SELF'], $stats_hitcount_tablename);

	// The creation checks the login.
	$user = new User($db, $user_tablename, $loginlog_tablename);

	// Login?
	checkLogin($user);
	// Logout?
	checkLogout();

	$formerrors = intval(0);
	$eventid = isset($_GET['eventid']) ? intval(removeMagicQuotes($_GET['eventid'])) : -1;
	if ($eventid == -1) {
		$eventid = isset($_POST['event_id']) ? intval(removeMagicQuotes($_POST['event_id'])) : -1;
	}

	if ($_SESSION['logged'] == TRUE) {
		// Login template for statusbar.
		$page_statusbar = 'page_statusbar_logged_in.ihtml';
		if ($_SESSION['usergroup'] == 'admin' &&
			(isset($_POST['race_submit']) || isset($_GET['editraceid'])))
		{
			if (isset($_POST['race_submit'])) {
				if ($formerrors = checkRaceInput()) {
					// Errors.
					$page_content = 'admin_race_edit.ihtml';
				} else {
					updateRaceInput($race_tablename, $event_tablename, $track_tablename, $path_to_root);
					$page_content = 'admin_race_create_ok.ihtml';
				}
			} else {
				$page_content = 'admin_race_edit.ihtml';
			}
		} else {
			$page_content = 'admin_no_access.ihtml';
		}
	} else {
		// Status view template for statusbar.
		$page_statusbar = 'page_statusbar.ihtml';
		$page_content = 'admin_no_access.ihtml';
	}

	// Create template instance for page layout.
	$page = new Template($path_to_root . 'templates', 'keep');

	// Define template file(s).
	$page->set_file(array(
		'page'				=> 'page.ihtml',
		'PAGE_BEGIN_T'		=> 'page_begin.ihtml',
		'PAGE_TITLEBAR_T'	=> 'page_titlebar.ihtml',
		'PAGE_STATUSBAR_T'	=> $page_statusbar,
		'PAGE_NAVIGATION_T'	=> 'page_navigation.ihtml',
		'PAGE_CONTENT_T'	=> $page_content,
		'PAGE_FOOTER_T'		=> 'page_footer.ihtml',
		'PAGE_END_T'		=> 'page_end.ihtml'
	));

	// Set up page header.
	$page->set_var(array(
		'PB_PAGETITLE'		=> 'The TORCS Racing Board Race Edit Page',
		'PB_DESCRIPTION'	=> 'Edit a race for the TORCS racing board',
		'PB_AUTHOR'			=> 'Bernhard Wymann',
		'PB_KEYWORDS'		=> 'TORCS, racing, berniw, Bernhard, Wymann, Championship, World, event, race, edit',
		'ROOTPATH'			=> $path_to_root,
		'PB_EVENT_ID'		=> $eventid
	));

	if ($_SESSION['logged'] == TRUE) {
		// Variables if logged in.
		$page->set_var(array(
			'PS_USERNAME'			=> $_SESSION['username'],
			'PS_ACCOUNT_TYPE'		=> $_SESSION['usergroup'],
			'PS_IPADSRESS'			=> $_SERVER['REMOTE_ADDR'],
			'PS_LOGOUTPAGE'			=> $path_to_root . 'index.php',
			'PC_EDITRACEPAGE'		=> $_SERVER['PHP_SELF']
		));

		if ($_SESSION['usergroup'] == 'admin' && $page_content == 'admin_race_edit.ihtml') {

			$sql = "SELECT * FROM $track_tablename ORDER BY type, name ASC";
			$tresult = mysql_query($sql);
			$page->set_block("PAGE_CONTENT_T", "row", "rows");

			$sql = "SELECT * FROM $version_tablename ORDER BY name ASC";
			$vresult = mysql_query($sql);
			$page->set_block("PAGE_CONTENT_T", "version", "versions");

			if (isset($_POST['race_submit']) && $formerrors > 0) {

				$page->set_var(array(
					'PC_EVENT_ID'					=> $eventid,
					'PC_RACE_ID'					=> isset($_POST['race_id']) ? htmlentities(removeMagicQuotes($_POST['race_id'])) : "",
					'PC_RACE_ROBOT_SUBMIT_START'	=> isset($_POST['race_robot_sub_start']) ? htmlentities(removeMagicQuotes($_POST['race_robot_sub_start'])) : "",
					'PC_RACE_ROBOT_SUBMIT_END'		=> isset($_POST['race_robot_sub_end']) ? htmlentities(removeMagicQuotes($_POST['race_robot_sub_end'])) : "",
					'PC_RACE_RESULT_SUBMIT_START'	=> isset($_POST['race_result_sub_start']) ? htmlentities(removeMagicQuotes($_POST['race_result_sub_start'])) : "",
					'PC_RACE_RESULT_SUBMIT_END'		=> isset($_POST['race_result_sub_end']) ? htmlentities(removeMagicQuotes($_POST['race_result_sub_end'])) : "",
				));

				while ($tmyrow = mysql_fetch_array($tresult)) {
					$page->set_var(array(
						'PC_RACE_TRACK_SEL'		=> ($tmyrow['trackid'] == intval(removeMagicQuotes($_POST['race_track']))) ? 'selected' : '',
						'PC_RACE_TRACK_ID'		=> $tmyrow['trackid'],
						'PC_RACE_TRACK_NAME'	=> htmlentities($tmyrow['name'])
					));
					$page->parse("rows", "row", true);
				}

				while ($vmyrow = mysql_fetch_array($vresult)) {
					$page->set_var(array(
						'PC_VERSION_SEL'		=> ($vmyrow['id'] == intval(removeMagicQuotes($_POST['version']))) ? 'selected' : '',
						'PC_VERSION_ID'			=> $vmyrow['id'],
						'PC_VERSION_NAME'		=> htmlentities($vmyrow['name'])
					));
					$page->parse("versions", "version", true);
				}
			} elseif (isset($_GET['editraceid'])) {

				$id_for_db = quoteString(intval(removeMagicQuotes($_GET['editraceid'])));
				$sql = "SELECT * FROM $race_tablename WHERE raceid=" . $id_for_db;
				$rresult = mysql_query($sql);

				if ($rmyrow = mysql_fetch_array($rresult)) {
					$page->set_var(array(
						'PC_EVENT_ID'					=> $rmyrow['eventid'],
						'PC_RACE_ID'					=> $rmyrow['raceid'],
						'PC_RACE_ROBOT_SUBMIT_START'	=> htmlentities($rmyrow['robot_submission_start']),
						'PC_RACE_ROBOT_SUBMIT_END'		=> htmlentities($rmyrow['robot_submission_end']),
						'PC_RACE_RESULT_SUBMIT_START'	=> htmlentities($rmyrow['result_submission_start']),
						'PC_RACE_RESULT_SUBMIT_END'		=> htmlentities($rmyrow['result_submission_end']),
					));

					while ($tmyrow = mysql_fetch_array($tresult)) {
						$page->set_var(array(
							'PC_RACE_TRACK_SEL'		=> ($tmyrow['trackid'] == $rmyrow['trackid']) ? 'selected' : '',
							'PC_RACE_TRACK_ID'		=> $tmyrow['trackid'],
							'PC_RACE_TRACK_NAME'	=> htmlentities($tmyrow['name'])
						));
						$page->parse("rows", "row", true);
					}

					while ($vmyrow = mysql_fetch_array($vresult)) {
						$page->set_var(array(
							'PC_VERSION_SEL'		=> ($vmyrow['id'] == $rmyrow['versionid']) ? 'selected' : '',
							'PC_VERSION_ID'			=> $vmyrow['id'],
							'PC_VERSION_NAME'		=> htmlentities($vmyrow['name'])
						));
						$page->parse("versions", "version", true);
					}
				} else {
					$page->set_file('PAGE_CONTENT_T', 'admin_no_access.ihtml');
				}
			}
		}
	} else {
		// Variables if NOT logged in.
		$page->set_var(array(
			'PS_PASSWORD_SIZE'	=> MAX_USERNAME_LENGTH,
			'PS_USERNAME_SIZE'	=> MAX_USERNAME_LENGTH,
			'PS_LOGINPAGE'		=> $_SERVER['PHP_SELF'],
			'PS_HOSTNAME'		=> SERVER_NAME
		));
	}

	require_once($path_to_root . 'lib/functions_navigation.php');
	setupNavigationAndFooter($page, $stats_tablename, $stats_hitcount_tablename);

	$page->parse('PAGE_BEGIN', 'PAGE_BEGIN_T');
	$page->parse('PAGE_TITLEBAR', 'PAGE_TITLEBAR_T');
	$page->parse('PAGE_STATUSBAR', 'PAGE_STATUSBAR_T');
	$page->parse('PAGE_NAVIGATION', 'PAGE_NAVIGATION_T');
	$page->parse('PAGE_CONTENT', 'PAGE_CONTENT_T');
	$page->parse('PAGE_FOOTER', 'PAGE_FOOTER_T');
	$page->parse('PAGE_END', 'PAGE_END_T');

	$page->parse('OUTPUT', array(
		'PAGE_BEGIN',
		'PAGE_TITLEBAR',
		'PAGE_NAVIGATION',
		'PAGE_STATUSBAR',
		'PAGE_CONTENT',
		'PAGE_FOOTER',
		'PAGE_END',
		'page'
	));

	$page->p('OUTPUT');
?>
