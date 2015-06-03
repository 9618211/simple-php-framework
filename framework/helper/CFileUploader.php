<?php
/**
 * helper-----文件上传类
 * Class CFileUploader
 */
class CFileUploader
{
	private static $_instance;
	private $mineTypes = array(
		'image' => array(
			'image/jpeg',
			'image/pjpeg',
			'image/jpg',
			'image/png',
			'image/x-png'
		),
		'video' => array(
			'video/3gp',
			'video/3gpp',
			'video/avi',
			'video/x-msvideo',
			'video/mp4',
			'video/mpeg4',
			'video/mpeg',
			'video/mpg',
			'video/x-flv',
			'video/flv',
			'video/x-wmv',
			'video/wmv',
			'application/octet-stream',
			'video/rmvb',
			'video/mkv',
		),

		'audio' => array(
			'audio/mp3',
			'audio/wma',
			'audio/wav',
			'audio/mod',
			'audio/ra',
			'audio/cd',
			'audio/md',
			'audio/asf',
			'audio/aac',
			'audio/mpeg'
		)
	);
	private $_config = array('path' => '');
	function __construct($config=array())
	{
		$this->_config = array_merge($this->_config, $config);
		if (!is_dir($this->_config['path'])){
			mkdir($this->_config['path']); //只需要读写权限即可
		}
	}

	function getInstance($config=array())
	{
		if (null == self::$_instance){
			self::$_instance = new self($config);
		}
		return self::$_instance;
	}

	/**
	 * @param $sourceFile
	 * @param string $destFile
	 * @param string $fileIden
	 * @param array $size
	 * @return array
	 */
	function upload($sourceFile, $destFile='', $fileIden = 'image', $size = array('min' => 0, 'max' => 31457280))
	{
		if ($sourceFile['size'] <= $size['min']){
			return array('code' => -3, 'msg' => '文件太小');
		}
		if ($sourceFile['size'] > $size['max']){
			return array('code' => -4, 'msg' => '文件太大');
		}
		if (!$this->checkFileTypeIlegal($sourceFile, $fileIden)){
			return array('code' => -1, 'msg' => '文件类型不合法');
		}
		if (!$destFile){
			$fileName = $this->generateFileName($sourceFile['name']);
			$destFile = $this->_config['path'].'/'.$fileName;
		}
		if($this->copyFile($sourceFile['tmp_name'], $destFile, $fileIden)){
			return array('code' => 0, 'msg' => '上传成功','fileName' => isset($fileName)?$fileName:$destFile);
		}
		return array('code' => -2, 'msg' => '上传失败');
	}

	/**
	 * 生成一个MD5的文件名
	 * @param $fileName
	 * @return string
	 */
	function generateFileName($fileName)
	{
		return md5(time()).'.'.$this->getExtensionName($fileName);
	}

	/**
	 * 检查文件类型合法性
	 */
	function checkFileTypeIlegal($tmpFile, $fileIden = 'image')
	{
		if (empty($this->mineTypes[$fileIden]) || !in_array($tmpFile['type'], $this->mineTypes[$fileIden])){
			return false;
		}
		$extName = $this->getExtensionName($tmpFile['name']);
		$flag = false;
		foreach($this->mineTypes[$fileIden] as $type){
			if ($extName === substr($type, strlen($fileIden)+1)){
				$flag = true;
				break;
			}
		}
		return $flag;
	}

	/**
	 * 获取文件扩展名
	 * @param $name
	 * @return string
	 */
	function getExtensionName($name)
	{
		if(($pos=strrpos($name,'.'))!==false)
			return (string)substr($name,$pos+1);
		else
			return '';
	}

	/**
	 * 复制文件
	 * @param $sourceFile
	 * @param $destFile
	 * @param string $fileIden
	 * @return bool|void
	 */
	function copyFile($sourceFile, $destFile, $fileIden='image')
	{
		switch($fileIden){
			case 'image':
				return $this->copyImage($sourceFile, $destFile);
			break;
			default:
				return move_uploaded_file($sourceFile, $destFile);
				break;
		}
	}

	/**
	 * 复制图片
	 * @param $sourceFile
	 * @param $descFile
	 * @return bool
	 */
	function copyImage($sourceFile, $descFile)
	{
		$extName = $this->getExtensionName($descFile);
		$createFunc = ($extName == 'jpg') ? 'imagecreatefromjpeg':'imagecreatefrom'.$extName;
		$src = @$createFunc($sourceFile);
		if (!$src){
			return false;
		}
		$fileSize = getimagesize($sourceFile);
		$dest = imagecreatetruecolor($fileSize[0], $fileSize[1]);
		imagecopy($dest, $src, 0, 0, 0, 0, $fileSize[0], $fileSize[1]);
		$outputFunc = ($extName == 'jpg') ? 'imagejpeg':'image'.$extName;
		$outputFunc($dest, $descFile);
		return true;
	}
}