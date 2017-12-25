<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Storage\Driver;
use Think\Storage;
// 本地文件写入存储类
class File extends Storage{

    private $contents=array();

    /**
     * 架构函数
     * @access public
     */
    public function __construct() {
    }

    /**
     * 文件内容读取
     * @access public
     * @param string $filename  文件名
     * @return string     
     */
    public function read($filename,$type=''){
        return $this->get($filename,'content',$type);
    }

    /**
     * 文件写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  文件内容
     * @return boolean         
     */
    public function put($filename,$content,$type=''){
        $dir         =  dirname($filename);
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        if(false === file_put_contents($filename,$content)){
            E(L('_STORAGE_WRITE_ERROR_').':'.$filename);
        }else{
            $this->contents[$filename]=$content;
            return true;
        }
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  追加的文件内容
     * @return boolean        
     */
    public function append($filename,$content,$type=''){
        if(is_file($filename)){
            $content =  $this->read($filename,$type).$content;
        }
        return $this->put($filename,$content,$type);
    }

    /**
     * 加载文件
     * @access public
     * @param string $filename  文件名
     * @param array $vars  传入变量
     * @return void        
     */
    public function load($_filename,$vars=null){
        if(!is_null($vars)){
            extract($vars, EXTR_OVERWRITE);
        }
        include $_filename;
    }

    /**
     * 文件是否存在
     * @access public
     * @param string $filename  文件名
     * @return boolean     
     */
    public function has($filename,$type=''){
        return is_file($filename);
    }

    /**
     * 文件删除
     * @access public
     * @param string $filename  文件名
     * @return boolean     
     */
    public function unlink($filename,$type=''){
        unset($this->contents[$filename]);
        return is_file($filename) ? unlink($filename) : false; 
    }

    /**
     * 读取文件信息
     * @access public
     * @param string $filename  文件名
     * @param string $name  信息名 mtime或者content
     * @return boolean     
     */
    public function get($filename,$name,$type=''){
        if(!isset($this->contents[$filename])){
            if(!is_file($filename)) return false;
           $this->contents[$filename]=file_get_contents($filename);
        }
        $content=$this->contents[$filename];
        $info   =   array(
            'mtime'     =>  filemtime($filename),
            'content'   =>  $content
        );
        return $info[$name];
    }


	/**
	 * 获取目录下子目录及文件列表
	 * @param string $dirUrl 要扫描的目录地址。
	 * @return array
	*/
	public function getList($dirUrl){
		$dirUrl=rtrim($dirUrl,'/');
		if(!is_dir($dirUrl)){
			return false;
		}
		$fileList=array();
		$dirList=array();
		$objects=scandir($dirUrl);
		foreach($objects as $obj){
			if($obj=='.'||$obj=='..'){
				continue;
			}
			$fileUrl=$dirUrl.'/'.$obj;
			if(is_file($fileUrl)){
				$filesize=filesize($fileUrl);
				$fileupdate=fileatime($fileUrl);
				array_push($fileList,array('Name'=>$obj,'fullName'=>$fileUrl,'length'=>$filesize,'uploadTime'=>$fileupdate));
			}
			if(is_dir($fileUrl)){
				array_push($dirList,array('name'=>$obj,'fullName'=>$fileUrl));
			}
		}
		return array('dirNum'=>count($dirList),'fileNum'=>count($fileList),'dirs'=>$dirList,'files'=>$fileList);
	}

	/**
	 * 移动指定文件
	 * @param string $fileUrl 要移动的文件地址。
	 * @param string $aimUrl 移动后的新地址。
	 * @param boolen $overWrite 是否覆盖已存在的文件。
	 * @return boolen
	*/
	public function moveFile($fileUrl, $aimUrl, $overWrite = true) {
		if (!is_file($fileUrl)) {
			return false;
		}
		if (is_file($aimUrl) && $overWrite == false) {
			return false;
		} elseif (is_file($aimUrl) && $overWrite == true) {
			$this->unlinkFile($aimUrl);
		}
		$aimDir = dirname($aimUrl);
		$this->createDir($aimDir);
		rename($fileUrl, $aimUrl);
		return true;
	}

	/**
	 * 清空目录
	 * @param string $dirUrl 要清空的目录地址。
	 * @return boolen
	*/
	public function clearDir($dirUrl){
		$dirUrl=rtrim($dirUrl,'/');
		if(!is_dir($dirUrl)){
			return false;
		}
		$infos=$this->getList($dirUrl);
		$result=true;
		foreach ($infos['files'] as $file){
			$result=$this->unlinkFile($file['fullName']);
		}
		foreach ($infos['dirs'] as $dir){
			$result=$this->unlinkDir($dir['fullName']);
		}
		return $result;
	}

	/**
	 * 删除目录
	 * @param string $dirUrl 要删除的目录地址。
	 * @return boolen
	*/
	public function unlinkDir($dirUrl){
		$dirUrl=rtrim($dirUrl,'/');
		if(!is_dir($dirUrl)){
			return false;
		}
		$infos=$this->getList($dirUrl);
		foreach ($infos['files'] as $file){
			$this->unlinkFile($file['fullName']);
		}
		foreach ($infos['dirs'] as $dir){
			$this->unlinkDir($dir['fullName']);
		}
		return rmdir($dirUrl);
	}

}
