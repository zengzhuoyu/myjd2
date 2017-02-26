<?php
namespace app\controllers;
use app\controllers\CommonController;
use app\models\User;
use Yii;

class MemberController extends CommonController
{
    //前台用户注册登录页面 x 登录操作
    public function actionAuth()
    {

        $this->layout = 'layout2';

        if (Yii::$app->request->isGet) {//表示是从get方式请求过来的

            $url = Yii::$app->request->referrer;//获得上一个页面地址
            if (empty($url)) {
                $url = "/";
            }
            Yii::$app->session->setFlash('referrer', $url);
        }
        $model = new User;
        if (Yii::$app->request->isPost) {//登录
            $post = Yii::$app->request->post();
            if ($model->login($post)) {
                $url = Yii::$app->session->getFlash('referrer');
                return $this->redirect($url);
            }
        }
        return $this->render("auth", ['model' => $model]);
    }

    //退出 x
    public function actionLogout()
    {
        Yii::$app->session->remove('loginname');
        Yii::$app->session->remove('isLogin');
        if (!isset(Yii::$app->session['isLogin'])) {
            return $this->goBack(Yii::$app->request->referrer);//回到上一个页面地址
        }
    }

    //用户注册操作
    public function actionReg()
    {
        $model = new User;
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if ($model->regByMail($post)) {
                Yii::$app->session->setFlash('info', '电子邮件发送成功');
            }
        }
        $this->layout = 'layout2';
        return $this->render('auth', ['model' => $model]);
    }

    //跳转到qq登录页面
    public function actionQqlogin()
    {
        require_once("../vendor/qqlogin/qqConnectAPI.php");//为什么不能用use引入？
        $qc = new \QC();
        $qc->qq_login();
    }

    //点击qq登录后执行的回调方法
    public function actionQqcallback()
    {
        //如果qq账号已经绑定该网站，直接登录 否则需要进行绑定
        
        require_once("../vendor/qqlogin/qqConnectAPI.php");
        $auth = new \OAuth();//A为社么是大写？
        $accessToken = $auth->qq_callback();
        $openid = $auth->get_openid();//每一个qq号都会有对应的一个唯一的openid
        $qc = new \QC($accessToken, $openid);
        $userinfo = $qc->get_user_info();//获取用户信息

        //开启session操作
        $session = Yii::$app->session;

        $session['userinfo'] = $userinfo;
        $session['openid'] = $openid;

        if ($model = User::find()->where('openid = :openid', [':openid' => $openid])->one()) {//qq账号已经绑定该网站
            $session['loginname'] = $model->username;
            $session['isLogin'] = 1;
            return $this->redirect(['index/index']);
        }
        return $this->redirect(['member/qqreg']);
    }

    //没有绑定的用户进行绑定
    public function actionQqreg()
    {
        $session = Yii::$app->session;

        if(!$session['userinfo']){//如果没有用qq登录的用户直接访问该页面的话
            return $this->goBack(Yii::$app->request->referrer);
        }

        $this->layout = "layout2";

        $model = new User;
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();    
            $post['User']['openid'] = $session['openid'];
            if ($model->reg($post, 'qqreg')) {
                $session['loginname'] = $post['User']['username'];
                $session['isLogin'] = 1;
                return $this->redirect(['index/index']);
            }
        }
        return $this->render('qqreg', ['model' => $model]);
    }


}
