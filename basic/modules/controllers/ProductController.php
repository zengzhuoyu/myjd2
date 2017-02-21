<?php

namespace app\modules\controllers;
use app\models\Category;
use app\models\Product;
// use yii\web\Controller;
use Yii;
use yii\data\Pagination;
use crazyfd\qiniu\Qiniu;
use app\modules\controllers\CommonController;

class ProductController extends CommonController
{
    public function actionList()
    {
        $this->layout = "layout1";

        $model = Product::find();
        $count = $model->count();
        $pageSize = Yii::$app->params['pageSize']['product'];
        $pager = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $products = $model->offset($pager->offset)->limit($pager->limit)->all();

        return $this->render("products", ['pager' => $pager, 'products' => $products]);
    }

    public function actionAdd()
    {
        $this->layout = "layout1";

        $model = new Product;
        $cate = new Category;

        $list = $cate->getOptions();
        unset($list[0]);
        
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $pics = $this->upload();

            if (!$pics) {
                $model->addError('cover', '封面不能为空');
            } else {
                $post['Product']['cover'] = $pics['cover'];
                $post['Product']['pics'] = $pics['pics'];
            }
            if ($pics && $model->add($post)) {//短路现象的条件应该写前面
                Yii::$app->session->setFlash('info', '添加成功');
            } else {
                Yii::$app->session->setFlash('info', '添加失败');
            }

        }

        return $this->render("add", ['opts' => $list, 'model' => $model]);
    }

    private function upload()
    {
        if ($_FILES['Product']['error']['cover'] > 0) {//图片封面：说明上传有错误
            return false;
        }

        //连接七牛
        $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);//控制器调用模型里的自定义常量
        $key = uniqid();//以微秒计的当前时间，生成一个唯一的 ID 生成一个key,因为七牛上使用key来定位的(就是七牛上图片的名称)

        //上传图片封面
        $qiniu->uploadFile($_FILES['Product']['tmp_name']['cover'], $key);//第一个参数是临时文件
        $cover = $qiniu->getLink($key);//获得上传成功后图片外链地址

        //上传商品图片
        $pics = [];
        foreach ($_FILES['Product']['tmp_name']['pics'] as $k => $file) {
            if ($_FILES['Product']['error']['pics'][$k] > 0) {
                continue;
            }
            $key = uniqid();//上传一张图片就需要一个key
            $qiniu->uploadFile($file, $key);
            $pics[$key] = $qiniu->getLink($key);
        }

        return ['cover' => $cover, 'pics' => json_encode($pics)];
    }

    public function actionMod()
    {
        $this->layout = "layout1";

        $cate = new Category;
        $list = $cate->getOptions();
        unset($list[0]);

        $productid = (int) Yii::$app->request->get("productid");
        $model = Product::find()->where('productid = :id', [':id' => $productid])->one();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
            $post['Product']['cover'] = $model->cover;//?

            if ($_FILES['Product']['error']['cover'] == 0) {//图片封面 值为4表示没有更改,0表示有新上传
                $key = uniqid();
                $qiniu->uploadFile($_FILES['Product']['tmp_name']['cover'], $key);
                $post['Product']['cover'] = $qiniu->getLink($key);
                $qiniu->delete(basename($model->cover));//删除七牛上的图片
            }

            //商品图片
            $pics = [];
            foreach($_FILES['Product']['tmp_name']['pics'] as $k => $file) {
                if ($_FILES['Product']['error']['pics'][$k] > 0) {
                    continue;
                }
                $key = uniqid();
                $qiniu->uploadfile($file, $key);
                $pics[$key] = $qiniu->getlink($key);

            }

            $post['Product']['pics'] = json_encode(array_merge((array)json_decode($model->pics, true), $pics));//（array）是否有必要？
            if ($model->load($post) && $model->save()) {
                Yii::$app->session->setFlash('info', '修改成功');
            }

        }

        return $this->render('add', ['model' => $model, 'opts' => $list]);

    }

    public function actionRemovepic()
    {
        $key = Yii::$app->request->get("key");
        $productid = (int) Yii::$app->request->get("productid");

        $model = Product::find()->where('productid = :pid', [':pid' => $productid])->one();

        $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
        $qiniu->delete($key);
        $pics = json_decode($model->pics, true);
        unset($pics[$key]);
        Product::updateAll(['pics' => json_encode($pics)], 'productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/mod', 'productid' => $productid]);
    }

    public function actionDel()
    {
        $productid = (int) Yii::$app->request->get("productid");
        $model = Product::find()->where('productid = :pid', [':pid' => $productid])->one();
        $key = basename($model->cover);
        $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
        //1.删除七牛图片封面
        $qiniu->delete($key);
        $pics = json_decode($model->pics, true);
        foreach($pics as $key=>$file) {
            //2.删除七牛商品图片
            $qiniu->delete($key);
        }
        //3.删除该调数据
        Product::deleteAll('productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/list']);
    }

    public function actionOn()
    {
        $productid = (int) Yii::$app->request->get("productid");
        Product::updateAll(['ison' => '1'], 'productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/list']);
    }

    public function actionOff()
    {
        $productid = (int) Yii::$app->request->get("productid");
        Product::updateAll(['ison' => '0'], 'productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/list']);
    }









}
