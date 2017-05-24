<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('javascript');
		$this->load->helper('menu');
		$this->load->helper('url');
		$this->load->helper('danish_date');
		$this->load->helper('date');
		$this->load->library('session');
		$this->load->model('Permission');
		$this->load->model('Memberinfo');
		$this->load->model('Personsmodel');
        $this->load->model('Lossalg');
    }

    function index() {
		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');
        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		$permissions = $this->session->userdata('permissions');

		$createsel = '';
		$createfsel = '';
		$adminsel = '';
		$cashsel = '';
		$excelsel = '';
		$dagenssalg = '';
		$nyemedlemmer = '';
		$welcome = '';
        $varetyper = '';
		$this->db->select('divisions.name, divisions.uid');
		$this->db->from('divisions');
		$this->db->where('type','aktiv');
		$this->db->order_by('divisions.name');
		$query = $this->db->get();

		foreach ($query->result_array() as $row)
		{
			$p_administrator = $this->Memberinfo->checkpermission($permissions, 'Administrator', $row['uid']);
			$p_kassemester   = $this->Memberinfo->checkpermission($permissions, 'Kassemester', $row['uid']);
			if (($p_administrator) || ($p_kassemester))
			{
				$excelsel .= '<a href="/admin/excel/' . $row['uid'] . '">' .$row['name'] . "</a><br>\n";
				$createsel .= '<option value="' . $row['uid'] . '">' . $row['name'] . "</option>\n";
				$createfsel .= $this->_getfuturepickupdays($row['uid']);
				$adminsel .= '<a href="/medlemmer/index/' . $row['uid'] . '">' .$row['name'] . "</a><br>\n";
				$cashsel .= '<a href="/kontantordrer/index/' . $row['uid'] . '">' .$row['name'] . "</a><br>\n";
				$dagenssalg .= '<a href="/admin/dagens_salg/' . $row['uid'] . '">' .$row['name'] . "</a><br>\n";
				$nyemedlemmer .= '<a href="/kassemester/nyemedlemmer' . $row['uid'] . '">' .$row['name'] . "</a><br>\n";
				$welcome .= '<a href="/admin/afdinfo/' . $row['uid'] . '">' .$row['name'] . "</a><br>\n";
			}
		}

		$bagdays = '';
		$this->db->select('id, explained');
		$this->db->from('producttypes');
		$this->db->where('bag','Y');
		$this->db->where('id !=',FF_GROCERYBAG);
		$this->db->order_by('sortkey');
		$query = $this->db->get();
		$bagdays = $query->result_array();
		$q2 = $this->db->query('select id, explained from ff_producttypes where bag = "Y" and id != ' . FF_GROCERYBAG);
		$bagdays = $q2->result_array();
		$content = '';


        $this->db->select('id, explained');
		$this->db->from('producttypes');
        $query = $this->db->get();

        $explained = '';
        foreach ($query->result_array() as $row)
        {
            $explained .= '<option value="' . $row['id'] . '">' . $row['explained'] . "</option>\n";
        }

        
		$data = array(
               'title' => 'KBHFF Administrationsside',
               'heading' => 'KBHFF Administrationsside',
               'content' => $content,
               'excelsel' => $excelsel,
               'createsel' => $createsel,
               'createfsel' => $createfsel,
               'adminsel' => $adminsel,
               'cashsel' => $cashsel,
               'dagenssalg' => $dagenssalg,
			   'nyemedlemmer' => $nyemedlemmer,
			   'welcome' => $welcome,
			   'bagdays' => $bagdays,
               'varetyper' => $explained,
        );

 
        
		$this->load->view('v_admin', $data);
    }

	function nyemedlemmer ()
	{
		$division = $this->input->get_post('division');
		$date = $this->input->get_post('dato');
		if ((int)$division > 0)
		{
				$divisionname = $this->_divisionname($division);
		} else {
				$divisionname = 'Alle afdelinger';
		}

		$this->db->select("ff_persons.uid, email, active+0 AS active, date_format(ff_persons.created,'%d/%m/%Y %k:%i') AS created, CONCAT(firstname,' ',middlename, ' ',lastname) AS name, tel", false);
		$this->db->select('membernote.note');
		$this->db->join('membernote', 'membernote.puid = ff_persons.uid', 'left');
		$this->db->from('persons, division_members');
		$this->db->where('persons.created >', $date);
		$this->db->where('ff_division_members.member', 'ff_persons.uid', false);

		if ((int)$division > 0)
		{
			$this->db->where('division_members.division', (int)$division);
		}

		$this->db->order_by('persons.created', 'desc');
		$query = $this->db->get();
		$medlemmer = $query->result_array();

		$data = array(
               'title' => 'KBHFF Administrationsside',
               'heading' => 'Nye medlemmer: ' . $divisionname . ' siden '.$date,
			   'medlemmer' => $medlemmer,
          );

		$this->load->view('v_nye_medlemmer.php', $data);
	}

    function opret() {
		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');
        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		$permissions = $this->session->userdata('permissions');

		$error = false;
		$errstr = '';
		$division = $this->input->post('division');
		$dato = $this->input->post('dato');
		$dato2 = $this->input->post('dato2');
		$tid2 = $this->input->post('tid2');
		// check for legal dates
		$c1 = human_to_unix("$dato 24:00:00");
		$c2 = human_to_unix("$dato2 24:00:00");
		if ( $c2 > $c1)
		{
			$error = true;
			$errstr .= 'Sidste frist for &aelig;ndring skal ligge f&oslash;r afhentningsdagen<br>';
		}

		if ( $c1 <= now() )
		{
			$error = true;
			$errstr .= 'Afhentningsdagen skal ligge i fremtiden<br>';
		}

		if ( $c2 <= now() )
		{
			$error = true;
			$errstr .= 'Sidste frist for &aelig;ndring skal ligge i fremtiden<br>';
		}


		if ($this->_checkpickupdate($dato, $division))
		{
			$error = true;
			$errstr .= 'Afhentningsdagen er allerede oprettet<br>';
		}

		if (! $error)
		{
			$sql = 'INSERT INTO ' . $this->db->protect_identifiers('pickupdates', TRUE) . ' (division, pickupdate) VALUES (' . (int)$division . ','.$this->db->escape($dato).')';
			$this->db->query($sql);
			$sql = 'REPLACE INTO ' . $this->db->protect_identifiers('itemdays', TRUE) . ' (item, pickupday, lastorder) VALUES (' . FF_GROCERYBAG . ',' . $this->db->insert_id().',' .$this->db->escape("$dato2 $tid2:00").')';
			$this->db->query($sql);
			$msg = 'Afhentningsdag ' . $dato . ' er oprettet.<br>';
		} else {
			$msg = 'Afhentningsdag ' . $dato . ' er IKKE oprettet.<br>' . $errstr . '<br>';
		}

		$this->_displayliste($division, $msg);
    }


    function opret_lossalg_type() {
		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');
        
        // insert new item type to table 'producttypes'
        $itemtype = $this->input->post('itemtype');
        if ($itemtype === "") {
            $this->index();
        }
        else {
            $sql = 'INSERT INTO ' . $this->db->protect_identifiers('producttypes', TRUE) . ' (id, explained, bag) VALUES (' . $this->db->insert_id() . ',' .  $this->db->escape($itemtype)  . ', "Y")';
            $this->db->query($sql);

            // get the id of the newly added item type
            $query = $this->db->query('select id from ff_producttypes where explained =' . $this->db->escape($itemtype) .' ;');
            $type_id = $query->row()->id;

            // Add each detail to table 'product_details
            $details = $this->input->post('detaljer');
            foreach ($details as $detail)
            {
                $this->db->trans_start();
                $sql = 'INSERT INTO ' . $this->db->protect_identifiers('product_details', TRUE) . ' (detail_id, id, detail_name) VALUES (' . $this->db->insert_id() . ',' . $type_id . ',' . $this->db->escape($detail) .')';
                $this->db->query($sql);
                $this->db->trans_complete();
            }
        
            $this->index();
        }
    }

    function opret_lossalg_vare() {

    }

    function hent_lossalg_detaljer() {
        $type_id =$this->input->post('id');    
        $details = $this->Lossalg->get_type_details($type_id);
        echo json_encode($details->result_array());
    }

    function slet_lossalg_type() {

    }

    function slet_lossalg_vare() {

    }

    function rediger_lossalg_type() {

    }

    function rediger_lossalg_vare() {

    }
    
    function opretf() {
		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');
        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		$permissions = $this->session->userdata('permissions');
		$bagitem = $this->uri->segment(3);
		$bagdate = "dato" . $bagitem;
		$bagtime = "tid" . $bagitem;
		$error = false;
		$errstr = '';
		$pickupday = $this->input->post('pickupday');
		$dato3 = $this->input->post($bagdate);
		$tid3 = $this->input->post($bagtime);

		$this->db->select('divisions.uid');
		$this->db->select('pickupdate');
		$this->db->from('divisions');
		$this->db->from('pickupdates ');
		$this->db->where('pickupdates.uid', (int)$pickupday);
		$this->db->where('ff_divisions.uid = ff_pickupdates.division');
		$query = $this->db->get();
		$row = $query->row();
		$division = $row->uid;
		$date = $row->pickupdate;


		// check for legal dates
		$c2 = human_to_unix("$dato3 24:00:00");
		if ( $c2 < now() )
		{
			$error = true;
			$errstr .= 'Sidste frist for &aelig;ndring ('. $dato3 .') skal ligge i fremtiden  ('.  $c2 .' < ' . now() . ')<br>';
		}

		if (! $error)
		{
			$sql = 'REPLACE INTO ' . $this->db->protect_identifiers('itemdays', TRUE) . ' (item, pickupday, lastorder) VALUES (' . $bagitem . ',' . (int)$pickupday .',' .$this->db->escape("$dato3 $tid3:00").')';
			$this->db->query($sql);
			$msg = 'Afhentningsdag ' . $date .' for frugtpose er oprettet.<br>';
		} else {
			$msg = 'Afhentningsdag ' . $date .' for frugtpose er IKKE oprettet.<br>' . $errstr . '<br>';
		}

		$this->_displayliste($division, $msg);
    }


	function dagens_salg($division=5,$dag = 0)
	{
		if ($this->uri->segment(3) > 0)
		{
			$division = $this->uri->segment(3);
		} else {
			$division = $this->input->post('division');
		}

		if ($this->uri->segment(4) > 0)
		{
			$dag = $this->uri->segment(4);
		} else {
			$dag = $this->input->post('dag');
		}

		$this->db->select("division, pickupdate as pickupdatesort, date_format(`ff_pickupdates`.`pickupdate`,'%d/%m/%Y') as pickupdate, date_format(`ff_itemdays`.`lastorder`,'%d/%m/%Y %k:%i') as lastorder, uid", FALSE);
		$this->db->from('pickupdates');
		$this->db->from('itemdays');
		$this->db->where('ff_pickupdates.uid', 'ff_itemdays.pickupday', FALSE);
		$this->db->where('itemdays.item', FF_GROCERYBAG);
		$this->db->where('division', (int)$division);
		$this->db->where('pickupdate <= curdate()');
		$this->db->order_by('pickupdatesort', 'desc');
		$query = $this->db->get();
		$afhentningsdage = $query->result_array();
		$divisionname = $this->_divisionname($division);

		if ($dag == 0)
		{

			$content = $divisionname . ': Dagens salg<br>';
			$data = array(
	               'title' => 'KBHFF Administrationsside',
	               'heading' => $divisionname . ': Dagens salg',
	               'content' => $content,
				   'kontant' => '',
				   'modtagnekontanter' => '',
				   'nets' => '',
				   'ikkeafhentet' => '',
	               'afhentningsdage' => $afhentningsdage,
	          );

			$this->load->view('v_dagens_salg', $data);
		} else {
			$this->db->select('pickupdate');
			$this->db->from('pickupdates');
			$this->db->where('uid', (int)$dag);
			$query = $this->db->get();
			$row = $query->row();
			$dagsdato = $row->pickupdate;
			$modtagnekontanter = $this->_dagens_modtagne_kontanter($division,$dagsdato);
			$ikkeafhentet = $this->_ikkeafhentet($division,$dagsdato);

			$kontant = $this->_dagens_kontantsalg($dag);
			$nets = $this->_dagens_netssalg($dag);
			$content = "$divisionname: Dagens salg $dagsdato<br>";

			$data = array(
	               'title' => 'KBHFF Administrationsside',
	               'heading' => "$divisionname: Dagens salg $dagsdato",
	               'content' => $content,
	               'afhentningsdage' => $afhentningsdage,
				   'kontant' => $kontant,
				   'modtagnekontanter' => $modtagnekontanter,
				   'ikkeafhentet' => $ikkeafhentet,
				   'nets' => $nets,
	          );
			$this->load->view('v_dagens_salg', $data);
		}

	}

	function ikke_afhentet($limit = 5)
	{

		if ($this->uri->segment(3) > 0)
		{
			$limit = $this->uri->segment(3);
		}

		$content = '';
		$ikkeafhentet = 0;
		$ikkeafhentet = array();

		$this->db->select("divisions.uid, divisions.name, divisions.shortname");
		$this->db->from('divisions');
		$this->db->where('type', 'aktiv');
		$this->db->order_by('divisions.name', 'asc');
		$query = $this->db->get();
		$divisions = $query->result_array();


		$this->db->select('pickupdate');
		$this->db->distinct();
		$this->db->from('pickupdates');
		$this->db->where('pickupdate <= curdate()');
		$this->db->order_by('pickupdate', 'desc');
		$this->db->limit($limit);
		$queryday = $this->db->get();
		$debug = $this->db->last_query();
		foreach ($queryday->result_array() as $row)
		{
			$quant = $this->_ikkeafhentet_statistik($row['pickupdate']);
			$ikkeafhentet[$row['pickupdate']] = $quant;
		}
		$data = array(
               'title' => 'KBHFF Administrationsside',
               'heading' => "Oversigt over ikke-afhentede poser",
               'content' => $content,
			   'divisions' => $divisions,
			   'debug' => $debug,
			   'ikkeafhentet' => $ikkeafhentet,
          );
		$this->load->view('v_ikke_afhentet', $data);

	}


	function opdatertransaktioner($ordre = 0)
	{
		if ($this->uri->segment(3) > 0)
		{
			$ordre = $this->uri->segment(3);
		} else {
			$ordre = $this->input->post('ordre');
		}

		$content = $this->_update_transactions_by_order($ordre);
		$data = array(
               'title' => 'KBHFF Administrationsside',
               'heading' => 'Opdatering af transaktioner',
               'content' => '<h2>' . $ordre . '</h2>' . $content,
          );

		$this->load->view('page', $data);
	}

	function grupper()
	{
        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		if ($this->uri->segment(4) > 0)
		{
			$division = $this->uri->segment(3);
			$puid = $this->uri->segment(4);
			$medlem = $this->Memberinfo->retrieve_by_medlemsnummer($puid);

			$this->db->select('name');
			$this->db->select('uid');
			$this->db->select('groupmembers.puid as member');
			$this->db->distinct();
			$this->db->from('groups');
			$this->db->join('groupmembers', 'groupmembers.group = groups.uid and ff_groupmembers.status = "aktiv" and ff_groupmembers.puid = ' . (int)$puid, 'left');
			$this->db->where('type', 'Arbejdsgruppe');
			$this->db->order_by('name');
			$query = $this->db->get();
			$arbejdsgruppe = $query->result_array();

			$this->db->select('name');
			$this->db->select('uid');
			$this->db->select('groupmembers.puid as member');
			$this->db->distinct();
			$this->db->from('groups');
			$this->db->join('groupmembers', 'groupmembers.group = groups.uid and ff_groupmembers.status = "aktiv" and ff_groupmembers.puid = ' . (int)$puid, 'left');
			$this->db->where('type', 'Projektgruppe');
			$this->db->order_by('name');
			$query = $this->db->get();
			$projektgruppe = $query->result_array();

			$this->db->select('groups.name');
			$this->db->select('groups.uid');
			$this->db->select('divisions.name as divisionname');
			$this->db->select('divisions.uid as division');
			$this->db->select('groupmembers.puid as member');
			$this->db->distinct();
			$this->db->from('groups');
			$this->db->from('divisions');
			$this->db->from('division_members');
			$this->db->join('groupmembers', 'groupmembers.group = groups.uid and ff_groupmembers.status = "aktiv" and ff_groupmembers.department = ff_division_members.division and ff_groupmembers.puid = ' . (int)$puid, 'left');
			$this->db->where('groups.type', 'Afdelingsgruppe');
			$this->db->where('division_members.division = ff_divisions.uid');
			$this->db->where('division_members.member', (int)$puid);
			$this->db->order_by('divisions.name');
			$this->db->order_by('groups.name');
			$query = $this->db->get();
			$afdelingsgruppe = $query->result_array();

			$this->db->select('chore_types.name');
			$this->db->select('chore_types.uid');
			$this->db->select('divisions.name as divisionname');
			$this->db->select('divisions.uid as division');
			$this->db->select('roles.puid as member');
			$this->db->distinct();
			$this->db->from('chore_types');
			$this->db->from('divisions');
			$this->db->from('division_members');
			$this->db->join('roles', 'roles.role = chore_types.uid and ff_roles.status = "aktiv" and ff_roles.department = ff_division_members.division and ff_roles.puid = ' . (int)$puid, 'left');
			$this->db->where('division_members.division = ff_divisions.uid');
			$this->db->where('division_members.member', (int)$puid);
			$this->db->where('chore_types.auth >', 0);
			$this->db->order_by('divisions.name');
			$this->db->order_by('chore_types.name');
			$query = $this->db->get();
			$roles = $query->result_array();

			$posts = array();
			$data = array(
	               'title' => 'KBHFF Administrationsside',
	               'heading' => 'Vedligeholdelse af gruppe-medlemskab',
	               'content' => 'Her kan du til/framelde gruppemedlemskaber.<br>',
				   'division' => $division,
				   'divisionname' => $this->_divisionname($division),
				   'puid' => $puid,
				   'message' => '',
				   'debug' => $this->db->last_query(),
				   'medlem' => $medlem['firstname'] .' ' . $medlem['middlename'] .' ' . $medlem['lastname'],
				   'projektgruppe' => $projektgruppe,
				   'arbejdsgruppe' => $arbejdsgruppe,
				   'afdelingsgruppe' => $afdelingsgruppe,
				   'roles' => $roles,
				   'posts' => $posts,
	          );

			$this->load->view('v_groups', $data);
		} else {
			if ($this->input->post('status'))
			{
				$division = $this->uri->segment(3);
				$this->db->select('uid')->from('groups')->where('groups.type <>', 'Afdelingsgruppe');
				$query = $this->db->get();
				if ($query->num_rows() > 0)
				{
					foreach ($query->result() as $row)
					{
						$status = $this->input->post('g' .$row->uid);
						$this->_updategroupmembership($row->uid, $division, $this->input->post('puid'), $status);
						// _updategroupmembership($group, $division, $puid, $status)
					}
				}

				$this->db->select('division_members.division')->from('division_members')->where('division_members.member =', $this->input->post('puid'));
				$divquery = $this->db->get();
				if ($divquery->num_rows() > 0)
				{
					foreach ($divquery->result() as $divrow)
					{
						$this->db->select('chore_types.uid')->from('chore_types')->where('chore_types.auth >', 0);
						$query = $this->db->get();
						if ($query->num_rows() > 0)
						{
							foreach ($query->result() as $row)
							{
								$status = $this->input->post('r' . $divrow->division . '-' . $row->uid);
								$this->_updaterolemembership($row->uid, $divrow->division, $this->input->post('puid'), $status);

							}
						}
						$this->db->select('uid')->from('groups')->where('groups.type', 'Afdelingsgruppe');
						$query = $this->db->get();
						if ($query->num_rows() > 0)
						{
							foreach ($query->result() as $row)
							{
								$status = $this->input->post('d' . $divrow->division . '-' . $row->uid);
								$this->_updategroupmembership($row->uid, $divrow->division, $this->input->post('puid'), $status);

							}
						}
					}
				}
				$division = $this->uri->segment(3);
				$medlem = $this->Memberinfo->retrieve_by_medlemsnummer($this->input->post('puid'));
				$message = $medlem['firstname'] .' ' . $medlem['middlename'] .' ' . $medlem['lastname'] . ' er opdateret<br>';
				$posts = array();
			} else {
				$posts = $this->Memberinfo->search_member($this->input->post('name'));
				$message = "S&oslash;gt p&aring; '" . $this->input->post('name') . "'";
			}

			$data = array(
	               'title' => 'KBHFF Administrationsside',
	               'heading' => 'Vedligeholdelse af gruppe-medlemskab',
	               'division' => $division,
				   'divisionname' => $this->_divisionname($division),
	               'content' => '',
				   'message' => $message,
				   'posts' => $posts,
			);
			$this->load->view('v_groups', $data);
		}
	}

	function _updategroupmembership($group, $division, $puid, $status){
		//group 	department 	puid 	status 	note 	valid_from 	expires
		if ($status > '')
		{
			$active = 'aktiv';
		} else {
			$active = '';
		}
		$query = $this->db->query('replace into `ff_groupmembers` set `group` = ' . (int)$group . ', puid = ' . (int)$puid . ', department = ' . (int)$division . ', status = "' . $active . '", note = "upd", valid_from = curdate(), expires = date_add(now(), interval 1 year) ' );
	}

	function _updaterolemembership($role, $division, $puid, $status){
		// role 	level 	puid 	department 	auth_by 	valid_from 	expires
		if ($status > '')
		{
			$active = 'aktiv';
		} else {
			$active = '';
		}
		$query = $this->db->query('replace into `ff_roles` set `role` = ' . (int)$role . ', puid = ' . (int)$puid . ', level = 1, department = ' . (int)$division . ', status = "' . $active . '", auth_by = "' . $this->session->userdata('uid') . '", valid_from = curdate(), expires = date_add(now(), interval 1 year) ' );
	}


    function _checkpickupdate($date, $division) {

		$this->db->select('pickupdate');
		$this->db->from('pickupdates');
		$this->db->where('division', (int)$division);
		$this->db->where('pickupdate', $date);
		$query = $this->db->get();
		return $query->num_rows();
	}

    function _update_transactions_by_order($orderno) {

		if ($orderno == 0)
		{
			$select = '';
		} else {
			$select = 'AND ff_orderhead.orderno = ' . (int)$orderno . ' ';
		}
		$query = $this->db->query('SELECT
		ff_orderhead.orderno, ff_orderlines.puid, ff_orderlines.amount, ff_orderhead.status1, ff_orderhead.status2, ff_orderhead.cc_trans_no, ff_orderhead.cc_trans_no
		FROM ff_orderlines, ff_orderhead
		WHERE ff_orderlines.orderno = ff_orderhead.orderno ' . $select .
		'AND ((ff_orderhead.status1 = "kontant") or (ff_orderhead.status1 = "nets"))');
		$ret = '';
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$ret .= $this->_update_transactions($row->orderno, $row->puid, $row->amount, $row->status1, $row->status2, $row->cc_trans_no);
			}
		}
		return $ret;
	}

