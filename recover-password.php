<?php require('classes/main.php'); ?>
<!DOCTYPE html>
<html>
<?php require('common/head.php'); ?>

<body>
    <?php require('common/header.php'); ?>
    <?php echo $main->formOpen('?recoverPassword'); ?>
        <div class="input box-radius login-form">
            <h1>Recuperar clave</h1>
            <div class="form-grp">
                <div class="form-input">
                    <input name="email" type="email" class="input" placeholder="Correo electrónico" required>
                </div>
            </div>
            <?php if(isset($_GET['setPassword'])) { ?>
                <div class="form-grp">
                    <div class="form-input">
                        <input name="pass" type="pass" class="input" placeholder="Nueva clave" required>
                    </div>
                </div>
                <div class="form-grp">
                    <div class="form-input">
                        <input name="passver" type="passver" class="input" placeholder="Digite de nuevo la clave" required>
                    </div>
                </div>
                <div class="form-grp">
                    <div class="form-input">
                        <input name="codigo" type="text" class="input" placeholder="Código enviado a su correo" required>
                    </div>
                </div>
            <?php } ?>
            <div class="form-grp">
                <div class="form-input text-center">
                    <img id="captcha_image" src="captcha/captcha.php?rand=<?php rand(); ?>'">
                    <br />
                    <a href="javascript:refreshCaptcha();" class="label_captcha">Cambiar imagen</a>
                    <br />
                    <input name="captcha" class="input" type="text" placeholder="Código de imagen" required>
                </div>
            </div>
            <div class="form-grp">
                <div class="form-input text-center">
                    <input type="submit" class="btn btn-success" value="Recuperar clave">
                    <a href="login.php">Volver</a>
                </div>
            </div>
        </div>
    <?php echo $main->formClose(); ?>
    <?php require('common/footer.php'); ?>
</body>
</html>