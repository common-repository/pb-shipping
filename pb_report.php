<?php if(!defined('ABSPATH')) exit;

function espb_reports() {
  // Check Permissions
  if(!is_user_logged_in()) {header("Location: ".site_url()."/wp-login.php?redirect_to=".urlencode($_SERVER['REQUEST_URI'])); exit();}
  espb_popup_css();
  if(!current_user_can('edit_shop_orders')) {echo 'Insufficient permissions'; exit();}

  if(empty(get_option('pb_developer_id'))||empty(get_option('pb_client_id'))) {
    if(current_user_can('manage_options')) header("location: ".admin_url('options-general.php?page=pb-admin'));
    else {echo 'Plugin not configured.'; exit();}
  }

  // Collect Variables
  $report_type=$special_instructions=$filter='';
  $rpt_id=$delete_report=$min_label=$max_label=$finalize=$dlv_status=0;
  $weight_unit=get_option('woocommerce_weight_unit');
  $user_id=get_current_user_id();
  $site_name=get_bloginfo('name');
  $white_label=get_option('pb_white_label');
  $site_url=site_url();
  $logo_url=espb_site_logo();
  $sorder_id=$offset=0;
  $filter_status='';
  $filter_status_list=array();
  if(isset($_POST['filter_status'])) $filter_status=sanitize_text_field($_POST['filter_status']);
  if(isset($_GET['dlv_status'])) $dlv_status=intval($_GET['dlv_status']); 
  if(isset($_POST['finalize'])) $finalize=intval($_POST['finalize']);
  if(isset($_POST['report_type'])) $report_type=sanitize_text_field($_POST['report_type']);
  else {
    $pending=espb_r("
      SELECT m.meta_id
      FROM wp_posts o
      JOIN wp_postmeta s ON s.post_id=o.ID AND s.meta_key='_last_shipped'
      JOIN wp_postmeta m ON m.post_id=o.ID AND m.meta_key LIKE '_pb_label_meta%'
      WHERE o.post_type IN ('shop_order','import_order')
      AND o.post_status IN ('wc-processing','wc-completed')
      AND m.meta_value NOT LIKE '%\"cancel\"%'
      AND m.meta_value NOT LIKE '%/inbound/%'
      AND m.meta_value LIKE '%usps%'
      AND m.meta_value NOT LIKE BINARY '%manifest%'
      AND s.meta_value BETWEEN NOW()-INTERVAL 1 MONTH AND NOW()+INTERVAL 1 DAY
      LIMIT 1;");
    if($pending) $report_type='manifest'; else $report_type='labels';
  }
  if(isset($_POST['container_type'])) {$container_type=sanitize_text_field($_POST['container_type']);update_option("pb_default_container_type_$user_id",$container_type);} else $container_type=get_option("pb_default_container_type_$user_id");
  if(isset($_POST['special_instructions'])) $special_instructions=sanitize_text_field($_POST['special_instructions']);
  if(isset($_POST['min_label'])) $min_label=intval($_POST['min_label']);
  if(isset($_POST['max_label'])) $max_label=intval($_POST['max_label']);
  if((isset($_POST['pb_ship_dt']) && isset($_POST['order_id'])) && check_admin_referer('ship_dt','pb_ship_dt')) { // update ship date
    $sorder_id=intval($_POST['order_id']);
    $sdate=sanitize_text_field($_POST['sdate']);
    if($sorder_id>0) {
      $report_type='labels';
      update_post_meta($sorder_id,'_last_shipped',$sdate);
    }
  }

  // Get Parameters
  if(isset($_GET['rpt_id'])) $rpt_id=intval($_GET['rpt_id']);
  if($dlv_status>0) espb_fetch_delivery_status($rpt_id);
  if($rpt_id>0) if(isset($_GET['delete_report'])) if($rpt_id==$_GET['delete_report']) $delete_report=1;

  // Get User Option Defaults
  $color_scheme=get_option("pb_default_color_scheme_$user_id");
  $env=get_option("pb_default_env_$user_id"); if(empty($env)) $env='sandbox';
  if(isset($_POST['carrier'])) $carrier=sanitize_text_field($_POST['carrier']); else $carrier=get_option("pb_default_carrier_$user_id");
  if($rpt_id<1) {
    if(isset($_POST['facility'])) $facility=sanitize_text_field($_POST['facility']); else $facility=get_option("pb_default_{$carrier}_facility_$user_id");
    $pickup=get_option("pb_default_{$carrier}_pickup_$user_id");
    $client_id=get_option('pb_client_id')[$facility];
    $client_facility_id=get_option('pb_client_facility_id')[$facility];
    $carrier_facility_id=get_option('pb_carrier_facility_id')[$facility];
    $client_facility_name=get_option('pb_client_facility_name')[$facility];
  }
  if($rpt_id<1) if($carrier!=='NEWGISTICS' && $report_type=='container') $report_type='manifest';
  if(isset($_POST['pickup'])) $pickup=sanitize_text_field($_POST['pickup']); else $pickup=0;
  $shipper_id=get_option('pb_merchant_id');

  // Update User Default Options
  update_option("pb_default_{$carrier}_pickup_$user_id",$pickup);


  // Run Report
  $report=$report_url=$search_tracking='';
  $report_ct=0;
  $tracking_array=array();
  if($min_label>0) $filter.=" AND m.meta_id>=$min_label ";
  if($max_label>0) $filter.=" AND m.meta_id<=$max_label ";

  if($rpt_id>0) {
    $client_facility_name=get_the_excerpt($rpt_id);
    $report_data=espb_r("
      SELECT r.post_type
      ,m.post_id order_id
      ,m.meta_id
      ,m.meta_key
      ,m.meta_value label_meta
      ,(SELECT DATE_FORMAT(s.meta_value,'%Y-%m-%d') FROM wp_postmeta s WHERE s.post_id=m.post_id AND s.meta_key='_last_shipped' LIMIT 1)ship_dt
      ,(SELECT DATE_FORMAT(d.meta_value,'%Y-%m-%d') FROM wp_postmeta d WHERE d.post_id=m.post_id AND d.meta_key='delivery_est' LIMIT 1)del_est
      FROM wp_posts r
      JOIN wp_postmeta m ON m.meta_key LIKE '_pb_label_meta%' AND m.meta_value LIKE '%i:$rpt_id;%'
      AND m.meta_value LIKE CONCAT(CONCAT('%\"',REPLACE(r.post_type,'pb_','')),'\"%')
      AND r.ID=$rpt_id
      AND r.post_status!='trash'
      ORDER BY m.meta_id DESC;
    ");

  } else {
    if(isset($_POST['offset'])) $offset=intval($_POST['offset']);
    if(isset($_POST['search_tracking'])) $search_tracking=sanitize_text_field($_POST['search_tracking']);
    if(!empty($search_tracking)) {$offset=0; $filter.=" AND m.meta_key LIKE '%$search_tracking'";} else $filter.=" AND m.meta_value NOT LIKE '%/inbound/%'";
    if(empty($filter) && empty($filter_status)) $filter.=" AND s.meta_value>NOW()-INTERVAL 3 MONTH";
    if($report_type!='labels') $filter.=" AND s.meta_value<=NOW()+INTERVAL 1 DAY";
    if($report_type=='manifest') $filter.=" AND s.meta_value>NOW()-INTERVAL 1 MONTH";

    $report_data=espb_r("
      SELECT DISTINCT o.ID order_id
      ,m.meta_id
      ,m.meta_key
      ,m.meta_value label_meta
      ,DATE_FORMAT(s.meta_value,'%Y-%m-%d')ship_dt
      ,(SELECT DATE_FORMAT(d.meta_value,'%Y-%m-%d') FROM wp_postmeta d WHERE d.post_id=o.ID AND d.meta_key='delivery_est' LIMIT 1)del_est
      FROM wp_posts o
      LEFT JOIN wp_postmeta s ON s.post_id=o.ID AND s.meta_key='_last_shipped'
      JOIN wp_postmeta m ON m.post_id=o.ID AND m.meta_key LIKE '_pb_label_meta%'
      WHERE o.post_type IN ('shop_order','import_order')
      AND o.post_status IN ('wc-processing','wc-completed')
      AND m.meta_value NOT LIKE '%\"cancel\"%'
      AND m.meta_value LIKE '%\"$carrier\"%'
      AND m.meta_value LIKE '%\"$client_facility_id\"%'
      AND m.meta_value NOT LIKE BINARY '%\"$report_type\"%'
      $filter
      ORDER BY ship_dt DESC, m.meta_id DESC
      LIMIT 25 OFFSET $offset;
    ");
  }

  if($report_data) {
    $usps_deleted_manifest=get_transient("pb_usps_deleted_manifest");
    $tot_oz=$oz=$tot_cost=$cost=$tracking_ct=0;
    $max_ship_dt='';
    foreach($report_data as $r) {
      $order_id=$r->order_id;
      $ship_dt=$r->ship_dt;
      $del_est=$r->del_est;
      $meta_key=$r->meta_key;
      $tracking=str_replace('_pb_label_meta_','',$meta_key);
      $lm=$r->label_meta;
      $lm=unserialize($lm);
      if(isset($lm['dt'])) {$label_dt=date('Y-m-d G:i:s',$lm['dt']); if(strtotime($label_dt)>$max_ship_dt)$max_ship_dt=strtotime($label_dt);} else $label_dt='';
      if(isset($lm[1])) $carrier=$lm[1];
      if(isset($lm[3])) $service_id=$lm[3];
      if(isset($lm['oz'])) {$oz=$lm['oz']; if($oz>0) $tot_oz+=$oz;}
      if(isset($lm['tot'])) {$cost=$lm['tot']; $tot_cost+=$cost;}
      if(isset($lm['st'])) $status=$lm['st']; else $status='Pending';
      $filter_status_txt=preg_replace("/[^A-Za-z]/",'',$status);
      if(!in_array($filter_status_txt,$filter_status_list)) array_push($filter_status_list,$filter_status_txt);
      if(!empty($filter_status)) if($filter_status_txt!==$filter_status) continue;
      $track_url=espb_track_url($carrier,$tracking);
      if($sorder_id==$order_id) $style="style='background:#cfffcf'"; else $style='';
      if(stripos($status,'Delivered')===false) {if(!empty($del_est)) $del_est="<br>Est Delivery $del_est";} else $del_est='';
      $report.="
      <tr title='Label ID {$r->meta_id}' $style>
        <td>$client_facility_name</td>
        <td nowrap>$label_dt</td>
        <td nowrap onclick=\"sdate$order_id.style.display='block';sdate$order_id.focus();\">$ship_dt <input title='Press enter to save' type='search' id='sdate$order_id' onchange='ship_dt($order_id,this.value)'; required value='$ship_dt' style='display:none'></td>
        <td onclick=\"window.open('".admin_url('admin.php?page=pb-ship&order_id=')."$order_id&override=1','Ship','width=780,height=1005,resizable=yes,scrollbars=yes');\"><span class='dashicons dashicons-external'></span> $order_id</td>
        <td align='right'>{$oz}$weight_unit<br>$$cost</td>
        <td onclick=\"window.open('$track_url','Track','width=780,height=1005,resizable=yes,scrollbars=yes');\"><span class='dashicons dashicons-external'></span> $carrier $service_id<br>$tracking</td>
        <td>$status$del_est</td>
      </tr>
      <form name='pb_ship_dt' id='pb_ship_dt' method='post' accept-charset='UTF-8 ISO-8859-1'>
        ".wp_nonce_field('ship_dt','pb_ship_dt')."
        <input type='hidden' id='sdate' name='sdate'>
        <input type='hidden' id='order_id' name='order_id'>
      </form>
      <script type='text/javascript'>
        function ship_dt(oid,ship_dt) {
          document.getElementById('order_id').value=oid;
          document.getElementById('sdate').value=ship_dt;
          document.getElementById('pb_ship_dt').submit();
        }
      </script>
      ";
      if(is_array($usps_deleted_manifest)) {if(!in_array($tracking,$usps_deleted_manifest,true)) array_push($tracking_array,$tracking);} else array_push($tracking_array,$tracking);// Prep tracking array
      $report_ct++;

      if($delete_report>0) { // Order Label meta
        $post_type=$r->post_type;
        $post_type=str_replace('pb_','',$post_type);
        unset($lm["$post_type"]);
        update_post_meta($order_id,$meta_key,$lm); // Update label meta
      }

    }
    $tracking_ct=count($tracking_array);
    if($report_ct>1) $plr='s'; else $plr='';

    if($delete_report>0) { // Save tracking array
      if($carrier=='USPS') {
        if(!empty($usps_deleted_manifest)) array_push($usps_deleted_manifest,$tracking_array); else $usps_deleted_manifest=$tracking_array;
        set_transient("pb_usps_deleted_manifest",$usps_deleted_manifest,'86500');
      }
      else wp_trash_post($rpt_id);
      update_post_meta($rpt_id,'deleted',1); 
    }
    
    if($report_ct>0) $avg_cost=number_format(($tot_cost/$report_ct),2); else $avg_cost=0;
    $tot_cost=number_format($tot_cost,2);
    $report="<table class='pb_rpt'><tr><th>Facility</th><th>Label Date</th><th>Ship Date</th><th>Order</th><th style='text-align:right' title='$$tot_cost (Avg $$avg_cost)\n{$tot_oz}$weight_unit'>Totals</th><th>Labels ($report_ct item$plr)</th><th>Status</td></tr>$report</table>";

    if($finalize>0 && $delete_report<1 && $rpt_id<1 && check_admin_referer('finalize_report','pb_finalize_report')) { // Submit
    
      if(stripos('NEWGISTICS,USPS',$carrier)!==false) $api=1; else $api=0;
      $now=strtotime(current_time('mysql'));
      $current_time=current_time('mysql');
      $current_date=substr($current_time,0,10);
      $submissionDate=$current_date;
      if($carrier=='USPS' && date('G',$now)>=8) $submissionDate=date('Y-m-d',strtotime('+1 day'));
      $max_ship_dt=date('Y-m-d G:i:s',$max_ship_dt);
      if($current_date==substr($max_ship_dt,0,10)) $max_ship_dt=$current_time;
      $post_id=wp_insert_post(array('post_title'=>"$client_facility_name <br>$carrier $report_type",'post_excerpt'=>"$client_facility_name",'post_type'=>"pb_$report_type",'post_status'=>'private','guid'=>'','post_author'=>$user_id,'post_date'=>"$max_ship_dt")); // Create Post

      $post_fields=new stdClass();
      $post_fields->carrier=$carrier;
      if($carrier!='NEWGISTICS') $post_fields->submissionDate=$submissionDate;
      $post_fields->parcelTrackingNumbers=$tracking_array;
      $parameters=new stdClass();
      if($report_type!='container') {
        $parameters=[
           array('name'=>'SHIPPER_ID','value'=>$shipper_id)
          ,array('name'=>'CLIENT_ID','value'=>$client_id)
        ];
      }

      if($carrier=='USPS') { // Schedule pickup, Request manifest from USPS
        $fromAddress=espb_create_obj_fromAddress($facility);
        $post_fields->fromAddress=$fromAddress;

        if($pickup>0) {
          $pickup_fields=new stdClass();
          $pickup_fields->carrier=$carrier;
          $pickup_fields->packageLocation='Other';
          $pickup_fields->specialInstructions=$special_instructions;

          $pickupAddress=espb_create_obj_fromAddress($facility);
          $pickup_fields->pickupAddress=$fromAddress;

          $pickupSummary=new stdClass();
          $pickupSummary=[array('serviceId'=>$service_id,'count'=>strval($tracking_ct),'totalWeight'=>array('unitOfMeasurement'=>'OZ','weight'=>strval($tot_oz)))];
          $pickup_fields->pickupSummary=$pickupSummary;
        } else $pickup_fields='';

        $post_fields->parameters=$parameters;
        if($tracking_ct==0) $api=0;
        $report_url=espb_report($report_type,$carrier,$post_fields,$post_id,$api,$pickup_fields,$env);
      }

      else { // Send to PB, Batch all Carriers

        if($report_type=='container') {
          $post_fields->containerType=$container_type;
          $documents=new stdClass();
          $documents=[array('fileFormat'=>'PDF','size'=>'DOC_6X4','resolution'=>'DPI_203')];
          $post_fields->documents=$documents;
          $parameters=[
             array('name'=>'CARRIER_FACILITY_ID','value'=>$carrier_facility_id)
            ,array('name'=>'CARRIER_GATEWAY_FACILITY_ID','value'=>$carrier_facility_id)
            ,array('name'=>'CLIENT_ID','value'=>$client_id)
            ,array('name'=>'CLIENT_CONTAINER_ID','value'=>strval($post_id))
            ,array('name'=>'SHIP_DATE','value'=>$current_date)
            ,array('name'=>'TOTAL_CONTAINER_COUNT','value'=>strval($tracking_ct))
          ];
        }
        $post_fields->parameters=$parameters;
        $report_url=espb_report($report_type,$carrier,$post_fields,$post_id,$api,'',$env);
      }

      if(!empty(espb_r("SELECT guid FROM wp_posts WHERE ID=$post_id AND LENGTH(guid)>0 AND guid NOT LIKE 'http%';"))) { // If Report - Add container meta
        update_post_meta($post_id,'items',$report_ct); 
        update_post_meta($post_id,'wt',$tot_oz);
        update_post_meta($post_id,'cost',$tot_cost);

        reset($report_data);
        foreach($report_data as $r) {
          $order_id=$r->order_id;
          $meta_key=$r->meta_key;
          $lm=$r->label_meta;
          $lm=unserialize($lm);
          $lm["$report_type"]=$post_id; // Add to Array
          update_post_meta($order_id,$meta_key,$lm); // Update label meta
        }
      } else wp_delete_post($post_id,true);
    }
  }
  else if($rpt_id>0) $report="<div class='err'>No items exist.</div>";
  elseif($report_type=='labels' && empty($search_tracking) && empty($filter_status)) $report="<div class='err'>Use tracking or status filter for more results.</div>";
  elseif(!empty($report_type)) $report="<div class='err'>All shipments complete.</div>"; 
  ?>

    <!doctype html>
    <html lang="en-US">
      <head>
        <title>Shipping Report</title>
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

      <?php if($finalize>0 && $rpt_id<1) { ?>
        <div class='module label noprint' id='pb_report'>
          <div class='title'>Label</div>
          <?php echo $report_url;?>
        </div>

        <?php 
        echo "<div class='form_control noprint'><input type='button' style='margin-top:1em' value='Go Back' onclick='pb_load_animation();window.history.back();';></div>";
      } else { ?>

      <form name='report' id='pb_report' method='post' accept-charset='UTF-8 ISO-8859-1' style='margin:1em 0' onsubmit="pb_load_animation();">
        <?php if($rpt_id>0) echo '<br>'.espb_report_history($rpt_id); ?>
        <div class='module report'>
          <div class='title'><?php if($rpt_id>0) echo 'Labels'; else echo 'Search';?></div>
          <div style='margin-left:4px'>
            <?php if($rpt_id<1) { ?>
            <select name='facility' id='facility' onchange="pb_load_animation();document.getElementById('pb_report').submit();">
              <option value='' disabled selected>Facility
              <?php echo espb_list_facility($facility);?>
            </select>
            <select name='carrier' id='carrier' onchange="pb_load_animation();document.getElementById('pb_report').submit();">
              <option value='' disabled selected>Carrier
              <?php echo espb_list_carrier($carrier);?>
            </select>
            <select name='report_type' id='report_type' required onchange="pb_load_animation();document.getElementById('pb_report').submit();">
              <option value=''>Select Type
              <option value='manifest' <?php if($report_type=='manifest') echo 'selected';?>>Manifests
              <option value='container' <?php if($carrier!=='NEWGISTICS') echo ' disabled '; elseif($report_type=='container') echo 'selected';?>>Containers
              <option value='labels' <?php if($report_type=='labels') echo 'selected';?>>Label History
            </select>
            <?php if($report_type=='manifest' || $report_type=='container') { ?>
              <div id='manifest_filter' style='<?php if($min_label+$max_label<1) echo 'display:none';?>'>
                <input type='number' step='1' value='<?php if($min_label>0) echo $min_label;?>' name='min_label' title='Hover over a row to obtain the Label ID' placeholder='Min Label ID'>
                <input type='number' step='1' value='<?php if($max_label>0) echo $max_label;?>' name='max_label' title='Hover over a row to obtain the Label ID' placeholder='Max Label ID'>
              </div>
            <?php }
            elseif($report_type=='labels') {
              $offset+=25;
              echo "<br>
              <input type='hidden' name='offset' id='offset' value='$offset'>
              <input type='search' name='search_tracking' placeholder='Tracking Number' value='$search_tracking' onchange=\"offset.value='';pb_load_animation();document.getElementById('pb_report').submit();\">
              <input type='search' name='filter_status' placeholder='Status' list='status_list' value='$filter_status' onchange=\"offset.value='';pb_load_animation();document.getElementById('pb_report').submit();\">";
              if($offset>25) echo "<input type='button' value='Clear' style='margin:.3em' onclick=\"offset.value='';search_tracking.value='';pb_load_animation();document.getElementById('pb_report').submit();\">
                <input type='button' style='margin:.3em' value='Prev Page' onclick=\"offset.value=offset.value-50; pb_load_animation();document.getElementById('pb_report').submit();\">";
              echo "<input type='button' value='Next Page' style='margin:.3em' onclick=\"pb_load_animation();document.getElementById('pb_report').submit();\">"; 
            } ?>
            
            <select name='container_type' id='container_type' <?php if($report_type!='container' || $carrier!='Newgistics') echo "style='display:none'"; ?> onchange="pb_load_animation();document.getElementById('pb_report').submit();">
              <option value='carton' <?php if($container_type=='carton') echo 'selected';?>>Carton
              <option value='pallet' <?php if($container_type=='pallet') echo 'selected';?>>Pallet
              <option value='gaylord' <?php if($container_type=='gaylord') echo 'selected';?>>Gaylord
            </select>
            <?php if($carrier!='NEWGISTICS' && $report_type!='labels') {?><br>
              <input type='checkbox' id='pickup' name='pickup' <?php if(!empty($pickup)) echo 'checked';?>> Request Pickup<br>
              <input type='text' name='special_instructions' id='special_instructions' placeholder='Provide pickup instructions / parcel location e.g. Warehouse' style='display:block;width:80%'>
            <?php } ?>

          <?php } if(isset($report)) echo $report; ?></div>
          <datalist id='status_list'><option><?php echo implode("</option><option>",$filter_status_list);?></option></datalist>
        </div>

        <div class='form_control'>
          <?php if($rpt_id<1) {?>

          <?php if(current_user_can('view_woocommerce_reports')) {?>
            <input type='button' value='Options' onclick="var pb_o=pb_getE('pb_options'); var pb_m=pb_getE('manifest_filter'); if(pb_o.style.display=='inline-block') {pb_o.style.display='none';pb_m.style.display='none';} else {pb_o.style.display='inline-block';pb_m.style.display='block';location.hash='#pb_options';}">
            <input type='submit' name='quote' value='View' style='margin-top:1em'>
            <?php echo wp_nonce_field('finalize_report','pb_finalize_report');?>
            <input type='hidden' name='finalize' id='finalize' value='0'>
            <?php if($report_type!='labels' && $report_ct>0) {?>
            <input type='submit' name='approve' id='approve' value='Submit' style='float:right;margin-top:1em;background:var(--txt_hl_clr);color:#fff' onclick="if(pb_getE('pickup')) if(pb_getE('special_instructions')) {if(pickup.checked && special_instructions.value.length==0) {alert('Special Instructions Required.'); return false;}} finalize.value=1;this.form.submit();">
            <?php }
            } ?>
            <div id='pb_options' style='display:none'>
              <input type='button' value='Queue' onclick="open_pb_queue();">
              <?php if(current_user_can('manage_options')) { ?>
                <input type='button' onclick="window.open('<?php echo admin_url('options-general.php?page=pb-admin');?>')" value='Admin'>
              <?php } ?>
            </div>
          <?php } ?>
        </div>

        <?php if($report_type!='labels') echo espb_report_history($rpt_id); ?>
      </form><?php }
      echo espb_inc_js(''); ?>
    </body>
  </html><?php
}