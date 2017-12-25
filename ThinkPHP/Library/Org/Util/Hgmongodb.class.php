<?php
/**
 *  Hgmongodb.php 操作mongodb类
 *  该mongodb类主要是解决thinkphp无法和mysql一起使用的拓展类
 */

class Hgmongodb{
    protected $_mongo           =   null; // MongoDb Object
    protected $_collection      =   null; // MongoCollection Object集合对象
    protected $_dbName          =   ''; // dbName数据名字
    protected $_collectionName  =   ''; // collectionName集合名字
    protected $_cursor          =   null; // MongoCursor Object游标
    protected $comparison       =   array('neq'=>'ne','ne'=>'ne','gt'=>'gt','egt'=>'gte','gte'=>'gte','lt'=>'lt','elt'=>'lte','lte'=>'lte','in'=>'in','not in'=>'nin','nin'=>'nin');
    //array('neq','ne','gt','egt','gte','lt','lte','elt')
    protected $linkID      = null;//连接标记句柄
    protected $connected   = false;//成功连接标记
    protected $coll_prefix = '';//集合的前缀
    protected $lastInsID   = null;//最后插入的ID
    protected $error       = null;
    protected $host_ = null;


    protected $config = array(
        'hostname'       => null ,
        'hostport'       =>'27017',
        'username'       =>'',
        'password'       =>'',
        'database'       =>'boss3_www',
        'coll_prefix'    =>'',
        'params'         =>array('connect'=>true),
        'db_deploy_type' => 0,//是否注销连接
    );

    protected $is_newmongodb = false;

    /**
     * [是否为新mongodb php版本>5.5 description]
     * @return boolean [description]
     */
    function isNewMongodb(){
        if(version_compare(PHP_VERSION,'5.6.0','>')){
            $this->is_newmongodb = true;
        }
        return $this->is_newmongodb;
    }

