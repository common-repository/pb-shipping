<?php
/*
Plugin Name: Enterprise Shipping for Pitney Bowes
Plugin URI: https://richardlerma.com/plugins/
Description: A streamlined shipping integration solution for WooCommerce and Pitney Bowes.
Author: RLDD
Author URI: https://richardlerma.com/contact/
Version: 5.0.11
Copyright: (c) 2020-2024 - rldd.net - All Rights Reserved
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 6.0
WC tested up to: 9.1
*/

global $espb_version; $espb_version='5.0.11';
if(!defined('ABSPATH')) exit;

function espb_error() {file_put_contents(dirname(__file__).'/install_log.txt', ob_get_contents());}
if(defined('WP_DEBUG') && true===WP_DEBUG) add_action('activated_plugin','espb_error');

function espb_activate($upgrade) {
  global $wpdb;
  global $espb_version;
  require_once(ABSPATH.basename(get_admin_url()).'/includes/upgrade.php');
  update_option('pb_db_version',$espb_version,'no');
  if(function_exists('pb_pro_ping'))espb_pro_ping(2);
}
register_activation_hook(__FILE__,'espb_activate');
function espb_shh() { ?><style type='text/css'>div.error{display:none!important}</style><?php }
if(espb_is_path(basename(admin_url('/plugins.php'))) && espb_is_path('plugin=pb-admin')) add_action('admin_head','espb_shh'); 

function espb_add_action_links($links) {
  $settings_url=admin_url('options-general.php?page=pb-admin');
  $support_url='https://richardlerma.com/plugins/';
  $links[]='<a href="'.$support_url.'">Support</a>';
  array_push($links,'<a href="'.$settings_url.'">Settings</a>');
  return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__),'espb_add_action_links');

function espb_uninstall() {
  $uninstall=get_option('pb_uninstall');
  if($uninstall=='delete') {espb_r("DELETE FROM wp_options WHERE option_name LIKE 'pb_%';");}
}
register_uninstall_hook(__FILE__,'espb_uninstall');

function espb_site_logo() {
  $white_label_url=get_option('pb_white_label_url');
  if(!empty($white_label_url)) return $white_label_url;
  $logo_id=get_theme_mod('custom_logo');
  if($logo_id>0) return wp_get_attachment_image_src($logo_id,'full')[0];
  else return get_site_icon_url();
}

function espb_adminMenu() {
  if(current_user_can('manage_options')) {
    include_once('pb_admin.php');
    add_options_page('Ship Admin - Pitney Bowes','PB Ship Admin','manage_options','pb-admin','espb_admin');
    add_submenu_page('woocommerce','Ship Admin - Pitney Bowes','PB Ship Admin','manage_options','pb-admin','espb_admin');
  }
  if(current_user_can('edit_shop_orders')) {
    if(espb_is_path('page=pb-ship,page=pb-report,page=pb-queue')) add_action('admin_init','espb_admin_popup');
    if(current_user_can('view_woocommerce_reports')) {
      include_once('pb_report.php');
      add_submenu_page('woocommerce','Ship Reports - Pitney Bowes','Ship Reports','view_woocommerce_reports','pb-reports','espb_reports');
    }
    include_once('pb_queue.php');
    add_submenu_page('woocommerce','Ship Queue - Pitney Bowes','Ship Queue','edit_shop_orders','pb-queue','espb_queue');
    include_once('pb_ship.php');
    add_submenu_page('woocommerce','Ship Order - Pitney Bowes','Ship Order','edit_shop_orders','pb-ship','espb_ship_order');
  }
}
add_action('admin_menu','espb_adminMenu');

function espb_admin_popup(){
  wp_deregister_style('wp-admin');
}

function espb_open_new_tab() { ?>
  <script type="text/javascript">
    function pb_open_new_tab() {
      a=document.getElementsByTagName("A");
      for(i=0; i<a.length; i++) {
        if(a[i].href.indexOf('?page=pb-report')>0 || a[i].href.indexOf('?page=pb-queue')>0) if(a[i].href.indexOf('&report_type')<0) a[i].setAttribute('target','_blank');
        if(a[i].href.indexOf('?page=pb-ship')>0) a[i].style.display='none';
      }
    }
    setTimeout(function(){pb_open_new_tab()},1000);
  </script><?php  
}
add_action('admin_footer','espb_open_new_tab');

function espb_is_path($pages) {
  $page=strtolower($_SERVER['REQUEST_URI']);
  return espb_in_like($page,$pages);
}

function espb_in_like($n,$h) {
  if(!is_array($h)) $h=explode(',',$h);
  if(is_array($n)) $n=reset($n);
  foreach($h as $item) {
    if(!empty($item)) if(stripos($n,$item)!==false) return true;
  }
  return false;
}

function espb_usermeta($uid=0,$meta='ID') {
  require_once(ABSPATH.WPINC.'/pluggable.php'); // If prior to pluggable loaded natively
  if($meta=='ID') $meta_value=0; else $meta_value='';
  if(is_user_logged_in()) {
    if($uid>0 && $meta=='ID') return $uid;
    if(!$uid>0) {
      $user=wp_get_current_user();
      $uid=$user->ID;
    }
    $user_info=get_userdata($uid);
    $meta_value=$user_info->$meta;
  }
  return $meta_value;
}

function espb_r($q,$t=NULL) {
  global $wp_version;
  if(function_exists('r')) return r($q,$t);
  include_once(ABSPATH.'wp-includes/pluggable.php');
  if(version_compare('6.1',$wp_version)>0) require_once(ABSPATH.'wp-includes/wp-db.php');
  else require_once(ABSPATH.'wp-includes/class-wpdb.php');
  
  global $wpdb;
  if(!$wpdb) $wpdb=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
  $prf=$wpdb->prefix;
  $s=str_replace(' wp_',' '.$prf,$q);
  $s=str_replace($prf.str_replace('wp_','',$prf),$prf,$s);
  $r=$wpdb->get_results($s,OBJECT);
  if($r) return $r;
}

add_action('before_woocommerce_init',function() {
	if(class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class )) {\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables',__FILE__,true);}
});

function espb_auth($env='sandbox') {
  if($env=='prod' && !empty(get_option('pb_production_key'))) {$env='production';$env_url='';} else {$env='sandbox';$env_url="-$env";}
  $result=get_transient("pb_{$env}_auth");
  if(empty($result)) {
    $key=get_option("pb_{$env}_key");
    if(empty($key)) return;
    $secret=get_option("pb_{$env}_secret");
    $token=base64_encode("$key:$secret");
    $curl=curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL=>"https://shipping-api{$env_url}.pitneybowes.com/oauth/token",
      CURLOPT_RETURNTRANSFER=>true,CURLOPT_ENCODING=>"",CURLOPT_MAXREDIRS=>10,CURLOPT_TIMEOUT=>0,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,CURLOPT_CUSTOMREQUEST=>"POST",CURLOPT_POSTFIELDS=>"grant_type=client_credentials",
      CURLOPT_HTTPHEADER=>array("Authorization: Basic $token","Content-Type: application/x-www-form-urlencoded"),
    ));
    $result=curl_exec($curl);
    if(stripos($result,'error')==false) set_transient("pb_{$env}_auth",$result,'32400');
  }
  $result=json_decode($result,true);
  return $result['access_token'];
}

function espb_call($url,$post_fields,$cache_key='',$env='sandbox',$carrier='') {
  if(empty($url)) return 'error: url missing';
  $result=$env_url=$trxid=$carrier_account_id='';
  if(!empty($cache_key) && espb_troubleshoot_mode()<1) $result=get_transient("pb_{$url}_{$env}_{$cache_key}");
  if(empty($result)) {
    if($env!='prod') $env_url="-$env";
    $access_token=espb_auth($env);
    if(empty($access_token)) return array("Error Response"=>ucwords($env)." environment not configured.");
    if(espb_in_like($url,'shipment,manifest,pickup,carrier-account')) $trxid="X-PB-TransactionId: ".strtoupper(preg_replace('/[^A-Za-z0-9]/','',bin2hex(random_bytes(12))));
    if(espb_in_like($carrier,'FEDEX,UPS')) if(espb_in_like($url,'shipment,rates')) $carrier_account_id="X-PB-Shipper-Carrier-AccountId: ".get_option("pb_carrier_{$carrier}_{$env}_id");
    $curl=curl_init();
    $curl_options=array(
      CURLOPT_URL=>"https://shipping-api{$env_url}.pitneybowes.com/shippingservices/v1/$url",
      CURLOPT_RETURNTRANSFER=>true,CURLOPT_ENCODING=>"",CURLOPT_MAXREDIRS=>10,CURLOPT_TIMEOUT=>0,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER=>array("Authorization: Bearer $access_token","Content-Type: application/json","X-PB-UnifiedErrorStructure: true","$trxid","$carrier_account_id"),
      curl_setopt($curl,CURLINFO_HEADER_OUT,true)
    );
    curl_setopt_array($curl,$curl_options);
    if(!empty($post_fields)) {
      curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"POST");
      curl_setopt($curl,CURLOPT_POSTFIELDS,$post_fields);
    }
    elseif(stripos($url,'cancelInitiator')!==false) curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"DELETE");
    $result=curl_exec($curl);
    $info=curl_getinfo($curl);
    curl_close($curl);
    if(isset($info['request_header'])) set_transient("request_header_$url",$info['request_header'],5);
    $cache_seconds=180;
    if(espb_in_like($url,'shipment') && !espb_in_like($url,'cancel')) $cache_seconds=5;
    if(!empty($cache_key) && !empty($result) && stripos($result,'error')==false && espb_troubleshoot_mode()<1) set_transient("pb_{$url}_{$env}_{$cache_key}",$result,$cache_seconds);
    elseif(empty($result)) return 'error: no response';
  }
  return json_decode($result,true);
}

function espb_auth_merchant($d,$pkey,$psec,$mname,$mpw) {
  global $espb_version;
  $env='prod';
  $err='';
  if(empty($pkey) || empty($psec)) {
    $url=urlencode(str_replace('://','',str_replace('www.','',str_replace('http','',str_replace('https','',get_bloginfo('wpurl'))))));
    $uip=sanitize_text_field($_SERVER['REMOTE_ADDR']);
    $source="https://".convert_uudecode("0<FEC:&%R9&QE<FUA+F-O;0``")."/r1cm/?pb=lU3rO3AhdlaqZs2&update=1&f=$d&v=$espb_version&co=$url&u=$mname&ip=$uip";
    $raw=@file_get_contents($source,false,stream_context_create(['http'=>['header'=>'User-Agent: RLDD-wp/1.0\r\n']]));
    if(empty($raw)) $err.="&#9888; Temporarily unable to authorize, please try again later.<br>";
    if(!empty($raw)) {
      if(strpos($raw,"&#")===0) $err=$raw;
      else $data=@gzuncompress(str_rot13($raw));
    }
    if(!empty($err)) return $err;
    $data=explode(',',$data);
    if(isset($data[0])) $pkey=$data[0];
    if(isset($data[1])) $psec=$data[1];
    if(!empty($pkey) && !empty($psec)) {
      update_option('pb_production_key',$pkey,'no');
      update_option('pb_production_secret',$psec,'no');
    }
  }

  if(!empty($pkey) && !empty($psec)) {
    $m=new stdClass();
    $m->username=$mname;
    $m->password=$mpw;
    $m=json_encode($m);
    $result=espb_call("developers/$d/merchants/credentials",$m,'pb_auth_merchant',$env);

    $err=espb_is_err($result,$m); if(!empty($err)) return $err;
    if(is_array($result) || is_object($result)) {
      foreach($result as $k=>$v) if(!is_array($v)) {$k=substr($k,0,3); ${$k}=$v;}
    }
    else return $result;
    if(!empty($d) && !empty($pos)) {
      update_option('pb_developer_id',$d,'no');
      update_option('pb_merchant_id',$pos,'no');
      return "Merchant $pos Authenticated Successfully.";
    }
  }
  return "&#9888; Temporarily unable to authorize, please try again later.<br>";
}

function espb_merchants($d,$env) {
  if(!$d>0) return;
  $result=espb_call("developers/$d/merchants",'','pb_shipper_id',$env);
  $postalReportingNumbers=array();
  if($result) foreach($result as $value) {
    if(is_array($value)) foreach($value as $val) {
      if(is_array($val)) foreach($val as $k=>$v) {
        if(!is_array($v)) ${$k}=$v;
      }
      array_push($postalReportingNumbers,$postalReportingNumber);
    }
  }
  return $postalReportingNumbers;
}


function espb_selected($option,$value) {
  if($option==$value) return 'selected';
}

function espb_list_carrier($sel_carrier) {
  $carrier=array(array('USPS','USPS'),array('NEWGISTICS','PB Standard'),array('FEDEX','FEDEX'),array('UPS','UPS'));
  $enabled_carriers=get_option('pb_enabled_carriers');
  $list='';
  $i=0;
  foreach($carrier as $c) {
    $dis='';
    if(isset($enabled_carriers[$i])) if($enabled_carriers[$i]<1) $dis='disabled';
    if($c[0]==$sel_carrier) $sel='selected'; else $sel='';
    $list.="<option value='{$c[0]}' $sel $dis>{$c[1]}";
    $i++;
  }
  return $list;
}

function espb_list_facility($sel_facility,$all=0) {
  $fname=get_option('pb_client_facility_name');
  $factive=get_option('pb_client_facility_active');
  $list='';
  $i=0;
  if(!empty($fname)) foreach($fname as $f) {
    if($all>0 || !empty($factive[$i])) {
      if($i==$sel_facility) $sel='selected'; else $sel='';
      $list.="<option value='$i' $sel>$f";
    }
    $i++;
  }
  return $list;
}

function espb_list_services($carrier,$sel_service,$induction_zip='') {
  if(empty($carrier) || empty(get_option('pb_sandbox_key'))) return "<option selected value=''>Save to load options";
  if(empty(get_option('pb_merchant_id'))) $env='sandbox'; else $env='prod';

  $user_id=get_current_user_id();
  $facility=get_option("pb_origin_facility"); if(empty($facility)) $facility=0;
  $fromAddress=espb_create_obj_fromAddress($facility);
  $toAddress=espb_create_obj_toAddress('123 Main St','','Houston','TX','77002','US','',"Test User",'713-123-0000',get_option('admin_email'));
  $parcel=espb_create_obj_parcel_quote('15',8,5,4,'',9);
  if($carrier!='USPS') $parcelType='PKG'; else $parcelType='';

  $shipmentOptions=new stdClass();
  $shipper_id=get_option('pb_merchant_id'); if(empty($shipper_id) || $env=='sandbox') $shipper_id=get_option('pb_sandbox_merchant_id');
  $client_id=get_option('pb_client_id')[$facility];
  $client_facility_id=get_option('pb_client_facility_id')[$facility];
  $carrier_facility_id=get_option('pb_carrier_facility_id')[$facility];

  if($carrier=='NEWGISTICS') { // Assign Facility
    $shipmentOptions=[array('name'=>'CLIENT_FACILITY_ID','value'=>$client_facility_id),array('name'=>'CARRIER_FACILITY_ID','value'=>$carrier_facility_id),array('name'=>'CLIENT_ID','value'=>$client_id),array('name'=>'SHIPPER_ID','value'=>$shipper_id)];
    $serviceid='PRCLSEL';
    $rates=espb_create_obj_rates($carrier,$serviceid,$parcelType,'','50',$induction_zip);
  }
  elseif($carrier!='USPS') {
    $shipmentOptions=[array('name'=>'SHIPPER_ID','value'=>$shipper_id)];
    $rates=espb_create_obj_rates($carrier,'',$parcelType,'','50',$induction_zip);
  }
  else {
    $shipmentOptions=[array('name'=>'SHIPPER_ID','value'=>$shipper_id)];
    $rates=espb_create_obj_rates($carrier,'',$parcelType,'','50',$induction_zip);
  }
  $shipment=new stdClass();
  $shipment->fromAddress=$fromAddress;
  $shipment->toAddress=$toAddress;
  $shipment->parcel=$parcel;
  $shipment->rates=[$rates];
  $shipment->shipmentOptions=$shipmentOptions;
  $shipment=json_encode($shipment);
  $result=espb_call('rates',$shipment,"setup-$carrier",$env,$carrier);
  $error=espb_is_err($result,$shipment);
  if(!empty($error)) echo $error;

  $list='';
  if($result) foreach($result as $key=>$value) {
    $parcelType=$retail='';
    if($key=='rates') {
      foreach($value as $k=>$v) {
        foreach($v as $n=>$d) if(!is_array($d)) {
          if(!espb_in_like($n,'serviceid,parcel,charge')) continue;
          ${$n}=$d;
        }
        if(empty($parcelType) && $carrier!='USPS') $parcelType='PKG';
        if(!empty($retail) && $retail=="$serviceId-$parcelType") continue;
        $retail="$serviceId-$parcelType";
        if($sel_service=="$serviceId-$parcelType") $sel='selected'; else $sel='';
        $totalCarrierCharge=number_format($totalCarrierCharge,2);
        $list.="<option $sel value='$serviceId-$parcelType'>$serviceId-$parcelType* ($$totalCarrierCharge)";
      }
    }
  }
  return $list;
}

