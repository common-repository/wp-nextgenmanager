<?php
$ngg_options = null;

function dbcon(){
	$con = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$con)return null;
	else {
		mysql_select_db(DB_NAME, $con);
		return $con;
	}	
}

function dbquery($query, $vars = null){
	//error_log("SB: ".$query);
	if($vars == null)$vars = array();
	$con = dbcon();
	$p1 = 0;
	for($i = 0; $i < count($vars) && $p1 < strlen($query); $i ++){
		$s = mysql_real_escape_string($vars[$i], $con);
		if(!is_numeric($s))$s = "'".$s."'";
		            //$query = str_replace("?", $vars[$i], $query, 1);
		$p1 = strpos($query, "?", $p1);
		$query = substr($query, 0, $p1).$s.substr($query, $p1+1);
		$p1 += strlen($s);
	}
	//echo $query;
	
	$ret = mysql_query($query, $con);
	//error_log("DB: ".$query);
	//error_log("el2:".$query);
	//if(mysql_error($con) != "") error_log("el:".$query." ".mysql_error($con));
	return $ret;
}

function dbline($result){
	return mysql_fetch_array($result);
}

function wplogin($u, $p){
	$sql = "select `id`, `user_pass` from `".DB_PREFIX."users` where `user_login` = ?;";
	$rs = dbquery($sql, array($u));
	if($line = dbline($rs)){
		//return $line["id"];
		$ph = new PasswordHash(8, true);
		//error_log($ph->HashPassword($p));
		if($ph->CheckPassword($p, $line["user_pass"]))return $line["id"]; 
	}
	return false;
}

function isNgmPluginActive(){
	$sql = "select `option_value` from `".DB_PREFIX."options` where `option_name` = 'active_plugins';";
	$rs = dbquery($sql);
	if($line = dbline($rs)){
		//error_log("ngg options".$line["option_value"]);
		$plugins = $line["option_value"];
		if(strpos($plugins, "wp_nextgenmanager.php") !== false)return true;
	}
	return false;
}

function getNgOption($n){
	if($ngg_options == null){
		$sql = "select `option_value` from `".DB_PREFIX."options` where `option_name` = 'ngg_options';";
		$rs = dbquery($sql);
		if($line = dbline($rs)){
			//error_log("ngg options".$line["option_value"]);
			$ngg_options = unserialize($line["option_value"]);	
		}
	}
	
	if(isset($ngg_options[$n]))return $ngg_options[$n];
	else return "";
}

function getImageList($gid = null){
	$sql = "";
	if($gid != null)$sql = "select * from `".DB_PREFIX."ngg_pictures` where `galleryid` = ?;";
	else $sql = "select * from `".DB_PREFIX."ngg_pictures`;";
	$ret = array();
	$rs = dbquery($sql, array($gid));
	while($line = dbline($rs))array_push($ret, $line);
	return $ret;
}

function getGalleryList(){
	$sql = "select * from `".DB_PREFIX."ngg_gallery`";
	$rs = dbquery($sql);
	$ret = array();
	while($line = dbline($rs)){

		array_push($ret, $line);
		//$ret .= $line["gid"]."\t".str_replace("\t", "    ", $line["name"])."\t".str_replace("\t", "    ", $line["title"])."\t\r\n";
	}
	return $ret;
}

function saveGallery($gid, $name, $title){
	$res = true;
	if($gid == "-1"){
		$ngp = getNgOption("gallerypath");
		if($ngp != ""){
			$gp = $ngp.strtolower(str_replace(" ", "", $name));
			$tmpgp = $gp;
			$count = 0;
			while(file_exists(ABSPATH.$tmpgp)){
				$tmpgp = $gp.$count;
				$count ++;
			}
			$gp = $tmpgp;
			mkdir(ABSPATH.$gp, 0777, true);
			chmod(ABSPATH.$gp, 0777);
			mkdir(ABSPATH.$gp."/thumbs", 0777, true);
			chmod(ABSPATH.$gp."/thumbs",0777);
			$sql = "insert into `".DB_PREFIX."ngg_gallery`(`name`, `title`, `path`) values(?, ?, ?);";
			$res = dbquery($sql, array($name, $title, $gp));
		}
		else {
			//error_log("error geting gallery path from: ".print_r($ngg_options, true));
			return "Could not get gallery path.";	
		}
	}
	else{
		$sql = "update `".DB_PREFIX."ngg_gallery` set `name` = ?, title = ? where `gid` = ?;";
		$res = dbquery($sql, array($name, $title, $gid));
	}
	if (!$res) {
	    return 'Invalid query: ' . mysql_error();
	}
	return true;
}

