<?php
include_once 'function.php';
//уничтожаем сессию и перекидываем пользователя на главную страницу
session_destroy();
header('Location: ' . get_url());
die;