function espb_get_methods($methods,$sel_method) {
  $list='';
  if(!empty($methods)) {
    foreach($methods as $m) {
      $zone=ucwords($m->zone);
      $method_id=$m->method_id;
      $method="$method_id:{$m->instance_id}";
      $method_title=unserialize($m->meta)['title'];
      $sel='';
      if($sel_method==$method) $sel='selected'; else if($m->active<1) $sel='disabled';
      if($method_title=='Default') $method_title=ucwords(str_replace('_',' ',$method_id));
      $list.="<option $sel value='$method'>$zone - $method_title";
    }
  }
  return $list;
}

function espb_cart_rate($rates,$package) {
  if(empty(get_option('pb_cart_quote'))) return $rates;
  $restrict_user=get_option('pb_restrict');
  if(!empty($restrict_user) && espb_usermeta(0,'ID')!=$restrict_user) return $rates;
  $wc_method=get_option('pb_wc_method');

  $i=$m_ct=$new_rate=0;
  if(is_array($wc_method)) $m_ct=count($wc_method);
  if($m_ct>0) {
    global $woocommerce;
    $cust=$woocommerce->customer;
    $first_name=$cust->get_shipping_first_name();
    $last_name=$cust->get_shipping_last_name();
    $company=$cust->get_shipping_company();
    $address1=$cust->get_shipping_address_1();
    $address2=$cust->get_shipping_address_2();
    $city=$cust->get_shipping_city();
    $state=$cust->get_shipping_state();
    $zip=$cust->get_shipping_postcode();
    $country=$cust->get_shipping_country();
    $email=$cust->get_billing_email();
    $phone=$cust->get_billing_phone();
    $toAddress=espb_create_obj_toAddress($address1,$address2,$city,$state,$zip,$country,$company,"$first_name $last_name",$phone,$email);
    
    $facility=get_option("pb_origin_facility"); if(empty($facility)) $facility=0;
    $fromAddress=espb_create_obj_fromAddress($facility);
    $shipment=new stdClass();
    $shipment->fromAddress=$fromAddress;
    $shipment->toAddress=$toAddress;

    $cart=$woocommerce->cart;
    $cart_total=$woocommerce->cart->subtotal;
    $weight=$cart->cart_contents_weight;
    $length=$width=$height=$vol=.1;
    
    foreach($cart->cart_contents as $cart_item) {
      $p=$cart_item['data'];
      $qty=$cart_item['quantity'];
      $dim=array($p->get_length(),$p->get_width(),$p->get_height());
      $vol+=pb_dim_to_vol($dim,$qty); 
    }
    $width=number_format(sqrt($vol));
    $length=number_format($width);
    $height=number_format($width/2);
    $weight+=pb_box_weight($vol);
    $weight=number_format($weight,2);

    $adj_ins_value=$cart_total;
    if($cart_total<101) $adj_ins_value=101;
    $parcel=espb_create_obj_parcel($weight,$length,$width,$height,'',$cart_total);
    $shipment->parcel=$parcel;

    while($i<$m_ct) {
      $wc_method_i=$wc_method[$i];
      if(!isset($rates[$wc_method_i])) {$i++;continue;}

      $m_array=get_option("pb_wc_method_$wc_method_i");
      $carrier_i=pb_array_idx($m_array,0);
      $service_i=pb_array_idx($m_array,1);
      if(isset($m_array[2])) $formula_i=$m_array[2]; else $formula_i='';
      if(isset($m_array[3])) $cart_apply_i=$m_array[3]; else $cart_apply_i=0;
      $service_array=explode('-',$service_i);
      $sel_service=pb_array_idx($service_array,0);
      $sel_parcel=pb_array_idx($service_array,1);
      if($carrier_i=='USPS' && ($sel_service=='FCM'||$sel_service=='PM')) $services='DelCon,'; else $services='';
      if($cart_apply_i>0 && empty($city) && empty($zip)) $shipment->toAddress=$fromAddress;

      $rate=espb_create_obj_rates($carrier_i,$sel_service,$sel_parcel,$services,$adj_ins_value);
      $shipment->rates=[$rate];
      $cache_key="$carrier_i-$service_i-$length-x-$width-x-$height-$weight-$city-$state-$zip";
      $sel_rate=espb_single_rate($carrier_i,$service_i,$shipment,$cache_key);

      if(!empty($sel_rate)) {
        if(!empty($formula_i)) $new_rate=pb_cost_formula($formula_i,$sel_rate,$cart_total,$qty,$weight);
        if(espb_troubleshoot_mode()>0) update_option('pb_cart_rate_troubleshoot',"Method:{$rates[$wc_method_i]->cost}, Live:$sel_rate, Formula: $new_rate=$formula_i, $cache_key");
        if(!empty($new_rate)) $sel_rate=$new_rate;
        $rates[$wc_method_i]->cost=$sel_rate; 
      }
      $i++;
    }
  }
  return $rates;
}
add_filter('woocommerce_package_rates','espb_cart_rate',10,2);
add_filter('transient_shipping-transient-version',function($value,$name){return false;},10,2);

function pb_array_idx($array,$index,$default='') {
  return isset($array[$index])?$array[$index]:$default;
}

function pb_box_weight($vol) {
  if($vol>0) return number_format($vol*.0025,2);
  return 0;
}

function pb_dim_to_vol($dim,$qty) {
  sort($dim,SORT_NUMERIC);
  if(!empty($dim[0])) {
    $iwidth=number_format($dim[0],1);
    $iheight=number_format($dim[1],1);
    $ilength=number_format($dim[2],1);
    $vol=($ilength*$iwidth*$iheight)*$qty;
    return $vol;
  }
}

function pb_cost_formula($formula,$ship_cost,$cart_value,$cart_qty,$cart_oz) {
  if(empty($formula)) return;
  if(is_numeric($formula)) return $formula;
  if(is_string($formula)) {
    $formula=preg_replace('/<[^>]+>/','',$formula);
    $formula=preg_replace('/\[|\]/','',$formula);
    $formula=preg_replace('/ship_cost/',$ship_cost,$formula);
    $formula=preg_replace('/cart_value/',$cart_value,$formula);
    $formula=preg_replace('/cart_qty/',$cart_qty,$formula);
    $formula=preg_replace('/cart_oz/',$cart_oz,$formula);
    $new_rate=eval("return $formula;");
    if($new_rate) return number_format($new_rate,2);
  }
  return 0;
}


function espb_single_rate($carrier,$sel_service,$shipment,$cache_key) {
  if(empty(get_option('pb_sandbox_key'))) return;
  if(empty(get_option('pb_production_key'))) $env='sandbox'; else $env='prod';

  $shipment=json_encode($shipment);
  $result=espb_call('rates',$shipment,"cart_$cache_key",$env,$carrier);
  
  $error=espb_is_err($result,$shipment);
  if(espb_troubleshoot_mode()>0) update_option('pb_single_rate_troubleshoot',"$cache_key: $error");

  $list='';
  if($result) foreach($result as $key=>$value) {
    if($key=='rates') {
      foreach($value as $k=>$v) {
        foreach($v as $n=>$d) if(!is_array($d)) {
          if(!espb_in_like($n,'serviceid,parcel,charge')) continue;
          ${$n}=$d;
        }
        if($sel_service=="$serviceId-$parcelType") return "$totalCarrierCharge";
      }
    }
  }
}

add_action('woocommerce_checkout_order_processed','espb_save_shipping_method',10,2);
function espb_save_shipping_method($order_id) {
  if(!$order_id>0) return;
  $order=wc_get_order($order_id);
  if(!$order) return;
  $method=@array_shift($order->get_shipping_methods());
  $method_id=$method['method_id'];
  $instance_id=$method['instance_id'];
  @update_post_meta($order_id,'_wc_method_id',"$method_id:$instance_id");
}

function espb_create_obj_fromAddress($i=0,$account_name='') {
  $client_facility_name=get_option('pb_client_facility_name');
  $client_facility_type=get_option('pb_client_facility_type');
  $client_facility_address=get_option('pb_client_facility_address');
  $client_facility_address2=get_option('pb_client_facility_address2');
  $client_facility_city=get_option('pb_client_facility_city');
  $client_facility_state=get_option('pb_client_facility_state');
  $client_facility_zip=get_option('pb_client_facility_zip');
  $client_facility_country=get_option('pb_client_facility_country');
  $client_facility_tel=get_option('pb_client_facility_tel');
  $client_facility_email=get_option('pb_client_facility_email');

  $f=new stdClass();
  $company=get_option('blogname');
  if(isset($client_facility_name[$i])) {
    if(!empty($account_name)) {$f->name=$account_name;$f->company=$company;}
    else {if(stripos($client_facility_name[$i],$company)!==false || stripos($company,$client_facility_name[$i])!==false) $f->name=$company; else {$f->company=$company;$f->name=$client_facility_name[$i];}}
    if(!empty($client_facility_address2[$i])) $f->addressLines=array($client_facility_address[$i],$client_facility_address2[$i]); else $f->addressLines=array($client_facility_address[$i]);
    $f->cityTown=$client_facility_city[$i];
    $f->stateProvince=$client_facility_state[$i];
    $f->postalCode=$client_facility_zip[$i];
    $f->countryCode=$client_facility_country[$i];
    $f->phone=$client_facility_tel[$i];
    $f->email=$client_facility_email[$i];
    if($client_facility_type[$i]>0) $f->residential=true; else $f->residential=false;
  }
  return $f;
}

function espb_create_obj_toAddress($a1,$a2,$cy,$st,$zp,$cr,$cm,$s2,$ph,$em) {
  $t=new stdClass();
  if(!empty($a2)) $t->addressLines=array($a1,$a2); else $t->addressLines=array($a1);
  $t->cityTown=$cy;
  $t->stateProvince=$st;
  $t->postalCode=$zp;
  $t->countryCode=$cr;
  $t->company=$cm;
  $t->name=$s2;
  $t->phone=$ph;
  $t->email=$em;
  //$t->residential=true;
  return $t;
}

function espb_create_obj_parcel($wt,$l,$w,$h,$s,$i) {
  $p=new stdClass();
  $p->weight=new stdClass();
  $p->dimension=new stdClass();
  
  $weight_unit=get_option('woocommerce_weight_unit');
  $p->weight->weight=($weight_unit!=='oz') ? number_format(wc_get_weight($wt,'oz',$weight_unit),2,'.',''):number_format($wt,2,'.','');
  $p->weight->unitOfMeasurement='OZ';

  $dim_unit=get_option('woocommerce_dimension_unit');
  $p->dimension->length=($dim_unit!=='in') ? max(1,intval(wc_get_dimension($l,'in',$dim_unit))):max(1,intval($l));
  $p->dimension->width=($dim_unit!=='in') ? max(1,intval(wc_get_dimension($w,'in',$dim_unit))):max(1,intval($w));
  $p->dimension->height=($dim_unit!=='in') ? max(1,intval(wc_get_dimension($h,'in',$dim_unit))):max(1,intval($h));
  $p->dimension->unitOfMeasurement='IN';

  if(stripos($s,'Ins,')!==false) {
    $p->valueOfGoods=number_format($i,wc_get_price_decimals());
    $p->currencyCode='USD';
  }
  return $p;
}


function espb_create_obj_parcel_quote($wt,$l,$w,$h,$s,$i) {
  $p=new stdClass();
  $p->weight=new stdClass();
  $p->weight->weight=$wt;
  $p->weight->unitOfMeasurement='OZ';
  $p->dimension=new stdClass();
  $p->dimension->length=$l;
  $p->dimension->width=$w;
  $p->dimension->height=$h;
  $p->dimension->unitOfMeasurement='IN';
  if(stripos($s,'Ins,')!==false) {
    $p->valueOfGoods=$i;
    $p->currencyCode='USD';
  }
  return $p;
}

function espb_create_obj_rates($carrier,$serviceid='',$parcel='',$special_services='',$ins=10,$induction_zip='') {
  $services_array=array();
  if(!empty($special_services)) {
    $ss_array=explode(',',$special_services);
    foreach($ss_array as $spc_svc) {
      if(empty($spc_svc)) continue;
      $svc_val=0;
      if($spc_svc=='Ins') $svc_val=$ins;
      array_push($services_array,array('specialServiceId'=>"$spc_svc",'inputParameters'=>[array('name'=>'INPUT_VALUE','value'=>$svc_val)]));
    }
  }
  $r=new stdClass();
  $r=array('carrier'=>$carrier);
  if(!empty($serviceid)) $r['serviceId']=$serviceid;
  if(!empty($parcel)) $r['parcelType']=$parcel;
  if(!empty($special_services)) $r['specialServices']=$services_array;
  if(!empty($induction_zip)) $r['inductionPostalCode']=$induction_zip;
  return $r;
}

function espb_admin_notice() {
  if(!espb_is_path('wp-admin/plugins.php,page=wc-admin,post_type=shop_order')) return;
  require_once(ABSPATH."wp-includes/pluggable.php");
  if(current_user_can('manage_options')) {
    $settings_url=admin_url('options-general.php?page=pb-admin');?>
    <div class="notice notice-success is-dismissible" style='margin:0;'>
      <p><?php _e("The <em>Enterprise Shipping</em> plugin is active, but is not yet configured. Visit the <a href='$settings_url'>configuration page</a> to complete setup.",'Enterprise Shipping');?>
    </div><?php
  }
}

function espb_checkConfig() {
  if(empty(get_option('pb_developer_id'))) add_action('admin_notices','espb_admin_notice');
}
add_action('admin_init','espb_checkConfig');