function saveImage($pid, $title, $desc, $gid){
	$res = true;
	if($pid == -1){
		$gallery = null;
		$sql = "select * from `".DB_PREFIX."ngg_gallery` where `gid`=?";
		$rs = dbquery($sql, array($gid));
		if($line = dbline($rs))$gallery = $line;
		else return false;
		
		$dt = date("Y-m-d H:i:s", strtotime("now"));
		if(isset($_REQUEST["datetime"]))$dt = date("Y-m-d H:i:s", $_REQUEST["datetime"]); 
		
		$ngp = getNgOption("gallerypath");
		
		$filename = null;
		$fileval = null;
		
		if(count($_FILES) > 0){
			foreach ($_FILES as $key => $val){
				error_log("Found file with key: $key and name:".$_FILES[$key]["name"]);
				$filename = $_FILES[$key]["name"];
				$fileval = $key;
				//break;
			}
		}
		
		$filename = str_replace(" ", "_", $filename);
		
		$newfilename = $filename;
		$count = 0;
		while(file_exists(ABSPATH.$gallery["path"]."/".$newfilename)){
			$newfilename = $count.$filename;
			$count ++;
		}
		//error_log("Moving file: ".$_FILES[$fileval]['tmp_name']. " with fileval:".$fileval." to: ".ABSPATH.$gallery["path"]."/".$newfilename);
		if(!move_uploaded_file($_FILES[$fileval]['tmp_name'], ABSPATH.$gallery["path"]."/".$newfilename))error_log("Error moving ".$_FILES[$fileval]['tmp_name']." to ".ABSPATH.$gallery["path"]."/".$newfilename) ;
		$sql = "insert into `".DB_PREFIX."ngg_pictures` (`galleryid`, `filename`, `alttext`, `description`, `imagedate`) values(?, ?, ?, ?, ?);";
		$res = dbquery($sql, array($gid, $newfilename, $title, $desc, $dt));
		//error_log($res);
		//Test if imagemagic convert is available
		$textoutput = array();
		$com = "convert";
		exec($com, $textoutput);
		
		//error_log("Convert output: ".implode("",$textoutput));
		if(strpos(implode("",$textoutput), "ImageMagick") !== false){
		
			//Resizing Image
			$imgWidth = getNgOption("imgWidth");
			$imgHeight = getNgOption("imgHeight");
			
			$com = "convert ".ABSPATH.$gallery["path"]."/".$newfilename." -resize ".$imgWidth."x".$imgHeight." ".ABSPATH.$gallery["path"]."/".$newfilename;
			//error_log($com);
			exec(stripcslashes($com));
			
			//Creating Thumb			
			$twidth = getNgOption("irWidth");
			$theight = getNgOption("irHeight");
			
			$com = "convert ".ABSPATH.$gallery["path"]."/".$newfilename." -resize ".$twidth."x".$theight." ".ABSPATH.$gallery["path"]."/thumbs/thumbs_".$newfilename;
			//error_log($com);
			exec(stripcslashes($com));
		}
	}
	else{
		$sql = "update `".DB_PREFIX."ngg_pictures` set `alttext` = ?, `description` = ? where `pid` = ?;";
		$res = dbquery($sql, array($title, $desc, $pid));
	}
	
	if (!$res) {
	    return 'Invalid query: ' . mysql_error();
	}
	return true;
}

function getResizedImage($img){
	$info = GetImageSize(ABSPATH.$img);
	
	$width = $info[0];
	$height = $info[1];
	$mime = $info['mime'];
	
	$nwidth = 800;
	$nheight = ($height/($width/$nwidth));
	
	$image_create_func = '';
	$image_save_func = '';
	$new_image_ext = '';
	
	$type = substr(strrchr($mime, '/'), 1);
	switch ($type)
	{
		case 'jpeg':
			$image_create_func = 'ImageCreateFromJPEG';
			$image_save_func = 'ImageJPEG';
			$new_image_ext = 'jpg';
		break;
		
		case 'png':
			$image_create_func = 'ImageCreateFromPNG';
			$image_save_func = 'ImagePNG';
			$new_image_ext = 'png';
		break;
		
		case 'bmp':
			$image_create_func = 'ImageCreateFromBMP';
			$image_save_func = 'ImageBMP';
			$new_image_ext = 'bmp';
		break;
		 
		case 'gif':
			$image_create_func = 'ImageCreateFromGIF';
			$image_save_func = 'ImageGIF';
			$new_image_ext = 'gif';
		break;
		 
		case 'vnd.wap.wbmp':
			$image_create_func = 'ImageCreateFromWBMP';
			$image_save_func = 'ImageWBMP';
			$new_image_ext = 'bmp';
		break;
		 
		case 'xbm':
			$image_create_func = 'ImageCreateFromXBM';
			$image_save_func = 'ImageXBM';
			$new_image_ext = 'xbm';
		break;
		 
		default:
			$image_create_func = 'ImageCreateFromJPEG';
			$image_save_func = 'ImageJPEG';
			$new_image_ext = 'jpg';
	}
	
	
	$inimg = $image_create_func(ABSPATH.$img);
	$image_c = ImageCreateTrueColor($nwidth, $nheight);		 
	//$new_image = $image_create_func($this->image_to_resize);		 
	ImageCopyResampled($image_c, $inimg, 0, 0, 0, 0, $nwidth, $nheight, $width, $height);
		
	header('Content-Type: '.$mime);
	$image_save_func($image_c);	
	
	//echo "t";
} 
?>