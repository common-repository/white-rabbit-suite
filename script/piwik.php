<?php
/**
 * Created by PhpStorm.
 * User: Enterprise
 * Date: 18/05/2016
 * Time: 12:48
 */

include_once(ABSPATH . 'wp-includes/pluggable.php');

function printPiwikScript()
{
	echo getPiwikScript();
}

function getUserEmail() {
	$wpUserEmail = "";
	$wpUser =  wp_get_current_user();
	if ($wpUser instanceof WP_User) {
		$wpUserEmail = $wpUser->get('user_email');
	}
	return $wpUserEmail;
}

function getPiwikScript()
{
	if (get_option("wr_general_enabled")) {
        $piwikCode = get_option("wr_plugin_wordpress_analytics_piwik_code");
		$piwikUrl = get_option('wr_whiterabbit_piwik_url');
		$scriptUrl = get_option('wr_plugin_wordpress_script_url');
        $scriptToken = get_option('wr_plugin_wordpress_script_token');
		$userEmail = getUserEmail();
		$setUserId = empty($userEmail) ? "" : "_paq.push(['setVisitorId', '" . hash('fnv1a64',$userEmail) . "']); _paq.push(['setUserId', '$userEmail']);";

		return <<<EOF
<script type="text/javascript">
function fnv1a64(r){var t,n=[];for(t=0;t<256;t++)n[t]=(t>>4&15).toString(16)+(15&t).toString(16);var o=r.length,a=0,f=8997,e=0,g=33826,i=0,v=40164,c=0,h=52210;for(t=0;t<o;)e=435*g,i=435*v,c=435*h,i+=(f^=r.charCodeAt(t++))<<8,c+=g<<8,f=65535&(a=435*f),g=65535&(e+=a>>>16),h=c+((i+=e>>>16)>>>16)&65535,v=65535&i;return n[h>>8]+n[255&h]+n[v>>8]+n[255&v]+n[g>>8]+n[255&g]+n[f>>8]+n[255&f]}

document.addEventListener( 'wpcf7submit', function( event ) {
	var inputs = event.detail.inputs;
	for ( var i = 0; i < inputs.length; i++ ) {
		if ( 'email' == inputs[i].name && inputs[i].value !== '' && typeof _paq !== 'undefined') {
			_paq.push(['setVisitorId', fnv1a64(inputs[i].value)]);
			_paq.push(['setUserId', inputs[i].value]);
			_paq.push(['trackPageView']);
			_paq.push(['enableLinkTracking']);
		}
	}
}, false );
</script>
<!-- WR -->
<script id="wr-script" type="text/javascript" src="{$scriptUrl}?token={$scriptToken}"></script>
<noscript><p><img src="{$piwikUrl}matomo.php?idsite={$piwikCode}" style="border:0;" alt=""/></p></noscript>
<!-- End WR -->
<script type="text/javascript">
(function() {
    {$setUserId}
})();
</script>
EOF;
	}

	return "";
}