<?php
/* 
Plugin Name: Adsense-Deluxe2
Version: 1.0
Plugin URI: http://blogetery.com/blog/adsense-deluxe2/
Description: AdSense-Deluxe2 Plugin for WPMU and Standalone WordPress with revenue share functionality. Originally this WordPress plugin was developed by acmetech.com.
Author: Blogetery.com
Author URI: http://blogetery.com/
*/ 

//error_reporting(E_ERROR | E_WARNING | E_PARSE);

//--
//-- Version of the plugin
//--
$__ADSENSE_DELUXE_VERSION__ = "1.0";


$__ACMETECH_REV_SHARE = 10;
$__ACMETECH_CLIENT_ID__ = "pub-6200684333203789";
$__ACMETECH_SLOT = "3318452179";
$__ACMETECH_AD_DEFAULT_CODE__ = '
<script type="text/javascript"><!--
google_ad_client = "pub-6200684333203789";
/* 234x60, Blogetery */
google_ad_slot = "9337911112";
google_ad_width = 234;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
';

$__ACMETECH_AD_PARTNER__ = "1881826992";

//--
//-- 		CONSTANTS
//--
define('ADSDEL_OPTIONS_ID', 'acmetech_adsensedeluxe');

//--
//-- OUTPUTS debugging info in html comments on blog pages.
//--
$__AdSDelx_Debug__ = false;

//--
//-- If set to false, live adsense ads displayed in Post editing preview
//--
$__AdSDelx_USE_PREV_PLACEHOLDER = true;


/* 
adsense-deluxe
This function replaces <!--adsense--> or <!--adsense[#name]-->tags with actual Google Adsense code
*/ 

if (function_exists('is_plugin_page') && is_plugin_page()) :

	AdsenseDeluxeOptionsPanel(); // check here to see if the broken 1.5 options page feature is fixed

