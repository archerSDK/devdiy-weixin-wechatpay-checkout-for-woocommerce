<?php
/*
 * Plugin Name: Devdiy WeiXin Wechatpay Checkout Payments for WooCommerce
 * Plugin URI: https://www.devdiy.com
 * Description:给Woocommerce系统添加微信Native扫码及H5支付功能,支持扫码支付和退款功能。若需要企业版本，请访问<a href="http://www.devdiy.com" target="_blank">http://www.devdiy.com</a>
 * Version: 2.0.1
 * Author: DEVDIY开发者
 * Author URI:https://www.devdiy.com
 * Text Domain: Devdiy WeiXin Wechatpay Checkout Payments for WooCommerce
 * 
 */

if (! defined ( 'ABSPATH' )) exit ();

if (!defined ('DEVDIY_WC_WEIXINPAY')) { 
    define ('DEVDIY_WC_WEIXINPAY', 'DEVDIY_WC_WEIXINPAY' );
} else {
    return;
}

define('DEVDIY_WC_WECHAT_VERSION',	'2.0.1');
define('DEVDIY_WC_WECHAT_DIR',	rtrim(plugin_dir_path(__FILE__),'/'));
define('DEVDIY_WC_WECHAT_URL',	rtrim(plugin_dir_url(__FILE__),'/'));
define('DEVDIY_WC_WECHAT_ID',	'devdiy-wechatpay-checkout-for-woocommerce');

load_plugin_textdomain( 'wechatpay', false, dirname(plugin_basename( __FILE__)) . '/lang/');

function devdiy_wechat_wc_payment_gateway_plugin_edit_link( $links ){
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section='.DEVDIY_WC_WECHAT_ID) . '">'.__( 'Settings', 'wechatpay' ).'</a>'
        ),
        $links
    );
}

/*
 * PHP类注册为WooCommerce支付网关
 */
add_filter('woocommerce_payment_gateways', 'devdiy_wechatpay_add_gateway_class');
function devdiy_wechatpay_add_gateway_class( $gateways )
{
	$gateways[] = 'WC_Devdiy_Wechatpay_Gateway'; // 在这里添加类名称

	return $gateways;
}

