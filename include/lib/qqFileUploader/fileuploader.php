<?php
/**
 * ファイルアップロードクラス
 *
 * valums file-uploaderをMagic3用に改造
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2013 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: fileuploader.php 6172 2013-07-16 02:22:39Z fishbone $
 * @link       http://www.magic3.org
 */
/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;			// アップロード可能なファイルの最大サイズ
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = null)
	{
		global $gSystemManager;
		
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
        $this->allowedExtensions = $allowedExtensions;
		
		// アップロードファイルの最大サイズはPHPの設定に合わせる
        //$this->sizeLimit = $sizeLimit;
		$maxFileSize = $gSystemManager->getMaxFileSizeForUpload(true);
		if (empty($sizeLimit) || $sizeLimit > $maxFileSize){
			$this->sizeLimit = $maxFileSize;
		} else {
			$this->sizeLimit = $sizeLimit;
		}

        //$this->checkServerSettings();       // サーバ環境チェックなし
        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
/*    private function checkServerSettings(){
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }*/
    
/*    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }*/
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory)
	{
		global $gSystemManager;
		
		$uploadDirectory = rtrim($uploadDirectory, '/\\');
		
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0){
			// IE8では、アップロードファイルのサイズが「upload_max_filesize」よりも大きいと0が返る
			if (isset($_SERVER["CONTENT_LENGTH"])){
				//$displayMaxSize = ini_get('upload_max_filesize');
				//$errorMsg = 'File size is too large. ' . $_SERVER['CONTENT_LENGTH'] . ' bytes exceeds upload_max_filesize(' . $displayMaxSize . ' bytes).';
				$displayMaxSize = $gSystemManager->getMaxFileSizeForUpload();
				$errorMsg = 'File size is too large. ' . $_SERVER['CONTENT_LENGTH'] . ' bytes exceeds max upload filesize(' . $displayMaxSize . ' bytes).';
			} else {
				$errorMsg = 'File is empty';
			}
			return array('error' => $errorMsg);
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
		$filename = strtr($this->file->getName(), ' ', '_');	// 半角スペースをアンダーバーに変換(Firefox対応)
        $pathinfo = pathinfo($filename);
//        $filename = $pathinfo['filename'];
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        /*if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }*/
		// ファイルが存在する場合はエラー
		$fileId = md5(uniqid(rand(), true));
		$newFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileId;
		if (file_exists($newFilePath)){
			return array('error' => 'File already exists.');
		}
        
        if ($this->file->save($newFilePath)){
			$fileInfo = array('fileid' => $fileId, 'filename' => $filename, 'path' => $newFilePath, 'size' => $size);
            return array('success' => true, 'file' => $fileInfo);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
    }
}