else :

	function adsense_deluxe_insert_ads($data) {
		global	$__AdSDelx_USE_PREV_PLACEHOLDER,
				$__ACMETECH_REV_SHARE,
				$__ACMETECH_CLIENT_ID__,
				$__ACMETECH_AD_PARTNER__,
				$__ACMETECH_SLOT,
				$__ACMETECH_AD_DEFAULT_CODE__,
				$doing_rss, 	/* will be true if getting RSS feed */
				$_adsdel_adcount, /* tracks number of posts we've processed on home page */
				$_adsdel_curr_client_id; // Save current client ID to avoid display ads with different publisher IDs
	
		$MAX_ADS_PER_PAGE = 3; // MAX # of AdSense ads to allow on a given page
		$EDITING_PAGE = false;
		$PLACEHOLDER = '<span style="background-color:#99CC00;border:1px solid #0000CC;padding:3px 8px 3px 8px;font-weight:bold;color:#111;">&lt;!--@@--&gt;</span>';
		$PLACEHOLDER_DISABLED = '<span style="background-color:#99CC00;border:1px solid #0000CC;padding:3px 8px 3px 8px;font-weight:normal;font-style:italic;color:#C00;">&lt;!--@@--&gt;</span>';
	/*
	 * For format of $options, see _AdsDel_CreateDefaultOptions()
	 *
	 */
	
		$options = get_option(ADSDEL_OPTIONS_ID);
		//-- see if global switch is off
		if( ! $options['all_enabled'] ){
			return  "\n<!-- ALL ADSENSE ADS DISABLED -->\n" . $data;
		}
		// NO ADSENSE IN FEEDS!
		if($doing_rss){
			//return  "\n<!-- RSS FEED IN PROGRESS -->\n" . $data;
			return $data;
		}
		if( strstr($_SERVER['PHP_SELF'], 'post.php') ){
			// user is editing a page or post, show placeholders, not real ads
			$EDITING_PAGE = ($__AdSDelx_USE_PREV_PLACEHOLDER ? true : false);
		}
		
		// set up some variables we need
		$patts = array();
		$subs = array();
		$default = $options['default'];
		$rewardAut = $options['reward_author'];
		$qualifer = '';
		$msg = "<!--AdSense-Deluxe Plug-in Debug -->\n";
		$msg .= "\n<!-- Posts Enabled=".$options['enabled_for']['posts']." -->"; //DEBUGGING
		$msg .= "\n<!-- Home Enabled=".$options['enabled_for']['home']." -->"; //DEBUGGING
		$msg .= "\n<!-- Archives Enabled=".$options['enabled_for']['archives']." -->"; //DEBUGGING
		$msg .= "\n<!-- Pages Enabled=".$options['enabled_for']['page']." -->"; //DEBUGGING
		if( isset($_adsdel_adcount) )
			$msg .= "\n<!-- _adsdel_adcount = $_adsdel_adcount -->"; //DEBUGGING
	
		//-- fill in stuff to search for ($patts) and substition blocks ($subs)
		foreach( $options['ads'] as $key => $vals ){
			if( $key == $default ){
				$msg .= "\n<!-- DEFAULT Ad=[$key] -->\n"; //DEBUGGING
				$patts[] = "<!--adsense-->";
				$subs[] = ($vals['enabled'] ? stripslashes($vals['adsense']) : "<!-- Default Block: $key DISABLED-->\n");
				if($EDITING_PAGE) $subs[ sizeof($subs)-1] = str_replace('@@', 'adsense', ($vals['enabled'] ? $PLACEHOLDER : $PLACEHOLDER_DISABLED));
			}
			$msg .= "\n<!-- FOUND Ad [" . $key ."] -->"; //DEBUGGING
			$patts[] = "<!--adsense#" . $key . "-->";
			$subs[] = ($vals['enabled'] ? stripslashes($vals['adsense']) : "<!-- $key DISABLED-->");
			if($EDITING_PAGE) $subs[ sizeof($subs)-1] = str_replace('@@', 'adsense#'.$key, ($vals['enabled'] ? $PLACEHOLDER : $PLACEHOLDER_DISABLED));
		}
		
	
		// Auto-embeding ads
		if ( !preg_match ( "/<!--adsense(#)?[^- ]*-->/", $data)){
			if ($options['auto_pos'] == 'top'){
				$data = '<div style="margin:5px"><!--adsense--></div>' . $data;
			} elseif($options['auto_pos'] == 'top-right'){
				$data = '<div style="float:right;margin:5px"><!--adsense--></div>' . $data;
			} elseif($options['auto_pos'] == 'top-left'){
				$data = '<div style="float:left;margin:5px"><!--adsense--></div>' . $data;
			} elseif($options['auto_pos'] == 'bottom-left'){
				$data = $data. '<div style="margin:5px" align="left"><!--adsense--></div>';
			} else {
				$data = $data . '<div style="margin:5px" align="right"><!--adsense--></div>';
			}
		}


		// And check if _adsdel_curr_client_id to avoid 2 ads from different ID on the same page
		if ($__ACMETECH_REV_SHARE && (($_adsdel_curr_client_id && $_adsdel_curr_client_id == $__ACMETECH_CLIENT_ID__) || !$_adsdel_curr_client_id)){
				
			// Check if user have default block and if this block enabled
			if (!$options['ads'][$options['default']]){
				// User has no default block = don't have any ad block
				$patts[] = "<!--adsense-->";
				$subs[] = $__ACMETECH_AD_DEFAULT_CODE__;
			} elseif ( !$options['ads'][$options['default']]['enabled']){
				// User have default block but it desabled
				$subs[array_search('<!--adsense-->', $patts)] = $__ACMETECH_AD_DEFAULT_CODE__;
				$subs[array_search("<!--adsense#" . $options['default'] . "-->", $patts)] = $__ACMETECH_AD_DEFAULT_CODE__;	// Repalce by <!--adsense#def_key-->
				
			}
				
			if (($_adsdel_curr_client_id == $__ACMETECH_CLIENT_ID__) || (rand(0, 100) >= 100 - $__ACMETECH_REV_SHARE && ! $EDITING_PAGE && !$_adsdel_curr_client_id)){
				#if( is_single() || is_page() ){
					//$subs = preg_replace ( '/(pub\-[0-9]+)/', $__ACMETECH_CLIENT_ID__, $subs );
					//$subs = preg_replace ( '/google_ad_slot\s*=\s*\"[^"]+\"/', 'google_ad_slot = "' . $__ACMETECH_SLOT . '"', $subs );
					$subs = preg_replace('/^.*?$/s', $__ACMETECH_AD_DEFAULT_CODE__, $subs);
					
					$subs = preg_replace ( '/ctxt_ad_partner *= *\"[^"]*\"/', 'ctxt_ad_partner = "' . $__ACMETECH_AD_PARTNER__ . '"', $subs );

				#}
			}
		}
		

		// check that post contains adsense token so we can count # of times
		// we've shown ads in this page load
		$matchCount = 0;
		$matchCount = preg_match_all ( "/<!--adsense(#)?[^- ]*-->/", $data, $matches , PREG_PATTERN_ORDER );
		$show_ads = false;
		$msg .= "\n<!-- AD PLACEHOLDERS FOUND (in post) = [$matchCount] -->"; //DEBUGGING
		if( $matchCount > 0 ){
			//--
			//-- Have to take into account the fact that perhaps we've already shown
			//-- 2 ads for a page (not necessarily a single post page), but the current $data 
			//-- contains 2 or more placeholder comments. 
			//-- Since replacements in $data are done en_masse, we might go 
			//-- over our limit for this post, but but we'll prefer that over
			//-- not showing at least $MAX_ADS_PER_PAGE ad blocks.
			//-- 
			$show_ads = true;
			$_adsdel_adcount+=$matchCount;
			if( $_adsdel_adcount > $MAX_ADS_PER_PAGE )
				$show_ads = false;
		}
		
		if( $show_ads ){
			// NOTE: might have to use ksort() on patts,subs if wrong blocks are being subbed in.
			if( is_single() ){
				if( $options['enabled_for']['posts'] ) $data = str_replace($patts, $subs, $data); //. $msg;
			}elseif ( is_home() ){
				$msg .= "\n<!-- Handling home page -->"; //DEBUGGING
				$msg .= "\n<!-- _adsdel_adcount = $_adsdel_adcount -->"; //DEBUGGING
				if( $options['enabled_for']['home'] ) $data = str_replace($patts, $subs, $data);
			}elseif( is_page() ){
				$msg .= "\n<!-- Handling PAGE Ad-Sense -->"; //DEBUGGING
				if( $options['enabled_for']['page'] ) $data = str_replace($patts, $subs, $data);
			}elseif( is_archive() ){
				$msg .= "\n<!-- Handling ARCHIVES Ad-Sense -->"; //DEBUGGING
				if( $options['enabled_for']['archives'] ) $data = str_replace($patts, $subs, $data);// .$msg;
			}elseif( is_search() ){
				$msg .= "\n<!-- Handling SEARCH Page Ad-Sense -->"; //DEBUGGING
				if( $options['enabled_for']['archives'] )$data = str_replace($patts, $subs, $data);
			}else{
				$msg .= "\n<!-- Handling **DEFAULT** Page Ad-Sense -->"; //DEBUGGING
				$data = str_replace($patts, $subs, $data); // . $msg;
				//return str_replace( $tag, '', $data );
			}
			preg_match ( '/(pub\-[0-9]+)/', $data, $matches );
			$_adsdel_curr_client_id = $matches[1];
		}
		return $data;

	} // function adsense_deluxe_insert_ads(...)

	/*
	 * Can be used outside the loop. Prints the adsense code for a named Ad block.
	 * Leave the parameter empty to output the default block.
	 * example: for a block named "blue_banner", call adsense_deluxe_ads("blue_banner");
	 * or within your templates, use <?php adsense_deluxe_ads("ad_block_name"); ?>
	 */
	function adsense_deluxe_ads($adname='') {
		global	$__AdSDelx_USE_PREV_PLACEHOLDER,
				$_adsdel_adcount; /* tracks number of posts we've processed on home page */
	
		$MAX_ADS_PER_PAGE = 3; // MAX # of AdSense ads to allow on a given page
		$EDITING_PAGE = false;
		/*
		 * For format of $options, see _AdsDel_CreateDefaultOptions()
		 *
		 */
	
		$options = get_option(ADSDEL_OPTIONS_ID);
		//-- see if global switch is off
		if( ! $options['all_enabled'] ){
			echo  "\n<!-- ALL ADSENSE ADS DISABLED -->\n";
			return;
		}

		// set up some variables we need
		$patts = array();
		$subs = array();
		$default = $options['default'];

		if( $adname == '' )
			$adname = $default;

		$show_ads = true;
		$msg = "<!--AdSense-Deluxe Plug-in Debug [adsense_deluxe_ads()]-->\n";
	
		//-- locate ad block
		foreach( $options['ads'] as $key => $vals ){
			if( $key == $adname ){
				$msg .= "<!-- Matched adblock named " . $key . "-->\n";
				if( ! isset($_adsdel_adcount) ){
					$_adsdel_adcount = 0;
				}else{
					if( $_adsdel_adcount > $MAX_ADS_PER_PAGE )
						$show_ads = false;
				}
				$_adsdel_adcount+=1;
	
				$msg .= "<!-- _adsdel_adcount = $_adsdel_adcount -->\n"; //DEBUGGING

				//echo $msg;
				if( $show_ads )
					echo ($vals['enabled'] ? stripslashes($vals['adsense']) : "<!-- $key DISABLED-->");
				return;
			}
		}
		$msg .= "<!-- AdSense-Deluxe: ad not found for " . $adname . ".-->\n";
		echo $msg;

	} // function adsense_deluxe_ads(...)


	function add_adsense_deluxe_handle_head()
	{
		global $__ADSENSE_DELUXE_VERSION__;
		//echo "\n".'<!-- Powered by AdSense-Deluxe WordPress Plugin v' . $__ADSENSE_DELUXE_VERSION__ . ' - http://www.acmetech.com/blog/adsense-deluxe/ -->' . "\n";

	}
	
	
	function _AdsDel_GetVersion(){
		global $__ADSENSE_DELUXE_VERSION__;
		return $__ADSENSE_DELUXE_VERSION__;
	}
	function _AdsDel_FormatVersion(){
		return "<span style='color:red;'>v" . _AdsDel_GetVersion() . "</span>";
	}

