<?php

class Lossalg extends CI_Model {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    function add_new_type() {

    }


    function get_types() {
        $this->db->select('id, explained');
		$this->db->from('producttypes');
        $query = $this->db->get();

        return $query;
    }
    
    function get_type_details($type_id) {
        $this->db->where('id', $type_id);
        $query = $this->db->get('product_details');
        return $query;
    }

    function add_new_product($id, $details) {
        $data = array(
            'product_id'  => $this->db->insert_id(),
            'id'          => $id,
            'description' => $details 
            
        );
        $this->db->insert('products', $data);
    }

}