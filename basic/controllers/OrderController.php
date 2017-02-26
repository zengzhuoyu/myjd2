<?php
namespace app\controllers;
use app\controllers\CommonController;
use Yii;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Cart;
use app\models\Product;
use app\models\User;
use app\models\Address;
use app\models\Pay;
use dzer\express\Express;

class OrderController extends CommonController
{
    //前台用户订单中心页面
    public function actionIndex()
    {
        $this->layout = "layout2";

        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }
        $loginname = Yii::$app->session['loginname'];
        $userid = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one()->userid;

        $orders = Order::getProducts($userid);
        return $this->render("index", ['orders' => $orders]);
    }

    //购物车中的点击结算
    public function actionAdd()
    {
        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (Yii::$app->request->isPost) {

                $post = Yii::$app->request->post();
                $ordermodel = new Order;
                $ordermodel->scenario = 'add';
                $usermodel = User::find()->where('username = :name or useremail = :email', [':name' => Yii::$app->session['loginname'], ':email' => Yii::$app->session['loginname']])->one();
                if (!$usermodel) {
                    throw new \Exception();
                }
                $userid = $usermodel->userid;
                $ordermodel->userid = $userid;
                $ordermodel->status = Order::CREATEORDER;
                $ordermodel->createtime = time();
                if (!$ordermodel->save()) {
                    throw new \Exception();
                }
                $orderid = $ordermodel->getPrimaryKey();//获得刚添加的主键id

                foreach ($post['OrderDetail'] as $product) {

                    $model = new OrderDetail;//每操作一次数据库，就得实例化一下类
                    $product['orderid'] = $orderid;
                    $product['createtime'] = time();
                    $data['OrderDetail'] = $product;//之所以命名为OrderDetail，是一位要操作的是OrderDetail模型

                    if (!$model->add($data)) {
                        throw new \Exception();
                    }
                    Cart::deleteAll('productid = :pid' , [':pid' => $product['productid']]);//清空那条购物车数据

                    //商品库存要减少 updateAllCounters
                    Product::updateAllCounters(['num' => -$product['productnum']], 'productid = :pid', [':pid' => $product['productid']]);
                }
            }

            $transaction->commit();//都成功,执行提交
        }catch(\Exception $e) {
            $transaction->rollback();//有异常,回滚
            return $this->redirect(['cart/index']);
        }

        return $this->redirect(['order/check', 'orderid' => $orderid]);
    }

    //前台收银台页面
    public function actionCheck()
    {
        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }
        $orderid = (int) Yii::$app->request->get('orderid');
        $status = Order::find()->where('orderid = :oid', [':oid' => $orderid])->one()->status;//查出单条数据中单个字段的值
        if ($status != Order::CREATEORDER && $status != Order::CHECKORDER) {//CHECKORDER?
            return $this->redirect(['order/index']);
        }
        $loginname = Yii::$app->session['loginname'];
        $userid = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one()->userid;
        $addresses = Address::find()->where('userid = :uid', [':uid' => $userid])->asArray()->all();

        $details = OrderDetail::find()->where('orderid = :oid', [':oid' => $orderid])->asArray()->all();
        $data = [];
        foreach($details as $detail) {
            $model = Product::find()->where('productid = :pid' , [':pid' => $detail['productid']])->one();
            $detail['title'] = $model->title;
            $detail['cover'] = $model->cover;
            $data[] = $detail;
        }
        
        $express = Yii::$app->params['express'];
        $expressPrice = Yii::$app->params['expressPrice'];
        $this->layout = "layout1";
        return $this->render("check", ['express' => $express, 'expressPrice' => $expressPrice, 'addresses' => $addresses, 'products' => $data]);
    }    

    //前台收银台页面的确认订单
    public function actionConfirm()
    {
        //addressid, expressid, status, amount(orderid,userid)
        try {
            if (Yii::$app->session['isLogin'] != 1) {
                return $this->redirect(['member/auth']);
            }
            if (!Yii::$app->request->isPost) {//用意？
                throw new \Exception();
            }
            $post = Yii::$app->request->post();
            $loginname = Yii::$app->session['loginname'];
            $usermodel = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one();
            if (empty($usermodel)) {
                throw new \Exception();
            }
            $userid = $usermodel->userid;
            $model = Order::find()->where('orderid = :oid and userid = :uid', [':oid' => $post['orderid'], ':uid' => $userid])->one();
            if (empty($model)) {
                throw new \Exception();
            }
            $model->scenario = "update";
            $post['status'] = Order::CHECKORDER;
            $details = OrderDetail::find()->where('orderid = :oid', [':oid' => $post['orderid']])->all();
            $amount = 0;
            foreach($details as $detail) {
                $amount += $detail->productnum * $detail->price;
            }
            if ($amount <= 0) {
                throw new \Exception();
            }
            $express = Yii::$app->params['expressPrice'][$post['expressid']];
            if ($express < 0) {
                throw new \Exception();
            }
            $amount += $express;
            $post['amount'] = $amount;
            $data['Order'] = $post;

            if (empty($post['addressid'])) {
                //这里应该是跳转去填选收货地址
                // return $this->redirect(['order/pay', 'orderid' => $post['orderid'], 'paymethod' => $post['paymethod']]);
                return $this->redirect(['order/check', 'orderid' => $post['orderid']]);
            }
            if ($model->load($data) && $model->save()) {//一条数据的更改操作

                // return $this->redirect(['order/pay', 'orderid' => $post['orderid'], 'paymethod' => $post['paymethod']]);
                $this->_pay($post['orderid'],$post['paymethod']);//改成调用方法的方式请求
            }
        }catch(\Exception $e) {
            return $this->redirect(['index/index']);
        }
    }

    //这个方法结束后，表示提交了支付信息，待用户扫码支付
    private function _Pay($orderid,$paymethod)
    {
        try{
            if (Yii::$app->session['isLogin'] != 1) {
                throw new \Exception();
            }
            $orderid = (int) Yii::$app->request->get('orderid');//get方式
            $paymethod = Yii::$app->request->get('paymethod');
            if (empty($orderid) || empty($paymethod)) {
                throw new \Exception();//抛异常
            }
            if ($paymethod == 'alipay') {
                return Pay::alipay($orderid);//为什么会有个return？ 
            }
        }catch(\Exception $e) {}

        return $this->redirect(['order/index']);//?
    }

    //订单中心的查看送货情况
    public function actionGetexpress()
    {
        $expressno = Yii::$app->request->get('expressno');
        $res = Express::search($expressno);//返回json格式的数据
        echo $res;
        exit;
    }

    //订单中心的确认收货
    public function actionReceived()
    {
        $orderid = (int) Yii::$app->request->get('orderid');
        $order = Order::find()->where('orderid = :oid', [':oid' => $orderid])->one();
        if (!empty($order) && $order->status == Order::SENDED) {
            $order->status = Order::RECEIVED;
            $order->save();//load加载数据写法的必要性？ save更细与updateAll更细你的区别？
        }
        return $this->redirect(['order/index']);
    }

}








