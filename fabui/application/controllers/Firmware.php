<?php
/**
 * 
 * @author Daniel Kesler
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */

defined('BASEPATH') OR exit('No direct script access allowed');
 
class Firmware extends FAB_Controller {

	public function test()
	{
		$this->load->helper('fabtotum_helper');
		$this->load->helper('os_helper');
		$tmp = doMacro('version');
		$reply = $tmp['reply'];
		
		$this->output->set_content_type('application/json')->set_output(json_encode($reply));
	}

	public function index()
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('os_helper');
		
		$this->config->load('fabtotum');
		
		$data = array();
		
		$fw_versions_url = $this->config->item('firmware_endpoint') . 'fablin/atmega1280/version.json';
		
		$content = getRemoteFile($fw_versions_url);
		
		$tmp = doMacro('version');
		$reply = $tmp['reply'];
		
		$data['fw_version'] = $reply['firmware']['version']; //'V 1.0.0096-rc1';
		$data['fw_author'] = $reply['firmware']['author']; //'FABteam';
		$data['fw_buildate'] = $reply['firmware']['build_date']; //'Nov 14 2016 17:21:17';
		$data['td_serial'] = $reply['controller']['serial_id']; //'524205';
		$data['td_version'] = $reply['board']['version']; //'524205';
		$data['content'] = $content;

		$fw_versions = array();
		$fw_versions['factory'] = 'Factory default';
		
		if($content)
		{
			$tmp = json_decode($content, true)['firmware'];
			foreach($tmp as $key => $value)
			{
				if($key != 'latest')
				{
					$fw_versions[$key] = $key . ' (download)';
				}
			}
		}
		
		$fw_versions['upload'] = 'Upload custom';
		$data['fw_versions'] = $fw_versions;
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widgeFooterButtons = '';//$this->smart->create_button('Flash firmware', 'primary')->attr(array('id' => 'flashButton'))->attr('data-action', 'exec')->icon('fa-save')->print_html(true);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-firmware';
		$widget->header = array('icon' => 'fa-microchip', "title" => "<h2>Firmware</h2>");
		$widget->body   = array('content' => $this->load->view('firmware/main_widget', $data, true ), 'class'=>'fuelux', 'footer'=>$widgeFooterButtons);

		$this->addJSFile('/assets/js/plugin/bootstrap-progressbar/bootstrap-progressbar.min.js'); //datatable
		$this->addJsInLine($this->load->view('firmware/main_js', $data, true)); 
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function doFlashFirmware($version)
	{
		//load helpers
		$this->load->helper('fabtotum');
		$this->load->helper('update_helper');
		
		$data = array();
		$data['result'] = '';
		
		if($version == 'factory')
		{
			stopServices();
			$result = flashFirmware('factory');
			startServices();
		}
		else
		{
			stopServices();
			$result = flashFirmware('remote', $version);
			startServices();
		}
		
		$data['result'] = $result;
		
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}
	
	public function doUploadFirmware()
	{
		//load helpers
		$this->load->helper('file');
		$this->load->helper('fabtotum');
		$this->load->helper('update_helper');
		
		$upload_config['upload_path']   = '/tmp/fabui/';
		$upload_config['allowed_types'] = 'hex';
		
		$this->load->library('upload', $upload_config);
		
		if($this->upload->do_upload('hex-file')){ //do upload
			$upload_data = $this->upload->data();
			$result = false;
			
			stopServices();
			flashFirmware('custom', $upload_data['full_path']);
			startServices();
			
			$data['result'] = $result;
		}else{
			$data['error'] = strip_tags($this->upload->display_errors());
		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}

}
 
?>