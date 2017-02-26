<?php
namespace app\controllers;

use yii\web\Controller;
use app\models\Category;
use app\models\Cart;
use app\models\User;
use app\models\Product;
use Yii;

class CommonController extends Controller
{
    //x
    public function init()
    {
        //显示分类及其二级
        $menu = Category::getMenu();
        $this->view->params['menu'] = $menu;//传递到模板中 此法与自定义空数组并且赋值数组的方式有什么区别？

        $data = [];
        $data['products'] = [];
        $total = 0;
        if (Yii::$app->session['isLogin']) {//添加到购物车中但还未结算的商品 用在公共模板页显示
            $usermodel = User::find()->where('username = :name', [":name" => Yii::$app->session['loginname']])->one();
            // if (!empty($usermodel) && !empty($usermodel->userid)) {
            // 改成
            if (!empty($usermodel)) {
                $userid = $usermodel->userid;
                $carts = Cart::find()->where('userid = :uid', [':uid' => $userid])->asArray()->all();
                foreach($carts as $k=>$pro) {
                    $product = Product::find()->where('productid = :pid', [':pid' => $pro['productid']])->one();
                    $data['products'][$k]['cover'] = $product->cover;
                    $data['products'][$k]['title'] = $product->title;
                    $data['products'][$k]['productnum'] = $pro['productnum'];
                    $data['products'][$k]['price'] = $pro['price'];
                    $data['products'][$k]['productid'] = $pro['productid'];//?
                    $data['products'][$k]['cartid'] = $pro['cartid'];//?
                    $total += $data['products'][$k]['price'] * $data['products'][$k]['productnum'];//购物车中一个商品信息的价格 * 数量
                }
            }
        }

        $data['total'] = $total;
        $this->view->params['cart'] = $data;

        //底部公共部分的四种类型商品
        $tui = Product::find()->where('istui = "1" and ison = "1"')->orderby('createtime desc')->limit(3)->all();
        $new = Product::find()->where('ison = "1"')->orderby('createtime desc')->limit(3)->all();
        $hot = Product::find()->where('ison = "1" and ishot = "1"')->orderby('createtime desc')->limit(3)->all();
        $sale = Product::find()->where('ison = "1" and issale = "1"')->orderby('createtime desc')->limit(3)->all();
        $this->view->params['tui'] = (array)$tui;//(array)就没用
        $this->view->params['new'] = (array)$new;
        $this->view->params['hot'] = (array)$hot;
        $this->view->params['sale'] = (array)$sale;
    }
}
