<?php
include_once 'includes/function.php';
//если пользователь залогинен то с файла регистрации автоматически перенаправляем на главную стр
if(logged_in()) redirect(get_url());

$title= 'Регистария';
$error= get_error_message();

include_once 'includes/header.php';
include_once 'includes/register_form.php';
include_once 'includes/footer.php';

?>


