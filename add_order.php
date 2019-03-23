<?php 
/**
 * 添加订单 接口
 *
 * @license  jiqingchuan  2017年9月23日
 */
session_start();
include_once("common/common.php");
include_once("common/config.php");
include_once("common/lib_main.php");
include_once("common/lib_order.php");
include_once("common/lib_user.php");
$result=array('error'=>0,'errmsg'=>"");


$arr_token=get_system_token();
if($arr_token['error']==0)
{
	$token=	$arr_token['token'];
}
else
{//token 获取失败	
	$result['error']=91;
	$result['errmsg']="token获取失败";
	echo return_json_encode($result);//die(json_encode($result));
	exit;

}


$arr_tradeid=get_system_tradeid($token);
if($arr_tradeid['error']==0)
{
	$tradeid=$arr_tradeid['tradeid'];
}
else
{
	//token 获取失败
	$result['error']=92;
	$result['errmsg']="tradeid获取失败";
	echo return_json_encode($result);//die(json_encode($result));
	exit;	
}

$where="";
$where2="";
$r_order_bn=isset($_REQUEST['order_bn'])?trim($_REQUEST['order_bn']):"";
if($r_order_bn)
{
	$where2=" and o.order_bn='".$r_order_bn."'";
}

$where.=" and o.status='active' ";//活动订单
//已付款订单（款到发货）、货到付款
$where.=" and (((o.pay_status='1' or o.pay_status='4') and o.is_cod='false') or o.is_cod='true')";

//自建商城、第三方商城（京东、淘宝、天猫、小红书）
$arr_shop=get_shop_list();
$str_shop=implode("','",$arr_shop);
$where.=" and o.shop_id IN ('".$str_shop."')";


$sql="select o.*,m.shop_member_id from sdb_ome_orders as o ".
	 " LEFT JOIN sdb_ome_shop_members as m ON m.shop_id=o.shop_id and m.member_id=o.member_id ".
	 "where 1=1 and o.is_u8='0' and o.is_fail='false' ".$where.$where2." order by o.u8_time asc,o.order_id asc";//
$order=$db->getRow($sql);


