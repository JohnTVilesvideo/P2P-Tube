<?php

/**
 * Class Catalog controls video hierarchy and searching
 *
 * @category	Controller
 * @author		Călin-Andrei Burloiu
 */
class Catalog extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		//$this->lang->load('catalog');
	}	
	
	public function index()
	{
		// **
		// ** LOADING MODEL
		// **
		// Retrieve videos summary.
		$this->load->model('videos_model');
		foreach ($this->config->item('categories') as $id => $name)
		{
			// Videos
			$vs_data['videos'] = $this->videos_model->get_videos_summary(
				$id, 0, $this->config->item('videos_per_row'));
			
			// Category
			$vs_data['category_name'] = $name;
			$vs_data['category_title'] = $name ?
				$this->lang->line("ui_categ_$name") : $name;
			$vs_data['category_id'] = $id;
			
			// Pagination (not required)
			$vs_data['pagination'] = '';
			
			$data['videos_summaries'][] = 
				$this->load->view('catalog/videos_summary_view', 
				$vs_data, TRUE);
		}
		
		$params = array(	'title' => $this->config->item('site_name'),
							'css' => array(
								'catalog.css'
							),
							//'js' => array(),
							//'metas' => array('description'=>'','keywords'=>'')
							);
		$this->load->library('html_head_params', $params);
		
		// **
		// ** LOADING VIEWS
		// **
		$this->load->view('html_begin', $this->html_head_params);
		$this->load->view('header', array('selected_menu' => 'home'));
		
		$main_params['content'] = $this->load->view('catalog/index_view', $data, TRUE);
		$main_params['side'] = $this->load->view('side_default', NULL, TRUE);
		$this->load->view('main', $main_params);
		
		$this->load->view('footer');
		$this->load->view('html_end');
	}
	
	public function test($page = 0)
	{
		$params = array(	'title' => 'Test - '. $this->config->item('site_name'),
							//'css' => array(),
							//'js' => array(),
							//'metas' => array('description'=>'','keywords'=>'')
			);
		$this->load->library('html_head_params', $params);
		
		// **
		// ** LOADING VIEWS
		// **
		$this->load->view('html_begin', $this->html_head_params);
		$this->load->view('header', array('selected_menu' => 'home'));
		
		$this->load->view('echo', array('output'=>'Test Page', 'clear'=>TRUE));
		
		$this->load->view('footer');
		$this->load->view('html_end');
	}
	
	public function category($category_name, $ordering = 'hottest', $offset = 0)
	{
		// **
		// ** LOADING MODEL
		// **
		// Video Category
		$vs_data = $this->_get_category_data($category_name);
		
		// Retrieve videos summary.
		$this->load->model('videos_model');
		$vs_data['videos'] = $this->videos_model->get_videos_summary(
			$vs_data['category_id'], intval($offset),
			$this->config->item('videos_per_page'), $ordering);
		
		$vs_data['ordering'] = $ordering;
		
		// Pagination
		$this->load->library('pagination');
		$pg_config['base_url'] = site_url("catalog/category/$category_name/$ordering/");
		$pg_config['uri_segment'] = 5;
		$pg_config['total_rows'] = $this->videos_model->get_videos_count(
			$vs_data['category_id']);
		$pg_config['per_page'] = $this->config->item('videos_per_page');
		$this->pagination->initialize($pg_config);
		$vs_data['pagination'] = $this->pagination->create_links();
		
		// Video Summary
// 		$data['video_summary'] = $this->load->view('catalog/videos_summary_view',
// 			$vs_data, TRUE);
		
		$params = array(	'title' => $this->config->item('site_name'),
							'css' => array(
								'catalog.css'
							),
							//'js' => array(),
							//'metas' => array('description'=>'','keywords'=>'')
							);
		$this->load->library('html_head_params', $params);
		
		// **
		// ** LOADING VIEWS
		// **
		$this->load->view('html_begin', $this->html_head_params);
		$this->load->view('header', array(
			'search_category_name'=>$vs_data['category_name'],
			'search_category_title'=>$vs_data['category_title']
		));
		
// 		$main_params['content'] = $this->load->view('catalog/category_view', $data, TRUE);
		$main_params['content'] = 
			$this->load->view('catalog/videos_summary_view', $vs_data, TRUE);
		$main_params['side'] = $this->load->view('side_default', NULL, TRUE);
		$this->load->view('main', $main_params);
		
		$this->load->view('footer');
		$this->load->view('html_end');
	}
	
	public function search($search_query = "", $offset = 0, $category_name = NULL)
	{
		// Redirect to an URL which contains search string if data was passed
		// via POST method and not via URL segments.
		$str_post_search = $this->input->post('search');
		if ($search_query === "" && $str_post_search !== FALSE) 
			redirect('catalog/search/'. $str_post_search);

		$this->load->model('videos_model');
		$this->load->library('security');
		
		// **
		// ** LOADING MODEL
		// **
		// Search query is encoded for URL and must be decoded.
		$enc_search_query = $search_query;
		$search_query = $this->videos_model->decode_search_query($search_query);
		
		// Security filtering
		$search_query = $this->security->xss_clean($search_query);
		$results_data['search_query'] = $search_query;

		// Category
		$results_data = $this->_get_category_data($category_name);
		if ($results_data === NULL)
			$results_data = array('category_id'=>NULL);
		
		// Page header data
		$header_data['search_query'] = $search_query;
		if ($category_name !== NULL)
		{
			$header_data['search_category_name'] = $results_data['category_name'];
			$header_data['search_category_title'] = $results_data['category_title'];
		}
		
		// Check if search string is valid.
		if (strlen($search_query) === 0)
		{
			//$results_data['videos'] = NULL;
			$this->error($this->lang->line('error_search_query_empty'),
				$header_data);
			return;
		}
		else
		{
			// Retrieve search results.
			$results_data['count'] = $this->videos_model->search_videos(
				$search_query, 0, 0, $results_data['category_id']);
			$results_data['videos'] = $this->videos_model->search_videos(
				$search_query, intval($offset),
				$this->config->item('search_results_per_page'),
				$results_data['category_id']);
			if ($results_data['videos'] === NULL)
				$results_data['videos'] = array();
	
			// Pagination
			$this->load->library('pagination');
			$pg_config['base_url'] = site_url("catalog/search/$enc_search_query/");
			$pg_config['uri_segment'] = 4;
			$pg_config['total_rows'] = $results_data['count'];
			$pg_config['per_page'] =
				$this->config->item('search_results_per_page');
			$this->pagination->initialize($pg_config);
			$results_data['pagination'] = $this->pagination->create_links();
		}
		
		// HTML head parameters
		$params = array(	'title' => $this->config->item('site_name'),
							'css' => array(
								'catalog.css'
							),
							//'js' => array(),
							//'metas' => array('description'=>'','keywords'=>'')
							);
		$this->load->library('html_head_params', $params);
		
		// **
		// ** LOADING VIEWS
		// **
		$this->load->view('html_begin', $this->html_head_params);
		$this->load->view('header', $header_data);
		
		// Search Results
		$main_params['content'] = 
			$this->load->view('catalog/search_results_view',
				$results_data, TRUE);
		$main_params['side'] = $this->load->view('side_default', NULL, TRUE);
		$this->load->view('main', $main_params);
		
		$this->load->view('footer');
		$this->load->view('html_end');
	}
	
	public function error($msg, $header_data)
	{
		$params = array(	'title' => 'Error - '. $this->config->item('site_name'),
			//'css' => array(),
			//'js' => array(),
			//'metas' => array('description'=>'','keywords'=>'')
		);
		$this->load->library('html_head_params', $params);
		
		// **
		// ** LOADING VIEWS
		// **
		$this->load->view('html_begin', $this->html_head_params);
		$this->load->view('header', $header_data);
		
		$main_params['content'] = $this->load->view('error_view', array('msg'=>$msg), TRUE);
		$main_params['side'] = $this->load->view('side_default', NULL, TRUE);
		$this->load->view('main', $main_params);
		
		$this->load->view('footer');
		$this->load->view('html_end');
	}
	
	public function _get_category_data($category_name)
	{
		if ($category_name === NULL)
			return NULL;
		
		$categories = $this->config->item('categories');
		$category_id = array_search($category_name, $categories);
		$results_data['category_name'] = $category_name;
		$results_data['category_id'] = $category_id;
		$results_data['category_title'] = $category_name ?
			$this->lang->line("ui_categ_$category_name") : $category_name;
		
		return $results_data;
	} 
}

/* End of file catalog.php */
/* Location: ./application/controllers/catalog.php */
