<?php

namespace app\controllers;

use yii\web\Controller;

class CartController extends Controller
{
	//前台购物车页面
    public function actionIndex()
    {
        $this->layout = 'layout1';
        
        return $this->render('index');
    }
}