$order_info=array();
if(!empty($order))
{
	$giftadvance=$order['giftadvance'];
	if($giftadvance==0.000)
	{
		//自建商城
		if($order['shop_id']=='5345ee50bbdafcf8962d6cc3eee57c13')
		{
			$sql="select money from sdb_b2c_member_giftadvance where order_id='".$order['order_bn']."' limit 1";
			$member_giftadvance=$db_b2c->getRow($sql);
			if(!empty($member_giftadvance))
			{
				$giftadvance=$member_giftadvance['money'];
			}
			$sql2="update sdb_ome_orders set giftadvance=".$giftadvance." where order_bn='".$order['order_bn']."'";
			$db->query($sql2);
		}	
	}
	
	//已付款订单（款到发货）、货到付款
	if($order['is_cod']=='false' && $order['pay_status']!='1' && $order['pay_status']!='4')
	{
		change_to_u8_time($order['order_id']);
		$result['error']=72;
		$result['errmsg']="该订单是款到发货订单，未付款";
		echo return_json_encode($result);//die(json_encode($result));
		exit;
	}
	$is_order_yhj=change_order_yhj($order['order_id']);
	if($is_order_yhj)
	{
		$result['error']=73;
		$result['errmsg']="该订单含有优惠卷商品";
		echo return_json_encode($result);//die(json_encode($result));
		exit;
	}
	
	//判断使用哪个帐套及店铺
	$sys_u8=get_sys_u8($order);
	$ds_sequence=$sys_u8['ds_sequence'];//帐套
	$result_customer=get_customer($sys_u8['shop']['customer_id'],$ds_sequence,$token);//客户信息

	if($result_customer['errcode']==0)
	{
		$customer=$result_customer['customer'];
		
		$order_info["cShopCode"]=$sys_u8['shop']['id'];//店铺编码"01"{}
		$order_info["title"]=$sys_u8['shop']['id'];//{}
		$order_info["tid"]=$order['order_bn'];//交易编号"984470431496938",
		$order_info["buyer_nick"]=empty($order['shop_member_id'])?$order['ship_name']:$order['shop_member_id'];//买家会员号"加菲猫m",
		$order_info["receiver_name"]=$order['ship_name'];//"陈洁",
		$arr=explode(':', $order['ship_area']);
		$brr=explode('/', $arr[1]);
		$order_info["receiver_state"]=$brr[0];//"浙江省",
		$order_info["receiver_city"]=$brr[1];//"杭州市",
		$order_info["receiver_district"]=empty($brr[2])?$brr[1]:$brr[2];//"拱墅区",
		$order_info["receiver_address"]=$order['ship_addr'];//"北清路68号用友软件园",
		$order_info["receiver_zip"]=empty($order['ship_zip'])?'210000':$order['ship_zip'];//"310011",
		$order_info["receiver_mobile"]=empty($order['ship_mobile'])?$order['ship_tel']:$order['ship_mobile'];//"*35057*6665",
		$order_info["isInvoice"]=1;//是否开票(0=不开票;1=开票),****************************************
		$order_info["buyer_email"]="";//买家邮件地址"13505716665@139.com",
		$order_info["created"]=date('Y-m-d H:i:s',$order['createtime']);//交易时间"2014-12-0400]=;//00]=;//00",
		$order_info["pay_time"]=date('Y-m-d H:i:s',$order['paytime']);//付款时间"2014-12-0500]=;//00]=;//00"
		$order_info["invoice_name"]=$order['tax_company'];//发票抬头"陈洁",**************************************
		$order_info["cDepCode"]=$customer['super_dept'];//"500301";//部门编码"0304",{}
		$order_info["cPersonName"]=$customer['spec_operator_name'];//"赵红80510";//业务员"何彤",{}
		$order_info["cSTName"]="";//销售类型"电子商务",电商06 *****************
		$order_info["cSSName"]="中国银行";//结算方式"银行转账",*************************
		$order_info["cShipMode"]="";//发货模式"EnterpriseDeliver",***************
		$order_info["cExpressCoCode"]="02";//物流公司
		$mark_text=unserialize($order['mark_text']);//卖
		$custom_mark=unserialize($order['custom_mark']);//客户
		$str_text="";
		if($custom_mark)
		{
			$str_text.="客户：";
			foreach($custom_mark as $kc=>$vc)
			{
				$str_text.=$vc['op_time'].'-'.$vc['op_content'].";";
			}	
		}
		if($mark_text)
		{
			$str_text.="商家：";
			foreach($mark_text as $km=>$vm)
			{
				$str_text.=$vm['op_time'].'-'.$vm['op_content'].";";
			}	
		}
		$order_info["seller_memo"]=$str_text;//备注
		
		$order_del_goods=array();
		if($order['pay_status']=='4')
		{//部分退款处理
			$order_del_goods=get_order_del_goods($order['order_id']);
			if(empty($order_del_goods))
			{
				change_to_u8_status($order['order_id'],9,"订单部分退款，无匹配退款商品");
				$result['error']=82;
				$result['errmsg']="订单".$order['order_bn']."部分退款，无匹配退款商品";
				echo return_json_encode($result);//die(json_encode($result));
				exit;
			}
		}
		//print_r($order_del_goods);exit;
		
		$goods_list=array();
		//赠品
		$sql2="select i.* from sdb_ome_order_items as i LEFT JOIN sdb_ome_order_objects AS o ON o.obj_id=i.obj_id where i.item_type='gift' and i.delete='false' and i.order_id=".$order['order_id']." group by i.bn";//and o.oid!=0
		$order_goods01=$db->getAll($sql2);
		
		//商品
		$sql2="select i.* from sdb_ome_order_items as i LEFT JOIN sdb_ome_order_objects AS o ON o.obj_id=i.obj_id where i.item_type!='gift' and i.item_type!='pkg' and i.delete='false' and i.order_id=".$order['order_id']." ";//and o.oid!=0
		$order_goods02=$db->getAll($sql2);
		$order_goods1=array_merge($order_goods01,$order_goods02);
		
		
		//捆绑
		$sql2="select * from sdb_ome_order_items as i where i.item_type='pkg' and i.delete='false' and i.order_id=".$order['order_id'];
		$order_goods2=$db->getAll($sql2);
		if(!empty($order_goods2))
		{
			foreach($order_goods2 as $k2=>$v2)
			{
				$sql="select * from sdb_ome_order_objects where obj_id=".$v2['obj_id'];
				$obj=$db->getRow($sql);
				
				//捆绑商品明细信息
				$sql="select sum(pp.pkgprice*pp.pkgnum) as p_price,count(*) as p_num from sdb_omepkg_pkg_product as pp ".
					 "LEFT JOIN sdb_omepkg_pkg_goods AS pg ON pg.goods_id=pp.goods_id ".
					 "where pg.pkg_bn='".$obj['bn']."'";
				$arr_p=$db->getRow($sql);
				//print_r($arr_p);echo "1111<hr>";
				if($arr_p['p_num']==1)
				{
					$order_goods2[$k2]['price']=$obj['price']*$obj['quantity']/$v2['nums'];
					$order_goods2[$k2]['pmt_price']=$obj['pmt_price'];
					$order_goods2[$k2]['sale_price']=$obj['sale_price'];
					$order_goods2[$k2]['amount']=$obj['amount'];
					$order_goods2[$k2]['divide_order_fee']=$obj['divide_order_fee'];
					$order_goods2[$k2]['part_mjz_discount']=$obj['part_mjz_discount'];
				}
				else
				{
					//获取该商品在捆绑商品中对应的商品单价
					$sql4="select pp.* from sdb_omepkg_pkg_product as pp ".
						  "LEFT JOIN sdb_omepkg_pkg_goods AS pg ON pg.goods_id=pp.goods_id ".
					 	  "where pg.pkg_bn='".$obj['bn']."' and pp.bn='".$v2['bn']."' limit 1";
					$p_goods=$db->getRow($sql4);//print_r($p_goods);echo "2222<hr>";
					
					$order_goods2[$k2]['price']=$p_goods['pkgprice'];
					$order_goods2[$k2]['amount']=$p_goods['pkgprice']*$v2['nums'];
					$order_goods2[$k2]['pmt_price']=empty($arr_p['p_price'])?0:$obj['pmt_price']*$order_goods2[$k2]['amount']/$arr_p['p_price']/$obj['quantity'];
					$order_goods2[$k2]['sale_price']=$order_goods2[$k2]['amount']-$order_goods2[$k2]['pmt_price'];
					$order_goods2[$k2]['divide_order_fee']=empty($arr_p['p_price'])?0:$obj['divide_order_fee']*$order_goods2[$k2]['amount']/$arr_p['p_price']/$obj['quantity'];
					$order_goods2[$k2]['part_mjz_discount']=empty($arr_p['p_price'])?0:$obj['part_mjz_discount']*$order_goods2[$k2]['amount']/$arr_p['p_price']/$obj['quantity'];
					
				}
			}
			$order_goods=array_merge($order_goods1,$order_goods2);
		}
		else
		{
			$order_goods=$order_goods1;
		}
		
		//print_r($order_goods01);print_r($order_goods02);print_r($order_goods2);
		if($order_goods)
		{
			
			$pmt_order=change_order_dis($order,$order_goods,$giftadvance);
			
			foreach($order_goods as $key=>$goods)
			{
				$goods_list[$key]["num_iid"]=$goods['item_id'];//商品数字ID"39890984132",
				$goods_list[$key]["sku_id"]="";//商品Sku的id"72971108530",
				$goods_list[$key]["outer_sku_id"]="";//外部网店自己定义的Sku编号"11010612",
				$goods_list[$key]["sku_properties_name"]="";//SKU值"颜色分类]=;//黄色",
				$goods_list[$key]["cItemCode"]=$goods['bn'];//商品编码"000000U1010001",
				$goods_list[$key]["cItemName"]=$goods['name'];//商品名称"一体化机",

				$goods_list[$key]["price"]=$goods['price'];//empty($goods['divide_order_fee'])?$goods['price']:$goods['divide_order_fee'];//商品价格"799",
				$goods_list[$key]["num"]=$goods['nums'];//购买数量"1",
				if(isset($pmt_order[$goods['item_id']]))
				{
					$part_mjz_discount=isset($pmt_order[$goods['item_id']]['part_mjz_discount'])?$pmt_order[$goods['item_id']]['part_mjz_discount']:0;
				}
				else
				{
					$part_mjz_discount=0;
				}
				$discount_fee=$goods['pmt_price']+$goods['part_mjz_discount']+$part_mjz_discount;//$pmt_order[$goods['item_id']]['part_mjz_discount'];
				
				$goods_list[$key]["discount_fee"]=$discount_fee;//订单优惠金额"405",
				$goods_list[$key]["cWhCode"]=$sys_u8['shop']['ck_num'];//发货仓库"50"{}
			}
			
			//去除退款商品
			if(!empty($order_del_goods))
			{
				foreach($goods_list as $key1=>$goods)
				{
					if($order_del_goods[$goods['cItemCode']])
					{
						$new_num=$goods['num']-$order_del_goods[$goods['cItemCode']]['num'];
						if($new_num>0)
						{
							$goods_list[$key1]['num']=$new_num;
						}
						else
						{
							unset($goods_list[$key1]);	
						}
					}
				}
				$goods_list=array_values($goods_list);
			}
			
			if($order['cost_freight']>0)
			{
				$arr_yunfei["num_iid"]=99;//商品数字ID"39890984132",
				$arr_yunfei["sku_id"]="";//商品Sku的id"72971108530",
				$arr_yunfei["outer_sku_id"]="";//外部网店自己定义的Sku编号"11010612",
				$arr_yunfei["sku_properties_name"]="";//SKU值"颜色分类]=;//黄色",
				$arr_yunfei["cItemCode"]='YF-001';//商品编码"000000U1010001",
				$arr_yunfei["cItemName"]="";//商品名称"一体化机",

				$arr_yunfei["price"]=0;//empty($goods['divide_order_fee'])?$goods['price']:$goods['divide_order_fee'];//商品价格"799",
				$arr_yunfei["num"]=0;//购买数量"1",
				$arr_yunfei["discount_fee"]=0;//订单优惠金额"405",
				$arr_yunfei["isPostFee"]=1;//是否运费
				$arr_yunfei["post_fee"]=$order['cost_freight'];
				$arr_yunfei["cWhCode"]="";//发货仓库"50"{}
				$goods_list[]=$arr_yunfei;
			}	
		}
		
		$order_info["entry"]=$goods_list;//print_r($order_info);exit;
		$str="订单号:".$order['order_bn']."<>".json_encode($order_info);
		set_sys_log(0,$str,"cron");
		
		$result_trade=add_eb_trade($order_info,$token,$ds_sequence,$tradeid);
		
		if(isset($result_trade['errcode']) && $result_trade['errcode']==0)
		{
			change_to_u8_status($order['order_id'],1,$result_trade["errmsg"]);
			$result['errmsg']="订单".$order['order_bn']."同步成功";
		}
		else
		{
			if($result_trade["errmsg"])
			{
				change_to_u8_status($order['order_id'],9,$result_trade["errmsg"].$result_trade["url"]);
			}
			else
			{
				change_to_u8_status($order['order_id'],8,$result_trade["errmsg"].$result_trade["url"]);
			}
			$result['error']=81;
			$result['errmsg']="订单".$order['order_bn']."同步失败，上传订单失败：".$result_trade['errmsg'].$result_trade["url"];
		}
		
	}
	else
	{
		change_to_u8_status($order['order_id'],9,$result_customer["errmsg"]);
		$result['error']=82;
		$result['errmsg']="订单".$order['order_bn']."同步失败，客户信息获取失败：".$result_customer['errmsg'];
	}
	echo return_json_encode($result);//die(json_encode($result));
	exit;
	
	
}
else
{
	$msg="";
	if($where2!='')
	{
		$sql2="select o.*,m.shop_member_id from sdb_ome_orders as o ".
	 		 " LEFT JOIN sdb_ome_shop_members as m ON m.shop_id=o.shop_id and m.member_id=o.member_id ".
	 		 "where 1=1 ".$where2;//
		$order2=$db->getRow($sql2);
	
		if($order2['is_fail']=='true')
		{//o.is_fail='false'  是否失败订单
			$msg="该订单不是有效订单";
		}
		elseif($order2['is_u8']!=0)
		{//同步状态
			$msg="该订单同步已处理：当前状态".$u8_status[$order2['is_u8']];
		}
		
		
	}

	$result['error']=93;
	$result['errmsg']="暂无可处理订单-".$msg;
	echo return_json_encode($result);//die(json_encode($result));
	exit;	
}