function _update_transactions($orderno, $puid, $amount, $status1, $status2, $cc_trans_no)
{
	$amount = doubleval($amount);
	$orderno = doubleval($orderno);
	$puid = doubleval($puid);
	if ($this->_check_transaction($orderno, $puid) == 0)
	{
		$this->db->set('puid', $puid);
		$this->db->set('amount', $amount);
		$this->db->set('authorized_by', $status2 . '');
		$this->db->set('orderno', $orderno);
		if ($status1 == 'kontant')
		{
			$this->db->set('method', 'kontant');
		} else {
			$this->db->set('method', 'nets');
		}
		$this->db->set('trans_id', "$cc_trans_no");
		$this->db->set('item', 0);
		$this->db->set('created', 'now()', FALSE);
		$this->db->insert('transactions');
		$ret = $this->db->last_query();
	} else {
		$ret = 'ingen update, ordre ' . $orderno . ' - ' . $puid;
	}
	return $ret . "<br>\n";
} // _update_transactions

	function _check_transaction($orderno, $puid)
	{
		$query = $this->db->query('SELECT
		ff_transactions.puid
		FROM ff_transactions
		WHERE ff_transactions.orderno = ' . (int)$orderno . ' ' .
		'AND ff_transactions.puid = ' . (int)$puid);
		return $query->num_rows();
	}


    function liste() {
		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');
        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		$permissions = $this->session->userdata('permissions');

		if ($this->uri->segment(3) === 'delete')
		{
			$division = $this->uri->segment(4);
			$pickupdateuid = $this->uri->segment(5);
			$pickupdateitem = $this->uri->segment(6);
			$msg = $this->_deletepickupdate($division,$pickupdateuid, $pickupdateitem);
		} else {
			$division = $this->input->post('division');
			$msg = '';
		}
		$this->_displayliste($division, $msg);
    }

    function medlemmer($division = 0) {
		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');
		if ($this->uri->segment(3) > 0)
		{
			$division = $this->uri->segment(3);
		} else {
			$division = $this->input->post('division');
		}

		$permissions = $this->session->userdata('permissions');
		$p_administrator = $this->Memberinfo->checkpermission($permissions, 'Administrator', $division);

		if (! ($p_administrator))
			redirect(base_url().'index.php/logud');

		$this->viewdata['name'] = $this->input->post('name');

		if ($this->input->post('name') != '')
		{
			$this->viewdata['members'] = $this->Memberinfo->search_member($this->input->post('name'), $division);
			$this->viewdata['name'] = $this->input->post('name');
		}	else {
			$this->viewdata['name'] = '';
		}
		$this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
		$this->javascript->compile();
		$this->viewdata['title'] = 'Rediger medlemmer';
		$this->viewdata['heading'] = 'Rediger medlemmer';
		$this->viewdata['division'] = $division;
		$this->viewdata['divisionname'] = $this->_divisionname($division);
		$this->load->view('v_admin_medlemsfunktioner', $this->viewdata);
	}

    function afdinfo($info) {

		if (! intval($this->session->userdata('uid')) > 0)
			redirect('/login');

		// If admin is admin for more than one division
		if ($this->input->post('division') > 0)
		{
			$division = $this->input->post('division');
		}
		if ($this->uri->segment(3) > 0)
		{
			$division = $this->uri->segment(3);
		}

		$permissions = $this->session->userdata('permissions');
		$p_administrator = $this->Memberinfo->checkpermission($permissions, 'Administrator', $division);

		if (! ($p_administrator))
			redirect(base_url().'index.php/logud');

		if ($this->input->post('status') == 'update')
		{
			$this->db->set('support', $this->input->post('support'));
			$this->db->set('welcome', $this->input->post('welcome'));
			$this->db->where('division', $this->input->post('division'));
			$this->db->update('division_newmemberinfo');
			$message = 'Informationen er opdateret';
		}


		$this->db->select('division_newmemberinfo.support, division_newmemberinfo.welcome, division_newmemberinfo.division');
		$this->db->from('ff_division_newmemberinfo, ff_division_members');
		if ($division == 0)
		{
			$this->db->where('ff_division_newmemberinfo.division = ff_division_members.division');
		} else {
			$this->db->where('ff_division_newmemberinfo.division', (int)$division);
		}
		$this->db->where('division_members.member', $this->session->userdata('uid'));
		$query = $this->db->get();

		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$support = $row->support;
			$welcome = $row->welcome;
			$division = $row->division;
		} else {
			$support = 'it@kbhff.dk';
			$welcome = 'Du kan tilmelde dig vagter her: http://kbhff.wikispaces.com/Vagtplan';
		}

		$this->viewdata['title'] = 'Rediger afdelingsinformation';
		$this->viewdata['heading'] = 'Rediger afdelingsinformation';
		$this->viewdata['message'] = $message;
		$this->viewdata['division'] = $division;
		$this->viewdata['welcome'] = $welcome;
		$this->viewdata['support'] = $support;
		$this->viewdata['divisionname'] = $this->_divisionname($division);
		$this->load->view('v_dept_info', $this->viewdata);
	}

	function _deletepickupdate($division, $pickupdateuid, $pickupdateitem)
	{
		if (($division > 0)&&($pickupdateuid > 0)&&($pickupdateitem > 0))
		{
			if ($this->_checkpickupdateorders($pickupdateuid, $pickupdateitem)> 0)
			{
				$msg = 'Afhentningsdag kan ikke slettes, der er ordrer.';
			} else {
				if ($pickupdateitem == FF_GROCERYBAG)
				{
					$this->db->where('uid', $pickupdateuid);
					$this->db->where('division', $division);
					$this->db->delete('pickupdates');
				}
				$this->db->where('pickupday', $pickupdateuid);
				$this->db->where('item', $pickupdateitem);
				$this->db->delete('ff_itemdays');
				if ($this->db->affected_rows() > 0)
				{
					$msg = 'Afhentningsdag er slettet';
				} else {
					$msg = 'Afhentningsdag/vare fandtes ikke!';
				}
			}
		} else {
			$msg = 'Afhentningsdag/afdeling/vare fandtes ikke!';
		}
		return $msg;
	}

	function _checkpickupdateorders($pickupdateuid, $pickupdateitem)
	{
		if ($pickupdateitem == FF_GROCERYBAG)
		{
			$query = $this->db->query('SELECT
			ff_pickupdates.pickupdate
			FROM ff_orderlines, ff_orderhead, ff_pickupdates
			WHERE ff_orderlines.orderno = ff_orderhead.orderno
			AND ((ff_orderhead.status1 = "kontant") or (ff_orderhead.status1 = "nets"))
			AND ff_orderlines.iteminfo = ff_pickupdates.uid
			AND ff_pickupdates.uid = ' . (int)$pickupdateuid);
		} else {
			$query = $this->db->query('SELECT
			ff_pickupdates.pickupdate
			FROM ff_orderlines, ff_orderhead, ff_pickupdates
			WHERE ff_orderlines.orderno = ff_orderhead.orderno
			AND ((ff_orderhead.status1 = "kontant") or (ff_orderhead.status1 = "nets"))
			AND ff_orderlines.item = '. (int)$pickupdateitem .'
			AND ff_orderlines.iteminfo = ff_pickupdates.uid
			AND ff_pickupdates.uid = ' . (int)$pickupdateuid);
		}
		return $query->num_rows();
	}

	function _ikkeafhentet($division, $date)
	{
		$query = $this->db->query('SELECT
		ff_pickupdates.pickupdate, ff_orderlines.puid, ff_persons.firstname, ff_persons.middlename, ff_persons.lastname,ff_orderlines.orderno, ff_orderlines.quant, ff_orderlines.item, ff_items.units, ff_items.measure, ff_producttypes.explained
FROM ff_orderlines, ff_orderhead, ff_items, ff_producttypes, ff_pickupdates, ff_divisions, ff_persons
WHERE ff_orderlines.orderno = ff_orderhead.orderno
AND ff_orderlines.item = ff_items.id
AND ff_items.producttype_id = ff_producttypes.id
AND ff_orderlines.iteminfo = ff_pickupdates.uid
AND ff_divisions.uid = ff_pickupdates.division
AND ff_pickupdates.division = ff_items.division
		AND ff_orderlines.puid = ff_persons.uid
		AND ((ff_orderhead.status1 = "kontant") or (ff_orderhead.status1 = "nets"))
		AND ff_orderlines.status2 <> "udleveret"
		AND ff_pickupdates.pickupdate = "' . addslashes($date) . '"
		AND ff_pickupdates.division = ' . (int)$division);
		$row = $query->row();
		$ikkeafhentet = '';
		if ($query->num_rows() > 0)
		{
			$ikkeafhentet = '<table cellspacing="4">';
			foreach ($query->result() as $row)
			{
				$ikkeafhentet .= '<tr><td>Ordre ' . $row->orderno . '</td><td>' . $row->quant .' '. $row->measure . ' ' . $row->explained . '</td><td>medlem ' . $row->puid . '</td><td>' . $row->firstname . ' ' . $row->middlename . ' ' . $row->lastname . '</td></tr>';
			}
			$ikkeafhentet .= '<table>';
		} else {
			$ikkeafhentet = 'Alt afhentet.<br>';
		}
		return $ikkeafhentet;

	}

	function _ikkeafhentet_statistik($date)
	{

		$ret = array();
		$query = $this->db->query('SELECT
		SUM(ff_orderlines.quant) as quant, ff_pickupdates.pickupdate, ff_items.division, ff_producttypes.explained
		FROM (ff_orderlines, ff_orderhead, ff_items, ff_producttypes, ff_pickupdates, ff_divisions)
		WHERE ff_orderlines.orderno = ff_orderhead.orderno
		AND ff_orderlines.item = ff_items.id
		AND ff_items.producttype_id = ff_producttypes.id
		AND ff_orderlines.iteminfo = ff_pickupdates.uid
		AND ff_divisions.uid = ff_pickupdates.division
		AND ff_pickupdates.division = ff_items.division
		AND ff_pickupdates.pickupdate = "' . addslashes($date) . '"
		AND ((ff_orderhead.status1 = "kontant") or (ff_orderhead.status1 = "nets"))
		AND ff_orderlines.status2 <> "udleveret"
		GROUP by ff_pickupdates.pickupdate, ff_items.division
		ORDER BY ff_pickupdates.pickupdate desc, ff_items.division,  ff_producttypes.id');

		$row = $query->row();
		return $query->result_array();

	}

	function excel($division = 5) {

		/** Include PHPExcel */
		require_once 'PHPExcel.php';
		$divisionname = $this->_divisionname($division);
//		$cellval = trim(iconv("UTF-8","ISO-8859-1",$cell->getValue())," \t\n\r\0\x0B\xA0");
		$this->load->helper('date');

		$locale = 'da';
		date_default_timezone_set('Europe/London');
		$now = Date("H:i d-m-Y");

		// Create a workbook
		$objPHPExcel = new PHPExcel();
		PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
		$objPHPExcel->getProperties()->setCreator("KBHFF Medlemssystem");
		$objPHPExcel->getProperties()->setLastModifiedBy("KBHFF Medlemssystem $now");
		$objPHPExcel->getProperties()->setTitle( utf8_decode($divisionname) . ' medlemsliste');
		$objPHPExcel->getProperties()->setSubject("Medlemsliste");
		$objPHPExcel->getProperties()->setDescription('KBHFF ' . $divisionname . "medlemsliste udskrevet $now");
		$objPHPExcel->getProperties()->setKeywords("KBHFF medlemsliste");
		$objPHPExcel->getProperties()->setCategory("medlemsliste");
		$objPHPExcel->getSheet(0);
		$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
		$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

		// Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle(substr ( $divisionname . ' ' . Date("H.i d-m-Y"), 0, 31 ));

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$objWorksheet->getTabColor()->setRGB('33cc66');



		// Creating a title
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$objWorksheet->getStyle('A1:I1')->getFont()->setSize(13)->getColor()->setARGB(PHPExcel_Style_Color::COLOR_DARKGREEN);
		$objWorksheet->setCellValueByColumnAndRow(0, 1, 'Medlem #');
		$objWorksheet->setCellValueByColumnAndRow(1, 1, 'Fornavn');
		$objWorksheet->setCellValueByColumnAndRow(2, 1, 'Mellemnavn');
		$objWorksheet->setCellValueByColumnAndRow(3, 1, 'Efternavn');
		$objWorksheet->setCellValueByColumnAndRow(4, 1, 'Email');
		$objWorksheet->setCellValueByColumnAndRow(5, 1, 'Telefon');
		$objWorksheet->setCellValueByColumnAndRow(6, 1, 'Oprettet');
		$objWorksheet->setCellValueByColumnAndRow(7, 1, 'Aktiv');
		$objWorksheet->setCellValueByColumnAndRow(8, 1, 'Note');

		// Autoset widths
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$objWorksheet->getColumnDimension('A')->setAutoSize(true);
		$objWorksheet->getColumnDimension('B')->setAutoSize(true);
		$objWorksheet->getColumnDimension('C')->setAutoSize(true);
		$objWorksheet->getColumnDimension('D')->setAutoSize(true);
		$objWorksheet->getColumnDimension('E')->setAutoSize(true);
		$objWorksheet->getColumnDimension('F')->setAutoSize(true);
		$objWorksheet->getColumnDimension('G')->setAutoSize(true);
		$objWorksheet->getColumnDimension('H')->setAutoSize(true);
		$objWorksheet->getColumnDimension('I')->setAutoSize(true);

		if ($division > 0)
		{
			$select = ', ff_division_members where ff_division_members.member = ff_persons.uid AND ff_division_members.division = ' . (int)$division;
		}
		$query = $this->db->query('SELECT uid,firstname, middlename, lastname, email, tel, created, active, remark FROM (ff_persons) left join ff_persons_info on (ff_persons.uid = ff_persons_info.puid) ' . $select . ' order by firstname');
		$rowformat1 = array(
		'font' => array(
			'bold' => false,
			),
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'color' =>  array(
				'rgb' =>  'd9ffe2',
				),
			)
		);

		$rowformat2 = array(
		'font' => array(
			'bold' => false,
			)
		);

		$currentrow = 2;
		foreach ($query->result() as $row)
		{
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValueByColumnAndRow(0, $currentrow, ("$row->uid"))
				->setCellValueByColumnAndRow(1, $currentrow, ("$row->firstname"))
				->setCellValueByColumnAndRow(2, $currentrow, ("$row->middlename"))
				->setCellValueByColumnAndRow(3, $currentrow, ("$row->lastname"))
				->setCellValueByColumnAndRow(4, $currentrow, ("$row->email"))
				->setCellValueByColumnAndRow(5, $currentrow, ("$row->tel"))
				->setCellValueByColumnAndRow(6, $currentrow, ("$row->created"))
				->setCellValueByColumnAndRow(7, $currentrow, ("$row->active"))
				->setCellValueByColumnAndRow(8, $currentrow, ("$row->remark"));
			$dynformat = alternator('rowformat1', 'rowformat2');
			$format = $$dynformat;
			$objPHPExcel->getActiveSheet()->getStyle('A' . $currentrow .':I' . $currentrow)->applyFromArray($format);
			$currentrow++;
		}

		// Align
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$highestRow = $objWorksheet->getHighestRow();
		$objPHPExcel->getActiveSheet()->getStyle('F1:F' . $highestRow)
			->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle('A1:A' . $highestRow)
			->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

		// Set repeated headers
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		// Specify printing area
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
		$objPHPExcel->getActiveSheet()->getPageSetup()->setPrintArea('A1:' . $highestColumn . $highestRow );


		// Redirect output to a clients web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="KBHFF medlemsliste ' . $divisionname . ' ' . $now .'.xls"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');

	}


	function medlemsliste($division = 5) {

        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		if ($division > 0)
		{
			$select = ', ff_division_members where ff_division_members.member = ff_persons.uid AND ff_division_members.division = ' . (int)$division;
			$divisionname = $this->_divisionname($division);
		} else {
			$select = '';
			$divisionname = 'alle';
		}
		$query = $this->db->query('SELECT uid,firstname, middlename, lastname, email, UNIX_TIMESTAMP(last_login) AS last_login, UNIX_TIMESTAMP(created) AS created FROM ff_persons' . $select . ' order by firstname');
		$result = $query->result_array();

		$data = array(
               'title' => 'KBHFF medlemsliste',
               'heading' => "Afdeling $divisionname",
               'content' => $result,
          );

		$this->load->view('v_medlemsliste', $data);
	}

	function initmedlem($division = 8) {

        $this->jquery->script('/ressources/jquery-1.6.2.min.js', TRUE);
        $this->javascript->compile();

		if ($division > 0)
		{
			$select = ', ff_division_members where ff_division_members.member = ff_persons.uid AND ff_persons.PASSWORD is null AND ff_persons.user_activation_key is null AND ff_division_members.division = ' . (int)$division;
			$divisionname = $this->_divisionname($division);
			$query = $this->db->query('SELECT uid,firstname, middlename, lastname, email, UNIX_TIMESTAMP(last_login) AS last_login, UNIX_TIMESTAMP(created) AS created FROM ff_persons' . $select . ' order by firstname limit 200');
			$result = $query->result_array();
			$medlemmer = '';
			foreach ($query->result() as $row)
			{
				$this->Personsmodel->send_new_membermail($row->uid);
				$medlemmer .= 'Medlem ' . $row->uid . ', ' . $row->firstname . ' ' . $row->middlename . ' ' . $row->lastname . ': ' . $row->email . "<br>\n";
			}

			$data = array(
               'title' => 'Udsendelse af kodeordbrev til medlemmer',
               'heading' => "Udsendelse af kodeordbrev til medlemmer: Afdeling $divisionname",
               'content' => $medlemmer,
	          );
		} else {
			$data = array(
	               'title' => 'KBHFF medlemsliste',
	               'heading' => "Fejl: Afdeling skal angives",
	               'content' => $result,
	          );
		}

		$this->load->view('page', $data);
	}

	function _dagens_kontantsalg($divisionday)
	{
		$kontant = '';
			$query = $this->db->query('SELECT sum( ff_orderlines.quant ) AS quantsum , sum( ff_orderlines.amount ) AS amountsum , ff_orderlines.item, ff_items.units, ff_items.measure, ff_producttypes.explained, DATE_FORMAT(ff_orderlines.created,"%d-%m-%Y") as created
FROM ff_orderlines, ff_orderhead, ff_items, ff_producttypes, ff_pickupdates, ff_divisions
WHERE ff_orderlines.orderno = ff_orderhead.orderno
AND ff_orderhead.status1 = "kontant"
AND ff_orderlines.item = ff_items.id
AND ff_items.producttype_id = ff_producttypes.id
AND ff_orderlines.iteminfo = ff_pickupdates.uid
AND ff_divisions.uid = ff_pickupdates.division
AND ff_pickupdates.division = ff_items.division
AND ff_pickupdates.uid = ' . (int)$divisionday . '
GROUP BY year(ff_orderlines.created),month(ff_orderlines.created),day(ff_orderlines.created),ff_producttypes.explained
ORDER BY ff_orderlines.created,ff_producttypes.explained');
			$row = $query->row();
			if ($query->num_rows() > 0)
			{
				$quantsum = 0;
				$amountsum = 0;
				$kontant = '<table>';
				foreach ($query->result() as $row)
				{
					$kontant .= '<tr><td>' . $row->created . '</td><td width="150">varenummer ' . $row->item .'</td><td align="right">' . $row->quantsum . '</td><td width="200">' . $row->measure . '&nbsp;&nbsp;&nbsp;' . $row->explained . '</td><td align="right">'. $row->amountsum . ' kr.</td></tr>';
					$quantsum += $row->quantsum;
					$amountsum += $row->amountsum;
				}
				$kontant .= '</table>';
			} else {
				$kontant = 'Intet kontantsalg.<br>';
			}
		return $kontant;
	}

	function _dagens_modtagne_kontanter($division,$day)
	{
		$kontantermodtaget  = '';
			$query = $this->db->query('SELECT sum( ff_orderlines.quant ) AS quantsum , sum( ff_orderlines.amount ) AS amountsum , ff_orderlines.item, ff_items.units, ff_items.measure, ff_producttypes.explained, DATE_FORMAT(ff_orderlines.created,"%d-%m-%Y") as created
FROM ff_orderlines, ff_orderhead, ff_items, ff_producttypes
WHERE ff_orderlines.orderno = ff_orderhead.orderno
AND ff_orderhead.status1 = "kontant"
AND ff_orderlines.item = ff_items.id
AND ff_items.producttype_id = ff_producttypes.id
AND ff_items.division = ' . (int)$division . ' ' .
'AND DATE_FORMAT(ff_orderhead.created,"%Y-%m-%d") = "' . addslashes($day) . '" ' .
'GROUP BY year(ff_orderlines.created),month(ff_orderlines.created),day(ff_orderlines.created),ff_orderlines.item
ORDER BY ff_orderlines.created, ff_orderlines.item');

			$row = $query->row();
			if ($query->num_rows() > 0)
			{
				$quantsum = 0;
				$amountsum = 0;
				$kontantermodtaget = '<table>';
				foreach ($query->result() as $row)
				{
					$kontantermodtaget .= '<tr><td>' . $row->created . '</td><td width="150">varenummer ' . $row->item .'</td><td align="right">' . $row->quantsum . '</td><td width="200">' . $row->measure . '&nbsp;&nbsp;&nbsp;' . $row->explained . '</td><td align="right">'. $row->amountsum . ' kr.</td></tr>';
					$quantsum += $row->quantsum;
					$amountsum += $row->amountsum;
				}
				$kontantermodtaget .= '</table>';
			} else {
				$kontantermodtaget = 'Intet kontantsalg.<br>';
			}
		return $kontantermodtaget;
	}



	function _dagens_netssalg($divisionday)
	{
		$nets = '';
			$query = $this->db->query('SELECT sum( ff_orderlines.quant ) AS quantsum , sum( ff_orderlines.amount ) AS amountsum , ff_orderlines.item, ff_items.units, ff_items.measure, ff_producttypes.explained, ff_orderlines.created
FROM ff_orderlines, ff_orderhead, ff_items, ff_producttypes, ff_pickupdates, ff_divisions
WHERE ff_orderlines.orderno = ff_orderhead.orderno
AND ff_orderhead.status1 = "nets"
AND ff_orderlines.item = ff_items.id
AND ff_items.producttype_id = ff_producttypes.id
AND ff_orderlines.iteminfo = ff_pickupdates.uid
AND ff_divisions.uid = ff_pickupdates.division
AND ff_pickupdates.division = ff_items.division
AND ff_pickupdates.uid = ' . (int)$divisionday . '
GROUP BY ff_orderlines.item
ORDER BY ff_producttypes.explained');
			$row = $query->row();
			if ($query->num_rows() > 0)
			{
				$quantsum = 0;
				$amountsum = 0;
				$nets = '<table>';
				foreach ($query->result() as $row)
				{
					$nets .= '<tr><td>On-line k&oslash;b</td><td width="150">varenummer ' . $row->item .'</td><td align="right">' . $row->quantsum . '</td><td width="200">' . $row->measure . '&nbsp;&nbsp;&nbsp;' . $row->explained . '</td><td align="right">'. $row->amountsum . ' kr.</td></tr>';
					$quantsum += $row->quantsum;
					$amountsum += $row->amountsum;
				}
				$nets .= '</table>';
			} else {
				$nets = 'Intet on-linesalg<br>';
			}
		return $nets;
	}

	private function _divisionname($division)
	{
			$this->db->select('name');
			$this->db->from('divisions');
			$this->db->where('uid', (int)$division);
			$query = $this->db->get();
			$row = $query->row();
			return $row->name;
	}

	private function _displayliste($division, $msg)
	{
		$divisionname = $this->_divisionname($division);

		$bagdays = '';
		$q2 = $this->db->query('select id, explained from ff_producttypes where bag = "Y" order by sortkey');
		$bagdays = $q2->result_array();

		$bdsel1 = '';
		$bdsel2 = '';
		foreach ($bagdays as $bagday)
		{
			$bdsel1 .= ', p' . $bagday['id'] . '.explained as p' . $bagday['id'] . 'e ';
			$bdsel2 .= 'left join ff_producttypes as p' . $bagday['id'] . ' on p' . $bagday['id'] . '.id = ff_itemdays.item and p' . $bagday['id'] . '.id = ' . $bagday['id'] . ' ';
		}
		$q3 = $this->db->query("select division,
		pickupdate as pickupdatesort,
		date_format(`ff_pickupdates`.`pickupdate`,'%d/%m/%Y') as pickupdate,
		date_format(`ff_itemdays`.`lastorder`,'%d/%m/%Y %k:%i') as lastorder, uid, item $bdsel1
		from (ff_pickupdates, ff_itemdays)
		$bdsel2
		where ff_pickupdates.uid = ff_itemdays.pickupday
		and division = $division
		and pickupdate >= curdate()
		order by pickupdatesort, p47.sortkey");
		$bagcollectdays = $q3->result_array();


		$content = 'Afhentningsdage for ' . $divisionname . ':<br>';
		$data = array(
               'title' => 'KBHFF Administrationsside',
               'heading' => 'Afhentningsdage for ' . $divisionname,
               'msg' => $msg,
			   'bagdays' => $bagdays,
			   'bagcollectdays' => $bagcollectdays,
          );

		$this->load->view('v_afhentningliste', $data);

	}

	private function _getfuturepickupdays($division)
	{
		$divisionname = $this->_divisionname($division);
		$return = '<optgroup  label="' .$divisionname . '">';
			$query = $this->db->query("SELECT distinct
			ff_pickupdates.pickupdate, ff_pickupdates.uid
			FROM (ff_pickupdates)
			LEFT JOIN (ff_producttypes as pt) ON pt.bag = 'Y' and pt.id != ' . FF_GROCERYBAG .'
			LEFT JOIN ff_itemdays ON ff_itemdays.item = pt.id AND ff_itemdays.pickupday = ff_pickupdates.uid and ff_itemdays.lastorder is null
			WHERE `ff_pickupdates`.`division` = $division AND ff_pickupdates.pickupdate >= curdate()
			ORDER BY ff_pickupdates.pickupdate desc");
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$return .= '<option value="' . $row->uid .'">' . $row->pickupdate . "</option>\n";
			}
		}
		$return .= '</optgroup>' ."\n";
		return $return;
	}

} // class Admin


	include("ressources/.sendmail.php");

/* End of file admin.php */
/* Location: ./application/controllers/admin.php */
