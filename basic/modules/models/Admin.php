<?php 
	namespace app\modules\models;

	use yii\db\ActiveRecord;

	use Yii;

	class Admin extends ActiveRecord
	{
		public $rememberMe = true;//表单提交有，但数据库没有的字段

		public static function tableName()
		{
			return "{{%admin}}";
		}

		//验证数据
		public function rules()
		{
			return [
				['adminuser','required','message' => '管理员账号不能为空'],
				['adminpass','required','message' => '管理员密码不能为空'],
				['rememberMe','boolean'],//不是0或者1呢？
				['adminpass','validatePass'],
			];
		}

		//验证密码
		public function validatePass()
		{
			//如果之前的验证都没有出错
			if(!$this->hasErrors()){
				$data = self::find()
					->where('adminuser = :user and adminpass = :pass',[':user' => $this->adminuser,":pass" => md5($this->adminpass)])
					->one();

				if(is_null($data)){
					$this->addError('adminpass','用户名或密码错误');
				}
			}
		}

		//登录操作
		public function login($data)
		{	
			//加载数组并且验证数据
			//$this->load($data)?
			if($this->load($data) && $this->validate()){

				//用户登录信息写进session
				$lifetime = $this->rememberMe ? 24*60*60 : 0;

				$session = Yii::$app->session;
				session_set_cookie_params($lifetime);//设置保留时间：浏览器关闭后，session会被清除，除非设置了保留时间

				$session['admin'] = [
					'adminuser' => $this->adminuser,
					'isLogin' => 1,
				];

				$this->updateAll(//yii更新操作
					[
					'logintime' => time(),
					'loginip' => ip2long(Yii::$app->request->userIp)//yii获取客户端ip p2long：将四个字段以点分开的IP网络址协议地址转换成整数
					],
					'adminuser = :user',
					[":user" => $this->adminuser]

				);

				return (bool) $session['admin']['isLogin'];
			}

			return false;
		}
	}
 ?>