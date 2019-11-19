<?php
session_start();

require('classes/mysqliConn.php');
require('classes/functions.php');

class main extends mysqliConn {
    private $mysqli;
    private $token;
    private $stringlogin;
    private $maxLoginAttemps = 5;

    public $title = 'Main';
    public $get = array();
    public $post = array();
    public $session = array();
    public $checkToken = false;
    public $loginCheck = false;

    public $err = '';
    public $suc = '';

    function __construct($get, $post) {
        $this->mysqli = parent::mysqliConn();
        $this->get = $this->cleanvar($get);
        $this->post = $this->cleanvar($post);
        $this->session = $this->cleanvar($_SESSION);

        $this->token = $this->generateToken();
        $this->checkToken = $this->checkToken();
        $this->checkCaptcha = $this->checkCaptcha();

        $this->loginCheck = $this->loginCheck();

        if(isset($this->get['err']) && $this->get['err'] != '') {
            $this->err = '<div class="err">' . $this->get['err'] . '</div>';
        }

        if(isset($this->get['suc']) && $this->get['suc'] != '') {
            $this->suc = '<div class="suc">' . $this->get['suc'] . '</div>';
        }

        $this->redirect();
    }

    public function cleanvar($var) {
        $array = array();
        if(is_array($var)) {
            foreach($var as $key => $value) {
                $array[$key] = $this->cleanvar($value);
            }
        } else {
            return trim($this->mysqli->real_escape_string($var));
        }
        return $array;
    }
    