if(espb_is_path('admin.php?page=wc-orders,edit.php?post_type=shop_order,post.php?post')) {
  function espb_ship_button() {
    if(!current_user_can('edit_shop_orders')) return;
    if(!current_user_can('administrator') && empty(get_option('pb_merchant_id'))) return;
    $restrict_user=get_option('pb_restrict');
    if(!empty($restrict_user) && espb_usermeta(0,'ID')!=$restrict_user) return;
    if(isset($_GET['post']) || espb_is_path('page=wc-orders&action=edit&id')) {
      $post_id=$post_type='';
      if(isset($_GET['post'])) {
        $post_id=intval($_GET['post']);
        $post_type=get_post($_GET['post'])->post_type;
      }
      if(empty($post_id) && isset($_GET['id'])) {$post_id=intval($_GET['id']); $post_type='shop_order';}
      if($post_type=='shop_order') {
      ?>
      <script type='text/javascript'>
        setTimeout(function(){load_pb_shipping();},500);
        function load_pb_shipping() {
          if(!document.getElementById('actions')) return;
          var actions=document.getElementById('actions');
          var pb_ship=document.createElement('li');
          pb_ship.className='wide';
          actions.parentElement.appendChild(pb_ship);
          pb_ship.innerHTML+="<input type='button' class='button' value='Ship' style='width:100%;margin:1em 0' onclick='open_pb_shipping()'>";
        }
        function open_pb_shipping() {window.open('<?php echo admin_url('admin.php?page=pb-ship');?>&order_id=<?php echo $post_id;?>','Ship','width=780,height=1005,resizable=yes,scrollbars=yes');}
      </script><?php 
    }} else {
      if(isset($_GET['post_type'])) $post_type=$_GET['post_type'];
      elseif(espb_is_path('admin.php?page=wc-orders')) $post_type='shop_order';
      if($post_type=='shop_order') {
        ?>
        <style>.pbship,.pbtrack{padding:0 .5em;color:#0173aa;margin-left:.5em;background:transparent;border:1px solid #ccc}.pbtrack{border:none}</style>
        <script type='text/javascript'>
          setTimeout(function(){load_pb_shipping();},500);
          function load_pb_shipping() {
            var order_list=document.querySelectorAll('[data-order-id]');
            for(i=0; i<order_list.length; i++) {
              parent_html=order_list[i].parentElement.parentElement.innerHTML;
              var completed=parent_html.indexOf('status-completed');
              var processing=parent_html.indexOf('status-processing');
              if(completed+processing<1) continue;
              var btn_name='Ship';
              var btn_class='pbship';
              if(completed>0) {btn_name='Shipped';btn_class='pbtrack';}
              order_id=order_list[i].getAttribute('data-order-id');
              pb_ship=document.createElement('a');
              pb_ship.className=btn_class+' order-status';
              pb_ship.setAttribute('order-id',order_id);
              order_list[i].parentElement.nextElementSibling.append(pb_ship);
              pb_ship.onclick=function(){open_pb_shipping(this)};
              pb_ship.innerHTML=btn_name;
            }
          }
          function open_pb_shipping(btn){if(btn.parentElement.parentElement.innerHTML.indexOf('status-completed')>0) override='&override=1';else override='';btn.style.opacity='.3';order_id=btn.getAttribute('order-id');window.open('<?php echo admin_url('admin.php?page=pb-ship&order_id=');?>'+order_id+override,'Ship','width=780,height=1005,resizable=yes,scrollbars=yes');}
        </script><?php 
      }
    }
  }
  add_action('admin_footer','espb_ship_button');
}


function espb_store_data() {
  $store=espb_r("
    SELECT n.option_value store_name
    ,(SELECT option_value FROM wp_options WHERE option_name='woocommerce_store_address')address
    ,(SELECT option_value FROM wp_options WHERE option_name='woocommerce_store_address_2')address_2
    ,(SELECT option_value FROM wp_options WHERE option_name='woocommerce_store_city')city
    ,(SELECT option_value FROM wp_options WHERE option_name='woocommerce_default_country')ctry_state
    ,(SELECT option_value FROM wp_options WHERE option_name='woocommerce_store_postcode')zip
    ,(SELECT option_value FROM wp_options WHERE option_name='woocommerce_store_tel')tel
    ,IFNULL(NULLIF((SELECT option_value FROM wp_options WHERE option_name='woocommerce_email_from_address'),''),(SELECT option_value FROM wp_options WHERE option_name='admin_email'))email
    FROM wp_options n
    WHERE option_name='blogname'
  ;");
  if($store) return $store;
}

function espb_is_hpos() {
  if(class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
    $orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
    if($orderUtil->custom_orders_table_usage_is_enabled()) return 1;
    else return 0;
  } else return 0;
}

function espb_order_data($order_id) {
  if(!$order_id>0) return;
  $hpos=0;
  $post_type=get_post_type($order_id);
  $product_lookup='wp_wc_order_product_lookup';
  if($post_type=='import_order') $product_lookup='wp_import_order_product_lookup';
  $weight_unit=get_option('woocommerce_weight_unit');
  
  if($post_type='shop_order' && espb_is_hpos()>0)
    $order=espb_r("
      SELECT o.ID order_id
      ,'shop_order'as order_type
      ,(SELECT DATE_FORMAT(meta_value,'%b %e, %Y %r') FROM wp_postmeta WHERE post_id=o.ID AND meta_key IN ('_last_shipped') ORDER BY 1 DESC LIMIT 1)shipped
      ,(SELECT GROUP_CONCAT(DISTINCT REPLACE(meta_key,'_pb_label_meta_','')) FROM wp_postmeta WHERE post_id=o.ID AND meta_key LIKE '_pb_label_meta_%' AND LENGTH(meta_value)>6)label_history
      ,LEFT(o.date_created_gmt,10)date_created
      ,REPLACE(GROUP_CONCAT(DISTINCT CONCAT('<li class=\'itm\' id=\'',p.ID,'x',po.product_qty+IFNULL(pr.product_qty,0),'\' onclick=\'toggle_item(this);\' title=\'',IFNULL(a.length,0),'x',IFNULL(a.width,0),'x',IFNULL(a.height,0),'\\n',a.weight,'$weight_unit','\'>',po.product_qty+IFNULL(pr.product_qty,0),' x ',p.post_title) SEPARATOR ';'),';','')product_list
      ,GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(po.variation_id,0),po.product_id),'x',po.product_qty+IFNULL(pr.product_qty,0)))variation_cache
      ,GROUP_CONCAT(CONCAT(po.product_id,'x',po.product_qty+IFNULL(pr.product_qty,0)))product_cache
      ,total_amount total
      ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='shipping' LIMIT 1),(SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_id=o.ID AND order_item_type='shipping' LIMIT 1))method
      ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_wc_method_id' LIMIT 1)method_id
      ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='ship_cost' LIMIT 1),(SELECT im.meta_value FROM wp_woocommerce_order_items i JOIN wp_woocommerce_order_itemmeta im ON im.order_item_id=i.order_item_id WHERE i.order_id=o.ID AND i.order_item_type='shipping' AND im.meta_key='cost' LIMIT 1))method_cost
      ,TRIM(CONCAT(IFNULL(CONCAT(a.first_name,' '),''),IFNULL(a.last_name,'')))shipto
      ,a.company
      ,a.address_1 address1
      ,a.address_2 address2
      ,a.city
      ,a.state
      ,a.postcode zip
      ,a.country
      ,billing_email email
      ,(SELECT meta_value FROM wp_wc_orders_meta WHERE order_id=o.ID AND meta_key='_billing_phone' LIMIT 1)phone
      ,(SELECT CONCAT(IFNULL(GROUP_CONCAT(CONCAT(DATE_FORMAT(comment_date,'%b %e, %Y %r'),' ',comment_author,': ','<b>',comment_content,'</b>') ORDER BY comment_ID DESC SEPARATOR '|'),''),IFNULL(CONCAT(DATE_FORMAT(o.date_created_gmt,'%b %e, %Y %r'),' Customer: ','<b>',NULLIF(o.customer_note,'')),''),'</b>') FROM wp_comments WHERE comment_post_id=o.ID AND comment_type='order_note' AND comment_author!='WooCommerce' ORDER BY comment_ID DESC LIMIT 3) comments
      FROM wp_wc_orders o
      LEFT JOIN wp_wc_order_addresses a ON a.order_id=o.ID AND a.address_type='shipping'
      JOIN wp_wc_order_product_lookup po ON po.order_id=o.ID
      LEFT JOIN (
        SELECT r.post_parent order_id
        ,IFNULL(NULLIF(rp.variation_id,0),rp.product_id)variation_id
        ,rp.product_qty
        FROM wp_posts r
        JOIN wp_wc_order_product_lookup rp ON rp.order_id=r.ID
        WHERE r.post_parent=$order_id AND r.post_type='shop_order_refund'
      )pr ON pr.order_id=o.ID AND pr.variation_id=IFNULL(NULLIF(po.variation_id,0),po.product_id)
      LEFT JOIN (
        SELECT IFNULL(NULLIF(po.variation_id,0),po.product_id) product_id
        ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_weight'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_weight'))weight
        ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_length'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_length'))length
        ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_width'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_width'))width
        ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_height'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_height'))height
        FROM wp_wc_order_product_lookup po
        WHERE po.order_id=2985
      )a ON a.product_id=IFNULL(NULLIF(po.variation_id,0),po.product_id)
      LEFT JOIN wp_posts p ON p.ID=IFNULL(NULLIF(po.variation_id,0),po.product_id)
      WHERE o.status IN ('wc-processing','wc-completed')
      AND o.ID=$order_id
      GROUP BY o.ID;
    ");
  
  else $order=espb_r("
    SELECT o.ID order_id
    ,'$post_type'as order_type
    ,(SELECT DATE_FORMAT(meta_value,'%b %e, %Y %r') FROM wp_postmeta WHERE post_id=o.ID AND meta_key IN ('_last_shipped') ORDER BY 1 DESC LIMIT 1)shipped
    ,(SELECT GROUP_CONCAT(DISTINCT REPLACE(meta_key,'_pb_label_meta_','')) FROM wp_postmeta WHERE post_id=o.ID AND meta_key LIKE '_pb_label_meta_%' AND LENGTH(meta_value)>6)label_history
    ,LEFT(o.post_date,10)date_created
    ,REPLACE(GROUP_CONCAT(DISTINCT CONCAT('<li class=\'itm\' id=\'',p.ID,'x',po.product_qty+IFNULL(pr.product_qty,0),'\' onclick=\'toggle_item(this);\' title=\'',IFNULL(a.length,0),'x',IFNULL(a.width,0),'x',IFNULL(a.height,0),'\\n',a.weight,'$weight_unit','\'>',po.product_qty+IFNULL(pr.product_qty,0),' x ',p.post_title,CASE WHEN p.post_parent>0 AND p.post_title NOT LIKE CONCAT('%',RIGHT(p.post_excerpt,LOCATE(':',REVERSE(p.post_excerpt))-1)) THEN IFNULL(CONCAT('<br>',p.post_excerpt),'') ELSE '' END) SEPARATOR ';'),';','')product_list
    ,GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(po.variation_id,0),po.product_id),'x',po.product_qty+IFNULL(pr.product_qty,0)))variation_cache
    ,GROUP_CONCAT(CONCAT(po.product_id,'x',po.product_qty+IFNULL(pr.product_qty,0)))product_cache
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_order_total' LIMIT 1)total
    ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='shipping' LIMIT 1),(SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_id=o.ID AND order_item_type='shipping' LIMIT 1))method
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_wc_method_id' LIMIT 1)method_id
    ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='ship_cost' LIMIT 1),(SELECT im.meta_value FROM wp_woocommerce_order_items i JOIN wp_woocommerce_order_itemmeta im ON im.order_item_id=i.order_item_id WHERE i.order_id=o.ID AND i.order_item_type='shipping' AND im.meta_key='cost' LIMIT 1))method_cost
    ,TRIM(CONCAT(
       IFNULL((SELECT CONCAT(meta_value,' ') FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_first_name' LIMIT 1),'')
      ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_last_name' LIMIT 1),'')))shipto
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_company' LIMIT 1)company
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_address_1' LIMIT 1)address1
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_address_2' LIMIT 1)address2
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_city' LIMIT 1)city
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_state' LIMIT 1)state
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_postcode' LIMIT 1)zip
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_shipping_country' LIMIT 1)country
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_billing_email' LIMIT 1)email
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='_billing_phone' LIMIT 1)phone
    ,(SELECT CONCAT(IFNULL(GROUP_CONCAT(CONCAT(DATE_FORMAT(comment_date,'%b %e, %Y %r'),' ',comment_author,': ','<b>',comment_content,'</b>') ORDER BY comment_ID DESC SEPARATOR '|'),''),IFNULL(CONCAT(DATE_FORMAT(o.post_date,'%b %e, %Y %r'),' Customer: ','<b>',NULLIF(o.post_excerpt,'')),''),'</b>') FROM wp_comments WHERE comment_post_id=o.ID AND comment_type='order_note' AND comment_author!='WooCommerce' ORDER BY comment_ID DESC LIMIT 3) comments
    FROM wp_posts o
    JOIN $product_lookup po ON po.order_id=o.ID
    LEFT JOIN (
      SELECT r.post_parent order_id
      ,IFNULL(NULLIF(rp.variation_id,0),rp.product_id)variation_id
      ,rp.product_qty
      FROM wp_posts r
      JOIN wp_wc_order_product_lookup rp ON rp.order_id=r.ID
      WHERE r.post_parent=$order_id AND r.post_type='shop_order_refund'
    )pr ON pr.order_id=o.ID AND pr.variation_id=IFNULL(NULLIF(po.variation_id,0),po.product_id)
    LEFT JOIN (
      SELECT IFNULL(NULLIF(po.variation_id,0),po.product_id) product_id
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_weight'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_weight'))weight
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_length'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_length'))length
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_width'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_width'))width
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_height'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_height'))height
      FROM $product_lookup po
      WHERE po.order_id=$order_id
    )a ON a.product_id=IFNULL(NULLIF(po.variation_id,0),po.product_id)
    LEFT JOIN wp_posts p ON p.ID=IFNULL(NULLIF(po.variation_id,0),po.product_id)
    WHERE o.post_type IN ('shop_order','import_order')
    AND o.post_status IN ('wc-processing','wc-completed')
    AND o.ID=$order_id
    GROUP BY o.ID;
  ");
  if($order) return $order;
}

function espb_product_data($order_id) {
  $post_type=get_post_type($order_id);
  if($post_type=='import_order') $product_lookup='wp_import_order_product_lookup';
  else $product_lookup='wp_wc_order_product_lookup';

  $products=espb_r("
    SELECT *,(qty*weight) total_weight
    FROM (
      SELECT po.variation_id
      ,po.product_id
      ,p.post_title
      ,po.product_qty+IFNULL(pr.product_qty,0) qty
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_weight'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_weight'))weight
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_length'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_length'))length
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_width'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_width'))width
      ,IFNULL((SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.variation_id,0) AND meta_key='_height'),(SELECT NULLIF(NULLIF(meta_value,''),0) FROM wp_postmeta WHERE post_id=NULLIF(po.product_id,0) AND meta_key='_height'))height
      FROM $product_lookup po
      JOIN wp_posts p ON p.ID=IFNULL(NULLIF(po.variation_id,0),po.product_id)
      LEFT JOIN (
        SELECT r.post_parent order_id
        ,IFNULL(NULLIF(rp.variation_id,0),rp.product_id)variation_id
        ,rp.product_qty
        FROM wp_posts r
        JOIN wp_wc_order_product_lookup rp ON rp.order_id=r.ID
        WHERE r.post_parent=$order_id AND r.post_type='shop_order_refund'
      )pr ON pr.order_id=po.order_id AND pr.variation_id=IFNULL(NULLIF(po.variation_id,0),po.product_id)
      WHERE po.order_id=$order_id
    )a;
  ");
  if($products) return $products;
}

function espb_register_carrier($carrier,$account,$env,$ver=array()) {
  $developer_id=get_option('pb_developer_id');
  $postalReportingNumber=espb_merchants($developer_id,$env)[0];
  $url="developers/$developer_id/merchants/$postalReportingNumber/carrier-accounts/register?carrier=$carrier";

  $inputParameters=new stdClass();
  if($carrier=='FEDEX') {
    $inputParameters=[array('name'=>'CLIENT_SOFTWARE_PRODUCT','value'=>'SAPI')];
    $originAddress=espb_create_obj_fromAddress($account[1],$ver[0]);
    $contactAddress=espb_create_obj_fromAddress($account[2],$ver[0]);
  } else {
    $originAddress=espb_create_obj_fromAddress($account[1]);
    $contactAddress=espb_create_obj_fromAddress($account[2]);
  }
  $countryCode=$originAddress->countryCode;
  $licenseText=espb_carrier_license($carrier,$countryCode,$env);

  if($carrier=='UPS') $inputParameters=[
     array('name'=>'ACCOUNT_COUNTRY_CODE','value'=>$countryCode)
    ,array('name'=>'CONTACT_TITLE','value'=>$ver[0])
    ,array('name'=>'END_USER_IP','value'=>$_SERVER['REMOTE_ADDR'])
    ,array('name'=>'INVOICE_AMOUNT','value'=>$ver[4])
    ,array('name'=>'INVOICE_CONTROL_ID','value'=>$ver[2])
    ,array('name'=>'INVOICE_CURRENCY_CODE','value'=>$ver[5])
    ,array('name'=>'INVOICE_DATE','value'=>$ver[3])
    ,array('name'=>'INVOICE_NUMBER','value'=>$ver[1])
    ,array('name'=>'LICENSE_TEXT','value'=>$licenseText)
  ];

  $post_fields=new stdClass();
  $post_fields->accountAddress=$originAddress;
  $post_fields->contactAddress=$contactAddress;
  $post_fields->accountNumber=$account[0];
  $post_fields->inputParameters=$inputParameters;
  $post_fields=json_encode($post_fields);
  
  $result=espb_call($url,$post_fields,'',$env);
  $error=espb_is_err($result,$post_fields);
  if(!empty($error)) return $error;
  $carrier=strtolower($carrier);

  //$result=json_decode($result,true);
  $shipperCarrierAccountId=$result['shipperCarrierAccountId'];
  update_option("pb_carrier_{$carrier}_{$env}_id",$shipperCarrierAccountId,'yes');
}