add_action('plugins_loaded', 'devdiy_wechatpay_init_gateway_class');
function devdiy_wechatpay_init_gateway_class() {	

class WC_Devdiy_Wechatpay_Gateway extends WC_Payment_Gateway {	
    private $config;
    
	public function __construct() {
		//支持退款
		array_push($this->supports,'refunds');

		$this->id   = DEVDIY_WC_WECHAT_ID;
		$this->icon = DEVDIY_WC_WECHAT_URL. '/assets/images/logo.png';
		$this->has_fields = false;
		
		$this->method_title 	  = '微信支付'; 
	    $this->method_description = '企业版本支持微信原生支付（H5公众号）、微信登录、微信红包推广/促销、微信收货地址同步、微信退款等功能。若需要企业版本，请访问<a href="https://www.devdiy.com" target="_blank">https://www.devdiy.com</a> ';
	   
		$this->init_form_fields();
		$this->init_settings();
		
		$this->title 		= $this->get_option('title');
		$this->description 	= $this->get_option('description');
		
		$src = DEVDIY_WC_WECHAT_DIR.'/src';
		
		if(!class_exists('WechatPaymentApi')){
		    include_once ($src . '/WxPay.Data.php');
    		include_once ($src . '/WxPay.Api.php');
    		include_once ($src . '/WxPay.Exception.php');
    		include_once ($src . '/WxPay.Notify.php');
    		include_once ($src . '/WxPay.Config.php');
    		
    		if(!class_exists('WxLogHandler')){
    		  include_once ($src . '/WxPay.Log.php');
    		}
		}
		
		$this->config =new WechatPaymentConfig ($this->get_option('wechatpay_appID'),  $this->get_option('wechatpay_mchId'), $this->get_option('wechatpay_key'));
	}

	function init_form_fields() {
	    $this->form_fields = array (
	        'enabled' => array (
	            'title' => __ ( 'Enable/Disable', 'wechatpay' ),
	            'type' => 'checkbox',
	            'label' => __ ( 'Enable WeChatPay Payment', 'wechatpay' ),
	            'default' => 'no'
	        ),
	        'title' => array (
	            'title' => __ ( 'Title', 'wechatpay' ),
	            'type' => 'text',
	            'description' => __ ( 'Payment gateway name, which is displayed when the user checksout.', 'wechatpay' ),
	            'default' => __ ( 'WeChatPay', 'wechatpay' ),
	            'css' => 'width:400px'
	        ),
	        'description' => array (
	            'title' => __ ( 'Description', 'wechatpay' ),
	            'type' => 'textarea',
	            'description' => __ ( 'This controls the description which the user sees during checkout.', 'wechatpay' ),
	            'default' => __ ( "Pay via WeChatPay, if you don't have an WeChatPay account, you can also pay with your debit card or credit card", 'wechatpay' ),
	            //'desc_tip' => true ,
	            'css' => 'width:400px'
	        ),
	        'wechatpay_appID' => array (
	            'title' => __ ( 'Application ID', 'wechatpay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter the Application ID,If you don\'t have one, <a href="https://pay.weixin.qq.com" target="_blank">click here</a> to get.', 'wechatpay' ),
	            'css' => 'width:400px'
	        ),
	        'wechatpay_mchId' => array (
	            'title' => __ ( 'Merchant ID', 'wechatpay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter the Merchant ID,If you don\'t have one, <a href="https://pay.weixin.qq.com" target="_blank">click here</a> to get.', 'wechatpay' ),
	            'css' => 'width:400px'
	        ),
	        'wechatpay_key' => array (
	            'title' => __ ( 'WeChatPay Key', 'wechatpay' ),
	            'type' => 'text',
	            'description' => __ ( 'Please enter your WeChatPay Key; this is needed in order to take payment.', 'wechatpay' ),
	            'css' => 'width:400px',
	            //'desc_tip' => true
	        ),
	        'exchange_rate'=> array (
	            'title' => __ ( 'Exchange Rate', 'wechatpay' ),
	            'type' => 'text',
	            'default'=>1,
	            'description' =>  __ ( "Please set current currency against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19", 'wechatpay' ),
	            'css' => 'width:80px;',
	            'desc_tip' => true
	        )
	    );
	
	}
	
	public function process_payment($order_id) {
	    $order = new WC_Order ( $order_id );
	    return array (
	        'result' => 'success',
	        'redirect' => $order->get_checkout_payment_url ( true )
	    );
	}
	
	public  function woocommerce_wechatpay_add_gateway( $methods ) {
	    $methods[] = $this;
	    return $methods;
	}
	
	/**
	 * 
	 * @param WC_Order $order
	 * @param number $limit
	 * @param string $trimmarker
	 */
	public  function get_order_title($order,$limit=32,$trimmarker='...'){
	    $id = method_exists($order, 'get_id')?$order->get_id():$order->id;
		$title="#{$id}|".get_option('blogname');
		
		$order_items =$order->get_items();
		if($order_items&&count($order_items)>0){
		    $title="#{$id}|";
		    $index=0;
		    foreach ($order_items as $item_id =>$item){
		        $title.= $item['name'];
		        if($index++>0){
		            $title.='...';
		            break;
		        }
		    }    
		}
		
		return apply_filters('devdiy_wechat_wc_get_order_title',  $title);
		//return apply_filters('devdiy_wechat_wc_get_order_title', mb_strimwidth ( $title, 0,32, '...','utf-8'));
	}
	
	public function get_order_status() {
		$order_id 	= isset($_POST ['orderId'])?$_POST ['orderId']:'';
		$order 		= new WC_Order ( $order_id );
		$isPaid 	= ! $order->needs_payment ();
	
		echo json_encode ( array (
		    'status' =>$isPaid? 'paid':'unpaid',
		    'url' => $this->get_return_url ( $order )
		));
		
		exit;
	}
	
	function wp_enqueue_scripts() {
		$orderId 	= get_query_var ( 'order-pay' );
		$order 		= new WC_Order ( $orderId );
		$payment_method = method_exists($order, 'get_payment_method')?$order->get_payment_method():$order->payment_method;
		if ($this->id == $payment_method) {
			if (is_checkout_pay_page () && ! isset ( $_GET ['pay_for_order'] )) {
			    
			    wp_enqueue_script ( 'DEVDIY_WECHAT_JS_QRCODE', DEVDIY_WC_WECHAT_URL. '/assets/js/qrcode.js', array (), DEVDIY_WC_WECHAT_VERSION );
				wp_enqueue_script ( 'DEVDIY_WECHAT_JS_CHECKOUT', DEVDIY_WC_WECHAT_URL. '/assets/js/checkout.js', array ('jquery','DEVDIY_WECHAT_JS_QRCODE' ), DEVDIY_WC_WECHAT_VERSION );
				
			}
		}
	}
	
	public function check_wechatpay_response() {
	    if(defined('WP_USE_THEMES')&&!WP_USE_THEMES){
	        return;
	    }
	    
		$xml = isset($GLOBALS ['HTTP_RAW_POST_DATA'])?$GLOBALS ['HTTP_RAW_POST_DATA']:'';	
		if(empty($xml)){
		    $xml = file_get_contents("php://input");
		}
		
		if(empty($xml)){
		    return ;
		}
		
		$xml = trim($xml);
		if(substr($xml, 0,4) !='<xml'){
		    return;
		}
		
		//排除非微信回调
		if(strpos($xml, 'transaction_id')===false
		    ||strpos($xml, 'appid')===false
		    ||strpos($xml, 'mch_id')===false){
		        return;
		}
		// 如果返回成功则验证签名
		try {
		    $result = WechatPaymentResults::Init ( $xml );
		    if (!$result||! isset($result['transaction_id'])) {
		        return;
		    }
		    
		    $transaction_id=$result ["transaction_id"];
		    $order_id = $result['attach'];
		    
		    $input = new WechatPaymentOrderQuery ();
		    $input->SetTransaction_id ( $transaction_id );
		    $query_result = WechatPaymentApi::orderQuery ( $input, $this->config );
		    if ($query_result['result_code'] == 'FAIL' || $query_result['return_code'] == 'FAIL') {
                throw new Exception(sprintf("return_msg:%s ;err_code_des:%s "), $query_result['return_msg'], $query_result['err_code_des']);
            }
            
            if(!(isset($query_result['trade_state'])&& $query_result['trade_state']=='SUCCESS')){
                throw new Exception("order not paid!");
            }
		  
		    $order = new WC_Order ( $order_id );
		    if($order->needs_payment()){
		          $order->payment_complete ($transaction_id);
		    }
		    
		    $reply = new WechatPaymentNotifyReply ();
		    $reply->SetReturn_code ( "SUCCESS" );
		    $reply->SetReturn_msg ( "OK" );
		    
		    WxpayApi::replyNotify ( $reply->ToXml () );
		    exit;
		} catch ( WechatPaymentException $e ) {
		    return;
		}
	}

	public function process_refund( $order_id, $amount = null, $reason = ''){		
		$order = new WC_Order ($order_id );
		if(!$order){
			return new WP_Error( 'invalid_order','错误的订单' );
		}
	
		$trade_no =$order->get_transaction_id();
		if (empty ( $trade_no )) {
			return new WP_Error( 'invalid_order', '未找到微信支付交易号或订单未支付' );
		}
	
		$total = $order->get_total ();
		//$amount = $amount;
        $preTotal = $total;
        $preAmount = $amount;
        
		$exchange_rate = floatval($this->get_option('exchange_rate'));
		if($exchange_rate<=0){
			$exchange_rate=1;
		}
			
		$total = round ( $total * $exchange_rate, 2 );
		$amount = round ( $amount * $exchange_rate, 2 );
      
        $total = ( int ) ( $total  * 100);
		$amount = ( int ) ($amount * 100);
        
		if($amount<=0||$amount>$total){
			return new WP_Error( 'invalid_order',__('Invalid refused amount!' ,DEVDIY_WECHAT) );
		}
	
		$transaction_id = $trade_no;
		$total_fee = $total;
		$refund_fee = $amount;
	
		$input = new WechatPaymentRefund ();
		$input->SetTransaction_id ( $transaction_id );
		$input->SetTotal_fee ( $total_fee );
		$input->SetRefund_fee ( $refund_fee );
	
		$input->SetOut_refund_no ( $order_id.time());
		$input->SetOp_user_id ( $this->config->getMCHID());
	
		try {
			$result = WechatPaymentApi::refund ( $input,60 ,$this->config);
			if ($result ['result_code'] == 'FAIL' || $result ['return_code'] == 'FAIL') {
				Log::DEBUG ( " DEVDIYWechatPaymentApi::orderQuery:" . json_encode ( $result ) );
				throw new Exception ("return_msg:". $result ['return_msg'].';err_code_des:'. $result ['err_code_des'] );
			}
	
		} catch ( Exception $e ) {
			return new WP_Error( 'invalid_order',$e->getMessage ());
		}
	
		return true;
	}

	/**
	 * Archer[[:space:]]Framework
	 * [Archer!] (C)2006-2022 devdiy Inc. (http://www.devdiy.com)
	 *
	 * 判断是否是移动设备
	 * @gary
	 * @version $Id
	 **/
	function mobile()
	{
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if(isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
			return true;
		}
		// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if(isset ($_SERVER['HTTP_VIA'])){
			// 找不到为flase,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		//脑残法，判断手机发送的客户端标志,兼容性有待提高
		if(isset ($_SERVER['HTTP_USER_AGENT'])){
			$clientkeywords = array (
					'nokia',
					'sony',
					'ericsson',
					'mot',
					'samsung',
					'htc',
					'sgh',
					'lg',
					'sharp',
					'sie-',
					'philips',
					'panasonic',
					'alcatel',
					'lenovo',
					'iphone',
					'ipod',
					'blackberry',
					'meizu',
					'android',
					'netfront',
					'symbian',
					'ucweb',
					'windowsce',
					'palm',
					'operamini',
					'operamobi',
					'openwave',
					'nexusone',
					'cldc',
					'midp',
					'wap',
					'mobile'
		);
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
				return true;
			}
		}
		// 协议法，因为有可能不准确，放到最后判断
		if (isset ($_SERVER['HTTP_ACCEPT'])){
			// 如果只支持wml并且不支持html那一定是移动设备
			// 如果支持wml和html但是wml在html之前则是移动设备
			if((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * 
	 * @param WC_Order $order
	 */
	function receipt_page($order_id) {
	    $order = new WC_Order($order_id);
	    if(!$order||$order->is_paid()){
	       return;
		}

		$input = new WechatPaymentUnifiedOrder ();
		$input->SetBody ($this->get_order_title($order) );
	
		$input->SetAttach ( $order_id );
		$input->SetOut_trade_no ( md5(date ( "YmdHis" ).$order_id ));    
		$total = $order->get_total ();
        
		$exchange_rate = floatval($this->get_option('exchange_rate'));
		if($exchange_rate<=0){
		    $exchange_rate=1;
		}
		
		$total = round ($total * $exchange_rate, 2 );
        $totalFee = ( int ) ($total * 100);
        
		$input->SetTotal_fee ( $totalFee );
		
		$date = new DateTime ();
		$date->setTimezone ( new DateTimeZone ( 'Asia/Shanghai' ) );
		$startTime = $date->format ( 'YmdHis' );
		$input->SetTime_start ( $startTime );
		$input->SetNotify_url (get_option('siteurl') );
	
	    if(mobile()){
	        //H5支付 在微信客户端外的移动端网页使用微信支付
	        $input->SetTrade_type("MWEB");
	    }else{
	        //Native支付，商户系统按微信支付协议生成支付二维码，用户扫码支付
	        $input->SetTrade_type("NATIVE");
    	}
				
		$input->SetProduct_id ($order_id );

		try {
		    $result = WechatPaymentApi::unifiedOrder ( $input, 60, $this->config );
		} catch (Exception $e) {
		    echo $e->getMessage();
		    return;
		}

		$error_msg=null;
		if((isset($result['result_code'])&& $result['result_code']=='FAIL')
		    ||
		    (isset($result['return_code'])&&$result['return_code']=='FAIL')){
		    
		    $error_msg =  "return_msg:".$result['return_msg']." ;err_code_des: ".$result['err_code_des'];
	
		}

		//h5支付url
		if(!empty($result['return_code'])  && $result['return_code'] == 'SUCCESS'){
			if($result['trade_type'] == 'MWEB'){
				$result['code_url'] = $result['mweb_url'];
			}
		}
		
		$url =isset($result['code_url'])? $result ["code_url"]:'';
		
		if($this->mobile()){
		    wp_redirect($url);
		    exit;
		}

		echo  '<input type="hidden" id="devdiy-wechat-payment-pay-url" value="'.$url.'"/>';
		
		?>
		<style type="text/css">
		.pay-weixin-design{ display: block;background: #fff;/*padding:100px;*/overflow: hidden;}.page-wrap {padding: 50px 0;min-height: auto !important;  }.pay-weixin-design #WxQRCode{width:196px;height:auto}.pay-weixin-design .p-w-center{ display: block;overflow: hidden;margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;}.pay-weixin-design .p-w-center h3{    font-family: Arial,微软雅黑;margin: 0 auto 10px;display: block;overflow: hidden;}.pay-weixin-design .p-w-center h3 font{ display: block;font-size: 14px;font-weight: bold;    float: left;margin: 15px 10px 0 0;}.pay-weixin-design .p-w-center h3 strong{position: relative;text-align: center;line-height: 40px;border: 2px solid #3879d1;display: block;font-weight: normal;width: 130px;height: 44px; float: left;}.pay-weixin-design .p-w-center h3 strong #img1{margin-top: 10px;display: inline-block;width: 22px;vertical-align: top;}.pay-weixin-design .p-w-center h3 strong span{    display: inline-block;font-size: 14px;vertical-align: top;}.pay-weixin-design .p-w-center h3 strong #img2{    position: absolute;right: 0;bottom: 0;}.pay-weixin-design .p-w-center h4{font-family: Arial,微软雅黑;      margin: 0; font-size: 14px;color: #666;}.pay-weixin-design .p-w-left{ display: block;overflow: hidden;float: left;}.pay-weixin-design .p-w-left p{ display: block;width:196px;background:#00c800;color: #fff;text-align: center;line-height:2.4em; font-size: 12px; }.pay-weixin-design .p-w-left img{ margin-bottom: 10px;}.pay-weixin-design .p-w-right{ margin-left: 50px; display: block;float: left;}
        </style>
        		
        <div class="pay-weixin-design">
        
             <div class="p-w-center">
                <h3>
        		   <font>支付方式已选择微信支付</font>
        		   <strong>
        		      <img id="img1" src="<?php print DEVDIY_WC_WECHAT_URL?>/assets/images/weixin.png">
        			  <span>微信支付</span>
        			  <img id="img2" src="<?php print DEVDIY_WC_WECHAT_URL?>/assets/images/weixin_sprites.png">
        		   </strong>
        		</h3>
        	    <h4>通过微信首页右上角扫一扫，或者在“发现-扫一扫”扫描二维码支付。本页面将在支付完成后自动刷新。</h4>
        	    <span style="color:red;"><?php print $error_msg?></span>
        	 </div>
        		
             <div class="p-w-left">		  
        		<div  id="devdiy-wechat-payment-pay-img" style="width:200px;height:200px;padding:10px;" data-oid="<?php echo $order_id;?>"></div>
        		<p>使用微信扫描二维码进行支付</p>
        		
             </div>
        
        	 <div class="p-w-right">        
        	    <img src="<?php print DEVDIY_WC_WECHAT_URL?>/assets/images/weixin_tip.jpg">
        	 </div>
        
        </div>
		
		<?php 
	}
  }
}

add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'devdiy_wechat_wc_payment_gateway_plugin_edit_link');
add_action('init', 'devdiy_wechat_wc_payment_gateway_init');
if(!function_exists('devdiy_wechat_wc_payment_gateway_init')){
    function devdiy_wechat_wc_payment_gateway_init() {
        //if( !class_exists('WC_Payment_Gateway') )  return;
		
		$wechatapi = new WC_Devdiy_Wechatpay_Gateway();
        $wechatapi->check_wechatpay_response();

        add_filter('woocommerce_payment_gateways',array($wechatapi, 'woocommerce_wechatpay_add_gateway'), 10,1);
        add_action('wp_ajax_DEVDIY_WECHAT_PAYMENT_GET_ORDER', array($wechatapi, "get_order_status"));
        add_action('wp_ajax_nopriv_DEVDIY_WECHAT_PAYMENT_GET_ORDER', array($wechatapi, "get_order_status"));
        add_action('woocommerce_receipt_'.$wechatapi->id, array($wechatapi, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_'.$wechatapi->id, array ($wechatapi,'process_admin_options')); // WC >= 2.0
        add_action('woocommerce_update_options_payment_gateways', array ($wechatapi,'process_admin_options'));
        add_action('wp_enqueue_scripts', array ($wechatapi,'wp_enqueue_scripts')); 
    }
}
?>
