中间件：请求，响应，数据处理器，数据验证器
控制器：请求，响应，数据处理器，数据验证器，块访问，层访问
缓存器：请求，缓存操作
数据层：请求，数据操作




$this->response($data)->view('view');
$this->response($data)->json('模版名');
$this->request('post')->id;
$this->request('get')->type;