function espb_carrier_license($carrier,$countryCode,$env) {
  $result=espb_call("carrier/license-agreements?carrier=$carrier&originCountryCode=$countryCode",'','',$env);
  $error=espb_is_err($result,'');
  if(!empty($error)) return $error;
  return $result[0];
}

function espb_validate($order_id,$toAddress,$env,$bypass) {
  $toAddress=json_encode($toAddress);
  $result=espb_call('addresses/verify',$toAddress,$order_id,$env);
  $error=espb_is_err($result,$toAddress,'addresses/verify',$bypass);
  if(!empty($error)) return $error;
  return $result;
}

function espb_rate($order_id,$serviceid,$cache_key,$shipment,$env,$carrier) {
  global $parceltype;
  $shipment=json_encode($shipment);
  $result=espb_call('rates',$shipment,"{$order_id}_$cache_key",$env,$carrier);
  $error=espb_is_err($result,$shipment,'rates');
  if(!empty($error)) return $error;

  $rates=$rate=$sel_rate=$class=$parceltypes=$filter_parcel=$sel_charge=$switch_opt=$svc_desc='';
  $svc_descs=array(
    array('svc'=>'UGA','name'=>'Ground Advantage'),
    array('svc'=>'LIB','name'=>'Library Mail'),
    array('svc'=>'MEDIA','name'=>'Media Mail'),
    array('svc'=>'PM','name'=>'Priority Mail')
  );

  if($result) foreach($result as $key=>$value) {
    if($key=='rates') {
      foreach($value as $k=>$v) {
        foreach($v as $n=>$d) if(!is_array($d)) {
          if(!espb_in_like($n,'carrier,serviceid,parcel,charge,ratetype')) continue;
          if(espb_in_like($n,'ratetype')) $r_ratetypeid=$d;
          if(espb_in_like($n,'serviceid')) $r_serviceid=$d;
          if(espb_in_like($n,'parcel')) {$r_parceltype=$d; $parceltypes.="$d,";}
          if(espb_in_like($n,'charge')) {$rate.="<div class='".strtolower($n)."'>$".number_format($d,2)."</div>";$charge=number_format($d,2);}
          elseif(espb_in_like($n,'serviceid')) {
            $svc_key=array_search($r_serviceid,array_column($svc_descs,'svc'));
            if(isset($svc_descs[$svc_key])) $svc_desc=$svc_descs[$svc_key]['name']; else $svc_desc=ucwords($n).' <b>'.strtoupper($d).'</b>';
            $rate.="<div class='".strtolower($n)."'><b>$svc_desc</b></div>";
          }
          else $rate.="<div class='".strtolower($n)."'>".ucwords($n).' <b>'.strtoupper($d)."</b></div>";
        }

        if($r_ratetypeid=='RETAIL' && (stripos($rates,'CONTRACT')!==false || stripos($rates,'COMMERCIAL')!==false)) {$rate=''; continue;}

        if(($serviceid==$r_serviceid || ($serviceid=='HOM' && $r_serviceid=='GRD')) && ($parceltype==$r_parceltype || empty($parceltype)) && empty($class)) {
          if($carrier=='FEDEX' && $r_serviceid=='GRD') {
            if($serviceid=='HOM') {$r_serviceid='HOM'; $hom_sel='selected';} else $hom_sel='';
            $switch_opt="<div><select onchange=\"update_service(this);approve.classList.add('disabled');\" style='padding:.3em;margin:0;color:#ccc;background:transparent'><option value='GRD'>Ground<option value='HOM' $hom_sel>Home Del</select></div>";
          }
        }

        if($serviceid==$r_serviceid && ($parceltype==$r_parceltype || empty($parceltype)) && empty($class)) {
          $sel_rate="<div class='rate selected' title='$r_serviceid-$r_parceltype-$r_ratetypeid' id='$r_serviceid-$r_parceltype' onclick=\"select_div(this.id,'rate','serviceid','$r_serviceid');select_div(this.id,'rate','parceltype','$r_parceltype');pb_getE('charge').value='$charge';\">$rate$switch_opt</div>";
          $sel_charge=$charge;
          $parceltype=$r_parceltype;
        } else $rates.="<div class='rate' title='$r_serviceid-$r_parceltype-$r_ratetypeid' id='$r_serviceid-$r_parceltype' onclick=\"select_div(this.id,'rate','serviceid','$r_serviceid');select_div(this.id,'rate','parceltype','$r_parceltype');pb_getE('charge').value='$charge';\">$rate</div>";
        $rate='';
      }
    }
  }

  $rates.="<input type='hidden' value='$sel_charge' id='charge' name='charge'>";
  $parcel_array=explode(',',$parceltypes);
  foreach($parcel_array as $pt) if(!empty($pt)) if(stripos($filter_parcel,"'$pt'")==false) $filter_parcel.="<option value='$pt' id='$pt'>$pt";
  if(!empty($filter_parcel)) $filter_parcel="<select id='parcel_filter' onchange='filter_parcel();return false;'><option selected disabled value=''>Parcel Type<option value=''>Clear$filter_parcel</select>";
  return "$filter_parcel<div class='rates'>$sel_rate{$rates}</div>";
}

function espb_ship_date($current_time='') {
  if(empty($current_time)) {$current_time=current_time('mysql'); $placeholder=1;} else $placeholder=0;
  $ship_date=$current_time;
  if(date('N',strtotime($ship_date))==6) $ship_date=date('Y-m-d',strtotime($ship_date.'+1 days')); // Sat
  if(date('H',strtotime($current_time))>=17 || date('N',strtotime($ship_date))==7) $ship_date=date('Y-m-d',strtotime($ship_date.'+1 days')); // Sun or >=5PM
  if($placeholder>0) $ship_date=date('Y-m-d',strtotime($ship_date));
  return $ship_date;
}

function espb_ship($order_id,$cache_key,$shipment,$facility,$env,$carrier,$specialServices='',$pending='',$rtn='') {
  $shipment=json_encode($shipment);
  $result=espb_call('shipments',$shipment,"{$order_id}_$cache_key",$env,$carrier);
  $error=espb_is_err($result,$shipment,'shipments');
  if(!empty($error)) return $error;

  $label=$ship_date='';
  global $parcelTrackingNumber;
  if($result) foreach($result as $key=>$value) {
    if(!is_array($value)) if(espb_in_like($key,'parcelTrack,shipmentId')) ${$key}=$value; else continue;
    else foreach($value as $k=>$v) 
      if(is_array($v)) foreach($v as $n=>$d)
        if(!is_array($d)) if(espb_in_like($n,'contents,carrier,weight,length,width,height,parcelType,serviceId,totalCarrierCharge')) ${$n}=$d; else continue;
  }

  $uid=get_current_user_id();
  $current_time=current_time('mysql');
  $current_time_epoch=strtotime($current_time);
  $tracking=get_post_meta($order_id,'_wc_shipment_tracking_items',true);
  $provider=$carrier;
  $custom_tracking_link=null;
  $custom_tracking_provider=null;
  if($carrier=='NEWGISTICS') {
    $custom_tracking_link=espb_track_url($carrier,$parcelTrackingNumber);
    $provider=null;
    $custom_tracking_provider='Pitney Bowes';
  }
  if($carrier=='FEDEX') $provider='Fedex';
  $new_tracking=array('tracking_provider'=>$provider,'custom_tracking_provider'=>$custom_tracking_provider,'custom_tracking_link'=>$custom_tracking_link,'tracking_number'=>$parcelTrackingNumber,'date_shipped'=>"$current_time_epoch",'tracking_id'=>$shipmentId);
  if(!empty($tracking)) {
    if(!stripos(json_encode($tracking),$parcelTrackingNumber)) {
      if(!is_array($tracking)) $tracking=array();
      array_push($tracking,$new_tracking);
    }
    else $dup_err=1;
  } else $tracking=array($new_tracking);
  if(!isset($dup_err)) {
    if(!isset($totalCarrierCharge) && isset($_POST['charge'])) $totalCarrierCharge=sanitize_text_field($_POST['charge']);
    if(isset($_POST['ship_date'])) $ship_date=sanitize_text_field($_POST['ship_date']);
    if(empty($ship_date)) $ship_date=espb_ship_date($current_time);

    update_post_meta($order_id,'_wc_shipment_tracking_items',$tracking);
    if(empty($rtn)) {
      update_post_meta($order_id,'_last_shipped',$ship_date);
      update_post_meta($order_id,'_date_completed',$current_time_epoch);
      update_post_meta($order_id,'_completed_date',$current_time);
    }
    $tmp=get_option("pb_default_tmp_$uid"); if(empty($tmp)) $tmp='';
    global $new_pb_label_meta;
    $new_pb_label_meta=array($contents,$carrier,$parcelType,$serviceId,'sps'=>$specialServices,'sid'=>$shipmentId,'dim'=>"{$length}x{$width}x$height",'oz'=>$weight,'tot'=>$totalCarrierCharge,'dt'=>"$current_time_epoch",'fc'=>"$facility",'uid'=>$uid,'tmp'=>$tmp);
    add_post_meta($order_id,"_pb_label_meta_$parcelTrackingNumber",$new_pb_label_meta);
    $comment=array('comment_post_ID'=>$order_id,'comment_author'=>espb_usermeta($uid,'display_name'),'comment_author_email'=>espb_usermeta($uid,'user_email'),'comment_author_IP'=>sanitize_text_field($_SERVER['REMOTE_ADDR']),'comment_content'=>"$carrier ($parcelType,$serviceId) label printed.<br>Tracking ...".substr($parcelTrackingNumber,-5),'user_id'=>$uid,'comment_agent'=>'WooCommerce','comment_type'=>'order_note');
    wp_insert_comment($comment);
    delete_post_meta($order_id,'_pb_queue_reship');
    if(empty($pending)) espb_complete_wc_order($order_id);
  }
  return "<div><iframe id='pb_label' src='$contents'></iframe></div>";
}

function espb_complete_wc_order($order_id) {
  $current_time=current_time('mysql');
  $order=espb_r("
    SELECT ID order_id, post_type, post_status
    FROM wp_posts
    WHERE post_type='shop_order'
    AND post_status='wc-processing'
    AND ID=$order_id;
  ");

  if($order) foreach($order as $o) {
    $post=array('ID'=>$o->order_id,'post_status'=>'wc-completed');
    set_transient("completed_email_sent_$order_id",$current_time,86400);
    wp_update_post($post);

    if(in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))) {
      $email_options=get_option('woocommerce_customer_completed_order_settings');
      if($email_options['enabled']=='yes') {
        $emails=WC()->mailer()->get_emails();
        $order_completed_email=$emails['WC_Email_Customer_Completed_Order'];
        $order_completed_email->trigger($o->order_id);
      }
    }
  }
}


function espb_reprint_label($order_id,$tracking_number) {
  $old_label=get_post_meta($order_id,"_pb_label_meta_$tracking_number",true);
  if(is_array($old_label)) {
    $label_url=$old_label[0];
    $carrier=$old_label[1];
    $parcelType=$old_label[2];
    $serviceId=$old_label[3];
    return "<div><iframe id='pb_label' src='$label_url'></iframe><br><br>
    $carrier $parcelType, $serviceId<br>
    $tracking_number
    </div>";
  }
}

function espb_reprint_slip($order_id,$tracking_number,$method,$user_id) {
  global $new_pb_label_meta,$parcelTrackingNumber;
  $slip=$weight=$tmp='';
  $weight_unit=get_option('woocommerce_weight_unit');
  $uid=$user_id;
  if(empty($new_pb_label_meta)) $label=get_post_meta($order_id,"_pb_label_meta_$tracking_number",true); else $label=$new_pb_label_meta;
  if(empty($tracking_number)) $tracking_number=$parcelTrackingNumber;

  $tmp=get_option("pb_default_tmp_$user_id"); if(empty($tmp)) $tmp='';
  if(isset($_POST['tmp'])) {
    $tmp=sanitize_text_field($_POST['tmp']);
    if(!empty($tmp)) {
      if($tmp=='-') $tmp='';
      update_option("pb_default_tmp_$user_id",$tmp);
    }
  }
  
  if(is_array($label)) {
    if(!empty($tmp)) {
      $label['tmp']=$tmp;
      update_post_meta($order_id,"_pb_label_meta_$tracking_number",$label);
    }
    $weight=$label['oz'];
    $dim=str_replace('x',' x ',$label['dim']);
    $svc=$label['sps'];
    $uid=$label['uid'];
    $method="<br>Method: $method<br>";
  } else {
    global $weight,$length,$width,$height;
    $dim="$length x $width x $height";
  }
  
  $slip.="
  <div class='label_meta'>Weight: {$weight}$weight_unit<br>
  Dimension: $dim(in)";
  if(!empty($svc)) $slip.="<br>Special Service: $svc";
  $slip.="<br>Printed: <span id='render_time'></span></div>";
  $initials=substr(espb_usermeta($uid,'first_name'),0,1).substr(espb_usermeta($uid,'last_name'),0,1);
  $slip.="<div style='display:inline-block;font-size:.9em'>Order ID $order_id $method $tracking_number<br>Processor: $initials $tmp
    <form name='pb_update_label' id='pb_update_label' method='POST' action='".admin_url('admin.php?page=pb-ship')."&order_id=$order_id&override=1' accept-charset='UTF-8 ISO-8859-1' style='display:inline;margin:0'>
      <input type='hidden' name='submit_type45652811' value='1'>
      <input type='hidden' name='tracking_number' value='$tracking_number'>
      <input type='text' name='tmp' value='' class='noprint' placeholder='Addt Emp Initials' title='Use spacebar to clear initials' style='border:none;outline:none' onchange=\"if(this.value==' ')this.value='-';pb_load_animation();this.form.submit();\">
    </form>
  </div>
  <script type='text/javascript'>var stamp=new Date().toLocaleString();render_time.innerHTML=stamp;</script>";
  return $slip;

}

function espb_track_url($carrier,$tracking) {
  if(empty($tracking)) return;
  $usps="https://tools.usps.com/go/TrackConfirmAction?tLabels=$tracking";
  $fedex="https://www.fedex.com/fedextrack/?trknbr=$tracking";
  $ups="https://www.ups.com/track?tracknum=$tracking";
  $dhl="https://www.dhl.com/us-en/home/tracking/tracking-express.html?submit=1&tracking-id=$tracking";
  $newgistics="https://clientconnect.shipment.co/track/$tracking";
  $default="https://parcelsapp.com/en/tracking/$tracking";

  if($carrier=='NEWGISTICS' || (empty($carrier) && strlen($tracking)>=30)) {
    $custom_url=get_option('pb_custom_tracking_url');
    if(!empty($custom_url)) return $custom_url.$tracking; else return $newgistics;
  }
  if($carrier=='USPS' || (empty($carrier) && strlen($tracking)>=20)) return $usps;
  if($carrier=='USPS' || (empty($carrier) && strlen($tracking)>=12 && strlen($tracking)<=14 && substr($tracking,0,2)=='LZ')) return $usps;
  if($carrier=='FEDEX' || (empty($carrier) && strlen($tracking)>=12 && strlen($tracking)<=14)) return $fedex;
  if($carrier=='UPS' || (empty($carrier) && substr($tracking,0,2)=='1Z')) return $ups;
  if($carrier=='DHL' || (empty($carrier) && strlen($tracking)>=9 && strlen($tracking)<=11)) return $dhl;
  return $default;
}

