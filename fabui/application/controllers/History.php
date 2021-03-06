<?php
/**
 * 
 * @author Krios Mane
 * @author Daniel Kesler
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */

defined('BASEPATH') OR exit('No direct script access allowed');
 
class History extends FAB_Controller {

    public function index()
    {
		//load libraries, helpers, model
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->model('Tasks', 'tasks');
		
		$data = array();
		$data['start_date'] = date('d/m/Y', strtotime('today - 30 days'));
		$data['end_date']   = date('d/m/Y', strtotime('today'));
		
		$data['min_date'] = date('d/m/Y', strtotime($this->tasks->getMinDate('make')));
		$data['makeList'] = getMakeTaskTypeList();
		
		
		
		//main page widget
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$headerToolbar = '';
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-bed-calibration';
		$widget->header = array('icon' => 'fa-history', "title" => "<h2>"._("History")."</h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('history/main_widget', $data, true ), 'class'=>'fuelux');
		
		
		// datatable
		$this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable
		
		// datepicker
		$this->addJSFile('/assets/js/plugin/bootstrap-datepicker/moment.min.js');
		$this->addJSFile('/assets/js/plugin/bootstrap-datepicker/daterangepicker.min.js');
		$this->addCSSFile('/assets/js/plugin/bootstrap-datepicker/daterangepicker.css');
		
		// charts
		$this->addJSFile('/assets/js/plugin/morris/raphael.min.js');
		$this->addJSFile('/assets/js/plugin/morris/morris.min.js');
		
		$this->addCSSInLine('<style>.workaround-fix {opacity: 1 !important; transform: inherit !important;} </style>');
		
		$this->addJsInLine($this->load->view('history/main_js', $data, true));
		$this->content = $widget->print_html(true);
		$this->view();
    }
 
 	public function test()
 	{
		$this->load->model('Tasks', 'tasks');
		//$result = $this->tasks->getLastCreations('', 3);
		//$result = $this->tasks->getFileTasks(7);
		$result = array('start_date' => date('Y-m-d H:i:s'));
		$this->output->set_content_type('application/json')->set_output(json_encode($result));
	}
	
	public function getStatsData()
	{
		$this->load->model('Tasks', 'tasks');
		$this->load->helper('utility_helper');
		
		$params = $this->input->get();
		
		
		$filters['start_date'] = $params['start_date'] == '' ? date('d/m/Y', strtotime('today - 30 days')) : $params['start_date'];
		$filters['end_date']   = $params['end_date'] == '' ? date('d/m/Y', strtotime('today')) : $params['end_date'];
		$filters['type']       = isset($params['type']) ? $params['type'] : ''; //$params['type'];
		$filters['status']     = isset($params['status']) ? $params['status'] : '';; //$params['status'];
		
		$tasks = $this->tasks->getMakeTasks($filters);
		
		
		
		
		// @TODO: this is a temporary fix as it only covers the extra laser plugin scenario
		$data['icons'] = array('print' => 'icon-fab-print', 'mill' => 'icon-fab-mill', 'scan' => 'icon-fab-scan', 'laser' => 'icon-communication-143', 'prism' => 'fa fa-cube');

		$data['status_label'] = array('completed' => '<span class="label label-success">'._('Completed').'</span>', 'aborted' => '<span class="label label-warning">'._('Aborted').'</span>', 'terminated' => '<span class="label label-danger">'._('Terminated').'</span>');
		$data['stats_label']  =  array('total_time' => '<i class="fa fa-clock-o"></i> Total time', 'completed' => '<i class="fa fa-check txt-color-green"></i> '._('Completed'), 'aborted' => '<i class="fa fa-stop txt-color-orange"></i> '._('Aborted'), 'terminated' => '<i class="fa fa-exclamation-triangle txt-color-red"></i> '._('Terminated'));
		$data['type_options'] = array('print' => _('Print'), 'mill' => _('Mill'), 'scan' => _('Scan'), 'laser' => _('Laser'), 'prism' => _('Prism'));

		$data['status_options'] = array('completed' => _('Completed'), 'aborted' => _('Aborted'), 'terminated' => _('Terminated'));
		$data['status_colors']  = array('completed' => '#7e9d3a', 'aborted' => '#FF9F01', 'terminated' => '#a90329');
		
		$data['stats'] = array();
		
		if(count($tasks) > 0 )
		{
			if ($filters['type'] == '')
			{
				foreach ($data['type_options'] as $type => $label)
				{
				   
					if ($type != '')
						$data['stats'][$type]['total_time'] = $this->tasks->getTotalTime('make', $type, $filters['status'], $filters['start_date'], $filters['end_date']);

					if ($filters['status'] == '')
					{
						foreach ($data['status_options'] as $status => $label)
						{
							if ($status != '')
								$data['stats'][$type][$status] = $this->tasks->getTotalTasks('make', $type, $status, $filters['start_date'], $filters['end_date']);
						}
					}
					else
					{
						$data['stats'][$type][$filters['status']] = $this->tasks->getTotalTasks('make', $type, $filters['status'], $filters['start_date'], $filters['end_date']);
					}
				}
				
			} 
			else 
			{
			    
				$data['stats'][$filters['type']]['total_time'] = $this -> tasks->getTotalTime('make', $filters['type'], $filters['status'], $filters['start_date'], $filters['end_date']);
				
				

				if ($filters['status'] == '')
				{
					foreach ($data['status_options'] as $status => $label)
					{
						if ($status != '')
							$data['stats'][$filters['type']][$status] = $this->tasks->getTotalTasks('make', $filters['type'], $status, $filters['start_date'], $filters['end_date']);
					}
				} 
				else 
				{
					$data['stats'][$filters['type']][$filters['status']] = $this->tasks->getTotalTasks('make', $filters['type'], $filters['status'], $filters['start_date'], $filters['end_date']);
				}
			}
		}
		
		
		
		
		$content = $this->load->view('history/stats_tab', $data, true );
		echo $content;
	}
	
	public function getTableData()
	{
		$this->load->model('Tasks', 'tasks');
		$this->load->helper('utility_helper');
		
		$params = $this->input->get();

		$filters['start_date'] = !isset($params['start_date']) || $params['start_date'] == '' ? date('d/m/Y', strtotime('today - 30 days')) : $params['start_date'];
		$filters['end_date']   = !isset($params['end_date'])   || $params['end_date']   == '' ? date('d/m/Y', strtotime('today'))  : $params['end_date'];
		$filters['type']       = isset($params['type']) ? $params['type'] : '';
		$filters['status']     = isset($params['status']) ? $params['status'] : '';
		
		$tasks = $this->tasks->getMakeTasks($filters);
		
		// @TODO: this is a temporary fix as it only covers the extra laser plugin scenario
		$data['icons'] = array('print' => 'icon-fab-print', 'mill' => 'icon-fab-mill', 'scan' => 'icon-fab-scan', 'laser' => 'icon-communication-143', 'prism' => 'fa fa-cube');

		$data['status_label'] = array('completed' => '<span class="label label-success">'._('Completed').'</span>', 'aborted' => '<span class="label label-warning">'._('Aborted').'</span>', 'terminated' => '<span class="label label-danger">'._('Terminated').'</span>');
		
		$aaData = array();
		
	

		foreach ($tasks as $task) {
            
		    if(isset($task['task_attributes'])){
			     $attributes = json_decode(utf8_encode(preg_replace('!\\r?\\n!', "<br>", $task['task_attributes'])), true);
		    }

			$when = strtotime($task['finish_date']) > strtotime("-1 day") ? getTimePast($task['finish_date']) . ' '._("ago").'' : date('d M, Y', strtotime($task['finish_date']));
			$info = '<h4>';
			$icon = $task['file_deleted'] == 0 ? 'far fa-file' : 'fa fa-trash';
			$link = $task['file_deleted'] == 0 ? "#projectsmanager/file/".$task['id_file'] : "javascript:void(0)";
			$title = $task['file_deleted'] == 0 ? _("Go to file page") : _("File deleted");
			if ($task['file_name'] != '')
				$info .= '<i class=" '.$icon.'"></i> <a title="'.$title.'" href="' . $link. '">' . $task['client_name'] . '</a>';
			if ($task['object_name'] != '')
				$info .= ' <small>> <i class="fa fa fa-folder-open-o"></i> ' . $task['object_name'] . '</small>';
			/*if (isset($attributes['mode_name']) && $attributes['mode_name'] != '')
				$info .= '<a href="#">' . ucfirst($attributes['mode_name']) . '</a><small> </small>';*/
			$info .= '</h4>';

			$td_0 = '<a href="#" > <i class="fa fa-chevron-right fa-lg" data-toggle="row-detail" title="'._('Show Details').'"></i> </a>';
			$td_1 = $when;
			$td_2 = '<strong><i class="' . $data['icons'][$task['type']] . '"></i> <span class="hidden-xs">' . ucfirst($task['type']) . '</strong></span>';
			$td_3 = $data['status_label'][$task['status']];
			$td_4 = $info;
			$td_5 = $task['duration'];
			$td_6 = date('d M, Y', strtotime($task['start_date'])) . ' at ' . date('G:i', strtotime($task['start_date']));
			$td_7 = date('d M, Y', strtotime($task['finish_date'])) . ' at ' . date('G:i', strtotime($task['finish_date']));
			$td_8 = isset($attributes['note']) ? $attributes['note'] : '';
			$td_9 = $task['type'];
			$td_10 = $task['controller'];
			$td_11 = $task['id_file'];
			$td_12 = $task['id_object'];
			
			
			if( array_key_exists("rating", $attributes) )
				$td_13 = $attributes['rating'];
			else
				$td_13 = 0;
			
			$td_14 = $task['file_deleted'];
			$aaData[] = array($td_0, $td_1, $td_2, $td_3, $td_4, $td_5, $td_6, $td_7, $td_8, $td_9, $td_10, $td_11, $td_12, $td_13, $td_14);

		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array('aaData' => $aaData)));
	}

}
 
?>
