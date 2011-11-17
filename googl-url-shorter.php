<?php
/*
Plugin Name: Goo.gl Url Shorter
Plugin URI: http://codecto.com/
Description: Goo.gl Url Shorter
Author: CodeCTO
Author URI: http://codecto.com/
Version: 1.0.1
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
	/*
	global $wpdb;
	$table_name = $wpdb->prefix.'gus';
	if($_POST){
		check_admin_referer('GUS_setting_save','GUS_setting_save_nonce');
	}
	$test = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE long_url = %s", array('http://admindeal.net/')));
	print_r($test);
	*/
	$GUS_list_table = new WP_GUS_List_Table();
	$pagenum = $GUS_list_table->get_pagenum();
	$GUS_list_table->process_bulk_action();
	$GUS_list_table->prepare_items();
	//$GUS_list_table->display();
	?>
<script language=javascript>
var api = '<?php echo site_url('/wp-admin/admin-ajax.php');?>';
function GUS_get_short_url(){
	var $ = jQuery;
	$.getJSON(api+'?callback=?',{
		action : 'GUS_api',
		title : $('#title').val(),
		long_url : $('#long_url').val()
		},function(data){
			$('#short_url').val(data.short_url);
			$('<tr class="new"><th class="check-column" scope="row"><input type="checkbox" value="" name="GUS[]"></th><td class="ID column-ID">'+data.ID+'</td><td class="title column-title">'+data.title+'</td><td class="long_url column-long_url">'+data.long_url+'</td><td class="short_url column-short_url">'+data.short_url+'</td><td class="operation column-operation"><div class="row-actions-visible"><span class="delete"><a href="/wp/wp-admin/options-general.php?page=GUS_option_page&action=delete&GUS[]='+data.ID+'&_wpnonce=862af5a6b6">Delete</a></span></div></td></tr>').insertBefore('#the-list tr:eq(0)');
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
				<label for="title">Title: </label>
			</th>
			<td>
				<input type="text" class="regular-text code" value="" id="title" name="title">
			</td>
		</tr>
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
<br />
<h3>Short URLs Manager</h3>
	<?php
	$GUS_list_table->display();
}

add_action('wp_ajax_GUS_api', 'GUS_api');
add_action('wp_ajax_nopriv_GUS_api', 'GUS_api');
function GUS_api(){
	if(!filter_var($_REQUEST['long_url'], FILTER_VALIDATE_URL)){
		$result = array(
			'error' => 'Please input a valid long url.',
		);
		if($_REQUEST['callback']){
			echo $_REQUEST['callback'].'('.json_encode($result).')';
		}else{
			echo json_encode($result);
		}
		die;
	}
	global $wpdb;
	$table_name = $wpdb->prefix.'gus';
	$title = $_REQUEST['title'];
	$long_url = $_REQUEST['long_url'];
	$find = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE long_url = %s", array($long_url)));
	if(!$find){
		$googl = new goo_gl($long_url);
		$short_url = $googl->result();
		$wpdb->insert($wpdb->prefix.'gus', array(
			'id' => null,
			'title' => $title,
			'long_url' => $long_url,
			'short_url' => $short_url,
		));
		$ID = $wpdb->insert_id;
	}else{
		$short_url = $find->short_url;
		$ID = $find->ID;
	}
	$result = array(
		'ID' => $ID,
		'title' => $title,
		'long_url' => $long_url,
		'short_url' => $short_url,
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
	$find = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE long_url = %s", array($content)));
	if(!$find){
		$googl = new goo_gl($content);
		$short_url = $googl->result();
		$wpdb->insert($wpdb->prefix.'gus', array(
			'id' => null,
			'title' => $title,
			'long_url' => $content,
			'short_url' => $short_url,
		));
	}else{
		$short_url = $find->short_url;
	}
	return '<a href="'.$short_url.'"'.($title?' title="'.$title.'"':'').' target="_blank">'.($context?$context:$content).'</a>';
}

include_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
class WP_GUS_List_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'GUS',     //singular name of the listed records
            'plural'    => 'GUS',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    function column_default($item, $column_name){
		 return $item[$column_name];
		 
        switch($column_name){
            case 'rating':
            case 'director':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
    function column_operation($item){

		$delete_url = add_query_arg(array(
			'action'=>'delete',
			$this->_args['singular'].'[]'=>$item['ID'],
			'_wpnonce'=>wp_create_nonce('bulk-' . $this->_args['plural'])
			),
			remove_query_arg( $this->_args['singular'].'[]' ) 
		);
		        
        //Build row actions
        $actions = array(
            //'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
            'delete'    => '<a href="'.$delete_url.'">Delete</a>'
        );
        
        //Return the title contents
        return $this->row_actions($actions,true);
    }
	
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
        );
    }
    
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'ID'     => 'ID',
            'title'     => 'Title',
            'long_url'     => 'Long URL',
            'short_url'    => 'Short URL',
			'operation' => 'Operations'
			
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'display_name'     => array('display_name',false),     //true means its already sorted
            'time'     => array('time',false),     //true means its already sorted
            'discount'     => array('discount',false),     //true means its already sorted
        );
        return $sortable_columns;
    }
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'delete'
        );
        return $actions;
    }
    
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
				check_admin_referer('bulk-' . $this->_args['plural']);
				$delete_redeem_codes = $_REQUEST['GUS'];
				if(is_array($delete_redeem_codes)){
					global $wpdb;
					foreach($delete_redeem_codes as $delete_redeem_code){
						$wpdb->query("delete from {$wpdb->prefix}gus where id=".(int)$delete_redeem_code);
					}
				}
				?>
				<div class="updated" id="message"><p>Delete Short URL successed!</p></div>
				<?php

        }
        
    }
    
    function prepare_items() {
        
        $per_page = 20;
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
		global $wpdb;
		$data_per_page = $per_page; //每页显示多少
		$thispage = $this->get_pagenum(); //当前第几页
		if(!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']) && array_key_exists($_REQUEST['orderby'],$this->get_sortable_columns()) && in_array($_REQUEST['order'],array('asc','desc'))){
			$query_sort = ' ORDER BY '.$_REQUEST['orderby'].' '.$_REQUEST['order'].' '; //排序方式
		}else
			$query_sort = ' ORDER BY id desc '; //排序方式
		$s_display_name = stripslashes_deep($_REQUEST['s_display_name']);
		$thispage = (int) ( 0 == $thispage ) ? 1 : $thispage;
		$first_data = ($thispage - 1) * $data_per_page;
		$query_limit = $wpdb->prepare(" LIMIT %d, %d", $first_data, $data_per_page);	
		if(isset($_REQUEST['status']))
			$where =  $wpdb->prepare(' where status=%d ',$_REQUEST['status']);
		else
			$where =  ' where 1=1 ';
		
		$args = array(); //下一页网页中附加的参数
		
		if($s_display_name){
			$where .=  $wpdb->prepare(" and {$wpdb->users}.display_name = %s ",$s_display_name);
		}
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS * 
from {$wpdb->prefix}gus";
		$data = $wpdb->get_results($sql.$where.$query_sort.$query_limit,ARRAY_A);
		//echo $sql.$where.$query_sort.$query_limit;
		
        $current_page = $this->get_pagenum();
        
        $total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
		return $data;
    }
}