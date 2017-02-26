<?php
namespace app\controllers;

use app\controllers\CommonController;
use Yii;
use app\models\Product;
use yii\data\Pagination;

class ProductController extends CommonController
{
    //前台商品分类页面 x
    public function actionIndex()
    {
        $this->layout = "layout2";

        $cid = (int) Yii::$app->request->get("cateid");

        $where = "cateid = :cid and ison = '1'";
        $params = [':cid' => $cid];
        $model = Product::find()->where($where, $params);//yii自带的where条件语句使用

        // $all = $model->asArray()->all();//没用？

        $count = $model->count();//查询出来的结果继续使用！！！
        $pageSize = Yii::$app->params['pageSize']['frontproduct'];
        $pager = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $all = $model->offset($pager->offset)->limit($pager->limit)->asArray()->all();

        //where中字符串拼接了
        $tui = $model->Where($where . ' and istui = \'1\'', $params)->orderby('createtime desc')->limit(5)->asArray()->all();
        $hot = $model->Where($where . ' and ishot = \'1\'', $params)->orderby('createtime desc')->limit(5)->asArray()->all();
        $sale = $model->Where($where . ' and issale = \'1\'', $params)->orderby('createtime desc')->limit(5)->asArray()->all();

        return $this->render("index", ['sale' => $sale, 'tui' => $tui, 'hot' => $hot, 'all' => $all, 'pager' => $pager, 'count' => $count]);
    }

    //前台商品详情页面 x
    public function actionDetail()
    {
        $this->layout = "layout2";
        
        $productid = (int) Yii::$app->request->get("productid");
        $product = Product::find()->where('productid = :id', [':id' => $productid])->asArray()->one();
        $data['all'] = Product::find()->where('ison = "1"')->orderby('createtime desc')->limit(7)->all();
        
        return $this->render("detail", ['product' => $product, 'data' => $data]);
    }
}
