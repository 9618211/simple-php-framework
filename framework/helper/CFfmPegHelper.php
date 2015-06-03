<?php
/**
 * 依赖于系统安装ffmpeg软件
 * helper-----CFfmPegHelper
 * Class Ffmpeg
 */
class CFfmPegHelper{
	private static $_instance;

	static function getInstance()
	{
		if (null == self::$_instance){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 获取视频信息
	 * @param $file
	 * @return array|bool
	 */
	function getVideoInfo($file){
		$re = array();
		exec("ffmpeg -i {$file} 2>&1", $re);
		$info = implode("\n", $re);

		if(preg_match("/Invalid data/i", $info)){
			return false;
		}

		$match = array();
		$width = 0; $height = 0; $duration = 0; $bitrate = 0;
		preg_match("/\d{2,}x\d+/", $info, $match);
		if (!empty($match[0])){
			list($width, $height) = explode("x", $match[0]);
		}
		preg_match("/Duration:(.*?),/", $info, $match);
		if (!empty($match[1])){
			$duration = date("H:i:s", strtotime($match[1]));
			preg_match("/bitrate:(.*kb\/s)/", $info, $match);
			if (!empty($match[1])){
				$bitrate = $match[1];
			}
		}
		if(!$width && !$height && !$duration && !$bitrate){
			return false;
		}else{
			return array(
				"width" => $width,
				"height" => $height,
				"duration" => $duration,
				"bitrate" => $bitrate,
			);
		}
	}

	/**
	 * 截图
	 * @param $video_file
	 * @param $image_file
	 * @param int $width
	 * @param int $height
	 * @param int $offset_sec
	 * @return bool
	 */
	function createImage($video_file, $image_file, $width=0, $height=0, $offset_sec=0){
		$re = array();
		$info = false;
		if($width && !$height){
			if(!$info){
				$info = $this->getVideoInfo($video_file);
			}
			$width = min($width, $info['width']);
			$height = intval($width / $info['width'] * $info['height']);
		}
		if(!$width && $height){
			if(!$info){
				$info = $this->getVideoInfo($video_file);
			}
			$height = min($height, $info['height']);
			$width = intval($height / $info['height'] * $info['width']);
		}
		if($offset_sec){
			if(!$info){
				$info = $this->getVideoInfo($video_file);
			}
			$max_sec = strtotime($info['duration']) - strtotime(date("Y-m-d"));
			$offset_sec = min($offset_sec, $max_sec);
			$ss = " -ss {$offset_sec} ";
		}

		if($width && $height){
			$s = " -s {$width}x{$height} ";
		}
		$com = "ffmpeg -i {$video_file} -y -f image2 -vframes 1 {$ss} {$s} {$image_file} 2>&1";
		exec($com, $re);
		$r = array_pop($re);
		preg_match("/video:(\d*)kB/i", $r, $match);
		if(intval($match[1])){
			return true;
		}else{
			return false;
		}
	}
}