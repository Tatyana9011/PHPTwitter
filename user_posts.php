<?php
include_once 'includes/function.php';

$error= get_error_message();
/*   echo "<pre>";
  var_dump($_GET['id']); 
  echo "</pre>"; 
  die; */
$id = 0;
if(isset($GET['id']) && !empty($GET['id'])){
  $id = $GET['id'];
} else if(logged_in()){
  $id = $_SESSION['user']['id'];
}

$posts = get_posts($id);

if(!empty($posts) && $id > 0){
  $title = 'Твиты пользователя @' . $posts[0]['login'];
}else{
  $title = 'Твиты';
}

include_once 'includes/header.php';
if (logged_in()) include_once 'includes/tweet_form.php';
include_once 'includes/posts.php';
include_once 'includes/footer.php';

?>
