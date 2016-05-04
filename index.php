<?php
require_once "../../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/namespaces/Tsugi.html

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Cache;

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData(); 

// Model
$p = $CFG->dbprefix;
$old_code = Settings::linkGet('code', '');



if ( $USER->instructor) {
	
	if (isset($_POST['action']) && strcmp($_POST['action'], "results_window") == 0) {
		echo(file_get_contents("views/child_window.html"));
		return;
	}
	
	if (isset($_POST['type']) && strcmp($_POST['type'], "multiple-choice") == 0) {

	//	create poll in DB
		$title = null;
		if (isset($_POST['title'])) { $title=$_POST['title']; }
		$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_polls (context_id, modified, title) VALUES (:UI, NOW(),:TI)", 
		array(
			':UI' => $CONTEXT->id,
			':TI' => $title
			)
		);
		if ($stmt->success) { $poll_id = $PDOX->lastInsertId(); }
		else {
			error_log("TODO: failed to obtain lastInsertId from PDOX. Should probably handle appropriately...");
			throw new Exception("lastInsertId not obtained from PDOX");
		}
		
		
	//	insert choices into DB
		$choice_ids = array();
		foreach ($_POST['choice'] as $choice) {
			$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_choices (choice_hash, choice_value) 
									VALUES (PASSWORD(:VAL), :VAL) 
									ON DUPLICATE KEY UPDATE choice_id=LAST_INSERT_ID(choice_id), choice_value=:VAL",
			array(
				':VAL' => $choice
				)
			);
			if ($stmt->success) { $choice_ids[$PDOX->lastInsertId()] = $choice; }
			else {
				error_log("TODO: failed to obtain lastInsertId from PDOX. Should probably handle appropriately...");
				throw new Exception("lastInsertId not obtained from PDOX");
			}
		}
		
	//	associate poll and choices in DB
		foreach (array_keys($choice_ids) as $id) {
			$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_pollchoices (poll_id, choice_id) VALUES (:PID,:CID)",
			array(
				':PID' => $poll_id,
				':CID' => $id
				)
			);
			if (!$stmt->success) { throw new Exception("cannot associate poll and choices"); }
		}
		
	//	if choices should be ordered
		if (isset($_POST['ordered'])) {
			
	//		mark poll as ordered
			$PDOX->queryDie("UPDATE {$p}iclicker_polls SET ordered=1 WHERE poll_id=".$poll_id);
			
	//		store ordering in DB
			$i = 0;
			foreach (array_keys($choice_ids) as $id) {
				$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_order (poll_id, choice_id, position) VALUES (:PID,:CID,:POS)",
				array(
					':PID'=> $poll_id,
					':CID' => $id,
					':POS' => $i
					)
				);
				if (!$stmt->success) { throw new Exception("Failed to record position"); }
				$i++;
			}
		}
		
		if (isset($_POST['action']) && strcmp($_POST['action'], "save") == 0) {
			$stmt = $PDOX->queryDie("UPDATE iclicker_polls SET pending = 1 WHERE poll_id=".$poll_id);
			echo(json_encode(array("action"=>"save","result"=>true, "poll_id"=>$poll_id, "date"=>date('Y-m-d H:i:s'))));
			return;
		}
		
	//	process form data, be sure to sanatize input!
	//	Need to check that owner doesn't have more than one active poll (or do I want to support this?)
		$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_active (poll_id, context_id) VALUES (:PID, :CID) 
								ON DUPLICATE KEY UPDATE poll_id=:PID", 
		array(
			':PID' => $poll_id,
			':CID' => $CONTEXT->id
			)
		);
		
