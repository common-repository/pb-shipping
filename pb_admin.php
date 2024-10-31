<?php if(!defined('ABSPATH')) exit;

function espb_admin() {
  $error=$carrier_i=$service_i=$formula_i='';
  $site_name=get_bloginfo('name');
  $site_url=site_url();
  $logo_url=espb_site_logo();
  $user_id=get_current_user_id();
  $mname=$mpw=$mstatus='';

  if(isset($_POST['sandbox_developer_id']) && check_admin_referer('config_shipping','pb_config_shipping')) {
    $developer_id=sanitize_text_field($_POST['developer_id']); update_option('pb_developer_id',$developer_id,'yes');
    $merchant_id=sanitize_text_field($_POST['merchant_id']); update_option('pb_merchant_id',$merchant_id,'no');
    if(isset($_POST['production_key'])) $pkey=sanitize_text_field($_POST['production_key']); if(!empty($pkey)) update_option('pb_production_key',$pkey,'no');
    if(isset($_POST['production_secret'])) $psec=sanitize_text_field($_POST['production_secret']); if(!empty($psec)) update_option('pb_production_secret',$psec,'no');

    $sdeveloper_id=sanitize_text_field($_POST['sandbox_developer_id']); update_option('pb_sandbox_developer_id',$sdeveloper_id,'yes');
    $smerchant_id=sanitize_text_field($_POST['sandbox_merchant_id']); update_option('pb_sandbox_merchant_id',$smerchant_id,'no');
    $skey=sanitize_text_field($_POST['sandbox_key']); if(!empty($skey)) update_option('pb_sandbox_key',$skey,'no');
    $ssec=sanitize_text_field($_POST['sandbox_secret']); if(!empty($ssec)) update_option('pb_sandbox_secret',$ssec,'no');

    if(isset($_POST['merchant_name'])) $mname=sanitize_text_field($_POST['merchant_name']);
    if(isset($_POST['merchant_pw'])) $mpw=sanitize_text_field($_POST['merchant_pw']);
    if(!empty($mname) && !empty($mpw) && empty($merchant_id)) {
      if(!empty($developer_id)) $dev_id=$developer_id;
      elseif(!empty($sdeveloper_id)) $dev_id=$sdeveloper_id;
      if(!empty($dev_id)) $mstatus=espb_auth_merchant($dev_id,$pkey,$psec,$mname,$mpw);
    }

    if(isset($_POST['client_id'])) {
      $client_id=array_map('sanitize_text_field',$_POST['client_id']);
      $cfac_id=array_map('sanitize_text_field',$_POST['client_facility_id']);
      $carrier_facility_id=array_map('sanitize_text_field',$_POST['carrier_facility_id']);
      $cfac_name=array_map('sanitize_text_field',$_POST['client_facility_name']);
      $cfac_type=array_map('sanitize_text_field',$_POST['client_facility_type']);
      $cfac_active=array_map('sanitize_text_field',$_POST['client_facility_active']);
      $cfac_address=array_map('sanitize_text_field',$_POST['client_facility_address']);
      $cfac_address2=array_map('sanitize_text_field',$_POST['client_facility_address2']);
      $cfac_city=array_map('sanitize_text_field',$_POST['client_facility_city']);
      $cfac_state=array_map('sanitize_text_field',$_POST['client_facility_state']);
      $cfac_zip=array_map('sanitize_text_field',$_POST['client_facility_zip']);
      $cfac_induction_zip=array_map('sanitize_text_field',$_POST['client_facility_induction_zip']);
      $cfac_country=array_map('sanitize_text_field',$_POST['client_facility_country']);
      $cfac_tel=array_map('sanitize_text_field',$_POST['client_facility_tel']);
      $cfac_email=array_map('sanitize_text_field',$_POST['client_facility_email']);

      update_option('pb_client_id',$client_id,'no');
      update_option('pb_client_facility_id',$cfac_id,'no');
      update_option('pb_carrier_facility_id',$carrier_facility_id,'no');
      update_option('pb_client_facility_name',$cfac_name,'no');
      update_option('pb_client_facility_type',$cfac_type,'no');
      update_option('pb_client_facility_active',$cfac_active,'no');
      update_option('pb_client_facility_address',$cfac_address,'no');
      update_option('pb_client_facility_address2',$cfac_address2,'no');
      update_option('pb_client_facility_city',$cfac_city,'no');
      update_option('pb_client_facility_state',$cfac_state,'no');
      update_option('pb_client_facility_zip',$cfac_zip,'no');
      update_option('pb_client_facility_induction_zip',$cfac_induction_zip,'no');
      update_option('pb_client_facility_country',$cfac_country,'no');
      update_option('pb_client_facility_tel',$cfac_tel,'no');
      update_option('pb_client_facility_email',$cfac_email,'no');
    }

    $enabled_carriers=array_map('sanitize_text_field',$_POST['enabled_carriers']); update_option('pb_enabled_carriers',$enabled_carriers,'no');
    if($enabled_carriers) {
      $carrier_fedex=array_map('sanitize_text_field',$_POST['carrier_fedex']); update_option('pb_carrier_fedex',$carrier_fedex,'no');
      $carrier_fedex_ver=array_map('sanitize_text_field',$_POST['carrier_fedex_ver']); update_option('pb_carrier_fedex_ver',$carrier_fedex_ver,'no');
      $carrier_ups=array_map('sanitize_text_field',$_POST['carrier_ups']); update_option('pb_carrier_ups',$carrier_ups,'no');
      $carrier_ups_ver=array_map('sanitize_text_field',$_POST['carrier_ups_ver']); update_option('pb_carrier_ups_ver',$carrier_ups_ver,'no');
    }

    if(isset($_POST['carrier_fedex_sandbox_id'])) {$carrier_fedex_sandbox_id=sanitize_text_field($_POST['carrier_fedex_sandbox_id']); update_option('pb_carrier_fedex_sandbox_id',$carrier_fedex_sandbox_id,'yes');}
    if(isset($_POST['carrier_fedex_prod_id'])) {$carrier_fedex_prod_id=sanitize_text_field($_POST['carrier_fedex_prod_id']); update_option('pb_carrier_fedex_prod_id',$carrier_fedex_prod_id,'yes');}
    if(isset($_POST['carrier_ups_sandbox_id'])) {$carrier_ups_sandbox_id=sanitize_text_field($_POST['carrier_ups_sandbox_id']); update_option('pb_carrier_ups_sandbox_id',$carrier_ups_sandbox_id,'yes');}
    if(isset($_POST['carrier_ups_prod_id'])) {$carrier_ups_prod_id=sanitize_text_field($_POST['carrier_ups_prod_id']); update_option('pb_carrier_ups_prod_id',$carrier_ups_prod_id,'yes');}

    $white_label=''; if(isset($_POST['white_label'])) $white_label=sanitize_text_field($_POST['white_label']); update_option('pb_white_label',$white_label,'no');
    $white_label_url=sanitize_text_field($_POST['white_label_url']); update_option('pb_white_label_url',$white_label_url,'no');
    $slip_note=wp_kses_post($_POST['slip_note']); update_option('pb_slip_note',$slip_note,'no');
    $restrict=''; if(isset($_POST['restrict'])) $restrict=espb_usermeta(0,'ID'); update_option('pb_restrict',$restrict,'yes');
    $uninstall=sanitize_text_field($_POST['uninstall']); update_option('pb_uninstall',$uninstall,'no');

    $admin_emails=''; if(isset($_POST['admin_emails'])) $admin_emails=sanitize_text_field($_POST['admin_emails']); if(!empty($admin_emails)) update_option('pb_admin_emails',$admin_emails,'no');
    $admin_exc_updates=''; if(isset($_POST['admin_exc_updates'])) $admin_exc_updates=sanitize_text_field($_POST['admin_exc_updates']); update_option('pb_admin_exc_updates',$admin_exc_updates,'no');
    $admin_rtn_updates=''; if(isset($_POST['admin_rtn_updates'])) $admin_rtn_updates=sanitize_text_field($_POST['admin_rtn_updates']); update_option('pb_admin_rtn_updates',$admin_rtn_updates,'no');

    $email_updates=''; if(isset($_POST['email_updates'])) $email_updates=sanitize_text_field($_POST['email_updates']); update_option('pb_email_updates',$email_updates,'no');
    $email_exc_updates=''; if(isset($_POST['email_exc_updates'])) $email_exc_updates=sanitize_text_field($_POST['email_exc_updates']); update_option('pb_email_exc_updates',$email_exc_updates,'no');
    $email_rtn_updates=''; if(isset($_POST['email_rtn_updates'])) $email_rtn_updates=sanitize_text_field($_POST['email_rtn_updates']); update_option('pb_email_rtn_updates',$email_rtn_updates,'no');

    $sms_updates=''; if(isset($_POST['sms_updates'])) $sms_updates=sanitize_text_field($_POST['sms_updates']); update_option('pb_sms_updates',$sms_updates,'no');
    $sms_exc_updates=''; if(isset($_POST['sms_exc_updates'])) $sms_exc_updates=sanitize_text_field($_POST['sms_exc_updates']); update_option('pb_sms_exc_updates',$sms_exc_updates,'no');
    $sms_rtn_updates=''; if(isset($_POST['sms_rtn_updates'])) $sms_rtn_updates=sanitize_text_field($_POST['sms_rtn_updates']); update_option('pb_sms_rtn_updates',$sms_rtn_updates,'no');

    $fetch_freq=''; if(isset($_POST['fetch_freq'])) $fetch_freq=sanitize_text_field($_POST['fetch_freq']); if(get_option('pb_fetch_freq')!==$fetch_freq) delete_transient('fetch_delivery_status'); update_option('pb_fetch_freq',$fetch_freq,'yes');
    $mb_phone=''; if(isset($_POST['mb_phone'])) $mb_phone=sanitize_text_field($_POST['mb_phone']); update_option('pb_mb_phone',$mb_phone,'no');
    $mb_dev_key=''; if(isset($_POST['mb_dev_key'])) $mb_dev_key=sanitize_text_field($_POST['mb_dev_key']); if(!empty($mb_dev_key)) update_option('pb_mb_dev_key',$mb_dev_key,'no');
    $mb_prod_key=''; if(isset($_POST['mb_prod_key'])) $mb_prod_key=sanitize_text_field($_POST['mb_prod_key']); if(!empty($mb_prod_key)) update_option('pb_mb_prod_key',$mb_prod_key,'no');

    $quote=''; if(isset($_POST['quote'])) $quote=1; update_option('pb_cart_quote',$quote,'yes');
    if(isset($_POST['origin_facility'])) {$origin_facility=intval($_POST['origin_facility']); update_option('pb_origin_facility',$origin_facility,'yes');}

    if(isset($_POST['wc_method'])) {
      $wc_method=array_map('sanitize_text_field',$_POST['wc_method']);
      update_option('pb_wc_method',$wc_method,'no');
      espb_r("DELETE FROM wp_options WHERE option_name LIKE 'pb_wc_method_%:%';");
      $carrier=$service=$formula=$cart_apply='';
      if(isset($_POST['cart_apply'])) $cart_apply=array_map('sanitize_text_field',$_POST['cart_apply']);
      if(isset($_POST['carrier'])) $carrier=array_map('sanitize_text_field',$_POST['carrier']);
      if(isset($_POST['service'])) $service=array_map('sanitize_text_field',$_POST['service']);
      if(isset($_POST['formula'])) $formula=array_map('sanitize_text_field',$_POST['formula']);

      $i=$m_ct=0;
      if(is_array($wc_method)) $m_ct=count($wc_method);
      if($m_ct>0) while($i<$m_ct) {
        $wc_method_i=$wc_method[$i];
        if(is_array($cart_apply)) $cart_apply_i=$cart_apply[$i];
        if(is_array($carrier)) $carrier_i=$carrier[$i];
        if(is_array($service)) $service_i=$service[$i];
        if(is_array($formula)) $formula_i=$formula[$i];
        update_option("pb_wc_method_$wc_method_i",array($carrier_i,$service_i,$formula_i,$cart_apply_i),'no');
        $i++;
      }
    }
  }
  $developer_id=get_option('pb_developer_id');
  $merchant_id=get_option('pb_merchant_id');//$merchant_id=123;
  $pkey=get_option('pb_production_key');
  $psec=get_option('pb_production_secret');
  $sdeveloper_id=get_option('pb_sandbox_developer_id'); if(empty($sdeveloper_id)) {$sdeveloper_id=$developer_id; if(empty($sdeveloper_id)) $sdeveloper_id='94649849';update_option('pb_sandbox_developer_id',$sdeveloper_id,'yes');}
  $smerchant_id=get_option('pb_sandbox_merchant_id'); if(empty($smerchant_id)) {$smerchant_id=$merchant_id; if(empty($smerchant_id)) $smerchant_id='3001807805';update_option('pb_sandbox_merchant_id',$smerchant_id,'no');}
  $skey=get_option('pb_sandbox_key'); if(empty($skey)) {$skey='vvRgvpJ3T0QYTVaYolGbJFG9EovxCL11'; update_option('pb_sandbox_key',$skey,'no');}
  $ssec=get_option('pb_sandbox_secret'); if(empty($ssec)) {$ssec='YFJeCpWqcoO8B9DH'; update_option('pb_sandbox_secret',$ssec,'no');}
  if(in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))) $wc_active=1; else $wc_active=0;

  $client_id=get_option('pb_client_id'); 
  if(empty($client_id)) {
    $client_id=array('NGST');
    $cfac_id=array('0093');
    $carrier_facility_id=array('2594');

    $s=espb_store_data();
    $cfac_name[0]=$s[0]->store_name;
    $cfac_active[0]='1';
    $cfac_address[0]=$s[0]->address;
    $cfac_address2[0]=$s[0]->address_2;
    $cfac_city[0]=$s[0]->city;
    if(!empty($s[0]->ctry_state)) $cfac_state[0]=substr($s[0]->ctry_state,-2); else $cfac_state[0]='';
    $cfac_zip[0]=$s[0]->zip;
    $cfac_induction_zip[0]=$s[0]->zip;
    if(!empty($s[0]->ctry_state)) $cfac_country[0]=substr($s[0]->ctry_state,0,2); else $cfac_country[0]='';
    $cfac_tel[0]=$s[0]->tel;
    $cfac_email[0]=$s[0]->email;
  } else {
    $cfac_id=get_option('pb_client_facility_id');
    $carrier_facility_id=get_option('pb_carrier_facility_id');
    $cfac_name=get_option('pb_client_facility_name');
    $cfac_type=get_option('pb_client_facility_type');
    $cfac_active=get_option('pb_client_facility_active');
    $cfac_address=get_option('pb_client_facility_address');
    $cfac_address2=get_option('pb_client_facility_address2');
    $cfac_city=get_option('pb_client_facility_city');
    $cfac_state=get_option('pb_client_facility_state');
    $cfac_zip=get_option('pb_client_facility_zip');
    $cfac_induction_zip=get_option('pb_client_facility_induction_zip');
    $cfac_country=get_option('pb_client_facility_country');
    $cfac_tel=get_option('pb_client_facility_tel');
    $cfac_email=get_option('pb_client_facility_email');
  }

  $enabled_carriers=get_option('pb_enabled_carriers'); if(empty($enabled_carriers) || empty($merchant_id)) $enabled_carriers=array(1,0,0,0);
  $white_label=get_option('pb_white_label'); if(!empty($white_label)) $white_label='checked';
  $white_label_url=get_option('pb_white_label_url');
  $slip_note=get_option('pb_slip_note');
  $restrict=get_option('pb_restrict'); if(!empty($restrict)) $restrict='checked';
  $uninstall=get_option('pb_uninstall');

  $admin_emails=get_option('pb_admin_emails');
  $admin_exc_updates=get_option('pb_admin_exc_updates'); if(!empty($admin_exc_updates)) $admin_exc_updates='checked';
  $admin_rtn_updates=get_option('pb_admin_rtn_updates'); if(!empty($admin_rtn_updates)) $admin_rtn_updates='checked';

  $email_updates=get_option('pb_email_updates'); if(!empty($email_updates)) $email_updates='checked';
  $email_exc_updates=get_option('pb_email_exc_updates'); if(!empty($email_exc_updates)) $email_exc_updates='checked';
  $email_rtn_updates=get_option('pb_email_rtn_updates'); if(!empty($email_rtn_updates)) $email_rtn_updates='checked';

  $sms_updates=get_option('pb_sms_updates');
  $sms_exc_updates=get_option('pb_sms_exc_updates');
  $sms_rtn_updates=get_option('pb_sms_rtn_updates');

  $fetch_freq=get_option('pb_fetch_freq');
  $mb_phone=get_option('pb_mb_phone');
  $mb_dev_key=get_option('pb_mb_dev_key');
  $mb_prod_key=get_option('pb_mb_prod_key');
  
  $carrier_fedex_prod_id=get_option('pb_carrier_fedex_prod_id');
  $carrier_fedex_sandbox_id=get_option('pb_carrier_fedex_sandbox_id');
  $carrier_ups_prod_id=get_option('pb_carrier_ups_prod_id');
  $carrier_ups_sandbox_id=get_option('pb_carrier_ups_sandbox_id');
  
  $carrier_fedex=get_option('pb_carrier_fedex'); if(empty($carrier_fedex)) $carrier_fedex=array('',-1,-1);
  $carrier_fedex_ver=get_option('pb_carrier_fedex_ver'); if(empty($carrier_fedex_ver)) $carrier_fedex_ver=array('','','','','');
  $carrier_ups=get_option('pb_carrier_ups'); if(empty($carrier_ups)) $carrier_ups=array('',-1,-1);
  $carrier_ups_ver=get_option('pb_carrier_ups_ver'); if(empty($carrier_ups_ver)) $carrier_ups_ver=array('','','','','');

  $quote=get_option('pb_cart_quote'); if(!empty($quote)) $quote='checked';
  $origin_facility=get_option('pb_origin_facility');
  $wc_method=get_option('pb_wc_method'); if(!is_array($wc_method)) $wc_method=array('');
  
  
  if(isset($_POST['enabled_carriers'])) {
    // Register FedEx Account
    if($enabled_carriers[2]>0 && !empty($carrier_fedex[0])) {
      if($_POST['register_fedex_prod']>0 && empty($carrier_fedex_prod_id) && !empty($pkey)) $error.=espb_register_carrier('FEDEX',$carrier_fedex,'prod',$carrier_fedex_ver);
      if($_POST['register_fedex_sandbox']>0 && empty($carrier_fedex_sandbox_id) && !empty($skey)) $error.=espb_register_carrier('FEDEX',$carrier_fedex,'sandbox',$carrier_fedex_ver);
    }
    
    // Register UPS Account
    if($enabled_carriers[3]>0 && !empty($carrier_ups[0])) {
      if($_POST['register_ups_prod']>0 && empty($carrier_ups_prod_id) && !empty($pkey)) $error.=espb_register_carrier('UPS',$carrier_ups,'prod',$carrier_ups_ver);
      if($_POST['register_ups_sandbox']>0 && empty($carrier_ups_sandbox_id) && !empty($skey)) $error.=espb_register_carrier('UPS',$carrier_ups,'sandbox',$carrier_ups_ver);
    }

    $carrier_fedex_prod_id=get_option('pb_carrier_fedex_prod_id');
    $carrier_fedex_sandbox_id=get_option('pb_carrier_fedex_sandbox_id');
    $carrier_ups_prod_id=get_option('pb_carrier_ups_prod_id');
    $carrier_ups_sandbox_id=get_option('pb_carrier_ups_sandbox_id');
  }
  ?>

  <div class='wrap'>
    <img style='width:6em;margin-right:1em' src='<?php if($white_label=='checked') {if(!empty($logo_url)) echo $logo_url;} else echo plugins_url('/assets/icon-trns.png',__FILE__);?>'>
    <h3 style='display:inline-block;letter-spacing:.3em;font-variant-caps:all-petite-caps;font-weight:normal'>Shipping Admin</h3>
    <br><br>
    <?php 

    if(!empty($mstatus)) echo "<div class='pb_alert' style='border-left:1em solid red'>$mstatus</div>";

    if(!empty($error)) { ?> 
      <div class='pb_alert' style='border-left:1em solid #d70080'>
        <?php echo $error; ?>
      </div>
    <?php }

    if($enabled_carriers[2]>0 && !empty($carrier_fedex[0])) 
      if((empty($carrier_fedex_prod_id) && !empty($pkey)) || (empty($carrier_fedex_prod_id) && empty($carrier_fedex_sandbox_id) && !empty($skey))) { $error=1; ?> 
      <div class='pb_alert' style='border-left:1em solid orange'>
        <span class='dashicons dashicons-warning'></span> FexEx Account Not Linked <a href='#config_carrier'>Provide your FedEx account information to complete setup</a>.
      </div>
    <?php }

    if($enabled_carriers[3]>0 && !empty($carrier_ups[0])) 
      if((empty($carrier_ups_prod_id) && !empty($pkey)) || (empty($carrier_ups_sandbox_id) && !empty($skey))) { $error=1; ?> 
      <div class='pb_alert' style='border-left:1em solid orange'>
        <span class='dashicons dashicons-warning'></span> UPS Account Not Linked <a href='#config_carrier'>Provide your UPS account information to complete setup</a>.
      </div>
    <?php }

    if(empty($merchant_id)) {$error=1; 
      if(empty($quote)) { ?> 
        <div class='pb_alert' style='border-left:1em solid #d92593'>
          <span class='dashicons dashicons-warning'></span> <b>Pitney Bowes Merchant Portal</b> <div style='display:inline-block;font-size:.9em'>(Required to purchase labels)</div><br>
          <a href='https://www.pbshippingmerchant.pitneybowes.com/create/landingPage?developerID=<?php echo $sdeveloper_id;?>' target='_blank'><button>Get Started</button></a>
        </div><?php 
      }
    }

    if($wc_active!==1) { $error=1; ?>
      <div class='notice notice-error' style='padding:1em'> WooCommerce install required: <a href='https://wordpress.org/plugins/woocommerce/' target='_blank'>Download for free</a> or <a href='plugins.php' target='_blank'>Activate</a>.</div>
    <?php } ?>

    <div class='pb_alert' style='border-left:1em solid #207cb0;width:max-content;white-space:nowrap'>
      <span class="dashicons dashicons-editor-help"></span> <b>Need help?</b><br>
      <div style='background:#fafafa;margin:1em;padding:1em 3em 1em 1em;border:1px solid #eee;'>
        <ul style='list-style:none;padding:initial;'>
          <li><span class="dashicons dashicons-arrow-right-alt2"></span> Pitney Bowes <a href='https://docs.shippingapi.pitneybowes.com/contact-us.html' target='_blank'>Technical Support</a><br>
          <li><span class="dashicons dashicons-arrow-right-alt2"></span> Plugin Support <a href='https://richardlerma.com/contact' target='_blank'>RLDD</a><br>
          <li><span class="dashicons dashicons-arrow-right-alt2"></span> Advanced<a href='#keys' onclick="pb_tab('keys')"> Setup Options</a>
        </ul>
      </div>
    </div>

    <?php if(empty($error) && !empty($merchant_id)) { ?>
      <div class='pb_alert' style='border-left:1em solid #00b42b'><span class='dashicons dashicons-yes-alt' style='color:#00bc2d'></span> <b>Merchant Setup complete</b><br>
        <div style='background:#fafafa;margin:1em;padding:1em;border:1px solid #eee'><b>Begin shipping:</b><br><br>
          <div><span class="dashicons dashicons-groups" style='color:#b8b7b7'></span> Users with the WooCommerce privilege "edit_shop_orders" may print labels.</div>
          <div><span class="dashicons dashicons-align-pull-left" style='color:#b8b7b7'></span> Find the "Ship Queue" menu under WooCommerce, or find the "Ship" button next to each order in the WooCommerce <a href='edit.php?post_type=shop_order' style='font-weight:bold;text-decoration:none'>Order List</a></div>
        </div>
      </div><?php 
    } ?>

    <style>
      .nav-tab{background:#e7e7eb}
      .nav-tab.nav_sel{background:#fff}
      .pb_req{margin-left:.2em;color:#d70080;font-weight:bold;font-size:1.3em}
      .pb_alert{background:#fff;border:1px solid #ddd;padding:1.5em;margin-bottom:1em}
      .pb_alert ul{list-style:circle;padding:revert}
      .dashicons{vertical-align:text-top;transform:scale(.8);color:#207cb0;cursor:pointer}
      .dashicons-image-rotate,.dashicons-remove{color:#d82626}
      .dashicons-warning{color:#e31d92}
      #pb_admin a{display:inline-block;cursor:pointer;text-decoration:none;outline:none;box-shadow:none}
      #pb_admin a:hover .dashicons{transform:scale(.9)}
      #pb_admin td{padding:1em}
      #pb_admin input,#pb_admin select{margin:.5em 0}
      #pb_admin select{vertical-align:inherit}
      #pb_admin input.short{width:100px}
      #pb_admin table tr:nth-child(even){background:#f5f5f5}
      #pb_admin td.items div{padding:1em;border:1px solid #ccc;border-radius:5px;margin-bottom:1em}
      #pb_admin td.items div:nth-child(even){background:#fff}
      #pb_admin td.items div a{float:right;margin:.3em;zoom:1.5}
      #pb_admin td div{margin-bottom:1em;-webkit-transition:all .5s;transition:all .5s}
      #pb_admin .pb_new{opacity:1}
      #pb_admin .pb_new select{background:#f3f5f6}
      #pb_admin div.pb_del{opacity:0}
      #pb_admin .pb_ids{background-color:#eee}
      #pb_admin span.instr{padding:.3em 1em .3em .5em;margin:1em;background:#f1f9fd85;color:#787879;border:1px solid #ccc;border-radius:5px;vertical-align:middle}
      #pb_admin span.instr .dashicons-info-outline{pointer-events:none;color:#888}
      #pb_admin .items select[name="wc_method[]"]:first-of-type{color:#2271b1;font-weight:600;font-size:1.2em}
      #pb_admin input[type='submit']:hover,#dev_reg{background:#fff;color:#3e53a4}
      #pb_admin input[type='submit'],#dev_reg:hover{color:#fff;background:#3e53a4}
      .keys input[type=search]{padding:.5em}
      .pb_alert .err{padding:1em;border:2px solid #eee;font-size:.9em}
      .pb_alert .req{max-height:1.8em;padding:.4em;font-size:.8em;overflow:hidden;outline:none;background:#0073aa;color:#fff;white-space:pre;-webkit-transition:all 1s;transition:all 1s}
      .pb_alert .req:focus{max-height:49em;overflow:auto}
      .pb_alert .req:before{content:'API Call Details:';display:block;font-weight:bold;font-size:1em;margin:0 0 1em .3em;cursor:pointer}
      .pb_alert button{cursor:pointer;margin:.5em 0;padding:1em 5em;border:1px solid #ccc;background:#fff;border-radius:3px;appearance:none;width:-webkit-fill-available;max-width:20em}
      .pb_alert button:hover{background:#f1f9ff}
      .carrier_acct_id{background:#eee;opacity:.5}
      #config_FE .button,#config_UP .button{background:#fff;padding:.5em}
    </style>

    <script>
      function pb_tab(t) {
        pb_getE('pb_tab').value=t;
        var tre=document.querySelectorAll('#pb_admin tr');
        tre.forEach(tr=>{
          var sel=tr.classList.contains(t);
          if(tr.classList.contains('form_save')) sel=true;
          tr.style.display=sel?'table-row':'none';
        });
        var navItems=pb_getEC('nav-tab');
        for(var i=0; i<navItems.length; i++) {
          var navItem=navItems[i];
          if(navItem.onclick) {
            isT=navItem.onclick.toString().includes("'"+t+"'");
            if(isT) navItem.classList.add('nav_sel'); else navItem.classList.remove('nav_sel');
          }
        }
      }
    </script>
    <br>
    <div id='pb_tabs' style='display:flex'>
      <a href='#!' class='nav-tab' onclick="pb_tab('setup')">Setup</a>
      <a href='#!' class='nav-tab' onclick="pb_tab('checkout')">Cart Live Rates</a>
      <a href='#!' class='nav-tab' onclick="pb_tab('tracking')">Tracking</a>
      <a href='#!' class='nav-tab' onclick="open_pb_queue();">Ship Queue</a>
      <a href='#!' class='nav-tab' onclick="open_pb_report();">Reports</a>

    </div>
    <form id='pb_admin' method='post' action='<?php echo admin_url('admin.php?page=pb-admin');?>' onchange="unsaved_changes=true;" onsubmit="unsaved_changes=false;">
      <table style='background:#fff;border:1px solid #ddd;padding:1em;width:-webkit-fill-available;max-width:90em'>

        <tr class='<?php if(empty($merchant_id)) echo 'setup keys'; else echo 'keys'; ?>'>
          <td nowrap style='vertical-align:top'>
            <div style='margin-top:2em;font-weight:500;font-size:1.1em'>Merchant Portal Features</div>
            <span class='dashicons dashicons-yes-alt' style='color:#00bc2d'></span> Label Purchasing<br>
            <span class='dashicons dashicons-yes-alt' style='color:#00bc2d'></span> More accurate Live Rates<br>
            <span class='dashicons dashicons-yes-alt' style='color:#00bc2d'></span> Manage Carriers<br>
            <span class='dashicons dashicons-yes-alt' style='color:#00bc2d'></span> Shipment Tracking<br>
          </td>
          <td>
            <div style='font-size:1.2em;color:#3e53a4;padding:2em 0;border-radius:5px;'>
              <div class='pb_alert' style='float:left;margin-right:2em;background:#3e53a4;color:#fff;border-radius:5px;font-size:.8em'>
                <div><img src='https://www.pbshippingmerchant.pitneybowes.com/assets/images/logo-white.svg'> <b>Merchant Portal</b></div>
                <?php if(empty($merchant_id)) { ?><a href='https://www.pbshippingmerchant.pitneybowes.com/create/landingPage?developerID=<?php echo $sdeveloper_id;?>' target='_blank' style='display:block'><button>Get Started</button></a>
                <?php } else { ?><button style='opacity:.8;pointer-events:none;font-size:1.1em;color:green'><span class='dashicons dashicons-yes-alt' style='color:#00bc2d'></span>Connected</button></a><?php } ?>
                <a href='https://www.pbshippingmerchant.pitneybowes.com/login' target='_blank' style='display:block'><button>Log In</button></a>
              </div>
              <?php if(empty($merchant_id)) { ?>
              <div style='float:left;width:min-content'>
                <div style='white-space:nowrap'><b>1. Create your Pitney Bowes Merchant Account<br><br>2. Enter your Merchant Account credentials below</b></div>
                <?php if(!empty($mstatus)) echo "<div class='pb_alert' style='border-left:1em solid red'>$mstatus</div>"; ?>
                <input type='search' class='regular-text' name='merchant_name' id='merchant_name' placeholder='Account Email' title='Account Email' value='<?php echo $mname;?>' autocomplete='false'><br>
                <input type='search' class='regular-text' name='merchant_pw' id='merchant_pw' style='-webkit-text-security:disc' placeholder='Account Password' title='Account Password' value='<?php echo $mpw;?>' autocomplete='false'><br>
                <div><span class="dashicons dashicons-editor-help"></span><span style='font-size:.7em'> By connecting your Merchant account, you agree to share support details: email, url, and version with developer (ID <?php echo $sdeveloper_id;?>). Credentials are used for initial authentication with Pitney Bowes and are not shared or saved to the database.</span></div>
                <input type='submit' class='page-title-action' style='padding:1em 2em' value='Connect Account' onclick='unsaved_changes=false;'>
              </div>
              <?php } ?>
            </div>
          </td>
        </tr>

        <tr class='keys' id='keys'>
          <td nowrap>Sandbox<span class='pb_req'>*</span></td><td>

            <input type='search' pattern='[0-9]{7,9}' class='regular-text' name='sandbox_developer_id' placeholder='Developer ID' title='8 Digit Developer ID' value='<?php echo $sdeveloper_id;?>'>
            <a href='https://developerhub.shippingapi.pitneybowes.com/shipping/account' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your Developer ID</a><br>

            <input type='search' pattern='[0-9]{10,12}' class='regular-text' name='sandbox_merchant_id' placeholder='Merchant ID' title='10 Digit Merchant ID' value='<?php echo $smerchant_id;?>'>
            <a href='https://developerhub.shippingapi.pitneybowes.com/shipping/associated-merchants' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your Merchant ID</a><br>

            <?php if(!empty($skey.$ssec)) { ?>
              <div id='sandbox_keys'>
                <input type='search' readonly class='regular-text' style='pointer-events:none' value='xxxxxxxxxxxxxxxxxxxxxxxxxxx<?php echo substr($skey,-5);?>'>
                <a onclick="sandbox_keys.style.display='none';sandbox_key_input.style.display='block';sandbox_key.required=true;sandbox_secret.required=true;" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Replace Keys</a><br>
                <input type='search' readonly class='regular-text' style='pointer-events:none' value='xxxxxxxxxxx<?php echo substr($ssec,-5);?>' autocomplete='off'>
              </div>
            <?php $display_sandbox='none';} else $display_sandbox='block'; ?>
              <div id='sandbox_key_input' style='display:<?php echo $display_sandbox;?>'>
                <input type='search' pattern='[A-Za-z0-9]{30,50}' class='regular-text' name='sandbox_key' id='sandbox_key' placeholder='Sandbox Key' title='Sandbox Key' value=''>
                <a href='https://developerhub.shippingapi.pitneybowes.com/shipping/api-keys' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your API Keys</a><br>
                <input type='search' pattern='[A-Za-z0-9]{16,29}' class='regular-text' name='sandbox_secret' id='sandbox_secret' style='-webkit-text-security:disc' placeholder='Sandbox Secret' title='Sandbox Secret' value='' autocomplete='off'>
              </div>
          </td>
        </tr>

        </tr>
        <tr class='keys'>
          <td nowrap>Production</td><td>

            <input type='search' pattern='[0-9]{7,9}' class='regular-text' name='developer_id' placeholder='Developer ID' title='8 Digit Developer ID' value='<?php echo $developer_id;?>'>
            <a href='https://developerhub.shippingapi.pitneybowes.com/shipping/account' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your Developer ID</a><br>

            <input type='search' pattern='[0-9]{10,12}' class='regular-text' name='merchant_id' placeholder='Merchant ID' title='10 Digit Merchant ID' value='<?php echo $merchant_id;?>'>
            <a href='<?php if($developer_id==94649849) echo 'https://www.pbshippingmerchant.pitneybowes.com/accountinformation'; else echo 'https://developerhub.shippingapi.pitneybowes.com/shipping/associated-merchants';?>' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your Merchant ID</a><br>

            <?php if(!empty($pkey.$psec)) { ?>
              <div id='production_keys'>
                <input type='search' readonly class='regular-text' style='pointer-events:none' value='xxxxxxxxxxxxxxxxxxxxxxxxxxx<?php echo substr($pkey,-5);?>'>
                <a onclick="production_keys.style.display='none';production_key_input.style.display='block';production.required=true;production_secret.required=true;" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Replace Keys</a><br>
                <input type='search' readonly class='regular-text' style='pointer-events:none' value='xxxxxxxxxxx<?php echo substr($psec,-5);?>' autocomplete='off'>
              </div>
            <?php $display_production='none'; } else $display_production='block'; ?>
              <div id='production_key_input' style='display:<?php echo $display_production;?>'>
                <input type='search' pattern='[A-Za-z0-9]{30,50}' class='regular-text' name='production_key' id='production_key' placeholder='Production Key' title='Production Key' value=''>
                <a href='https://developerhub.shippingapi.pitneybowes.com/shipping/api-keys' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your API Keys</a><br>
                <input type='search' pattern='[A-Za-z0-9]{16,29}' class='regular-text' name='production_secret' id='production_secret' style='-webkit-text-security:disc' placeholder='Production Secret' title='Production Secret' value='' autocomplete='off'>
              </div>
          </td>
        </tr>

        <tr class='keys'>
          <td nowrap>Developer Account (optional)</td><td>
          <a id='dev_reg' href='https://developerhub.shippingapi.pitneybowes.com/signup/new-account' style='margin:1em 0' target='_blank' class='button'>Register for Pitney Bowes Developer Hub</a>
        </tr>

        <tr class='keys'><td colspan='2'></td></tr>

        <tr class='setup'>
          <td nowrap style='vertical-align:text-top'>Origin Facilities<span class='pb_req'>*</span></td>
          <td class='items'><?php
            if(is_array($client_id)) $f_ct=count($client_id); else $f_ct=1;
            $i=0;
            if($f_ct>0) while($i<$f_ct) { ?>
              <div id='facility_<?php echo $i;?>' class='facility'>
                <input type='text' required name='client_id[]' placeholder='Client ID*' pattern="[0-9A-Z]{0,9}" title='Client ID (uppercase)' class='pb_ids' value="<?php if(isset($client_id[$i])) echo $client_id[$i];?>">
                <input type='text' required name='client_facility_id[]' placeholder='Client Facility ID*' pattern="[0-9]{0,9}" title='Client Facility ID (Number)' class='pb_ids' value="<?php if(isset($cfac_id[$i])) echo $cfac_id[$i];?>">
                <input type='text' required name='carrier_facility_id[]' placeholder='Carrier Facility ID*' pattern="[0-9]{0,9}" title='Carrier Facility ID (Number)' class='pb_ids' value="<?php if(isset($carrier_facility_id[$i])) echo $carrier_facility_id[$i];?>"><br>
                <input type='text' required name='client_facility_name[]' placeholder='Client Facility Name*' pattern='[A-Za-z0-9-() ]{2,20}' title='Facility Name (20 char limit, no special characters)' value="<?php if(isset($cfac_name[$i])) echo $cfac_name[$i];?>">
                <select required name='client_facility_type[]'>
                  <option value=''>Facility Type
                  <option value='1' <?php if(isset($cfac_type[$i])) if($cfac_type[$i]>0) echo 'selected';?>>Residential
                  <option value='0' <?php if(isset($cfac_type[$i])) if($cfac_type[$i]<1) echo 'selected';?>>Non-Residential
                </select>
                
                <input type='search' name='client_facility_induction_zip[]' title='5 Digit Induction Zipcode (If different from facility)' placeholder='Induction Zipcode' pattern='[0-9]{5}' value='<?php if(isset($cfac_induction_zip[$i])) echo $cfac_induction_zip[$i];?>'><br>
                <input type='search' required name='client_facility_address[]' placeholder='Address' class='regular-text' value='<?php if(isset($cfac_address[$i])) echo $cfac_address[$i];?>'><br>
                <input type='search' name='client_facility_address2[]' placeholder='Address Line 2' class='regular-text' value='<?php if(isset($cfac_address2[$i])) echo $cfac_address2[$i];?>'><br>
                <input type='search' required name='client_facility_city[]' placeholder='City' pattern='[A-Za-z ]{2,50}'class='short regular-text' value='<?php if(isset($cfac_city[$i])) echo $cfac_city[$i];?>'>
                <input type='search' required name='client_facility_state[]' title='2 Character State Code' placeholder='State' pattern='[A-Za-z]{2,2}' class='short regular-text' value='<?php if(isset($cfac_state[$i])) echo $cfac_state[$i];?>'>
                <input type='search' required name='client_facility_zip[]' title='5-10 Digit Zipcode' placeholder='Zip' pattern='[0-9\-]{5,10}' class='short regular-text' value='<?php if(isset($cfac_zip[$i])) echo $cfac_zip[$i];?>'>
                <input type='search' required name='client_facility_country[]' title='2 Character Country Code' placeholder='Country' pattern='[A-Za-z]{2,2}' class='short regular-text' value='<?php if(isset($cfac_country[$i])) echo $cfac_country[$i];?>'><br>
                <input type='tel' required name='client_facility_tel[]' title='10 Digit Phone Number 123-456-7899' placeholder='Phone' pattern='[0-9\-]{10,12}' class='short regular-text' value='<?php if(isset($cfac_tel[$i])) echo $cfac_tel[$i];?>'>
                <input type='email' required name='client_facility_email[]' title='Email' placeholder='Email' class='regular-text' value='<?php if(isset($cfac_email[$i])) echo $cfac_email[$i];?>'>
                <input type='hidden' name='client_facility_active[]' value=<?php if($cfac_active[$i]>0) echo 1; else echo 0;?>>
                <input type='checkbox' <?php if($cfac_active[$i]>0) echo 'checked';?> onclick="pb_checkbox(this.previousElementSibling,this.checked);"> Enable Ship From

                <a href='#!' onclick="pb_add_item('add',this.parentElement,'facility');"><span class='dashicons dashicons-insert'></span></a>
                <a href='#!' onclick="pb_add_item('remove',this.parentElement,'facility');" <?php if($i<1) echo "style='display:none'";?>><span class='dashicons dashicons-remove'></span></a>
              </div><?php
              $i++;
            } ?>
          </td>
        </tr>

        <tr id='config_carrier' class='setup'>
          <td nowrap>Enabled Carriers</td>
          <td>
            <input type='checkbox' <?php if($enabled_carriers[0]>0) echo 'checked';?> onclick="pb_enable_carrier('US',this.checked);"> USPS<br>
            <span <?php if(empty($merchant_id)) echo "style='opacity:.5;pointer-events:none'";?>>
              <input type='checkbox' <?php if($enabled_carriers[1]>0) echo 'checked';?> onclick="pb_enable_carrier('PB',this.checked);"> Pitney Bowes<br>
              <input type='checkbox' <?php if($enabled_carriers[2]>0) echo 'checked';?> onclick="pb_enable_carrier('FE',this.checked);"> FedEx<br>
              <input type='checkbox' <?php if($enabled_carriers[3]>0) echo 'checked';?> onclick="pb_enable_carrier('UP',this.checked);"> UPS<br>
            </span>

            <input type='hidden' name='enabled_carriers[]' id='carrier_US' value=<?php echo $enabled_carriers[0];?>>
            <input type='hidden' name='enabled_carriers[]' id='carrier_PB' value=<?php echo $enabled_carriers[1];?>>
            <input type='hidden' name='enabled_carriers[]' id='carrier_FE' value=<?php echo $enabled_carriers[2];?>>
            <input type='hidden' name='enabled_carriers[]' id='carrier_UP' value=<?php echo $enabled_carriers[3];?>>
          </td>
        </tr>


        <tr class='setup'>
          <td class='config_FE' <?php if($enabled_carriers[2]<1) echo "style='display:none'";?> nowrap>FedEx<span class='pb_req'>*</span></td></td>
          <td class='config_FE' <?php if($enabled_carriers[2]<1) echo "style='display:none'";?>>
            <input type='search' name='carrier_fedex[]' placeholder='FedEx Account Number' value='<?php echo $carrier_fedex[0];?>'>
            <a href='https://www.fedex.com/apps/myprofile/accountmanagement/?locale=en_US&cntry_code=us' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your FedEx Account Number</a><br>
            <select name='carrier_fedex[]'>
              <option value='-1'>Origin Address
              <?php echo espb_list_facility($carrier_fedex[1],1);?>
            </select><span class='instr'><span class='dashicons dashicons-info-outline'></span> The billing address as it appears on the FedEx statement.</span>
            <br>
            <select name='carrier_fedex[]'>
              <option value='-1'>Contact Address
              <?php echo espb_list_facility($carrier_fedex[2],1);?>
            </select><span class='instr'><span class='dashicons dashicons-info-outline'></span> The contact address as it appears on the FedEx account.</span>
            <br><?php

            if(!empty($carrier_fedex_sandbox_id)) { ?><br>
              <span class='dashicons dashicons-yes-alt' id='carrier_fedex_sandbox_confirm' style='color:#00bc2d'></span> Sandbox registration complete.<br>
              <input type='search' class='carrier_acct_id regular-text' name='carrier_fedex_sandbox_id' id='carrier_fedex_sandbox_id' placeholder='Sandbox FedEx Carrier Account Id' value='<?php echo $carrier_fedex_sandbox_id;?>'> 
              <a onclick="if(confirm('Are you sure you want to delete the FedEx sandbox key?')) {carrier_fedex_sandbox_id.value='';carrier_fedex_sandbox_confirm.display='none';" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Remove Key</a><br><br>
            <?php } else if(!empty($skey)) {?>
              <input type='button' value='Submit FedEx Sandbox Registration' class='button' onclick='register_fedex_sandbox.value=1;this.form.submit();'>
            <?php }

            if(!empty($carrier_fedex_prod_id)) { ?><br>
              <span class='dashicons dashicons-yes-alt' id='carrier_fedex_prod_confirm' style='color:#00bc2d'></span> Production registration complete.<br>
              <input type='search' class='carrier_acct_id regular-text' name='carrier_fedex_prod_id' placeholder='Production FedEx Carrier Account Id' value='<?php echo $carrier_fedex_prod_id;?>'> 
              <a onclick="if(confirm('Are you sure you want to delete the FedEx prod key?')) {carrier_fedex_prod_id.value='';carrier_fedex_prod_confirm.display='none';" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Remove Key</a><br><br>
            <?php } else if(!empty($pkey)) {?>
              <input type='button' value='Submit FedEx Prod Registration' class='button' onclick='register_fedex_prod.value=1;this.form.submit();'>
            <?php } ?>
            
            <input type='hidden' name='register_fedex_sandbox' id='register_fedex_sandbox' value=0>
            <input type='hidden' name='register_fedex_prod' id='register_fedex_prod' value=0>

            <div style='padding:1em;border:1px solid #ccc;border-radius:5px;margin-bottom:1em;background:#fff'>
              <b>FedEx Account Verification</b><br>
              <input type='search' name='carrier_fedex_ver[]' placeholder='Account Holder Name' pattern='.{2,20}' value='<?php echo $carrier_fedex_ver[0];?>'><span class='instr'><span class='dashicons dashicons-info-outline'></span> First and Last Name as it appears on your FedEx billing statement.</span><br>
            </div>
          </td>
        </tr>

        <tr class='setup'><td colspan='2'></td></tr>

        <tr class='setup'>
          <td class='config_UP' <?php if($enabled_carriers[3]<1) echo "style='display:none'";?> nowrap>UPS<span class='pb_req'>*</span></td></td>
          <td class='config_UP' <?php if($enabled_carriers[3]<1) echo "style='display:none'";?>>
            <input type='search' class='regular-text' name='carrier_ups[]' pattern='.{0,6}' title='6 Character Account Number' class='short regular-text' placeholder='UPS Account Number' value='<?php echo $carrier_ups[0];?>'>
            <a href='https://wwwapps.ups.com/ppc/ppc.html?loc=en_US#/payment' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your UPS Account Number</a><br>
            <select name='carrier_ups[]'>
              <option value='-1'>Account Address
              <?php echo espb_list_facility($carrier_ups[1],1);?>
            </select><span class='instr'><span class='dashicons dashicons-info-outline'></span> The billing address as it appears on the UPS statement.</span>
            <br>
            <select name='carrier_ups[]'>
              <option value='-1'>Contact Address
              <?php echo espb_list_facility($carrier_ups[2],1);?>
            </select><span class='instr'><span class='dashicons dashicons-info-outline'></span> The contact address as it appears on the UPS account.</span>
            <br><?php

            if(!empty($carrier_ups_sandbox_id)) { ?><br>
              <span class='dashicons dashicons-yes-alt' id='carrier_ups_sandbox_confirm' style='color:#00bc2d'></span> Sandbox registration complete.<br>
              <input type='search' class='carrier_acct_id regular-text' name='carrier_ups_sandbox_id' id='carrier_ups_sandbox_id' placeholder='Sandbox UPS Carrier Account Id' value='<?php echo $carrier_ups_sandbox_id;?>'> 
              <a onclick="if(confirm('Are you sure you want to delete the UPS sandbox key?')) {carrier_ups_sandbox_id.value='';carrier_ups_sandbox_confirm.display='none';" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Remove Key</a><br><br>
            <?php } else if(!empty($skey)) {?>
              <input type='button' value='Submit UPS Sandbox Registration' class='button' onclick='register_ups_sandbox.value=1;this.form.submit();'>
            <?php }

            if(!empty($carrier_ups_prod_id)) { ?><br>
              <span class='dashicons dashicons-yes-alt' id='carrier_ups_prod_confirm' style='color:#00bc2d'></span> Production registration complete.<br>
              <input type='search' class='carrier_acct_id regular-text' name='carrier_ups_prod_id' placeholder='Production UPS Carrier Account Id' value='<?php echo $carrier_ups_prod_id;?>'> 
              <a onclick="if(confirm('Are you sure you want to delete the UPS prod key?')) {carrier_ups_prod_id.value='';carrier_ups_prod_confirm.display='none';" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Remove Key</a><br><br>
            <?php } else if(!empty($pkey)) {?>
              <input type='button' value='Submit UPS Prod Registration' class='button' onclick='register_ups_prod.value=1;this.form.submit();'>
            <?php } ?><br>

            <input type='hidden' name='register_ups_sandbox' id='register_ups_sandbox' value=0>
            <input type='hidden' name='register_ups_prod' id='register_ups_prod' value=0>

            <div style='padding:1em;border:1px solid #ccc;border-radius:5px;margin-bottom:1em;background:#fff'>
              <b>UPS Account Verification</b><br>
              <input type='search' name='carrier_ups_ver[]' placeholder='Title' pattern='.{2,20}' value='<?php echo $carrier_ups_ver[0];?>'><span class='instr'><span class='dashicons dashicons-info-outline'></span> Title of the primary contact when setting up the UPS account. e.g. Manager</span><br>
              <input type='search' name='carrier_ups_ver[]' placeholder='Number' pattern='.{0,13}' value='<?php echo $carrier_ups_ver[1];?>'><span class='instr'><span class='dashicons dashicons-info-outline'></span> Last invoice number (9-13 digits).</span><br>
              <input type='search' name='carrier_ups_ver[]' placeholder='ID' pattern='.{0,8}' value='<?php echo $carrier_ups_ver[2];?>'><span class='instr'><span class='dashicons dashicons-info-outline'></span> Last invoice control ID (4-8 digits).</span><br>
              <input type='search' name='carrier_ups_ver[]' placeholder='yyyyMMdd' pattern='.{0,8}' title='Format date as yyyyMMdd' class='short regular-text' value='<?php echo $carrier_ups_ver[3];?>'><span class='instr'><span class='dashicons dashicons-info-outline'></span> Last invoice date (Format as yyyyMMdd).</span><br>
              <input type='number' name='carrier_ups_ver[]' placeholder='Amount' pattern='.{0,8}' step='.01' class='short regular-text' value='<?php echo $carrier_ups_ver[4];?>'><span class='instr'><span class='dashicons dashicons-info-outline'></span> Last invoice amount.</span><br>
              <select name='carrier_ups_ver[]'>
                <option value=''>Last invoice currency
                <option value='USD' <?php if(isset($carrier_ups_ver[5])) if($carrier_ups_ver[5]=='USD') echo 'selected';?>>USD
                <option value='CAD' <?php if(isset($carrier_ups_ver[5])) if($carrier_ups_ver[5]=='CAD') echo 'selected';?>>CAD
              </select>
            </div>
          </td>
        </tr>

        <tr class='setup'><td colspan='2'></td></tr>
        <tr class='setup'>
          <td nowrap>White Label</td>
          <td>
            <input type='checkbox' <?php echo $white_label;?> name='white_label'>&nbsp; If selected, the plugin will use the site name and logo (if present) at the top of the shipping interface.<br>
            Optional logo URL <input type='url' name='white_label_url' class='regular-text' value='<?php echo $white_label_url;?>'> <a href='upload.php' target='_blank'>Open Media</a>
          </td>
        </tr>

        <tr class='setup'>
          <td nowrap>Packing Slip</td>
          <td>
            <div>Optional note to customer:</div>
            <textarea name='slip_note' style='width:100%;padding:.5em'><?php echo $slip_note;?></textarea>
          </td>
        </tr>
            
        <tr class='setup'>
          <td nowrap>Restrict Plugin</td>
          <td><input type='checkbox' <?php echo $restrict;?> name='restrict'>&nbsp; If selected, plugin features will be restricted to your user ID only. This setting is useful for testing.</td>
        </tr>

        <tr class='setup'>
          <td nowrap>Uninstall Option<span class='pb_req'>*</span></td>
          <td>
            <select name='uninstall' required>
              <option value='' selected disabled>Uninstall Option
              <option value='keep' <?php if($uninstall=='keep') echo 'selected';?>>Keep all settings
              <option value='delete' <?php if($uninstall=='delete') echo 'selected';?>>Delete all settings
            </select>
          </td>
        </tr>

        <tr class='checkout'>
          <td nowrap style='vertical-align:text-top;font-size:1.1em;font-weight:600;padding-top:3em;padding-bottom:3em;'>Cart Live Rates</td>
          <td>
            <input type='checkbox' <?php echo $quote;?> name='quote' id='cart_quote' onchange="pb_toggle_quote()" style='zoom:1.5;'>&nbsp; If selected, cart will display live rates when a valid shipping address is captured.
          </td>
        </tr>

        <tr class='checkout'>
          <td nowrap style='vertical-align:text-top'>Requirements</td>
          <td>
            <div><span class='dashicons dashicons-warning' style='color:#207cb0'></span> Shipping address must be entered at checkout.</div>
            <div><span class='dashicons dashicons-warning' style='color:#207cb0'></span> All products in cart must have assigned weights and dimensions.</div>
            <div><span class='dashicons dashicons-warning' style='color:#207cb0'></span> If live rate fails, the cart will display the default rate.</div>
          </td>
        </tr>

        <tr class='checkout'>
          <td nowrap style='vertical-align:text-top'>Default Rate</td>
          <td>
            <span style='display:inline-block'><b style='font-size:1.05em'>WC method</b>:&nbsp; Methods default to the cost on the WooCommerce shipping method until a valid address is captured.</span><br>
            <span style='display:inline-block;padding:.5em 0'><b style='font-size:1.05em'>Origin</b>:&nbsp; Optional on each method (below). Quotes use the origin facility as the destination until a valid address is captured.</span>
          </td>
        </tr>

        <tr class='checkout'>
          <td nowrap style='vertical-align:text-top'>Origin Facility</td>
          <td>
            <select name='origin_facility'>
              <option value=''>Origin
              <?php if($origin_facility=='') $origin_facility=get_option("pb_default_{$carrier_i}_facility_$user_id"); if($origin_facility=='') $origin_facility=0; echo espb_list_facility($origin_facility,0); ?>
            </select>
          </td>
        </tr>
        
        <tr class='checkout'>
          <td nowrap>New Method</span></td>
          <td>
            <div style='margin:1em 0;'>
              <a style='vertical-align:text-bottom' href='<?php echo admin_url('admin.php?page=wc-settings&tab=shipping');?>' class='button'>Create a New US-Zoned Shipping Method</a>
              <div style='display:inline-block;margin:0 1em;'> Or use an existing US-zoned method below.<br>Unsupported methods may be grayed out.</div>
            </div>
          </td>
        </tr>

        <tr class='checkout'>
          <td nowrap style='vertical-align:text-top'>Existing Methods</td>
          <td class='items'><?php
            $wc_methods='';
            if($wc_active>0) $wc_methods=espb_r("
              SELECT zone_name zone,method_id,instance_id
              ,IFNULL(meta,'a:1:{s:5:\"title\";s:7:\"Default\";}')meta
              ,CASE WHEN o.meta IS NULL OR l.location_code!='US' THEN 0 ELSE m.is_enabled END active
              FROM wp_woocommerce_shipping_zones z 
              JOIN wp_woocommerce_shipping_zone_locations l ON l.zone_id=z.zone_id
              JOIN wp_woocommerce_shipping_zone_methods m ON m.zone_id=z.zone_id
              LEFT JOIN (
                SELECT CAST(option_name AS CHAR) AS option_name, option_value meta
                FROM wp_options
                WHERE option_name LIKE 'woocommerce%settings'
                AND LENGTH(option_value)<250
                AND option_value LIKE '%title%'
              )o ON o.option_name=CONCAT('woocommerce_',CAST(m.method_id AS CHAR),'_',CAST(m.instance_id AS CHAR),'_settings')
              ORDER BY z.zone_order, m.method_order;
            ");

            $i=$m_ct=0;
            if(is_array($wc_method)) $m_ct=count($wc_method);
            if($m_ct>0) {
              while($i<$m_ct) {
                $wc_method_i=$wc_method[$i];
                $m_array=get_option("pb_wc_method_$wc_method_i");
                $carrier_i=$service_i=$formula_i=$cart_apply_i='';
                if($m_array) {$carrier_i=$m_array[0];$service_i=$m_array[1];$formula_i=$m_array[2];if(isset($m_array[3]))$cart_apply_i=$m_array[3];}
                ?>
                <div id='wc_method_<?php echo $i;?>'>
                  <select name='wc_method[]'>
                    <option value='' selected disabled>Choose Method (Required)
                    <?php echo espb_get_methods($wc_methods,$wc_method_i); ?>
                  </select>
                  
                  <span style='float:right'><input type='hidden' name='cart_apply[]' value='<?php echo $cart_apply_i;?>'><input type='checkbox' <?php if($cart_apply_i>0) echo 'checked';?> onclick="pb_checkbox(this.previousElementSibling,this.checked);">&nbsp; Default to Origin*</span><br>

                  <select name='carrier[]'>
                    <option value='' selected disabled>Carrier
                    <?php echo espb_list_carrier($carrier_i);?>
                  </select>

                  <select name='service[]'>
                    <option value='' selected disabled>Service
                    <?php echo espb_list_services($carrier_i,$service_i); ?>
                  </select>

                  <input type='text' name='formula[]' title='[ship_cost], [cart_value], [cart_qty], [cart_oz]' placeholder='Formula (optional)' style='min-width:15em' value='<?php echo $formula_i; ?>'>

                  <a href='#!' onclick="pb_add_item('add',this.parentElement,'wc_method');"><span class='dashicons dashicons-insert'></span></a>
                  <a href='#!' onclick="pb_add_item('remove',this.parentElement,'wc_method');" <?php if($i<1) echo "style='display:none'";?>><span class='dashicons dashicons-remove'></span></a>
                  
                </div><?php
                $i++;
              }
            } else echo "<a href='<?php echo admin_url('admin.php?page=wc-settings&tab=shipping');?>' class='page-title-action button'>Create a US-Zoned Shipping Method</a>";?>
          </td>
        </tr>

        <tr class='checkout'>
          <td nowrap style='vertical-align:text-top'>Definitions</td>
          <td>
           <div style='margin-bottom:2em'>Service quotes based on a 8x5x4 (LxWxH) 15oz box<?php if(isset($cfac_name[$origin_facility])) echo ' from '.$cfac_name[$origin_facility]; if(isset($cfac_city[$origin_facility])) echo ' to '.$cfac_city[$origin_facility];?>.</div>
           <div style='float:left;background:#fff;padding:1em;border-radius:3px;margin-right:2em'>
              <div style='display:block;font-weight:600;font-size:1.05em;margin:.5em 1em 1.5em 0'>Service Definitions*</div>
              <a href='https://pe.usps.com/text/dmm300/Notice123.htm' target='_blank'><span class="dashicons dashicons-info-outline"></span> USPS</a><br>
              <a href='https://www.fedex.com/en-us/shipping/services.html' target='_blank'><span class="dashicons dashicons-info-outline"></span> FedEx</a><br>
              <a href='https://www.ups.com/service-selector' target='_blank'><span class="dashicons dashicons-info-outline"></span> UPS</a>
            </div>
            <div style='float:left;background:#fff;padding:1em;border-radius:3px;margin-right:2em'>
              <div style='display:block;font-weight:600;font-size:1.05em;margin:.5em 1em 1em 0'>Formula Variables</div>
              <b style='color:#207cb0'>[ship_cost]</b> Live rate returned by PB<br>
              <b style='color:#207cb0'>[cart_value]</b> Total value in cart<br>
              <b style='color:#207cb0'>[cart_qty]</b> Total product quantity in cart<br>
              <b style='color:#207cb0'>[cart_oz]</b> Total weight of cart in ounces
            </div>
          </td>
        </tr>

        <tr class='tracking'>
          <td nowrap style='vertical-align:text-top;font-size:1.1em;font-weight:600;padding-top:3em;padding-bottom:3em;'>Track Shipments</td>
          <td>Fetch Frequency &nbsp;
            <select name='fetch_freq' id='fetch_freq' onchange="pb_toggle_ntf()">>
              <option value=90 <?php if($fetch_freq==90) echo 'selected';?>>90 min
              <option value=60 <?php if($fetch_freq==60) echo 'selected';?>>60 min
              <option value=30 <?php if(empty($fetch_freq) || $fetch_freq==30) echo 'selected';?>>30 min
              <option value=10 <?php if($fetch_freq==10) echo 'selected';?>>10 min
              <option value=-1 <?php if($fetch_freq==-1) echo 'selected';?>>Off (not recommended)
            </select>
            <div style='display:inline-block'><span class='dashicons dashicons-warning' style='color:#207cb0'></span> A maximum of 50 updates are fetched at once. Each fetch alternates between subscribers and non-subscribers.</div>
          </td>
        </tr>
        <tr class='tracking'>
          <td nowrap style='vertical-align:text-top'>Admin Notifications</td>
          <td>
            <input type='search' class='regular-text' name='admin_emails' placeholder='<?php echo get_option('admin_email');?>' title='Comma Separated Emails' value='<?php echo $admin_emails;?>'><br>
            <input type='checkbox' <?php echo $admin_exc_updates;?> name='admin_exc_updates'>&nbsp; Exception updates<br>
            <input type='checkbox' <?php echo $admin_rtn_updates;?> name='admin_rtn_updates'>&nbsp; Return shipments
          </td>
        </tr>
        <tr class='tracking'>
          <td nowrap style='vertical-align:text-top'>Customer Notifications</td>
          <td>
            <div style='background:#f5f5f5;width:fit-content;padding:1em;border-radius:3px;'>
              <b>Email</b><br>
              <input type='checkbox' <?php echo $email_updates;?> name='email_updates'>&nbsp; Out for delivery<br>
              <input type='checkbox' <?php echo $email_exc_updates;?> name='email_exc_updates'>&nbsp; Exceptions<br>
              <input type='checkbox' <?php echo $email_rtn_updates;?> name='email_rtn_updates'>&nbsp; Return shipment acceptance
            </div>

            <div style='background:#f5f5f5;width:fit-content;padding:1em;border-radius:3px;'>
              <b>SMS</b>
              <div style='font-size:.8em'>If enabled, SMS will take precedence over Email.</div>
              <select name='sms_updates'>
                <option value=0 <?php if($sms_updates<1) echo 'selected';?>>Off
                <option value=1 <?php if($sms_updates==1) echo 'selected';?>>To subscribers only
                <option value=2 <?php if($sms_updates==2) echo 'selected';?>>To ALL phone numbers
              </select>&nbsp; Out for delivery
              <br>
              <select name='sms_exc_updates'>
                <option value=0 <?php if($sms_exc_updates<1) echo 'selected';?>>Off
                <option value=1 <?php if($sms_exc_updates==1) echo 'selected';?>>To subscribers only
                <option value=2 <?php if($sms_exc_updates==2) echo 'selected';?>>To ALL phone numbers
              </select>&nbsp; Exceptions
              <br>
              <select name='sms_rtn_updates'>
                <option value=0 <?php if($sms_rtn_updates<1) echo 'selected';?>>Off
                <option value=1 <?php if($sms_rtn_updates==1) echo 'selected';?>>To subscribers only
                <option value=2 <?php if($sms_rtn_updates==2) echo 'selected';?>>To ALL phone numbers
              </select>&nbsp; Return shipment acceptance
            </div>
          </td>
        </tr>
        <tr class='tracking'>
          <td nowrap style='vertical-align:text-top'>SMS Requirements</td>
          <td>
            <div><span class='dashicons dashicons-warning' style='color:#207cb0'></span> To enable SMS, a <a href='https://dashboard.messagebird.com/en/sign-up?signup_source_id=157' target='_blank'>MessageBird account</a> must be configured. Add a MessageBird number & API keys in the next section.</div>
            <div><span class='dashicons dashicons-warning' style='color:#207cb0'></span> Record opt ins as sms_sb_[phone_number] in options table when using the "Subscribers Only" option.</div>
            <div><span class='dashicons dashicons-warning' style='color:#207cb0'></span> Opt out end point for email and SMS is <?php echo $site_url; ?>/?espb_unsubscribe={email / 10 digit phone}.</div>
          </td>
        </tr>
        <tr class='tracking'>
          <td nowrap style='vertical-align:text-top'>MessageBird API</td>
          <td>
            <input type='tel' name='mb_phone' title='MessageBird 10 Digit Phone Number 123-456-7899' placeholder='MessageBird Phone' pattern='[0-9\-]{10,12}' value='<?php echo $mb_phone;?>'><br>

            <?php if(!empty($mb_dev_key.$mb_prod_key)) { ?>
              <div id='mb_keys'>
                <input type='search' readonly class='regular-text' style='pointer-events:none' value='xxxxxxxxxxxxxxxxxxxxxxxxxxx<?php echo substr($mb_dev_key,-5);?>'> 
                <input type='search' readonly class='regular-text' style='pointer-events:none' value='xxxxxxxxxxx<?php echo substr($mb_prod_key,-5);?>' autocomplete='off'>
                <a onclick="mb_keys.style.display='none';mb_key_input.style.display='block';mb_dev_key.required=true;mb_prod_key.required=true;" style='color:#d70080'><span class="dashicons dashicons-image-rotate"></span> Replace Keys</a>
              </div>
            <?php $display_mb_keys='none'; } else $display_mb_keys='block'; ?>
              <div id='mb_key_input' style='display:<?php echo $display_mb_keys;?>'>
                <input type='search' pattern='[A-Za-z0-9]{25,40}' class='regular-text' name='mb_dev_key' id='mb_dev_key' style='-webkit-text-security:disc' placeholder='MessageBird Test Key' title='MessageBird Test Key' value='' autocomplete='off'> 
                <input type='search' pattern='[A-Za-z0-9]{25,40}' class='regular-text' name='mb_prod_key' id='mb_prod_key' style='-webkit-text-security:disc' placeholder='MessageBird Live Key' title='MessageBird Live Key' value='' autocomplete='off'>
                <a href='https://dashboard.messagebird.com/en/developers/access' target='_blank'><span class="dashicons dashicons-info-outline"></span> Find your API Keys</a>
              </div>
          </td>
        </tr>
        <tr class='form_save' style='background:#fff'>
          <td colspan='2'>
            <a href='update-core.php' target='_blank' class='page-title-action button' style='margin-top:3em'>Check for Updates</a>
            <?php echo wp_nonce_field('config_shipping','pb_config_shipping');?>
            <input type='hidden' id='pb_tab' name='tab'>
            <input type='submit' class='page-title-action' style='padding:1em 8em;float:right' value='Save' onclick='unsaved_changes=false;'>
          </td>
        </tr>

      </table>
    </form>

    <?php echo espb_inc_js('');?>
    <script>
      pb_tab('<?php if(isset($_REQUEST['tab'])) echo sanitize_text_field($_REQUEST['tab']); else echo 'setup';?>');
      var m_inc=1;
      var unsaved_changes=false;
      var usc_interval=setInterval(function() {
        if(document.readyState==='complete') {
          clearInterval(usc_interval);
          window.onbeforeunload=function(){return unsaved_changes ? 'If you leave this page you will lose unsaved changes.' : null;}
      }},100);
        
      function pb_enable_carrier(carrier,checked) {
        var fct=<?php echo $f_ct;?>; 
        var c=pb_getE('carrier_'+carrier);
        var cnfg=pb_getEC('config_'+carrier);
        if(checked>0) {
          c.value=1;
          if(fct>0) for(var i=0; i<cnfg.length; i++) {cnfg[i].style.display='table-cell';}
        } else {
          c.value=0;
          for(var i=0; i<cnfg.length; i++) {cnfg[i].style.display='none';}
        }
      }

      function pb_checkbox(id,checked) {
        if(!id) return;
        if(checked>0) id.value=1; else id.value=0;
      }

      function pb_toggle_quote() {
        var ck=pb_getE('cart_quote').checked;
        var trs=document.querySelectorAll('#pb_admin tr.checkout');
        trs.forEach((tr,idx)=>{
          if(idx>1) {
            if(ck) {tr.style.opacity=1;tr.style.pointerEvents='auto';}
            else {tr.style.opacity=0.3;tr.style.pointerEvents='none';}
          }
        });
      }
      <?php if(empty($quote)) echo 'pb_toggle_quote();';?>

      function pb_toggle_ntf() {
        var s=pb_getE('fetch_freq');
        var sel=s.options[s.selectedIndex].value;
        var trs=document.querySelectorAll('#pb_admin tr.tracking');
        trs.forEach((tr,idx)=>{
          if(idx>0) {
            if(sel>0) {tr.style.opacity=1;tr.style.pointerEvents='auto';}
            else {tr.style.opacity=0.3;tr.style.pointerEvents='none';}
          }
        });
      }
      <?php if($fetch_freq<0) echo 'pb_toggle_ntf();';?>

      function pb_add_item(a,p,t) {
        unsaved_changes=true;
        var m=pb_getE(t+'_0');
        if(a=='remove') {if(m.id!==p.id && confirm('Are you sure you want to delete this item?')) pb_remove(p); return false;}
        var i=document.createElement('div');
        m_inc++;
        i.id=t+'_'+m_inc;
        i.innerHTML=m.innerHTML;
        i.style.opacity=0;
        m.parentElement.append(i);
        i.lastElementChild.style.display='block';
        if(t=='facility') {
          var inc=0;
          var elm=i.firstElementChild;
          while(inc<=16) {
            if(inc<1) i.lastElementChild.previousElementSibling.previousElementSibling.checked=true;
            if(elm.value) elm.value='';
            elm=elm.nextElementSibling;
            inc++;
          }
        }
        setTimeout(function(){i.style.opacity='';i.classList.add('pb_new');},250);
      }
    </script>
  </div><?php
}