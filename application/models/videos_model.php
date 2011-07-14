<?php

/**
 * Class Videos_model models videos information from the DB
 *
 * @category	Model
 * @author		Călin-Andrei Burloiu
 */
class Videos_model extends CI_Model {
	private $db = NULL;
	
	public function __construct()
	{
		if ($this->db === NULL)
		{
			$this->load->library('singleton_db');
			$this->db = $this->singleton_db->connect();
			
			$this->load->helper('url');
		}
	}
	
	/**
	 * Retrieves information about a set of videos which are going to be
	 * displayed in the catalog.
	 *
	 * TODO: filter, limit, ordering parameters
	 * @return		array	a list of videos, each one being an assoc array with:
	 *   * id, name, title, duration, thumbs_count, default_thumb, views => from DB
	 *   * video_url => P2P-Tube video URl
	 *   * TODO: user_id, user_name
	 *   * thumbs => thumbnail images' URLs
	 */
	public function get_videos_summary()
	{
		$query = $this->db->query(
			'SELECT id, name, title, duration, user_id, views, thumbs_count,
				default_thumb
			FROM `videos`');
		$videos = $query->result_array();
		
		foreach ($videos as & $video)
		{
			// P2P-Tube Video URL
			$video['video_url'] = site_url(sprintf("video/watch/%d/%s",
				$video['id'], $video['name']));
			
			// Thumbnails
			$video['thumbs'] = $this->getThumbs($video['name'], 
				$video['thumbs_count']);
		}
		
		return $videos;
	}
	
	/**
	 * Retrieves information about a video.
	 *
	 * If $name does not match with the video's `name` from the DB an error is
	 * marked in the key 'err'. If it's NULL it is ignored.
	 *
	 * @access		public
	 * @param		string $id	video's `id` column from `videos` DB table
	 * @param		string $name	video's `name` column from `videos` DB
	 * table. NULL means there is no name provided.
	 * @return		array	an associative list with information about a video
	 * with the following keys:
	 *   * all columns form DB with some exceptions that are overwritten or new
	 *   * torrents => list of torrent file names formated as
	 * {name}_{format}.{default_video_ext}.{default_torrent_ext}
	 *   * user_name => TODO: user name from `users` table
	 *   * formats => list of formats like 1080p
	 *   * category_name => TODO: human-friendly category name
	 *   * tags => associative list of "tag => score"
	 *   * date => date and time when the video was created
	 *   * thumbs => thumbnail images' URLs
	 */
	public function get_video($id, $name = NULL)
	{
		$query = $this->db->query('SELECT * 
								FROM `videos` 
								WHERE id = ?', $id);
		$video = array();
		
		if ($query->num_rows() > 0)
		{
			$video = $query->row_array();
			if ($name !== NULL && $video['name'] != $name)
				$video['err'] = 'INVALID_NAME';
		}
		else
		{
			$video['err'] = 'INVALID_ID';
			return $video;
		}
		
		// Convert JSON encoded string to arrays.
		$video['formats'] = json_decode($video['formats'], TRUE);
		$video['tags'] = json_decode($video['tags'], TRUE);
		
		// Torrents
		$video['torrents'] = array();
		foreach ($video['formats'] as $format)
		{
			$pos = strpos($format, ' ');
			if($pos !== FALSE)
				$format = substr($format, 0, $pos);
 			$video['torrents'][] = site_url('data/torrents/'. $video['name'] . '_'
 				. $format . '.'. $this->config->item('default_video_ext')
 				. '.'. $this->config->item('default_torrent_ext'));
		}
		
		// Thumbnails
		$video['thumbs'] = $this->getThumbs($video['name'], $video['thumbs_count']);
		
		return $video;
	}
	
	public function getThumbs($name, $count)
	{
		$thumbs = array();
		
		for ($i=0; $i < $count; $i++)
			$thumbs[] = site_url(sprintf("data/thumbs/%s_t%02d.jpg", $name, $i));
		
		return $thumbs;
	}
}

/* End of file videos_model.php */
/* Location: ./application/models/videos_model.php */