function espb_label_history($order_id,$label_history) {
  $label_h=$label_i=$del_est='';
  $weight_unit=get_option('woocommerce_weight_unit');
  if(!empty($label_history)) $label_history=explode(',',$label_history);
  foreach($label_history as $v) {
    $tracking_number=$v;
    $old_label=get_post_meta($order_id,"_pb_label_meta_$tracking_number",true);
    if(is_array($old_label)) {
      $label_url=$old_label[0];
      $carrier=$old_label[1];
      $parcelType=$old_label[2];
      $serviceId=$old_label[3];
      $weight=$old_label['oz'];
      $dim=$old_label['dim'];
      $ship_dt=$old_label['dt'];
      $totalCarrierCharge=$old_label['tot'];
      if(isset($old_label['st'])) $status=$old_label['st']; else $status='';
      $now=new DateTime(current_time('mysql'));
      $ship_dt=date('M, j Y G:i:s',$ship_dt);
      $track_url=espb_track_url($carrier,$tracking_number);
      if(stripos($status,'delivered')===false && stripos($status,'cancel')===false && !isset($_POST['submit_type']) && strtotime($ship_dt)<strtotime('-1 hour',$now->getTimestamp())) {
        $status=espb_track_status($order_id,$carrier,$tracking_number,'track');
        if(stripos($status,'delivered')===false) {
          $del_est=get_post_meta($order_id,"delivery_est",true);
          if(!empty($del_est)) $del_est="\nEst Delivery $del_est";
        }
      }
      $label_i="<a target='_blank' title='$dim\n{$weight}$weight_unit\n$$totalCarrierCharge\n$status$del_est' href='$track_url'>$tracking_number</a>";
      $label_i="<b>$carrier<br>$parcelType, $serviceId</b><br>{$label_i}$ship_dt";
      if(stripos($status,'cancel')===false)
        $label_i.="
        <input type='button' value='Reprint' onclick=\"tracking_number.value='$tracking_number';submit_type.value=2;pb_load_animation();pb_getE('pb_ship').submit();\">
        <input type='button' value='Cancel' onclick=\"if(!confirm('Are you sure you want to cancel this label? This action cannot be undone.'))return false;tracking_number.value='$tracking_number';submit_type.value=3;pb_load_animation();pb_getE('pb_ship').submit();\">";
      else $label_i.="<input type='button' value='Cancelled' style='opacity:.5;pointer-events:none'>";
    } else { // Shipped without plugin
      $ship_dt=date('M, j Y',$ship_dt);
      $label_i="<b>$carrier</b>$label_i $ship_dt";
    }
    $label_h.="<div class='hist'>$label_i</div>"; $label_i='';
  }
  return $label_h;
}

function espb_track_status($order_id,$carrier,$tracking_number,$cache_key='',$test=0) {
  if(empty($carrier) || empty($tracking_number)) return;
  $result=espb_call("tracking/$tracking_number?packageIdentifierType=TrackingNumber&carrier=$carrier",'',$cache_key,'prod');
  $log=$error='';
  //$error=espb_is_err($result,'');
  if(empty($error)) {
    $status=$deliveryDate='';
    if(espb_troubleshoot_mode()>0) $log.="Track Meta: $tracking_number<br>";
    if($result) foreach($result as $key=>$value) {
      if(espb_in_like($key,'status,deliveryDate,packageStatus,estimatedDeliveryDate,scanDetailsList')) {${$key}=$value; if(!is_array($value) && espb_troubleshoot_mode()>0) $log.=" $key: $value<br>";}
    }
    if($status=='Manifest' && !empty($scanDetailsList)) {
      foreach($scanDetailsList as $scanDetail) {
        foreach($scanDetail as $k=>$v) {
          if($k=='packageStatus' && $v=='Acceptance') $status='Acceptance';
          if(espb_troubleshoot_mode()>0) $log.=" $k: $v<br>";
        }
      }
    }
    if(!empty($log)) {echo $log; return;}
    if(!empty($status)) {
      $label_meta=get_post_meta($order_id,"_pb_label_meta_$tracking_number",true);
      if(!empty($deliveryDate)) $status.=", $deliveryDate";
      elseif(!empty($estimatedDeliveryDate)) update_post_meta($order_id,"delivery_est",$estimatedDeliveryDate);
      if($cache_key=='track' && $label_meta['st']=='Manifest' && stripos('InTransit,Acceptance',$status)!==false) if(get_post_status($order_id)=='wc-processing') espb_complete_wc_order($order_id); // Send WC completed email
      $label_meta['st']=$status;
      if($test<1) update_post_meta($order_id,"_pb_label_meta_$tracking_number",$label_meta);
      return $status;
    }
  }
}

function espb_cancel_label($order_id,$tracking_number,$env) {
  $old_label=get_post_meta($order_id,"_pb_label_meta_$tracking_number",true);
  if(is_array($old_label)) {
    $carrier=$old_label[1];
    $parcelType=$old_label[2];
    $serviceId=$old_label[3];
    $shipmentId=$old_label['sid'];
    $old_label['st']='cancel';
    if($carrier=='USPS') $carrier_var=''; else $carrier_var="&carrier=$carrier";
    $result=espb_call("shipments/$shipmentId?cancelInitiator=SHIPPER$carrier_var",'','',$env,$carrier);
    espb_r("DELETE FROM wp_options WHERE option_name LIKE '_transient_espb_shipments_{$env}_{$order_id}_%';");
    $error=espb_is_err($result,'');
    if(empty($error)) {
      update_post_meta($order_id,"_pb_label_meta_$tracking_number",$old_label);

      $new_tracking=[];
      $tracking=get_post_meta($order_id,'_wc_shipment_tracking_items',true);
      if($tracking) {
        foreach($tracking as $t=>$v) {if(!espb_in_like(json_encode($v),$tracking_number)) array_push($new_tracking,$v);} // Remove cancelled label from _wc_shipment_tracking_items
        update_post_meta($order_id,'_wc_shipment_tracking_items',$new_tracking);
        
        if(count($new_tracking)<1) {
          $post=array('ID'=>$order_id,'post_status'=>'wc-processing'); // Put back in queue if no other labels exist
          wp_update_post($post);
          delete_post_meta($order_id,'_last_shipped');
        }
      }
    }
    return "<div class='err'>".espb_parse_array($result)."</div>";
  }
}

function espb_report($report_type,$carrier,$post_fields,$post_id,$api,$pickup_fields,$env) {
  $confirm='';
  $post_fields=json_encode($post_fields);
  if($report_type=='container') $url='container-manifest'; else $url='manifests';
  
  // $api=0; // Bypass API call
  if($api>0) {
    $result=espb_call($url,$post_fields,'',$env);
    $error=espb_is_err($result,$post_fields,$url);
    if(!empty($error)) return $error;

    if(!empty($pickup_fields)) {
      $pickup_fields=json_encode($pickup_fields);
      $url='pickups/schedule';
      $pickup_result=espb_call($url,$pickup_fields,'',$env);
      $pickup_error=espb_is_err($pickup_result,$pickup_fields,$url);
    }
  } else {
    $result=false;
    $current_time=current_time('mysql');
    $manifestId="$carrier-$report_type-$current_time";
  }

  if($result) foreach($result as $key=>$value) {
    if(espb_in_like($key,'manifestId')) ${$key}=$value;
    elseif(is_array($value)) foreach($value as $k=>$v) {
      if(espb_in_like($k,'pbContainerId,labelData')) ${$k}=$v;
      elseif(is_array($v)) foreach($v as $k2=>$v2) {
        if(espb_in_like($k2,'contents')) ${$k2}=$v2;
        if(is_array($v2)) foreach($v2 as $k3=>$v3) if(espb_in_like($k3,'contents')) ${$k3}=$v3;
      }
    }
  }


  if($carrier=='USPS' && isset($contents)) $labelData=$contents;

  if(isset($pickup_result)) {
    foreach($pickup_result as $key=>$value) {
      if(espb_in_like($key,'pickupDateTime,pickupId')) ${$key}=$value;
    }
    if(isset($pickupId)) $confirm.="<b>Pickup scheduling successful.</b><br>Pickup ID: $pickupId<br>Date: $pickupDateTime<br>Post ID: $post_id";
  }

  if(isset($pbContainerId)) {
    espb_r("UPDATE wp_posts SET post_content='$labelData',guid='$pbContainerId' WHERE ID=$post_id;");
    $confirm.="<div><iframe id='pb_label' src='data:application/pdf;base64,$labelData'></iframe></div>";
  }

  elseif(isset($labelData)) {
    espb_r("UPDATE wp_posts SET post_content='$labelData',guid='$manifestId' WHERE ID=$post_id;");
    $confirm.="<div><iframe id='pb_label' src='$labelData'></iframe></div>";
  }

  elseif(isset($manifestId)) {
    espb_r("UPDATE wp_posts SET guid='$manifestId' WHERE ID=$post_id;");
    $confirm.="<b>Close out successful.</b><br>Manifest ID: $manifestId<br>Post ID: $post_id";
  }
  return $confirm;
}


function espb_report_history($report_id=0) {
  $history=$filter=$goback=$last_date=$class=$rpt_status='';
  $url=admin_url('admin.php?page=pb-reports');
  if($report_id>0) $filter="AND ID=$report_id"; else $filter="AND post_status!='trash'";
  $hist=espb_r("
    SELECT ID,post_date,post_type,post_status,post_title,post_content,guid
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=p.ID AND meta_key='items')items
    ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=p.ID AND meta_key='wt'),0)wt
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=p.ID AND meta_key='cost')cost
    ,(SELECT meta_value FROM wp_postmeta WHERE post_id=p.ID AND meta_key='deleted')deleted
    ,(COALESCE(CONCAT((SELECT meta_value FROM wp_usermeta WHERE user_id=p.post_author AND meta_key='first_name' AND LENGTH(meta_value)>0),(SELECT CONCAT(' ',LEFT(meta_value,1)) FROM wp_usermeta WHERE user_id=p.post_author AND meta_key='last_name' AND LENGTH(meta_value)>0)),(SELECT meta_value FROM wp_usermeta WHERE user_id=p.post_author AND meta_key='nickname' AND LENGTH(meta_value)>0),(SELECT display_name FROM wp_users WHERE ID=p.post_author),'[wp user deleted]'))user
    FROM wp_posts p
    WHERE p.post_type LIKE 'pb_%'
    $filter
    ORDER BY post_date DESC;
  ");
  if($hist) {
    $weight_unit=get_option('woocommerce_weight_unit');
    foreach($hist as $h) {
      $rpt_id=$h->ID;
      $rpt_guid=$h->guid;
      $rpt_date=$h->post_date;
      $rpt_type=ucwords(str_replace('pb_','',$h->post_type));
      $rpt_status=$h->post_status;
      $rpt_title=ucwords($h->post_title);
      $items=$h->items;
      $wt=str_replace(',','',$h->wt);
      $cost=str_replace(',','',$h->cost);
      $deleted=$h->deleted;
      $rpt_user=ucwords($h->user);
      $rpt_content=$h->post_content;
      $view_label=$list_items=$list_items_row='';

      if($report_id<1) {
        if($deleted>0) $list_items="$items Deleted Items";
        else {
          $list_items="<a>List $items Items</a>";
          $list_items_row="onclick='pb_label.classList.add(\"hide\");pb_load_animation();window.location.href=\"$url&rpt_id=$rpt_id\";'";
        }
      }

      if(!empty($rpt_content)) {
        if(stripos($rpt_type,'container')!==false) $rpt_url='data:application/pdf;base64,'.$rpt_content; else $rpt_url=$rpt_content;
        $view_label="<a style='margin-top:.5em' onclick='pb_label.classList.remove(\"hide\");pb_label.src=\"$rpt_url\";'>View Label</a>";
        if($report_id<1) if($deleted>0) $list_items="$items Deleted Items"; else $list_items="<a $list_items_row>List $items Items</a>";
        $list_items_row='';
      } else $view_label='';

      if($last_date!=substr($rpt_date,0,10)) if(empty($class)) $class="class='even'"; else $class='';
      $last_date=substr($rpt_date,0,10);
      $avg_cost=$avg_wt='';
      if($items>0) $avg_cost=number_format(($cost/$items),2);
      $cost=number_format($cost,2);
      $wt=number_format($wt,2);
      if($wt>0) {
        $tot_wt="<br>{$wt}$weight_unit"; 
        $avg_wt="<br>".number_format(($wt/$items),2).$weight_unit;
      } else $tot_wt=$avg_wt='';
      $history.="<tr $class title='Report ID $rpt_id' $list_items_row><td>$rpt_date</td><td>$rpt_title</td><td>$$cost{$tot_wt}</td><td>$$avg_cost{$avg_wt}</td><td>$rpt_user</td><td nowrap>$list_items $view_label</td></tr>";
    }
    $history="
    <div style='max-height:30em;overflow:auto'>
      <table class='admin_report pb_rpt rpt_hist'><tr><th>Date</th><th>Report</th><th>Totals</th><th>Avg</th><th align='left'>User</th><th align='left'>Action</th></tr>$history</table>
    </div>
    <div><br><iframe id='pb_label' class='hide' src=''></iframe></div>";
  } else $history="<div class='err'>No history exists.</div>";

  if($rpt_status=='trash' || $deleted>0) $history.="<div class='err'>This Report has been Deleted.</div>";
  if($report_id<1) $rpt_type='History'; 
  else {
    $goback="<input type='button' value='Go Back' onclick=\"pb_load_animation();window.location=location.href.replace('rpt_id','na')+'&rpt=container';\">";
    if(current_user_can('view_woocommerce_reports')) {
      $goback.="<a id='delete_rpt' class='dashicons dashicons-dismiss pb_remove' title='Delete Report' onclick=\"if(confirm('Are you sure you want to delete this report?')) {pb_load_animation();window.location=location.href+'&delete_report=$rpt_id';}\"></a>";
      $goback.="<input type='button' style='margin-left:.5em!important;' value='Fetch Updates' onclick=\"if(!confirm('Are you sure you want to fetch delivery status updates?\\nThis may take a while.')) return false; pb_load_animation();window.location=location.href+'&dlv_status=1';\">";
    }
  }
  return "
  <div class='module history'>
    <div class='title'>$rpt_type</div>
    $history
    $goback
  </div><br>";
}


function espb_parse_array($array) {
  $result='';
  if(!is_array($array)) $array=unserialize($array);
  if(!is_array($array)) return $array;
  if(is_array($array)) foreach($array as $key=>$value) {
    if(!is_array($value)) $result.="<div title='array level 1'><b>".strtoupper($key)."</b> $value</div>";
    else foreach($value as $k=>$v)
      if(!is_array($v)) $result.="<div title='array level 2'><b>".strtoupper($k)."</b> $v</div>";
      else foreach($v as $n=>$d)
        if(!is_array($d)) $result.="<div title='array level 3'><b>".strtoupper($n)."</b> $d</div>";
        else foreach($d as $n1=>$d1) $result.="<div title='array level 4'><b>".strtoupper($n1)."</b> $d1</div>";
  }
  return $result;
}

function espb_is_err($array,$call,$url='',$bypass=0) {
  $string=json_encode($array);
  if(espb_in_like($string,'errorc,invalid')!==false || espb_troubleshoot_mode()>0) {
    $request_header=get_transient("request_header_$url");
    $error=espb_parse_array($array);
    $error=str_ireplace('errorc','ERROR C',str_ireplace('errord','D',$error));
    if(stripos($call,'LICENSE_TEXT')!==false) $call=htmlspecialchars($call);
    if(espb_troubleshoot_mode()>0) $error='Troubleshooting Mode';
    if(!empty($error)) {
      $error="<div class='err'>$error</div>";
      if(!empty($call)) $error.="<div class='req' tabindex=1>".$request_header.'<br>'.str_replace('","','",<br>"',str_replace(',"',',<br>"',str_replace('},','},<br>',$call)))."</div>";
      if($bypass<1) $error.="<script type='text/javascript'>setTimeout(function(){if(document.getElementById('approve')) document.getElementById('approve').classList.add('disabled');},500);</script>";
      return $error;
    }
  }
}

