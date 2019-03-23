<?php
/**
 * 个人中心页面
 *
 * @license  jiqingchuan  2017年9月23日
 */

class b2c_ctl_wap_member extends wap_frontpage
{

    function __construct( &$app )
    {
        parent::__construct($app);
        $shopname = app::get('wap')->getConf('wap.name');
        if ( isset($shopname) ) {
            $this->title       = app::get('b2c')->_('会员中心') . '_' . $shopname;
            $this->keywords    = app::get('b2c')->_('会员中心_') . '_' . $shopname;
            $this->description = app::get('b2c')->_('会员中心_') . '_' . $shopname;
        }
        $this->header .= '<meta name="robots" content="noindex,noarchive,nofollow" />';
        $this->_response->set_header('Cache-Control', 'no-store');
        $this->verify_member();
        $this->pagesize = 10;
        $this->action   = $this->_request->get_act_name();
        if ( !$this->action ) $this->action = 'index';
        $this->action_view = $this->action . ".html";
        $this->member      = $this->get_current_member();
		
		/*if($this->member['member_id']=='33553' || $this->member['member_id']=='44702' || $this->member['member_id']=='13157')
		{
			$arr=kernel::single('b2c_member_suyuan')->add_seller_member("123456");
		}*/
        /** end **/
    }

    /*
     *本控制器公共分页函数
     * */
    function pagination( $current, $totalPage, $act, $arg = '', $app_id = 'b2c', $ctl = 'wap_member' )
    { //本控制器公共分页函数
        if ( !$arg ) {
            $this->pagedata['pager'] = array(
                'current' => $current,
                'total'   => $totalPage,
                'link'    => $this->gen_url(array( 'app' => $app_id, 'ctl' => $ctl, 'act' => $act, 'args' => array( ( $tmp = time() ) ) )),
                'token'   => $tmp,
            );
        } else {
            $arg                     = array_merge($arg, array( ( $tmp = time() ) ));
            $this->pagedata['pager'] = array(
                'current' => $current,
                'total'   => $totalPage,
                'link'    => $this->gen_url(array( 'app' => $app_id, 'ctl' => $ctl, 'act' => $act, 'args' => $arg )),
                'token'   => $tmp,
            );
        }
    }

    function get_start( $nPage, $count )
    {
        $maxPage = ceil($count / $this->pagesize);
        if ( $nPage > $maxPage ) $nPage = $maxPage;
        $start            = ( $nPage - 1 ) * $this->pagesize;
        $start            = $start < 0 ? 0 : $start;
        $aPage['start']   = $start;
        $aPage['maxPage'] = $maxPage;
        return $aPage;
    }

