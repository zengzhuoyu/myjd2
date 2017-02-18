<?php

namespace app\modules\controllers;

use yii\web\Controller;

use Yii;
class DefaultController extends CommonController
{
	//后台首页
    public function actionIndex()
    {
        $this->layout = 'layout1';
        
        return $this->render('index');
    }

    //后台退出操作
    public function actionLogout()
    {
    	Yii::$app->session->removeAll();//删除所有的session 删除（摧毁就不存在了）和清空是不同的概念
    	// if(!isset(Yii::$app->session['admin']['isLogin'])){//存在和为空是两个不同的概念
    		$this->redirect(['public/login']);
    		Yii::$app->end();
    	// }
    	// $this->goback();//从哪来回到哪去
    }    
}
