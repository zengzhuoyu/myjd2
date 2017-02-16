<?php

namespace app\controllers;

use yii\web\Controller;

class MemberController extends Controller
{
	//前台用户注册登录页面
    public function actionAuth()
    {
        $this->layout = 'layout2';
        
        return $this->render('auth');
    }
}
