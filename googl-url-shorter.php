<?php
/*
Plugin Name: Goo.gl Url Shorter
Plugin URI: http://codecto.com/
Description: Goo.gl Url Shorter
Author: CodeCTO
Author URI: http://codecto.com/
Version: 1.0
*/
/* 
API Google URL Shortner - goo.gl 
Marcus Nunes - marcusnunes.com - 9/18/2010

eg:
$googl = new goo_gl('http://marcusnunes.com');
echo $googl->result();
*/

class goo_gl{
	
	var $url, $resul;
	
	//goo.gl construct method
	function goo_gl($url){
		
		$this->url = $url;
		
		$curl = curl_init(); 
		curl_setopt($curl, CURLOPT_URL, 'http://goo.gl/api/url'); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($curl, CURLOPT_POST, 1); 
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'user=cantonbolo@google.com&url='.urlencode($this->url).'&auth_token='.$this->googlToken($url)); 
		$saida = curl_exec($curl); 
		curl_close($curl);
		if($saida){
			$json = json_decode($saida);
			$this->resul = $json->short_url;
		}
	}
	
	//show url shorted by goo.gl
	function result(){
		return $this->resul;
	}
	
	//token code
	function googlToken($b){
		$i = $this->tke($b);
		$i = $i >> 2 & 1073741823;
		$i = $i >> 4 & 67108800 | $i & 63;
		$i = $i >> 4 & 4193280 | $i & 1023;
		$i = $i >> 4 & 245760 | $i & 16383;
		$j = "7";
		$h = $this->tkf($b);
		$k = ($i >> 2 & 15) << 4 | $h & 15;
		$k |= ($i >> 6 & 15) << 12 | ($h >> 8 & 15) << 8;
		$k |= ($i >> 10 & 15) << 20 | ($h >> 16 & 15) << 16;
		$k |= ($i >> 14 & 15) << 28 | ($h >> 24 & 15) << 24;
		$j .= $this->tkd($k);
		return $j;
	}

	function tkc(){
		$l = 0;
		foreach(func_get_args() as $val){
			$val &= 4294967295;
			$val += $val > 2147483647 ? -4294967296 : ($val < -2147483647 ? 4294967296 : 0);
			$l   += $val;
			$l   += $l > 2147483647 ? -4294967296 : ($l < -2147483647 ? 4294967296 : 0);
		}
		return $l;
	}

	function tkd($l){
		$l = $l > 0 ? $l : $l + 4294967296;
		$m = "$l";  //deve ser uma string
		$o = 0;
		$n = false;
		for($p = strlen($m) - 1; $p >= 0; --$p){
			$q = $m[$p];
			if($n){
				$q *= 2;
				$o += floor($q / 10) + $q % 10;
			} else {
				$o += $q;
			}
			$n = !$n;
		}
		$m = $o % 10;
		$o = 0;
		if($m != 0){
			$o = 10 - $m;
			if(strlen($l) % 2 == 1){
				if ($o % 2 == 1){
					$o += 9;
				}
				$o /= 2;
			}
		}
		return "$o$l";
	}

	function tke($l){
		$m = 5381;
		for($o = 0; $o < strlen($l); $o++){
			$m = $this->tkc($m << 5, $m, ord($l[$o]));
		}
		return $m;
	}

	function tkf($l){
		$m = 0;
		for($o = 0; $o < strlen($l); $o++){
			$m = $this->tkc(ord($l[$o]), $m << 6, $m << 16, -$m);
		}
		return $m;
	}
		
}

add_action('admin_menu', 'GUS_option_page');
function GUS_option_page() {
	add_options_page('Goo.gl URL Shorter', 'Goo.gl URL Shorter', 'manage_options', 'GUS_option_page', 'GUS_option_page_content');
}

function GUS_option_page_content(){
	if($_POST){
		check_admin_referer('GUS_setting_save','GUS_setting_save_nonce');
	}
	?>
<script language=javascript>
var api = '<?php echo site_url('/wp-admin/admin-ajax.php');?>';
function GUS_get_short_url(){
	var $ = jQuery;
	$.getJSON(api+'?callback=?',{
		action : 'GUS_api',
		long_url : $('#long_url').val()
		},function(data){
			$('#short_url').val(data.short_url);
		}
	)
}
</script>
<div class="wrap">
<div class="icon32" id="icon-options-general"><br></div><h2>Goo.gl URL Shoter</h2>

<form method="post" action="<?php echo admin_url('options-general.php?page=GUS_option_page'); ?>" name="form">
<?php wp_nonce_field('GUS_setting_save','GUS_setting_save_nonce'); ?>
<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="long_url">Long URL: </label>
			</th>
			<td>
				<input type="text" class="regular-text code" value="" id="long_url" name="long_url">
			</td>
		</tr>
		<tr>
			<th>
				<label for="short_url">Short URL:</label>
			</th>
			<td>
				<input type="text" class="regular-text code" value="" id="short_url" name="short_url">
			</td>
		</tr>
		<tr>
			<th>
			</th>
			<td>
				<a href="javascript:void(0)" class="button-primary" onclick="GUS_get_short_url();return false;">Get Short URL</a>
			</td>
		</tr>
	</tbody>
</table>

</div>
	<?php
}

add_action('wp_ajax_GUS_api', 'GUS_api');
add_action('wp_ajax_nopriv_GUS_api', 'GUS_api');
function GUS_api(){
	$googl = new goo_gl($_REQUEST['long_url']);
	$result = array(
		'long_url' => $_REQUEST['long_url'],
		'short_url' => $googl->result(),
	);
	if($_REQUEST['callback']){
		echo $_REQUEST['callback'].'('.json_encode($result).')';
	}else{
		echo json_encode($result);
	}
	die;
}

register_activation_hook( __FILE__, 'GUS_dbinstall');
/*
CREATE TABLE `wp`.`wp_gus` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`title` TEXT NOT NULL ,
`long_url` TEXT NOT NULL ,
`short_url` TEXT NOT NULL
) ENGINE = MYISAM ;
*/
function GUS_dbinstall() {
	global $wpdb;
	$table_name = $wpdb->prefix.'gus';
	if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."gus'") != $table_name) {
		$query = "CREATE TABLE ".$wpdb->prefix."gus (
  ID int(11) NOT NULL auto_increment,
  title text NOT NULL,
  long_url text NOT NULL,
  short_url text NOT NULL,
  PRIMARY KEY  (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;;";
		$wpdb->query($query);
	}
}

add_shortcode( 'googl', 'googl_shortcode' );
function googl_shortcode( $atts, $content = null ) {
	if(!filter_var($content, FILTER_VALIDATE_URL)){
		return $content;
	}
	global $wpdb;
	$table_name = $wpdb->prefix.'gus';
	extract( shortcode_atts( array(
		'title' => '',
		'context' => '',
	), $atts ) );
	$googl = new goo_gl($content);
	$short_url = $googl->result();
	if(!$wpdb->get_var($wpdb->prepare( "SELECT * FROM $table_name WHERE long_url = '$content'" ))){
		$wpdb->insert($wpdb->prefix.'gus', array(
			'id' => null,
			'title' => $title,
			'long_url' => $content,
			'short_url' => $short_url,
		));
	}
	return '<a href="'.$short_url.'"'.($title?' title="'.$title.'"':'').' target="_blank">'.($context?$context:$content).'</a>';
}