exit;


//订单促销金额分权
function change_order_dis($order,$order_goods,$giftadvance)
{
	$shop=array();
	$pmt_order=array();
	$shop[]='a3fab1b3ac1a81b56c73a2389f752c29';//京东
	$shop[]='5345ee50bbdafcf8962d6cc3eee57c13';//自建
	$part_mjz_discount=0;
	//判断是否该店铺订单优惠是否需要拆分
	$isin = in_array($order['shop_id'],$shop);
	$isin=1;
	if($isin)
	{//处理
		$order['pmt_order']=$order['pmt_order']+$giftadvance-$order['discount'];
		if($order['pmt_order']!=0)
		{
			$order_price=0;
			$have_pmt_price=0;
			foreach($order_goods as $key=>$goods)
			{
				
				$goods_price=$goods['price']*$goods['nums']-$goods['pmt_price']-$goods['part_mjz_discount'];
				$have_pmt_price=$have_pmt_price+$goods['part_mjz_discount'];
				if($goods_price>0)
				{
					$pmt_order[$goods['item_id']]['goods_price']=$goods_price;
					$order_price+=$goods_price;
				}
			}//echo $order_price."<br>";print_r($pmt_order);echo $order['pmt_order']."<>";echo $have_pmt_price;
			if($order['pmt_order']>$have_pmt_price)
			{
				$del_pmt_price=$order['pmt_order']-$have_pmt_price;
				if($pmt_order)
				{
					foreach($pmt_order as $k=>$v)
					{
						$part_mjz_discount=$del_pmt_price*$v['goods_price']/$order_price;
						$pmt_order[$k]['part_mjz_discount']=$part_mjz_discount;
						
					}
				}
			}
		}	
	}
	return $pmt_order;
	
}



//获取部分退款订单的退款商品
function get_order_del_goods($order_id)
{
	$order_del_goods=array();
	$sql="select * from sdb_ome_refund_apply where order_id=".$order_id;
	$refund_apply=$GLOBALS['db']->getRow($sql);//print_r($refund_apply);
	if(!empty($refund_apply))
	{
		$json_product_data=$refund_apply['product_data'];
		$product_data=unserialize($json_product_data);//print_r($product_data);
		if(!empty($product_data))
		{
			foreach($product_data as $key=>$value)
			{
				if(!empty($value['bn']))
				{
					$order_del_goods[$value['bn']]['bn']=$value['bn'];
					$order_del_goods[$value['bn']]['num']=$value['num'];
					$order_del_goods[$value['bn']]['price']=$value['price'];
				}
			}	
		}
	}
	return $order_del_goods;

}




















    

?>