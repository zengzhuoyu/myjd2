<?php 
	namespace app\modules\models;

	use yii\db\ActiveRecord;

	use Yii;

	class Admin extends ActiveRecord
	{
		public $rememberMe = true;//表单提交有，但数据库没有的字段 true表示默认选中

		// 添加属性
		public $repass;
		public static function tableName()
		{
			return "{{%admin}}";
		}

		//添加页面显示的字段名称
		public function attributeLabels()
		{
		    return [
		        'adminuser' => '管理员账号',
		        'adminemail' => '管理员邮箱',
		        'adminpass' => '管理员密码',
		        'repass' => '确认密码',
		    ];
		}

		// 验证规则
		public function rules()
		{
			return [
				['adminuser','required','message' => '管理员账号不能为空','on' => ['login','seekpass','changepass','adminadd','changeemail']],
				['adminuser','unique','message' => '管理员已被注册','on' => 'adminadd'],
				['adminpass','required','message' => '管理员密码不能为空','on'=> ['login','changepass','adminadd','changeemail']],
				['rememberMe','boolean','on'=>'login'],//不是0或者1呢？
				['adminpass','validatePass','on'=>['login','changeemail']],
				['adminemail','required','message' => '电子邮箱不能为空','on'=>['seekpass','adminadd','changeemail']],
				['adminemail','email','message' => '电子邮箱格式不正确','on'=>['seekpass','adminadd','changeemail']],
				['adminemail','unique','message' => '电子邮箱已被注册','on'=>['adminadd','changeemail']],
				['adminemail','validateEmail','on'=>'seekpass'],		
				['repass','required','message' => '确认密码不能不为空','on' => ['changepass','adminadd']],//确认密码与新密码一致，这里就可以不用判断了
				['repass','compare','compareAttribute' => 'adminpass','message'=>'两次输入密码不一致','on' => ['changepass','adminadd']],
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

		public function validateEmail()
		{
			if(!$this -> hasErrors()){//如果前三项没有错误
				$data = self::find()
					//绑定上数据
					->where('adminuser = :user and adminemail = :email',[":user" => $this->adminuser,':email' => $this->adminemail])
					->one();

				if(is_null($data)){//如果有查询出来就是一个对象，否则就为空
					$this->addError("adminemail","管理员电子邮箱不匹配");
				}
			}
		}

		//登录操作
		public function login($data)
		{	
			// 指定属于自己的验证场景:登录时不需要用到找回密码的ruels验证字段
			$this->scenario = "login";

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

				$this->updateAll(//yii更新操作 更新登录时间、登录ip
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

		//找回密码
		public function seekPass($data)
		{

			$this->scenario = "seekpass";

			if($this->load($data) && $this->validate()){

				//给管理员注册的邮箱发送邮件
				$time = time();
				// 传递token值,自己定义的生成规则
				$token = $this->createToken($data['Admin']['adminuser'],$time);

				//compose第一个参数：模板 第二个参数：传递的变量数组
				$mailer = Yii::$app->mailer->compose('seekpass',['adminuser' => $data['Admin']['adminuser'],'time' => $time,'token' => $token]);
				$mailer->setFrom('zengzhuoyu24@163.com');//发送邮件的邮箱
				$mailer->setTo($data['Admin']['adminemail']);//发送给谁
				$mailer->setSubject('慕课商城-找回密码');//邮件主题
				if($mailer->send()){
					return true;
				}
			}

			return false;
		}		

		// 生成token
		public function createToken($adminuser,$time)
		{

        			return md5(
        				md5($adminuser).base64_encode(Yii::$app->request->userIP).md5($time)
        				);
		}

		// 修改密码
		public function changePass($data)
		{

			$this->scenario = "changepass";

			if($this->load($data) && $this->validate()){

				//修改密码
            			return (bool) $this->updateAll(['adminpass' => md5($this->adminpass)], 'adminuser = :user', [':user' => $this->adminuser]);
			}

			return false;
		}		

		//添加管理员
		public function reg($data)
		{

			$this->scenario = 'adminadd';

			if($this->load($data) && $this->validate()){
				$this->adminpass = md5($this->adminpass);
				// save方法会先执行validate验证过程
				// save(false)：false值表示save不需要先执行validate验证
				if($this->save(false)){//yii的添加新数据
					return true;
				}
				return false;
			}
			return false;
		}		

		//当前登录管理员邮箱的修改
		public function changeEmail($data)
		{
		    $this->scenario = "changeemail";

		    if ($this->load($data) && $this->validate()) {
		        return (bool)$this->updateAll(['adminemail' => $this->adminemail], 'adminuser = :user', [':user' => $this->adminuser]);
		    }
		    return false;
		}		
	}
 ?>