<?php

namespace app\controllers;
use app\controllers\CommonController;
use app\models\Pay;
use Yii;

class PayController extends CommonController
{
    public $enableCsrfValidation = false;//关闭csrf
    
    //先进行的异步返回 更改字段
    //对order表中该订单的status、支付宝交易号、支付宝回传回来的字段进行保存和更改
    public function actionNotify()
    {
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if (Pay::notify($post)) {
                echo "success";
                exit;
            }
            echo "fail";
            exit;
        }
    }

    //后进行的同步返回 页面显示支付成功或者失败
    public function actionReturn()
    {
        $this->layout = 'layout1';
        
        $status = Yii::$app->request->get('trade_status');
        if ($status == 'TRADE_SUCCESS') {
            $s = 'ok';
        } else {
            $s = 'no';
        }
        return $this->render("status", ['status' => $s]);
    }
}





