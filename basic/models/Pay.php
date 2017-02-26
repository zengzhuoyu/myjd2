<?php
namespace app\models;

use app\models\Order;
use app\models\OrderDetail;
use app\models\Product;

class Pay{
    public static function alipay($orderid)
    {
        $amount = Order::find()->where('orderid = :oid', [':oid' => $orderid])->one()->amount;
        if (!empty($amount)) {
            $alipay = new \AlipayPay();
            $giftname = "京东商城";//商品名称
            $data = OrderDetail::find()->where('orderid = :oid', [':oid' => $orderid])->all();
            $body = "";
            foreach($data as $pro) {
                $body .= Product::find()->where('productid = :pid', [':pid' => $pro['productid']])->one()->title . " - ";
            }
            $body .= "等商品";
            $showUrl = "http://myjd.local";
            $html = $alipay->requestPay($orderid, $giftname, $amount, $body, $showUrl);
            echo $html;//返回的是什么？
        }//为空的情况没有返回?


    }

    public static function notify($data)
    {
        $alipay = new \AlipayPay();
        $verify_result = $alipay->verifyNotify();
        if ($verify_result) {

            $out_trade_no = $data['extra_common_param'];//订单id
            $trade_no = $data['trade_no'];//支付宝交易号
            $trade_status = $data['trade_status'];
            $status = Order::PAYFAILED;
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                $status = Order::PAYSUCCESS;
                $order_info = Order::find()->where('orderid = :oid', [':oid' => $out_trade_no])->one();
                if (!$order_info) {
                    return false;
                }
                if ($order_info->status == Order::CHECKORDER) {//需要json格式保存tradeext
                    Order::updateAll(['status' => $status, 'tradeno' => $trade_no, 'tradeext' => json_encode($data)], 'orderid = :oid', [':oid' => $order_info->orderid]);
                } else {
                    return false;
                }
            }

            return true;

        } else {
            return false;
        }
    }






}
