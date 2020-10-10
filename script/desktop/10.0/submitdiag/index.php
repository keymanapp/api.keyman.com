<?php
  if(!isset($_SERVER['argv'])) {
    if(!isset($_REQUEST['Username']) || !isset($_REQUEST['Title']) || !isset($_REQUEST['Body']) || !isset($_FILES['File']))
    {
      echo "<error>Invalid parameters</error>";
      exit;
    }

    $Username = iconv('CP1252', 'UTF-8//TRANSLIT', $_REQUEST['Username']);
    $Title = iconv('CP1252', 'UTF-8//TRANSLIT', $_REQUEST['Title']);
    $Body = iconv('CP1252', 'UTF-8//TRANSLIT', $_REQUEST['Body']);
    $DataFilename = $_FILES['File']['tmp_name'];
    $Filename = $_FILES['File']['name'];
  } else {
    // Command line test mode
    $Username = $argv[1];
    $Title = $argv[2];
    $Body = $argv[3];
    $DataFilename = 'c:\temp\tkd43E1.tsi';
    $Filename = 'tkd43E1.tsi';
  }

  // create a new case in Discourse

  $user = CallDiscourse("/admin/users/list/all.json?email=$Username", null);

  if(!is_array($user)) {
    die_errors("Did not receive a valid response from the server");
  }

  if(sizeof($user) == 0) {
    $data = [
      "name" => $Username,
      "email" => $Username,
      "password" => generatePassword(),
      "username" => generate_username_from_email($Username),
      "active" => "true",
      "approved" => "true"
    ];
    $user = CallDiscourse("/users", $data, 'POST');
    $username = $data['username'];
    if(isset($user->errors)) {
      die_errors($user->message);
    }
  } else {
    if(isset($user->errors)) {
      die_errors($user->message);
    }
    $username = $user[0]->username;
  }

  $data = [
    "type" => "composer",
    "username" => $username,
    "files[]" => new cURLFile($DataFilename, 'application/octet-stream', $Filename),
    "synchronous" => 'true'
  ];

  $uploads = CallDiscourse('/uploads.json', $data, 'POST');
  if(isset($uploads->errors)) {
    die_errors($uploads->message);
  }

  $upload_url = $uploads->url;

  $data = [
    "title" => "Diagnostic Report from ".$username,
    "raw" => "$Title\n\n$Body\n\nDiagnostic: $upload_url\n",
    "target_usernames" => "keyman-diagnostics",
    "archetype" => "private_message"
  ];

  $posts = CallDiscourse('/posts.json', $data, 'POST', $username);

  if(isset($posts->errors)) {
    die_errors($posts->errors[0]);
  }

  $topic_id = $posts->topic_id;
  echo "<result>$topic_id</result>";

  function CallDiscourse($path, $data, $method = 'GET', $username = '') {
    $discourse_key = $_SERVER['api_keyman_com_discourse_key'];
    $discourse_site = $_SERVER['api_keyman_com_discourse_site'];
    $discourse_username = $_SERVER['api_keyman_com_discourse_username'];

    if(empty($username)) $username = $discourse_username;
    $url = "$discourse_site$path";
    $ch = curl_init($url);

    if(!empty($data)) {
      if($method == 'GET' || $method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
      } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: application/json',
      "Api-Key: $discourse_key",
      "Api-Username: $username",
      'User-Agent: curl 1.0'));
    curl_setopt($ch, CURLOPT_HEADER, false);
    if(!$response = curl_exec($ch)) {
      die_errors(curl_error($ch));
      exit;
    }
    curl_close($ch);
    return json_decode($response);
  }

  function generatePassword($length = 16) {
    $bytes = openssl_random_pseudo_bytes($length, $strong);
    if (false !== $bytes && true === $strong) {
      $result = substr(base64_encode($bytes), 0, $length);
    }
    else {
      // Fallback to rand() if openssl_random_pseudo_bytes is not available
      // not great ... but usually only debug environments.
      $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      $count = strlen($chars);
      for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= substr($chars, $index, 1);
      }
    }

    return $result;
  }

  function generate_username_from_email($email) {
    return substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', $email), 0, 20);
  }

  function die_errors($msg) {
    if(isset($msg)) {
      $msg = iconv('UTF-8//TRANSLIT', 'CP1252', $msg);
      echo "<error>{$msg}</error>";
      exit;
    }
  }
?>
