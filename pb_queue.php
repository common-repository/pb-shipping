<?php if(!defined('ABSPATH')) exit;
  
function espb_queue() {
  if(!is_user_logged_in()) {header("Location: ".site_url()."/wp-login.php?redirect_to=".urlencode($_SERVER['REQUEST_URI'])); exit();}
  espb_popup_css();
  if(!current_user_can('edit_shop_orders')) {echo 'Insufficient permissions'; exit();}
  if(isset($_GET['report_type'])) $report_type=sanitize_text_field($_GET['report_type']); else $report_type='unshipped';
  
  $order_id=$reason=$status=$af_reship=$af_reship_script='';
  $user_id=get_current_user_id();
  if(isset($_POST['order_id'])) $order_id=intval($_POST['order_id']);


  // Validate Parameters
  if(!empty($order_id) && current_user_can('edit_shop_orders')) {
    check_admin_referer('filter_queue','pb_filter_queue');
    $order_id=intval($_POST['order_id']);
    $action=intval($_POST['action']);
    $report_type='reship';
    if($action>0) {
      delete_post_meta($order_id,'_pb_queue_reship');
    } else {
      if(isset($_POST['reason'])) $reason=sanitize_text_field($_POST['reason']);
      if(empty($reason)) $status="<div class='err'>Note Required.</div>";
      if(stripos('shop_order,import_order',get_post_type($order_id))===false) $status="<div class='err'>Invalid Order ID.</div>";
      if(empty($status)) {
        $current_time=current_time('mysql');
        $comment=array('comment_post_ID'=>$order_id,'comment_author'=>espb_usermeta($user_id,'display_name'),'comment_author_email'=>espb_usermeta($user_id,'user_email'),'comment_author_IP'=>sanitize_text_field($_SERVER['REMOTE_ADDR']),'comment_content'=>"RESHIP: $reason",'user_id'=>$user_id,'comment_agent'=>'WooCommerce','comment_type'=>'order_note');
        @update_post_meta($order_id,'_pb_queue_reship',"$current_time");
        wp_insert_comment($comment);
        //@add_post_meta($order_id,'_pb_reship',array($current_time,$reason,$user_id));
        $report_type='reship';
      }
    }
  }

  // Autofill Reship
  if(empty($order_id) && isset($_GET['order_id'])) {
    $order_id=intval($_GET['order_id']); 
    $af_reship_script="<script type='text/javascript'>setTimeout(function(){show_reship();},100);</script>";
  }
                
  if(empty(get_option('pb_developer_id'))||empty(get_option('pb_client_id'))) {
    if(current_user_can('manage_options')) header("location: ".admin_url('options-general.php?page=pb-admin'));
    else {echo 'Plugin not configured.'; exit();}
  }

  // Collect Variables
  $site_name=get_bloginfo('name');
  $white_label=get_option('pb_white_label');
  $site_url=site_url();
  $logo_url=espb_site_logo();


  // User Option Defaults
  $color_scheme=get_option("pb_default_color_scheme_$user_id");

  // Run Report
  $url=admin_url();
  $product_id='product_id';
  $report=$override=$delete_link='';
  if($report_type=='reship') {
    $override='&override=1';
    if(current_user_can('edit_shop_orders')) $delete_link=" <a class='dashicons dashicons-dismiss pb_remove' title='Remove from queue' onclick=\"if(confirm('Are you sure you want to remove this order from the queue?')) {pb_getE('action').value=1;pb_getE('order_id').value=[order];pb_remove(this.parentElement.parentElement);pb_getE('pb_reship').submit();pb_load_animation();}\"></a>";
  }
  elseif($report_type=='variation') $product_id='variation_id';
  if(function_exists('amz_order_product')) $import_order='wp_import_order_product_lookup'; else $import_order='wp_wc_order_product_lookup';
  $ship_link="<a class='button' target='_blank' href='!#' style='color:var(--bgd_hl_clr);font-weight:bold;text-decoration:none' onclick=\"this.style.opacity='.3';window.open('{$url}admin.php?page=pb-ship&order_id=[order]$override','Ship','width=780,height=1005,resizable=yes,scrollbars=yes');return false;\">Ship</a>";
  $report=$del_est=$addt_col='';

  if(empty($af_reship_script)) {
  
    if(espb_is_hpos()>0) $po=espb_r("
      SELECT ID, post_type
      ,CASE 
        WHEN DATE_FORMAT(delivery_est,'%y%m%d')>DATE_FORMAT(NOW()+INTERVAL 6 DAY,'%y%m%d') THEN .5
        WHEN DATE_FORMAT(delivery_est,'%y%m%d')>DATE_FORMAT(NOW()+INTERVAL 4 DAY,'%y%m%d') THEN 1
        WHEN DATE_FORMAT(post_date,'%y%m%d')<DATE_FORMAT(NOW(),'%y%m%d') THEN DATEDIFF(now(),post_date)
        WHEN DATE_FORMAT(post_date,'%y%m%d %H')<DATE_FORMAT(NOW(),'%y%m%d 12') THEN .5
        ELSE 0 
       END cutoff
      ,DATE_FORMAT(post_date,'%b %e, %Y<br>%r')order_date
      ,GROUP_CONCAT(DISTINCT item SEPARATOR '<br>')items
      ,method
      ,delivery_est
      ,reship
      ,(SELECT comment_content FROM wp_comments WHERE i.reship>0 AND comment_post_id=i.ID AND comment_type='order_note' AND comment_content LIKE 'RESHIP:%' AND user_id>0 ORDER BY comment_ID DESC LIMIT 1)reship_reason
      FROM (
          SELECT o.ID, o2.post_type
          ,o.post_date
          ,REPLACE(CONCAT((SELECT DISTINCT post_title FROM wp_posts WHERE ID=COALESCE(NULLIF(op.$product_id,0),ip.$product_id,op.product_id)),'(',SUM(IFNULL(op.product_qty,ip.product_qty)),')'),'(1)','')item
          ,o2.reship
          ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='shipping' LIMIT 1),(SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_id=o.ID AND order_item_type='shipping' LIMIT 1))method
          ,(SELECT NULLIF(m.meta_value,0) FROM wp_woocommerce_order_items oi JOIN wp_woocommerce_order_itemmeta m ON m.order_item_id=oi.order_item_id AND m.meta_key='cost' WHERE oi.order_id=o.ID AND oi.order_item_type='shipping' LIMIT 1)cost
          ,(SELECT meta_value FROM wp_postmeta WHERE meta_key='delivery_est' AND post_id=o.ID LIMIT 1)delivery_est
          FROM wp_posts o 
          JOIN (
            SELECT o.ID, 'shop_order' post_type, 0 reship
            FROM wp_wc_orders o
            LEFT JOIN wp_postmeta s ON s.post_id=o.ID AND s.meta_key='_wc_shipment_tracking_items' AND LENGTH(s.meta_value)>10
            WHERE o.status='wc-processing' AND s.meta_value IS NULL

            UNION
            SELECT o.ID, 'shop_order' post_type, rs.meta_value reship
            FROM wp_wc_orders o
            JOIN wp_postmeta rs ON rs.post_id=o.ID AND rs.meta_key='_pb_queue_reship'

            UNION
            SELECT o.ID, post_type, 0 reship
            FROM wp_posts o
            LEFT JOIN wp_postmeta s ON s.post_id=o.ID AND s.meta_key='_wc_shipment_tracking_items' AND LENGTH(s.meta_value)>10
            WHERE o.post_type='import_order'
            AND o.post_status='wc-processing' AND s.meta_value IS NULL

            UNION
            SELECT o.ID, post_type, rs.meta_value reship
            FROM wp_posts o
            JOIN wp_postmeta rs ON rs.post_id=o.ID AND rs.meta_key='_pb_queue_reship'
            WHERE o.post_type='import_order'
          )o2 ON o2.ID=o.ID
          LEFT JOIN wp_wc_order_product_lookup op ON op.order_id=o.ID
          LEFT JOIN $import_order ip ON ip.order_id=o.ID
          GROUP BY o.ID,op.$product_id,ip.$product_id
      )i
      GROUP BY ID
      ORDER BY CASE WHEN '$product_id'='variation_id' THEN item ELSE '' END,
       cutoff DESC
      ,POSITION('Corporate'IN method)DESC
      ,POSITION('Wholesale'IN method)DESC
      ,POSITION('Ins'IN method) DESC
      ,reship
      ,POSITION('Priority'IN method) DESC
      ,DATE_FORMAT(order_date,'%y%m%d%H%i%s')
    ");
    
    else $po=espb_r("
      SELECT ID, post_type
      ,CASE 
        WHEN DATE_FORMAT(delivery_est,'%y%m%d')>DATE_FORMAT(NOW()+INTERVAL 6 DAY,'%y%m%d') THEN .5
        WHEN DATE_FORMAT(delivery_est,'%y%m%d')>DATE_FORMAT(NOW()+INTERVAL 4 DAY,'%y%m%d') THEN 1
        WHEN DATE_FORMAT(post_date,'%y%m%d')<DATE_FORMAT(NOW(),'%y%m%d') THEN DATEDIFF(now(),post_date)
        WHEN DATE_FORMAT(post_date,'%y%m%d %H')<DATE_FORMAT(NOW(),'%y%m%d 12') THEN .5
        ELSE 0 
       END cutoff
      ,DATE_FORMAT(post_date,'%b %e, %Y<br>%r')order_date
      ,GROUP_CONCAT(DISTINCT item SEPARATOR '<br>')items
      ,method
      ,delivery_est
      ,reship
      ,(SELECT comment_content FROM wp_comments WHERE i.reship>0 AND comment_post_id=i.ID AND comment_type='order_note' AND comment_content LIKE 'RESHIP:%' AND user_id>0 ORDER BY comment_ID DESC LIMIT 1)reship_reason
      FROM (
          SELECT o.ID, post_type
          ,o.post_date
          ,REPLACE(CONCAT((SELECT DISTINCT post_title FROM wp_posts WHERE ID=COALESCE(NULLIF(op.$product_id,0),ip.$product_id,op.product_id)),'(',SUM(IFNULL(op.product_qty,ip.product_qty)),')'),'(1)','')item
          ,o2.reship
          ,IFNULL((SELECT meta_value FROM wp_postmeta WHERE post_id=o.ID AND meta_key='shipping' LIMIT 1),(SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_id=o.ID AND order_item_type='shipping' LIMIT 1))method
          ,(SELECT NULLIF(m.meta_value,0) FROM wp_woocommerce_order_items oi JOIN wp_woocommerce_order_itemmeta m ON m.order_item_id=oi.order_item_id AND m.meta_key='cost' WHERE oi.order_id=o.ID AND oi.order_item_type='shipping' LIMIT 1)cost
          ,(SELECT meta_value FROM wp_postmeta WHERE meta_key='delivery_est' AND post_id=o.ID LIMIT 1)delivery_est
          FROM wp_posts o 
          JOIN (
            SELECT o.ID, 0 reship
            FROM wp_posts o
            LEFT JOIN wp_postmeta s ON s.post_id=o.ID AND s.meta_key='_wc_shipment_tracking_items' AND LENGTH(s.meta_value)>10
            WHERE o.post_type in ('shop_order','import_order')
            AND o.post_status='wc-processing' AND s.meta_value IS NULL

            UNION
            SELECT o.ID, rs.meta_value reship
            FROM wp_posts o
            JOIN wp_postmeta rs ON rs.post_id=o.ID AND rs.meta_key='_pb_queue_reship'
            WHERE o.post_type in ('shop_order','import_order')
          )o2 ON o2.ID=o.ID
          LEFT JOIN wp_wc_order_product_lookup op ON op.order_id=o.ID
          LEFT JOIN $import_order ip ON ip.order_id=o.ID
          GROUP BY o.ID,op.$product_id,ip.$product_id
      )i
      GROUP BY ID
      ORDER BY CASE WHEN '$product_id'='variation_id' THEN item ELSE '' END,
       cutoff DESC
      ,POSITION('Corporate'IN method)DESC
      ,POSITION('Wholesale'IN method)DESC
      ,POSITION('Ins'IN method) DESC
      ,reship
      ,POSITION('Priority'IN method) DESC
      ,DATE_FORMAT(post_date,'%y%m%d%H%i%s')
    ");
  }

  $unshipped=$reships=0;
  if(isset($po)) if($po) if($po[0]->ID>0) {

    foreach($po as $o) {
      $highlight='';
      if($o->reship<1) $unshipped++; else $reships++;
      if($report_type!='reship' && $o->reship>0) continue;
      if($report_type=='reship') {
        if($o->reship<1) continue;
        $order_date=$o->reship;
      } else {
        if(!isset($_GET['show_all']) && $unshipped>30) continue;
        $order_date=$o->order_date;
      }
      if($order_id==$o->ID) $highlight='background:lightgreen;';
      elseif($o->reship<1 && $o->cutoff>=0) {if($o->cutoff>=2) $highlight.="box-shadow:10px 0px 5px -9px red"; elseif($o->cutoff>=1) $highlight.="box-shadow:10px 0px 4px -9px orange"; else $highlight.="box-shadow:10px 0px 3px -9px #06ca0e";}
      $report.="<tr style='line-height:1.5em'>";
      $report.="<td><a class='button' onclick=\"open_wc_order('{$o->ID}','{$o->post_type}')\">{$o->ID}</a></td>";
      $report.="<td nowrap>$order_date</td>";
      $report.="<td style='white-space:nowrap'>{$o->items}</td>";
      $report.="<td>{$o->method}</td>";
      $report.="<td nowrap style='$highlight'>".str_replace("[order]",$o->ID,$ship_link)."</td>";

      if($report_type=='reship') {
        $addt_col='<th>Reship</th><th>Remove</th>';
        $report.='<td>'.str_replace("RESHIP: ",'',$o->reship_reason).'</td>';
        $report.='<td>'.str_replace("[order]",$o->ID,$delete_link).'</td>';
      }
      elseif(!empty($o->delivery_est)) {
        $addt_col='<th>Est Delivery</th>';
        $report.="<td nowrap>{$o->delivery_est}</td>";
      }
      $report.="</tr>";
    }
    if($report_type=='reship') $date_col='Queue Date'; else $date_col='Order Date';
    $report="<table class='pb_rpt'><tr><th>Order</th><th>$date_col</th><th><a href='?page=pb-queue&report_type=' style='text-decoration:none'>Product</a> | <a href='?page=pb-queue&report_type=variation' style='text-decoration:none'>Variation</a></th><th>Method</th><th>Action</th>$addt_col</tr>$report</table>";
  }
  
  if(empty($report)) $status.="<div class='err'>No pending shipments.</div>";?>

    <!doctype html>
    <html lang="en-US">
      <head>
        <title>Ship Queue</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=2.0">
        <meta name='robots' content='noindex,nofollow'>
        <meta http-equiv='refresh' content='120;?page=pb-queue'>
        <script type='text/javascript'>
          function pb_qicon(msg=0) {
            pb_url='<?php echo $url=plugins_url();?>';
            if(<?php if(empty($report)) echo 0; else echo 1;?>>0) pb_url+='/pb-shipping/assets/icon-msg.png'; else pb_url+='/pb-shipping/assets/icon-256x256.png';
            links=document.getElementsByTagName("link");
            if(links) {
              for (var i=0; i<links.length; i++) if(links[i].getAttribute('rel')=='icon') links[i].href=pb_url;
            }
          }
          pb_qicon();
        </script>
        <?php echo espb_inc_css($color_scheme);?>
      </head>

      <body>
      <div class='noprint' style='margin:0 1em 1em -1.5em'>
        <img class='logo' src='<?php if(!empty($white_label)) {if(!empty($logo_url)) echo $logo_url;} else echo plugins_url('pb-shipping/assets/icon-trns.png'); ?>'>
        <div style='display:inline-block;letter-spacing:.3em;font-variant-caps:all-petite-caps'><?php if(!empty($white_label)) {if(empty($logo_url)) echo "$site_name Shipping";} else echo 'Enterprise Shipping'; ?></div>
      </div>
      <div id='uploading' class='progress'>Loading</div>
      <div id='neon_cat' class='progress'><img src='<?php echo plugins_url('pb-shipping/assets/neon_cat.gif');?>'></div>

      <div id='pb_report'>
        <div class='module label noprint'>
          <div class='title'>Options</div>

          <form name='pb_reship' id='pb_reship' method='post' accept-charset='UTF-8 ISO-8859-1' style='float:right;text-align:right' onsubmit="pb_load_animation();">
            <div class='form_control'>
              <?php if(current_user_can('edit_shop_orders')) {?>
                <div id='reship' style='display:none'>
                  <input name='order_id' id='order_id' type='text' pattern='[0-9 ]{2,11}' placeholder='Order ID' value='<?php echo $order_id;?>'>
                  <input name='reason' id='reason' type='text' placeholder='Note Item & Qty'><br>
                  <input name='action' id='action' type='hidden' value=0>
                  <?php echo wp_nonce_field('filter_queue','pb_filter_queue');?>
                  <input type='submit' value='Queue Order for Reship' id='submit_reship'>
                </div>
              <?php } ?>
            </div>
          </form>

          <form name='pb_filter' method='get' action='admin.php' style='display:inline-block;margin-right:.5em' onsubmit="pb_load_animation();">
            <div class='form_control'>
              <input type='hidden' name='page' value='pb-queue'>
              <select name='report_type' id='report_type' class='multiple' multiple='multiple' onchange='pb_load_animation();this.form.submit()'>
                <option value='unshipped' <?php if($report_type=='unshipped') echo "style='background:#0078ad;color:#fff'"; ?>>Unshipped Orders <?php if(!empty($unshipped)) echo "($unshipped)";?>
                <option value='reship' <?php if($report_type=='reship') echo "style='background:#0078ad;color:#fff'"; ?>>Orders Pending Reship <?php if(!empty($reships)) echo " ($reships)";?>
              </select>
            </div>
          </form>

          <?php if(current_user_can('view_woocommerce_reports')) {?>
            <div id='pb_option' style='display:inline-block;float:unset;vertical-align:top'>
              <input type='button' style='float:unset;margin-right:.2em' value='Options' onclick="var pb_o=pb_getE('pb_options'); if(pb_o.style.display=='inline-block') pb_o.style.display='none'; else {pb_o.style.display='inline-block';}">
              <div id='pb_options' style='display:none'>
                <input type='button' value='Reports' style='margin-right:.5em;float:none' onclick="open_pb_report();">
                <?php if(current_user_can('manage_options')) {?><input type='button' style='margin-right:.5em;float:none' onclick="window.open('<?php echo admin_url('options-general.php?page=pb-admin');?>')" value='Admin'>
                <?php if(function_exists('amz_insert_orders')) {?>
                  <input type='button' style='margin-right:.5em;float:none' onclick="window.open('<?php echo admin_url('options-general.php?page=amz_admin');?>')" value='Amazon API'>
                  <input type='button' style='margin-right:.5em;float:none' onclick="window.open('<?php echo admin_url('options-general.php?page=amz_admin&tab=orders');?>')" value='Imported Orders'><?php 
                  }
                } ?>
              </div>
            </div>
          <?php } ?>

          <input type='button' value='Add to Queue / Reship' style='position:absolute;margin:0 .5em' id='reship_btn' onclick="show_reship();">
          <script type='text/javascript'>
            function show_reship() {
              if(pb_getE('pb_option')) pb_getE('pb_option').style.display='none';
              pb_getE('reship_btn').style.display='none';
              pb_getE('reship').style.display='block';
              pb_getE('order_id').focus();
            }
            function reship_reminder() {
              var dt=new Date();
              var mins=dt.getMinutes();
              if(mins>=58) alert('<?php echo $reships;?> Reships Pending');
            }
            <?php if($report_type!=='reship' && $reships>0) echo "reship_reminder();";?>
          </script>
        </div>

        <?php if(empty($af_reship_script)) { ?>
        <div class='module report' style='margin:1em 0'>
          <div class='title'>Queue</div>
          <div style='margin-left:4px'>
            <?php echo $status;?>
            <?php if(isset($report)) echo $report; ?>
          </div>
        </div>
        <?php } ?>
      </div><?php
      echo espb_inc_js('');
      echo $af_reship_script; ?>
    </body>
  </html><?php
}