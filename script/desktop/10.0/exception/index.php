<?php
  require_once('../../../../tools/base.inc.php');
  $env = getenv();

  define('CACERT_PATH', $env['DOCUMENT_ROOT'].'/tools/cacert.pem');
  if(!isset($_REQUEST['Text']) ||
    !isset($_REQUEST['Details']) ||
    !isset($_REQUEST['Version']) ||
    !isset($_REQUEST['Application']) ||
    !isset($_REQUEST['CrashID'])) {
    echo "<error>Invalid parameters.</error>";
    exit;
  }

  header('Content-Type: text/plain');

  $github_user = $env['api_keyman_com_github_user'];
  $github_repo = $env['api_keyman_com_github_repo'];

  $crashid = $_REQUEST['CrashID'];
  $text = $_REQUEST['Text'];
  $details = $_REQUEST['Details'];
  $version = $_REQUEST['Version'];
  $app = $_REQUEST['Application'];

  $github_report = '';

  // Query GH API for existing issue number

  $issue_data = CallGitHub("/search/issues?q=in:title%20CrashID:$crashid%20repo:$github_repo", null);
  //var_dump($issue_data);
  //var_dump($github_report);

  if(!isset($issue_data->total_count) || $issue_data->total_count == 0) {
    $data = Json(array(
      "title" => "CrashID:$crashid",
      "body" => "Crash reports for $crashid\nApplication: $app\nVersion: $version",
      "labels" => array("submitted", "crash")
    ));
    $issue_id = ImportIssue($data);
  } else {
    $issue_id = $issue_data->items[0]->number;
  }

  $report = FormatCrashReport();
  $data = Json(array(
    "body" => $report
  ));
  $comment_id = ImportDocument($issue_id, $data);
  if(!$comment_id) {
    echo "Failed:\n\n$github_report\n";
    exit;
  }

  /*
    Export attachments to GitHub
  */

  if(isset($_FILES['DiagnosticReport'])) {
    $report .= "\n___\n";
    UploadFile($_FILES['DiagnosticReport']['name'], $_FILES['DiagnosticReport']['tmp_name'], "/repos/$github_repo/contents/gh$issue_id/$comment_id/");
  }

  $i = 0;
  while(isset($_FILES['ErrLog'.$i])) {
    if(!isset($_FILES['DiagnosticReport']))
      $report .= "\n___\n";

    UploadFile($_FILES['ErrLog'.$i]['name'], $_FILES['ErrLog'.$i]['tmp_name'], "/repos/$github_repo/contents/gh$issue_id/$comment_id/");
    $i++;
  }

  $data = Json(array(
    "body" => $report
  ));
  if(!CallGitHub("/repos/$github_repo/issues/comments/$comment_id", $data, "PATCH")) {
    echo "Failed:\n\n$github_report\n";
    exit;
  }

  function UploadFile($name, $source, $url) {
    global $issue_id;
    global $report, $comment_id, $github_repo;
    $data = Json(array(
      "message" => "Attachment for issue $issue_id",
      "branch" => "master",
      "content" => base64_encode(file_get_contents($source))
    ));
    if(!!CallGitHub($url . $name, $data, 'PUT')) {
      $report .= "* [$name](https://github.com/$github_repo/blob/master/gh$issue_id/$comment_id/$name?raw=true)\n";
    }
  }

  echo "<result><text>Issue logged with ID GH$issue_id</text><id>GH$issue_id</id><doc>$comment_id</doc></result>";

  function FormatCrashReport() {
    global $crashid, $text, $details, $version, $app;

    if(preg_match("/^\d+\.\d+/", $version, $pr)) {
      $majorVersion = $pr[0];
    } else {
      $majorVersion = "unknown-version";
    }

    $report = <<<END
````
Crash Identifier: $crashid

$text

$details

$app-$version $app-$majorVersion $app $version $majorVersion
````

END;
    return $report;
  }

  function ImportIssue($data) {
    global $github_repo;
    $res = CallGitHub("/repos/$github_repo/issues", $data);
    if(!$res) {
      return false;
    }
    return $res->number;
  }

  function ImportDocument($id, $data) {
    global $github_repo;
    $res = CallGitHub("/repos/$github_repo/issues/$id/comments", $data);
    if(!$res) {
      return false;
    }
    return $res->id;
  }

  function Json($array) {
    return json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  function CallGitHub($path, $data, $method = 'GET') {
    global $github_user, $github_report;
    $url = "https://$github_user@api.github.com$path";
    $ch = curl_init($url);
    //var_dump($url);
    curl_setopt($ch, CURLOPT_CAINFO, CACERT_PATH);
    if(!empty($data)) {
      if($method == 'GET') {
        curl_setopt($ch, CURLOPT_POST, true);
      } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: application/vnd.github.full+json',
      'User-Agent: curl 1.0'));
    curl_setopt($ch, CURLOPT_HEADER, false);
    if(!$response = curl_exec($ch)) {
      trigger_error(curl_error($ch));
      exit;
    }
    curl_close($ch);
    $github_report = $response;
    return json_decode($response);
  }

?>
