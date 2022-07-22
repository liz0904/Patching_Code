<?php
session_start();
include "./config.php";
if($_GET['page'] == "login"){
    try{
        $input = json_decode(file_get_contents('php://input'), true);
    }
    catch(Exception $e){
        exit("<script>alert(`wrong input`);history.go(-1);</script>");
    }
    $db = dbconnect();
    $query = "select id,pw from member where id='{$input['id']}'";
    $result = mysqli_fetch_array(mysqli_query($db,$query));
    if($result['id'] && $result['pw'] == $input['pw']){
        $_SESSION['id'] = $result['id'];
        exit("<script>alert(`login ok`);location.href=`/`;</script>");
    }
    else{ exit("<script>alert(`login fail`);history.go(-1);</script>"); }
}
if($_GET['page'] == "join"){
    try{
        $input = json_decode(file_get_contents('php://input'), true);
    }
    catch(Exception $e){
        exit("<script>alert(`wrong input`);history.go(-1);</script>");
    }
    $db = dbconnect();
    if(strlen($input['id']) > 256) exit("<script>alert(`userid too long`);history.go(-1);</script>");
    if(strlen($input['email']) > 120) exit("<script>alert(`email too long`);history.go(-1);</script>");
    if(!filter_var($input['email'],FILTER_VALIDATE_EMAIL)) exit("<script>alert(`wrong email`);history.go(-1);</script>");
    $query = "select id from member where id='{$input['id']}'";
    $result = mysqli_fetch_array(mysqli_query($db,$query));
    if(!$result['id']){
        $query = "insert into member values('{$input['id']}','{$input['email']}','{$input['pw']}','user')";
        mysqli_query($db,$query);
        exit("<script>alert(`join ok`);location.href=`/`;</script>");
    }
    else{
        exit("<script>alert(`Userid already existed`);history.go(-1);</script>");
    }
}
if($_GET['page'] == "upload"){
    if(!$_SESSION['id']){
        exit("<script>alert(`login plz`);history.go(-1);</script>");
    }
    if($_FILES['fileToUpload']['size'] >= 1024 * 1024 * 1){ exit("<script>alert(`file is too big`);history.go(-1);</script>"); } // file size limit(1MB). do not remove it.
    $extension = explode(".",$_FILES['fileToUpload']['name'])[1];
    if($extension == "txt" || $extension == "png"){
        system("cp {$_FILES['fileToUpload']['tmp_name']} ./upload/{$_FILES['fileToUpload']['name']}");
        exit("<script>alert(`upload ok`);location.href=`/`;</script>");
    }
    else{
        exit("<script>alert(`txt or png only`);history.go(-1);</script>");
    }
}
if($_GET['page'] == "download"){
    $content = file_get_contents("./upload/{$_GET['file']}");
    if(!$content){
        exit("<script>alert(`not exists file`);history.go(-1);</script>");
    }
    else{
        header("Content-Disposition: attachment;");
        echo $content;
        exit;
    }
}
if($_GET['page'] == "admin"){
    $db = dbconnect();
    $result = mysqli_fetch_array(mysqli_query($db,"select id from member where id='{$_SESSION['id']}'"));
    if($result['id'] == "admin"){
        echo file_get_contents("/flag"); // do not remove it.
    }
    else{
        exit("<script>alert(`admin only`);history.go(-1);</script>");
    }
}

/*  this is hint. you can remove it.
CREATE TABLE `member` (
    `id` varchar(120) NOT NULL,
    `email` varchar(120) NOT NULL,
    `pw` varchar(120) NOT NULL,
    `type` varchar(5) NOT NULL
  );
  
  INSERT INTO `member` (`id`, `email`, `pw`, `type`)
      VALUES ('admin', '**SECRET**', '**SECRET**', 'admin');
*/

?>