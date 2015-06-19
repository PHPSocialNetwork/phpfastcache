<?php defined('BASEPATH') OR exit('No direct script access allowed.');

// 递归创建目录
if(!function_exists('mkdir_r')){
	function mkdir_r($dirName, $rights=0777){ 
		$dirs = explode('/', $dirName); 
		$dir=''; 
		foreach ($dirs as $part) { 
			$dir.=$part.'/'; 
			if (!is_dir($dir) && strlen($dir)>0) 
				mkdir($dir, $rights); 
		} 
	} 
}

// 远程获取图片数据
if(!function_exists('remoteimg')){
	function remoteimg($imgurl){
		$CI = &get_instance();
		$CI->load->library('curl');
		$content = $CI->curl->simple_get($imgurl);
		$info = $CI->curl->info;
		$info['content'] = $content;
		return $info;
	}
}
// 远程图片本地化
if(!function_exists('remote2local')){
	function remote2local($content){
		
		$CI = &get_instance();
		$base_url = base_url();
		include APPPATH . 'helpers/simple_html_dom.php';
		$dom = str_get_html($content);
		foreach($dom->find('img') as $img){
			// var_dump('222');
			// 微信
			$src = $img->getAttribute('data-src');
			if($src){
				preg_match('/http:\/\/mmbiz.qpic.cn\/mmbiz\/(.+)\/0/', $src, $file);
				$filename = $file[1];
				$filename = $filename . '.' . $img->getAttribute('data-type');
				$response = remoteimg($src);
				if($response && $response['http_code'] == '200'){
					$img->removeAttribute('data-src');
					$filename1 = FCPATH . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename;
					mkdir_r(dirname($filename1));
					file_put_contents($filename1, $response['content']);
					$img->src = $base_url . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename;
				}
			}
			// 普通图片
			$src1 = $img->src;
			// 如果是站内的图片，不本地化
			if(strpos($src1, $base_url) !== FALSE) continue;
			if($src1){
				$src2 = strtolower($src1);
				// if(strpos($src2, '.jpg') === FALSE && strpos($src2, '.jpeg') === FALSE && strpos($src2, '.gif') === FALSE && strpos($src2, '.bmp') === FALSE && strpos($src2, '.png') === FALSE){
					$response = remoteimg($src1);
					if($response && $response['http_code'] == '200'){
						$content_type = $response['content_type'];
						$filename3 = md5($src1);
						switch($content_type){
							case 'image/jpeg':
							case 'image/jpg':
							case 'image/pjpeg':
								$filename1 =  FCPATH . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.jpg'; 
								$filename2 =  $base_url . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.jpg';
								break;
							case 'image/gif':
								$filename1 = FCPATH . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.gif'; 
								$filename2 = $base_url . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.gif'; 
								break;
							case 'image/png':
							case 'image/x-png':
								$filename1 = FCPATH . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.png'; 
								$filename2 = $base_url . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.png'; 
								break;
							case 'image/bmp':
								$filename1 = FCPATH . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.bmp';
								$filename2 = $base_url . 'resource/uploads/' . date('Y') . '/' . date('md') . '/' . $filename3 . '.bmp';
								break;
						}
						mkdir_r(dirname($filename3));
						file_put_contents($filename1, $response['content']);
						$img->src = $filename2;						
					}
				// }
			}
			
		}
		$content = $dom->save();
		return $content;
	}
}
