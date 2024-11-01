<?php
	define('BASE_PATH', dirname(__FILE__) . '/');
	
	$plugin_version = 0.7;

	include_once BASE_PATH.'../../../wp-config.php';
	include_once BASE_PATH.'../../../wp-includes/class-phpass.php';
	include_once BASE_PATH. 'lib/func.php';
	
	define("DB_PREFIX", $table_prefix);
	
	$ret = array();
	if(!isNgmPluginActive()){
		$ret["status"] = "ERROR";
		$ret["status_message"] = "Plugin is not activated, go to plugin manager in wordpress to activate.";
	}
	elseif(isset($_REQUEST["ngma"])){
		if(isset($_REQUEST["wpu"]) || isset($_REQUEST["wpp"])){
			$id = wplogin($_REQUEST["wpu"], $_REQUEST["wpp"]);
			if($id === false){
				//echo "STATUS:ERROR:no login";
				$ret["status"] = "ERROR";
				$ret["status_message"] = "No login";
			}
			else{
				//echo $id;
				if($_REQUEST["ngma"] == "testlogin"){
					//echo "STATUS:ERROR:no login";
					$ret["status"] = "OK";
					$ret["status_message"] = "Login ok";
				}
				elseif($_REQUEST["ngma"] == "getgallerylist"){
					//echo "STATUS:ERROR:no login";
					$ret["status"] = "OK";
					$ret["status_message"] = "Getting gallerys";
					//echo "STATUS:OK:Getting list\r\n";
					$ret["gallerys"] = getGalleryList();
				}
				elseif($_REQUEST["ngma"] == "gallerysave"){
					if(isset($_REQUEST["gid"]) && isset($_REQUEST["name"]) && isset($_REQUEST["title"])){
						$res = saveGallery($_REQUEST["gid"], $_REQUEST["name"], $_REQUEST["title"]);
						if($res === true){
							//echo "STATUS:OK:Gallery saved.\r\n";
							$ret["status"] = "OK";
							$ret["status_message"] = "Gallery saved";
						}
						else{
							$ret["status"] = "ERROR";
							$ret["status_message"] = $res;
							//echo "STATUS:ERROR:$res.\r\n";
						}
					}
					else{
						$ret["status"] = "ERROR";
						$ret["status_message"] = "Did not provide gallery id, name and title";
						//echo "STATUS:ERROR:Did not provide gallery id, name and title.\r\n";
					}
				}
				elseif($_REQUEST["ngma"] == "listimages"){
					if(isset($_REQUEST["gid"])){
						$ret["status"] = "OK";
						$ret["status_message"] = "Getting images";
						
						$ret["images"] = getImageList($_REQUEST["gid"]);
					}
					else{
						$ret["status"] = "OK";
						$ret["status_message"] = "Getting all images for sync";
						
						$ret["images"] = getImageList($_REQUEST["gid"]);
					}
				}
				elseif($_REQUEST["ngma"] == "imagesave"){
					if(isset($_REQUEST["pid"]) && isset($_REQUEST["alttext"]) && isset($_REQUEST["description"]) && isset($_REQUEST["galleryid"])){
						$res = saveImage($_REQUEST["pid"], urldecode($_REQUEST["alttext"]), urldecode($_REQUEST["description"]), $_REQUEST["galleryid"]);
						if($res === true){
							//echo "STATUS:OK:Gallery saved.\r\n";
							$ret["status"] = "OK";
							$ret["status_message"] = "Image saved";
						}
						else{
							$ret["status"] = "ERROR";
							$ret["status_message"] = $res;
							//echo "STATUS:ERROR:$res.\r\n";
						}
					}
					else{
						$ret["status"] = "ERROR";
						$ret["status_message"] = "Did not provide gallery id, name and title";
						//echo "STATUS:ERROR:Did not provide gallery id, name and title.\r\n";
					}
				}
				elseif($_REQUEST["ngma"] == "getpluginversion"){
					$ret["status"] = "OK";
					$ret["status_message"] = "Getting images";
					
					$ret["version"] = $plugin_version;
				}
				else{
					$ret["status"] = "ERROR";
					$ret["status_message"] = "Invalid action";
					//echo "STATUS:ERROR:invalid func";
				}
			}
		}
		elseif($_REQUEST["ngma"] == "getsizedimg"){
			$requestimg = $_REQUEST["img"];
			getResizedImage($requestimg);
			die();
		}
		else{
			$ret["status"] = "ERROR";
			$ret["status_message"] = "no login";
			//echo "STATUS:ERROR:no login";
		}
	}
	else{
		$ret["status"] = "ERROR";
		$ret["status_message"] = "No action";
		//echo "STATUS:ERROR:no func";
	}
	echo json_encode($ret);
	//print_r($_REQUEST);
?>