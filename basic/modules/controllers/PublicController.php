<?php

namespace app\modules\controllers;

use yii\web\Controller;

use Yii;
use app\modules\models\Admin;

class PublicController extends Controller
{

	//后台登录页面显示 及 登录表单提交
    public function actionLogin()
    {
    		//进入登录页面时，已经登录过了，就直接跳转到后台首页
    	if(isset(Yii::$app->session['admin']['isLogin'])){
    		$this->redirect(['default/index']);
    		Yii::$app->end();
    	}    	

        $this->layout = false;
        
        $model = new Admin;
        if(Yii::$app->request->isPost){//判断是否是post方式提交的数据
        		$post = Yii::$app->request->post();//接收post提交的数据
        		if($model->login($post)){
        			$this->redirect(['default/index']);//yii跳转函数
        			Yii::$app->end();//是跳转后必写的吗？
        		}
        }

        return $this->render('login',['model' => $model]);
    }

    //找回密码
    public function actionSeekpassword()
    {

        $this->layout = false;
        $model = new Admin;

        if(Yii::$app->request->isPost){
            $post = Yii::$app->request->post();
             if($model->seekPass($post)){
                Yii::$app->session->setFlash('info','电子邮件已发送成功,请注意查收');//写入session的info变量
             }
        }
        return $this->render('seekpassword',['model' => $model]); 
    }    

}
