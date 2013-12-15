<?php
	/*
	Plugin Name: AppDetails 2
	Plugin URI: http://jordi.masip.cat/
	Description: Get easily the description of an app from the AppStore or Google Play. How to use: 1) Google Play: <strong>[app]com.android.chrome[/app]</strong> <em>(https://play.google.com/store/apps/details?id=<strong>com.android.chrome</strong>&hl=ca)</em>. 2) AppStore: <strong>[app]535886823[/app]</strong> <em>(https://itunes.apple.com/en/app/chrome/id<strong>535886823</strong>?mt=8)</em> 3) Windows Phone: <strong>[app]vimeo/ff8dadc8-8efd-42c7-a0f4-de7a48dd186b[/app]</strong> <em>(http://www.windowsphone.com/es-es/store/app/<strong>vimeo/ff8dadc8-8efd-42c7-a0f4-de7a48dd186b</strong>)</em>
	Version: 2.0.2
	Author: Jordi Masip i Riera
	Author URI: http://jordi.masip.cat/
	License: MIT
	*/

	//ini_set('display_errors', 1);
	//error_reporting(E_ALL);

	require_once('constants.php');
	require_once('config.php');

	function AP_custom_head() {
		echo '<link rel="stylesheet" type="text/css" href="' . plugins_url("", __FILE__) . '/template/'.AD_CSS_STYLE.'">';
	}
	add_action("wp_head", "AP_custom_head");

	function AP_getContent($data, $template){
		$contents=file_get_contents($template);
		foreach ($data as $k=>$v){
			$contents=str_ireplace('{{'.$k.'}}', $v, $contents);
		}
		return $contents;
	}

	function parseData($data){
		$cache=Array();
		preg_match_all("/(.*)=(.*);/mU", $data, $cache);
		//print_r($ret)
		$ret=Array();
		foreach ($cache[1] as $k=>$vKey){
			$ret[strtolower($vKey)]=trim($cache[2][$k]);
		}
		return $ret;
	}

	function AP_the_reescriptor($text) {
		$plugins_url = plugins_url("", __FILE__);
		$pattern = "/\[app\](.*?)\[\/app\]/s";
		preg_match_all($pattern, $text, $id);

		//print_r($id);

		$len = count($id) - 1;
		$i = 0;
		if($len >= 0) {

			$template_translation = addslashes(str_replace("\n", "", file_get_contents($plugins_url . "/template/translation.json")));
			$urls_list = Array();
			foreach ($id[$len] as $value) {
				//var_dump($value);
				if($value !== null || $value !== "") {
					//$data=json_decode(str_replace("“","\"",$value));
					$data=parseData($value);
					//print_r($data);
					$data['store']=strtoupper($data['store']);
					$app_box='';
					//print_r($data);
					//print_r(array(GPLAY,ITUNES, WSTORE));
					if (in_array($data['store'], array(GPLAY,ITUNES, WSTORE))){
						$urls_list []= $plugins_url . '/engine/get-json.php?app=' . $data['id'] . '&store='.$data['store'];
						$app_box = str_replace("{{i}}", $i, file_get_contents($plugins_url . "/template/template-loading.html"));
						$i++;
					} else {
						$app_box=AP_getContent($data, $plugins_url.'/template/'.AD_TEMPLATE);
					}
					$text = preg_replace($pattern, $app_box, $text, 1);
				}
			}
			//print_r($urls_list);
			$urls = '[ "'. implode('","', $urls_list) . '" ]';
			$template = str_replace("\n", "", file_get_contents($plugins_url . "/template/".AD_TEMPLATE));

$text .= <<<END
<script type="text/javascript">
window.AD_showAppInfo = function() {
	var template = '$template', template_translation = eval("(" + "$template_translation" + ")");
	for(key in template_translation) {
		template = template.replace(key, template_translation[key]);
	}
	var $ = jQuery, urls = $urls, i = 0;
	window.app_info = document.getElementsByClassName("ai-container");
	window.app_info_count = 0;
	for(i = 0; i < urls.length; i++) {
		$.getJSON(urls[i], function(data) {
			var app_info = window.app_info, new_template = template, k = 0;
			for(key in data) {
				var re=RegExp("{{"+key+"}}","gi")
				new_template = new_template.replace(re, data[key]);
			}
			app_info[window.app_info_count].innerHTML = new_template;
			window.app_info_count += 1;
		});
	}
}
if (typeof jQuery == 'undefined') {
    window.addEventListener("load", window.AD_showAppInfo);
} else {
    window.AD_showAppInfo();
}
</script>
END;
		}
		return $text;
	}

	function bytesConverter($int_bytes){
		if(strlen((string) $int_bytes) >= 10) {
			return round(intval($int_bytes) / 1073741824, 2) . " GB";
		}
		else {
			return round(intval($int_bytes) / 1048576, 2) . " MB";
		}
	}

	add_filter("the_content", "AP_the_reescriptor");
?>