    /**
     *  构造函数，初始化连接信息
     */
    public function __construct($config=array()){
        $this->config['hostname'] = C('MONGOHOST');
        $this->isNewMongodb();
        // if(!$this->is_newmongodb){
        //     if ( !class_exists('MongoClient') ) {
        //        throw new Exception("Not support MongoClient");
        //        exit;
        //     }

        //     if(!empty($config)) {
        //         $this->config   =   $config;
        //         if(empty($this->config['params'])) {
        //             $this->config['params'] =   array('connect'=>true);
        //         }
                
        //         if(isset($this->config['coll_prefix'])){
        //             $this->coll_prefix = $this->config['coll_prefix'];
        //         }
                
        //         if(isset($this->config['database'])){
        //             $this->_dbName = $this->config['database'];
        //         }
        //     }
        // }else{
        //     $this->construct_new($config);
        // }

        if(!$this->is_newmongodb){
            if ( !class_exists('MongoClient') ) {
               throw new Exception("Not support MongoClient");
               exit;
            }
        }

        if(!empty($config)) {
            $this->config   =   $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   array('connect'=>true);
            }
            
            if(isset($this->config['coll_prefix'])){
                $this->coll_prefix = $this->config['coll_prefix'];
            }
            
            if(isset($this->config['database'])){
                $this->_dbName = $this->config['database'];
            }
        }

        
    }
    
    /**
     *  连接数据库，返回连接句柄$this->linkID触发式连接Mongodb
     */
    public function connect($config = ''){
        if ( !isset($this->linkID) ) {
            if(empty($config))  $config =   $this->config;
            $host = 'mongodb://'.($config['username']?"{$config['username']}":'').($config['password']?":{$config['password']}@":'').$config['hostname'].($config['hostport']?":{$config['hostport']}":'');
            $this->host_ = $host;
            // print_r($host);exit;
            //$m = new MongoClient();
            //$db = $m->qian; // 获取名称为 "foo" 的数据库
            //$user = $db->dv_we_user->findOne();
            //    var_dump($user);die;
            try{
                if(!$this->is_newmongodb){
                    $this->linkID = new MongoClient($host,$config['params']);
                }else{
                    $this->linkID    = new MongoDB\Driver\Manager($host);
                }
               //$db = $this->linkID->qiangdawei; // 获取名称为 "foo" 的数据库
               //$user = $db->dv_we_user->findOne();
               //var_dump($user);die;
                
            }catch (Exception $e){
                throw new Exception($e->getmessage());
            }
            // 标记连接成功
            $this->connected    =   true;
           
            // 注销数据库连接配置信息db_deploy_type = 1;
            if(1 == $config['db_deploy_type']) unset($this->config);
        }
        $this->_mongo = $this->linkID;//mongo连接对象，连接句柄
        if(!$this->is_newmongodb){
            $this->selectDB($this->config["database"]);
        }
        // unset($this->config);
        return $this->linkID;
    }
    
    /**
     *  选择数据库，可以指定某个数据库
     */
    public function selectDB($dbname){
        $this->_dbName = $this->_mongo->selectDB($dbname);
    }
    
    /**
     *  选择集合，返回指定的集合
     *  切换数据库db和集合，默认是配置$config指定的数据库database
     */
    public function selectCollection($collectionName,$db=''){
        $db = !empty($db) ? $db : $this->_dbName;
        // print_r($this->coll_prefix."--");exit;
        $this->_collectionName = $this->coll_prefix.$collectionName;
        $this->_collection = $this->linkID->$db->selectCollection($this->_collectionName);
        // print_r($this->_collection);exit;
        unset($db);
        return $this->_collection;
    }
    
    /**
     * 释放查询结果
     * @access public
     */
    public function free() {
        $this->_cursor = null;
    }
    
    /**
     * 执行命令
     * @access public
     * @param array $command  指令
     * @return array
     */
    public function command($command=array()) {
        if(!isset($this->linkID)){//判断是否已经连接
            $this->connect();
        }
        $this->_mongo = $this->_linkID;
        $result   = $this->_mongo->command($command);
        if(!$result['ok']) {
             throw new Exception($result['errmsg']);
        }
        return $result;
    }
    
    /**
     * 执行语句
     * @access public
     * @param string $code  sql指令
     * @param array $args  参数
     * @return mixed
     */
    public function execute($code,$args=array()) {
        if(!isset($this->linkID)){//判断是否已经连接
            $this->connect();
        }
        $this->_mongo = !empty($this->_mongo) ? $this->_mongo : $this->linkID;
        $result   = $this->_mongo->execute($code,$args);
        if($result['ok']) {
            return $result['retval'];
        }else{
            throw_exception($result['errmsg']);
        }
    }
    
    /**
     * 关闭数据库
     * @access public
     */
    public function close() {
        if($this->linkID) {
            $this->linkID->close();
            $this->linkID = null;
            $this->_mongo = null;
            $this->_collection =  null;
            $this->_cursor = null;
        }
    }
    
    /**
     *  $options=array('table'=>'we_user','w'=>1,'wtimeout'=>500);
     *  table插入的表（文档），w是否确认写，wtimeout服务器等待确认时间
     */
    public function insert($data,$options=array(),$replace=false) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);//选择文档
        }
        unset($options['table']);
        try{
            //是否替换
            $result =  $replace ? $this->_collection->save($data,$options) : $this->_collection->insert($data,$options);
           if($result) {
               $_id    = $data['_id'];
                if(is_object($_id)) {
                    $_id = $_id->__toString();
                }
               $this->lastInsID    = $_id;
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     *  批量插入数据到集合中
     *  新增数据
	 * @param array $dataList 需要新增的数据 例如：array('title' => '1000', 'username' => 'xcxx')
	 * @param array $option 参数
     */
    public function insertAll($dataList,$options=array()) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        try{
           
           $result =  $this->_collection->batchInsert($dataList,$options);
           return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 生成下一条记录ID 用于自增非MongoId主键
     * @access public
     * @param string $pk 主键名
     * @return integer
     */
    public function mongo_next_id($pk) {
        try{
            $result   =  $this->_collection->find(array(),array($pk=>1))->sort(array($pk=>-1))->limit(1);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        $data = $result->getNext();
        return isset($data[$pk])?$data[$pk]+1:1;
    }
    
    /**
	 * 根据条件更新数据
 	 * @param array $query  条件 例如：array(('title' => '1000'))
 	 * @param array $data   需要更新的数据 例如：array(0=>array('title' => '1000', 'username' => 'xcxx'))
	 * @param array $options 参数
     * options其他7个参数：
     * upsert 为真的时，并搜索条件未被匹配，就创建一个新文档
     * multiple 为真时，所有搜索的文档都更新
     * fsync 为真时，数据将在更新结果返回前同步到磁盘，即使w已经设置了其他值，也会把选项w设置为0
     * w 设置为0表示更新操作不会得到确认，在使用复制集时，可设置为n,保证主服务器在将复制到n个节点后才确认该更新操作。1表示确认更新
     * j 布尔值为真，数据在更新结果返回之前写入到日志中，默认是假
     * wtimeout服务器等待确认时间,单位毫秒
     * timeout 指定客户端需要等待数据库返回的时间，单位毫秒
	 */
	public function update($query, $data, $options = array()) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        //限制只更新查询到的条件一条
        if(isset($options['limit']) && $options['limit'] == 1) {
            $options['multiple']   =   array("multiple" => false);
        }else{
            $options['multiple']   =   array("multiple" => true);
        }
        try{
          return $this->_collection->update($query, $data, $options);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
		
	}
    
    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * where 支持不同的表达式和条件
     * @return bool
     */
    public function update_by_where($data,$options) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        if(isset($options['where'])){
                $query  =  $this->parseWhere($options['where']);//查询条件
        }
        
        $set  =  $this->parseSet($data);
        try{
            if(isset($options['limit']) && $options['limit'] == 1) {
                $multiple   =   array("multiple" => false);
            }else{
                $multiple   =   array("multiple" => true);//更新多个
            }
            $result   = $this->_collection->update($query,$set,$multiple);
            return $result;
        } catch (Exception $e) {
           throw new Exception($e->getMessage());
        }
    }
    
    
    /**
	 * 保存数据，如果已经存在在库中，则更新，不存在，则新增
 	 * @param array $data 需要新增的数据 例如：array(0=>array('title' => '1000', 'username' => 'xcxx'))
	 * @param array $options 参数
     * options其他个参数：
     * fsync 为真时，数据将在更新结果返回前同步到磁盘，即使w已经设置了其他值，也会把选项w设置为0
     * w 设置为0表示更新操作不会得到确认，在使用复制集时，可设置为n,保证主服务器在将复制到n个节点后才确认该更新操作。1表示确认更新
     * j 布尔值为真，数据在更新结果返回之前写入到日志中，默认是假
     * wtimeout服务器等待确认时间,单位毫秒
     * timeout 指定客户端需要等待数据库返回的时间，单位毫秒
	 */
	public function save($data, $options = array()) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        try{
            return $this->_collection->save($data, $options);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
	}
    /**
     * 查找记录
     * @access public
     * @param array $options 表达式
     * @return iterator
     */
    public function select($options=array()) {
        if(!$this->is_newmongodb){
            if(isset($options['table'])) {
                $this->selectCollection($options['table']);
            }
            unset($options['table']);
            $query  =  $this->parseWhere($options['where']);
            $field =  $this->parseField($options['fields']);

            try{
                $_cursor   = $this->_collection->find($query,$field);
                if($options['order']) {
                    $order   =  $this->parseOrder($options['order']);
                    $_cursor =  $_cursor->sort($order);
                }
                if(isset($options['page'])) { // 根据页数计算limit
                    if(strpos($options['page'],',')) {
                        list($page,$length) =  explode(',',$options['page']);
                    }else{
                        $page    = $options['page'];
                    }
                    $page    = $page ? $page:1;
                    $length = isset($length)?$length:(is_numeric($options['limit'])?$options['limit']:20);
                    $offset  =  $length*((int)$page-1);
                    $options['limit'] =  $offset.','.$length;
                    // print_r($options);exit;
                }
                
                if(isset($options['limit'])) {
                    list($offset,$length) =  $this->parseLimit($options['limit']);
                    if(!empty($offset)) {
                        $_cursor =  $_cursor->skip(intval($offset));
                    }
                    $_cursor =  $_cursor->limit(intval($length));
                }
                
                $this->_cursor =  $_cursor;
                $resultSet  =  iterator_to_array($_cursor);
                return $resultSet;
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }else{
           return $this->newSelect($options);
        }
        
    }
    
    /**
     *  查找多条记录
     *  $fields查询的字段，是一个数组
     */
     public function find($options=array(),$fields=array()){
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        
        try{
            //var_dump($options);
            //var_dump($this->_collection->find()->getNext());die;
            
            
            //$cursor   = $this->_collection->find($options,$fields);
            $query = array();
            $fields=array();
            //$user = $mongo->find(array('table'=>'we_user','limit'=>1,'where'=>array('we_account_id'=>100102003)),array('we_account_id'));
            if(isset($options['where'])){
                $query  =  $this->parseWhere($options['where']);//查询条件
            }
            //当前游标
            $this->_cursor = $this->_collection->find($query,$fields);
            
            //排序是array('subscribe_time'=>1)//正序，-1反序
            //$this->_cursor->sort(array('subscribe_time'=>1));
            if (isset($options['sort'])) $this->_cursor->sort($options['sort']);
            if (isset($options['skip'])) $this->_cursor->skip($options['skip']);
            if (isset($options['limit'])) $this->_cursor->limit($options['limit']);
             //var_dump($this->_cursor->getNext());die;
            //var_dump(iterator_to_array($this->_cursor));die;
            return iterator_to_array($this->_cursor);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
     }
    /**
     * 查找某个记录
     * @access public
     * @param array $options 表达式
     *$mongo = new Hgmongodb($config);
        *$mongo->connect();
       * $user = $mongo->findOne(array('table'=>'we_user','limit'=>1,'where'=>array('we_account_id'=>100102034)),array('we_account_id'));
       ** //其他的条件
        *$user = $mongo->findOne(array('table'=>'we_user','limit'=>1,'where'=>array('we_account_id'=>array('neq',100102034))),array('we_account_id'));
     * @return array
     */
    public function findOne($options=array(),$fields=array()){
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        $query = array();
        if(isset($options['where'])){
            $query  =  $this->parseWhere($options['where']);
        }
        
        try{
            $result   = $this->_collection->findOne($query,$fields);
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     *  删除数据delete
     *  @param array $options 参数
     *  options其他个参数：
     *  justOne 为真，最多只有一个匹配条件的文档被删除
     *  fsync 为真时，数据将在更新结果返回前同步到磁盘，即使w已经设置了其他值，也会把选项w设置为0
     * w 设置为0表示更新操作不会得到确认，在使用复制集时，可设置为n,保证主服务器在将复制到n个节点后才确认该更新操作。1表示确认更新
     * j 布尔值为真，数据在更新结果返回之前写入到日志中，默认是假
     * wtimeout服务器等待确认时间,单位毫秒
     * timeout 指定客户端需要等待数据库返回的时间，单位毫秒
     */
     public function delete($query, $options = array()) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
		return $this->_collection->remove($query, $options);
	}
    /**多条件的删除
     *  支持条件表达式和where条件
     */
    public function remove($options=array()) {
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        $query = array();
        if(isset($options['where'])){
                $query  =  $this->parseWhere($options['where']);//查询条件
        }
        
        try{
            $result   = $this->_collection->remove($query);
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    /**
     *  清空集合中的数据clear
     *  options 参数集合名字
     */
     public function clear($options=array()){
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        try{
            $result   =  $this->_collection->drop();
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
	 * 数据统计
	 */
	public function coll_count() {
		return $this->_collection->count();
	}
    
    /**
     * 统计记录数
     * @access public
     * @param array $options 表达式
     * @return iterator
     */
    public function count($options=array()){
        if(isset($options['table'])) {
            $this->selectCollection($options['table']);
        }
        unset($options['table']);
        $query = array();
        if(isset($options['where'])){
           $query  =  $this->parseWhere($options['where']);//查询条件
        }
        try{
            $count   = $this->_collection->count($query);
            return $count;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     *  分组group
     */
    public function group($keys,$initial,$reduce,$options=array()){
        $this->_collection->group($keys,$initial,$reduce,$options);
    }
    /**
     * 取得集合中的字段信息
     * @access public
     * @return array
     */
    public function getFields($collection=''){
        if(!empty($collection)) {
            $this->selectCollection($collection);
        }
        try{
           
            $result   =  $this->_collection->findOne();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if($result) { // 存在数据则分析字段
            $info =  array();
            foreach ($result as $key=>$val){
                $info[$key] =  array(
                    'name'=>$key,
                    'type'=>getType($val),
                    );
            }
            return $info;
        }
        // 暂时没有数据 返回false
        return false;
    }
    /**
     * 取得当前数据库或指定数据库中的collection信息
     * @access public
     */
    public function getTables($db = ''){
        $db = !empty($db) ? $db : $this->_dbName;
        $list   = $db->listCollections();
        $info =  array();
        foreach ($list as $collection){
            $info[]   =  $collection->getName();
        }
        unset($db);
        return $info;
    }
    
    /**
     * set分析
     * @access protected
     * @param array $data
     * @return string
     */
    protected function parseSet($data) {
        $result   =  array();
        foreach ($data as $key=>$val){
            if(is_array($val)) {
                switch($val[0]) {
                    case 'inc':
                        $result['$inc'][$key]  =  (int)$val[1];
                        break;
                    case 'set':
                    case 'unset':
                    case 'push':
                    case 'pushall':
                    case 'addtoset':
                    case 'pop':
                    case 'pull':
                    case 'pullall':
                        $result['$'.$val[0]][$key] = $val[1];
                        break;
                    default:
                        $result['$set'][$key] =  $val;
                }
            }else{
                $result['$set'][$key]    = $val;
            }
        }
        return $result;
    }
    
    /**
     * order分析
     * @access protected
     * @param mixed $order
     * @return array
     */
    protected function parseOrder($order) {
        if(is_string($order)) {
            $array   =  explode(',',$order);
            $order   =  array();
            foreach ($array as $key=>$val){
                $arr  =  explode(' ',trim($val));
                if(isset($arr[1])) {
                    $arr[1]  =  $arr[1]=='asc'?1:-1;
                }else{
                    $arr[1]  =  1;
                }
                $order[$arr[0]]    = $arr[1];
            }
        }
        return $order;
    }
    
    /**
     * limit分析
     * @access protected
     * @param mixed $limit
     * @return array
     */
    protected function parseLimit($limit) {
        if(strpos($limit,',')) {
            $array  =  explode(',',$limit);
        }else{
            $array   =  array(0,$limit);
        }
        return $array;
    }
    
    /**
     * field分析
     * @access protected
     * @param mixed $fields
     * @return array
     */
    public function parseField($fields){
        if(empty($fields)) {
            $fields    = array();
        }
        if(is_string($fields)) {
            $fields    = explode(',',$fields);
        }
        return $fields;
    }
    
    /**
     * where分析
     * @access protected
     * @param mixed $where
     * @return array
     */
    public function parseWhere($where){
        $query   = array();
        foreach ($where as $key=>$val){
            if('_id' != $key && 0===strpos($key,'_')) {
                // 解析特殊条件表达式
                $query   = $this->parseThinkWhere($key,$val);
            }else{
                // 查询字段的安全过滤
                if(!preg_match('/^[A-Z_\|\&\-.a-z0-9]+$/',trim($key))){
                   throw new Exception ('ERROR_QUERY:'.$key);
                }
                $key = trim($key);
                if(strpos($key,'|')) {
                    $array   =  explode('|',$key);
                    $str   = array();
                    foreach ($array as $k){
                        $str[]   = $this->parseWhereItem($k,$val);
                    }
                    $query['$or'] =    $str;
                }elseif(strpos($key,'&')){
                    $array   =  explode('&',$key);
                    $str   = array();
                    foreach ($array as $k){
                        $str[]   = $this->parseWhereItem($k,$val);
                    }
                    $query   = array_merge($query,$str);
                }else{
                    $str   = $this->parseWhereItem($key,$val);
                    $query   = array_merge($query,$str);
                }
            }
        }
        return $query;
    }
    
    
    /**
     * 特殊条件分析
     * @access protected
     * @param string $key
     * @param mixed $val
     * @return string
     */
    protected function parseThinkWhere($key,$val) {
        $query   = array();
        switch($key) {
            case '_query': // 字符串模式查询条件
                parse_str($val,$query);
                if(isset($query['_logic']) && strtolower($query['_logic']) == 'or' ) {
                    unset($query['_logic']);
                    $query['$or']   =  $query;
                }
                break;
            case '_string':// MongoCode查询
                $query['$where']  = new MongoCode($val);
                break;
        }
        return $query;
    }
    
    /**
     * where子单元分析
     * @access protected
     * @param string $key
     * @param mixed $val
     *$user = $mongo->findOne(array('table'=>'we_user','limit'=>1,'where'=>array('we_account_id'=>array('neq',100102034))),array('we_account_id'));
     * @return array
     *$user = $mongo->findOne(array('table'=>'we_user','limit'=>1,'where'=>array('remark_name'=>array('like','布布'))),array('we_account_id'));
     */
    protected function parseWhereItem($key,$val) {
        $query   = array();
        
        if(is_array($val)) {
            if(is_string($val[0])) {
                $con  =  strtolower($val[0]);
                if(in_array($con,array('neq','ne','gt','egt','gte','lt','lte','elt'))) { // 比较运算
                    $k = '$'.$this->comparison[$con];
                    $query[$key]  =  array($k=>$val[1]);
                }elseif('like'== $con){ // 模糊查询 采用正则方式
                    $query[$key]  =  new MongoRegex("/".$val[1]."/");
                }elseif('mod'==$con){ // mod 查询
                    $query[$key]   =  array('$mod'=>$val[1]);
                }elseif('regex'==$con){ // 正则查询
                    $query[$key]  =  new MongoRegex($val[1]);
                }elseif(in_array($con,array('in','nin','not in'))){ // IN NIN 运算
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    $k = '$'.$this->comparison[$con];
                    $query[$key]  =  array($k=>$data);
                }elseif('all'==$con){ // 满足所有指定条件
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    $query[$key]  =  array('$all'=>$data);
                }elseif('between'==$con){ // BETWEEN运算
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    $query[$key]  =  array('$gte'=>$data[0],'$lte'=>$data[1]);
                }elseif('not between'==$con){
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    $query[$key]  =  array('$lt'=>$data[0],'$gt'=>$data[1]);
                }elseif('exp'==$con){ // 表达式查询
                    $query['$where']  = new MongoCode($val[1]);
                }elseif('exists'==$con){ // 字段是否存在
                    $query[$key]  =array('$exists'=>(bool)$val[1]);
                }elseif('size'==$con){ // 限制属性大小
                    $query[$key]  =array('$size'=>intval($val[1]));
                }elseif('type'==$con){ // 限制字段类型 1 浮点型 2 字符型 3 对象或者MongoDBRef 5 MongoBinData 7 MongoId 8 布尔型 9 MongoDate 10 NULL 15 MongoCode 16 32位整型 17 MongoTimestamp 18 MongoInt64 如果是数组的话判断元素的类型
                    $query[$key]  =array('$type'=>intval($val[1]));
                }else{
                    $query[$key]  =  $val;
                }
                return $query;
            }
        }
        $query[$key]  =  $val;
        return $query;
    }
    
	/**
     * 数据库错误信息
     * @access public
     * @return string
     */
    public function error() {
        $this->error = $this->_mongo->lastError();
        return $this->error;
    }
    
	/**
	 * 错误信息
	 */
	public function dberror() {
		return $this->_dbName->lastError();
	}
	
	/**
	 * 获取集合对象
	 */
	public function getCollection() {
		return $this->_collection;
	}
	
	/**
	 * 获取DB对象
	 */
	public function getDb() {
		return $this->_dbName;
	}



    

     /**
     ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
     */
    

    /**
     * php 7.0 mongodb query
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    function newSelect($params){
        // 查询数据1
        $this->_mongo = new MongoDB\Driver\Manager($this->host_);
        $query        = new MongoDB\Driver\Query($params["filter"], $params["queryWriteOps"]);
        $cursor       = $this->_mongo->executeQuery($params['db_table'], $query);
        
        $list         = array();
        foreach ($cursor as $k => $v) {
            $list[]  = $this->objectToArray($v);
        }
        unset($query);
        unset($cursor);
        unset($this->_mongo);
        return $list;
    }


 


    /**
     * 插入数据
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function newInsert($params){
        // 插入数据
        $this->_mongo = new MongoDB\Driver\Manager($this->host_);
        $bulk         = new MongoDB\Driver\BulkWrite();
        foreach ($params["datas"] as $k => $v) {
            $bulk->insert($v);
        }
        $row = $this->_mongo->executeBulkWrite($params["db_table"], $bulk);
        unset($this->_mongo);
        return $row;
    }


    function objectToArray($object) {
        $object =  json_decode( json_encode( $object),true);
        return  $object;
    }
}