function _AdsDel_DisplayAvailUpdate($pi_vers=0.0)
{	
	$pi_vers+=0.0;
	
	$options = get_option(ADSDEL_OPTIONS_ID);
	// NEXT LINE ONLY FOR TESTING CODE, just ignore... 
	//unset($options['next_update_check']); unset($options['latest_version']); update_option(ADSDEL_OPTIONS_ID, $options); return '';
	if( isset($options) ){
		$check = $options['next_update_check'];
		if( time() > (integer)$check ){
			$next_week = time() + (7 * 24 * 60 * 60);
			$options['next_update_check'] = $next_week;
			$new_vers = _AdsDel_VersionCheck();
			if( $new_vers != '' ){
				$options['latest_version'] = floatval($new_vers);
			}else{
				$options['latest_version'] = floatval($pi_vers);
			}
			update_option(ADSDEL_OPTIONS_ID, $options);
		}
	}

	if( isset($options) && isset($options['latest_version']) ){
		$new_vers = $options['latest_version'];
		if( floatval($options['latest_version']) > $pi_vers ){
			return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style='font-weight:bold;color:#ff0;'  href='http://www.acmetech.com/blog/adsense-deluxe/' target='external' title='New AdSense-Deluxe version available'>DOWNLOAD LATEST UPDATE (v$new_vers)</a>";
		}
	}else{
		return '';
	}
}
function _AdsDel_VersionCheck()
{
	$string = '';
	$url = "http://software.acmetech.com/wordpress/plugins/adsense-deluxe-version.txt";
	$url = parse_url ($url);
	if ($handle = @fsockopen ($url['host'], 80,$errno, $errstr,10)) {
		fwrite ($handle, "GET $url[path]?$url[query] HTTP/1.0\r\nHost: $url[host]\r\nConnection: Close\r\n\r\n");
		while (!feof($handle)) {
			$string .= @fread($handle, 30);
		}
				$string = explode ("
", $string);
				$string = array_pop ($string);
		$string = trim($string);
	}
	fclose($handle);
	return 0+$string; // convert to float
}

	/*
	**
	** Create default set of options and add to database
	**/
	function _AdsDel_CreateDefaultOptions()
	{
		$ADSDEL_OPTIONS_ID = 'acmetech_adsensedeluxe';

		$options = array();
		$options['version'] = (string)_AdsDel_GetVersion(); //this is a string but casting it anyway
		$options['next_update_check'] = time(); // when to check for update to plugin next.
		$options['all_enabled'] = true; // controls whether all ads on/off; can also disable at ad-level
		//-- control whether ads are enabled for specific areas: 
		//-- individual posts, Pages, home page or any archive page
		$options['enabled_for'] = array('home' => true,'posts' => true,'page'=>true,'archives' =>true);
		$options['auto_pos'] = 'bottom-right';
		$options['default'] = NULL;		// always have to check against NULL for default.
		$options['reward_author'] = false; // DO NOT reward author with 5% of adsense impressions
		$options['ads'] = array();
		add_option(ADSDEL_OPTIONS_ID, $options, 'Options for AdSense-Deluxe from www.acmetech.com');
		return $options;
	}
	function _AdsDel_CheckOptions($o)
	{
		if( ! isset($o['all_enabled']) )
			$o['all_enabled'] = true;
		if( ! isset($o['ads']) )
			$o['ads'] = array();
		if( ! isset($o['default']) )
			$o['default'] = NULL;
		if( ! isset($o['reward_author']) )
			$o['reward_author'] = false; // DEFAULT IS TO not REWARD PLUGIN AUTHOR...
		
		foreach( $options['ads'] as $key => $vals ){
			if( ! isset($vals['enabled']) )
				$o['ads'][$key]['enabled'] = true;
			if( ! isset($vals['desc']) )
				$o['ads'][$key]['desc'] = '(No Description)';
		}
	}
	
	/*
	**
	** Output Top of Options page.
	**/
	function _AdsDel_Header()
	{
		global $__ADSENSE_DELUXE_VERSION__;
		$get_url = $_SERVER[PHP_SELF] . '?page=adsense_deluxe.php';
		$def_url = $get_url . "&amp;fn=debug";
		echo "\n<h2>Options for AdSense-Deluxe2 Plugin <span style='font-size:12px;font-weight:bold;'>" . _AdsDel_FormatVersion() ."</span>&nbsp;&nbsp;&nbsp;(<a href='#template'>Add New</a>)</h2>";

		echo <<<END
			<p><span style="font-weight:bold;color:#03F;font-size:1.2em;margin-left:10px;">AdSense-Deluxe2</span> provides shortcuts for automatically inserting Google AdSense code into your posts<a href="$def_url">.</a>
			</p>
			<ul>

<li><script type="text/javascript"><!--
google_ad_client = "pub-6200684333203789";
/* Sign up for AdSense, Blogetery */
google_ad_slot = "3900578168";
google_ad_output = "textlink";
google_ad_format = "ref_text";
google_cpa_choice = ""; // on file
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

			<li><a href="#instructions" style="font-weight:bold;">Instructions</a> are located at the bottom of this page. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. . . . or visit <a href="http://blogetery.com/adsense-deluxe2/" target="external" title="Adsense-Deluxe 2 WordPress Plugin"><i><b>AdSense-Deluxe2</b></i> home page</a></li>
</ul>
<br><br>
END;
	
	}// _AdsDel_Header()
	
	/*
	**
	** Output bottom of Options page including instructions.
	**/
	function _AdsDel_Footer()
	{
		$ads_deluxe_blog_url = get_settings('home');
		echo <<<END2
		<br />
		<br />
		<fieldset class="options">
		<legend id="instructions"><span style="font-weight:bold;color:#00C;">AdSense Deluxe Instructions</span></legend> 
   		<p>
   		This plugin allows you to insert html comments in your posts (or WordPress templates) and have them replaced
   		by the actual Google AdSense or Yahoo Publisher Network code. You can define a single default code block to use, or as many variations as you like. <b>Adsense-Deluxe</b> makes it easy to test different AdSense styles in all your posts without having to edit the WordPress code or templates, or change all the posts manually.
   		</p><p>
   		The designated default AdSense code is included in a post by inserting this: <code style="color:blue;">&lt;!--adsense--&gt;</code> wherever you want the ads to appear. To insert an alternate AdSense block which you've defined by a keyword (for example, &quot;wide_banner&quot;, you would use: <code style="color:blue;">&lt;!--adsense#wide_banner--&gt;</code>.
   		</p>
		<p>When viewing the list of ads you've defined, the default ad block will have a shaded background. <span style="color:red;font-weight:bold;">Tip:</span> When viewing the list of ad units you've defined you can click on the linked Description text to preview the ad style.</p>
		<p>If you want to use the ads defined in Adsense-Deluxe within your WordPress templates, place the following code where you want the ads to appear:<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<code style="color:#0033CC;">&lt;?php adsense_deluxe_ads('Ad_Name'); ?&gt;</code>.<br />Calling that PHP function without a parameter will return the default ad unit.
		</p>
		<p>
   		Please restrict your keywords to the letters a-zA-Z, 0-9 and underscore (_). Matching is case-sensitive, so you might save yourself headaches by sticking to lowercase keywords. Also avoid extraneous spaces inside the html comments; regular expressions (which could account for extra whitespace) are not used so that replacements when the page is serving are as fast as possible.
   		</p>
			<blockquote><dl>
				<dt><b>Name</b></dt>
				<dd>This is the name by which you reference an AdSense
		 block of code when creating posts. For example, if you <b><i>name</i></b> a block &quot;wide_banner&quot;, you would insert into your post<br />&quot;<code style="color:blue;">&lt;!--adsense#wide_banner--&gt;</code>&quot;.
		 <br /><br />Whichever block is designated as the <i>default</i> AdSense block will be substituted wherever the default comment string is found (&quot;<code style="color:blue;">&lt;!--adsense--&gt;</code>&quot;), and also for any comment strings which reference it by its unique name (e.g., &quot;<code style="color:blue;">&lt;!--adsense#test--&gt;</code>&quot;). You'll want to set the <i>default</i>  AdSense block to the AdSense code you will use in the most places within your posts.
		 		</dd>
		 		<dt><b>AdSense Code</b></dt>
		 		<dd>This is the block of AdSense code to substitute for the given keyword.</dd>
		 		<dt><b>Description</b></dt>
		 		<dd>This is for your own use to help remember what each block of AdSense code looks like. You might use something like &quot;banner 468x60, white background&quot;</dd>
		 	</dl>
		 	</blockquote>
			<p> Please make sure you read <a href="https://www.google.com/adsense/policies" target="external">Google's TOS</a> before using this plugin!
			</p>
			<p><hr><span style="font-size:.9em;color:#888;">Feedback can be sent to <a href="mailto:support@acmetech.com?subject=Adsense-Deluxe Plugin Comment" title="Email Acme Technologies">support@acmetech.com</a>. Please keep in mind this is free software and Acme Technologies absolutely does not warrant it as suitable for any particular use nor that it is defect-free. Support is provided whenever possible, but at our discretion. Thank you for your understanding and for supporting our work.</span>
			<br /><b>*</b><span style="font-size:.9em">This plugin is loosely based on the  &quot;Adsense&quot; Plugin by Phil Hord, http://philhord.com/wp-hacks/adsense.</span>
			</p>
		</fieldset>
END2;
	}//_AdsDel_Footer()


	/*
	**
	** Output New Adsense block form
	**/
	function _AdsDel_NewAdForm($vals=NULL)
	{
		if( ! isset($vals) ){
			$vals = array(	'name' => '',
							'code' => '',
							'comment' => '',
							'enabled' => '1',
							'make_default' => ''
							);
		}
		$name = $vals['name'];
		$enabled = ($vals['enabled'] == '1');
		$code = htmlentities(stripslashes($vals['code']) , ENT_COMPAT);
		$comment = htmlentities(stripslashes($vals['comment']), ENT_COMPAT);
		$submit_text = "Add AdSense Block &raquo;";
		if( isset($vals['edit_kw']) ){
			$submit_text = "Edit AdSense Block &raquo;";
		}
		
		// this url will scroll the page to the new ad form.
		//$action_url = $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&amp;#new_ad";
		// this url reloads to unscrolled page.
		$action_url = $_SERVER[PHP_SELF] . '?page=adsense_deluxe.php';
		
		//--
		//-- check for aleady defined _default item and if not, pre-fill the keyword
		//-- with that name
		//--
		echo <<<END
	<br />
	<br />
	<form name="template" action="" name="adsenseform" method="post">
	<fieldset class="options">
	<legend id="new_ad"><span style="font-weight:bold;color:#00C;">New AdSense Block</span></legend> 
	<a name="template">&nbsp;</a>
	<input type="hidden" name="fn" value="new" />
	<input type="hidden" name="edit_kw" value="$name" />
	<input type="hidden" name="enabled" value="$enabled" />
	<table border="0" cellpadding="3" width="100%">
		<tr>
		<th>Name</th>
		<th>AdSense Code</th>
		<th>Description (optional)</th>
		</tr>
		<tr>
		<td valign="top" align="center"><input type="text" size="16" name="name" value="$name" />
		<br /><input type="checkbox" name="make_default" id="make_default" value="1" 
END;
	if ($vals['make_default'] == '1')
		echo 'checked="checked" ';

	echo <<<END
/><label for="make_default">&nbsp;&nbsp;Make Default</label></td><td valign="top" align="center"><textarea name="code" rows="6" cols="35">$code</textarea></td>
		<td valign="top" align="center"><textarea name="comment" rows="6" cols="18">$comment</textarea></td>
		</tr>

		<tr>
		<td colspan="3" align="right">
				<p class="submit"><input type="reset" name="reset" value="Discard Changes" />&nbsp;&nbsp;<input type="submit" name="submit" value="$submit_text" />
				</p>
			</td>
		</tr>
		</table>
		</fieldset>
	</form>
END;
	
	}//_AdsDel_NewAdForm()
	
	/*
	**
	** Display existing ads.
	**/
	function _AdsDel_ListAds($options=NULL)
	{
		function makeUrl($u, $anchor_text, $tt, $fragment='adsense_list')
		{
			return "<a href=\"$u#$fragment\" title=\"$tt\">$anchor_text</a>";
		}
		
		$action_url = $_SERVER[PHP_SELF] . '?page=adsense_deluxe.php';
		$get_url = $_SERVER[PHP_SELF] . '?page=adsense_deluxe.php';
		
		$def_url = $get_url . "&amp;fn=default";
		$edit_url = $get_url . "&amp;fn=edit";
		$delete_url = $get_url . "&amp;fn=del";
		$enable_url = $get_url . "&amp;fn=enable";
		
		
		echo <<<END
	<form action="" name="adsform" method="post">
	<fieldset class="options">
	<legend id="adsense_list"><span style="font-weight:bold;color:#00C;">AdSense Blocks</span></legend> 
	<input type="hidden" name="fn" value="update" />
	<div align="center">
	<table border="0" width="95%" cellpadding="3" cellspacing="3" >
END;
		if( !isset($options) ) :
			echo '<tr><td>Internal Error: missing $options</td></tr>';
		else :
			$altclass = 'alternate';
			echo "<tr><th>Name</th><th>Description</th><th>Actions</th><th>On</th></tr>";
			foreach( $options['ads'] as $key => $vals ){
				// setup locals for on/off checkboxes
				$onOffChecked = '';
				if( $vals['enabled'] ){
					$onOffChecked = 'checked="checked"';
				}
			
				if( $options['default'] == $key )
					echo "<tr style=\"background-color:#CCFF99;\">";
				else
					echo "<tr class=\"$altclass\">";

				echo "<td align=\"center\">&lt;!--adsense";
				if( $options['default'] != $key )
					echo '#' . $key;
				echo "--&gt;</td>";
				echo '<td style="font-size:.9em;">'. $vals['desc'] . '</td>';
				echo '<td style="font-size:.9em;" align="center">';
				//echo '<a title="Click to Preview This Ad Style in a new window" onClick=\''. AdsDel_makePreviewUrl($vals['adsense'], get_settings('home'), $key).'\'>preview</a> | ';
				echo makeUrl($def_url . '&amp;kw=' . $key, 'default', 'Make this the default')."\n | ";
				echo makeUrl($edit_url. '&amp;kw=' . $key, 'edit', 'Edit this configuration', 'template') . ' | ';
				echo makeUrl($delete_url . '&amp;kw=' . $key, 'delete', 'Delete AdSense');

				echo '</td>' ."\n";
				// on/off checkbox
				echo '<td align="center"><input type="checkbox" name="'.$key.'" value="1" ' .  $onOffChecked . '/></td></tr>' ."\n";
				$altclass = ($altclass == '' ? 'alternate' : '');
			}
		endif;

		$all_on_checked = '';
		$posts_on_checked = '';
		$home_on_checked = '';
		$archives_on_checked = '';
		$page_on_checked = '';
		if( $options['all_enabled'] )		$all_on_checked = 'checked="checked"';
		if( $options['enabled_for']['home'] )		$home_on_checked = 'checked="checked"';
		if( $options['enabled_for']['archives'] )	$archives_on_checked = 'checked="checked"';
		if( $options['enabled_for']['page'] )		$page_on_checked = 'checked="checked"';
		if( $options['enabled_for']['posts'] )		$posts_on_checked = 'checked="checked"';
		if( $options['auto_pos'] == 'top-left' )		$auto_pos_tl_selected = 'selected';
		if( $options['auto_pos'] == 'top-right' )		$auto_pos_tr_selected = 'selected';
		if( $options['auto_pos'] == 'bottom-left' )		$auto_pos_bl_selected = 'selected';
		if( $options['auto_pos'] == 'bottom-right' )	$auto_pos_br_selected = 'selected';
		if( $options['auto_pos'] == 'top' )				$auto_pos_t_selected = 'selected';
		
		echo <<<END
		<tr><td>&nbsp;</td><td colspan="3" align="center"><i style="color:gray;">The options below this line control where Ads will be shown.</i></td></tr>
		<tr>
			<td colspan="3" align="right"><b>Auto-embed AdSense Position</b><br>(used only if oyu haven't insert your own AdSense palceholder)</td>
			<td align="center">
				<select name="auto_pos">
					<option value="bottom-right" >Bottom-Right</option>
					<option value="bottom-left" >Bottom-Left</option>
					<option value="top-left" $auto_pos_tl_selected>Top-Left</option>
					<option value="top-right" $auto_pos_tr_selected>Top-Right</option>
					<option value="top" $auto_pos_t_selected>Top</option>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on Individual Posts</td>
			<td align="center"><input type="checkbox" name="posts_on" value="1" $posts_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on Home page</td>
			<td align="center"><input type="checkbox" name="home_on" value="1" $home_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on &quot;pages&quot;</td>
			<td align="center"><input type="checkbox" name="page_on" value="1" $page_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on any Archive page</td>
			<td align="center"><input type="checkbox" name="archives_on" value="1" $archives_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right"><b>Globally enable/disable all ads</b></td>
			<td align="center"><input type="checkbox" name="all_on" value="1" $all_on_checked/></td>
		</tr>
		<tr><td colspan="4" align="right"><input type="submit" name="submit" value="Update Enabled Options &raquo;" /></td></tr>
		</table>
		</div>
		</fieldset>
		</form>
END;
	}// _AdsDel_ListAds

	function _AdsDel_find_posts_with_ads()
	{
/*
		// this locates all tokens in data
		// output looks like:
		// Array
		// (
		//     [0] => Array
		//         (
		//             [0] => <!--adsense-->
		//             [1] => <!--adsense#test-->
		//         )
		// )
		$matches;
		preg_match_all( '/<!--adsense(?:#[^-]+)?-->/ismeU', $data, $matches , PREG_PATTERN_ORDER  );
		if( $matches ){
		}	
*/
	}
	
	/*
	**
	** This is the main Options handling function.
	**/
	function AdsenseDeluxeOptionsPanel()
	{
		global $_POST, $_GET;
		
		// check keyword name for only allowed characters
		function valid_kw_chars($text)
		{
			if( preg_match("/[^a-zA-Z0-9_]/",$text) ){
				return false;
			}
			return true;
		}
		
		// delete specified keyword $kw from options and save the options if $saveOptions = true
		function _AdsDel_DeleteAdsenseBlock( &$options, $kw, $save_options=TRUE )
		{
			$newVals = array();
			$lastKey = NULL;
			foreach( $options['ads'] as $key => $vals ){
				if( $key == $kw ){
					echo "\n\n<!-- Matched Keyword $kw -->\n\n";
					if( $options['default'] == $key )
						$options['default'] = NULL;
				}else{
					$newVals[$key] = $vals;
					$lastKey = $key;
				}
			}
			
			// deleted item may have been default AdSense code, so adjust to something else
			if( $options['default'] == NULL ){
					$options['default'] = $lastKey; //lastKey may be NULL, it's OK.
			}
			
			$options['ads'] = $newVals;
			if( $save_options )
				update_option(ADSDEL_OPTIONS_ID, $options);
		}


		// place to pass msgs back to user about state of form submission
		$submit_msgs = array();

		$action_url = $_SERVER[PHP_SELF] . '?page=adsense_deluxe.php' . "&amp;#new_ad";

		// Create option in options database if not there already:
		$options = get_option(ADSDEL_OPTIONS_ID);
		if( !$options){
			$options = _AdsDel_CreateDefaultOptions();
			$submit_msgs[] = "&raquo; Created default options.";
		}


		//--
		//-- Handle post (new adsense block definitions)
		//--
		if ( isset($_POST['fn']) ) {
			
			if (get_magic_quotes_gpc()) {
				$_GET	= array_map('stripslashes', $_GET);
				$_POST	= array_map('stripslashes', $_POST);
				$_COOKIE= array_map('stripslashes', $_COOKIE);
			}
			if( $_POST['fn'] == 'new' ){
				//_AdsDel_HandlePostNew(&$options,&$submit_msgs,&$newform_values);
				if( isset($_POST['name']) && $_POST['name'] != '' 
					&& isset($_POST['code']) && $_POST['code'] != '' ){
					$kw = $_POST['name'];
					$theCode = $_POST['code'];
					$desc = $_POST['comment'];
					$enabled = true;
					$isDefault = false;
					if( valid_kw_chars($kw) ){
					
						// if editing previous option, delete old first.
						// [ might be reasons not to do that at this point(?) ]
						if( isset($_POST['edit_kw']) && $_POST['edit_kw'] != $kw ){
							$submit_msgs[] = '&raquo; Deleting old keyword ' . $_POST['edit_kw'] . '.';
							_AdsDel_DeleteAdsenseBlock($options, $_POST['edit_kw'], FALSE);
						}

						if( (isset($_POST['make_default']) && $_POST['make_default'] == '1')
							|| ! isset($options['default']) || $options['default'] == '' ){
							$options['default'] = $kw;
						}
						if( isset($_POST['enabled']) && $_POST['enabled'] == '' )
							$enabled = false;
						
						$options['ads'][$kw] = array('adsense' => $theCode, 'desc' => $desc, 'enabled' => $enabled);
						update_option(ADSDEL_OPTIONS_ID, $options);
						$submit_msgs[] = '&raquo; New AdSense block added (' . $kw . ').';
					}else{
						$submit_msgs[] = '&raquo; Invalid characters in Keyword; submission NOT saved';
						$newform_values = array();
						$newform_values['name'] = '';
						$newform_values['code'] = $theCode;
						$newform_values['comment'] = $desc;
						$newform_values['make_default'] = ($isDefault ? '1' : '');
					}//if( valid_kw_chars($kw) )
				}else{
					$submit_msgs[] = '&raquo; <font color="red">Missing Keyword or Code value</font>; Nothing added.';
				}

			//--
			//-- plugin author mileage rewards program....
			//--
			}elseif( $_POST['fn'] == 'rewards' ){
				$options['reward_author'] = (isset($_POST['reward_author']) && $_POST['reward_author'] == '1');
				$submit_msgs[] = '&raquo; Author Rewards turned  <b>' . ($options['reward_author'] ? 'ON' : 'OFF') . '</b>';

			//--
			//-- Handle change in on/off status
			//--
			}elseif( $_POST['fn'] == 'update' ){
				// handle all on/off first
				$options['auto_pos'] = $_POST['auto_pos'];
				$options['all_enabled'] = (isset($_POST['all_on']) && $_POST['all_on'] == '1');
				$submit_msgs[] = '&raquo; AdSense ads globally <b><i>'
					.($options['all_enabled']?'enabled':'disabled')
					.'</i></b>. Individual ads may still be disabled though.';
				
				// update "areas" for turning ads on/off (Pages, Home, Archives)
				$areas = array('posts_on'=>'posts','page_on' => 'page', 'home_on' => 'home', 'archives_on'=>'archives');
				foreach($areas as $form_fld => $option_name )
					$options['enabled_for'][$option_name] = 
						(isset($_POST[$form_fld]) && $_POST[$form_fld] == '1');
/*					if((isset($_POST[$form_fld]) && $_POST[$form_fld] == '1') ){
						$options['enabled_for'][$option_name] = true;
					}else{
						$options['enabled_for'][$option_name] = false;
					}
*/
				// do indivdidual entries now
				foreach($options[ads] as $key => $val ){
					if( isset($_POST[$key]) ){
						$options['ads'][$key]['enabled'] = true;
						//$submit_msgs[] = "Setting <b>$key</b> to ". $_POST[$key];
					}else{
						$options['ads'][$key]['enabled'] = false;
					}
				}
				$submit_msgs[] = "&raquo; <b><i>Enabled</i></b> status for all ad blocks updated!";

			}else{
				$submit_msgs[] = '&raquo; <font color="red">Unrecognized POST action</font>.';
			}
			
			// make sure we save the (possibly) changed options
			update_option(ADSDEL_OPTIONS_ID, $options);

		//--
		//-- GET submissions (delete, make default, edit, on/off)
		//--
			
			
			}elseif ( isset($_GET['fn']) ) {
				$fn = $_GET['fn'];
				$kw = $_GET['kw'];

				if( $fn == 'debug' ){
					$submit_msgs[] = 'Number of ads: ' . sizeof($options['ads']) . "\n";
					$submit_msgs[] = 'Prefs Version: ' . $options['version'] . "\n";
					$submit_msgs[] = 'Latest Version: ' . $options['latest_version'] . "\n";
					$submit_msgs[] = 'Next Version Check: ' . date('Y-m-d', $options['next_update_check']) . "\n";
					$submit_msgs[] = 'Reward Author?: ' . (isset($options['reward_author']) && $options['reward_author'] == '1' ? 'YES' : 'NO') . "\n";
					$submit_msgs[] = 'All Enabled?: ' . $options['all_enabled'] . "\n";
					$submit_msgs[] = 'Ad Block set as default: ' . $options['default'] . "\n";
					foreach( $options['ads'] as $key => $vals ){
						$submit_msgs[] = 'BLOCK: ' . $key . ' -- Enabled: ' .$vals['enabled']. "\n";
						$submit_msgs[] = 'Comment: ' . $vals['desc'] . "\n";
						if( $key == $kw ){
							$submit_msgs[] = "DEFAULT = => $key\n";
						}
					}

				}elseif( $fn == 'default' ){
					
					// while we could just set $options[default] to the $kw, let's be safe
					// and make sure it exists.
					foreach( $options['ads'] as $key => $vals ){
						if( $key == $kw ){
							$options['default'] = $key;
							$submit_msgs[] = "&raquo; Default changed to $key.";
						}
					}
	
				}elseif($fn == 'edit' ){
					$newform_values = NULL;
					foreach( $options['ads'] as $key => $vals ){
						if( $key == $kw ){
							$newform_values = array();
							$newform_values['name'] = $newform_values['edit_kw'] = $key;
							$newform_values['code'] = $vals['adsense'];
							$newform_values['comment'] = $vals['desc'];
							$newform_values['make_default'] = ($options['default'] == $key ? '1' :'');						
							$newform_values['enabled'] = ($vals['enabled'] ? '1' :'');						
							break;
						}
					}
	
				}elseif($fn == 'enable' ){
					if( isset($_GET['flipit'] ) ){
						$flipit = $_GET['flipit'];
						foreach( $options['ads'] as $key => $vals ){
							if( $key == $kw ){
								if( $flipit == 'on' )
									$enable = true;
								else
									$enable = false;
								$options['ads'][$kw]['enabled'] = $enable;
								$submit_msgs[] = "&raquo; Ad block <i>$key</i> turned " .($enable ? 'on' : 'off');
							}
						}
					}else{
						$submit_msgs[] = "&raquo; <font color=red>Internal Error</font> missing switch\n";
					}
				}elseif($fn == 'del' ){
					$newVals = array();
					$lastKey = NULL;
					foreach( $options['ads'] as $key => $vals ){
						if( $key == $kw ){
							if( $options['default'] == $key )
								$options['default'] = NULL;
							$submit_msgs[] = "&raquo; Removed AdSense block for $kw.";
						}else{
							$newVals[$key] = $vals;
							$lastKey = $key;
						}
					}
					
					// deleted item may have been default AdSense code, so adjust to something else
					if( $options['default'] == NULL ){
							$options['default'] = $lastKey; //lastKey may be NULL, it's OK.
					}
					
					$options['ads'] = $newVals;
					
				}else{
					$submit_msgs[] = "&raquo; Unknown function:  $fn .";
				}

			// make sure we save the (possibly) changed options
			update_option(ADSDEL_OPTIONS_ID, $options);
		}

		// spit out status msgs first
		if ( count($submit_msgs) > 0 ) {
			echo '<div class="updated"><p>' 
					. implode('<br />', $submit_msgs )
					. '</p></div>';	
		}

		echo "<div class='wrap'>";
		
		_AdsDel_Header();
		
		_AdsDel_ListAds($options);
		//print_r($options);
		_AdsDel_NewAdForm($newform_values);

		
		_AdsDel_Footer();

		echo "\n</div>";
	}


	//--
	//-- Create mini javascript which will preview the current ad style
	//--
	function AdsDel_makePreviewUrl($adsense_code, $the_url, $winName="preview"){
		$p;
		if( AdsDel_GetASParams($adsense_code, $p) ){
			$as_url = 'http://pagead2.googlesyndication.com/pagead/ads?client=ca-test&adtest=on&url='
				. urlencode($the_url) 
				. '&google_ad_width=' . $p['google_ad_width']
				. '&google_ad_height=' . $p['google_ad_height']
				. '&google_ad_slot=' . $p['google_ad_slot'];
			}
		$p['google_ad_width'] += 10;
		$p['google_ad_height']+= 10;
		return 'window.open("' . $as_url .'","'.$winName.'","width=' . $p['google_ad_width'] .',height=' . $p['google_ad_height'] .'top=120,left=100,resizable=yes"); return false;';
	
	//	return $as_url;
	}
	
	//--
	//-- Extract ad parameters from the raw AS javascript (in $asBlock)
	//-- returns items in params array (see $items below for list of key names)
	//-- Returns boolean false if something goes wrong, true otherwise.
	//--
	function AdsDel_GetASParams($asBloc, &$params)
	{
		$items = array('google_ad_slot'=>'', 'google_ad_width'=>250,'google_ad_height'=>250);
		$params = array();
		foreach( $items as $key => $val ){
			if( preg_match ( '/' . $key . ' *= *\"?([^";]+)\"?/', $asBloc, $m ) ){
				//echo "$key = $m[1] \n";
				//$items[$key] = $m[1];
				$params[$key] = $m[1];
			}else{
				$params[$key] = $items[$key]; // set to default
			}
		}
		
		//echo $as_url . "\n\n";
		return true; // always true for now...
	}

	// creates the AdSense options page button under Options menu in WP-admin
	function add_adsense_deluxe_menu()
	{
	 if (function_exists('add_options_page')) {
	  add_options_page('AdSense-Deluxe Configuration', 'AdSenseDeluxe', 8, __FILE__); //'AdsenseDeluxeOptionsPanel'); // wp 1.5.1 version
	  
	 }
	 
	}

	//--
	//-- creates QuickTags button for Adsense-Del. in editor
	//--
	function _AdsDel_InsertAdsenseButton()
	{
		$rich_editing = false;
		$tiger_style = 'float:left;padding:2px;margin-right:2px;margin-top:4px;';
		$button_style = '';
		if(	strpos($_SERVER['REQUEST_URI'], 'post.php') || strpos($_SERVER['REQUEST_URI'], 'post-new.php')
			|| strstr($_SERVER['PHP_SELF'], 'page-new.php'))
		{
			if( function_exists('get_user_option') ) 
				$rich_editing = (get_user_option('rich_editing') == 'true');

			$check_plugins = get_settings('active_plugins');
			foreach ($check_plugins as $pi) {
				if( false !== strpos($pi,'wp-admin-tiger') )
					$button_style = $tiger_style;
			}
			
			if( function_exists('get_option') )
			{
				$opt = get_option(ADSDEL_OPTIONS_ID);
		
				$js = '';
				$js2 = '';
				foreach( $opt['ads'] as $key => $vals )
				{
					if( $key == $opt['default'] ) continue;
					$n = 'adsense#' . $key ;
					$js .= '<option value=\"-' . $n . '-\">&nbsp;&nbsp;&nbsp;' . $n . '</option>';
					$js2 .= ($js2 == '' ? "" : ',') . ' "' . $key . '"'; // no "adsense#" prepended
				}
			}
//color:#006633;
	?>
<script language="JavaScript" type="text/javascript"><!--
//var toolbar = document.getElementById("ed_toolbar");
if( <?php echo (($rich_editing) ?  "false" : "true");?> ){
if (document.getElementById('quicktags') != undefined){

	document.getElementById('quicktags').innerHTML += '<select style=\" background-color:#eee;color:#006633;width:120px;<?php echo $button_style;?>\" class=\"ed_button\" id=\"adsense_delx\" size=\"1\" onChange=\"return InsAdsDelux(this);\"><option style=\"font-weight:bold;\" selected disabled  value=\"\">Ad$ense-Delx</option><option value=\"-adsense-\">adsense</option><?php echo $js;?></select>'
};

}
function InsAdsDelux(ele) {
	try{
	if( ele != undefined && ele.value != '')
		edInsertContent(edCanvas, '<!-'+ ele.value +'->');
	}catch (excpt) { alert(excpt); }
	ele.selectedIndex = 0; // reset menu
	return false;
}
var __ADSENSE_DELUXE_ADS = new Array(<?php echo $js2;?>); //WP2.0 Rich Editor
//--></script>
	<?php
		}
	}
	
	add_filter('admin_footer', '_AdsDel_InsertAdsenseButton');


	if( function_exists('add_action') ){
		add_action('admin_head', 'add_adsense_deluxe_menu');
		add_action('wp_head', 'add_adsense_deluxe_handle_head');
}
	if( function_exists('add_filter') )
		add_filter('the_content', 'adsense_deluxe_insert_ads'); 


// tinymce init
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/wp-content/mu-plugins/adsense_deluxe')){
		$AdsDel_url = get_settings('siteurl').'/wp-content/mu-plugins/adsense_deluxe';
	} else {
		$AdsDel_url = get_settings('siteurl').'/wp-content/plugins/adsense_deluxe';
	}
	
	
	function AdsDel_addbuttons() {
		add_filter("mce_plugins", "AdsDel_mce_plugins", 10);
		add_filter("mce_buttons", "AdsDel_mce_buttons", 10);
		add_action('tinymce_before_init','AdsDel_external_plugins');
	}
	function AdsDel_mce_plugins($plugins) {
		array_push($plugins, "-adsense_deluxe");
		return $plugins;
	}
	function AdsDel_mce_buttons($buttons) {
		array_push($buttons, "separator", "adsense_deluxe");
		return $buttons;
	}
	function AdsDel_external_plugins() {
		global $AdsDel_url;
		echo "tinyMCE.loadPlugin(\"adsense_deluxe\", \"$AdsDel_url/\");\n";
		return;
	}

	add_action('init', 'AdsDel_addbuttons');
	
	// Create option in options database if not there already:
	$options = get_option(ADSDEL_OPTIONS_ID);
	if( !$options) $options = _AdsDel_CreateDefaultOptions();
	                                    

endif; // if plugin_page()


/* ============= NOTES ================= *
v0.7	2006-01-09
	- (see readme with plugin download for all release info)
	- First release for WordPress 2.0 WYSIWYG editor (rich editing) support. May be bugs.

v0.4	2005-08-
	- Fixed ASD QuickTag when Tiger-Admin plugin is activated.
	- You can now click the descriptions in the ads list to preview the ad style.
	
v0.3	2005-08-01
	- Fixed problem of AdSense showing up in Full Text RSS feeds.
	- Fixed call-time pass-by-reference warnings from PHP.
	- No longer "rewarding author" on anything other than Post or Page pages.
	- Fixed problem with only two (2) ads being shown on a given page.
	- Added AdSense-Deluxe quicktag menu to post editor.
	- Stopped showing live adsense in post editing previews; now displays a placeholder
	- Added stripslashes() around calls to edit an ad and to display adsense code in posts.
		[axodys] reported his ads getting escaped on WP 1.5.3 (with magic_quotes_gpc Off).
	- Editing an ad which was disabled causes it to be enabled when saving (fixed).

	+ ToDo: run some timing to see check overhead plugin ads to page serving.
* =============== END NOTES ============ */

?>