    /*
     *会员中心首页
     * */
    public function index()
    {

        //面包屑
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $GLOBALS['runtime']['path'] = $this->path;

        #获取会员等级
        $obj_mem_lv = $this->app->model('member_lv');
        $levels     = $obj_mem_lv->getList('name,lv_logo,disabled', array( 'member_lv_id' => $this->member['member_lv'] ));
        if ( $levels[0]['disabled'] == 'false' ) {
            $this->member['levelname'] = $levels[0]['name'];
            $this->member['lv_logo']   = $levels[0]['lv_logo'];
        }
        $oMem_lv                     = $this->app->model('member_lv');
        $this->pagedata['switch_lv'] = $oMem_lv->get_member_lv_switch($this->member['member_lv']);

        //交易提醒
#        $msgAlert = $this->msgAlert();
#        $this->member = array_merge($this->member,$msgAlert);

        //订单列表
#        $oRder = $this->app->model('orders');//--11sql
#        $aData = $oRder->fetchByMember($this->app->member_id,$nPage=1,array(),5); //--141sql优化点
#        $this->get_order_details($aData, 'member_latest_orders');//--177sql 优化点
#        $this->pagedata['orders'] = $aData['data'];

        //收藏列表
        $obj_member                 = $this->app->model('member_goods');
        $aData_fav                  = $obj_member->get_favorite($this->app->member_id, $this->member['member_lv'], $page = 1, $num = 4);//201sql
        $this->pagedata['favorite'] = $aData_fav['data'];
        #默认图片
#        $imageDefault = app::get('image')->getConf('image.set');
#        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];

        //输出
        $this->pagedata['member'] = $this->member;
        $this->set_tmpl('member');
        //未评价商品咨询开关
        $this->pagedata['comment_switch_discuss'] = $this->app->getConf('comment.switch.discuss');
        $this->pagedata['comment_switch_ask']     = $this->app->getConf('comment.switch.ask');

        //预存款判断是否可以充值
        $mdl_payment_cfgs = app::get('ectools')->model('payment_cfgs');
        $payment_info     = $mdl_payment_cfgs->getPaymentInfo('deposit');
        if ( $payment_info['app_staus'] == app::get('ectools')->_('开启') ) {
            $this->pagedata['deposit_status'] = 'true';
        }


        $open_aftersales   = true;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings      = array();
        if ( !isset($obj_return_policy) || !is_object($obj_return_policy) ) {
            $open_aftersales = false;
        }
        if ( !$obj_return_policy->get_conf_data($arr_settings) ) {
            $open_aftersales = false;
        }
        $member_signin_obj                                = $this->app->model('member_signin');
        $this->pagedata['signin_status']                  = $member_signin_obj->exists_signin($this->app->member_id, date('Y-m-d'));
        $this->pagedata['site_checkout_login_point_open'] = $this->app->getConf('site.checkout.login_point.open');
        $this->pagedata['site_login_point_num']           = $this->app->getConf('site.login_point.num');

        $this->pagedata['is_wechat_login'] = kernel::single('b2c_user_object')->is_wechat_login();
        $this->pagedata['open_aftersales'] = $open_aftersales;
        $this->pagedata['bottom_nav']      = 'member';
        //判断是否使用积分 显示积分兑换优惠券按钮
        $site_get_policy_method = $this->app->getConf('site.get_policy.method');
        if ( $site_get_policy_method != '1' ) {
            $this->pagedata['point_usaged'] = true;
        }


        $order_status_delivered = [
            'promotion_type' => 'normal',
            'pay_status'     => 1,
            'ship_status'    => 1,
            'status'         => 'active',
            'member_id'      => $this->app->member_id,
            'displayonsite'  => 'true'
        ];

        //退货
        $obj_return_policy   = kernel::service("aftersales.return_policy");
        $aData = $obj_return_policy->get_return_product_list('*', ['member_id'=>$this->app->member_id], 1);

        $order_status_nopayed   = $order_status_no_delivered = $order_status_delivered;

        $order_status_nopayed['pay_status']       = 0;
        $order_status_nopayed['ship_status']      = 0;
        $order_status_no_delivered['ship_status'] = 0;
        $order_status_delivered['received_status'] = 0;

        $order                                      = $this->app->model('orders');
        $this->pagedata['ship_status_nopayed']      = $order->count($order_status_nopayed);  // 未付款
        $this->pagedata['ship_status_no_delivered'] = $order->count($order_status_no_delivered);  // 未发货
        $this->pagedata['ship_status_delivered']    = $order->count($order_status_delivered); // 待收货
        $this->pagedata['ship_status_afterrec']     = $aData['total'] ? $aData['total'] : 0; //退换货
        $oCoupon                                    = kernel::single('b2c_coupon_mem');
		$filter_search=array();
		$filter_search['disabled'] = 'false';//是否失效
		$filter_search['memc_isvalid'] = 'true';//会员优惠券是否当前可用
		$nPage=null;
        $couponCount                                = $oCoupon->get_list_m($this->app->member_id,$nPage,$filter_search);
        $this->pagedata['coupon_count']             = count($couponCount);
		
		$this->pagedata['store_baidu']             = $this->app->getConf('store.baidu');//百度商桥

        $obj_policy = kernel::service("referrals.member_policy");

        $code = $obj_policy->create_code($this->app->member_id);

        $url=strtolower(kernel::request()->get_schema()).'://'.kernel::request()->get_host().kernel::single('wap_frontpage')->gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'signup')).'?referrals_code='.$code;
        $this->pagedata['code_image_id']=kernel::single('weixin_qrcode')->store($url);

		
		//代理商白名单绑定
		kernel::single('b2c_member_suyuan')->add_seller_member_dls();
		//汇大夫医生绑定
		kernel::single('b2c_member_suyuan')->add_seller_member_hdf();
        $this->page('wap/member/index.html');
    }

    /*
     *会员中心首页交易提醒 (未付款订单,到货通知，未读的评论咨询回复)
     * */
    private function msgAlert()
    {
        //获取待付款订单数
        $oRder                   = $this->app->model('orders');//--11sql
        $un_pay_orders           = $oRder->count(array( 'member_id' => $this->member['member_id'], 'pay_status' => 0, 'status' => 'active' ));
        $member['un_pay_orders'] = $un_pay_orders;
        //获取预售订单数
        $prepare_pay_orders           = $oRder->count(array( 'member_id' => $this->member['member_id'], 'promotion_type' => 'prepare' ));
        $member['prepare_pay_orders'] = $prepare_pay_orders;
        //到货通知
        $member_goods            = $this->app->model('member_goods');
        $member['sto_goods_num'] = $member_goods->get_goods($this->app->member_id);

        //评论咨询回复
        $mem_msg           = $this->app->model('member_comments');
        $object_type       = array( 'discuss', 'ask' );
        $aData             = $mem_msg->getList('*', array( 'to_id' => $this->app->member_id, 'object_type' => $object_type, 'mem_read_status' => 'false', 'display' => 'true' ));
        $un_readAskMsg     = 0;
        $un_readDiscussMsg = 0;
        foreach ( $aData as $val ) {
            if ( $val['object_type'] == 'ask' ) {
                $un_readAskMsg += 1;
            } else {
                $un_readDiscussMsg += 1;
            }
        }
        $member['un_readAskMsg']     = $un_readAskMsg;
        $member['un_readDiscussMsg'] = $un_readDiscussMsg;
        return $member;
    }

    //积分历史
    function point_history( $nPage = 1 )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('积分历史'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;
        $member                     = app::get('pointprofessional')->model('members');
        $member_point               = app::get('pointprofessional')->model('member_point');
        $obj_gift_link              = kernel::service('b2c.exchange_gift');
        if ( $obj_gift_link ) {
            $this->pagedata['exchange_gift_link'] = $obj_gift_link->gen_exchange_link();
        }
        // 额外的会员的信息 - 冻结积分、将要获得的积分
        $obj_extend_point = kernel::servicelist('b2c.member_extend_point_info');
        if ( $obj_extend_point ) {
            foreach ( $obj_extend_point as $obj ) {
                $this->pagedata['extend_point_html'] = $obj->gen_extend_detail_point($this->app->member_id);
            }
        }
        $nodes_obj = $this->app->model('shop');
        $nodes     = $nodes_obj->count(array( 'node_type' => 'ecos.taocrm', 'status' => 'bind' ));
		$nodes = 0;//crm对接，积分不对接
        if ( $nodes > 0 ) {
            $getlog_params = array( 'member_id' => $this->app->member_id, 'page' => $nPage, 'page_size' => $this->pagesize );
            $pointlog      = kernel::single('b2c_member_point_contact_crm')->getPointLog($getlog_params);

            $count                      = $pointlog['total'];
            $aPage                      = $this->get_start($nPage, $count);
            $this->pagedata['total']    = $member->get_real_point($this->app->member_id, '1');
            $this->pagedata['historys'] = $pointlog['historys'];
        } else {
            $count                      = $member_point->count(array( 'member_id' => $this->app->member_id ));
            $aPage                      = $this->get_start($nPage, $count);
            $params['data']             = $member_point->get_all_list('*', array( 'member_id' => $this->app->member_id, 'status' => 'false' ), $aPage['start'], $this->pagesize);
            $this->pagedata['total']    = $member->get_real_point($this->app->member_id, '1');
            $this->pagedata['historys'] = $params['data'];
        }
        $params['page'] = $aPage['maxPage'];
        $this->pagination($nPage, $params['page'], 'point_history');
        $this->page('wap/member/point_history.html');
    }

    //我的订单
    public function orders( $pay_status = 'all', $nPage = 1 )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('我的订单'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;
		$order                      = $this->app->model('orders');
		$is_show=0;
		if($this->app->member_id=='33553')
		{
			$is_show=1;
			//$this->app->member_id='738';
		}
        if ( $pay_status == 'all' ) {
            $aData = $order->fetchByMember($this->app->member_id, $nPage);
        } else {
            $order_status = array();
            if ( $pay_status == 'nopayed' ) {
                $order_status['promotion_type'] = 'normal';
                $order_status['pay_status']     = 0;
                $order_status['status']         = 'active';
            } else if ( $pay_status == 'no_delivered' ) { // 待发货
                $order_status['promotion_type'] = 'normal';
                $order_status['pay_status']     = 1;
                $order_status['ship_status']    = 0;
                $order_status['status']         = 'active';
            } else if ( $pay_status == 'has_delivered' ) { // 待收货
                $order_status['promotion_type']  = 'normal';
                $order_status['pay_status']      = 1;
                $order_status['ship_status']     = 1;
                $order_status['status']          = 'active';
                $order_status['received_status'] = 0;
            } else if ( $pay_status == 'sale_return' ) { // 退货
                $order_status['promotion_type'] = 'normal';
                $order_status['pay_status']     = 6;
                $order_status['ship_status']    = 4;
            } else if ( $pay_status == 'point' ) { // 积分
                $order_status['order_type'] = 'point';
            }

            if ( $pay_status == 'prepare' ) {
                //$order_status['pay_status'] = 1;
                //$order_status['status'] = 'active';
                $order_status['promotion_type'] = 'prepare';
            }
            $aData = $order->fetchByMember($this->app->member_id, $nPage, $order_status);
        }
        $this->get_order_details($aData, 'member_orders');
        $oImage       = app::get('image')->model('image');
        $oGoods       = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach ( $aData['data'] as $k => &$v ) {
            $order_payed = kernel::single('b2c_order_pay')->check_payed($v['order_id']);
            if ( $order_payed == $v['total_amount'] ) {
                $v['is_pay'] = 1;
            } else {
                $v['is_pay'] = 0;
            }
            foreach ( $v['goods_items'] as $k2 => &$v2 ) {
                $spec_desc_goods              = $oGoods->getList('spec_desc', array( 'goods_id' => $v2['product']['goods_id'] ));
                $select_spec_private_value_id = reset($v2['product']['products']['spec_desc']['spec_private_value_id']);
                $spec_desc_goods              = reset($spec_desc_goods[0]['spec_desc']);
                if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                    list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                    $v2['product']['thumbnail_pic'] = $default_product_image;
                } else {
                    if ( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id", array( 'image_id' => $v['image_default_id'] )) ) {
                        $v2['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    }
                }
            }
        }
        // echo '<pre>';print_r( $aData['data']);exit();
        //获取预售信息主要是时间217-225
        $prepare_order = kernel::service('prepare_order');
        if ( $prepare_order ) {
            $pre_order = $prepare_order->get_prepare_info($aData['data']);
            foreach ( $aData['data'] as $key => $value ) {
                if ( $value['promotion_type'] == 'prepare' ) {
                    $aData['data'][ $key ]['prepare'] = $pre_order[ $value['order_id'] ];
                    //判断是否在预售期，查看订金金额是否已经支付
                    $prepare     = $aData['data'][ $key ]['prepare'] = $pre_order[ $value['order_id'] ];
                    $order_payed = kernel::single('b2c_order_pay')->check_payed($value['order_id']);
                    if ( $order_payed > 0 ) {
                        if ( empty($prepare) ) {
                            $aData['data'][ $key ]['is_pay'] = 0;
                            continue;
                        }
                        if ( $prepare['begin_time'] < time() && time() < $prepare['end_time'] ) {
                            if ( $order_payed == $prepare['preparesell_price'] && $value['pay_status'] == '0' ) {
                                $aData['data'][ $key ]['is_pay'] = 1;
                            }
                        }
                        if ( time() > $prepare['begin_time_final'] && $order_payed >= $prepare['promotion_price'] && ( $value['pay_status'] == '0' || $value['pay_status'] == '3' ) ) {
                            $aData['data'][ $key ]['is_pay'] = 1;
                        }
                    }
                }

            }
        }
        foreach ( $aData['data'] as $key => $value ) {
            $aData['data'][ $key ]['url'] = $this->gen_url(array( 'app' => 'b2c', 'ctl' => "wap_member", 'act' => "receive", 'arg0' => $value['order_id'] ));;
        }
        $this->pagedata['orders'] = $aData['data'];
		if($is_show==1)
		{
			//print_r($aData['data']);
		}
        $arr_args = array( $pay_status );
        $this->pagination($nPage, $aData['pager']['total'], 'orders', $arr_args);
        $mdl_b2c_refund_apply                   = $this->app->model('refund_apply');
        $this->pagedata["field_refunds_reason"] = $mdl_b2c_refund_apply->get_field_refunds_reason(); //退款申请理由列表
        $this->pagedata['res_url']              = $this->app->res_url;
        $this->pagedata['is_orders']            = "true";
        $this->pagedata['active_tab']           = $pay_status;
        $this->page('wap/member/orders.html');
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string tpl
     * @return null
     */
    protected function get_order_details( &$aData, $tml = 'member_orders' )
    {
        if ( isset($aData['data']) && $aData['data'] ) {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type     = kernel::servicelist('order_goodstype_operation');
            foreach ( $arr_service_goods_type as $obj_service_goods_type ) {
                $goods_types                                = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[ $goods_types ] = $obj_service_goods_type;
            }

            foreach ( $aData['data'] as &$arr_data_item ) {
                $this->get_order_detail_item($arr_data_item, $tml);
            }
        }
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string 模版名称
     * @return null
     */
    protected function get_order_detail_item( &$arr_data_item, $tpl = 'member_order_detail' )
    {
        if ( isset($arr_data_item) && $arr_data_item ) {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type     = kernel::servicelist('order_goodstype_operation');
            foreach ( $arr_service_goods_type as $obj_service_goods_type ) {
                $goods_types                                = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[ $goods_types ] = $obj_service_goods_type;
            }


            $arr_data_item['goods_items'] = array();
            $obj_specification            = $this->app->model('specification');
            $obj_spec_values              = $this->app->model('spec_values');
            $obj_goods                    = $this->app->model('goods');
            $oImage                       = app::get('image')->model('image');
            if ( isset($arr_data_item['order_objects']) && $arr_data_item['order_objects'] ) {
                foreach ( $arr_data_item['order_objects'] as $k => $arr_objects ) {
                    $index      = 0;
                    $index_adj  = 0;
                    $index_gift = 0;
                    $image_set  = app::get('image')->getConf('image.set');
                    if ( $arr_objects['obj_type'] == 'goods' || $arr_objects['obj_type'] == 'point' ) {
                        foreach ( $arr_objects['order_items'] as $arr_items ) {
                            if ( !$arr_items['products'] ) {
                                $o                                   = $this->app->model('order_items');
                                $tmp                                 = $o->getList('*', array( 'item_id' => $arr_items['item_id'] ));
                                $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                            }

                            if ( $arr_items['item_type'] == 'product' || $arr_items['item_type'] == 'point' ) {
                                if ( $arr_data_item['goods_items'][ $k ]['product'] )
                                    $arr_data_item['goods_items'][ $k ]['product']['quantity'] = $objMath->number_plus(array( $arr_items['quantity'], $arr_data_item['goods_items'][ $k ]['product']['quantity'] ));
                                else
                                    $arr_data_item['goods_items'][ $k ]['product']['quantity'] = $arr_items['quantity'];

                                $arr_data_item['goods_items'][ $k ]['product']             = $arr_items;
                                $arr_data_item['goods_items'][ $k ]['product']['name']     = $arr_items['name'];
                                $arr_data_item['goods_items'][ $k ]['product']['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][ $k ]['product']['price']    = $arr_items['price'];
                                $arr_data_item['goods_items'][ $k ]['product']['score']    = intval($arr_items['score'] * $arr_data_item['goods_items'][ $k ]['product']['quantity']);
                                $arr_data_item['goods_items'][ $k ]['product']['amount']   = $arr_items['amount'];
                                $arr_goods_list                                            = $obj_goods->getList('image_default_id,spec_desc', array( 'goods_id' => $arr_items['goods_id'] ));

                                $arr_goods = $arr_goods_list[0];
                                // 获取货品关联第一张图片
                                $select_spec_private_value_id = reset($arr_items['products']['spec_desc']['spec_private_value_id']);
                                $spec_desc_goods              = reset($arr_goods['spec_desc']);
                                if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                                    list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                                    $arr_goods['image_default_id'] = $default_product_image;
                                } else {
                                    if ( !$arr_goods['image_default_id'] && !$oImage->getList("image_id", array( 'image_id' => $arr_goods['image_default_id'] )) ) {
                                        $arr_goods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                }

                                $arr_data_item['goods_items'][ $k ]['product']['thumbnail_pic'] = $arr_goods['image_default_id'];
                                $arr_data_item['goods_items'][ $k ]['product']['link_url']      = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_product', 'act' => 'index', 'arg0' => $arr_items['products']['product_id'] ));
                                if ( $arr_items['addon'] ) {
                                    $arrAddon = $arr_addon = unserialize($arr_items['addon']);
                                    if ( $arr_addon['product_attr'] )
                                        unset($arr_addon['product_attr']);
                                    $arr_data_item['goods_items'][ $k ]['product']['minfo'] = $arr_addon;
                                } else {
                                    unset($arrAddon, $arr_addon);
                                }
                                if ( $arrAddon['product_attr'] ) {
                                    foreach ( $arrAddon['product_attr'] as $arr_product_attr ) {
                                        $arr_data_item['goods_items'][ $k ]['product']['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                    }
                                }

                                if ( isset($arr_data_item['goods_items'][ $k ]['product']['attr']) && $arr_data_item['goods_items'][ $k ]['product']['attr'] ) {
                                    if ( strpos($arr_data_item['goods_items'][ $k ]['product']['attr'], $this->app->_(" ")) !== false ) {
                                        $arr_data_item['goods_items'][ $k ]['product']['attr'] = substr($arr_data_item['goods_items'][ $k ]['product']['attr'], 0, strrpos($arr_data_item['goods_items'][ $k ]['product']['attr'], $this->app->_(" ")));
                                    }
                                }
                            } elseif ( $arr_items['item_type'] == 'adjunct' ) {
                                $str_service_goods_type_obj = $arr_service_goods_type_obj[ $arr_items['item_type'] ];
                                $str_service_goods_type_obj->get_order_object(array( 'goods_id' => $arr_items['goods_id'], 'product_id' => $arr_items['products']['product_id'] ), $arrGoods, $tpl);


                                if ( $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ] )
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['quantity'] = $objMath->number_plus(array( $arr_items['quantity'], $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['quantity'] ));
                                else
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['quantity'] = $arr_items['quantity'];

                                if ( !$arrGoods['image_default_id'] ) {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]                  = $arr_items;
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['name']          = $arr_items['name'];
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['score']         = intval($arr_items['score'] * $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['quantity']);
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['goods_id']      = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['price']         = $arr_items['price'];
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['link_url']      = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_product', 'act' => 'index', 'arg0' => $arr_items['products']['product_id'] ));
                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['amount']        = $arr_items['amount'];

                                if ( $arr_items['addon'] ) {
                                    $arr_addon = unserialize($arr_items['addon']);

                                    if ( $arr_addon['product_attr'] ) {
                                        foreach ( $arr_addon['product_attr'] as $arr_product_attr ) {
                                            $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                        }
                                    }
                                }

                                if ( isset($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr']) && $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr'] ) {
                                    if ( strpos($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr'], $this->app->_(" ")) !== false ) {
                                        $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr'] = substr($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr'], 0, strrpos($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_adj ]['attr'], $this->app->_(" ")));
                                    }
                                }

                                $index_adj++;
                            } else {
                                // product gift.
                                if ( $arr_service_goods_type_obj[ $arr_objects['obj_type'] ] ) {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[ $arr_items['item_type'] ];
                                    $str_service_goods_type_obj->get_order_object(array( 'goods_id' => $arr_items['goods_id'], 'product_id' => $arr_items['products']['product_id'] ), $arrGoods, $tpl);

                                    if ( $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ] )
                                        $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['quantity'] = $objMath->number_plus(array( $arr_items['quantity'], $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['quantity'] ));
                                    else
                                        $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['quantity'] = $arr_items['quantity'];

                                    if ( !$arrGoods['image_default_id'] ) {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]                  = $arr_items;
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['name']          = $arr_items['name'];
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['goods_id']      = $arr_items['goods_id'];
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['price']         = $arr_items['price'];
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['score']         = intval($arr_items['score'] * $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['quantity']);
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['link_url']      = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_product', 'act' => 'index', 'arg0' => $arr_items['products']['product_id'] ));
                                    $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['amount']        = $arr_items['amount'];

                                    if ( $arr_items['addon'] ) {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ( $arr_addon['product_attr'] ) {
                                            foreach ( $arr_addon['product_attr'] as $arr_product_attr ) {
                                                $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if ( isset($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr']) && $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr'] ) {
                                        if ( strpos($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr'], $this->app->_(" ")) !== false ) {
                                            $arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr'] = substr($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr'], 0, strrpos($arr_data_item['goods_items'][ $k ][ $arr_items['item_type'] . '_items' ][ $index_gift ]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                                $index_gift++;
                            }
                        }
                    } else {
                        if ( $arr_objects['obj_type'] == 'gift' ) {
                            if ( $arr_service_goods_type_obj[ $arr_objects['obj_type'] ] ) {
                                foreach ( $arr_objects['order_items'] as $arr_items ) {
                                    if ( !$arr_items['products'] ) {
                                        $o                                   = $this->app->model('order_items');
                                        $tmp                                 = $o->getList('*', array( 'item_id' => $arr_items['item_id'] ));
                                        $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                                    }

                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[ $arr_objects['obj_type'] ];
                                    $str_service_goods_type_obj->get_order_object(array( 'goods_id' => $arr_items['goods_id'], 'product_id' => $arr_items['products']['product_id'] ), $arrGoods, $tpl);

                                    if ( !isset($arr_items['products']['product_id']) || !$arr_items['products']['product_id'] )
                                        $arr_items['products']['product_id'] = $arr_items['goods_id'];

                                    if ( $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ] )
                                        $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['quantity'] = $objMath->number_plus(array( $arr_items['quantity'], $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['quantity'] ));
                                    else
                                        $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['quantity'] = $arr_items['quantity'];

                                    if ( !$arrGoods['image_default_id'] ) {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }

                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['name']          = $arr_items['name'];
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['goods_id']      = $arr_items['goods_id'];
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['price']         = $arr_items['price'];
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['point']         = intval($arr_items['score'] * $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['quantity']);
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['nums']          = $arr_items['quantity'];
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['link_url']      = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_product', 'act' => 'index', 'arg0' => $arr_items['products']['product_id'] ));
                                    $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['amount']        = $arr_items['amount'];

                                    if ( $arr_items['addon'] ) {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ( $arr_addon['product_attr'] ) {
                                            foreach ( $arr_addon['product_attr'] as $arr_product_attr ) {
                                                $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if ( isset($arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr']) && $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr'] ) {
                                        if ( strpos($arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr'], $this->app->_(" ")) !== false ) {
                                            $arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr'] = substr($arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr'], 0, strrpos($arr_data_item[ $arr_items['item_type'] . '_items' ][ $arr_items['products']['product_id'] ]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                            }
                        } else {
                            if ( $arr_service_goods_type_obj[ $arr_objects['obj_type'] ] ) {

                                $str_service_goods_type_obj       = $arr_service_goods_type_obj[ $arr_objects['obj_type'] ];
                                $arr_data_item['extends_items'][] = $str_service_goods_type_obj->get_order_object($arr_objects, $arr_Goods, $tpl);
                            }
                        }
                    }
                }
            }

        }
    }


    public function signin()
    {
        $site_checkout_login_point_open = $this->app->getConf('site.checkout.login_point.open');
        $site_login_point_num           = $this->app->getConf('site.login_point.num');
        if ( $site_checkout_login_point_open == 'false' ) {
            $msg = app::get('b2c')->_('未开启签到送积分功能');
            $this->splash('failed', '', $msg, '', '', true);
        }


        $signin_obj  = $this->app->model('member_signin');
        $member_id   = $this->app->member_id;
        $signin_date = date('Y-m-d');

        if ( $signin_obj->exists_signin($member_id, $signin_date) ) {
            $msg = app::get('b2c')->_('您今天已经签到过');
            $this->splash('failed', '', $msg, '', '', true);
        }
        $data   = array(
            'member_id'   => $member_id,
            'signin_date' => $signin_date,
            'signin_time' => time(),
            'point'       => $site_login_point_num
        );
        $result = kernel::single('b2c_member_signin')->sign($data);
        if ( $result ) {
            $msg = app::get('b2c')->_('签到成功，获得' . $site_login_point_num . '积分');
            $this->splash('success', '', $msg, false, 0, true);

        } else {
            $msg = app::get('b2c')->_('签到失败');
            $this->splash('error', '', $msg, false, 0, true);
        }
    }


    /**
     * Generate the order detail
     * @params string order_id
     * @return null
     */
    public function orderdetail( $order_id = 0 )
    {
		if($this->app->member_id=='33553')
		{
			$is_show=1;
			//$this->app->member_id='738';
		}
        if ( !isset($order_id) || !$order_id ) {
            $this->begin(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index' ));
            $this->end(false, app::get('b2c')->_('订单编号不能为空！'));
        }
        $objOrder  = $this->app->model('orders');
        $subsdf    = array( 'order_objects' => array( '*', array( 'order_items' => array( '*', array( ':products' => '*' ) ) ) ), 'order_pmt' => array( '*' ) );
        $sdf_order = $objOrder->dump($order_id, '*', $subsdf);
        $objMath   = kernel::single("ectools_math");
        if ( !$sdf_order || $this->app->member_id != $sdf_order['member_id'] ) {
            $this->_response->set_http_response_code(404);
            $this->_response->set_body(app::get('b2c')->_('订单号：') . $order_id . app::get('b2c')->_('不存在！'));
            return;
        }
        if ( $sdf_order['member_id'] ) {
            $member                         = $this->app->model('members');
            $aMember                        = $member->dump($sdf_order['member_id'], 'email');
            $sdf_order['receiver']['email'] = $aMember['contact']['email'];
        }

        // 处理收货人地区
        $arr_consignee_area    = array();
        $arr_consignee_regions = array();
        if ( strpos($sdf_order['consignee']['area'], ':') !== false ) {
            $arr_consignee_area = explode(':', $sdf_order['consignee']['area']);
            if ( $arr_consignee_area[1] ) {
                if ( strpos($arr_consignee_area[1], '/') !== false ) {
                    $arr_consignee_regions = explode('/', $arr_consignee_area[1]);
                }
            }

            $sdf_order['consignee']['area'] = ( is_array($arr_consignee_regions) && $arr_consignee_regions ) ? $arr_consignee_regions[0] . $arr_consignee_regions[1] . $arr_consignee_regions[2] : $sdf_order['consignee']['area'];
        }

        // 订单的相关信息的修改
        $obj_other_info = kernel::servicelist('b2c.order_other_infomation');
        if ( $obj_other_info ) {
            foreach ( $obj_other_info as $obj ) {
                $this->pagedata['discount_html'] = $obj->gen_point_discount($sdf_order);
            }
        }
        $order_payed = kernel::single('b2c_order_pay')->check_payed($sdf_order['order_id']);
        if ( $order_payed == $sdf_order['total_amount'] ) {
            $sdf_order['is_pay'] = 1;
        } else {
            $sdf_order['is_pay'] = 0;
        }
        //判断预售 promotion_type
        if ( $sdf_order['promotion_type'] == 'prepare' ) {
            $prepare_order = kernel::service('prepare_order');
            if ( $prepare_order ) {
                $prepare     = $prepare_order->get_order_info($sdf_order['order_id']);
                $order_payed = kernel::single('b2c_order_pay')->check_payed($sdf_order['order_id']);
                if ( $order_payed > 0 ) {
                    if ( $prepare['begin_time'] < time() && time() < $prepare['end_time'] ) {
                        if ( $order_payed == $prepare['preparesell_price'] ) {
                            $sdf_order['is_pay'] = 1;
                        }
                    }
                }
            }
        }
        //显示是否有必填项
        $minfo = $objOrder->minfo($sdf_order);
        if ( !empty($minfo) ) {
            $this->pagedata['is_minfo'] = 1;
            $this->pagedata['minfo']    = $minfo;
        } else {
            $this->pagedata['is_minfo'] = 0;
        }
        $this->pagedata['order'] = $sdf_order;

        $order_items = array();
        $gift_items  = array();
        $this->get_order_detail_item($sdf_order, 'member_order_detail');
        $this->pagedata['order'] = $sdf_order;
// echo "<pre>";print_r($this->pagedata['order']);exit;
        /** 将商品促销单独剥离出来 **/
        if ( $this->pagedata['order']['order_pmt'] ) {
            foreach ( $this->pagedata['order']['order_pmt'] as $key => $arr_pmt ) {
                if ( $arr_pmt['pmt_type'] == 'goods' ) {
                    $this->pagedata['order']['goods_pmt'][ $arr_pmt['product_id'] ][ $key ] = $this->pagedata['order']['order_pmt'][ $key ];
                    unset($this->pagedata['order']['order_pmt'][ $key ]);
                }
            }
        }
        /** end **/

        // 得到订单留言.
        $oMsg        = kernel::single("b2c_message_order");
        $arrOrderMsg = $oMsg->getList('*', array( 'order_id' => $order_id, 'object_type' => 'order' ), $offset = 0, $limit = -1, 'time DESC');

        $this->pagedata['ordermsg'] = $arrOrderMsg;
        $this->pagedata['res_url']  = $this->app->res_url;

        //我已付款
        $$timeHours = array();
        for ( $i = 0; $i < 24; $i++ ) {
            $v               = ( $i < 10 ) ? '0' . $i : $i;
            $timeHours[ $v ] = $v;
        }
        $timeMins = array();
        for ( $i = 0; $i < 60; $i++ ) {
            $v              = ( $i < 10 ) ? '0' . $i : $i;
            $timeMins[ $v ] = $v;
        }
        $this->pagedata['timeHours'] = $timeHours;
        $this->pagedata['timeMins']  = $timeMins;

        // 生成订单日志明细
        //$oLogs =$this->app->model('order_log');
        //$arr_order_logs = $oLogs->getList('*', array('rel_id' => $order_id));
        $arr_order_logs              = $objOrder->getOrderLogList($order_id);
        $this->pagedata['orderlogs'] = $arr_order_logs['data'];
        $logi                        = app::get('logisticstrack')->is_actived();
        $this->pagedata['logi']      = $logi;

        // 取到支付单信息
        $obj_payments                   = app::get('ectools')->model('payments');
        $this->pagedata['paymentlists'] = $obj_payments->get_payments_by_order_id($order_id);
		
		//取退款信息
		$refunds = app::get('ectools')->model('refunds');
		$order_bills = app::get('ectools')->model('order_bills');
		$aRefund = $order_bills->getList('*',array('rel_id'=>$order_id, 'bill_type'=>'refunds', 'pay_object'=>'order'));
		$aRefund_money=0;
		if ($aRefund)
        {
            foreach ($aRefund as &$str_refund)
            {
                $arr_refunds = $refunds->dump($str_refund['bill_id'], 'pay_name,status,t_payed');
				if($arr_refunds['status']=='succ')
				{
					$aRefund_money+=$str_refund['money'];
				}
                $str_refund['pay_name'] = $arr_refunds['pay_name'];
                $str_refund['status'] = $arr_refunds['status'];
                $str_refund['t_payed'] = $arr_refunds['t_payed'];
            }
        }
		$this->pagedata['aRefund_money'] = $aRefund_money;

        // 支付方式的解析变化
        $obj_payments_cfgs                  = app::get('ectools')->model('payment_cfgs');
        $arr_payments_cfg                   = $obj_payments_cfgs->getPaymentInfo($this->pagedata['order']['payinfo']['pay_app_id']);
        $this->pagedata['order']['payment'] = $arr_payments_cfg;

        //获取运单号
        $deliveryMdl = app::get('b2c')->model('delivery');
        $deliveryArray = $deliveryMdl->getRow('logi_name,logi_no', ['order_id'=>$order_id]);

        $this->pagedata['reship'] = $deliveryArray;

        #//物流跟踪安装并且开启
        #$logisticst = app::get('b2c')->getConf('system.order.tracking');
        #$logisticst_service = kernel::service('b2c_change_orderloglist');
        #if(isset($logisticst) && $logisticst == 'true' && $logisticst_service){
        #    $this->pagedata['services']['logisticstack'] = $logisticst_service;
        #}
        $this->pagedata['orderlogs'] = $arr_order_logs['data'];
        // 添加html埋点
        foreach ( kernel::servicelist('b2c.order_add_html') as $services ) {
            if ( is_object($services) ) {
                if ( method_exists($services, 'fetchHtml') ) {
                    $services->fetchHtml($this, $order_id, 'site/invoice_detail.html');
                }
            }
        }
        $this->pagedata['controller'] = "orders";
        // 预售订单信息
        $prepare_order = kernel::service('prepare_order');
        if ( $prepare_order ) {
            $pre_order = $prepare_order->get_order_info($order_id);
            if ( !empty($pre_order) ) {
                $pre_order['prepare_type'] = 'prepare';
                $this->pagedata['prepare'] = $pre_order;
            }
        }

        //echo '<pre>';print_r($prepare_order);exit();
        $mdl_b2c_refund_apply                   = $this->app->model('refund_apply');
        $this->pagedata["field_refunds_reason"] = $mdl_b2c_refund_apply->get_field_refunds_reason(); //退款申请理由列表
        $this->page('wap/member/orderdetail.html');
    }

    //物流信息查询
    function logistic( $deliveryid )
    {
        $deliveryMdl                    = app::get('b2c')->model('delivery');
        $delivery                       = $deliveryMdl->getList('logi_id,logi_name,logi_no', array( 'delivery_id' => $deliveryid, 'disabled' => 'false' ), 0, 1);
        $this->pagedata['delivery']     = $delivery;
        $this->pagedata['logisticsurl'] = $this->gen_url(array( 'app' => 'logisticstrack', 'ctl' => 'wap_tracker', 'act' => 'pull', 'arg0' => $deliveryid ));
        $this->page('wap/member/logistic.html');
    }

    function deposit()
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('预存款充值'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;

        $oCur                        = app::get('ectools')->model('currency');
        $currency                    = $oCur->getDefault();
        $this->pagedata['currencys'] = $currency;
        $this->pagedata['currency']  = $currency['cur_code'];
        $opay                        = app::get('ectools')->model('payment_cfgs');
        $aOld                        = $opay->getListByCode($currency['cur_code'], array( 'iscommon', 'iswap' ));

        #获取默认的货币
        $obj_currency                   = app::get('ectools')->model('currency');
        $arr_def_cur                    = $obj_currency->getDefault();
        $this->pagedata['def_cur_sign'] = $arr_def_cur['cur_sign'];

        $aData = array();
        foreach ( $aOld as $val ) {
            if ( $this->from_weixin() ) {
                if ( $val['app_id'] == 'malipayv2' ) {
                    continue;
                }
            } else {
                if ( substr($val['app_id'], 0, 5) == 'wxpay' ) {
                    continue;
                }
            }
            if ( ( $val['app_id'] != 'deposit' ) && ( $val['app_id'] != 'offline' ) ) {
				//当支付方式为微信支付js接口时，获取openid
				if ( $val['app_id'] == 'wxpayjsapi' ) {
					$return_url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'deposit','full'=>1));
					if ( !kernel::single('weixin_openid')->check($return_url, $msg) )
					{
						
					}
					else
					{
						$aData[] = $val;
					}
				}
				else
				{
					$aData[] = $val;
				}
				
            }
        }
        $moneySetting = app::get('proregister')->getConf('balance');
        $moneyRule = json_decode($moneySetting['rule'],true);
        $moneyList = [];
        if(!empty($moneyRule)){
			if($moneySetting['case']==1)
			{
				foreach ($moneyRule as $key=>$value){
					$value['min_receive']=$value['min_amount']*$value['receive']/100;
					$moneyList[$value['min_amount']] = $value;
				}
			}
			if($moneySetting['case']==2)
			{
				foreach ($moneyRule as $key=>$value){
					$value['min_receive']=$value['receive'];
					$moneyList[$value['amount']] = $value;
				}
			}

        }
        ksort($moneyList);
		if($moneyList)
		{
			$k=1;
			foreach ($moneyList as $key=>$value)
			{
				if($k==1)
				{
					$first_m=array('key'=>$key,'val'=>$value['min_receive']);
				}
				$k++;
			}
		}

        $this->pagedata['moneyStatus'] = $moneySetting['status'];
		$this->pagedata['moneyCase'] = $moneySetting['case'];
		$this->pagedata['first_m'] = $first_m;
        $this->pagedata['moneyList'] = $moneyList;
        $this->pagedata['total']      = $this->member['advance'];
		$this->pagedata['gifttotal']      = $this->member['giftadvance'];
		$this->pagedata['alltotal']      = $this->member['alladvance'];
        $this->pagedata['payments']   = $aData;
        $this->pagedata['member_id']  = $this->app->member_id;
        $this->pagedata['return_url'] = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'balance' ));

        $this->page('wap/member/deposit.html');
    }

    // 判断是否来自微信浏览器
    function from_weixin()
    {
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }


    //预存款交易记录
    public function balance( $nPage = 1 )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('我的预存款'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;

        $member         = $this->app->model('members');
        $mem_adv        = $this->app->model('member_advance');
        $items_adv      = $mem_adv->get_list_bymemId($this->app->member_id);
        $count          = count($items_adv);
        $aPage          = $this->get_start($nPage, $count);
        $params['data'] = $mem_adv->getList('*', array( 'member_id' => $this->app->member_id ), $aPage['start'], $this->pagesize, 'mtime desc');
        $params['page'] = $aPage['maxPage'];
        $this->pagination($nPage, $params['page'], 'balance');
        $this->pagedata['advlogs'] = $params['data'];
        $data                      = $member->dump($this->app->member_id, 'advance');
        $this->pagedata['total']   = $data['advance']['total'];
        // errorMsg parse.
        $this->pagedata['errorMsg'] = json_decode($_GET['errorMsg']);
        $this->page('wap/member/balance.html');
    }

    function favorite( $nPage = 1 )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('商品收藏'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;
        $aData                      = kernel::single('b2c_member_fav')->get_favorite($this->app->member_id, $this->member['member_lv'], $nPage);
        $imageDefault               = app::get('image')->getConf('image.set');
        $aProduct                   = $aData['data'];
        foreach ( $aProduct as $k => $v ) {
            if ( $v['nostore_sell'] ) {
                $aProduct[ $k ]['store']      = 999999;
                $aProduct[ $k ]['product_id'] = $v['spec_desc_info'][0]['product_id'];
            } else {
                foreach ( (array)$v['spec_desc_info'] as $value ) {
                    $aProduct[ $k ]['product_id'] = $value['product_id'];
                    $aProduct[ $k ]['spec_info']  = $value['spec_info'];
                    $aProduct[ $k ]['price']      = $value['price'];
                    if ( is_null($value['store']) ) {
                        $aProduct[ $k ]['store'] = 999999;
                        break;
                    } elseif ( ( $value['store'] - $value['freez'] ) > 0 ) {
                        $aProduct[ $k ]['store'] = $value['store'] - $value['freez'];
                        break;
                    } else {
                        $aProduct[ $k ]['store'] = false;
                    }
                }
            }
        }
		
        $this->pagedata['favorite'] = $aProduct;
        $this->pagination($nPage, $aData['page'], 'favorite');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $setting['buytarget']           = $this->app->getConf('site.buy.target');
        $this->pagedata['setting']      = $setting;
        $this->pagedata['current_page'] = $nPage;
        /** 接触收藏的页面地址 **/
        $this->pagedata['fav_ajax_del_goods_url'] = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'ajax_del_fav', 'args' => array( 'goods' ) ));
        $this->page('wap/member/favorite.html');
    }

    /*
     *删除商品收藏
     * */
    function ajax_del_fav( $gid = null, $object_type = 'goods' )
    {
        if ( !$gid ) {
            $this->splash('error', null, app::get('b2c')->_('参数错误！'));
        }
        if ( !kernel::single('b2c_member_fav')->del_fav($this->app->member_id, $object_type, $gid, $maxPage) ) {
            $this->splash('error', null, app::get('b2c')->_('移除失败！'));
        } else {
            $this->set_cookie('S[GFAV]' . '[' . $this->app->member_id . ']', $this->get_member_fav($this->app->member_id), false);

            $current_page = $_POST['current'];
            if ( $current_page > $maxPage ) {
                $current_page = $maxPage;
                $reload_url   = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'favorite', 'args' => array( $current_page ) ));
                $this->splash('success', $reload_url, app::get('b2c')->_('成功移除！'));
            }
            $aData    = kernel::single('b2c_member_fav')->get_favorite($this->app->member_id, $this->member['member_lv'], $current_page);
            $aProduct = $aData['data'];

            $oImage       = app::get('image')->model('image');
            $imageDefault = app::get('image')->getConf('image.set');
            foreach ( $aProduct as $k => $v ) {
                if ( !$oImage->getList("image_id", array( 'image_id' => $v['image_default_id'] )) ) {
                    $aProduct[ $k ]['image_default_id'] = $imageDefault['S']['default_image'];
                }
            }
            $this->pagedata['favorite']     = $aProduct;
            $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
            $reload_url                     = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'favorite' ));
            $this->splash('success', $reload_url, app::get('b2c')->_('成功移除！'));
        }
    }

    function ajax_fav()
    {
        $object_type = $_POST['type'];
        $nGid        = $_POST['gid'];
        if ( !kernel::single('b2c_member_fav')->add_fav($this->app->member_id, $object_type, $nGid) ) {
            $this->splash('failed', app::get('b2c')->_('商品收藏添加失败！'), '', '', true);
        } else {
            $this->set_cookie('S[GFAV]' . '[' . $this->app->member_id . ']', $this->get_member_fav($this->app->member_id), false);
            $this->splash('success', $url, app::get('b2c')->_('商品收藏成功'), false, 0, true);
        }
    }

    //收获地址
    function receiver()
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('收货地址'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;
        $objMem                     = $this->app->model('members');
        $this->pagedata['receiver'] = $objMem->getMemberAddr($this->app->member_id);
        $this->pagedata['is_allow'] = ( count($this->pagedata['receiver']) < 10 ? 1 : 0 );
        $this->pagedata['num']      = count($this->pagedata['receiver']);
        $this->pagedata['res_url']  = $this->app->res_url;
        $this->page('wap/member/receiver.html');
    }


    /*
     * 设置和取消默认地址，$disabled 2为设置默认1为取消默认
     */
    function set_default( $addrId = null, $disabled = 2 )
    {
        // $addrId = $_POST['addr_id'];
        if ( !$addrId ) $this->splash('failed', null, app::get('b2c')->_('参数错误'), true);
        $url        = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'receiver' ));
        $obj_member = $this->app->model('members');
        $member_id  = $this->app->member_id;
        if ( $obj_member->check_addr($addrId, $member_id) ) {
            if ( $obj_member->set_to_def($addrId, $member_id, $message, $disabled) ) {
                $this->splash('success', $url, $message, false, 0, true);
            } else {
                $this->splash('failed', $url, $message, false, 0, true);
            }
        } else {
            $this->splash('failed', 'back', app::get('b2c')->_('参数错误'), false, 0, true);
        }
    }

    /*
     *添加、修改收货地址
     * */
    function modify_receiver( $addrId = null )
    {
        if ( !$addrId ) {
            echo app::get('b2c')->_("参数错误");
            exit;
        }
        $obj_member = $this->app->model('members');
        if ( $obj_member->check_addr($addrId, $this->app->member_id) ) {
            if ( $aRet = $obj_member->getAddrById($addrId) ) {
                $aRet['defOpt'] = array( '0' => app::get('b2c')->_('否'), '1' => app::get('b2c')->_('是') );
                $this->pagedata = $aRet;
            } else {
                $this->_response->set_http_response_code(404);
                $this->_response->set_body(app::get('b2c')->_('修改的收货地址不存在！'));
                exit;
            }
            $this->page('wap/member/modify_receiver.html');
        } else {
            echo app::get('b2c')->_("参数错误");
            exit;
        }
    }

    /*
     *保存收货地址
     * */
    function save_rec()
    {
        if ( !$_POST['def_addr'] ) {
            $_POST['def_addr'] = 0;
        }
        $save_data = kernel::single('b2c_member_addrs')->purchase_save_addr($_POST, $this->app->member_id, $msg);
        if ( !$save_data ) {
            $this->splash('failed', null, $msg, '', '', true);
        }
        $this->splash('success', $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'receiver' )), app::get('b2c')->_('保存成功'), '', '', true);
    }

    //添加收货地址
    function add_receiver()
    {
        $obj_member = $this->app->model('members');
        if ( $obj_member->isAllowAddr($this->app->member_id) ) {
            $this->page('wap/member/modify_receiver.html');
        } else {
            $this->splash('failed', $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'receiver' )), app::get('b2c')->_('最多添加10个收货地址'), '', '', true);
        }
    }


    //删除收货地址
    function del_rec()
    {
        $addrId = $_POST['addr_id'];
        if ( !$addrId ) {
            $arr_json_data = array(
                "success" => false,
                "error"   => '参数错误',
            );
            echo json_encode($arr_json_data);
            exit;
        }
        $obj_member = $this->app->model('members');
        if ( $obj_member->check_addr($addrId, $this->app->member_id) ) {
            if ( $obj_member->del_rec($addrId, $message, $this->app->member_id) ) {
                $arr_json_data = array(
                    "success" => true
                );
                echo json_encode($arr_json_data);
                exit;
            } else {
                $arr_json_data = array(
                    "success" => false,
                    "error"   => $message,
                );
                echo json_encode($arr_json_data);
                exit;
            }
        } else {
            $arr_json_data = array(
                "success" => false,
                "error"   => '操作失败',
            );
            echo json_encode($arr_json_data);
            exit;
        }
    }


    /*
        过滤POST来的数据,基于安全考虑,会把POST数组中带HTML标签的字符过滤掉
    */
    function check_input( $data )
    {
        $aData = $this->arrContentReplace($data);
        return $aData;
    }

    function arrContentReplace( $array )
    {
        if ( is_array($array) ) {
            foreach ( $array as $key => $v ) {
                $array[ $key ] = $this->arrContentReplace($array[ $key ]);
            }
        } else {
            $array = strip_tags($array);
        }
        return $array;
    }

    /*
     * 获取评论咨询的数据
     *
     * */
    public function getComment( $nPage = 1, $type = 'discuss' )
    {
        //获取评论咨询基本数据
        $comment     = kernel::single('b2c_message_disask');
        $aData       = $comment->get_member_disask($this->app->member_id, $nPage, $type);
        $gids        = array();
        $productGids = array();
        foreach ( (array)$aData['data'] as $k => $v ) {
            if ( $v['type_id'] && !in_array($v['type_id'], $gids) ) {
                $gids[] = $v['type_id'];
            }
            if ( !$v['product_id'] && !in_array($v['type_id'], $productGids) ) {
                $productGids[] = $v['type_id'];
            }

            if ( $v['items'] ) {//统计回复未读的数量
                $unReadNum = 0;
                foreach ( $v['items'] as $val ) {
                    if ( $val['mem_read_status'] == 'false' ) {
                        $unReadNum += 1;
                    }
                }
            }
            $aData['data'][ $k ]['unReadNum'] = $unReadNum;
        }

        //获取货品ID
        $productData = $productGids ? $this->app->model('products')->getList('goods_id,product_id', array( 'goods_id' => $productGids, 'is_default' => 'true' )) : array();
        foreach ( (array)$productData as $p_row ) {
            $productList[ $p_row['goods_id'] ] = $p_row['product_id'];
        }
        $this->pagedata['productList'] = $productList;

        //评论咨询商品信息
        $goodsData = $gids ? $this->app->model('goods')->getList('goods_id,name,price,thumbnail_pic,udfimg,image_default_id', array( 'goods_id' => $gids )) : null;
        if ( $goodsData ) {
            foreach ( $goodsData as $row ) {
                $goodsList[ $row['goods_id'] ] = $row;
            }
        }
        $this->pagedata['goodsList'] = $goodsList;

        //评论咨询私有的数据
        if ( $type == 'discuss' ) {
            $this->pagedata['point_status'] = app::get('b2c')->getConf('goods.point.status') ? app::get('b2c')->getConf('goods.point.status') : 'on';
            if ( $this->pagedata['point_status'] == 'on' ) {//如果开启评分则获取评论评分
                $objPoint                      = $this->app->model('comment_goods_point');
                $goods_point                   = $objPoint->get_single_point_arr($gids);
                $this->pagedata['goods_point'] = $goods_point;
            }
        } else {
            $gask_type = unserialize($this->app->getConf('gask_type'));//咨询类型
            foreach ( (array)$gask_type as $row ) {
                $gask_type_list[ $row['type_id'] ] = $row['name'];
            }
            $this->pagedata['gask_type'] = $gask_type_list;
        }
        return $aData;
    }

    function comment( $nPage = 1 )
    {
        //面包屑
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('评论与咨询'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;

        $comment                       = $this->getComment($nPage, 'discuss');
        $this->pagedata['commentList'] = $comment['data'];
        $this->pagination($nPage, $comment['page'], 'comment');
        $this->output();
    }

    function ask( $nPage = 1 )
    {
        //面包屑
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('评论与咨询'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;

        $this->pagedata['controller']  = "comment";
        $comment                       = $this->getComment($nPage, 'ask');
        $this->pagedata['commentList'] = $comment['data'];
        $this->pagedata['commentType'] = 'ask';
        $this->pagination($nPage, $comment['page'], 'ask');
        $this->action_view = 'comment.html';
        $this->output();
    }

    /*
     *未评论商品
     **/
    public function nodiscuss( $nPage = 1 )
    {
        //面包屑
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]               = array( 'title' => app::get('b2c')->_('未评论商品'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;

        //获取会员已发货的商品日志
        $sell_logs = $this->app->model('sell_logs')->getList('order_id,product_id,goods_id', array( 'member_id' => $this->app->member_id ));
        //获取会员已评论的商品
        $comments = $this->app->model('member_comments')->getList('order_id,product_id', array( 'author_id' => $this->app->member_id, 'object_type' => 'discuss', 'for_comment_id' => '0' ));
        $data     = array();
        if ( $comments ) {
            foreach ( (array)$comments as $row ) {
                if ( $row['order_id'] && $row['product_id'] ) {
                    $data[ $row['order_id'] ][ $row['product_id'] ] = $row['product_id'];
                }
            }
        }

        foreach ( (array)$sell_logs as $key => $log_row ) {
            if ( $data && $data[ $log_row['order_id'] ][ $log_row['product_id'] ] == $log_row['product_id'] ) {
                unset($sell_logs[ $key ]);
            } else {
                $filter['order_id'][]        = $log_row['order_id'];
                $filter['product_id'][]      = $log_row['product_id'];
                $filter['item_type|noequal'] = 'gift';
            }
        }

        $orderItemModel = app::get('b2c')->model('order_items');
        $limit          = $this->pagesize;
        $start          = ( $nPage - 1 ) * $limit;
        $i              = 0;
        $nogift         = $orderItemModel->getList('order_id,product_id', $filter);
        if ( $nogift ) {
            foreach ( $nogift as $row ) {
                $tmp_nogift_order_id[]   = $row['order_id'];
                $tmp_nogift_product_id[] = $row['product_id'];
            }
        }
        foreach ( (array)$sell_logs as $key => $log_row ) {
            if ( in_array($log_row['order_id'], $tmp_nogift_order_id) && in_array($log_row['product_id'], $tmp_nogift_product_id) ) {//剔除赠品,赠品不需要评论
                if ( $i >= $start && $i < ( $nPage * $limit ) ) {
                    $sell_logs_data[] = $log_row;
                    $gids[]           = $log_row['goods_id'];
                }
                if ( $nogift ) {
                    $i += 1;
                }
            }
        }
        $totalPage = ceil($i / $limit);
        if ( $nPage > $totalPage ) $nPage = $totalPage;

        $this->pagedata['list'] = $sell_logs_data;
        $this->pagination($nPage, $totalPage, 'nodiscuss');

        if ( $gids ) {
            //获取商品信息
            $goodsData = $this->app->model('goods')->getList('goods_id,name,image_default_id', array( 'goods_id' => $gids ));
            $goodsList = array();
            foreach ( (array)$goodsData as $goods_row ) {
                $goodsList[ $goods_row['goods_id'] ]['name']             = $goods_row['name'];
                $goodsList[ $goods_row['goods_id'] ]['image_default_id'] = $goods_row['image_default_id'];
            }
            $this->pagedata['goods'] = $goodsList;

            $this->pagedata['point_status'] = app::get('b2c')->getConf('goods.point.status') ? app::get('b2c')->getConf('goods.point.status') : 'on';
            $this->pagedata['verifyCode']   = $this->app->getConf('comment.verifyCode');
            if ( $this->pagedata['point_status'] == 'on' ) {
                //评分类型
                $comment_goods_type                   = $this->app->model('comment_goods_type');
                $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');
                if ( !$this->pagedata['comment_goods_type'] ) {
                    $sdf['type_id']          = 1;
                    $sdf['name']             = app::get('b2c')->_('商品评分');
                    $addon['is_total_point'] = 'on';
                    $sdf['addon']            = serialize($addon);
                    $comment_goods_type->insert($sdf);
                    $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');
                }
            }

            $this->pagedata['submit_comment_notice'] = $this->app->getConf('comment.submit_comment_notice.discuss');
        }

        $this->pagedata['hide_bottom_nav'] = true;

        $this->page('wap/member/nodiscuss.html');
    }


    function discussDetail( $id )
    {

        $comments = $this->app->model('member_comments')->getRow('*', array( 'comment_id' => $id ));

        $point_status = app::get('b2c')->getConf('goods.point.status') ? app::get('b2c')->getConf('goods.point.status'): 'on';
        if($point_status == 'on'){
            $objPoint = $this->app->model('comment_goods_point');
            $comments['point'] = $objPoint->getList("*", array( 'comment_id' => $id ));
        }
        $comment_goods_type                   = $this->app->model('comment_goods_type');
        $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');

        $this->pagedata['comment'] = $comments;

        $goodsData = $this->app->model('goods')->getRow('goods_id,name,image_default_id', array( 'goods_id' => $comments['point'][0]['goods_id'] ));

        $this->pagedata['goodsData'] = $goodsData;
        $this->page('wap/member/discuss_detail.html');
    }

    public function discuss( $nPage = 1 )
    {


        //获取会员已发货的商品日志
        $sell_logs = $this->app->model('sell_logs')->getList('order_id,product_id,goods_id', array( 'member_id' => $this->app->member_id ));
        //获取会员已评论的商品
        $comments = $this->app->model('member_comments')->getList('comment_id, order_id,product_id', array( 'author_id' => $this->app->member_id, 'object_type' => 'discuss', 'for_comment_id' => '0' ));
        $data     = array();
        if ( $comments ) {
            foreach ( (array)$comments as $row ) {
                if ( $row['order_id'] && $row['product_id'] ) {
                    $data[ $row['order_id'] ][ $row['product_id'] ] = [
                        'product_id' =>  $row['product_id'],
                        'comment_id' =>$row['comment_id']
                    ];
                }
            }
        }

        foreach ( (array)$sell_logs as $key => $log_row ) {
            if ( $data && $data[ $log_row['order_id'] ][ $log_row['product_id'] ]['product_id'] == $log_row['product_id'] ) {
                $filter['order_id'][]        = $log_row['order_id'];
                $filter['product_id'][]      = $log_row['product_id'];
                $filter['item_type|noequal'] = 'gift';
            } else {
                unset($sell_logs[ $key ]);
            }
        }

        $orderItemModel = app::get('b2c')->model('order_items');
        $limit          = $this->pagesize;
        $start          = ( $nPage - 1 ) * $limit;
        $i              = 0;
        $nogift         = $orderItemModel->getList('order_id,product_id', $filter);

        if ( $nogift ) {
            foreach ( $nogift as $row ) {
                $tmp_nogift_order_id[]   = $row['order_id'];
                $tmp_nogift_product_id[] = $row['product_id'];
            }
        }

        foreach ( (array)$sell_logs as $key => $log_row ) {
            if ( in_array($log_row['order_id'], $tmp_nogift_order_id) && in_array($log_row['product_id'], $tmp_nogift_product_id) ) {//剔除赠品,赠品不需要评论
                if ( $i >= $start && $i < ( $nPage * $limit ) ) {
                    $sell_logs_data[$key] = $log_row;
                    $sell_logs_data[$key]['comment_id'] = $data[$log_row['order_id']][$log_row['product_id']]['comment_id'];
                    $gids[]           = $log_row['goods_id'];
                }
                if ( $nogift ) {
                    $i += 1;
                }
            }
        }

        $totalPage = ceil($i / $limit);
        if ( $nPage > $totalPage ) $nPage = $totalPage;
        $this->pagedata['list'] = $sell_logs_data;
        $this->pagination($nPage, $totalPage, 'nodiscuss');

        if ( $gids ) {
            //获取商品信息
            $goodsData = $this->app->model('goods')->getList('goods_id,name,image_default_id', array( 'goods_id' => $gids ));
            $goodsList = array();
            foreach ( (array)$goodsData as $goods_row ) {
                $goodsList[ $goods_row['goods_id'] ]['name']             = $goods_row['name'];
                $goodsList[ $goods_row['goods_id'] ]['image_default_id'] = $goods_row['image_default_id'];
            }
            $this->pagedata['goods'] = $goodsList;

            $this->pagedata['point_status'] = app::get('b2c')->getConf('goods.point.status') ? app::get('b2c')->getConf('goods.point.status') : 'on';
            $this->pagedata['verifyCode']   = $this->app->getConf('comment.verifyCode');
            if ( $this->pagedata['point_status'] == 'on' ) {
                //评分类型
                $comment_goods_type                   = $this->app->model('comment_goods_type');
                $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');
                if ( !$this->pagedata['comment_goods_type'] ) {
                    $sdf['type_id']          = 1;
                    $sdf['name']             = app::get('b2c')->_('商品评分');
                    $addon['is_total_point'] = 'on';
                    $sdf['addon']            = serialize($addon);
                    $comment_goods_type->insert($sdf);
                    $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');
                }
            }

            $this->pagedata['submit_comment_notice'] = $this->app->getConf('comment.submit_comment_notice.discuss');
        }


        $this->display('wap/member/discuss.html');
    }


    //我的优惠券
    function coupon( $nPage = 1 )
    {
        $member_center_url          = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 ));
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $member_center_url );
        $this->path[]               = array( 'title' => app::get('b2c')->_('我的优惠券'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;
        $oCoupon                    = kernel::single('b2c_coupon_mem');
		$filter_search=array();
		$filter_search['disabled'] = 'false';//是否失效
		$filter_search['memc_isvalid'] = 'true';//会员优惠券是否当前可用
        $aData                      = $oCoupon->get_list_m($this->app->member_id,$nPage,$filter_search);
        $total                      = $oCoupon->get_list_m($this->app->member_id,null,$filter_search);
		
        $this->pagination($nPage, ceil(count($total) / $this->pagesize), 'coupon');
        $this->pagedata['coupons']           = $aData;
        $this->pagedata['member_center_url'] = $member_center_url;
        $this->page('wap/member/coupon.html');
    }


    /**
     * 添加留言
     * @params string order_id
     * @params string message type
     */
    public function add_order_msg( $order_id, $msgType = 0 )
    {
        $timeHours = array();
        for ( $i = 0; $i < 24; $i++ ) {
            $v               = ( $i < 10 ) ? '0' . $i : $i;
            $timeHours[ $v ] = $v;
        }
        $timeMins = array();
        for ( $i = 0; $i < 60; $i++ ) {
            $v              = ( $i < 10 ) ? '0' . $i : $i;
            $timeMins[ $v ] = $v;
        }
        $this->pagedata['orderId']   = $order_id;
        $this->pagedata['msgType']   = $msgType;
        $this->pagedata['timeHours'] = $timeHours;
        $this->pagedata['timeMins']  = $timeMins;

        $this->page('wap/member/add_order_msg.html');
    }

    /**
     * 订单留言提交
     * @params null
     * @return null
     */
    public function toadd_order_msg()
    {
        if ( !$_POST['msg']['orderid'] ) {
            $this->splash(false, app::get('b2c')->_('参数错误'), true);
        }

        $obj_filter = kernel::single('b2c_site_filter');
        $_POST      = $obj_filter->check_input($_POST);

        $_POST['to_type']   = 'admin';
        $_POST['author_id'] = $this->app->member_id;
        $_POST['author']    = $this->member['uname'];
        $is_save            = true;
        $obj_order_message  = kernel::single("b2c_order_message");
        if ( $obj_order_message->create($_POST) ) {
            $url = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'orderdetail', 'arg0' => $_POST['msg']['orderid'] ));
            $this->splash('success', $url, app::get('b2c')->_('留言成功'), '', '', true);
        } else {
            $url = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'add_order_msg', 'arg0' => $_POST['msg']['orderid'], 'arg1' => 1 ));
            $this->splash(false, $url, app::get('b2c')->_('留言失败'), '', '', true);
        }
    }
	
	
	/*
     *会员中心首页
     * */
    public function set()
    {

        //面包屑
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
		$this->path[]                    = array( 'title' => app::get('b2c')->_('设置'), 'link' => '#' );
        $GLOBALS['runtime']['path']      = $this->path;
        
        $this->page('wap/member/set.html');
    }
	
	
	/*
     *会员中心 设置手机号页面
     * */
    function set_mobile( $type = '' )
    {
        $member                          = $this->member;
        $obj_pam_members                 = app::get('pam')->model('members');
        $this->pagedata['site_sms_valide'] = $this->app->getConf('site.sms_valide');
		//判断是否绑定手机号码
		$pam_members_model = app::get('pam')->model('members');
        $login_account = $pam_members_model->getList('*',array('member_id'=>$this->app->member_id, 'login_type'=>'mobile'));
		if(empty($login_account))
		{
			$type="reset";
		}
		
		$this->pagedata['send_type'] = $type;
		$this->pagedata['user_account']=$login_account[0];
        $this->path[]                    = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]                    = array( 'title' => app::get('b2c')->_('绑定手机号'), 'link' => '#' );
        $GLOBALS['runtime']['path']      = $this->path;
        $this->page('wap/member/modify_set_mobile.html');
    }
	
	/*
     *保存绑定手机
     * */
    function save_set_mobile()
    {
		$userVcode = kernel::single('b2c_user_vcode');
        if( !$vcodeData = $userVcode->verify($_POST['mobilevcode'],$_POST['mobile'],'activation')){
            $msg = app::get('b2c')->_('验证码错误');
			$this->splash('failed', null, $msg, '', '', true);
        }
		
		$userPassport = kernel::single('b2c_user_passport');
		//判断手机号码唯一性
		if(!$userPassport->check_signup_account( trim($_POST['mobile']),$msg )){
            $this->splash('failed',null,$msg,'','',true);
        }
		
        $accountType = $userPassport->get_login_account_type($_POST['mobile']);
        if($_POST['send_type'] == 'reset'){
          if( !$userPassport->set_new_account($this->app->member_id,trim($_POST['mobile']),$msg) ){
            $msg = $msg ? $msg : app::get('b2c')->_('绑定失败');
			$this->splash('failed', null, $msg, '', '', true);
          }
        }
		else{
          if( !app::get('pam')->model('members')->update(array('login_account'=>$_POST['mobile'],'disabled'=>'false'),array('member_id'=>$this->app->member_id,'login_type'=>'mobile')) ){
            $msg = app::get('b2c')->_('绑定失败');
            $this->splash('failed',null,$msg,true);exit;
          }
        }
        //增加会员同步 2012-05-15
        if( $member_rpc_object = kernel::service("b2c_member_rpc_sync") ) {
            $member_rpc_object->modifyActive($this->app->member_id);
        }
		$url=$this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'set', 'full' => 1 ));
		
		$this->splash('success', $url, "绑定成功", '', '', true);
	}
	
    /*
     *会员中心 修改密码页面
     * */
    function security( $type = '' )
    {
        $member                          = $this->member;
        $obj_pam_members                 = app::get('pam')->model('members');
        $this->pagedata['is_nopassword'] = $obj_pam_members->is_nopassword($member['member_id']);
        $this->path[]                    = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $this->path[]                    = array( 'title' => app::get('b2c')->_('修改密码'), 'link' => '#' );
        $GLOBALS['runtime']['path']      = $this->path;
        $this->page('wap/member/modify_password.html');
    }

    /*
     *保存修改密码
     * */
    function save_security()
    {
        $member          = $this->member;
        $obj_pam_members = app::get('pam')->model('members');
        $passport_login  = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_passport', 'act' => 'login' ));
        $url             = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_passport', 'act' => 'logout', 'arg0' => $passport_login ));
        $userPassport    = kernel::single('b2c_user_passport');
        if ( $obj_pam_members->is_nopassword($member['member_id']) == 'true' ) {
            $result = $userPassport->reset_passport($this->app->member_id, $_POST['passwd']);
            if ( $result ) {
                $this->splash('success', $url, app::get('b2c')->_('修改成功'), true);
            } else {
                $this->splash('failed', null, app::get('b2c')->_('修改失败'), true);
            }
        }
        $result = $userPassport->save_security($this->app->member_id, $_POST, $msg);
        if ( $result ) {
            $this->splash('success', $url, $msg, '', '', true);
        } else {
            $this->splash('failed', null, $msg, '', '', true);
        }
    }

    function cancel( $order_id )
    {
        $this->pagedata['cancel_order_id'] = $order_id;
        $this->page('wap/member/order_cancel_reason.html');

    }

    function docancel()
    {
        $arrMember = kernel::single('b2c_user_object')->get_current_member(); //member_id,uname
        //开启事务处理
        $db                 = kernel::database();
        $transaction_status = $db->beginTransaction();

        $order_cancel_reason = $_POST['order_cancel_reason'];

        $error_url = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'cancel', 'arg0' => $order_cancel_reason['order_id'] ));
        if ( $order_cancel_reason['reason_type'] == 7 && !$order_cancel_reason['reason_desc'] ) {
            $this->splash('failed', $error_url, '请输入详细原因', true);
        }
        if ( strlen($order_cancel_reason['reason_desc']) > 150 ) {
            $this->splash('failed', $error_url, '详细原因过长，请输入50个字以内', true);
        }
        if ( $order_cancel_reason['reason_type'] != 7 && strlen($order_cancel_reason['reason_desc']) > 0 ) {
            $order_cancel_reason['reason_desc'] = '';
        }
        $order_cancel_reason                = utils::_filter_input($order_cancel_reason);
        $order_cancel_reason['cancel_time'] = time();
        $mdl_order                          = app::get('b2c')->model('orders');
        $sdf_order_member_id                = $mdl_order->getRow('member_id', array( 'order_id' => $order_cancel_reason['order_id'] ));
        if ( $sdf_order_member_id['member_id'] != $arrMember['member_id'] ) {
            $db->rollback();
            $this->splash('failed', $error_url, "请勿取消别人的订单", true);
            return;
        }

        $order_payed = kernel::single('b2c_order_pay')->check_payed($order_cancel_reason['order_id']);
        if ( $order_payed > 0 ) {
            $this->splash('failed', $error_url, "支付过的订单，无法取消订单", true);
        }

        $mdl_order_cancel_reason = app::get('b2c')->model('order_cancel_reason');
        $result                  = $mdl_order_cancel_reason->save($order_cancel_reason);
        if ( !$result ) {
            $db->rollback();
            $this->splash('failed', $error_url, "订单取消原因记录失败", true);
        }
        $obj_checkorder = kernel::service('b2c_order_apps', array( 'content_path' => 'b2c_order_checkorder' ));
        if ( !$obj_checkorder->check_order_cancel($order_cancel_reason['order_id'], '', $message) ) {
            $db->rollback();
            $this->splash('failed', $error_url, $message, true);
        }

        $sdf['order_id']     = $order_cancel_reason['order_id'];
        $sdf['op_id']        = $arrMember['member_id'];
        $sdf['opname']       = $arrMember['uname'];
        $sdf['account_type'] = 'member';

        $b2c_order_cancel = kernel::single("b2c_order_cancel");
        if ( $b2c_order_cancel->generate($sdf, $this, $message) ) {
            if ( $order_object = kernel::service('b2c_order_rpc_async') ) {
                $order_object->modifyActive($sdf['order_id']);
            }
            $db->commit($transaction_status);
            $url        = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index' ));
            $obj_coupon = kernel::single("b2c_coupon_order");
            $obj_coupon->use_c($sdf['order_id'], 'cancel');
            $this->splash('success', $url, "订单取消成功", true);
        } else {
            $db->rollback();
            $this->splash('failed', $error_url, "订单取消失败", true);
        }
    }

    function receive( $order_id )
    {
        $arrMember                        = kernel::single('b2c_user_object')->get_current_member();
        $mdl_order                        = app::get('b2c')->model('orders');
        $sdf_order_member_id              = $mdl_order->getRow('member_id', array( 'order_id' => $order_id ));
        $sdf_order_member_id['member_id'] = (int)$sdf_order_member_id['member_id'];
        if ( $sdf_order_member_id['member_id'] != $arrMember['member_id'] ) {
            return '请勿操作别人的收货';
        } else {
            $arr_updates = array( 'order_id' => $order_id, 'received_status' => '1', 'received_time' => time() );
            $mdl_order->save($arr_updates);
            $delivery_mdl = app::get('b2c')->model('order_delivery_time');
            $delivery_mdl->delete(array( 'order_id' => $order_id ));
            $orderLog      = $this->app->model("order_log");
            $log_text      = serialize($log_text);
            $sdf_order_log = array(
                'rel_id'    => $order_id,
                'op_id'     => $arrMember['member_id'],
                'op_name'   => ( !$arrMember['member_id'] ) ? app::get('b2c')->_('顾客') : $arrMember['uname'],
                'alttime'   => time(),
                'bill_type' => 'order',
                'behavior'  => 'receive',
                'result'    => 'SUCCESS',
                'log_text'  => '用户已确认收货！',
            );
            if ( $orderLog->save($sdf_order_log) ) {
                $this->splash('success', null, '已完成收货', true);
                exit;
            } else {
                $this->splash('error', null, '收货失败', true);
                exit;
            }
        }
    }

    public function coupon_receive()
    {
        if ( isset($_POST['cpns_id']) ) {
            $cpnsId = $_POST['cpns_id'];

            $concurrent_cpns = kernel::single("base_concurrent_file");
            $cpns_group_id   = 'cpns_group_' . intval($cpnsId % 1000);
            $concurrent_cpns->status($cpns_group_id);
            if ( !$concurrent_cpns->check_flock() ) {
                $concurrent_cpns->close_lock();
                echo json_encode(array( 'status' => 'fail', msg => "网络异常，请重试" ));
                exit();
            }

            $oExchangeCoupon    = kernel::single('b2c_coupon_mem');
            $memberId           = intval($this->app->member_id);//会员id号
            $obj_widget_coupons = kernel::single('wap_widgets_coupons');
            if ( $memberId ) {
                $msgArr = array(
                    '2' => 'cpns_id为空',
                    '3' => '优惠券已经领光',
                    '4' => '会员等级不符',
                    '5' => '活动未开始',
                    '6' => '活动已结束',
                    '7' => '领取数量超限',
                );
                if ( $obj_widget_coupons->getStatus($cpnsId) ) {
                    $concurrent_cpns->close_lock();
                    echo json_encode(array( 'status' => 'fail', msg => "不可重复领取" ));
                    exit();
                }

                $verify_status = $obj_widget_coupons->verify($cpnsId);
                if ( $verify_status != 1 ) {
                    $concurrent_cpns->close_lock();
                    echo json_encode(array( 'status' => 'fail', 'msg' => $msgArr[ $verify_status ] ));
                    exit();
                }

                $coupons    = $this->app->model('coupons');
                $cur_coupon = $coupons->dump($cpnsId);

                if ( $oExchangeCoupon->obtain($cpnsId, $memberId, $params) ) {
                    $concurrent_cpns->unlock();
                    echo json_encode(array( 'status' => 'success', msg => "领取成功" ));
                    exit();
                } else {
                    $concurrent_cpns->close_lock();
                    echo json_encode(array( 'status' => 'fail', msg => "领取失败" ));
                    exit();
                }
            } else {
                $concurrent_cpns->close_lock();
                echo json_encode(array( 'status' => 'fail', msg => "没有登录" ));
                exit();
            }
        }

        echo json_encode(array( 'status' => 'fail', msg => "参数异常" ));
        exit();
    }


    /**
     * 优惠券列表挂件内优惠券状态查询
     * @params $cpns_id string 优惠券id
     * @return json array('status'=>'success/fail',coupon=>array('1'=>array('receiveStatus'=>true,'url'=>)),msg=>'tips')
     */
    public function coupon_status( $cpns_id )
    {
        if ( !$cpns_id ) {
            echo json_encode(array( 'status' => 'fail', 'coupon' => array(), 'msg' => '参数异常' ));
            exit();
        }
        $cpns_id = explode(',', $cpns_id);

        $data   = array();
        $filter = array( 'cpns_id' => $cpns_id );

        $data = kernel::single('wap_widgets_coupons')->getPromotionCoupons($filter);

        echo json_encode(array( 'status' => 'success', 'coupon' => $data, 'msg' => '' ));
        exit();
    }

    function afterlist( $nPage = 1 )
    {
        $nPage                      = intval($nPage);
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy          = kernel::service("aftersales.return_policy");
        $arr_settings               = array();
        if ( !isset($obj_return_policy) || !is_object($obj_return_policy) ) {
            $this->afterlist_msg('fail', '售后服务应用不存在！', $url = '');
            return '';
        }

        if ( !$obj_return_policy->get_conf_data($arr_settings) ) {
            $this->afterlist_msg('fail', '售后服务信息没有取到！', $url = '');
            return '';
        }
        $order                       = $this->app->model('orders');
        $order_status['pay_status']  = 1;
        $order_status['ship_status'] = array( 1, 2, 3 );
        $order_status['status']      = 'active';
        $aData                       = $order->fetchByMember($this->app->member_id, $nPage, $order_status);
        $this->get_order_details($aData, 'member_orders');
        $oImage       = app::get('image')->model('image');
        $oGoods       = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach ( $aData['data'] as $k => &$v ) {
            foreach ( $v['goods_items'] as $k2 => &$v2 ) {
                $spec_desc_goods = $oGoods->getList('spec_desc,image_default_id', array( 'goods_id' => $v2['product']['goods_id'] ));
                if ( $v2['product']['products']['spec_desc']['spec_private_value_id'] ) {
                    $select_spec_private_value_id = reset($v2['product']['products']['spec_desc']['spec_private_value_id']);
                    $spec_desc_goods              = reset($spec_desc_goods[0]['spec_desc']);
                }
                if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                    list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                    $v2['product']['thumbnail_pic'] = $default_product_image;
                } elseif ( $spec_desc_goods[0]['image_default_id'] ) {
                    if ( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id", array( 'image_id' => $spec_desc_goods[0]['image_default_id'] )) ) {
                        $v2['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    } else {
                        $v2['product']['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];
                    }
                }
            }
            $v['is_afterrec'] = $obj_return_policy->is_order_aftersales($v['order_id']);
        }
        $this->pagedata['orders'] = $aData['data'];

        $arr_args = array();
        $this->pagination($nPage, $aData['pager']['total'], 'afterlist', $arr_args);
        $this->page('wap/afterlist/afterlist.html');

    }

    public function add_aftersales( $order_id )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy          = kernel::service("aftersales.return_policy");
        $arr_settings               = array();

        if ( !isset($obj_return_policy) || !is_object($obj_return_policy) ) {
            $this->afterlist_msg('fail', '售后服务应用不存在！', $url = '');
            return '';
        }

        if ( !$obj_return_policy->get_conf_data($arr_settings) ) {
            $this->afterlist_msg('fail', '售后服务信息没有取到！', $url = '');
            return '';
        }

        if ( !$obj_return_policy->is_order_aftersales($order_id) ) {
            $this->afterlist_msg('fail', '该订单您已经申请过退换货，无退换商品', $url = '');
            return '';
        }

        $products                   = app::get('b2c')->model('products');
        $this->pagedata['order_id'] = $order_id;
        $objOrder                   = $this->app->model('orders');
        $subsdf                     = array( 'order_objects' => array( '*', array( 'order_items' => array( '*', array( ':products' => '*' ) ) ) ) );
        $this->pagedata['order']    = $objOrder->dump($order_id, '*', $subsdf);

        // 校验订单的会员有效性.
        $is_verified = ( $this->_check_verify_member($this->pagedata['order']['member_id']) ) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ( $_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token') . $order_id) && !$is_verified ) {
            $this->afterlist_msg('fail', '订单无效！', $url = '');
            return '';
        }

        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id);

        if ( !$this->pagedata['order'] ) {
            $this->afterlist_msg('fail', '订单无效！', $url = '');
            return '';
        }

        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type     = kernel::servicelist('order_goodstype_operation');
        foreach ( $arr_service_goods_type as $obj_service_goods_type ) {
            $goods_types                                = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[ $goods_types ] = $obj_service_goods_type;
        }

        $objMath      = kernel::single("ectools_math");
        $oImage       = app::get('image')->model('image');
        $oGoods       = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach ( $this->pagedata['order']['order_objects'] as $k => $arrOdr_object ) {
            $index      = 0;
            $index_adj  = 0;
            $index_gift = 0;
            $tmp_array  = array();
            if ( $arrOdr_object['obj_type'] == 'timedbuy' ) {
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ( $arrOdr_object['obj_type'] == 'goods' ) {
                $order_aftersales_products_quantity = $obj_return_policy->order_products_quantity($order_id);
                foreach ( $arrOdr_object['order_items'] as $key => $item ) {
                    if ( $item['item_type'] == 'product' )
                        $item['item_type'] = 'goods';
                    if ( $tmp_array = $arr_service_goods_type_obj[ $item['item_type'] ]->get_aftersales_order_info($item) ) {
                        $tmp_array = (array)$tmp_array;
                        if ( !$tmp_array ) continue;
                        $product_id            = $tmp_array['products']['product_id'];
                        $item['quantity']      = $order_aftersales_products_quantity[ $product_id ];
                        $tmp_array['quantity'] = $order_aftersales_products_quantity[ $product_id ];
                        if ( empty($item['quantity']) ) {
                            continue;
                        }
                        $tmp_array['quantity'] = intval($tmp_array['quantity']);
                        if ( !$order_items[ $product_id ] ) {
                            $tmp_array['arrNum']        = $this->intArray($tmp_array['quantity']);
                            $order_items[ $product_id ] = $tmp_array;
                        } else {
                            $order_items[ $product_id ]['sendnum']  = floatval($objMath->number_plus(array( $order_items[ $product_id ]['sendnum'], $tmp_array['sendnum'] )));
                            $order_items[ $product_id ]['quantity'] = intval(floatval($objMath->number_plus(array( $order_items[ $product_id ]['quantity'], $tmp_array['quantity'] ))));
                            $order_items[ $product_id ]['arrNum']   = $this->intArray($order_items[ $product_id ]['quantity']);
                        }
                        // 货品图片
                        $spec_desc_goods = $oGoods->getList('spec_desc,image_default_id', array( 'goods_id' => $item['goods_id'] ));

                        if ( $item['products']['spec_desc']['spec_private_value_id'] ) {
                            $select_spec_private_value_id = reset($item['products']['spec_desc']['spec_private_value_id']);
                            $spec_desc_goods              = reset($spec_desc_goods[0]['spec_desc']);
                        }
                        if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                            list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                            $order_items[ $product_id ]['thumbnail_pic'] = $default_product_image;
                        } elseif ( $spec_desc_goods[0]['image_default_id'] ) {
                            if ( !$order_items[ $product_id ]['thumbnail_pic'] && !$oImage->getList("image_id", array( 'image_id' => $spec_desc_goods[0]['image_default_id'] )) ) {
                                $order_items[ $product_id ]['thumbnail_pic'] = $imageDefault['S']['default_image'];

                            } else {
                                $order_items[ $product_id ]['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];

                            }
                        } else {
                            $result                                      = $products->getRow('goods_id,spec_desc', array( 'product_id' => $product_id ));
                            $default_image                               = $oGoods->getRow('image_default_id', array( 'goods_id' => $result['goods_id'] ));
                            $order_items[ $product_id ]['thumbnail_pic'] = $default_image['image_default_id'];
                        }
                    }
                }
            } else {
                if ( $tmp_array = $arr_service_goods_type_obj[ $arrOdr_object['obj_type'] ]->get_aftersales_order_info($arrOdr_object) ) {
                    $tmp_array = (array)$tmp_array;
                    if ( !$tmp_array ) continue;
                    foreach ( $tmp_array as $tmp ) {
                        if ( !$order_items[ $tmp['product_id'] ] ) {
                            $tmp['arrNum']                     = $this->intArray($tmp['quantity']);
                            $order_items[ $tmp['product_id'] ] = $tmp;
                        } else {

                            $order_items[ $tmp['product_id'] ]['sendnum']  = floatval($objMath->number_plus(array( $order_items[ $tmp['product_id'] ]['sendnum'], $tmp['sendnum'] )));
                            $order_items[ $tmp['product_id'] ]['nums']     = floatval($objMath->number_plus(array( $order_items[ $tmp['product_id'] ]['nums'], $tmp['nums'] )));
                            $order_items[ $tmp['product_id'] ]['quantity'] = floatval($objMath->number_plus(array( $order_items[ $tmp['product_id'] ]['quantity'], $tmp['quantity'] )));
                            $order_items[ $tmp['product_id'] ]['arrNum']   = $this->intArray($order_items[ $tmp['product_id'] ]['quantity']);
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }

        $this->pagedata['order_id']       = $order_id;
        $this->pagedata['order']['items'] = $order_items;
        $this->pagedata['controller']     = 'afterlist';
        // echo "<pre>";print_r($this->pagedata);exit;
        $this->page('wap/afterlist/afterinfo.html');
    }


    public function return_save()
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy          = kernel::service("aftersales.return_policy");
        $arr_settings               = array();
        //var_dump($obj_return_policy);

        if ( !isset($obj_return_policy) || !is_object($obj_return_policy) ) {
            $this->afterlist_msg('fail', '售后服务应用不存在！', $url = '');
            return '';
        }

        if ( !$obj_return_policy->get_conf_data($arr_settings) ) {
            $this->afterlist_msg('fail', '售后服务信息没有取到！', $url = '');
            return '';
        }

        if ( !$_POST['product_bn'] ) {
            $this->afterlist_msg('fail', '您没有选择商品，请先选择商品！', $url = '');
            return '';
        }

        if ( !$_POST['title'] ) {
            $this->afterlist_msg('fail', '请填写退货理由', $url = '');
            return '';
        }

        $upload_file = "";
        if ( $_FILES['file']['size'] > 314572800 ) {
            $this->afterlist_msg('fail', '上传文件不能超过300M！', $url = '');
            return '';
        }

        if ( $_FILES['file']['name'] != "" ) {
            $type = array( "png", "jpg", "gif", "jpeg", "rar", "zip" );

            if ( !in_array(strtolower($this->fileext($_FILES['file']['name'])), $type) ) {
                $text = implode(",", $type);
                $this->end(false, app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url, false, $_POST['response_json']);
                $this->ajax_callback('error', app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>");
            }

            $mdl_img    = app::get('image')->model('image');
            $image_name = $_FILES['file']['name'];
            $image_id   = $mdl_img->store($_FILES['file']['tmp_name'], null, null, $image_name);
            $mdl_img->rebuild($image_id, array( 'L', 'M', 'S' ));

            if ( isset($_REQUEST['type']) ) {
                $type = $_REQUEST['type'];
            } else {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
        }

        if ( !$_POST['agree'] ) {
            $this->afterlist_msg('fail', '请先查看售后服务须知并且同意', $url = '');
            return '';
        }

        $obj_filter = kernel::single('b2c_site_filter');
        $_POST      = $obj_filter->check_input($_POST);

        $product_data            = array();
        $order_products_quantity = $obj_return_policy->order_products_quantity($_POST['order_id']);
        foreach ( (array)$_POST['product_bn'] as $key => $val ) {
            $item               = array();
            $item['bn']         = $val;
            $item['name']       = $_POST['product_name'][ $key ];
            $item['num']        = intval($_POST['product_nums'][ $key ]);
            $item['price']      = floatval($_POST['product_price'][ $key ]);
            $item['product_id'] = intval($key);
            if ( $order_products_quantity[ $key ] < $item['num'] ) {
                $is_aftersales_status = 'product_num_error';
            }
            $product_data[] = $item;
        }

        if ( !empty($is_aftersales_status) ) {
            $this->afterlist_msg('fail', '您申请退换货的物品大于可退货换数量', $url = '');
            return '';
        }


        $aData['order_id']     = $_POST['order_id'];
        $aData['title']        = $_POST['title'];
        $aData['type']         = $_POST['type'] == 2 ? 2 : 1;
        $aData['add_time']     = time();
        $aData['image_file']   = $image_id;
        $aData['member_id']    = $this->app->member_id;
        $aData['product_data'] = serialize($product_data);
        $aData['content']      = $_POST['content'];
        $aData['status']       = 2;
        $msg                   = "";
        $obj_aftersales        = kernel::service("api.aftersales.request");
        if ( $obj_aftersales && $obj_aftersales->generate($aData, $msg) ) {
            $obj_rpc_request_service = kernel::service('b2c.rpc.send.request');
            if ( $obj_rpc_request_service && method_exists($obj_rpc_request_service, 'rpc_caller_request') ) {
                if ( $obj_rpc_request_service instanceof b2c_api_rpc_request_interface )
                    $obj_rpc_request_service->rpc_caller_request($aData, 'aftersales');
            } else {
                $obj_aftersales->rpc_caller_request($aData);
            }
            $this->afterlist_msg('success', '提交成功', $url = '');
            return '';
        } else {
            $this->afterlist_msg('fail', 'error', $url = '');
            return '';
        }
    }


    function afterrec( $type = 'noarchive', $nPage = 1 )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy          = kernel::service("aftersales.return_policy");
        $arr_settings               = array();

        $filter              = array();
        $filter["member_id"] = $this->app->member_id;
        $obj_return_policy   = kernel::service("aftersales.return_policy");
        $arr_settings        = array();
        if ( !isset($obj_return_policy) || !is_object($obj_return_policy) ) {
            $this->afterlist_msg('fail', '售后服务应用不存在！', $url = '');
            return '';
        }

        if ( !$obj_return_policy->get_conf_data($arr_settings) ) {
            $this->afterlist_msg('fail', '售后服务信息没有取到！', $url = '');
            return '';
        }


        if ( $type == 'archive' ) {
            $this->pagedata['type'] = 'archive';
            $aData                  = $this->get_return_product_list('*', $filter, $nPage);
        } else {
            $this->pagedata['type'] = 'noarchive';
            $aData                  = $obj_return_policy->get_return_product_list('*', $filter, $nPage);
        }

        $oImage       = app::get('image')->model('image');
        $oGoods       = app::get('b2c')->model('goods');
        $products     = app::get('b2c')->model('products');
        $imageDefault = app::get('image')->getConf('image.set');

        foreach ( $aData['data'] as $key => $val ) {
            $aData['data'][ $key ]['product_data'] = unserialize($val['product_data']);
            foreach ( $aData['data'][ $key ]['product_data'] as $gkey => $gval ) {
                $result = $products->getRow('goods_id,spec_desc', array( 'bn' => $gval['bn'] ));
                if ( $result['spec_desc']['spec_private_value_id'] ) {
                    $select_spec_private_value_id = reset($result['spec_desc']['spec_private_value_id']);
                    $spec_desc_goods              = reset($result['spec_desc']);
                }
                if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                    list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                    $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $default_product_image;
                } elseif ( $spec_desc_goods[0]['image_default_id'] ) {
                    if ( !$oImage->getList("image_id", array( 'image_id' => $spec_desc_goods[0]['image_default_id'] )) ) {
                        $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    } else {
                        $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];
                    }
                } else {
                    $default_image                                                   = $oGoods->getRow('image_default_id', array( 'goods_id' => $result['goods_id'] ));
                    $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $default_image['image_default_id'];

                }
                $aData['data'][ $key ]['product_data'][ $gkey ]['link_url'] = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_product', 'act' => 'index', 'arg0' => $aData['data'][ $key ]['product_data']['0']['product_id'] ));
            }
            $aData['data'][ $key ]['comment'] = unserialize($val['comment']);
        }


        if ( isset($aData['data']) && $aData['data'] ) {
            $this->pagedata['return_list'] = $aData['data'];
        }


        $imageDefault                   = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $arrPager                       = $this->get_start($nPage, $aData['total']);
        $this->pagination($nPage, $aData['pager']['total'], 'afterrec', $arr_args);
        $this->pagedata['controller'] = 'afterrec';
        //获取物流公司表数据
        $mdl_b2c_dlycorp            = $this->app->model("dlycorp");
        $this->pagedata["dlycorps"] = $mdl_b2c_dlycorp->getList();

        $this->page('wap/afterlist/afterrec.html');

    }


    function afterrec_info( $return_order_id )
    {
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 )) );
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy          = kernel::service("aftersales.return_policy");
        $arr_settings               = array();

        $filter              = array();
        $filter["member_id"] = $this->app->member_id;
        $obj_return_policy   = kernel::service("aftersales.return_policy");
        $arr_settings        = array();
        if ( !isset($obj_return_policy) || !is_object($obj_return_policy) ) {
            $this->afterlist_msg('fail', '售后服务应用不存在！', $url = '');
            return '';
        }

        if ( !$obj_return_policy->get_conf_data($arr_settings) ) {
            $this->afterlist_msg('fail', '售后服务信息没有取到！', $url = '');
            return '';
        }
        $filter['return_id'] = $return_order_id;

        if ( $type == 'archive' ) {
            $this->pagedata['type'] = 'archive';
            $aData                  = $this->get_return_product_list('*', $filter, $nPage);
        } else {
            $this->pagedata['type'] = 'noarchive';
            $aData                  = $obj_return_policy->get_return_product_list('*', $filter, $nPage);
        }

        $oImage       = app::get('image')->model('image');
        $oGoods       = app::get('b2c')->model('goods');
        $products     = app::get('b2c')->model('products');
        $imageDefault = app::get('image')->getConf('image.set');

        if ( isset($aData['data']) && $aData['data'] ) {
            foreach ( $aData['data'] as $key => $val ) {
                $aData['data'][ $key ]['product_data'] = unserialize($val['product_data']);
                foreach ( $aData['data'][ $key ]['product_data'] as $gkey => $gval ) {
                    $result = $products->getRow('goods_id,spec_desc', array( 'bn' => $gval['bn'] ));
                    if ( $result['spec_desc']['spec_private_value_id'] ) {
                        $select_spec_private_value_id = reset($result['spec_desc']['spec_private_value_id']);
                        $spec_desc_goods              = reset($result['spec_desc']);
                    }

                    if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                        list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                        $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $default_product_image;
                    } elseif ( $spec_desc_goods[0]['image_default_id'] ) {
                        if ( !$oImage->getList("image_id", array( 'image_id' => $spec_desc_goods[0]['image_default_id'] )) ) {
                            $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $imageDefault['S']['default_image'];
                        } else {
                            $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];
                        }
                    } else {
                        $default_image                                                   = $oGoods->getRow('image_default_id', array( 'goods_id' => $result['goods_id'] ));
                        $aData['data'][ $key ]['product_data'][ $gkey ]['thumbnail_pic'] = $default_image['image_default_id'];

                    }
                    $aData['data'][ $key ]['product_data'][ $gkey ]['link_url'] = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_product', 'act' => 'index', 'arg0' => $aData['data'][ $key ]['product_data']['0']['product_id'] ));
                }
                $aData['data'][ $key ]['comment'] = unserialize($val['comment']);
            }
            $this->pagedata['order'] = $aData['data'][0];
        }


        //退款金额
        $order_bills = app::get('ectools')->model('order_bills');
        $refunds = app::get('ectools')->model('refunds');
        $aRefund = $order_bills->getList('*',array('rel_id'=>$this->pagedata['order']['order_id'], 'bill_type'=>'refunds', 'pay_object'=>'order'));

        if ($aRefund)
        {
            foreach ($aRefund as &$str_refund)
            {
                $arr_refunds = $refunds->dump($str_refund['bill_id'], 'pay_name,status,t_payed');
                $str_refund['pay_name'] = $arr_refunds['pay_name'];
                $str_refund['status'] = $arr_refunds['status'];
                $str_refund['t_payed'] = $arr_refunds['t_payed'];
            }
            $this->pagedata['refundInfo'] = $str_refund;
        }



        if ( $this->pagedata['order']["customer_logistics"] ) {
            //获取顾客前台填写的物流信息
            $mdl_aftersales_return_product             = app::get('aftersales')->model('return_product');
            $customer_logistics_text = $mdl_aftersales_return_product->customer_logistics_text($this->pagedata['order']["customer_logistics"]);

            if($customer_logistics_text != ""){
                $this->pagedata['customer_logistics_text'] = str_replace("&nbsp;&nbsp;&nbsp;","<br/>",$customer_logistics_text);
            }
        }


        $imageDefault                   = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $arrPager                       = $this->get_start($nPage, $aData['total']);
        $this->pagination($nPage, $aData['pager']['total'], 'afterrec', $arr_args);
        $this->pagedata['controller'] = 'afterrec';
        $this->page('wap/afterlist/afterinfree.html');

    }

    public function read()
    {
        $this->pagedata['comment'] = app::get('aftersales')->getConf('site.return_product_comment');
        $this->page('wap/afterlist/afterpact.html');

    }

    private function afterlist_msg( $status, $msg, $url = '' )
    {
        if ( empty($url) ) {
            $url = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 ));
        }
        $this->pagedata['status'] = $status;
        $this->pagedata['msg']    = $msg;
        $this->pagedata['url']    = $url;
        $this->page('wap/afterlist/afterwin.html');
    }


    private function intArray( $int = 1 )
    {
        for ( $i = 1; $i <= $int; $i++ ) {
            $return[ $i ] = $i;
        }
        return $return;
    }

    //积分兑换优惠券页面展示
    function coupon_exchange()
    {
        $member_center_url          = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index', 'full' => 1 ));
        $this->path[]               = array( 'title' => app::get('b2c')->_('会员中心'), 'link' => $member_center_url );
        $this->path[]               = array( 'title' => app::get('b2c')->_('积分兑换优惠卷'), 'link' => '#' );
        $GLOBALS['runtime']['path'] = $this->path;
        //获取兑换券列表
        $oExchangeCoupon                    = kernel::single('b2c_coupon_mem');
        $site_point_usage                   = $this->app->getConf('site.point_usage');
        $this->pagedata['site_point_usage'] = ( $site_point_usage == '1' ) ? true : false;
        if ( $aExchange = $oExchangeCoupon->get_list() ) {
            foreach ( $aExchange as $k => $item ) {
                $member_lvs = explode(',', $item['time']['member_lv_ids']);
                if ( !in_array($this->member['member_lv'], (array)$member_lvs) ) {
                    unset($aExchange[ $k ]);
                    continue;
                }
            }
            $this->pagedata['couponList'] = $aExchange;
        }
        $this->pagedata['member_center_url'] = $member_center_url;
        $this->page('wap/member/coupon_exchange.html');
    }

    //积分兑换优惠券 兑换操作
    function exchange()
    {
        $cpnsId              = $_POST["cpns_id"];
        $arr_json_data       = array( "success" => false );
        $coupon_exchange_url = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'coupon_exchange' ));
        //积分设置的用途
        $site_point_usage = app::get('b2c')->getConf('site.point_usage');
        if ( $site_point_usage != '1' ) {
            $arr_json_data["message"] = '积分只用于抵扣，不能兑换';
            echo json_encode($arr_json_data);
            exit;
        }
        if ( !$cpnsId ) {
            $arr_json_data["message"] = '参数错误';
            echo json_encode($arr_json_data);
            exit;
        }
        $oExchangeCoupon = kernel::single('b2c_coupon_mem');
        $memberId        = intval($this->app->member_id);//会员id号
        if ( $memberId ) {
            $cur_coupon_nums = $this->app->model('member_coupon')->count(array( 'cpns_id' => $cpnsId, 'member_id' => $memberId, 'source' => 1 ));
            $coupons         = $this->app->model('coupons');
            $cur_coupon      = $coupons->dump($cpnsId);
            if ( $cur_coupon['cpns_max_num'] > 0 ) {  //兼容老数据处理老数据还是无限制兑换
                if ( $cur_coupon_nums >= $cur_coupon['cpns_max_num'] ) {
                    $arr_json_data["message"] = '您的兑换次数已达上限！';
                    echo json_encode($arr_json_data);
                    exit;
                }
            }

            $obj_point_common = kernel::single('pointprofessional_point_common');
            $real_point_get   = $obj_point_common->check_used_point_get($memberId);
            if ( !$real_point_get["rs"] ) {
                $arr_json_data["message"] = '获取可用积分失败！';
                echo json_encode($arr_json_data);
                exit;
            }

            if ( $real_point_get['real_point'] < $cur_coupon['cpns_point'] ) {
                $arr_json_data["message"] = '您的积分不足！';
                echo json_encode($arr_json_data);
                exit;
            }

            if ( $oExchangeCoupon->exchange($cpnsId, $memberId, $real_point_get['real_point'], $params) ) {
                $cpns_point   = $params['cpns_point'];
                $member_point = $this->app->model('member_point');
                if ( $obj_point_common->point_change_action($this->member['member_id'], -$cpns_point, $msg, 'exchange_coupon', 2, $memberId, $memberId, 'exchange', false) ) {
                    $change_nums = $cur_coupon['cpns_max_num'] - $cur_coupon_nums - 1;
                    $coupon_url  = $this->gen_url(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'coupon' ));
                    if ( $cur_coupon['cpns_max_num'] > 0 ) {
                        $arr_json_data["message"] = '兑换成功,您还可以兑换' . $change_nums . '张';
                    } else {
                        $arr_json_data["message"] = '兑换成功';
                    }
                    $arr_json_data['success'] = true;
                    echo json_encode($arr_json_data);
                    exit;
                } else {
                    $oExchangeCoupon->exchange_delete($params);
                    $arr_json_data["message"] = $msg;
                    echo json_encode($arr_json_data);
                    exit;
                }
            }
        } else {
            $arr_json_data["message"] = '没有登录';
            echo json_encode($arr_json_data);
            exit;
        }
        $arr_json_data["message"] = '积分不足或兑换购物券无效';
        echo json_encode($arr_json_data);
        exit;
    }

    //申请退款列表
    public function refundlist( $nPage = 1 )
    {
        $mdl_b2c_refund_apply = $this->app->model('refund_apply');
        $aData                = $mdl_b2c_refund_apply->fetchByMember($this->app->member_id, $nPage);
        //统一获取相关的order_id 获得订单相关信息
        $arr_order_ids = array();
        foreach ( $aData["data"] as $var_data ) {
            if ( !in_array($var_data["order_id"], $arr_order_ids) ) {
                $arr_order_ids[] = $var_data["order_id"];
            }
        }
        $mdl_b2c_orders = $this->app->model('orders');
        $rs_orders      = $mdl_b2c_orders->getList('*', array( "order_id|in" => $arr_order_ids ));
        $subsdf         = array( 'order_objects' => array( '*', array( 'order_items' => array( '*', array( ':products' => '*' ) ) ) ) );
        foreach ( $rs_orders as &$arr_order ) {
            $arr_order = $mdl_b2c_orders->dump($arr_order['order_id'], '*', $subsdf);
        }
        $orders["data"] = $rs_orders;
        $this->get_order_details($orders, 'member_orders');
        $oImage       = app::get('image')->model('image');
        $oGoods       = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach ( $orders['data'] as $k => &$v ) {
            $order_payed = kernel::single('b2c_order_pay')->check_payed($v['order_id']);
            if ( $order_payed == $v['total_amount'] ) {
                $v['is_pay'] = 1;
            } else {
                $v['is_pay'] = 0;
            }
            foreach ( $v['goods_items'] as $k2 => &$v2 ) {
                $spec_desc_goods              = $oGoods->getList('spec_desc', array( 'goods_id' => $v2['product']['goods_id'] ));
                $select_spec_private_value_id = reset($v2['product']['products']['spec_desc']['spec_private_value_id']);
                $spec_desc_goods              = reset($spec_desc_goods[0]['spec_desc']);
                if ( $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images'] ) {
                    list($default_product_image) = explode(',', $spec_desc_goods[ $select_spec_private_value_id ]['spec_goods_images']);
                    $v2['product']['thumbnail_pic'] = $default_product_image;
                } else {
                    if ( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id", array( 'image_id' => $v['image_default_id'] )) ) {
                        $v2['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    }
                }
            }
        }
        //做个order_id为key的关系数组
        $rl_order_id_order_info = array();
        foreach ( $orders["data"] as $var_order ) {
            $rl_order_id_order_info[ $var_order["order_id"] ] = $var_order;
        }
        //压入要order_info用来显示在整体信息下方
        foreach ( $aData['data'] as &$var_adata ) {
            $var_adata["order_info"] = $rl_order_id_order_info[ $var_adata["order_id"] ];
        }
        unset($var_adata);
        $this->pagedata['refund'] = $aData['data'];
        $this->pagination($nPage, $aData['pager']['total'], 'refundlist');
        $this->page('wap/member/refundlist.html');
    }

    /*
     * 申请退款详细页
     * @params int $refund_apply_bn 退款申请单号
     * */
    function refund_detail( $refund_apply_bn )
    {
        if ( !$refund_apply_bn ) {
            $this->begin(array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'index' ));
            $this->end(false, app::get('b2c')->_('退款申请单号不能为空！'));
        }
        //获取退款申请信息
        $mdl_b2c_refund_apply                = $this->app->model('refund_apply');
        $this->pagedata["refund_apply_info"] = $mdl_b2c_refund_apply->dump(array( "refund_apply_bn" => $refund_apply_bn ));
        //获取refunds_reason_text
        $this->pagedata["refund_apply_info"]["refunds_reason"] = $mdl_b2c_refund_apply->get_refunds_reason_text($this->pagedata["refund_apply_info"]["refunds_reason"]);
        //获取退款申请日志
        $mdl_b2c_order_log                  = $this->app->model('order_log');
        $this->pagedata["refund_apply_log"] = $mdl_b2c_order_log->getList("*", array( "rel_id" => $refund_apply_bn, "bill_type" => "refund_apply", "behavior" => "refunds" ), 0, -1, 'log_id asc');
        $this->page('wap/member/refund_detail.html');
    }

    //售前申请退款操作
    public function do_refund_apply()
    {
        //默认不成功
        $arr_json_data = array(
            "success" => false,
        );
        $order_id      = $_POST["order_id"];
        if ( !$order_id ) {
            $arr_json_data["err_msg"] = "订单id不存在";
            echo json_encode($arr_json_data);
            exit;
        }
        $refund_apply_reason = $_POST["refund_apply_reason"];
        if ( !$refund_apply_reason ) {
            $arr_json_data["err_msg"] = "请选择退款理由";
            echo json_encode($arr_json_data);
            exit;
        }
        //检查订单是否符合要求：未发货 已支付
        $mdl_order  = $this->app->model('orders');
        $order_info = $mdl_order->dump(array( "order_id" => $order_id ), "pay_status,ship_status,member_id,final_amount");
        if ( empty($order_info) ) {
            $arr_json_data["err_msg"] = "此订单不存在";
            echo json_encode($arr_json_data);
            exit;
        }
        if ( $order_info["cur_amount"] == 0 ) {
            $arr_json_data["err_msg"] = "0元订单不能申请退款申请";
            echo json_encode($arr_json_data);
            exit;
        }
        //是否有售后申请记录
        $mdl_aftersales_return_product = app::get('aftersales')->model('return_product');
        $rs_aftersales_return_product  = $mdl_aftersales_return_product->dump(array( "order_id" => $order_id ));
        //判断是否有未操作过的此订单的退款申请记录
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $rs_refund_apply      = $mdl_b2c_refund_apply->dump(array( "order_id" => $order_id, "status" => "0" ));
        //满足已支付 未发货 没有售后退换货记录的 做售前退款操作
        if ( $order_info["pay_status"] == "1" && $order_info["ship_status"] == "0" && empty($rs_aftersales_return_product) && empty($rs_refund_apply) ) {
            $refund_apply_bn = date("YmdHis", time()) . str_pad(rand(1, 999), 4, '0', STR_PAD_LEFT);
            $current_time    = time();
            $request_arr     = array(
                "order_id"        => $order_id,
                "member_id"       => $order_info["member_id"],
                "refund_apply_bn" => $refund_apply_bn,
                "refunds_reason"  => $refund_apply_reason,
                "money"           => $order_info["cur_amount"],
                "current_time"    => $current_time,
            );
            //如绑定把退款申请单打到oms
            $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            $obj_apiv->rpc_caller_request($request_arr, 'orderrefund');
            //生成售前退款记录
            $insert_arr = array(
                "refund_apply_bn" => $refund_apply_bn,
                "order_id"        => $order_id,
                "member_id"       => $order_info["member_id"],
                "money"           => $order_info["cur_amount"],
                "refunds_reason"  => $refund_apply_reason,
                "create_time"     => $current_time,
                "last_modified"   => $current_time,
            );
            if ( $mdl_b2c_refund_apply->insert($insert_arr) ) {
                //更新订单支付状态为退款申请中
                $mdl_order->update(array( 'pay_status' => '6' ), array( 'order_id' => $order_id ));
                $msg                      = "退款申请成功";
                $arr_json_data["success"] = true;
                $arr_json_data["suc_msg"] = $msg;
            } else {
                $msg                      = "退款申请失败";
                $arr_json_data["err_msg"] = $msg;
            }
            $mdl_b2c_refund_apply->saveOrderLog($refund_apply_bn, $msg);
            echo json_encode($arr_json_data);
            exit;
        } else {
            $arr_json_data["err_msg"] = "不满足售前退款的条件";
            echo json_encode($arr_json_data);
            exit;
        }
    }

    //售后前台退换货申请通过审核后保存填写的物流信息
    public function save_logistics_info()
    {
        //默认不成功
        $arr_json_data = array(
            "success" => false,
        );
        if ( !$_POST["logistics_company_name"] ) {
            $arr_json_data["err_msg"] = "请选择物流公司";
            echo json_encode($arr_json_data);
            exit;
        }
        if ( !$_POST["logistics_no"] ) {
            $arr_json_data["err_msg"] = "请填写物流单号";
            echo json_encode($arr_json_data);
            exit;
        }

        if ( !$_POST["logistics_mobile"] ) {
            $arr_json_data["err_msg"] = "请填写收货手机号";
            echo json_encode($arr_json_data);
            exit;
            exit;
        }
        if ( !$_POST["logistics_address"] ) {
            $arr_json_data["err_msg"] = "请填写收货地址";
            echo json_encode($arr_json_data);
            exit;
        }

        if ( $_POST["afterrec_type"] == "archive" ) {
            $mdl_aftersales_return_product = app::get('aftersales')->model('archive_return_product');
        } else {
            $mdl_aftersales_return_product = app::get('aftersales')->model('return_product');
        }

        $return_info = $mdl_aftersales_return_product->dump(array( "return_id" => $_POST["return_id"] ));
        if ( empty($return_info) ) {
            $arr_json_data["err_msg"] = "不存在此退换货数据";
            echo json_encode($arr_json_data);
            exit;
        }

        //请求接口处理
        $api_data = array(
            "return_id"              => $_POST['return_id'],
            "order_id"               => $_POST['order_id'],
            "logistics_company_name" => $_POST['logistics_company_name'],
            "logistics_no"           => $_POST['logistics_no']
        );
        $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
        $obj_apiv->rpc_caller_request($api_data, 'aftersalelogisticsupdate');

        //本地保存填写的信息 用于后台展示
        $arr_customer_logistics = array(
            "物流公司"  => $_POST["logistics_company_name"],
            "物流单号"  => $_POST["logistics_no"],
            "收货手机号" => $_POST["logistics_mobile"],
            "收货地址"  => $_POST["logistics_address"],
        );
        $result                 = $mdl_aftersales_return_product->update(array( "customer_logistics" => json_encode($arr_customer_logistics) ), array( "return_id" => $_POST["return_id"] ));
        if ( $result ) {
            $arr_json_data["success"] = true;
            $arr_json_data["suc_msg"] = "保存成功";
            echo json_encode($arr_json_data);
            exit;
        } else {
            $arr_json_data["err_msg"] = "保存失败";
            echo json_encode($arr_json_data);
            exit;
        }
    }


    function history()
    {
        $this->page('wap/member/history.html');
    }

    public function checkin(){
        $this->page('wap/member/checkin.html');
    }

    public function getCheckInList(){
        $memberId        = intval($this->app->member_id);
        $objSignIn = kernel::single('b2c_member_signin');
        $checkSignIn = [];
        if($dataSignIn = $objSignIn->signList($memberId)){
            foreach ($dataSignIn as $k=>$v){
                $checkSignIn[] = date('Ymd',$v['signin_time']);
            }

            echo json_encode($checkSignIn);
            exit;
        }
        return '';

    }



}
