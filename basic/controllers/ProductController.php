<?php

namespace app\controllers;

use yii\web\Controller;

class ProductController extends Controller
{
	//前台商品分类页面
    public function actionIndex()
    {
        $this->layout = 'layout2';
        
        return $this->render('index');
    }

    //前台商品详情页面
    public function actionDetail()
    {
        $this->layout = 'layout2';
        
        return $this->render('detail');
    }    
}
