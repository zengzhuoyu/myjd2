<?php

namespace app\controllers;
use app\controllers\CommonController;

use Yii;
use app\models\User;
use app\models\Address;

class AddressController extends CommonController
{
    //order/check 收银台添加联系人收货地址
    public function actionAdd()
    {
        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }
        $loginname = Yii::$app->session['loginname'];
        $userid = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one()->userid;
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $post['userid'] = $userid;
            $post['address'] = $post['address1'].$post['address2'];
            $data['Address'] = $post;
            $model = new Address;
            $model->load($data);
            $model->save();
        }
        return $this->redirect($_SERVER['HTTP_REFERER']);//跳回到过来的地址
    }

    //order/check 收银台添加联系人收货地址
    public function actionDel()
    {
        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }
        $loginname = Yii::$app->session['loginname'];
        $userid = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one()->userid;
        $addressid = (int) Yii::$app->request->get('addressid');
        if (!Address::find()->where('userid = :uid and addressid = :aid', [':uid' => $userid, ':aid' => $addressid])->one()) {
            return $this->redirect($_SERVER['HTTP_REFERER']);//$_SERVER['HTTP_REFERER'] 为什么不适合在线上这么写？
        }
        Address::deleteAll('addressid = :aid', [':aid' => $addressid]);
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }










}
