<?php require('classes/main.php'); ?>
<!DOCTYPE html>
<html>
<?php require('common/head.php'); ?>

<body>
    <?php require('common/header.php'); ?>
    <?php echo $main->formOpen('?loginHandler'); ?>
        <div class="input box-radius login-form">
            <h1>Iniciar sesión</h1>
            <p>Por favor ingrese su usuario y clave.</p>
            <div class="form-grp">
                <div class="form-input">
                    <input name="email" type="email" class="input" placeholder="Correo electrónico">
                </div>
            </div>
            <div class="form-grp">
                <div class="form-input">
                    <input name="password" type="password" class="input" placeholder="Contraseña">
                </div>
            </div>
            <div class="form-grp">
                <div class="form-input text-center">
                    <img id="captcha_image" src="captcha/captcha.php?rand=<?php rand(); ?>'">
                    <br />
                    <a href="javascript:refreshCaptcha();" class="label_captcha">Cambiar imagen</a>
                    <br />
                    <input name="captcha" class="input" type="text" placeholder="Codigo de imagen">
                </div>
            </div>
            <div class="form-grp">
                <div class="form-input text-center">
                    <input type="submit" class="btn btn-success" value="Iniciar sesión">
                </div>
            </div>
            <div class="form-grp">
                <div class="form-input text-center">
                    <a href="recover-password.php">¿Olvidó su clave?</a>
                    <br />
                    <a href="index.php">Volver</a>
                </div>
            </div>
        </div>
    <?php echo $main->formClose(); ?>
    <?php require('common/footer.php'); ?>
</body>
</html>