<?php
KYWeb::refuse_direct_access(".sql.php");

// Library objects
$page = KYWeb::page();
$file = KYWeb::file();
$db   = KYWeb::db();
$mail = KYWeb::mail();
$api  = KYWeb::api();


// @function [KYPage object]->get_post
//     Get value of url parameter in order of GET and POST.
// @param $name Parameter name.
// @param $default Default value if not exist parameter name. (Optional)
// @return Value of GET or POST parameter.
// @sample
//     $a = $page->get_post("a");


// @function [KYPage object]->post_get
//     Get value of url parameter in order of POST and GET.
// @param $name Parameter name.
// @param $default Default value if not exist parameter name. (Optional)
// @return Value of POST or GET parameter.
// @sample
//     $b = $page->post_get("b");


// @function [KYPage object]->uri
//     Get uri argument.
// @param $index Index of uri arguments. (Optional)
// @return If $index is not set then return array of all uri arguments.
//         If $index is setted then return value of uri argument at the position $index.
// @sample
//   $uri = $page->uri();
//   $val = $page->uri(1);


// @function [KYPage object]->session
//     Set, Get & Remove session value.
// @param $name Name of session.
// @param $value Value of session. If $value is NULL then the $name session was removed.
// @return If $value is not set then return the value of $name session.
// @sample
//     $page->session("a", "b");


// @function [KYPage object]->clear_session
//     Clear all session values.
// @sample
//     $page->clear_session();


// @function [KYPage object]->login
//     Set & Get login value. Is same to $page->session("login")
// @param $value Login value, for example user id.
// @return If param $value is not setted return login value.
// @sample
//     $page->login($value);


// @function [KYPage object]->logout
//     Clear login value. Is same to $page->session("login", NULL)
// @sample
//     $page->logout();


// @function [KYPage object]->is_logined
//     Return if logined state.
// @return Logined state. true=Logined, false=Logout
// @sample
//     if ($page->is_logined() {


// @function [KYPage object]->request_header
//     Output header:
//     HTTP/1.1 400 Bad Request
//     HTTP/1.1 401 Unauthorized
//     HTTP/1.1 404 Not Found
// @param $code Header code (400, 401, 404)
// @param $realm Script unique id.
// @param $error Error name.
// @param $error_description Error description.
// @sample
//     $page->request_header(400, "", "", "");
//     $page->request_header(401, "");
//     $page->request_header(404);


// @function [KYPage object]->write_log
//     Write error log.
// @param $title Error title.
// @param $source Error source.
// @param $message Error message.
// @param $email If need send mail set email address. (Optional)
// @sample
//     write_log("System Error", "sample.php", "This is error sample.");
//     write_log("System Error", "sample.php", "This is error sample.", "error@sample.com");


// @function [KYPage object]->assign
//     Assign value for replace html {tag}.
// @param $arg0 Tag name or associative array of tag name and value.
// @param $arg1 Replace value if use tag name in $arg0.
// @sample
//     $page->assign("VAR1", "Hello");
//     $page->assign("VAR2", "World");
// @sample
//     $page->assign(array(
//         "VAR1" => "Hello",
//         "VAR2" => "World",
//     ));


// @function [KYPage object]->sample
//     Get [KYSample object] for repeat html.
// @param $tag Tag name. This tag is eclosed html for repeat.
//     e.g.
//     <!--[SAMPLE:TEST]-->
//     <tr>
//         <td>{VALUE1}<td>
//         <td>{VALUE2}<td>
//     </tr>
//     <!--[SAMPLE:TEST]-->
// @return [KYSample object]
// @sample
//     $test = $page->sample("TEST");


// @function [KYSample object]->add
//     Add repeat html to sample
// @return [KYHtml object]
//     [KYHtml object] has assign functionallity equals to [KYPage object]->assign.
// @sample
//     $item = $test->add();
//     $item->assign("VALUE1", "abc");
//     $item->assign("VALUE2", "xyz");
// @sample
//     $test->add(array(
//         "VALUE1" => "abc", 
//         "VALUE2" => "xyz"
//     ));


// @function [KYMail object]->template
//     Set template name for send mail.
//     The templates is storage into /private/mail.
// @param $name Template name.
// @sample
//     $mail->template("sample");


// @function [KYMail object]->assign
//     Is similar to [KYPage object]->assign.
//     Assign value for replace template {tag} for send mail.
// @param $tag Tag name.
// @param $value Replace value.
// @sample
//     $mail->assign("NAME", "xxx");
// @sample
//     $mail->assign(array(
//         "NAME" => "xxx"
//     ));


// @function [KYMail object]->send
//     Send mail prepared with previously:
//     [KYMail object]->template
//     [KYMail object]->assign
// @param $form From email address.
// @param $to To email address.
// @param $cc Cc email address.
// @sample
//     $mail->template("sample");
//     $mail->assign("NAME", "xxx");
//     $mail->send("from@sample.com", "to@sample.com", "cc@sample.com");


// @function [KYDB object]->connect
//     Connect to database.
// @sample
//     $db->connect();


// @function [KYDB object]->close
//     Close to database.
// @sample
//     $db->close();


// @function [KYDB object]->execute
//     Execute a query on the database.
// @param $query Query Name.
// @param $params Associative array of param name and value.
// @return Returns false on failure. For successful return a result object.
// @sample
//     if ($db->connect()) {
//         $res = $db->execute("GET_SAMPLE", array("user_id"=>999));
//         if ($db->errno() == 0 && $row = $db->fetchAssoc($res)) {
//             echo $row["name"];
//         } else {
//            echo $db->error();
//         }
//         $db->close();
//     }

?>