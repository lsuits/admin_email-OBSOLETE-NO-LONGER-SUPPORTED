<?php

require_once '../../config.php';
require_login();

global $DB, $OUTPUT, $SITE;

$blockname = get_string('pluginname', 'block_admin_email');
$header = get_string('email_detail', 'block_admin_email');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_url('/blocks/admin_email/');
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$id = required_param('id', PARAM_INT);
$record = $DB->get_record('block_admin_email_log', array('id'=> $id));


$sender = $DB->get_record('user', array('id' => $record->userid));
$sentto = explode(',', $record->mailto);

$recips = $DB->get_records_list('user', 'id', $sentto);


$emails = array();
foreach($recips as $r){
    $emails[] = $r->email;
}
$addresses = implode(',', $emails);
$back = html_writer::link('index.php', 'Go Back');
echo sprintf("On %s, %s sent the following message, with subject %s, to the recipients listed at bottom<hr/>", 
        html_writer::tag('strong',strftime('%F %T',$record->time)),  
        html_writer::tag('strong', $sender->username), 
        html_writer::tag('em', $record->subject)
        );
echo html_writer::tag('textarea', $record->message, array('cols'=>80, 'rows'=>10));
echo "<br/>";
echo html_writer::tag('textarea', $addresses, array('cols'=>80, 'rows'=>20));
echo "<br/>".$back;

echo $OUTPUT->footer();

?>