    /* START - Login functions */
    private function loginHandler() {
        $err = '¡Error!';
        if($this->checkToken) {
            if($this->checkCaptcha) {
                $email = isset($this->post['email']) && filter_var($this->post['email'], FILTER_VALIDATE_EMAIL) ? $this->post['email'] : '';
                $password = SHA1(isset($this->post['password']) ? $this->post['password'] : '');

                if ($stmt = $this->mysqli->prepare('SELECT iduser, fname, lname, passdb, status FROM user WHERE email = ? LIMIT 1')) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($iduser, $fname, $lname, $passdb, $status);
                    $stmt->fetch();

                    if ($stmt->num_rows == 1) {
                        if ($this->checkBrute($iduser)) {
                            $err .= 'Ha hecho mas de ' . $this->maxLoginAttemps . ' intentos de inicio de sesión en los últimos 30 minutos. Debe esperar una hora para ser habilitado de nuevo o intente recuperar su contraseña.';
                            
                        } else {
                            if (hash_equals($passdb, $password)) {
                                if($status == 1) {
                                    $user_browser = $_SERVER['HTTP_USER_AGENT'] . $this->userIp();

                                    $_SESSION['iduser'] = $iduser;
                                    $_SESSION['email'] = $email;
                                    $_SESSION['fname'] = $fname;
                                    $_SESSION['lname'] = $lname;
                                    $_SESSION['status'] = $status;
                                    $_SESSION['stringlogin'] = hash('sha512', $password . $user_browser);

                                    $sql = 'INSERT INTO session(iduser, stringlogin) VALUES (' . $iduser . ', "' . $_SESSION['stringlogin'] . '")';

                                    $this->mysqli->query($sql) or exit($this->mysqli->error);
                                    $this->header('index.php', '?suc=Inicio de sesión correcto.');
            
                                } else {
                                    $err .= 'Su usuario ha sido desactivado. Contacte al administrador.';

                                }

                            } else {
                                $this->mysqli->query('INSERT INTO trysession(iduser) VALUES ("' . $iduser . '")') or exit($this->mysqli->error);
                                $err .= 'La contraseña no es correcta. Intente de nuevo.';

                            }
                        }
                    } else {
                        $err .= 'El correo electrónico no esta registrado.';

                    }
                } else {
                    $err .= $this->mysqli->error;

                }
            } else {
                $err .= 'El código de la imagen no es correcto. Intente de nuevo.';
                
            }
        } else {
            $err .= 'La validación del formulario no ha sido exitosa. Intente de nuevo.';

        }
        $this->header('', '?err=' . $err);
        
    }

    private function logoutHandler() {
        if ($stmt = $this->mysqli->prepare('SELECT idsesion FROM session WHERE iduser = ? AND timeend IS NULL')) {
            $stmt->bind_param('i', $this->iduser);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows >= 1) {
                $stmt->bind_result($idsesion);
            
                while($stmt->fetch()) {
                    $this->mysqli->query('UPDATE session SET timeend = now() WHERE iduser = ' . $this->iduser) or exit($this->mysqli->error);
                }
            }
            $stmt->close();
        }

        $_SESSION = array();

        session_unset();
        session_destroy(); 

        $this->header('index.php', '?suc=Ha cerrado sesión.');
        
    }

    private function recoverPassword() {
        $err = '¡Error!';
        if($this->checkToken) {
            $email = isset($this->post['email']) && filter_var($this->post['email'], FILTER_VALIDATE_EMAIL) ? $this->post['email'] : '';
            $codigo = isset($this->post['codigo']) ? hash('sha512', $this->post['codigo']) : 'null';
            $pass = sha1(isset($this->post['pass']) ? $this->post['pass'] : 'null');
            $passver = sha1(isset($this->post['passver']) ? $this->post['passver'] : 'null2');
            
            if($email != '') {
                if ($stmt = $this->mysqli->prepare('SELECT iduser, status FROM user WHERE email = ? LIMIT 1')) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($iduser, $status);
                    $stmt->fetch();

                    if ($stmt->num_rows == 1) {
                        if($status == 1) {
                            if($codigo != 'null') {
                                if($stmt = $this->mysqli->prepare('SELECT secret FROM recoverpassword WHERE iduser = ? AND status = 1 ORDER BY time DESC')) {
                                    $stmt->bind_param('i', $iduser);
                                    $stmt->execute();
                                    $stmt->store_result();
                                    $stmt->bind_result($secret);
                                    $stmt->fetch();

                                    if(hash_equals($codigo, $secret)) {
                                        if($pass == $passver) {
                                            $sql = 'UPDATE user SET passdb = "' . $pass . '" WHERE iduser = ' . $iduser;
                                            $this->mysqli->query($sql) or exit($this->mysqli->error);

                                            $sql = 'UPDATE recoverpassword SET status = 0 WHERE iduser = ' . $iduser;
                                            $this->mysqli->query($sql) or exit($this->mysqli->error);

                                            $suc = 'Ha cambiado su contraseña, inicie sesión.';
                                        }
                                        $err .= 'Las contraseñas no coinciden, recupere su contraseña de nuevo.';

                                    }
                                } else {
                                    $this->header('', '?err=' . html_entity_decode($this->mysqli->error));
                                    
                                }
    
                            } else {
                                $secret = mt_rand(100001, 999999);
                                $hashsecret = hash('sha512', $secret);
    
                                $sql = 'INSERT INTO recoverpassword(iduser, secret) VALUES (' . $iduser . ', "' . $hashsecret . '")';
                                $this->mysqli->query($sql) or exit($this->mysqli->error);
                                
                                $subject = $this->title . ' - Recuperar clave';

                                $message = '<html><head><title>' . $subject . '</title></head><body><p>Ha solicitado recuperar su contraseña. Ingrese el código inferior en la pagina para continuar:<br /><br /><strong>'.$secret.'</strong><br /><br />' . $this->title . '</p></body></html>';
                                $headers = "MIME-Version: 1.0" . "\r\n";
                                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                                $headers .= 'From: <' . $this->mail_email . '>' . "\r\n";
                                $headers .= 'Cc: ' . $this->mail_email . "\r\n";

                                sendemail($this, $email, 'Recuperar clave', $message, '', 'setPassword&suc=Verifique su correo e ingrese el codigo enviado');
                                
                            }
                        } else {
                            $err .= 'Su usuario ha sido desactivado. Contacte al administrador.';

                        }

                    } else {
                        $err .= 'El correo electrónico no esta registrado. Intente de nuevo.';
                                     
                    }
                } else {
                    $err .= $this->mysqli->error;
                    
                }
            }
        } else {
            $err .= 'La validación del formulario no ha sido exitosa. Intente de nuevo.';
            
        }

        $sql = 'UPDATE recoverpassword SET status = 0 WHERE iduser = ' . $iduser;
        $this->mysqli->query($sql) or exit($this->mysqli->error);
        
        if(isset($suc)) {
            $this->header('login.php', '?suc=' . $suc);
            
        }
        $this->header('', '?err=' . $err);

    }

    private function checkBrute($iduser) {
        $intentosvalidos = time() - (2 * 60 * 60); //30Min (1 * 30 * 60) - 1Hr (1 * 60 * 60) - 2Hr (2 * 60 * 60) - 3Hr (3 * 60 * 60) ...

        if ($stmt = $this->mysqli->prepare('SELECT time FROM trysession WHERE iduser = ? AND time > ?')) {
            $stmt->bind_param('is', $iduser, $intentosvalidos);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > $this->maxLoginAttemps) {
                return true;
            }
        }
        return false;
    }

    private function loginCheck() {
        if(isset($this->session['iduser'], $this->session['email'], $this->session['fname'], $this->session['lname'], $this->session['status'], $this->session['stringlogin'])) {
            $this->iduser = $this->session['iduser'];
            $this->email = $this->session['email'];
            $this->fname = $this->session['fname'];
            $this->lname = $this->session['lname'];
            $this->status = $this->session['status'];
            $this->stringlogin = $this->session['stringlogin'];
            
            $user_browser = $_SERVER['HTTP_USER_AGENT'] . $this->userIp();

            if($stmt = $this->mysqli->prepare('SELECT user.passdb, user.status FROM user JOIN session ON user.iduser = session.iduser WHERE user.iduser = ? AND session.stringlogin = ? AND session.timeend IS NULL')) {
                $stmt->bind_param('is', $this->iduser, $this->stringlogin);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($passdb, $status);
                    $stmt->fetch();
                    $login_check = hash('sha512', $passdb . $user_browser);

                    if($status == 1) {
                        if(hash_equals($login_check, $this->stringlogin)) {
                            return true;
                        }
                    }
                }
            }
        }
        unset($this->iduser, $this->email, $this->fname, $this->lname, $this->status, $this->stringlogin);
        return false;

    }

    private function register() {
        $err = '¡Error!';
        $email = isset($this->post['email']) && filter_var($this->post['email'], FILTER_VALIDATE_EMAIL) ? $this->post['email'] : '';
        $emailalt = isset($this->post['emailalt']) && filter_var($this->post['emailalt'], FILTER_VALIDATE_EMAIL) ? $this->post['emailalt'] : '';
        $pass = SHA1(isset($this->post['pass']) ? $this->post['pass'] : 'null');
        $passver = SHA1(isset($this->post['passver']) ? $this->post['passver'] : 'null2');
        $fname = isset($this->post['fname']) ? $this->post['fname'] : '';
        $lname = isset($this->post['lname']) ? $this->post['lname'] : '';
        $type_id = isset($this->post['type_id']) ? $this->post['type_id'] : '';
        $number_id = isset($this->post['number_id']) ? $this->post['number_id'] : '';
        $institution = isset($this->post['institution']) ? $this->post['institution'] : '';
        $position = isset($this->post['position']) ? $this->post['position'] : '';
        
        if($this->checkToken) {
            if($this->checkCaptcha) {
                if($email != '' || $emailalt != '' || $fname != '' || $lname != '' || $type_id != '' || $number_id != '' || $institution != '' || $position != '') {
                    if ($stmt = $this->mysqli->prepare('SELECT email FROM user WHERE email = ?')) {
                        $stmt->bind_param('s', $email);
                        $stmt->execute();
                        $stmt->store_result();
    
                        if ($stmt->num_rows == 0) {
                            if($pass == $passver) {
                                if($stmt = $this->mysqli->prepare("INSERT INTO user(email, emailalt, passdb, fname, lname, type_id, number_id) VALUES (?, ?, ?, ?, ?, ?, ?)")) {
                                    $stmt->bind_param("sssssii", $email, $emailalt, $pass, $fname, $lname, $type_id, $number_id);
                                    $stmt->execute();
                                    $iduser = $stmt->insert_id;
        
                                    if($iduser > 0) {
                                        $stmt = $this->mysqli->prepare("INSERT INTO userinstitution(iduser, institution, position) VALUES (?, ?, ?)");
                                        $stmt->bind_param("iss", $iduser, $institution, $position);
                                        $stmt->execute();
                                    }
        
                                    $this->header('index.php', '?suc=Registro exitoso, el Administrador validara la información y hará la activación de su usuario.');
                                }
    
                            } else {
                                $err .= 'La contraseña no coincide. Intente de nuevo.';
    
                            }
                        } else {
                            $err .= 'El correo electrónico ya se encuentra registrado.';

                        }
                    }
                        
                } else {
                    $err .= 'Todos los campos son obligatorios. Intente de nuevo.';

                }
            } else {
                $err .= 'El código de la imagen no es correcto. Intente de nuevo.';
                
            }
        } else {
            $err .= 'La validación del formulario no ha sido exitosa. Intente de nuevo.';

        }
        $this->header('', '?err=' . $err); 

    }
    /* END - Login functions */

    /* START - Form functions */
    public function formOpen($action = '') {
        return '<form action="' . $action . '" method="post" enctype="multipart/form-data"><input type="hidden" name="token" value="' . $this->token . '">';
    }

    public function formClose() {
        return '</form>';
    }

    protected function generateToken() {
        $this->token = hash('sha512', openssl_random_pseudo_bytes(16));
        $_SESSION['token'] = $this->token;
        return $this->token;
    }

    protected function checkToken() {
        if(isset($this->session['token'], $this->post['token']) && $this->session['token'] == $this->post['token']) {
            return true;
        }
        return false;
    }

    protected function userIp() {
        $ipaddress = '';

        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');

        } else if(getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');

        } else if(getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');

        } else if(getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');

        } else if(getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');

        } else if(getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');

        } else {
            $ipaddress = 'UNKNOWN';

        }
     
        return $ipaddress;
    }

    private function checkCaptcha() {
        $captcha = isset($this->post['captcha']) ? hash('sha512', $this->mysqli->real_escape_string($this->post['captcha'])) : '';

        if(isset($_SESSION['captcha']) && hash_equals($_SESSION['captcha'], $captcha)) {
            return true;
        }
        return false;
    }
    /* END - Form functions */

    public function getvalues($data) {
        $return = array();

        if($stmt = $this->mysqli->prepare('SELECT * FROM ' . $this->cleanvar($data))) {
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $text, $status);
                while ($stmt->fetch()) {
                    $return[$id] = array('value' => $text, 
                                        'status' => $status);
                }
            }
        } else {
            $this->header('index.php', '?err=' . html_entity_decode($this->mysqli->error));

        }
        return $return;
    }

    public function displayMsgs() {
        if(isset($this->get['err'])) {
            return '<div class="err">' . $this->get['err'] . '</div>';
        }

        if(isset($this->get['suc'])) {
            return '<div class="suc">' . $this->get['suc'] . '</div>';
        }
    }

    private function redirect() {
        if(isset($this->get['loginHandler'])) {
            return $this->loginHandler();
        } else if(isset($this->get['recoverPassword'])) {
            return $this->recoverPassword();
        } else if(isset($this->get['logoutHandler'])) {
            return $this->logoutHandler();
        } else if(isset($this->get['register'])) {
            return $this->register();
        }
        
    }

    public function header($url = 'index.php', $params = '?') {
        header('Location: ' . $url . $params);
        echo '
        Haga <a href="' . $url . $params . '">clic aquí</a> para continuar.
        <script>window.location = "' . $url . $params . '";</script>';
        exit;
    }
}

$main = new main($_GET, $_POST); ?>
