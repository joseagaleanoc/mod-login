<?php require('classes/main.php'); ?>
<!DOCTYPE html>
<html>
<?php require('common/head.php'); ?>

<body>
    <?php require('common/header.php'); ?>
    <?php echo $main->formOpen('?register'); ?>
    <div class="input box-radius login-form">
        <h1>Regístrese</h1>

        <div class="form-grp">
            <div class="form-input">
                <span>Datos de ingreso</span>
            </div>
        </div>
        <div class="form-grp">
            <div class="form-input">
                <input name="email" type="email" class="input" placeholder="Correo electrónico">
            </div>
            <div class="form-input">
                <input name="emailalt" type="email" class="input" placeholder="Correo electrónico alternativo">
            </div>
        </div>
        <div class="form-grp">
            <div class="form-input">
                <input name="pass" type="password" class="input" placeholder="Contraseña">
            </div>
            <div class="form-input">
                <input name="passver" type="password" class="input" placeholder="Ingrese la contraseña de nuevo">
            </div>
        </div>
        <div class="form-grp">
            <div class="form-input">
                <span>Datos personales</span>
            </div>
        </div>
        <div class="form-grp">
            <div class="form-input">
                <input name="fname" type="text" class="input" placeholder="Nombres">
            </div>
            <div class="form-input">
                <input name="lname" type="text" class="input" placeholder="Apellidos">
            </div>
        </div>
        <div class="form-grp">
            <div class="form-input">
                <select name="type_id">
                    <option value="">--Tipo de identificación--</option>
                    <?php foreach($main->getvalues('type_id') as $key => $data) { ?>
                    <option value="<?php echo $key; ?>"><?php echo $data['value']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-input">
                <input name="number_id" type="number" class="input" placeholder="Número de identificación (Solo números)">
            </div>
        </div>
        <div class="form-grp">
            <div class="form-input">
                <input name="institution" type="text" class="input" placeholder="Institución">
            </div>
            <div class="form-input">
                <input name="position" type="text" class="input" placeholder="Cargo">
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
                <p>*Todos los campos son obligatorios.</p>
                <input type="submit" class="btn btn-success" value="Enviar">
            </div>
        </div>
    </div>
    <?php echo $main->formClose(); ?>
    <?php require('common/footer.php'); ?>
</body>

</html>