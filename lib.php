<?php


class Message {
    
    public $subject, $text, $html, $users, $warnings, $noreply;
    
    public function __construct($data, $users){
        $this->warnings = array();

        $this->subject  = $data->subject;
        $this->text     = strip_tags($data->body['text']);
        $this->html     = $data->body['text'];
        $this->noreply  = $data->noreply;
        $this->users    = $users;
    }
    
    public function send(){
        global $USER;
        foreach($this->users as $user) {

            $success = email_to_user(
                    $user,          // to
                    $USER,          // from
                    $this->subject, // subj
                    $this->text,    // body in plain text
                    $this->html,    // body in HTML
                    '',             // attachment
                    '',             // attachment name
                    true,           // user true address ($USER)
                    $this->noreply, // reply-to address
                    get_string('pluginname', 'block_admin_email') // reply-to name
                    );
            if(!$success)
                $this->warnings[] = get_string('email_error', 'block_admin_email', $user);
            else{
                //
            }
        }
    }
}

?>
