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
	$sshot_tablename = $db_prefix . TBL_SCREENSHOT;

	countSession(session_id(), $stats_sessioncount_tablename, $stats_tablename);
	countHit($_SERVER['PHP_SELF'], $stats_hitcount_tablename);

	// The creation checks the login.
	$user = new User($db, $user_tablename, $loginlog_tablename);

	// Login?
	checkLogin($user);
	// Logout?
	checkLogout();

	if ($_SESSION['logged'] == TRUE) {
		// Login template for statusbar.
		$page_statusbar = 'page_statusbar_logged_in.ihtml';
	} else {
		// Status view template for statusbar.
		$page_statusbar = 'page_statusbar.ihtml';
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
		'PAGE_CONTENT_T'	=> 'admin_no_access.ihtml',
		'PAGE_FOOTER_T'		=> 'page_footer.ihtml',
		'PAGE_END_T'		=> 'page_end.ihtml'
	));

	// Set up page header.
	$page->set_var(array(
		'PB_PAGETITLE'		=> 'The TORCS Racing Board Screenshot View',
		'PB_DESCRIPTION'	=> 'View a screenshot on the TORCS racing board',
		'PB_AUTHOR'			=> 'Bernhard Wymann',
		'PB_KEYWORDS'		=> 'TORCS, racing, berniw, Bernhard, Wymann, Championship, World, Board, View, Show, Screenshot',
		'ROOTPATH'			=> $path_to_root,
	));

	if ($_SESSION['logged'] == TRUE) {
		// Variables if logged in.
		$page->set_var(array(
			'PS_USERNAME'		=> $_SESSION['username'],
			'PS_ACCOUNT_TYPE'	=> $_SESSION['usergroup'],
			'PS_IPADSRESS'		=> $_SERVER['REMOTE_ADDR'],
			'PS_LOGOUTPAGE'		=> $path_to_root . 'index.php'
		));
	} else {
		// Variables if NOT logged in.
		$page->set_var(array(
			'PS_PASSWORD_SIZE'	=> MAX_USERNAME_LENGTH,
			'PS_USERNAME_SIZE'	=> MAX_USERNAME_LENGTH,
			'PS_LOGINPAGE'		=> $_SERVER['PHP_SELF'],
			'PS_HOSTNAME'		=> SERVER_NAME
		));
	}

	if (/*isset($_GET['viewshotid']) &&*/ isset($_GET['shotrowid'])) {
		//$shotid = intval(removeMagicQuotes($_GET['viewshotid']));
		//$shotid_for_db = quoteString($shotid);
		$rowid = intval(removeMagicQuotes($_GET['shotrowid']));
		$rowid_for_db = quoteString($rowid);
		$listfrom_for_db = quoteString($rowid - 1);

		$nextimg = false;
		$previmg = false;

		if ($rowid > 0) {
			$sql = "SELECT * FROM $sshot_tablename " .
				   "ORDER BY id DESC LIMIT $listfrom_for_db, 3";
			$result = mysql_query($sql);
			$numrows = mysql_num_rows($result);
			if ($numrows == 3) {
				// prev and next
				$myrow = mysql_fetch_array($result); // skip first.
				$myrow = mysql_fetch_array($result);
				$nextimg = true;
				$previmg = true;
			} else if ($numrows == 2) {
				// prev
				$myrow = mysql_fetch_array($result); // skip first.
				$myrow = mysql_fetch_array($result);
				$previmg = true;
			} else {
				// Just this image.
				$myrow = mysql_fetch_array($result);
			}
		} else if ($rowid == 0) {
			$sql = "SELECT * FROM $sshot_tablename " .
				   "ORDER BY id DESC LIMIT 0, 2";
			$result = mysql_query($sql);
			$numrows = mysql_num_rows($result);
			if ($numrows == 2) {
				// next
				$myrow = mysql_fetch_array($result);
				$nextimg = true;
			} else {
				// Just this image.
				$myrow = mysql_fetch_array($result);
			}
		}

		if (isset($myrow) && $myrow) {
			$page->set_file('PAGE_CONTENT_T', 'sshot_view.ihtml');
			$page->set_var(array(
				'PC_SSHOT_ID'			=> $myrow['id'],
				'PC_SSHOT_DESC'			=> htmlentities($myrow['description']),
				'PC_SSHOT_TIMESTAMP'	=> mysqlToTime($myrow['time']),
				'PC_SSHOT_IMG_SRC'		=> $path_to_root . 'images/screenshots/' . $myrow['id'] . '.jpg'
			));
		}

		$page->set_var(array(
			'PC_LIST_NEXT_LINK'		=> ($nextimg) ? $_SERVER['PHP_SELF'] . '?shotrowid=' . ($rowid+1) : '',
			'PC_LIST_NEXT'			=> ($nextimg) ? 'Next' : '',
			'PC_LIST_PREV_LINK'		=> ($previmg) ? $_SERVER['PHP_SELF'] . '?shotrowid=' . ($rowid-1) : '',
			'PC_LIST_PREV'			=> ($previmg) ? 'Previous' : ''
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