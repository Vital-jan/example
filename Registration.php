<?
class Registration extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('register_model');
    }
    
    public function register() {
        echo json_encode($this->register_model->check_email());
    }

    public function login_start() {
        echo json_encode($this->register_model->login());
    }

    public function logout() {
        $this->register_model->logout();
        header("Location: /");
    }

    public function load_file() {
        echo json_encode($this->register_model->load_file());
    }

    public function profile_save () {
        echo json_encode($this->register_model->profile_save());
    }
}
?>