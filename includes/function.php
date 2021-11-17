<?php
include_once 'config.php';
function debug($var, $stop = false){
  echo "<pre>";
  print_r($var);
  echo "</pre>";
  if($stop) die;
}
function get_url($page = ''){
  return HOST . "/$page";
}
function get_page_title($title=''){
  if(!empty($title)){
  return SITE_NAME . " - $title";
  }else{
    return SITE_NAME;
  }
}
function redirect($link=HOST){
  header("Location:$link");
  die;
}
function db(){
  try{
    return new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS,
  [
      PDO::ATTR_EMULATE_PREPARES => false,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]
);
  } catch (PDOException $e){
    die($e->getMessage());
  }
}

function db_query($sql, $exec = false){
  if(empty($sql)) return false;

  if($exec){
    return db()->exec($sql);
  } else{
    return db()->query($sql);
  }

}

function get_posts($user_id = 0, $sort = false){
  $sorting = 'DESC';
  if($sort) $sorting = 'ASC';

  if($user_id > 0) return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = 
  posts.user_id WHERE posts.user_id = $user_id ORDER BY `posts`.`date` $sorting;")->fetchAll();

  return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id ORDER BY `posts`.`date` $sorting")
  ->fetchAll();
}

function get_user_info($login){
   if (empty($login)) return [];

  return db_query("SELECT * FROM `users` WHERE `login` = '$login';")->fetch();
}

 //добавление пользователя
function add_user($login, $pass){
  //для сокрытия пароля используют специальные функции md5() но ее легко расшифровуют потому используем другую
  $password = password_hash($pass, PASSWORD_DEFAULT); 
  $login = trim($login);
  $name = ucfirst($login);//делает слово с большой буквы

  return db_query("INSERT INTO `users` (`id`, `login`, `pass`, `name`) VALUES (NULL, '$login', '$password', '$name')", true);
}

function register_user($auth_data){
  if(empty($auth_data) || !isset($auth_data['login']) || empty($auth_data['login'])|| !isset($auth_data['pass']) || !isset($auth_data['pass2'])) return false;
  
  $user = get_user_info($auth_data['login']);

  if(!empty($user)){
    $_SESSION['error'] = "Пользователь '" . $auth_data['login'] . "' уже существует";
    redirect(get_url('register.php'));
  }

  if($auth_data['pass'] !== $auth_data['pass2']){
    $_SESSION['error'] = "Пароли не совпадают";
    redirect(get_url('register.php'));
  }

  if(add_user($auth_data['login'], $auth_data['pass'])){
    redirect(get_url());
    //можно выводить положительное сообщение о регистрации но в верстке не предусмотрено пртому не пишим
  }
//пароль не меньше 8 символов и логин ннеменьше 8 символов не длиннее 50 символов
 // return true;
}

function login($auth_data){

   if(empty($auth_data) || !isset($auth_data['login']) || empty($auth_data['login']) || !isset($auth_data['pass']) || empty($auth_data['pass'])) {
    $_SESSION['error'] = "Логин или пароль не может быть пустым";
    redirect(get_url());
    //die;
   }

   $user = get_user_info($auth_data['login']);

   if(empty($user)){
      $_SESSION['error'] = "Пользователь '" . $auth_data['login'] . "' не найден";
      redirect(get_url());
     // die;
    }
    //проверяем пароль если он не совпадает то сообщаем пользователю
    //password_verify - это спец функция сравнения паролей возвращает правда если пароли совпали и лож есни не совпали
    if(password_verify($auth_data['pass'], $user['pass'])){
      //когда юзер авторизовался ми сохраняем его в сессию
      $_SESSION['user'] = $user;
      $_SESSION['error'] = '';
      redirect(get_url('user_posts.php?id=' . $user['id']));
     // die;
    }else{
      $_SESSION['error'] = "Пароль неверен!";
      redirect(get_url());
     // die;
    }
  
}
//если есть ошибка будем ее возвращать
function get_error_message(){
  $error='';
  if(isset($_SESSION['error']) && !empty($_SESSION['error'])){
     $error = $_SESSION['error'];
     $_SESSION['error'] = '';
  }
  return $error;
}
//функция проверки авторезированна человек или нет
function logged_in(){
  return isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);

}
function add_post($text, $image){
  $text = trim($text);
  if(mb_strlen($text) > 255){
    $text = mb_substr($text, 0, 255) . "...";
  }

  $user_id = $_SESSION['user']['id'];//CURRENT_TIMESTAMP убрали из запроса так как дата автоматически подставляется
  $sql = "INSERT INTO `posts` (`id`, `user_id`, `text`, `image`) VALUES (NULL, $user_id, '$text', '$image');";
  return db_query($sql, true);
}

function delete_post($id){
  //ДЗ написать проверку 'id' на то что это число и он больше 0
  $user_id = $_SESSION['user']['id'];
  return db_query("DELETE FROM `posts` WHERE `posts`.`id` = $id AND `user_id`= $user_id", true);
//AND `user_id`= $user_id это условие для того что бы удалился толдько у текущего
}
function get_likes_count($post_id){
  if(empty($post_id)) return 0;

  return db_query("SELECT COUNT(*) FROM `likes` WHERE `post_id`= $post_id;")->fetchColumn();
  //->fetchColumn() возвращает число лайков
}
function is_post_liked($post_id){

  $user_id = $_SESSION['user']['id'];
  if(empty($post_id)) return false;

  return db_query("SELECT * FROM `likes` WHERE post_id = $post_id AND user_id = $user_id")->rowCount() > 0;
  //->rowCount возвращает количество строк которое пришло и ми его сравниваем если оно больше 0 то вернет правда если меньше то лож
}

function add_like($post_id){
  $user_id = $_SESSION['user']['id'];
  if(empty($post_id)) return false;
//добавляем лайки
  $sql = "INSERT INTO `likes` (`post_id`, `user_id`) VALUES ($post_id, $user_id);";
  return db_query($sql, true);
}
function delete_like($post_id){
  //ДЗ написать проверку 'id' на то что это число и он больше 0
  $user_id = $_SESSION['user']['id'];
  if(empty($post_id)) return false;
  
  return db_query("DELETE FROM `likes` WHERE `post_id` = $post_id AND `user_id`= $user_id", true);
//AND `user_id`= $user_id это условие для того что бы удалился толдько у текущего
}

function get_liked_posts(){
  $user_id = $_SESSION['user']['id'];
  $sql = "SELECT posts.*, users.name, users.login, users.avatar FROM `likes` JOIN `posts` 
  ON posts.id = likes.post_id JOIN `users` ON users.id = posts.user_id WHERE likes.user_id = $user_id";

 return db_query($sql);
}