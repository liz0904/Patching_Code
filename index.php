<?php
session_start();

function sqlfilter($str) { 
  $str = addslashes($str);
  $strdata = "'&\&\"&\&(&)&#&>&<&=&*/&/*&+&\&%&;&|&--&@&=&[&]&,";
  $search = explode("&",$strdata);
  for($i=0;$i < count($search);$i++){
    $str = str_replace($search[$i], "", $str); 
  }
  return $str;
}

include "./config.php";
if($_GET['page'] == "login"){
    if(preg_match("/ |\/|\(|\)|\||&|select|onload|onerror|alert|curl|from|0x/i",$input['id'])) exit("no hack");
    if(preg_match("/#|select|\(| |where|or|from|where|limit|=|0x/i",$input['id'])) exit("no hack");

    try{
        $input = json_decode(file_get_contents('php://input'), true);
    }
    catch(Exception $e){
        exit("<script>alert(`wrong input`);history.go(-1);</script>");
    }
    $db = dbconnect();

    $query = "select id,pw from member where id=?";
    $q = $db->prepare( $query );
    $q->bind_param( 's', $input['id']);
    $q->execute();
    $result = mysqli_fetch_array($q->get_result());

    ///////admin 로그인 시 비밀번호 암호화////////////
    if($input['id']=='admin'){
       $result['pw']=hash("sha256",$result['pw']);
    }
    $input['pw']=hash("sha256",$input['pw']);
    if($result['id'] && $result['pw'] == $input['pw']){
        $_SESSION['id'] = $result['id'];
        exit("<script>alert(`login ok`);location.href=`/`;</script>");
    }
    else{ exit("<script>alert(`login fail`);history.go(-1);</script>"); }
}
if($_GET['page'] == "join"){
    if(preg_match("/ |\/|\(|\)|\||&|select|onload|onerror|alert|curl|from|0x/i",$input['id'])) exit("no hack");
    if(preg_match("/#|select|\(| |where|or|from|where|limit|=|0x/i",$input['id'])) exit("no hack");
    if(preg_match("/ |\/|\(|\)|\||&|select|onload|onerror|alert|curl|from|0x/i",$input['email'])) exit("no hack");
    if(preg_match("/#|select|\(| |where|or|from|where|limit|=|0x/i",$input['pw'])) exit("no hack");

    try{
        $input = json_decode(file_get_contents('php://input'), true);
    }
    catch(Exception $e){
        exit("<script>alert(`wrong input`);history.go(-1);</script>");
    }
    $db = dbconnect();
    if(strlen($input['id']) > 256) exit("<script>alert(`userid too long`);history.go(-1);</script>");
    if(strlen($input['email']) > 120) exit("<script>alert(`email too long`);history.go(-1);</script>");
    ////////////////안전한 비밀번호 설정/////////////////////////
    $pw=$input['pw'];
    $num_check=preg_match('/[0-9]/u', $pw);
    $eng_check=preg_match('/|[a-z]/u', $pw);
    $big_eng_check=preg_match('/|[A-Z]/u', $pw);
    $spe_check=preg_match('/\!|\@|\#|\$|\%|\^|\&|\*|/u', $pw);
    if($num_check==0 || $eng_check==0 || $big_eng_check==0 || $spe_check==0){
       exit("<script>alert(`no secure password!`);history.go(-1);</script>");
    }
    if(!filter_var($input['email'],FILTER_VALIDATE_EMAIL)) exit("<script>alert(`wrong email`);history.go(-1);</script>");
    
    $input['id']=mysqli_real_escape_string($db, $input['id']);
    $input['email']=mysqli_real_escape_string($db, $input['email']);
    $input['pw']=mysqli_real_escape_string($db, $input['pw']);

    $query = "select id,pw from member where id=?";
    $q = $db->prepare( $query );
    $q->bind_param( 's', $input['id']);
    $q->execute();
    $result = mysqli_fetch_array($q->get_result());
    if(!$result['id']){
        ///////////회원가입 시 비밀번호 암호화//////////////
        $input['pw']=hash("sha256",$input['pw']);
        $query = "insert into member values(?,?,?,'user')";
        $q = $db->prepare( $query );
        $q->bind_param( 'sss', $input['id'], $input['email'], $input['pw']);
        $q->execute();
        exit("<script>alert(`join ok`);location.href=`/`;</script>");
    }
    else{
        exit("<script>alert(`Userid already existed`);history.go(-1);</script>");
    }
}
if($_GET['page'] == "upload"){
    if(preg_match("/ |\/|\(|\)|\||&|select|onload|onerror|alert|curl|from|0x/i",$_GET['no'])) exit("no hack");
    if(preg_match("/#|select|\(| |where|or|from|where|limit|=|0x/i",$_GET['no'])) exit("no hack");

    if(!$_SESSION['id']){
        exit("<script>alert(`login plz`);history.go(-1);</script>");
    }
    if($_FILES['fileToUpload']['size'] >= 1024 * 1024 * 1){ exit("<script>alert(`file is too big`);history.go(-1);</script>"); } // file size limit(1MB). do not remove it.
    $extension = explode(".",$_FILES['fileToUpload']['name'])[1];

    if($extension == "txt" || $extension == "png"){
        sqlfilter(explode(".",$_FILES['fileToUpload']['name'])[0]);
        system("cp {$_FILES['fileToUpload']['tmp_name']} ./upload/{$_FILES['fileToUpload']['name']}");
        exit("<script>alert(`upload ok`);location.href=`/`;</script>");
    }
    else{
        exit("<script>alert(`txt or png only`);history.go(-1);</script>");
    }
}
if($_GET['page'] == "download"){
    if(preg_match("/ |\/|\(|\)|\||&|select|onload|onerror|alert|curl|from|0x/i",$_GET['no'])) exit("no hack");
    if(preg_match("/#|select|\(| |where|or|from|where|limit|=|0x/i",$_GET['no'])) exit("no hack");

    $content = file_get_contents("./upload/{$_GET['file']}");
    ///////////////////파일 다운로드 시 확장자 제한////////////////////////////////
    $filepath="./upload/{$_GET['file']}";
    $extension = pathinfo($filepath, PATHINFO_EXTENSION);
    if($extension == "txt" || $extension == "png"){
        if(!$content){
            exit("<script>alert(`not exists file`);history.go(-1);</script>");
        }
        else{
            header("Content-Disposition: attachment;");
            echo $content;
            exit;
        }
    }
    else{        
        exit("<script>alert(`txt or png only`);history.go(-1);</script>");
    }
}
if($_GET['page'] == "admin"){
    if(preg_match("/ |\/|\(|\)|\||&|select|onload|onerror|alert|curl|from|0x/i",$_GET['no'])) exit("no hack");
    if(preg_match("/#|select|\(| |where|or|from|where|limit|=|0x/i",$_GET['no'])) exit("no hack");

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