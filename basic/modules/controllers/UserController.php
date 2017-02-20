<?php

namespace app\modules\controllers;
// use yii\web\Controller;

use yii\data\Pagination;
use app\models\User;
use app\models\Profile;
use Yii;
use app\modules\controllers\CommonController;

class UserController extends CommonController
{
    public function actionUsers()
    {
        $this->layout = "layout1";

        //关联表 user 和 profile 
        $model = User::find()->joinWith('profile');
        $count = $model->count();
        $pageSize = Yii::$app->params['pageSize']['user'];
        $pager = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $users = $model->offset($pager->offset)->limit($pager->limit)->all();

        return $this->render('users', ['users' => $users, 'pager' => $pager]);
    }

    public function actionReg()
    {
        $this->layout = "layout1";

        $model = new User;
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if ($model->reg($post)) {
                Yii::$app->session->setFlash('info', '添加成功');
            }
        }

        $model->userpass = '';
        $model->repass = '';

        return $this->render("reg", ['model' => $model]);
    }

    public function actionDel()
    {
        try{

            $userid = (int) Yii::$app->request->get('userid');
            $userid = 0;
            if (empty($userid)) {
                throw new \Exception();//抛出异常 当异常被抛出时，其后的代码不会继续执行，PHP 会尝试查找匹配的 "catch" 代码块。
            }

            $trans = Yii::$app->db->beginTransaction();//创建事务
            if (Profile::find()->where('userid = :id', [':id' => $userid])->one()) {//如果有这条数据 和下面的user表写法对比说明：该条profile表数据不一定会有
                $res = Profile::deleteAll('userid = :id', [':id' => $userid]);
                if (empty($res)) {
                    throw new \Exception();
                }
            }
            if (!User::deleteAll('userid = :id', [':id' => $userid])) {
                throw new \Exception();
            }
            $trans->commit();//提交事务

        } catch(\Exception $e) {

            // if (Yii::$app->db->getTransaction()) {
                $trans->rollback();//回滚事务
            // }

        }

        $this->redirect(['user/users']);
        Yii::$app->end();
    }

}
