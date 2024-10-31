<?php if(!defined('ABSPATH')) exit;

function espb_ship_order() {
  // Check Permissions
  if(!is_user_logged_in()) {header("Location: ".site_url()."/wp-login.php?redirect_to=".urlencode($_SERVER['REQUEST_URI'])); exit();}
  if(!current_user_can('edit_shop_orders')) {echo 'Insufficient permissions.'; exit();}
  espb_popup_css();

  // Validate Parameters
  if(isset($_GET['order_id'])) $order_id=intval($_GET['order_id']); else header("location: ".admin_url('admin.php?page=pb-queue'));
  if(empty($order_id)) header("location: ".admin_url('edit.php?post_type=shop_order'));
  if(isset($_REQUEST['override'])) $override=intval($_REQUEST['override']); else $override=0;
  

  if(empty(get_option('pb_developer_id'))||empty(get_option('pb_client_id'))) {
    if(current_user_can('manage_options')) header("location: ".admin_url('options-general.php?page=pb-admin'));
    else {echo 'Plugin not configured.'; exit();}
  }

  // Collect Variables
  $user_id=get_current_user_id();
  $site_name=get_bloginfo('name');
  $white_label=get_option('pb_white_label');
  $site_url=site_url();
  $logo_url=espb_site_logo(); if(empty($logo_url)) $logo_url=get_site_icon_url();
  $site_url=site_url();
  $slip_note=get_option('pb_slip_note');
  global $parceltype; 
  $serviceid=$parceltype=$shipped=$use_val_address=$services=$tracking_number=$label=$sopt=$val_addressLines_1=$val_addressLines_2=$val_cityTown=$val_stateProvince=$val_postalCode=$val_countryCode=$induction_zip=$user_induction_zip=$sys_calc_dim=$sys_calc_weight=$user_cached_dim=$user_cached_weight=$user_cached_alert=$adj_product=$rtn_cache=$ship_date='';
  $val_status='NOT VALIDATED_';
  $submit_type=$adj_ins_value=$rtn=$prv_rtn=0;
  if(!isset($_POST['submit_type']) && !empty(get_post_meta($order_id,'_last_shipped',true))) $wait=espb_fetch_delivery_status(0,$order_id);
  
  $weight_unit=get_option('woocommerce_weight_unit');
  $dim_unit=get_option('woocommerce_dimension_unit');
  $order=espb_order_data($order_id);
    if(!$order) {$order_status=get_post_status($order_id); if(!empty($order_status)) echo "Order status: ".strtoupper($order_status); else echo "Order $order_id not found."; exit();}
    $order_type=$order[0]->order_type;
    $shipped=$order[0]->shipped;
    $label_history=$order[0]->label_history;
    $method=$order[0]->method;
    $method_id=$order[0]->method_id;
    $method_cost=$order[0]->method_cost;
    $shipto=$order[0]->shipto;
    $company=$order[0]->company;
    $address1=$order[0]->address1;
    $address2=$order[0]->address2;
    $city=$order[0]->city;
    $state=$order[0]->state;
    $zip=$order[0]->zip;
    $country=strtoupper($order[0]->country);
    $email=$order[0]->email;
    $phone=$order[0]->phone;
    $product_list=$order[0]->product_list;
    $variation_cache=$order[0]->variation_cache;
    $product_cache=$order[0]->product_cache;
    $ins_value=number_format($order[0]->total,2,'.','');
    $comments=$order[0]->comments;

  $products=espb_product_data($order_id);
    global $weight,$length,$width,$height;
    $weight=$adj_prd=0;
    $length=$width=$height=$mx_length=$mx_width=$mx_height=$vol=.1;

    // Product DIM cache
    if(isset($_POST['adj_product'])) {
      $adj_product=trim(sanitize_text_field($_POST['adj_product']),',');
      if($adj_product!==$variation_cache) {$adj_prd=1; $variation_cache=$adj_product;}
    }
    $cached_dim=get_option("pb_dim_$variation_cache");
    if(empty($cached_dim)) $cached_dim=get_option("pb_dim_$product_cache");

    foreach($products as $p) {
      if($p->variation_id>0 && stripos(",$variation_cache","{$p->variation_id}x")==false) continue; // Skip if variation removed from shipment
      if($p->variation_id<1 && stripos(",$product_cache","{$p->product_id}x")==false) continue; // Skip if product removed from shipment
      $qty=$p->qty;
      $weight+=$p->total_weight;
      $dim=array($p->length,$p->width,$p->height);
      $vol+=pb_dim_to_vol($dim,$qty);
      sort($dim);
      if($mx_length<$dim[2]) $mx_length=$dim[2];
      if($mx_width<$dim[1]) $mx_width=$dim[1];
      if($mx_height<$dim[0]) $mx_height=$dim[0]*$qty;
    }
    $width=sqrt($vol);
    $length=$width;
    $height=($width/2);
    if($length<$mx_length) $length=$mx_length;
    if($width<$mx_width) $width=$mx_width;
    if($height<$mx_height) $height=$mx_height;
    $dim=array($length,$width,$height);
    sort($dim);
    $length=number_format(ceil($dim[2]));
    $width=number_format(ceil($dim[1]));
    $height=number_format(ceil($dim[0]));
    $weight+=pb_box_weight($vol);

    $weight=number_format($weight,1,'.','');
    $sys_weight=$weight;
    $sys_calc_weight="Sys: {$sys_weight}$weight_unit";
    $sys_calc_dim="Sys: $length x $width x $height";

    // Cached DIMs
    if(!empty($cached_dim)) {
      $length=$cached_dim[0];
      $width=$cached_dim[1];
      $height=$cached_dim[2]; $user_cached_dim="\nUser: $length x $width x $height";
      
      $user_weight=$cached_dim[3]; $user_cached_weight="\nUser: {$user_weight}$weight_unit";
      if(!isset($_POST['adj_product'])) if(($user_weight-$weight)>($weight*.335) || ($weight>$user_weight && abs($user_weight-$weight)>($weight*.1))) $user_cached_alert="if(confirm('The system weight for combined product is {$sys_weight}$weight_unit, vs {$user_weight}$weight_unit user input.\\nWould you like to use to system weight?')) {parcel_weight.value='$sys_weight';parcel_weight.onchange();}";
      $weight=$user_weight;
    }
    $weight=number_format($weight,2,'.','');

    // Get User Option Defaults
  if(isset($_POST['return'])) {$rtn=sanitize_text_field($_POST['return']); if($rtn>0) $rtn_cache='_rtn';}
  $env=get_option("pb_default_env_$user_id"); if(empty($env)) $env='sandbox';
  $pending=get_option("pb_default_pending_$user_id");
  $carrier=get_option("pb_default_carrier_$user_id");
  $parceltype=get_option("pb_default_{$carrier}_parceltype{$rtn_cache}_$user_id"); if(empty($parceltype) && $carrier!='USPS') $parceltype='PKG';
  $serviceid=get_option("pb_default_{$carrier}_{$parceltype}_{$method}_serviceid{$rtn_cache}_$user_id");
  $facility=get_option("pb_default_{$carrier}_facility{$rtn_cache}_$user_id"); if(empty($facility)) $facility=0;
  $services=get_option("pb_default_{$carrier}_{$parceltype}_{$method}_services{$rtn_cache}_$user_id");

  if(isset($_POST['submit_type']) && check_admin_referer('purchase_label','pb_purchase_label')) {
    $submit_type=intval($_POST['submit_type']);
    $color_scheme=sanitize_text_field($_POST['color_scheme']);
    if(!empty($_POST['product_list'])) $product_list=stripslashes(html_entity_decode($_POST['product_list']));
    $tracking_number=sanitize_text_field($_POST['tracking_number']);
    $env=sanitize_text_field($_POST['envir']);
    $carrier=sanitize_text_field($_POST['carrier']);
    $prv_rtn=sanitize_text_field($_POST['prev_rtn']);
    $prv_carrier=sanitize_text_field($_POST['prev_carrier']);

    if($carrier==$prv_carrier && $rtn==$prv_rtn) {
      if(isset($_POST['facility'])) $facility=sanitize_text_field($_POST['facility']); else $facility=0;
      $serviceid=sanitize_text_field($_POST['serviceid']);
      $parceltype=sanitize_text_field($_POST['parceltype']);
      $services=sanitize_text_field($_POST['services']);
    }
    $use_val_address=intval($_POST['use_val_address']);

    $address1=sanitize_text_field($_POST['address1']);
    $address2=sanitize_text_field($_POST['address2']);
    $city=sanitize_text_field($_POST['city']);
    $state=sanitize_text_field($_POST['state']);
    $zip=sanitize_text_field($_POST['zip']);
    $country=strtoupper(sanitize_text_field($_POST['country']));
    if(isset($_POST['pending'])) $pending=sanitize_text_field($_POST['pending']); else $pending=0;
    $ins_value=sanitize_text_field($_POST['ins_value']);

    $adj_weight=$adj_dim=0;
    if(isset($_POST['adj_weight'])) $adj_weight=intval($_POST['adj_weight']);
    if(isset($_POST['adj_dim'])) $adj_dim=intval($_POST['adj_dim']);
    if($adj_weight>0) $weight=sanitize_text_field($_POST['weight']);
    if(($adj_weight+$adj_dim)>0) {
      $length=sanitize_text_field($_POST['length']);
      $width=sanitize_text_field($_POST['width']);
      $height=sanitize_text_field($_POST['height']);
    }
    if($adj_prd<1) {
      update_option("pb_dim_$variation_cache",array($length,$width,$height,$weight),false);
      update_option("pb_dim_$product_cache",array($length,$width,$height,$weight),false);
    }
    $ship_date=sanitize_text_field($_POST['ship_date']); set_transient("pb_ship_date_$user_id",$ship_date,21600);

    // Update User Default Options
    update_option("pb_default_color_scheme_$user_id",$color_scheme);
    update_option("pb_default_pending_$user_id",$pending);
    update_option("pb_default_env_$user_id",$env);
    update_option("pb_default_carrier_$user_id",$carrier);
    update_option("pb_default_{$carrier}_parceltype{$rtn_cache}_$user_id",$parceltype);
    update_option("pb_default_{$carrier}_{$parceltype}_{$method}_serviceid{$rtn_cache}_$user_id",$serviceid);
    update_option("pb_default_{$carrier}_facility{$rtn_cache}_$user_id",$facility);
    update_option("pb_default_{$carrier}_{$parceltype}_{$method}_services{$rtn_cache}_$user_id",$services);

  } else { // User Option Defaults
    $color_scheme=get_option("pb_default_color_scheme_$user_id");
    $use_val_address=1;
    $services=get_option("pb_default_{$carrier}_services_$user_id");

    // Plugin Option Defaults
    if(!empty($method_id)) {
      $wc_method=get_option("pb_wc_method_$method_id");
      if($wc_method) {
        $carrier=$wc_method[0];
        $serviceid=explode('-',$wc_method[1])[0];
        $parceltype=explode('-',$wc_method[1])[1];
      }
    }
    if(empty($carrier)) $carrier='USPS';
    if(empty($ship_date)) $ship_date=get_transient("pb_ship_date_$user_id");
  }


  // Validate DIM
  if(!($weight*$length*$width*$height)>0 && $submit_type<2) $flag='Verify weight and dimensions.';
  if(empty($address1)||empty($city)||empty($state)||empty($zip)||empty($country)) $flag='Provide a shipping address.';

  // Validate Services
  if($submit_type<=1 && !isset($_POST['submit_type']) && $carrier=='USPS' && ($serviceid=='FCM'||$serviceid=='PM') && empty($rtn) && stripos($services,'DelCon')===false) $services.='DelCon,';

  // Create API Objects
  if(empty($flag)) {
    $fromAddress=espb_create_obj_fromAddress($facility);
    $toAddress=espb_create_obj_toAddress($address1,$address2,$city,$state,$zip,$country,$company,$shipto,$phone,$email);
    $toAddress_valid=espb_validate($order_id,$toAddress,$env,1);

    if(is_array($toAddress_valid)) {
      foreach($toAddress_valid as $key=>$value) {
        if(!is_array($value)) { if(espb_in_like($key,'city,state,postal,country,status')) ${'val_'.$key}=strtoupper($value); }
        else foreach($value as $k=>$v) if(!is_array($v)) ${'val_'.$key.'_'.($k+1)}=strtoupper($v);
      }
    } else $toAddress_err=$toAddress_valid;

    $adj_ins_value=$ins_value;
    if($ins_value<101) $adj_ins_value=101;
    //$weight=number_format($weight,0);
    $parcel=espb_create_obj_parcel($weight,$length,$width,$height,$services,$ins_value);
    $shipmentOptions=new stdClass();
    $shipper_id=get_option('pb_merchant_id'); if(empty($shipper_id) || $env=='sandbox') $shipper_id=get_option('pb_sandbox_merchant_id');
    $shipmentOptions=[array('name'=>'SHIPPER_ID','value'=>$shipper_id)];
    
    $client_id=get_option('pb_client_id')[$facility];
    $client_facility_id=get_option('pb_client_facility_id')[$facility];
    $carrier_facility_id=get_option('pb_carrier_facility_id')[$facility];
    
    // Induction Zip Logic
    $facility_zip=get_option('pb_client_facility_zip')[$facility];
    $facility_induction_zip=get_option('pb_client_facility_induction_zip')[$facility];
    if(isset($_POST['user_induction_zip'])) {$user_induction_zip=sanitize_text_field($_POST['user_induction_zip']); set_transient("pb_induction_zip_$user_id",$user_induction_zip,21600);}
    else $user_induction_zip=get_transient("pb_induction_zip_$user_id");

    if(!empty($user_induction_zip)) $induction_zip=$user_induction_zip;
    elseif(!empty($facility_induction_zip) && empty($rtn)) $induction_zip=$facility_induction_zip;
    if(!empty($induction_zip)) if(substr("$facility_zip",0,5)==$induction_zip) $induction_zip='';

    if($carrier=='NEWGISTICS') { // Assign Facility
      array_push($shipmentOptions,array('name'=>'CLIENT_FACILITY_ID','value'=>$client_facility_id));
      array_push($shipmentOptions,array('name'=>'CARRIER_FACILITY_ID','value'=>$carrier_facility_id));
      array_push($shipmentOptions,array('name'=>'CLIENT_ID','value'=>$client_id));
      $serviceid='PRCLSEL';
      $rates=espb_create_obj_rates($carrier,$serviceid,$parceltype,$services,$adj_ins_value,$induction_zip);
      $sopt="$client_id-$client_facility_id-$carrier_facility_id";
    }
    elseif($carrier!='USPS') {
      $rates=espb_create_obj_rates($carrier,'',$parceltype,$services,$adj_ins_value,$induction_zip);
    }
    else {
      if(empty($rtn)) array_push($shipmentOptions,array('name'=>'ADD_TO_MANIFEST','value'=>"True"));
      $rates=espb_create_obj_rates($carrier,'','',$services,$adj_ins_value,$induction_zip);
    }

    if($submit_type==1) array_push($shipmentOptions,array('name'=>"MINIMAL_ADDRESS_VALIDATION",'value'=>"True")); // Allow bypass address issues on purchase

    $shipment=new stdClass();
    if(!empty($rtn)) {
      $shipment->shipmentType='RETURN';
      $shipment->fromAddress=$toAddress;
      $shipment->toAddress=$fromAddress;
    } else {
      $shipment->fromAddress=$fromAddress;
      $shipment->toAddress=$toAddress;
    }
    $shipment->parcel=$parcel;
    $shipment->rates=[$rates];
    $shipment->shipmentOptions=$shipmentOptions;

    /* submit_type
    0=quote
    1=purchase
    2=reprint
    3=cancel
    */
    
    if($submit_type>=1 && (empty($shipped) || (!empty($shipped) && $override>0))) {
      if($use_val_address==1 && empty($rtn) && !empty($_POST['addressLines_1'])) {
        $toAddress->addressLines=array(sanitize_text_field($_POST['addressLines_1']),sanitize_text_field($_POST['addressLines_2']));
        $toAddress->cityTown=sanitize_text_field($_POST['cityTown']);
        $toAddress->stateProvince=sanitize_text_field($_POST['stateProvince']);
        $toAddress->postalCode=sanitize_text_field($_POST['postalCode']);
        $toAddress->countryCode=sanitize_text_field($_POST['countryCode']);
        $shipment->toAddress=$toAddress;
      }
      if($submit_type>=2) { 
        if($submit_type==3) $label=espb_cancel_label($order_id,$tracking_number,$env); 
        else {
          if(!isset($label_history)) $label='No History';
          else $label=espb_reprint_label($order_id,$tracking_number);
        }
      }
      else { // Print
        $rates=espb_create_obj_rates($carrier,$serviceid,$parceltype,$services,$adj_ins_value,$induction_zip);
        $shipment->rates=[$rates];
        
        $documents=new stdClass();
        $documents=[array('type'=>'SHIPPING_LABEL','contentType'=>'URL','size'=>'DOC_4X6','fileFormat'=>'PDF','printDialogOption'=>'EMBED_PRINT_DIALOG')];
        $shipment->documents=$documents;

        $cache_key="$carrier-$serviceid-$sopt-$parceltype-$adj_ins_value-$length-x-$width-x-$height-$weight-$services-$ins_value-$induction_zip-$city-$state-$zip-$rtn";
        $label=espb_ship($order_id,$cache_key,$shipment,$client_facility_id,$env,$carrier,$services,$pending,$rtn);
      }
    }
    if(empty($label)) { // Preview
      $cache_key="$carrier-$serviceid-$sopt-$length-x-$width-x-$height-$weight-$services-$ins_value-$induction_zip-$city-$state-$zip-$rtn";
      $rate_options=espb_rate($order_id,$serviceid,$cache_key,$shipment,$env,$carrier);
    }

  } else $rate_options="<div class='err'>$flag</div>";


  // Admin Interface ?>
    <!doctype html>
    <html lang="en-US">
      <head>
        <title>Shipping</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=2.0">
        <meta name='robots' content='noindex,nofollow'>
      </head>

      <?php echo espb_inc_css($color_scheme);?>

      <body>
      <div class='noprint' style='margin:0 1em 1em -1.5em'>
        <img class='logo' src='<?php if(!empty($white_label)) {if(!empty($logo_url)) echo $logo_url;} else echo plugins_url('pb-shipping/assets/icon-trns.png'); ?>'>
        <div style='display:inline-block;letter-spacing:.3em;font-variant-caps:all-petite-caps'><?php if(!empty($white_label)) {if(empty($logo_url)) echo "$site_name Shipping";} else echo 'Enterprise Shipping'; ?></div>
      </div>
      <div id='uploading' class='progress'>Loading</div>
      <div id='neon_cat' class='progress'><img src='<?php echo plugins_url('pb-shipping/assets/neon_cat.gif');?>'></div>

      <?php if(!empty($label)) { // Label 
        if($label!='No History') { ?>
        <div class='module label noprint'>
          <div class='title'>Label</div>
          <?php echo $label;?>
        </div><br>

        <?php } if($submit_type<3 && stripos($label,'error code')==false) { // Packing Slip ?>
        <div class='module slip'>
          <?php if($submit_type==1 && empty($rtn)) { ?><script type='text/javascript'>window.print();</script><?php } ?>
          <input type='button' value='Print Packing Slip' style='margin-left:2em' class='noprint' onclick="window.print();">
            <div>
              <?php if(!empty($logo_url)) echo "<img class='logo' style='float:right' src='$logo_url'>";?>
              <?php if(!empty($fromAddress->company)) echo $fromAddress->company.'<br>';?>
              <?php if(!empty($fromAddress->name)) echo $fromAddress->name.'<br>';?>
              <?php echo $fromAddress->addressLines[0]; if(!empty($fromAddress->addressLines[1])) echo ' '.$fromAddress->addressLines[1];?><br>
              <?php echo $fromAddress->cityTown;?>, <?php echo $fromAddress->stateProvince;?><br>
              <?php echo $fromAddress->postalCode;?> <?php echo $fromAddress->countryCode;?>
            </div>

            <div class='address'>
              <b style='font-size:.6em'>TO</b><br>
              <?php if(!empty($toAddress->name)) echo '<b>'.strtoupper($toAddress->name).'</b><br>';?>
              <?php if(!empty($toAddress->company)) echo $toAddress->company.'<br>';?>
              <?php echo $toAddress->addressLines[0]; if(!empty($toAddress->addressLines[1])) echo ' '.$toAddress->addressLines[1];?><br>
              <?php echo $toAddress->cityTown;?>, <?php echo $toAddress->stateProvince;?><br>
              <?php echo $toAddress->postalCode;?> <?php echo $toAddress->countryCode;?>
            </div>

          <div><?php echo $product_list;?></div>
          <?php if(!empty($slip_note)) echo "<div style='display:block;margin:1em 0;white-space:pre-line'>$slip_note</div>";?>
          <br>
          <?php echo espb_reprint_slip($order_id,$tracking_number,$method,$user_id); ?>
        </div><?php 
        } ?>
        <div class='form_control noprint'><input type='button' style='margin-top:1em' value='Go Back' onclick="window.location=location.href.replace(location.hash,'')+'&override=1';"></div><?php

      } else { // Initial Ship Preview  ?>

      <form name='ship' id='pb_ship' method='post' accept-charset='UTF-8 ISO-8859-1' style='margin:1em 0' onsubmit="pb_load_animation();">
        <?php if(isset($label_history)) { ?>
            <div class='module history'>
              <div class='title'>History</div>
              <?php echo espb_label_history($order_id,$label_history);?>
            </div><br>
        <?php }?>

        <div class='module method'>
          <div class='title'>Method</div>
          <div style='margin-left:4px'>
            <select name='facility' id='facility' onchange="pb_load_animation();document.getElementById('pb_ship').submit();">
              <option value='' disabled selected>Facility
              <?php echo espb_list_facility($facility);?>
            </select>
            <div style='float:right;font-size:.8em;padding:1em'>Customer Selected:<br><?php echo $method; if($method_cost) echo " $$method_cost";?></div>
            <select name='carrier' id='carrier' onchange="pb_load_animation();document.getElementById('pb_ship').submit();">
              <option value='' disabled selected>Carrier
              <?php echo espb_list_carrier($carrier);?>
            </select>
            <input type='hidden' name='prev_rtn' value='<?php echo $rtn;?>'>
            <input type='hidden' name='prev_carrier' value='<?php echo $carrier;?>'>
          <?php if(isset($rate_options)) echo $rate_options; ?>
          </div>
        </div><br>

        <div class='module dest'>
        <?php if(isset($toAddress_err)) echo $toAddress_err; ?>
          <div class='title'>Destination</div>
          <div style='float:right;<?php if(!isset($label_history) && empty($rtn) && !isset($_GET['override'])) echo "display:none;"; ?>'><input type='checkbox' name='return' <?php if(!empty($rtn)) echo 'checked';?> onclick="approve.classList.add('disabled');this.form.submit();"> Return Label</div>
          <input type='text' placeholder='Recipient' readonly style='color:var(--txt_clr);pointer-events:none' value='<?php echo $shipto;?>'>
          <input type='text' placeholder='Company' readonly style='color:var(--txt_clr);pointer-events:none' value='<?php echo $company;?>'><br>
          <div class='address <?php if($use_val_address<1 || empty($val_addressLines_1)) echo 'selected';?>' id='address_original' onclick="select_div(this.id,'address','use_val_address',0)">
            <div class='address_input' onclick="approve.classList.add('disabled')">
              <input type='text' name='address1' required placeholder='Address Line 1' value="<?php echo $address1;?>"><br>
              <input type='text' name='address2' placeholder='Address Line 2' value="<?php echo $address2;?>"><br>
              <input type='text' name='city' required pattern='[A-Za-z ]{2,50}' placeholder='City' value="<?php echo $city;?>">
              <input type='text' name='state' required placeholder='State' class='short' pattern="[A-Za-z']{2}" title='2 Character State Code' value="<?php echo $state;?>">
              <input type='text' name='zip' required placeholder='Zip' style='width:33%' title='5-10 Digit Zipcode' value="<?php echo $zip;?>">
              <input type='text' name='country' required placeholder='Country' class='short' pattern="[A-Za-z']{2}" title='2 Character Country Code' value="<?php echo $country;?>">
            </div>
            <div class='address_status'>ORIGINAL</div>
          </div>
          <div class='address <?php if($use_val_address>0 && !empty($val_addressLines_1)) echo 'selected';?>' id='address_validated' onclick="select_div(this.id,'address','use_val_address',1)">
            <div class='address_input'>
              <input type='text' name='addressLines_1' placeholder='Address Line 1' value="<?php echo $val_addressLines_1;?>"><br>
              <input type='text' name='addressLines_2' placeholder='Address Line 2' value="<?php echo $val_addressLines_2;?>"><br>
              <input type='text' name='cityTown' placeholder='City' value="<?php echo $val_cityTown;?>">
              <input type='text' name='stateProvince' placeholder='State' class='short' value="<?php echo $val_stateProvince;?>">
              <input type='text' name='postalCode' placeholder='Zip' style='width:33%' value="<?php echo $val_postalCode;?>">
              <input type='text' name='countryCode' placeholder='Country' class='short' value="<?php echo $val_countryCode;?>">
            </div>
            <div class='address_status'><?php echo substr($val_status,0,stripos($val_status,'_'));?> </div>
          </div>
        </div><br>

        <div class='module parcel' onclick="approve.classList.add('disabled')">
          <div class='title'>Parcel</div>
          <select id='svc' onchange="add_service(0);this.value='';return false;">
            <option value='' selected>Add Services
            <?php echo espb_get_services($carrier);?>
          </select>
          <div id='sel_services' style='float:right;display:inline-grid;margin:0 1em'></div>
          <input type='hidden' name='services' id='services' value='<?php echo $services;?>'><br>
          <div style='position:absolute;margin:.8em 0 0 .5em;color:var(--txt_hl_clr)'>$</div><input type='number' name='ins_value' style='width:5em' placeholder='Ins' step='.01' value='<?php echo $ins_value;?>'>
          <input type='number' name='weight' class='short' required pattern='[0-9]{.05,999}' placeholder='Weight' id='parcel_weight' step='.05' title='<?php echo $sys_calc_weight.$user_cached_weight;?>' value='<?php echo $weight;?>' onchange="pb_getE('adj_weight').value=1;"> <div style='display:inline-block'><?php echo $weight_unit;?></div><br>
          <input type='number' name='length' class='short' required pattern='[0-9]{.05,999}' placeholder='L' step='1' title='<?php echo $sys_calc_dim.$user_cached_dim;?>' value='<?php echo $length;?>'  onchange="pb_getE('adj_dim').value=1;">
          <input type='number' name='width'  class='short' required pattern='[0-9]{.05,999}' placeholder='W'  step='1' title='<?php echo $sys_calc_dim.$user_cached_dim;?>' value='<?php echo $width;?>'  onchange="pb_getE('adj_dim').value=1;">
          <input type='number' name='height' class='short' required pattern='[0-9]{.05,999}'  placeholder='H' step='1' title='<?php echo $sys_calc_dim.$user_cached_dim;?>' value='<?php echo $height;?>' onchange="pb_getE('adj_dim').value=1;"> <div style='display:inline-block'>L W H (<?php echo $dim_unit;?>)</div>
          <div onclick="product_list.value=this.innerHTML"><?php echo $product_list;?></div>
        </div>
        
        <?php if(strlen($comments)>5) {echo "<br>
          <div class='module note'>".str_replace('|','<br>',str_replace('<br>','',$comments)).'</div>';}?>

        <div class='form_control'>
          <input type='button' value='Options' onclick="var pb_o=pb_getE('pb_options'); if(pb_o.style.display=='block') pb_o.style.display='none'; else {pb_o.style.display='block';location.hash='#pb_options';}">
          <?php echo wp_nonce_field('purchase_label','pb_purchase_label');?>
          <input type='hidden' name='product_list' id='product_list' value='<?php if(!empty($_POST['product_list'])) echo $product_list;?>'>
          <input type='hidden' name='adj_product' id='adj_product' value=',<?php echo $variation_cache;?>'>
          <input type='hidden' name='adj_weight' id='adj_weight' value='<?php if($adj_prd>0) echo $adj_weight; else echo 0;?>'>
          <input type='hidden' name='adj_dim' id='adj_dim' value='<?php if($adj_prd>0) echo $adj_dim; else echo 0;?>'>
          <input type='hidden' name='tracking_number' id='tracking_number' value=''>
          <input type='hidden' name='serviceid' id='serviceid' value='<?php echo $serviceid;?>'>
          <input type='hidden' name='parceltype' id='parceltype' value='<?php echo $parceltype;?>'>
          <input type='hidden' name='override' id='override' value='<?php echo $override;?>'>
          <input type='hidden' name='use_val_address' id='use_val_address' value='<?php echo $use_val_address;?>'>
          <input type='hidden' name='submit_type' id='submit_type' value='0'>
          <input type='hidden' name='tmp' id='tmp' value=''>
          <input type='submit' name='quote' value='Preview Rates' style='margin-top:1em'>
          <input type='submit' name='approve' id='approve' value='Purchase Label' style='float:right;margin-top:1em;background:var(--txt_hl_clr);color:#fff' onclick='submit_type.value=1;this.form.submit();'>
          <div id='pb_options' style='display:none'>
            <input type='text' name='ship_date' value='<?php echo $ship_date;?>' onfocus="(this.type='date')" placeholder='<?php echo espb_ship_date(); ?>' style='border: 1px solid #888'> <span class="dashicons dashicons-info-outline" style='vertical-align:middle' title='Ship Date. If not specified, will use next week day.'></span>
            <?php if(current_user_can('edit_posts')) {?>
              <div style='float:right;text-align:right'>
                <input type='tel' name='user_induction_zip' placeholder='<?php if(!empty($facility_induction_zip)) echo "Induction Zip $facility_induction_zip"; else echo '5 digit Induction Zipcode';?>' title='5 digit Induction Zipcode' pattern="[0-9]{5}" style='border: 1px solid #888;' value='<?php echo $user_induction_zip;?>'>
                <div title='Checking this box will delay order completion (status & customer completed email) until package receives initial acceptance scan from courier.'><input type='checkbox' name='pending' <?php if(!empty($pending)) echo 'checked';?>> Delay Email <span class="dashicons dashicons-info-outline" style='vertical-align:middle'></span></div>
              </div>
            <?php } ?>
            <br>
            <input type='button' value='Order' onclick="open_wc_order('<?php echo $order_id;?>','<?php echo $order_type;?>');">
            <?php if(!isset($label_history)) { ?><input type='button' value='Packing Slip' onclick="tracking_number.value='0';override.value=1;submit_type.value=2;pb_load_animation();pb_getE('pb_ship').submit();"><?php } ?>
            <input type='button' value='Queue' onclick="open_pb_queue();">
            <?php if(current_user_can('view_woocommerce_reports')) {?><input type='button' value='Reports' onclick="open_pb_report();"><?php } ?>
            <?php if(current_user_can('manage_options')) {?>
              <input type='button' onclick="window.open('<?php echo admin_url('options-general.php?page=pb-admin');?>')" value='Admin'>
            <?php } ?>
            <br>
            <select name='envir' onchange="this.style.color='#fff'; this.style.background='#2cbb2c'; if(this.options[this.selectedIndex].value=='prod') this.style.background='#d70080';">
              <option value='sandbox' <?php if($env!=='prod') echo 'selected';?>>Sandbox API
              <option value='prod' <?php if($env=='prod') echo 'selected';?>>Prod API
            </select>
            <select name='color_scheme' onchange="pb_load_animation();document.getElementById('pb_ship').submit();">
              <option disabled>Color Scheme
              <option value='light' <?php if($color_scheme!=='light') echo 'selected';?>>Light
              <option value='dark' <?php if($color_scheme=='dark') echo 'selected';?>>Dark
            </select>
          </div>
        </div>

      </form><?php }
      
      echo espb_inc_js($parceltype); 
      
      echo "<script type='text/javascript'>";
      
      if(!empty($user_cached_alert)) echo $user_cached_alert;

      if(!empty($pending) || !empty($user_induction_zip)) echo "pb_getE('pb_options').style.display='block';";
      
      if(isset($_POST['submit_type45652811']) && isset($_POST['tracking_number']) && isset($_POST['tmp'])) {
        $tracking_number=sanitize_text_field($_POST['tracking_number']);
        $tmp=sanitize_text_field($_POST['tmp']);
        echo "
          tracking_number.value='$tracking_number';
          tmp.value='$tmp';
          submit_type.value=2;
          pb_load_animation();
          pb_getE('pb_ship').submit();";
      }

      if(!empty($shipped) && $override<1) echo "setTimeout(function(){alert('Order previously shipped on $shipped.')},500); document.getElementById('override').value=1;";?>
      </script>
    </body>
  </html><?php
}