<?php
session_start();

require('classes/mysqliConn.php');

class main extends mysqliConn {
    private $mysqli;
    private $token;
    private $stringlogin;

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
        $captcha = isset($this->post['captcha']) ? hash('sha512', $this->mysqli->real_escape_string($this->post['captcha'])) : '';

        if($this->checkToken) {
            if(hash_equals($_SESSION['captcha'], $captcha)) {
                $email = isset($this->post['email']) && filter_var($this->post['email'], FILTER_VALIDATE_EMAIL) ? $this->post['email'] : '';
                $password = isset($this->post['password']) ? sha1($this->post['password']) : '';

                if ($stmt = $this->mysqli->prepare('SELECT iduser, fname, lname, clave, rol, estado FROM user WHERE email = ? LIMIT 1')) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($iduser, $fname, $lname, $clave, $rol, $estado);
                    $stmt->fetch();

                    if ($stmt->num_rows == 1) {
                        if ($this->checkBrute($iduser)) {
                            $this->header('', '?err=Ha hecho mas de 5 intentos de inicio de sesión en los últimos 30 minutos. Debe esperar una hora para ser habilitado de nuevo o intente recuperar su clave.');
                            
                        } else {
                            if (hash_equals($clave, $password)) {
                                if($estado == 1) {
                                    $user_browser = $_SERVER['HTTP_USER_AGENT'] . $this->userIp();

                                    $_SESSION['iduser'] = $iduser;
                                    $_SESSION['email'] = $email;
                                    $_SESSION['fname'] = $fname;
                                    $_SESSION['lname'] = $lname;
                                    $_SESSION['rol'] = $rol;
                                    $_SESSION['estado'] = $estado;
                                    $_SESSION['stringlogin'] = hash('sha512', $password . $user_browser);

                                    $sql = 'INSERT INTO session(iduser, stringlogin) VALUES (' . $iduser . ', "' . $_SESSION['stringlogin'] . '")';

                                    $this->mysqli->query($sql) or exit($this->mysqli->error);
                                    $this->header('index.php', '?suc=Inicio de sesión correcto.');
            
                                } else {
                                    $this->header('', '?err=Su user ha sido desactivado. Contacte al administrador.');

                                }

                            } else {
                                $this->mysqli->query('INSERT INTO trysession(iduser) VALUES ("' . $iduser . '")') or exit($this->mysqli->error);
                                $this->header('', '?err=La clave no es correcta. Intente de nuevo.');
                            }
                        }
                    } else {
                        $this->header('', '?err=El correo electrónico no esta registrado.');

                    }
                } else {
                    echo $this->mysqli->error;
                    exit;
                }
            } else {
                $this->header('', '?err=El código de la imagen no es correcto. Intente de nuevo.');
                
            }
        } else {
            $this->header('', '?err=La validación del formulario no ha sido exitosa. Intente de nuevo.');

        }
        $this->header('', '?err=Error desconocido');
        
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
        if($this->checkToken) {
            $email = isset($this->post['email']) && filter_var($this->post['email'], FILTER_VALIDATE_EMAIL) ? $this->post['email'] : '';
            $codigo = isset($this->post['codigo']) ? hash('sha512', $this->post['codigo']) : 'null';
            $password = sha1(isset($this->post['password']) ? $this->post['password'] : 'null');
            $password2 = sha1(isset($this->post['password2']) ? $this->post['password2'] : 'null2');
            
            if($email != '') {
                if ($stmt = $this->mysqli->prepare('SELECT iduser, estado FROM user WHERE email = ? LIMIT 1')) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($iduser, $estado);
                    $stmt->fetch();

                    if ($stmt->num_rows == 1) {
                        if($estado == 1) {
                            if($codigo != 'null') {
                                if ($stmt = $this->mysqli->prepare('SELECT secret FROM recoverpassword WHERE iduser = ? LIMIT 1')) {
                                    $stmt->bind_param('i', $iduser);
                                    $stmt->execute();
                                    $stmt->store_result();
                                    $stmt->bind_result($secret);
                                    $stmt->fetch();
                                    
                                    $sql = 'DELETE FROM recoverpassword WHERE iduser = ' . $iduser;
                                    $this->mysqli->query($sql) or exit($this->mysqli->error);

                                    if(hash_equals($codigo, $secret)) {
                                        if($password == $password2) {
                                            $sql = 'UPDATE user SET clave = "' . $password . '" WHERE iduser = ' . $iduser;
                                            $this->mysqli->query($sql) or exit($this->mysqli->error);

                                            $this->header('login.php', '?suc=Ha cambiado su clave, inicie sesión.');
                                        }
                                        $this->header('', '?err=Las contraseñas no coinciden, recupere su clave de nuevo.');

                                    }
                                }
                                
                                $this->header('', '?err=Código invalido, recupere su clave de nuevo.');
    
                            } else {
                                $secret = mt_rand(100001, 999999);
                                $hashsecret = hash('sha512', $secret);
    
                                $sql = 'INSERT INTO recoverpassword(iduser, secret) VALUES (' . $iduser . ', "' . $hashsecret . '")';
                                $this->mysqli->query($sql) or exit($this->mysqli->error);
                                
                                require 'PHPMailer/Exception.php';
                                require 'PHPMailer/PHPMailer.php';
                                require 'PHPMailer/SMTP.php';
    
                                $mail = new PHPMailer(true);
    
                                try {
                                    $mail->isSMTP();                                            // Set mailer to use SMTP
                                    $mail->Host       = 'mail.ascofame.org.co';                 // Specify main and backup SMTP servers
                                    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                                    $mail->Username   = 'info@ascofame.org.co';                 // SMTP username
                                    $mail->Password   = '*3202283620*';                         // SMTP password
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // Enable TLS encryption, `PHPMailer::ENCRYPTION_SMTPS` also accepted
                                    $mail->Port       = 465;                                    // TCP port to connect to
                                    $mail->CharSet    = 'UTF-8';
    
                                    //Recipients
                                    $mail->setFrom('info@ascofame.org.co');
                                    $mail->addAddress($email);     // Add a recipient
                                    $mail->addBCC('jgaleano@ascofame.org.co');
    
                                    // Content
                                    $mail->isHTML(true);                                  // Set email format to HTML
                                    $mail->Subject = 'Catalogo - Recuperar clave';
                                    $mail->Body    = 'Ha solicitado recuperar su clave. Ingrese el código inferior en la pagina para continuar:<br /><br /><strong>'.$secret.'</strong><br /><br />Catalogo IETS';
                                    $mail->AltBody = 'Ha solicitado recuperar su clave. Ingrese el código inferior en la pagina para continuar:'.$secret;
    
                                    $mail->send();
                                    $this->header('', '?setPassword&suc=Se ha enviado un código de verificación a su correo, no cierre esta ventana.');
    
                                } catch (Exception $e) {
                                    $this->header('', '?err=Error. Contacte el administrador.' . $mail->ErrorInfo);
                                    
                                }
                            }

                        } else {
                            $this->header('', '?err=Su user ha sido desactivado. Contacte al administrador.');

                        }
                        
                        return false;
                    } else {
                        $this->header('', '?err=El correo electrónico no esta registrado. Intente de nuevo.');
                                     
                    }
                } else {
                    $this->header('', '?err=' . $this->mysqli->error);
                    
                }
            }
        } else {
            $this->header('', '?err=La validación del formulario no ha sido exitosa. Intente de nuevo.');
            
        }
        $this->header('', '?err=Error desconocido');

    }

    private function checkBrute($iduser) {
        $intentosvalidos = time() - (2 * 60 * 60); //30Min (1 * 30 * 60) - 1Hr (1 * 60 * 60) - 2Hr (2 * 60 * 60) - 3Hr (3 * 60 * 60) ...

        if ($stmt = $this->mysqli->prepare('SELECT time FROM trysession WHERE iduser = ? AND time > ?')) {
            $stmt->bind_param('is', $iduser, $intentosvalidos);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 5) {
                return true;
            }
        }
        return false;
    }

    private function loginCheck() {
        if(isset($this->session['iduser'], $this->session['email'], $this->session['fname'], $this->session['lname'], $this->session['rol'], $this->session['estado'], $this->session['stringlogin'])) {
            $this->iduser = $this->session['iduser'];
            $this->email = $this->session['email'];
            $this->fname = $this->session['fname'];
            $this->lname = $this->session['lname'];
            $this->estado = $this->session['estado'];
            $this->rol = $this->session['rol'];
            $this->stringlogin = $this->session['stringlogin'];
            
            $user_browser = $_SERVER['HTTP_USER_AGENT'] . $this->userIp();

            if($stmt = $this->mysqli->prepare('SELECT user.clave, user.estado FROM user JOIN session ON user.iduser = session.iduser WHERE user.iduser = ? AND session.stringlogin = ? AND session.timeend IS NULL')) {
                $stmt->bind_param('is', $this->iduser, $this->stringlogin);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($clave, $estado);
                    $stmt->fetch();
                    $login_check = hash('sha512', $clave . $user_browser);

                    if($estado == 1) {
                        if(hash_equals($login_check, $this->stringlogin)) {
                            return true;
                        }
                    }
                }
            }
        }
        unset($this->iduser, $this->email, $this->fname, $this->lname, $this->estado, $this->stringlogin);
        return false;

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
    /* END - Form functions */


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
        } else if(isset($this->get['evaluatorAuth'])) {
            return $this->evaluatorAuth();
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
