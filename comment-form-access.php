<?php

/*
Plugin Name: Comment Form Access
License: GPLv2
Author: Michel Boucey
Description: This plugin gives two methods to limit access to comment form that one can use alone or combined. The first one limits access to URLs that have a predefined key access in their query string, the second one to hosts that have IP(v4/v6) addresses belongs to the white list. By default, once this plugin is activated, the comment form doesn't show up anymore except for URLs that use the given random key access. Comment Form Access's form lives in "Settings".
Version: 0.2.2
Author URI: http://mb.ioflow.co
Plugin URI: http://mb.ioflow.co/comment-form-access-wordpress-plugin/
*/

/*  Copyright 2011  Michel Boucey  (email : michel.boucey@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_option ( 'comment_form_access_list', '127.0.0.1' );

add_option ( 'comment_form_access_key', cfa_randkey () );

add_option ( 'comment_form_access_method', 'key' );

add_action ( 'comment_form_before', 'do_comment_form_access',5,0 );

add_filter ( 'query_vars', 'add_cfa_query_var' );

if ( is_admin() ) add_action ('admin_menu', 'comment_form_access_menu');

function comment_form_access_menu(){

	add_options_page ('Comment Form Access', 'Comment Form Access', 'manage_options', 'comment_form_access', 'comment_form_access_menu_page');

} 

function cfa_randkey($l = 16){

	$s = ''; $c = "12345ABCDEFGHJKLMN_-PQRSTUVWX123456789YZabcdefghjkmnp_qrs-tuvwxwz6789";

 	$slc = strlen ($c)-1; for(;$l>0;$l--) $s .= $c{rand(0,$slc)}; return str_shuffle ($s);

}

function cfa_filter_list ($list) {

	$ips = array (); $noips = array (); $pips = explode (',', $list); 

	foreach ($pips as $pip) filter_var ($pip, FILTER_VALIDATE_IP) ? $ips[] = $pip : $noips[] = $pip;

	$out[] = implode (',', $ips); $out[] = implode (', ', $noips); return $out;

}

function comment_form_access_menu_page(){

	if ( !current_user_can ('manage_options'))

		wp_die ( __('You do not have sufficient permissions to access this page.') );

	if ( isset ($_POST['cfa_submit'])) {

		list($list,$discarted_inputs) = cfa_filter_list ($_POST['cfa_list']);

		if ( isset ($_POST['cfa_method_list'])) $pcfaml = $_POST['cfa_method_list'];

		if ( isset ($_POST['cfa_method_key'])) $pcfamk = $_POST['cfa_method_key'];

		if ( isset ($_POST['cfa_random_key'])) update_option ('comment_form_access_key', cfa_randkey ());

		if ( (!isset ($pcfaml)) && isset ($pcfamk)) $set_cfa_method = 'key';

		if ( isset ($pcfaml) && (!isset ($pcfamk))) $set_cfa_method = 'list';

		if ( isset ($pcfaml) && isset ($pcfamk)) $set_cfa_method = 'all';

		if ( (!isset ($pcfaml)) && (!isset ($pcfamk))) $set_cfa_method = 'none';

		update_option ('comment_form_access_method', $set_cfa_method);

		update_option ('comment_form_access_list', $list);

        }

	$cfa_method = get_option ('comment_form_access_method');

	echo '<div class="wrap"><h2>Comment Form Access</h2><br><form method="post" action="'.$_SERVER['REQUEST_URI'].'">';

	echo '<input type="checkbox" name="cfa_method_key"';

	if ( $cfa_method == "key" || $cfa_method == "all" ) echo ' checked';

	echo '>&nbsp;Append the variable name and key value below to query string to access the comment form:<br><br>&nbsp;<b>comment_form_access_key='. get_option ('comment_form_access_key') ."</b>";

	echo '<br><br><input type="checkbox" name="cfa_random_key">Generate a new random key value';

        echo '<br><br><br><input type="checkbox" name="cfa_method_list"';

        if ( $cfa_method == "list" || $cfa_method == "all" ) echo ' checked'; echo '>';

        echo '&nbsp;List of IP(v4/v6) addresses (comma separated) of hosts that can access to the comment form:<br>';

        echo '<textarea style="width:670px;height:100px" name="cfa_list">'. get_option ('comment_form_access_list') 
.'</textarea>';

	echo '<br><br><input type="submit" name="cfa_submit" value="Submit"></form>';

	if ( !empty ($discarted_inputs)) echo "<br><b>Updated with discarded inputs from IP addresses list</b> : ". $discarted_inputs .".";

	echo "</div>";

}

function add_cfa_query_var ($vars) { $vars[] = "comment_form_access_key"; return $vars; }

function cfa_no_comment_form () { get_footer (get_template()); exit; }

function cfa_is_key_ok () { return (get_query_var ('comment_form_access_key') == get_option ('comment_form_access_key')); }

function cfa_is_ipaddr_ok () { return (in_array ($_SERVER['REMOTE_ADDR'], explode (',', trim (get_option ('comment_form_access_list'))))); }

function do_comment_form_access () {

	switch ( get_option ('comment_form_access_method')) {

		case "none": return; break;

		case "key": if (!cfa_is_key_ok ()) cfa_no_comment_form (); break;

		case "list": if (!cfa_is_ipaddr_ok ()) cfa_no_comment_form (); break;

		case "all": if (!(cfa_is_key_ok () && cfa_is_ipaddr_ok ())) cfa_no_comment_form (); break;

		default: cfa_no_comment_form (); break;

	}

}

?>