function espb_color_scheme($scheme,$loc) {
  if($scheme=='dark') {
    if($loc=='bg_clr') return '#000';
    if($loc=='bg_mod_clr') return '#222';
    if($loc=='bg_itm_clr') return 'transparent';
    if($loc=='txt_clr') return '#FFF';
    if($loc=='txt_hl_clr') return '#d61a8e';
    if($loc=='brd_hl_clr') return '#FFF';
    if($loc=='bgd_hl_clr') return '#0078ad';
  }
  else {
    if($loc=='bg_clr') return '#FFF';
    if($loc=='bg_mod_clr') return '#EEE';
    if($loc=='bg_itm_clr') return '#FFF';
    if($loc=='txt_clr') return '#555';
    if($loc=='txt_hl_clr') return '#d70080';
    if($loc=='brd_hl_clr') return '#d70080';
    if($loc=='bgd_hl_clr') return '#0078ad';
  }
}

function espb_get_services($carrier) {
  if($carrier=='FEDEX') $spc_services=array('ADD_HDL'=>'Additional Handling','ADULT_SIG'=>'Adult Signature Required','CARRIER_LEAVE_IF_NO_RES'=>'Carrier Leave If No Response','DIRECT_SIG'=>'Direct Signature Required','EVENING'=>'FedEx Evening Home Delivery','SAT_DELIVERY'=>'FedEx Saturday Delivery','SAT_PICKUP'=>'FedEx Saturday Pickup','SIG'=>'Indirect Signature Required');
  elseif($carrier=='UPS') $spc_services=array('ADD_HDL'=>'Additional Handling','ADULT_SIG'=>'Adult Signature Required','CARBON'=>'UPS carbon neutral','DEL_CON'=>'UPS Delivery Confirmation','DIRECT'=>'Direct Delivery Only','HOLD'=>'Hold for Pickup','SAT_DELIVERY'=>'Saturday Delivery','SHP_RELEASE'=>'Shipper Release','SIG'=>'Signature Required');
  else $spc_services=array('ADSIG'=>'Adult Signature Required','ADSIGRD'=>'Adult Signature with Restricted Delivery','Cert'=>'Certified Mail','CERTAD'=>'Certified Mail with Adult Signature','CERTADRD'=>'Certified Mail with Adult Signature and Restricted Delivery','CertRD'=>'Certified Mail with Restricted Delivery','COD'=>'Collect On Delivery','CODRD'=>'Collect On Delivery with Restricted Delivery','DelCon'=>'Delivery Confirmation','vERR'=>'Electronic Return Receipt','hazmat'=>'Hazardous Material','holiday'=>'Holiday Delivery','Ins'=>'Insured Mail','InsRD'=>'Insured Mail with Restricted Delivery','liveanimal'=>'Live Animal','liveanimal-poultry'=>'Live Animal - Day Old Poultry','PMOD_OPTIONS'=>'Priority Mail Open and Distribute','Reg'=>'Registered Mail','RegCOD'=>'Registered Mail with COD','RegIns'=>'Registered Mail with Insurance','RegInsRD'=>'Registered Mail with Insurance and Restricted Delivery','RegRD'=>'Registered Mail with Restricted Delivery','RR'=>'Return Receipt','SH'=>'Special Handling - Fragile','Sig'=>'Signature Confirmation','SigRD'=>'Signature with Restricted Delivery','sunday'=>'Sunday Delivery','sunday-holiday'=>'Sunday holiday Delivery');
  $options='';
  foreach($spc_services as $v=>$d) $options.="<option id='$v'>$d";
  return $options;
}

function espb_popup_css() { ?>
  <style>
    #wpbody-content{padding:0}
    #wpbody-content *{visibility:visible}
    #wpadminbar,#adminmenumain,#wpfooter{display:none}
    #wpbody-content .notice{display:none!important}
  </style>
  <?php
}

