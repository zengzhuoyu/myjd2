<?php

namespace app\controllers;

use yii\web\Controller;

class OrderController extends Controller
{
	//前台用户订单中心页面
    public function actionIndex()
    {
        $this->layout = 'layout2';
        
        return $this->render('index');
    }

    //前台收银台页面
    public function actionCheck()
    {
        $this->layout = 'layout1';
        
        return $this->render('check');
    }
}
