<?
class Register_model extends CI_Model {
    public function __construct()
    {
            $this->load->database();
            $this->load->model('common_model');
            $this->load->library('session');
            // $config = Array(
            //     'protocol' => 'smtp',
            //     'smtp_host' => 'ssl://smtp.googlemail.com',
            //     'smtp_port' => 465,
            //     'smtp_user' => 'vitaljan.spam@gmail.com', 
            //     'smtp_pass' => '', 
            //     'charset' => 'UTF-8',
            // );
            $this->load->library('email');
            // $this->email->initialize($config);
    }

    public function check_email () {
        // returns error code:
        // 0 - success
        // 1 - invalid email
        // 2 - email already registered
        // 3 - error insert to database
        $email = $this->input->post('email');
        $email = strtolower(trim($email));
        // валидация email:
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return ['error'=>1]; 
        //проверяет наличие email в базе
        $res = $this->db->select('email')
            ->from('users')
            ->where('email', $email)
            ->get()->result();
        if (count($res) > 0) return ['error'=>2];
        // создаем запись
        $rand_pas = $this->common_model->gen_password();
        $hash = hash('md4', $rand_pas);
        if ($this->db->insert('users', [
            'email'=> $email,
            'password'=> $hash,
            'active'=> 1,
            'comment'=> $rand_pas])
            )
            {
                $this->email->from('vitaljan.spam@gmail.com', 'Need 4 Ride');
                $this->email->to('vitaljan@gmail.com');
                $this->email->subject('Реєстрація Need4Ride');
                $this->email->message("Вітаємо!\nВи зареєструвались в команді NEED FOR A RIDE!\nВаш пароль для входу: {$rand_pas}\nЩоб мати можливість створювати власні походи/покатеньки або приєднуватись до інших, авторизуйтесь на сайті need4ride.com та заповніть Ваш профіль користувача.");
                $this->email->send();
                return ['error'=>0, 'sendmail'=>"Ваш пароль {$rand_pas}"];
            } else return ['error'=>3];
    }

    public function login () {
        // returns error code:
        // 0 - success
        // 1 - unregistered email
        // 2 - invalid password
        // 3 - other error
        $email = $this->input->post('email');
        $email = strtolower(trim($email));
        $res = $this->db->select('id, name, email, password, active, confirmed, role, city, img')
            ->from('users')
            ->where('email', $email)
            ->get()->result();
        if (count($res) < 1) return ['error'=> 1];
        $pass = $this->input->post('password');
        if (!$pass || $pass == '') return ['error'=> 2];
        $hash = hash('md4', $pass);
        if ($hash != $res[0]->password) return ['error'=> 2];
        // session start
        $this->session->set_userdata([
            'user_id' => (int)$res[0]->id,
            'name' => $res[0]->name,
            'email' => $res[0]->email,
            'active' => $res[0]->active,
            'confirmed' => $res[0]->confirmed,
            'role' => $res[0]->role,
            'city' => $res[0]->city,
            'img' => $res[0]->img,
        ]);
        return ['error'=>0, 'name'=>$res[0]->name];
    }

    
    public function logout() {
        $this->session->sess_destroy();
    }

    public function is_login() {
        if ($this->session->active) return true;
        return false;
    }

    public function is_confirmed() {
        if ($this->session->active && $this->session->confirmed) return true;
        return false;
    }

    public function user_id() {
        if ($this->is_login()) return $this->session->user_id;
        return false;
    }

    public function load_file() {
        $config['upload_path'] = FCPATH.'/uploads/';
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size']     = '500';
        $config['max_width'] = '400';
        $config['max_height'] = '400';
        $config['overwrite'] = false;
        $this->load->library('upload', $config);
        $this->upload->do_upload('file');
        return [
            'file'=>$this->upload->data('file_name'),
            'width'=>$this->upload->data('image_width'),
            'height'=>$this->upload->data('image_height'),
            'type'=>$this->upload->data('image_type'),
            'size'=>$this->upload->data('file_size'),
            'errors'=>$this->upload->display_errors()
        ];
    }

    public function profile_save() {
        $res = [];
        $post = $this->input->post();
        // error codes:
        // 1 - logout
        // 2 - empty username
        // 3 - no exclusive username
        // 4 - empty city
        if (!$this->is_login()) { // no login
            $res['error'] = 1;
            return $res;
        }

        // validate:

        $name = trim($post['name']);
        if ($name == '' || $post['name'] == null) { // empty name validate
            $res['error'] = 2;
            return $res;
        }

        $search = $this->db->select('name, id') // exclusive username validate
            ->from('users')
            ->where('lower(name)', strtolower($name))
            ->get()->result();
        if (count($search) > 0 && $search[0]->id != $this->user_id()) {
            $res['error'] = 3;
            return $res;
        };

        if (!$post['city']) { // empty city validate
            $res['error'] = 4;
            return $res;
        }

        $this->db->set('name', $name)
            ->set('city', $post['city'])
            ->set('gender', (int)$post['gender'])
            ->set('favor_style', (int)$post['style'])
            ->set('about', $post['about'])
            ->set('birthday', $post['birthday'])
            ->set('confirmed', 1);

        if ($post['filename'] && $post['filename'] != '')
            $this->db->set('img', $post['filename']);
            
        $res['success'] = $this->db->where('id', $this->user_id())
            ->update('users');
        return $res;
    }

    public function get_profile_data() {
        $res = ['name'=>null, 'img'=>null, 'active'=>null, 'confirmed'=>null];
        if (!$this->is_login()) return $res;
        $request = $this->db->select('name, img, active, confirmed')
            ->from('users')
            ->where('id', $this->user_id())
            ->get()->result();
        $res['name'] = $request[0]->name;
        $res['img'] = $request[0]->img;
        $res['active'] = $request[0]->active;
        $res['confirmed'] = $request[0]->confirmed;
        return $res;
    }
}
?>