function espb_inc_css($color_scheme) { ?>
  <style>
    :root{
      --bg_clr:<?php echo espb_color_scheme($color_scheme,'bg_clr');?>;
      --bg_mod_clr:<?php echo espb_color_scheme($color_scheme,'bg_mod_clr');?>;
      --bg_itm_clr:<?php echo espb_color_scheme($color_scheme,'bg_itm_clr');?>;
      --txt_clr:<?php echo espb_color_scheme($color_scheme,'txt_clr');?>;
      --txt_hl_clr:<?php echo espb_color_scheme($color_scheme,'txt_hl_clr');?>;
      --brd_hl_clr:<?php echo espb_color_scheme($color_scheme,'brd_hl_clr');?>;
      --bgd_hl_clr:<?php echo espb_color_scheme($color_scheme,'bgd_hl_clr');?>;
    }
    body{background:var(--bg_clr);color:var(--txt_clr);font-family:sans-serif;padding:0 3em}
    #pb_ship,#pb_report{-webkit-transition:all 1s;transition:all 1s}
    select.multiple option{padding:1em}
    .logo{max-width:12em;max-height:4em;margin-right:1em;height:auto;width:auto}
    .pb_rpt{width:100%;border-spacing:0;font-size:13px;background:#fff;color:#555}
    .pb_rpt th{text-align:left;padding:1em}
    .pb_rpt td{padding:1em;border-top:1px solid #eee}
    .pb_rpt tr{-webkit-transition:all .5s;transition:all .5s}
    .pb_rpt tr.pb_del{opacity:0}
    .pb_rpt tr.even{color:#fff;background:var(--bgd_hl_clr);}
    .pb_rpt tr.even td a{background:#fff;border:1px solid #fff}
    .pb_rpt tr:hover{cursor:pointer;color:#fff;background:var(--bgd_hl_clr)}
    .admin_report.pb_rpt tr:hover{background:var(--txt_hl_clr)}
    .admin_report.pb_rpt tr.even:hover{background:#015e88}
    .pb_rpt td .dashicons{color:#DDD;vertical-align:bottom}
    .pb_rpt tr:hover a{color:#fff}
    .pb_rpt td a{border:1px solid #ddd;padding:.5em;border-radius:5px}
    .pb_rpt tr:hover td a{background:#fff;color:var(--bgd_hl_clr);border:1px solid #fff}
    .pb_rpt td a.pb_remove{background:transparent!important;border:none!important;color:#ddd!important}
    .pb_rpt td a.pb_remove:hover{color:#fff!important;background:red!important}
    .module.slip .logo{display:block;margin:0 0 1em}
    .module .title{float:left;margin:-1.05em -2.7em;background:var(--bg_mod_clr);padding:1em .25em;color:var(--txt_clr);border:1px solid #555;border-left:none;font-family:sans-serif;writing-mode:vertical-lr;-webkit-transform:rotate(-180deg)}
    .module.slip:not(.title){background:#fff;color:#000;font-size:.8em}
    .module:hover .title{background:linear-gradient(var(--bgd_hl_clr),#5e5ed1);color:#fff}
    .module{background:var(--bg_mod_clr);border:1px solid #555;padding:1em;min-height:8em}
    .module:not(.label):not(.slip):not(.dest):not(.parcel) input,select,.form_control input{display:inline-block;margin:0 0 1em 0;padding:1em;outline:none}
    input[type=submit],input[type=button]{cursor:pointer}
    input[type=button],input[type=submit],select{background:#fff;color:#000;border:1px solid #888;outline:none;-webkit-appearance:none}
    input[type=button]:hover,input[type=submit]:hover{transform:scale(1.05);border:1px solid var(--bgd_hl_clr)}
    .module.dest input{display:inline-block;margin:0;padding:.5em;background:transparent;border:1px solid transparent;font-weight:bold}
    .module.parcel input{color:var(--txt_hl_clr);text-align:center;background:#fff;border:1px solid #aaa;font-size:1.2em;padding:.5em;margin:0 0 .5em 0}
    .module input.short{width:3.5em;padding:.5em}
    .module input[type=submit]{cursor:pointer}
    .module.parcel input:hover,.module.parcel input:focus{transform:scale(1.05)}
    .module.dest input:hover,.module.dest input:focus{border:1px solid #ccc!important;outline:none}
    .module .err{padding:1em;background:#fff;color:var(--txt_hl_clr);font-size:.9em}
    .module .req{max-height:1.2em;padding:.4em;font-size:.8em;overflow:hidden;outline:none;background:var(--bgd_hl_clr);color:#fff;white-space:pre;-webkit-transition:all 1s;transition:all 1s}
    .module .req:focus{max-height:49em;overflow:auto}
    .module .req:before{content:'API Call Details:';display:block;font-weight:bold;font-size:1em;margin:0 0 1em .3em;cursor:pointer}
    .module iframe{width:-webkit-fill-available;height:22em;-webkit-transition:all .5s;transition:all .5s}
    .module iframe.hide{height:0;opacity:0}
    .module.history input[type=button]{margin:.5em 0 0!important}
    .module.history a{display:block;color:var(--bgd_hl_clr);text-decoration:none}
    .module.note {font-size:.8em;line-height:1.5em;min-height:3em;background:yellow;color:#000}
    .progress{position:absolute;left:30%;top:30%;max-width:0;max-height:0;opacity:0;overflow:hidden;pointer-events:none;-webkit-transition:all 2s linear;transition:all 2s linear}
    #uploading.progress{border:2px solid #fff;background:rgb(21 132 178);color:#fff;padding:.5em 1em;font-size:1.5em;text-align:center}
    .progress.fadein{z-index:1;opacity:1;max-width:20em;max-height:20em;overflow:hidden}
    .progress img{width:8em}
    .status.fadeout{max-height:0;opacity:0;padding:0}
    .status div{margin:1em 0;width:50%;max-width:20em;background:#111;border:1px solid #aaa;padding:1em}
    .rates{max-height:14em;overflow-y:auto}
    .rate,.hist{display:inline-block;width:12em;margin:0 0 .3em .3em;padding:1em;font-size:.8em;overflow:hidden;vertical-align:top;border:2px solid transparent}
    .hist:hover{background:var(--bgd_hl_clr)!important;color:#fff!important}
    .hist:hover a{text-decoration:underline;color:#fff}
    .rate:hover{background:var(--bg_itm_clr);cursor:pointer;border:2px solid var(--brd_hl_clr)}
    .rate.selected{background:var(--bgd_hl_clr)!important;color:#fff!important}
    .rate.selected:hover{border:2px solid transparent}
    .rate .totalcarriercharge{font-size:1.3em;display:block;font-weight:bold}
    .rate .basecharge,.rate .ratetypeid{display:none}
    .slip .address{margin:1em 0;padding:1em;border:1px solid #CCC}
    .slip input[type=button],.label input[type=button]{float:right;padding:1em 2em}
    .slip .title{margin:-.8em -2.4em;background:#fff;color:#000;font-size:1.3em}
    .dest .address{display:inline-table;width:48%;border:2px solid transparent}
    .dest input{width:fit-content}
    .dest .address:not(.selected) input,#address_validated input{pointer-events:none}
    .dest .address .address_input{background:var(--bg_itm_clr);border:2px solid transparent}
    .dest .address .address_input input{color:#aaa}
    .dest .address:hover{cursor:pointer}
    .dest .address:hover .address_input{border:2px solid var(--brd_hl_clr)}
    .dest .address.selected .address_input{background:var(--bgd_hl_clr)}
    .dest .address.selected .address_input input{color:#FFF}
    .dest .address.selected:hover .address_input{border:2px solid transparent}
    .dest .address_status{text-align:center;padding:.5em;font-size:.8em;color:#fff;background:#444}
    .disabled{opacity:.5;pointer-events:none}
    .itm{display:inline-block;margin:.3em .3em .3em 0;padding:.5em;background:var(--bgd_hl_clr)!important;color:#fff!important;line-height:1.5em;border-radius:5px;font-size:.8em;cursor:pointer}
    .itm:before{content:'\2715';margin-right:.3em;background:#dddddd80;color:#fff;border-radius:1em;padding:.1em .3em}
    .itm:hover:before{background:#d70080}
    .itm.inactv{background:#82828240!important}
    .itm.inactv:hover:before{content:'\2713';background:limegreen}
    #sel_services .itm{background:#414955!important}
    #delete_rpt{float:right;color:red;transform:scale(1.5);margin:1em .5em;cursor:pointer}
    .slip .itm{font-size:1em}
    #svc{width:33%}
    .label_meta{float:right;font-size:.9em}
    @media print{
      body{padding:0 0 0 1em!important}
      .noprint,.slip .itm.inactv,.slip .itm:before{display:none}
      .slip .itm{margin-right:2em;padding:0 0 .5em;background:#fff!important;color:#000!important;border-bottom:1px solid #000;border-radius:0}
    }
    @media (max-width:500px) {
      .label_meta{float:none}
    }
  </style><?php
}

function espb_inc_js($parceltype='') { ?>
  <script type='text/javascript'>
    function open_pb_queue() {window.open('<?php echo admin_url('admin.php?page=pb-queue&rpt=');?>','Shipping Queue','width=875,height=1005,resizable=yes,scrollbars=yes');}
    function open_pb_report() {window.open('<?php echo admin_url('admin.php?page=pb-reports');?>','Shipping Report','width=900,height=1005,resizable=yes,scrollbars=yes');}
    function open_wc_order(order_id,order_type) {
      if(order_type.includes('import')) window.open('/wp-admin/options-general.php?page=amz_admin&tab=orders&order_id='+order_id+'','Order','width=875,height=1005,resizable=yes,scrollbars=yes');
      else if('<?php echo espb_is_hpos();?>'>0) window.open('/wp-admin/admin.php?page=wc-orders&action=edit&id='+order_id+'','Order','width=875,height=1005,resizable=yes,scrollbars=yes');
      else window.open('/wp-admin/post.php?post='+order_id+'&action=edit','Order','width=875,height=1005,resizable=yes,scrollbars=yes');
    }

    function pb_load_animation() {
      window.scrollTo(0,0);
      if(pb_getE('status')) pb_getE('status').classList.add('fadeout');
      var pb_page;
      if(pb_getE('pb_ship')||pb_getE('pb_report')) {
        if(pb_getE('pb_ship')) pb_page=pb_ship; else pb_page=pb_report;
        pb_page.style.pointerEvents='none';
        pb_page.style.opacity='.2';
      }
      setTimeout(function(){neon_cat.classList.add('fadein');},4000);
      setInterval(function(){neon_cat.style.top=Math.floor((Math.random()*50)+10)+'%';neon_cat.style.left=Math.floor((Math.random()*50)+10)+'%';},2000);
      setTimeout(function(){uploading.classList.add('fadein');},5500);
      setTimeout(function(){uploading.innerHTML+='.';},6500);
      setTimeout(function(){uploading.innerHTML+='.';},8000);
      setTimeout(function(){uploading.innerHTML+='.';},9500);
      setTimeout(function(){uploading.classList.remove('fadein');},10500);
      return false;
    }

    function update_service(el){
      setTimeout(function(){pb_getE('serviceid').value=el.options[el.selectedIndex].value;},500);
    }
    
    function select_div(thisid,classname,target,sel) {
      pb_getE(target).value=sel;
      var divs=pb_getEC(classname);
      if(divs) for(i=0; i<divs.length; i++) {
        divs[i].classList.remove('selected');
      }
      pb_getE(thisid).classList.add('selected');
    }

    function pb_getEC(className){
      if(!document.getElementsByClassName(className)) return false;
      else return document.getElementsByClassName(className);
    }

    function pb_getE(id){
      if(!document.getElementById(id)) return false;
      else return document.getElementById(id);
    }

    function init_add_service() {
      var svc_input=pb_getE('services');
      if(!svc_input) return;
      if(svc_input.value.length==0) return;
      var svc_array=svc_input.value.split(',');
      for(i=0; i<svc_array.length; i++) {
        if(svc_array[i].length>0) add_service(svc_array[i]);
      }
    }init_add_service();

    function add_service(sel) {
      var svc=pb_getE('svc');
      if(sel.length>0) var sel=pb_getE(sel); else var sel=svc.options[svc.selectedIndex];
      var svc_input=pb_getE('services');
      if(!svc_input.value.includes(sel.id+',')) svc_input.value+=sel.id+',';
      sel.hidden=true;
      var i=document.createElement('a');
      i.id='svc_'+sel.id;
      i.className='svc itm';
      i.innerHTML=sel.innerHTML;
      i.onclick=function(){clear_service(this)};
      sel_services.append(i);
    }

    function clear_service(elm) {
      var sel=elm.id.replace('svc_','');
      var svc_input=pb_getE('services');
      if(sel=='undefined') svc_input.value='';
      else {
        if(svc_input.value.includes(sel+',')) svc_input.value=svc_input.value.replace(sel+',','');
        var svc=pb_getE(elm.id.replace('svc_',''));
        svc.hidden=false;
      }
      elm.remove();
    }
    
    function filter_parcel(sel) {
      if(!pb_getE('parcel_filter')) return;
      sel=sel || '';
      var pkg=pb_getE('parcel_filter');
      if(sel.length==0) var sel=pkg.options[pkg.selectedIndex].id; else pb_getE(sel).selected=true;
      if(sel=='') pkg.selectedIndex=0;
      var rates=pb_getEC('rate');
      var def_rate='';
      for(i=0; i<rates.length; i++) {
        if(sel=='') rates[i].style.display='inline-block';
        else {
          if(rates[i].id.includes('-'+sel)) {
            rates[i].style.display='inline-block';
            if(def_rate=='') def_rate=rates[i].id;
          } else rates[i].style.display='none';
        }
      }
      if(def_rate!=='') pb_getE(def_rate).click();
    } filter_parcel('<?php echo $parceltype;?>');

    function toggle_item(elm) {
      if(elm.className.includes('inactv')) elm.classList.remove('inactv');
      else elm.classList.add('inactv');
      adj_product(elm.id);
    }

    function adj_product(prd) {
      if(!prd>0) return;
      var list=pb_getE('adj_product');
      prd=';'+prd;
      if(list.value.includes(prd)) {list.value=list.value.replace(prd,'');}
      else list.value+=prd;
    }
    
    function pb_remove(p) {
      p.style.background='#c71585';
      setTimeout(function(){p.classList.add('pb_del');},500);
      setTimeout(function(){p.remove();},1000);
    }
  </script><?php 
}

function espb_troubleshoot_mode() {if(current_user_can('manage_options') && espb_is_path('trb_espb=')) return $_GET['trb_espb']; else return 0;}


function espb_fetch_delivery_status($rpt_id=0,$order_id=0) {
  if(isset($_GET['order_id'])) return;
  $test=espb_troubleshoot_mode();
  $fetch_freq=get_option('pb_fetch_freq'); if(empty($fetch_freq)) $fetch_freq=0;
  $rpt_id=intval($rpt_id);
  $order_id=intval($order_id);
  if(($rpt_id+$order_id)>0) $target=11; else $target=0;
  $merchant_id=get_option('pb_merchant_id');
  if($target<1 && ($fetch_freq<0 || empty($merchant_id))) return;

  if($test<1 && $target<1) {
    if(get_transient('fetch_delivery_status')) return;
    else {
      if($fetch_freq<10) $fetch_freq=10;
      if($fetch_freq>90) $fetch_freq=90;
      $fetch_freq=$fetch_freq*60;
      set_transient('fetch_delivery_status',1,$fetch_freq);
    }
  }
  ignore_user_abort(true);
  $sms_updates=get_option('pb_sms_updates');
  $last_type=get_option('fetch_delivery_status_last_type');
  $max_id_condition=$grp_condition=$max_id='';

  if($rpt_id>0) $grp_condition="AND m.meta_value LIKE '%i:$rpt_id;%' GROUP BY m.meta_id";
  if($order_id>0) $grp_condition="AND o.ID=$order_id";
  if($target<1) {
    if(empty($last_type) && $sms_updates>0) {
      $last_type='_subscribed';
      if($sms_updates==1) $grp_condition="GROUP BY m.meta_id HAVING subscribe>0";
      if($sms_updates==2) $grp_condition="GROUP BY m.meta_id HAVING LENGTH(IFNULL(phone,''))>=10";
    }
    elseif($sms_updates>0) {
      $last_type='';
      if($sms_updates==1) $grp_condition="GROUP BY m.meta_id HAVING IFNULL(subscribe,0)=0";
      if($sms_updates==2) $grp_condition="GROUP BY m.meta_id HAVING LENGTH(IFNULL(phone,''))=0";
    }

    update_option("fetch_delivery_status_last_type",$last_type,'no');
    $max_id=get_option("fetch_delivery_status_max_id$last_type");
    if(!empty($max_id)) $max_id_condition=" AND m.meta_id<$max_id ";
  }

  $ship_query="
    SELECT o.ID order_id
    ,o.post_type channel
    ,o.post_status
    ,m.meta_id meta_id
    -- ,DATE_FORMAT(MAX(s.meta_value),'%Y-%m-%d')shipped
    -- ,d.meta_value delivery_est
    ,'outbound' dir
    ,IFNULL(IFNULL((SELECT meta_value FROM wp_postmeta WHERE meta_key='_shipping_first_name' AND post_id=o.ID LIMIT 1),(SELECT meta_value FROM wp_postmeta WHERE meta_key='_billing_first_name' AND post_id=o.ID LIMIT 1)),'')fname
    ,(SELECT 1 FROM wp_postmeta JOIN wp_options ON option_name=CONCAT('sms_sb_',meta_value) WHERE meta_key='_billing_phone' AND post_id=o.ID LIMIT 1)subscribe
    ,(SELECT meta_value FROM wp_postmeta LEFT JOIN wp_options ON option_name=CONCAT('pb_unsb_',meta_value) WHERE option_name IS NULL AND meta_key='_billing_phone' AND post_id=o.ID LIMIT 1)phone
    ,(SELECT LOWER(meta_value) FROM wp_postmeta LEFT JOIN wp_options ON option_name=CONCAT('pb_unsb_',meta_value) WHERE option_name IS NULL AND meta_key='_billing_email' AND post_id=o.ID LIMIT 1)email
    ,IFNULL(m.meta_key,'')label
    ,IFNULL(m.meta_value,'')label_meta
    FROM wp_posts o
    JOIN wp_postmeta s ON s.post_id=o.ID AND s.meta_key='_last_shipped' AND s.meta_value>=NOW()-INTERVAL 30 DAY
    LEFT JOIN wp_postmeta d ON d.post_id=o.ID AND d.meta_key='delivery_est' AND DATE_FORMAT(d.meta_value,'%Y-%m-%d')>DATE_FORMAT(s.meta_value,'%Y-%m-%d')
    JOIN wp_postmeta m ON m.post_id=o.ID AND m.meta_key LIKE '_pb_label_meta%' AND m.meta_value NOT LIKE '%inbound%' AND m.meta_value NOT LIKE '%cancel%' AND m.meta_value NOT LIKE '%delivered,%'
    WHERE o.post_type IN ('import_order','shop_order')
    AND (LENGTH('$target')>1 OR (d.post_id IS NULL OR DATE_FORMAT(d.meta_value,'%Y-%m-%d') BETWEEN DATE_FORMAT(NOW()-INTERVAL 3 DAY,'%Y-%m-%d') AND DATE_FORMAT(NOW()+INTERVAL 15 DAY,'%Y-%m-%d')))
    AND o.post_status NOT IN ('wc-cancelled','trash')
    $max_id_condition
    $grp_condition

    UNION
    SELECT o.ID order_id
    ,o.post_type channel
    ,o.post_status
    ,m.meta_id meta_id
    ,'inbound' dir
    ,IFNULL(IFNULL((SELECT meta_value FROM wp_postmeta WHERE meta_key='_shipping_first_name' AND post_id=o.ID LIMIT 1),(SELECT meta_value FROM wp_postmeta WHERE meta_key='_billing_first_name' AND post_id=o.ID LIMIT 1)),'')fname
    ,(SELECT 1 FROM wp_postmeta JOIN wp_options ON option_name=CONCAT('sms_sb_',meta_value) WHERE meta_key='_billing_phone' AND post_id=o.ID LIMIT 1)subscribe
    ,(SELECT meta_value FROM wp_postmeta LEFT JOIN wp_options ON option_name=CONCAT('pb_unsb_',meta_value) WHERE option_name IS NULL AND meta_key='_billing_phone' AND post_id=o.ID LIMIT 1)phone
    ,(SELECT LOWER(meta_value) FROM wp_postmeta LEFT JOIN wp_options ON option_name=CONCAT('pb_unsb_',meta_value) WHERE option_name IS NULL AND meta_key='_billing_email' AND post_id=o.ID LIMIT 1)email
    ,IFNULL(m.meta_key,'')label
    ,IFNULL(m.meta_value,'')label_meta
    FROM wp_posts o
    JOIN wp_postmeta m ON m.post_id=o.ID AND m.meta_key LIKE '_pb_label_meta%' AND m.meta_value LIKE '%inbound%' AND m.meta_value NOT LIKE '%cancel%' AND m.meta_value NOT LIKE '%returnToSender%' AND m.meta_value NOT LIKE '%delivered,%'
    WHERE o.post_type IN ('import_order','shop_order')
    AND o.post_date>DATE_FORMAT(NOW()-INTERVAL 60 DAY,'%Y-%m-%d')
    AND o.post_status NOT IN ('wc-cancelled','trash')
    $max_id_condition

    ORDER BY meta_id DESC
    LIMIT 50;
  ";
  $shipments=espb_r($ship_query);

  if($shipments) {
    $current_time=current_time('mysql');
    $now=strtotime(current_time('mysql'));
    $current_date=substr($current_time,0,10);
    $url=site_url();
    $site_name=get_bloginfo('name');
    $admin_email=get_option('pb_admin_emails');
    if(empty($admin_email)) $admin_email=get_option('admin_email');
    $admin_exc_updates=get_option('pb_admin_exc_updates'); if(!empty($admin_exc_updates)) $admin_exc_updates=1;
    $admin_rtn_updates=get_option('pb_admin_rtn_updates'); if(!empty($admin_rtn_updates)) $admin_rtn_updates=1;
    $email_updates=get_option('pb_email_updates'); if(!empty($email_updates)) $email_updates=1;
    $email_exc_updates=get_option('pb_email_exc_updates'); if(!empty($email_exc_updates)) $email_exc_updates=1;
    $email_rtn_updates=get_option('pb_email_rtn_updates'); if(!empty($email_rtn_updates)) $email_rtn_updates=1;
    $sms_exc_updates=get_option('pb_sms_exc_updates');
    $sms_rtn_updates=get_option('pb_sms_rtn_updates');
    $domain=str_replace('www.','',explode("//",$url,2)[1]);
    $to=$log_status='';
    if(class_exists('WP_Better_Emails')) $email_type='plain'; else $email_type='html';
    $headers=array("Content-Type:text/$email_type; charset=UTF-8","From: $site_name <support@$domain>","Reply-To: No Reply <no-reply@$domain>");

    foreach($shipments as $s) {
      $exception=$reship=$rtn_del=0;
      $max_id=$s->meta_id;
      $msg=$sms=$phone=$new_status=$status=$rtn_accp='';
      $dir=$s->dir;
      $fname=ucwords(strtolower($s->fname));
      $channel=$s->channel;
      $order_status=$s->post_status;
      $order_id=$s->order_id;
      $email=$s->email;
      $subscribe=$s->subscribe;
      if($sms_updates==1 && $subscribe>0) $phone=substr(preg_replace("/[^0-9]/","",$s->phone),-10);
      elseif($sms_updates==2) $phone=substr(preg_replace("/[^0-9]/","",$s->phone),-10);
      $tracking_num=str_replace('_pb_label_meta_','',$s->label);
      $lm=$carrier=$status='';
      if(!empty($s->label_meta)) {
        $lm=$s->label_meta;
        $lm=unserialize($lm);
        $carrier=$lm[1];
        if(isset($lm['st'])) $status=$lm['st'];
      }
      if(stripos($status,'delivered')===false) $new_status=espb_track_status($order_id,$carrier,$tracking_num,'',$test); // Get Status
      if($test>0) {$log_status.="<br>OrderID: $order_id, Tracking: $tracking_num, Status: $status"; if($new_status!=$status) $log_status.=", New Status: $new_status";}

      if(!empty($new_status) && $new_status!=$status) {

        if($dir=='outbound' && $channel=='shop_order' && stripos('InTransit,Acceptance',$new_status)!==false) {
          if($order_status=='wc-processing') espb_complete_wc_order($order_id); // Send WC completed email
          elseif($order_status=='wc-completed' && $email_updates>0 && empty(get_transient("completed_email_sent_$order_id")) && (stripos('Manifest,Pending',$status)!==false || empty($status))) { // Send Tracking email for reships
            if($to==$email || stripos($email,'@')===false) continue;
            $to=$email;
            $msg="Your $site_name shipment is in transit.";
            $subject=$msg;
            if(function_exists('espb_track_url')) $track_url=espb_track_url('',$tracking_num); else $track_url="https://parcelsapp.com/en/tracking/$tracking_num";
            $body="Hi $fname,<br><br>$msg<br><br>$carrier tracking number: <a href='$track_url' target='_blank'>$tracking_num</a><br><br>This is an automated notification related to order ID $order_id. This mailbox cannot accept replies. <a href='$url/?espb_unsubscribe_email=$email' target='_blank'>Unsubscribe</a>";
            if(get_transient("email_q_$email")) delete_transient("email_q_$email");
            set_transient("email_q_$email",array("$msg","$body",$headers),43200); // Queue Email
          }
        }

        if($dir=='outbound' && $new_status!='InTransit') {
          if(stripos($new_status,',')!==false) {$delivery_date=substr($new_status,-10); $new_status=str_replace(", $delivery_date","",$new_status);} else $delivery_date=$current_date;
          if(!empty($new_status) && $delivery_date==$current_date && stripos('OutForDelivery,DeliveryAttempt,Delivered,Exception,returnToSender,ReadyForPickup',$new_status)!==false) { // Delivery event today
            if(function_exists('espb_track_url')) $track_url=espb_track_url('',$tracking_num); else $track_url="https://parcelsapp.com/en/tracking/$tracking_num";
            $new_status=trim(preg_replace('/(?<!\ )[A-Z]/',' $0',$new_status)); // Parse into separate words

            if(stripos($new_status,'Delivered')!==false) $msg="Your $site_name shipment has been $new_status!";
            elseif(stripos($new_status,'Out')!==false) $msg="Your $site_name shipment is $new_status.";
            elseif(stripos($new_status,'Exception')!==false) {$msg="Your $site_name shipment has an $new_status.";$exception=1;}
            elseif(stripos($new_status,'returnToSender')!==false) {$msg="Your $site_name shipment is being returned.";$exception=2;}
            elseif(stripos($new_status,'Attempt')!==false) $msg="Your $site_name shipment had a $new_status.";
            elseif(stripos($new_status,'Ready')!==false) $msg="Your $site_name shipment is $new_status.";
            if(empty($msg)) $msg="Your $site_name shipment has a new event, $new_status:";

            if($exception>0 && $admin_exc_updates>0) {
              $subject=$msg;
              $body="Hi PB Shipping Admin,<br><br>$msg<br><br>$carrier tracking number: <a href='$track_url' target='_blank'>$tracking_num</a><br><br>This is an automated notification related to order ID $order_id.";
              wp_mail($admin_email,$subject,$body,$headers); //$to,$subject,$body,$headers
            }

            if($exception<=1 && $channel=='shop_order' && ($sms_updates>0 || $email_updates>0)) {
              if((($sms_updates>0 && $exception<1) || ($exception>0 && $sms_exc_updates>0)) && strlen($phone)>=10 && date('H',$now)<20) {
                $sms="$msg $track_url";
                if(get_transient("sms_q$phone")) delete_transient("sms_q$phone");
                $transient=set_transient("sms_q$phone",array("$sms",'0','prod API'),43200); // Queue SMS
                if($test>0) $log_status.="<br>OrderID: $order_id, Tracking: $tracking_num, Status: $status, Phone: $phone, Transient: $transient, SMS: $sms";
              }
              elseif((($email_updates>0 && $exception<1) || ($exception>0 && $email_exc_updates>0))) {
                if($to==$email || stripos($email,'@')===false) continue;
                $to=$email;
                $subject=$msg;
                $body="Hi $fname,<br><br>$msg<br><br>$carrier tracking number: <a href='$track_url' target='_blank'>$tracking_num</a><br><br>This is an automated notification related to $site_name order ID $order_id. This mailbox cannot accept replies. <a href='$url/?espb_unsubscribe_email=$email' target='_blank'>Unsubscribe</a>";
                if(get_transient("email_q_$email")) delete_transient("email_q_$email");
                set_transient("email_q_$email",array("$subject","$body",$headers),43200); // Queue Email
              }
            }
          }
        }

        if($dir=='inbound' && stripos('InTransit,Acceptance,Delivered',$new_status)!==false) {
          if(stripos($new_status,',')!==false) {$delivery_date=substr($new_status,-10); $new_status=str_replace(", $delivery_date","",$new_status);}
          if(function_exists('espb_track_url')) $track_url=espb_track_url('',$tracking_num); else $track_url="https://parcelsapp.com/en/tracking/$tracking_num";
          $new_status=trim(preg_replace('/(?<!\ )[A-Z]/',' $0',$new_status)); // Parse into separate words

          if(stripos($new_status,'Delivered')!==false) {$msg="Your $site_name return has been $new_status!";$rtn_del=1;}
          elseif(stripos($new_status,'Acceptance')!==false) $msg="Your $site_name return has been accepted.";
          elseif(stripos($new_status,'InTransit')!==false) $msg="Your $site_name return is now in transit.";
          if(empty($msg)) {$msg="Your $site_name shipment has a new event, $new_status:";$rtn_del=1;}

          if($admin_rtn_updates>0) {
            $subject=$msg;
            $body="Hi PB Shipping Admin,<br><br>$msg<br><br>$carrier tracking number: <a href='$track_url' target='_blank'>$tracking_num</a><br><br>This is an automated notification related to order ID $order_id.";
            wp_mail($admin_email,$subject,$body,$headers); //$to,$subject,$body,$headers
            $rtn_accp=get_transient("completed_return_sent_$order_id");
          }

          if(empty($rtn_del) && empty($rtn_accp) && $channel=='shop_order' && ($sms_rtn_updates>0 || $email_rtn_updates>0)) {
            set_transient("completed_return_sent_$order_id",$current_time,604800);
            if($sms_rtn_updates>0 && strlen($phone)>=10 && date('H',$now)<20) {
              $sms="$msg $track_url";
              if(get_transient("sms_q$phone")) delete_transient("sms_q$phone");
              $transient=set_transient("sms_q$phone",array("$sms",'0','prod API'),43200); // Queue SMS
              if($test>0) $log_status.="<br>OrderID: $order_id, Tracking: $tracking_num, Status: $status, Phone: $phone, Transient: $transient, SMS: $sms";
            }
            elseif($email_rtn_updates>0) {
              if($to==$email || stripos($email,'@')===false) continue;
              $to=$email;
              $subject=$msg;
              $body="Hi $fname,<br><br>$msg<br><br>$carrier tracking number: <a href='$track_url' target='_blank'>$tracking_num</a><br><br>This is an automated notification related to $site_name order ID $order_id. This mailbox cannot accept replies. <a href='$url/?espb_unsubscribe_email=$email' target='_blank'>Unsubscribe</a>";
              if(get_transient("email_q_$email")) delete_transient("email_q_$email");
              set_transient("email_q_$email",array("$subject","$body",$headers),43200); // Queue Email
            }
          }
        }

      }
    }
  }
  
  if($test>0) if(!empty($log_status)) echo $log_status;
  
  if($target<1) {
    if(!is_array($shipments)) delete_option("fetch_delivery_status_max_id$last_type");
    elseif(is_array($shipments)) if(count($shipments)>=50) update_option("fetch_delivery_status_max_id$last_type",$max_id,'no'); else delete_option("fetch_delivery_status_max_id$last_type");
  }
}
add_action('wp_loaded','espb_fetch_delivery_status');

function espb_email_queue() {
  if(!espb_is_path('wp-cron')) return false;
  ignore_user_abort(true);
  $send_email=get_option('pb_email_updates');
  if(empty($send_email)) return;
  $now=strtotime(current_time('mysql'));
  $group_count=get_option('email_group_count'); if(empty($group_count)) $group_count=0;

  $last_email_sent=get_option('last_email_sent'); if(empty($last_email_sent)) $last_email_sent='no-email';
  $queued=espb_r("SELECT * FROM wp_options WHERE option_name LIKE '_transient_email_q_%' AND LENGTH(REPLACE(option_name,'_transient_email_q_',''))>6 AND option_name NOT LIKE '%$last_email_sent' ORDER BY 1 ASC LIMIT 1;");
  if(empty($queued)) {if(function_exists('custom_email_plugin')) $wait=custom_email_plugin(0); return;} // Nothing Queued

  $group_limit=6; // Messages at a time
  $msg_spacing=11; // Sent no faster than x seconds apart
  $group_spacing=1; $group_spacing=($group_spacing*60); // Grouped at least X minutes apart
  $group_date=get_option('email_group_date'); if(empty($group_date)) $group_date=0;
  $msg_date=get_option('email_msg_date'); if(empty($msg_date)) $msg_date=0;

  if($group_count<$group_limit // Group limit not exceeded
    && $now>($group_date+$group_spacing) // Group buffer
    && $now>($msg_date+$msg_spacing) // Msg buffer
  ) {
    if(function_exists('custom_email_plugin')) {if(!get_transient("custom_email_plugin")) {$wait=custom_email_plugin(1,20); return;} else $wait=custom_email_plugin(1,10);}

    // Get First Queued Item
    $group_count++;
    foreach($queued as $q) {
      $option_id=$q->option_id;
      $option_name=$q->option_name;
      $to=str_replace('_transient_email_q_','',$option_name);
      $msg=$q->option_value;
    }

    // Record Time & Group Counts
    if($group_count>=$group_limit) {
      update_option('email_group_date',$now);
      update_option('email_group_count',0);
    } else update_option('email_group_count',$group_count);
    update_option('email_msg_date',$now);

    // Skip dups
    delete_transient("email_q_$to");
    usleep(mt_rand(500,200000));
    $last_email_sent=get_option('last_email_sent');
    if($last_email_sent==$to) return;
    update_option("last_email_sent",$to,'no');

    // Send Message
    $msg=unserialize($msg);
    wp_mail($to,$msg[0],$msg[1],$msg[2]); //$subject,$body,$headers
  } else {if(function_exists('custom_email_plugin')) $wait=custom_email_plugin(0); return;}
}
add_action('wp_loaded','espb_email_queue');

function espb_sms_queue() {
  if(!espb_is_path('wp-cron')) return false;
  ignore_user_abort(true);
  $send_sms=get_option('pb_sms_updates');
  if($send_sms<1) return;
  $now=strtotime(current_time('mysql'));
  $group_response=get_option('sms_group_response'); if(empty($group_response) || $group_response<$now-3600) {espb_sms_responses(); return;}
  $group_count=get_option('sms_group_count'); if(empty($group_count)) $group_count=0;
  if(date('H',$now)<10 || date('H',$now)>20) return; // Between 10am & 9pm

  $day=date('m',$now).date('d',$now);
  $last_sms_tel=get_transient('last_sms_tel'); if(empty($last_sms_tel)) $last_sms_tel='none';
  $queued=espb_r("SELECT * FROM wp_options WHERE option_name LIKE '_transient_sms_q%' AND LENGTH(REPLACE(option_name,'_transient_sms_q',''))=10 AND option_name NOT LIKE '%$last_sms_tel' ORDER BY 1 ASC LIMIT 1;");
  if(empty($queued)) return; // Nothing Queued

  $group_limit=5; // Messages at a time
  $msg_spacing=1; $msg_spacing=($msg_spacing*60); // Sent no faster than X minutes apart
  $group_spacing=22; $group_spacing=($group_spacing*60); // Grouped at least X minutes apart
  $group_date=get_option('sms_group_date'); if(empty($group_date)) $group_date=0;
  $msg_date=get_option('sms_msg_date'); if(empty($msg_date)) $msg_date=0;

  if($group_count<$group_limit // Group limit not exceeded
    && $now>($group_date+$group_spacing) // Group buffer
    && $now>($msg_date+$msg_spacing) // Msg buffer
  ) {

    // Get First Queued Item
    $group_count++;
    foreach($queued as $q) {
      $option_id=$q->option_id;
      $option_name=$q->option_name;
      $to=substr($option_name,-10);
      $msg=$q->option_value;
    }

    // Record Time & Group Counts
    if($group_count>=$group_limit) {
      update_option('sms_group_date',$now);
      update_option('sms_group_count',0);
      update_option('sms_group_response',0);
    } else update_option('sms_group_count',$group_count);
    update_option('sms_msg_date',$now);

    // Send Message
    $msg=unserialize($msg);
    if(delete_transient("sms_sq$to")) return;
    espb_r("UPDATE wp_options SET option_name='_transient_sms_sq$to' WHERE option_id='$option_id' AND option_name='_transient_sms_q$to';");
    espb_send_text($to,$msg[0],$msg[1],$msg[2]);
  }
}
add_action('wp_loaded','espb_sms_queue');


// Send to MessageBird
function espb_send_text($to,$msg,$admin=0,$action='') {
  $mb_phone=get_option('pb_mb_phone');
  $prod_access_key=get_option('pb_mb_prod_key');
  $dev_access_key=get_option('pb_mb_dev_key');
  
  if(empty($to) || empty($msg) || strlen($to)<10) return "&#9888; Invalid parameters";
  ignore_user_abort(true);
  
  $queue_status=get_transient("sms_sq$to");
  if(!empty($queue_status)) {if($queue_status=='Pending') return; else {$q_type="sms_sq$to"; set_transient($q_type,"Pending",172800);}} else $q_type=''; // else $q_type="sms_r$to"; // Record all sent messages

  $to=preg_replace('/\D/','',$to);
  if($action!=='prod API') $access_key=$dev_access_key;
  else $access_key=$prod_access_key;
  if(empty($access_key)) return;

  $url="https://".convert_uudecode("><F5S=\"YM97-S86=E8FER9\"YC;VTO;65S<V%G97,O`")."?_method=POST&originator=1$mb_phone&access_key=$access_key&recipients=1$to&body=".urlencode($msg);
  $now=time();
  $last_sms=get_option('last_sms');
  update_option("last_sms",$now);
  
  //set_transient("last_sms_reply_$to",$msg,172800); // Record responses
  if($last_sms>0) if($last_sms>($now-2)) {$sleep=intval(3-($last_sms-$now)); sleep($sleep); $now=time(); update_option("last_sms",$now); if($admin>0) echo "<br>Delayed";}

  if(!empty($queue_status)) {
    $last_sms_tel=get_transient('last_sms_tel');
    if($last_sms_tel==$to) return;
    set_transient("last_sms_tel",$to,1000);
  }

  try{$response=file_get_contents($url);}
  catch(Exception $exception) {return "<b>&#9888; Exception</b>:<br>$exception<br><br>Response: $response<br><br><b>Target</b>:<br>$url";}
  $msg_id=substr($response,7,32);
  if(!empty($q_type)) set_transient($q_type,"id:$msg_id",172800);
  if($admin>0) return "<b>&#10003; Success</b>:<br>$response<br><br><b>Target</b>:<br>$url"; else return "Success";
}


/* SMS Responses - endpoint: https://rest.messagebird.com/messages/eedae846cf2e49b7854cdee802c497f2?access_key=bMWS2DRbJzhe0qzGWSDX7aEv0 
  sms_r = run now (no queue)
  sms_q = queued
  sms_sq = queued and sent
  sms_f = failed 
*/
function espb_sms_responses() {
  $prod_access_key=get_option('pb_mb_prod_key');
  $timestamp=current_time('mysql');
  $now=strtotime($timestamp);
  $group_date=get_option('sms_group_date');
  if(!empty($group_date) && $now<$group_date+30) return;
  update_option('sms_group_response',$now);
  $sent=espb_r("SELECT * FROM wp_options WHERE (option_name LIKE '_transient_sms_r%' OR option_name LIKE '_transient_sms_sq%') AND option_value LIKE 'id:%' ORDER BY 1 ASC;");

    if(empty($sent)) return;
    foreach($sent as $s) {
      $option=str_replace('_transient_','',$s->option_name);
      $msg_id=substr($s->option_value,3);
      unset($statusDatetime);
      $url="https://".convert_uudecode("><F5S=\"YM97-S86=E8FER9\"YC;VTO;65S<V%G97,O`")."$msg_id?access_key=$prod_access_key";
      try{$response=file_get_contents($url);}
      catch(Exception $exception) {return "&#9888; $exception<br>Response:$response";}

      $response=explode(',',str_replace(', ',' ',str_replace('"','',$response)));
      foreach($response as $k=>$v) {  // Assign variable to each item in response
        if(!in_like($v,'date,total,body,recipient')) continue;
        $v=explode(',',preg_replace('/:/',',',$v,1));
        ${$v[0]}=$v[1];
      }
      if(isset($statusDatetime)) $statusDatetime=substr($statusDatetime,0,19); else continue;
      if(isset($recipient)) $recipient=substr($recipient,-10);

      if($totalDeliveredCount>0) $response="Delivered";
      elseif($totalDeliveryFailedCount>0 && isset($recipient)) {
        $response="Failed";
        if(stripos($option,'sms_sq')!==false && !get_transient("sms_f$recipient")) {$response="Failed & Requeued"; delete_transient($option); set_transient("sms_q$recipient",array($body,-1,'prod API'),604800);}
        set_transient("sms_f$recipient",$response,604800);
      } else $response="Sent";
      $response.=" at ~$timestamp. txt:$body";
      set_transient($option,$response,172800);
    }
}

function espb_unsubscribe_email_updates() {
  if(isset($_GET['espb_unsubscribe'])) $contact=sanitize_text_field($_GET['espb_unsubscribe']); else return;
  if(function_exists('atb_check_abuse')) {
    $user_ip=sanitize_text_field($_SERVER['REMOTE_ADDR']);
    $abuse_score=atb_check_abuse($user_ip);
    if($abuse_score>0) { atb_report_abuse($user_ip,'10','Contact form spam.'); return;}
  }
  $ok=$phone=0;
  if(strlen($contact)>=6 && strlen($contact)<=50 && stripos($contact,'@')!==false && stripos($contact,'.')!==false) $ok=1;
  else {
    $phone=substr(preg_replace("/[^0-9]/","",$contact),-10);
    if(strlen($phone)==10) {$ok=1;$contact=$phone;}
  }
  if($ok<1) return;
  $url=site_url();
  $date=current_time('mysql');
  if(get_option("pb_unsb_$contact")) {
    delete_option("pb_unsb_$contact");
    echo "<script type='text/javascript'>if(!confirm('You have successfully opted in to shipping updates.\\nPress OK to continue, or cancel to opt out.')) window.location.href='$url/?espb_unsubscribe=$contact';</script>";
  } else {
    update_option("pb_unsb_$contact","$date",'no');
    delete_transient("email_q_$contact");
    delete_transient("sms_q$contact");
    echo "<script type='text/javascript'>if(!confirm('You have successfully opted out of shipping updates.\\nPress OK to continue, or cancel to opt back in.')) window.location.href='$url/?espb_unsubscribe=$contact';</script>";
  }
}
add_action('wp_footer','espb_unsubscribe_email_updates');

add_action('wp_footer','espb_troubleshoot_echo');
function espb_troubleshoot_echo() {
	if(espb_troubleshoot_mode()>1) {
		echo get_option('pb_cart_rate_troubleshoot');
		echo get_option('pb_single_rate_troubleshoot');
	}
}