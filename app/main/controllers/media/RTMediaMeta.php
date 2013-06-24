<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RTMediaMetaQuery
 *
 * @author saurabh
 */
class RTMediaMeta {

	/**
	 *
	 */
	function __construct() {
		$this->model = new RTDBModel('rtm_media_meta');
	}

	function get_meta($id=false,$key=false){
		if($id===false)return false;
		if($key===false){
			return $this->get_all_meta($id);
		}else{
			return $this->get_single_meta($id,$key);
		}

	}

	private function get_all_meta($id=false){
		if($id===false) return false;
		return maybe_unserialize($this->model->get(array('media_id'=>$id)));
	}

	private function get_single_meta($id=false, $key=false){
		if($id===false) return false;
		if($key===false) return false;
		return maybe_unserialize($this->model->get(array('media_id'=>$id,'meta_key'=>$key)));
	}

	function add_meta($id=false,$key=false,$value=false,$duplicate=false){
		$this->update_media_meta($id=false,$key=false,$value=false,$duplicate=true);
	}

	function update_meta($id=false,$key=false,$value=false,$duplicate=false){
		if($id===false) return false;
		if($key===false) return false;
		if($value===false) return false;
		$value = maybe_serialize($value);

		if($duplicate===true){
			$media_meta = $this->model->insert(array('media_id'=>$id,'meta_key'=>$key, 'meta_value'=>$value));
		}else{
				$meta = array('meta_value' => $value);
				$where = array('media_id' => $id, 'meta_key' => $key);
			$media_meta = $this->model->update($meta, $where);
		}
	}

	function delete_meta($id=false,$key=false){
		if($id===false) return false;
		if($key===false){
			$where = array('media_id' => $id);
		}else{
			$where = array('media_id' => $id, 'meta_key' => $key);
		}
		$this->model->delete($where);
	}

}

?>
