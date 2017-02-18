<?php

namespace app\modules\controllers;

use yii\web\Controller;
use Yii;

class CommonController extends Controller
{

    public function init()//所有 方法 执行前，init会自动执行
    {
    	// if(Yii::$app->session['admin']['isLogin'] != 1){
    	if(!isset(Yii::$app->session['admin']['isLogin'])){
    		return $this->redirect(['/admin/public/login']);//路径没弄明白？
    	}
    }
}
