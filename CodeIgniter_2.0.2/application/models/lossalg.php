<?php

class Lossalg extends CI_Model {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
     }



    
    function get_type_details($type_id) {
        $this->db->where('id', $type_id);
        $query = $this->db->get('product_details');
        return $query;
    }

    function add_new_product() {

    }

}