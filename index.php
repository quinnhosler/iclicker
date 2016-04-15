<?php
require_once "../../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/namespaces/Tsugi.html

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData(); 

// Model
$p = $CFG->dbprefix;
$old_code = Settings::linkGet('code', '');

// This section is only meant to process form POSTs. isset() are needed to 
if ( $USER->instructor && isset($_POST['test_field'])) {
//    Settings::linkSet('code', $_POST['code']);
//    $_SESSION['success'] = 'Code updated';
//    header( 'Location: '.addSession('index.php') ) ;
//    return;
//	echo(implode(" ",$_POST));
	$entryData = array(
			'category' => "multiple_choice",
			'title'    => "test title",
			'article'  => "test article",
			'when'     => time()
	);
	
	$context = new ZMQContext();
	$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
	$socket->connect("tcp://localhost:5555");

	$socket->send(json_encode($entryData));
	
	echo("poll sent to students!");
	return;
} else { // Student
//    if ( $old_code == $_POST['code'] ) {
//        $PDOX->queryDie("INSERT INTO {$p}attend
//            (link_id, user_id, ipaddr, attend, updated_at)
//            VALUES ( :LI, :UI, :IP, NOW(), NOW() )
//            ON DUPLICATE KEY UPDATE updated_at = NOW()",
//            array(
//                ':LI' => $LINK->id,
//                ':UI' => $USER->id,
//                ':IP' => $_SERVER["REMOTE_ADDR"]
//            )
//        );
//        $_SESSION['success'] = __('Attendance Recorded...');
//    } else {
//        $_SESSION['error'] = __('Code incorrect');
//    }
//    header( 'Location: '.addSession('index.php') ) ;
//    return;
}
//
//// View
//$OUTPUT->header();
//$OUTPUT->bodyStart();
//$OUTPUT->flashMessages();
//$OUTPUT->welcomeUserCourse();
//
//// We could use the settings form - but we will keep this simple
//echo('<form method="post">');
//echo(_("Enter code:")."\n");
//if ( $USER->instructor ) {
//    echo('<input type="text" name="code" value="'.htmlent_utf8($old_code).'"> ');
//    echo('<input type="submit" class="btn btn-normal" name="set" value="'._('Update code').'"> ');
//    echo('<input type="submit" class="btn btn-warning" name="clear" value="'._('Clear data').'"><br/>');
//} else {
//    echo('<input type="text" name="code" value=""> ');
//    echo('<input type="submit" class="btn btn-normal" name="set" value="'._('Record attendance').'"><br/>');
//}
//echo("\n</form>\n");
//
//if ( $USER->instructor ) {
//    $rows = $PDOX->allRowsDie("SELECT user_id,attend,ipaddr FROM {$p}attend
//            WHERE link_id = :LI ORDER BY attend DESC, user_id",
//            array(':LI' => $LINK->id)
//    );
//    echo('<table border="1">'."\n");
//    echo("<tr><th>"._("User")."</th><th>"._("Attendance")."</th><th>"._("IP Address")."</th></tr>\n");
//    foreach ( $rows as $row ) {
//        echo "<tr><td>";
//        echo($row['user_id']);
//        echo("</td><td>");
//        echo($row['attend']);
//        echo("</td><td>");
//        echo(htmlent_utf8($row['ipaddr']));
//        echo("</td></tr>\n");
//    }
//    echo("</table>\n");
//}

//$OUTPUT->footer();

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

if ( $USER->instructor ) {
	
	include "views/instructor.php";
} else {
	include "views/student.php";
}

echo <<<EOT
<script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] +
  ':35729/livereload.js?snipver=1"></' + 'script>')</script>
EOT;

$OUTPUT->footer();