//		$stmt = $PDOX->queryDie("UPDATE iclicker_polls SET active=1");
		if (!$stmt->success) {
			error_log("TODO: unable to move to active. What to do...");
			throw new Exception("failed to insert into active table");
		}

		$entryData = array(
				'category' => 'polls',
				'type' => $_POST['type'],
				'poll' => $poll_id,
				'choices'  => $choice_ids,
				'order' => 'random',
				'when'     => time()
		);
		
		$context = new ZMQContext();
		$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
		$socket->connect("tcp://localhost:5555");

		$socket->send(json_encode($entryData));
		
		echo(json_encode(array('poll_id'=>$poll_id, 'date'=>date('Y-m-d H:i:s'))));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"get_active") == 0) {
		$stmt = $PDOX->queryDie("SELECT p.poll_id, p.title, p.modified FROM iclicker_active a INNER JOIN iclicker_polls p ON a.poll_id=p.poll_id WHERE a.context_id=:CID",
			array(
				':CID' => $CONTEXT->id
			)
	    );
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$row) { echo(""); return;}
		
		$stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$p}iclicker_responses WHERE poll_id=".$row['poll_id']);
		$count = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$row['count'] = $count['COUNT(*)'];
		echo(json_encode($row));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"retract_poll") == 0) {
		if (!isset($_POST['poll_id'])) { echo("incomplete API call; missing parameter"); return; }
		
		$PDOX->queryDie("UPDATE iclicker_polls SET completed=NOW() WHERE poll_id=".$_POST['poll_id']);
		$stmt = $PDOX->queryDie("DELETE FROM {$p}iclicker_active WHERE context_id=".$CONTEXT->id);
		if ($stmt->rowCount() > 0) {
			echo("true");
		} else {
			echo('no poll to retract');
		}
		
		$entryData = array(
				'category' => 'retract',
				'when'     => time()
		);
		
		$context = new ZMQContext();
		$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
		$socket->connect("tcp://localhost:5555");

		$socket->send(json_encode($entryData));
		
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"get_archive") == 0) {
		if (isset($_POST['range']) && $_POST['range'] != "undefined") {
			$stmt = $PDOX->queryDie("SELECT p.poll_id, p.title, p.completed AS date, COUNT(r.user_id) AS count, SUM(r.choice_id=p.answer_id) AS correct 
									FROM {$p}iclicker_polls p LEFT JOIN {$p}iclicker_responses r 
									ON p.poll_id=r.poll_id WHERE context_id=:CID AND p.pending IS NULL
									GROUP BY p.poll_id ORDER BY modified 
									DESC LIMIT ".$_POST['range'].", 5", array(':CID'=>$CONTEXT->id));
			$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
			echo(json_encode($row));
		} else {
			$stmt = $PDOX->queryDie("SELECT p.poll_id, p.title, p.completed AS date, COUNT(r.user_id) AS count, SUM(r.choice_id=p.answer_id) AS correct 
									FROM {$p}iclicker_polls p LEFT JOIN {$p}iclicker_responses r 
									ON p.poll_id=r.poll_id WHERE context_id=:CID AND p.pending IS NULL
									GROUP BY p.poll_id ORDER BY modified  
									DESC LIMIT 5", array(':CID'=>$CONTEXT->id));
			$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
			echo(json_encode($row));
		}
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"get_poll") == 0) {
		if (!isset($_POST['poll_id'])) { echo("incomplete API call; missing parameter"); return; }
		
		$stmt=$PDOX->queryDie("SELECT c.choice_value AS value, COUNT(*) AS count FROM iclicker_responses r
							JOIN iclicker_choices c ON r.choice_id=c.choice_id
							WHERE poll_id=".$_POST['poll_id']." GROUP BY c.choice_value");
		$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmt=$PDOX->queryDie("SELECT c.choice_value AS value FROM iclicker_pollchoices pc JOIN iclicker_choices c ON pc.choice_id=c.choice_id WHERE pc.poll_id=".$_POST['poll_id']);
		$choices = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmt = $PDOX->queryDie("SELECT * FROM iclicker_polls WHERE poll_id=".$_POST['poll_id']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$row['responses'] = $responses;
		$row['choices'] = $choices;
		echo(json_encode($row));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"copy_poll") == 0) {
		if (!isset($_POST['poll_id'])) { echo("incomplete API call; missing parameter"); return; }
		
		$stmt = $PDOX->queryDie("SELECT poll_id, title as ':t', answer_id as ':a', ordered as ':o' FROM iclicker_polls WHERE poll_id=".$_POST['poll_id']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$old_poll_id = $row['poll_id'];
		unset($row['poll_id']);
		$row[':CID'] = $CONTEXT->id;
		if (strcmp($row[':t'], "") == 0)
			$row[':t'] = null;
		
		$stmt = $PDOX->queryDie("INSERT INTO iclicker_polls (title, context_id, answer_id, ordered, modified, pending) VALUES ( :t, :CID, :a, :o, NOW(), 1)", $row);
		$new_poll_id = $PDOX->lastInsertId();
		
		$stmt = $PDOX->queryDie("SELECT choice_id FROM iclicker_pollchoices WHERE poll_id=".$old_poll_id);
		$choices = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($choices as $choice) {
			$stmt = $PDOX->queryDie("INSERT INTO iclicker_pollchoices (poll_id, choice_id) VALUES (:p, :c)", array(':p'=>$new_poll_id,':c'=>$choice['choice_id']));
		}
		
		echo(json_encode(array('poll_id'=>$new_poll_id)));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"get_pending") == 0) {
		$stmt = $PDOX->queryDie("SELECT poll_id,title,modified as date FROM iclicker_polls WHERE context_id=:CID AND pending=1",array(":CID"=>$CONTEXT->id));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$response = array("action"=>"get_pending", "result"=>$rows);
		echo(json_encode($response));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"make_active") == 0) {
		if (!isset($_POST['poll_id'])) { echo("incomplete API call; missing parameter"); return; }
		
		$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_active (poll_id, context_id) VALUES (:PID, :CID) 
								ON DUPLICATE KEY UPDATE poll_id=:PID", 
		array(
			':PID' => $_POST['poll_id'],
			':CID' => $CONTEXT->id
			)
		);
		
		$PDOX->queryDie("UPDATE iclicker_polls SET pending=NULL WHERE poll_id=".$_POST['poll_id']);
		$stmt = $PDOX->queryDie("SELECT c.choice_id, c.choice_value FROM iclicker_pollchoices pc
								JOIN iclicker_choices c ON pc.choice_id=c.choice_id
								WHERE pc.poll_id=".$_POST['poll_id']);
		$choices = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$choice_ids = array();
		foreach ($choices as $choice) {
			$choice_ids[$choice['choice_id']] = $choice['choice_value'];
		}
		
		$entryData = array(
				'category' => 'polls',
				'type' => "multiple-choice",
				'poll' => $_POST['poll_id'],
				'choices'  => $choice_ids,
				'order' => 'random',
				'when'     => time()
		);
		
		$context = new ZMQContext();
		$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
		$socket->connect("tcp://localhost:5555");

		$socket->send(json_encode($entryData));
		echo(json_encode($entryData));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'],"delete_poll") == 0) {
		if (!isset($_POST['poll_id'])) { echo("incomplete API call; missing parameter"); return; }
		
		$PDOX->queryDie("DELETE FROM iclicker_polls WHERE poll_id=".$_POST['poll_id']." AND context_id=".$CONTEXT->id);
		$PDOX->queryDie("DELETE FROM iclicker_pollchoices WHERE poll_id=".$_POST['poll_id']);
		echo('true');
		return;
	}
	
} else { // Student
	
//	iclicker API values, action specifies response
	if (isset($_POST['action']) && strcmp($_POST['action'], "get_active") == 0) {
		$cacheloc = 'iclicker_active';
		$row = Cache::check($cacheloc, $CONTEXT->id);
		
		// if $row not found in cache
		if ( $row == false ) {
			$stmt = $PDOX->queryDie("SELECT poll_id FROM {$p}iclicker_active WHERE context_id=:CID",
				array(
					':CID' => $CONTEXT->id
				)
		    );
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
		}
		
//		if no active poll, echo empty response
		if (!$row['poll_id']) {
			echo('');
			return;
		}
		
//		if poll is ordered, get choices and postions
		if (isset($row['ordered'])) {
			$stmt = $PDOX->queryDie("SELECT pc.poll_id, c.choice_id, c.choice_value, o.position
									FROM iclicker_pollchoices pc INNER JOIN iclicker_choices c
									ON pc.choice_id = c.choice_id JOIN iclicker_order o
									ON c.choice_id = o.choice_id AND pc.poll_id = o.poll_id
									WHERE pc.poll_id = :PID", 
			array(
				':PID' => $row['poll_id']
				)
			);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
		} 
//		if not ordered, get choices
		else {
			$stmt = $PDOX->queryDie("SELECT pc.poll_id, c.choice_id, c.choice_value
									FROM iclicker_pollchoices pc INNER JOIN iclicker_choices c
									ON pc.choice_id = c.choice_id
									WHERE pc.poll_id = :PID", 
			array(
				':PID' => $row['poll_id']
				)
			);
			$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		
//		return response
		echo(json_encode($row));
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'], "vote") == 0) {
		$stmt = $PDOX->queryDie("SELECT * from iclicker_active WHERE poll_id=:PID",array(':PID'=>$_POST['poll_id']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			echo "expired poll";
			return;
		}
		
		$stmt = $PDOX->queryDie("INSERT INTO {$p}iclicker_responses (user_id, poll_id, choice_id) VALUES (:UID,:PID,:CID) 
								ON DUPLICATE KEY UPDATE choice_id = :CID",
		array(
			':UID' => $USER->id,
			':PID' => $_POST['poll_id'],
			':CID' => $_POST['choice_id']
			)
		);
		if (!$stmt->success) { echo("false"); throw new Exception("unable to record response"); return; }
		echo("true");
		return;
	}
	
	else if (isset($_POST['action']) && strcmp($_POST['action'], "get_vote") == 0) {
		$stmt = $PDOX->queryDie("SELECT choice_id FROM {$p}iclicker_responses WHERE user_id=:UID AND poll_id=:PID",
		array(
			':UID' => $USER->id,
			':PID' => $_POST['poll_id']
			)
		);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		echo(json_encode($row));
		return;
	}
}


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


