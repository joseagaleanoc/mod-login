<header>
    <div class="menu">
        <?php if($main->loginCheck) { ?>
        <span><?php echo $main->fname . ' ' . $main->lname; ?></span>

        <?php } ?>
        <a href="index.php" class="menu-item">Inicio</a>
        <?php if($main->loginCheck) { ?>
            <a href="?logoutHandler"class="menu-item">Cerrar sesión</a>
        <?php } else { ?>
            <a href="login.php"class="menu-item">Iniciar sesión</a>
            <a href="register.php"class="menu-item">Regístrese</a>
        <?php } ?>
    </div>
</header>
<?php echo $main->displayMsgs(); ?>
<div class="container">