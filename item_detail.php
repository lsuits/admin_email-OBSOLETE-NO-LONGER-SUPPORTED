<?php

require_once '../../config.php';
require_login();

global $DB, $OUTPUT, $SITE;

$blockname = get_string('pluginname', 'block_admin_email');
$header = get_string('email_detail', 'block_admin_email');
$context = get_context_instance(CONTEXT_SYSTEM);

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$PAGE->set_context($context);
$PAGE->set_url('/blocks/admin_email/');
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_heading($SITE->shortname.': '.$blockname);


$id = required_param('id', PARAM_INT);

$record = $DB->get_record('block_admin_email_log', array('id'=> $id));
$sender = $DB->get_record('user', array('id' => $record->userid));

echo sprintf("Sender:<br/>%s<br/>", $sender->username);
echo sprintf("Message:<br/>%s<br/>", $record->message);

$sentto = explode(',', $record->mailto);

$recips = $DB->get_records_list('user', 'id', $sentto);

echo sprintf("Sent To:<br/>");
foreach($recips as $r){
    echo sprintf("%s<br/>",$r->email);
}

echo $OUTPUT->footer();

?>
