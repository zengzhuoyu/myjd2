<?php

namespace app\modules\controllers;

// use yii\web\Controller;

use Yii;

use app\modules\models\Admin;

use yii\data\Pagination;

use app\modules\controllers\CommonController;

class ManageController extends CommonController
{

    public function actionMailchangepass()
    {

        $this->layout = false;

        $time = Yii::$app->request->get("timestamp");
        $adminuser = Yii::$app->request->get("adminuser");
        $token = Yii::$app->request->get("token");

        $model = new Admin;

        //由此可见,今后可能复用的方法就该写在model里
        $myToken = $model->createToken($adminuser, $time);
        if ($token != $myToken) {
            $this->redirect(['public/login']);
            Yii::$app->end();
        }
        //超过5分钟了,失效
        if (time() - $time > 300) {
            $this->redirect(['public/login']);
            Yii::$app->end();
        }

        if(Yii::$app->request->isPost){
            $post = Yii::$app->request->post();
            if($model->changePass($post)){
                // $this->redirect('public/login');
                // 或者显示修改成功
                Yii::$app->session->setFlash('info','密码修改成功');
            }
        }

        $model->adminuser = $adminuser;//传递给模板的隐藏域
        return $this->render('mailchangepass',['model' => $model]);
    }

    //管理员列表
    public function actionManagers(){

        $this->layout = "layout1";

        $model = Admin::find();//查找时写法
        $count = $model->count();

        // totalCount 总条数 ; pageSize 每页显示条数
        $pageSize = Yii::$app->params['pageSize']['manage'];
        $pager = new Pagination(['totalCount' => $count,'pageSize' => $pageSize]);//分页字符串
        $managers = $model->offSet($pager->offSet)->limit($pager->limit)->all();
        return $this->render('managers',['managers' => $managers,'pager' => $pager]);
    }

    // 添加管理员
    public function actionReg(){

        $this->layout = 'layout1';

        $model = new Admin;
        if(Yii::$app->request->isPost){
            $post = Yii::$app->request->post();
            if($model->reg($post)){
                Yii::$app->session->setFlash('info','添加成功');
            }else{
                Yii::$app->session->setFlash('info','添加失败');
            }
        }

        //清除页面显示密码
        $model->adminpass = '';
        $model->repass = '';

        return $this->render('reg',['model' => $model]);
    }

    //删除管理员
    public function actionDel()
    {
        $adminid = (int)Yii::$app->request->get("adminid");

        if (empty($adminid) || $adminid == 1) {
            $this->redirect(['manage/managers']);
            return false;
        }
        $model = new Admin;
        if ($model->deleteAll('adminid = :id', [':id' => $adminid])) {//yii删除操作
            Yii::$app->session->setFlash('info', '删除成功');
        }else{
            Yii::$app->session->setFlash('info', '删除失败');
        }

        $this->redirect(['manage/managers']);
        Yii::$app->end();
    }    

    //当前登录管理员邮箱的修改
    public function actionChangeemail()
    {
        $this->layout = 'layout1';

        $model = Admin::find()->where('adminuser = :user', [':user' => Yii::$app->session['admin']['adminuser']])->one();
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if ($model->changeemail($post)) {
                Yii::$app->session->setFlash('info', '修改成功');
            }
        }
        $model->adminpass = "";
        return $this->render('changeemail', ['model' => $model]);
    }

    //当前登录管理员密码的修改
    public function actionChangepass()
    {
        $this->layout = "layout1";
        
        $model = Admin::find()->where('adminuser = :user', [':user' => Yii::$app->session['admin']['adminuser']])->one();
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if ($model->changepass($post)) {
                Yii::$app->session->setFlash('info', '修改成功');
            }
        }
        $model->adminpass = '';
        $model->repass = '';
        return $this->render('changepass', ['model' => $model]);
    }

}
