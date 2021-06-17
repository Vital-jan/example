<?
class Common_model extends CI_Model {

    public function gen_password($length = 8){ // random password generate	
        $chars = 'qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP'; 
        $size = strlen($chars) - 1; 
        $password = ''; 
        while($length--) {
            $password .= $chars[random_int(0, $size)]; 
        }
        return $password;
    }

    public function load_json(){
        $json = file_get_contents(FCPATH.'assets/json/ukraine.json');
        if ($json) return json_decode($json);
    }

    public function get_user_data () {
        $this->load->database();
        $this->load->library('session');
        $res = ["error"=>0];
        $request = $this->db->select('*')
            ->from('users')
            ->where('id', $this->session->user_id)
            ->get()->result();
        if (!$request || count($request) == 0) {
            $res['error'] = 1;
            return $res;
        }
        $res['data'] = $request[0];
        return $res;
    }
}
?>