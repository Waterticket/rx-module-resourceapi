<?php

/**
 * Resource Api
 * 
 * Copyright (c) Waterticket
 * 
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class ResourceapiController extends Resourceapi
{
	public function getResourceapiLastupdate()
	{
		Context::setRequestMethod('XML');
		Context::setResponseMethod('XML');

		$output = executeQuery('resourceapi.getLastUpdate');
		$last_update = ($output->toBool()) ? $output->data->last_update : 0;

		$obj = new stdClass();
		$obj->error = 0;
		$obj->message = "success";
		$obj->updatedate = $last_update;

		$onx = new ObjectAndXML();
		$xml = $onx->response($obj);
		echo $xml;
		exit();
	}

	public function getResourceapiPackagelist()
	{
		Context::setRequestMethod('XML');
		Context::setResponseMethod('XML');

		$config = $this->getConfig();
		$vars = Context::getRequestVars();

		$page = $vars->page;
		$order_target = $vars->order_target;
		$order_type = $vars->order_type;
		$category_srl = $vars->category_srl;
		$search_keyword = $vars->search_keyword;

		if(!$page)
		{
			$page = 1;
		}

		if(!in_array($order_type, array('asc', 'desc')))
		{
			$order_type = 'desc';
		}

		if(!in_array($order_target, array('newest', 'download', 'popular')))
		{
			$order_target = 'newest';
		}

		$oResourceModel = getModel('resource');
		$output = $oResourceModel->getLatestItemList(135, $category_srl, null, null, $search_keyword, $order_target, $order_type, $page);
		$onx = new ObjectAndXML();

		if(!$output->toBool())
		{
			die($onx->response($this->createObject(-1, $output->message)));
		}

		$obj->error = $output->error;
		$obj->message = $output->message;
		$obj->packageList = new stdClass();
		$obj->packageList->item = $output->data;
		$obj->page_navigation = $output->page_navigation;

		die($onx->response($obj));
	}

	public function getResourceapiMenuPackageList()
	{
		$this->getResourceapiPackagelist();
	}

	public function getResourceapiSkinPackageList()
	{
		$this->getResourceapiPackagelist();
	}

	public function getResourceapiPackages()
	{
		Context::setRequestMethod('XML');
		Context::setResponseMethod('XML');

		$config = $this->getConfig();
		$vars = Context::getRequestVars();

		$package_srls = $vars->package_srls; // (int or array)
		if(is_numeric($package_srls)) $package_srls = array($package_srls);

		$package_list = array();

		foreach($package_srls as $package_srl)
		{
			$args = new stdClass();
			$args->package_srl = $package_srl;
			$output = executeQuery('resourceapi.getPackages', $args);
			if(!$output->toBool()) continue;
			
			/* Dependancy 추가하기! */

			array_push($package_list, $output->data);
		}
		
		$obj->error = 0;
		$obj->message = 'success';
		$obj->packageList = new stdClass();

		if(!empty($package_list))
			$obj->packageList->item = (object) $package_list;

		$onx = new ObjectAndXML();
		die($onx->response($obj));
	}

	public function getResourceapiInstallInfo()
	{
		Context::setRequestMethod('XML');
		Context::setResponseMethod('XML');

		$config = $this->getConfig();
		$vars = Context::getRequestVars();

		$package_srl = $vars->package_srl; // (int)

		$args = new stdClass();
		$args->package_srl = $package_srl;
		$output = executeQuery('resourceapi.getInstallInfo', $args);

		$response = new stdClass();
		$response->error = $output->error;
		$response->message = $output->message;
		$response->package = $output->data;
		
		$onx = new ObjectAndXML();
		die($onx->response($response));
	}

	public function getResourceapiUpdate()
	{
		Context::setRequestMethod('XML');
		Context::setResponseMethod('XML');
		$config = $this->getConfig();

		// 카테고리
		$oDocumentModel = getModel('document');
		$category_output = $oDocumentModel->getCategoryList(135);
		$category_list = array();
		foreach($category_output as $srl => $category)
		{
			$temp = new stdClass();
			$temp->category_srl = $category->category_srl;
			$temp->parent_srl = $category->parent_srl;
			$temp->title = $category->title;
			$temp->depth = $category->depth;
			array_push($category_list, $temp);
		}

		// 패키지
		$package_output = executeQueryArray('resourceapi.getAllPackages');
		$packages = array();
		foreach($package_output->data as $inc => $package)
		{
			// $temp = new stdClass();
			// $temp->package_srl = $package->package_srl;
			// $temp->category_srl = $package->category_srl;
			// $temp->path = $package->path;
			// $temp->have_instance = 'N';
			// $temp->updatedate = $package->updatedate;
			// $temp->latest_item_srl = $package->latest_item_srl;
			// $temp->version = $package->version;
			$package->have_instance = 'N';

			array_push($packages, $package);
		}

		$response = new stdClass();
		$response->error = 0;
		$response->message = 'success';
		$response->packages = new stdClass();
		$response->packages->item = $packages;

		$response->categorylist = new stdClass();
		$response->categorylist->item = $category_list;

		$onx = new ObjectAndXML();
		die($onx->response($response));
	}

	public function procResourceapiDownload()
	{
		$config = $this->getConfig();
		$vars = Context::getRequestVars();

		$path = $vars->path; //어떤 미친놈이 다른거 놔두고 path를 보내냐..
		$args = new stdClass();
		$args->path = $path;
		$output = executeQuery('resourceapi.getFileSrlByPath', $args);

		$package_srl = $output->data->package_srl;
		$file_srl = $output->data->file_srl;
		$oFileModel = getModel('file');
		$file_data = $oFileModel->getFile($file_srl);

		$file_absolute_path = FileHandler::getRealPath($file_data->uploaded_filename);

		$tar_file = $this->change_zip_to_tar($file_absolute_path);

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="xe.tar"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($tar_file));
		ob_end_clean();
		flush(); // Flush system output buffer
		readfile($tar_file);

		ob_start();
		$this->remove_made_tar($tar_file);

		$oResourceApiModel = getModel('resourceapi');
		$oResourceApiModel->increaseDownloadCount(135, $package_srl);
		ob_end_clean();
		die();
	}

	private $made_tar = array();
	private $zip2tar_dir = RX_BASEDIR.'/files/convert_zip';

	private function change_zip_to_tar($file_absolute_path)
	{
		$output = shell_exec("cd '{$this->zip2tar_dir}' && zip2tar.sh {$file_absolute_path}");
		$data = json_decode($output);
		if(json_last_error() != JSON_ERROR_NONE) return $this->createObject(-1, 'change Error!');

		$tar_file = $this->zip2tar_dir.'/'.($data->file_name);
		array_push($this->made_tar, basename($data->file_name, '.tar'));

		return $tar_file;
	}

	private function remove_made_tar($file_absolute_path)
	{
		$bn = basename($file_absolute_path, '.tar');
		if(in_array($bn, $this->made_tar))
		{
			shell_exec("cd '{$this->zip2tar_dir}' && rm -rf '{$bn}.tar'");
		}
	}
}
