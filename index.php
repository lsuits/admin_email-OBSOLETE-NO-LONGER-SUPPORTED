<?php

// Written at Louisiana State University

require_once '../../config.php';
require_once "$CFG->dirroot/course/lib.php";
require_once "$CFG->libdir/adminlib.php";
require_once "$CFG->dirroot/user/filters/lib.php";
require_once 'email_form.php';

require_login();
global $DB;

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$sort = optional_param('sort', '', PARAM_ACTION);
$direction = optional_param('dir', 'ASC', PARAM_ACTION);

$blockname = get_string('pluginname', 'block_admin_email');
$header = get_string('send_email', 'block_admin_email');

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);
$PAGE->set_url('/blocks/admin_email/');
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_heading($SITE->shortname.': '.$blockname);
$PAGE->set_pagelayout('admin');

// Get Our users
$fields = array(
    'courserole' => 0,
    'systemrole' => 0,
    'realname' => 1,
    'username' => 1,
);
$ufiltering = new user_filtering($fields);
list($sql, $params) = $ufiltering->get_sql_filter();

$usersearchcount = get_users(false, '', true, null, '', '', '', '', '', 
                '*', $sql, $params);

if(empty($sort)) $sort = 'lastname';

$users = empty($sql) ? array() :
    get_users_listing($sort, $direction, 0, 
    0, '', '', '', $sql, $params);

$paged_users = array_chunk($users, $perpage, true);

$form = new email_form();

// Process data submission
if ($form->is_cancelled()) {
    unset($SESSION->user_filtering);
    redirect(new moodle_url('/blocks/admin_email/'));
} else if ($data = $form->get_data()) {

    $warnings = array();

    $record = new stdClass();
    $record->subject = $data->subject;
    $record->text = strip_tags($data->body['text']);
    $record->html = $data->body['text'];
    $record->sender = $USER->id;
    $record->sent = array();
    
    foreach($users as $user) {
        $success = email_to_user($user, $USER, $record->subject, $record->text, $record->html, '', '', 
            true, $data->noreply, $blockname);
        if(!$success)
            $warnings[] = get_string('email_error', 'block_admin_email', $user);
        else{
            $record->sent[] = $user->id;
        }
    }
    
    //save a record for later
    admin_email_util::log($record);
    
    // Empty errors mean that you can go back home
    if(empty($warnings))
        redirect(new moodle_url('/'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

// Notify the admin.
if(!empty($warnings)) {
    foreach($warnings as $warning) {
        echo $OUTPUT->notification($warning);
    }
}

// Start work
$ufiltering->display_add();
$ufiltering->display_active();

$paging_bar = !$sql ? '' :
    $OUTPUT->paging_bar($usersearchcount, $page, $perpage,
        new moodle_url('/blocks/admin_email/index.php', array(
            'sort' => $sort,
            'dir' => $direction,
            'perpage' => $perpage
        )
    ));

if(!empty($sql)) {
    echo $OUTPUT->heading("Found $usersearchcount User(s)");
}

echo $paging_bar;

if(!empty($paged_users[$page])) {
    $columns = array('firstname', 'lastname', 'email', 'city', 'lastaccess');
    foreach($columns as $column) {
        $direction = ($sort == $column and $direction == "ASC") ? "DESC" : "ASC";
        $$column = html_writer::link('index.php?sort='.$column.'&dir='.
            $direction, get_string($column));
    }
    $table = new html_table();

    $table->head = array("$firstname / $lastname", $email, $city, $lastaccess); 
    $table->data = array_map(function($user) {
        $fullname = fullname($user);
        $email = $user->email;
        $city = $user->city;
        $lastaccess_time = isset($user->lastaccess) ? 
            format_time(time() - $user->lastaccess) : get_string('never');
        return array($fullname, $email, $city, $lastaccess_time);
    }, $paged_users[$page]);
    echo html_writer::table($table);
    $form->set_data(array('noreply' => $CFG->noreplyaddress));
    echo $form->display();
}

echo admin_email_util::history_table();
echo $paging_bar;

echo $OUTPUT->footer();

class admin_email_util{
    public static function log($record){
        global $DB;
        // Finished processing
        // save a record in the DB
        $r = new stdClass();
        $r->userid  = $record->sender;
        $r->mailto  = implode(',', $record->sent);
        $r->subject = $record->subject;
        $r->message = $record->text;
        $r->time    = time();
        //insert
        $r->id      = $DB->insert_record('block_admin_email_log', $r);
    }

    public static function history_table(){
        global $DB;
        //display a table of previous email messages
        $history = new html_table();
        $history->head = array('id', 'User', '# Recipients', 'Subject', 'Message', 'Time');

        //hard-code limit to 50 records
        //@TODO let this be a paged table of results
        $historic_records = $DB->get_records_sql('SELECT * FROM {block_admin_email_log} ORDER BY time DESC', null, 0, 50);
        $history->data = array_map(function($record){
            global $DB;
            $id = html_writer::link('item_detail.php?id='.$record->id, $record->id);
            $sender = $DB->get_record('user', array('id' => $record->userid));
            $message = strlen($record->message) >80 ? substr($record->message, 0, 80).'...' : $record->message;
            return array($id, $sender->username, count(explode(',',$record->mailto)), $record->subject, $message, strftime('%F %T',$record->time));
        },$historic_records);

        return html_writer::table($history);
    }
}