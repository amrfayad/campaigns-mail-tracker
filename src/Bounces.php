<?php

namespace amrfayad\CampaignMailTracker;

class Bounces {

    private $c = false; //connection
    private $errors; // imap errors
    private $messagesFound = array(); // bounced message ids to be deleted after record it
    // Imap variables
    private $imap_port = 993;  // imap port number
    private $imap_host = 'imap.gmail.com'; // imap host name
    private $imap_secure = 'ssl'; // imap secuirity
    private $label = 'INBOX';  // mail box
    private $imap_user;    // imap user name
    private $imap_pass;  // imap password
    private $from_emails;   // is array of from email that bounced 
    /**
     * @Usage for set Imap Account Data
     * @author Amr Fayad <amr.fci2007@gmail.com>
     * @param array $data
        [
             'imap_port'=>,
             'imap_host'=>,
             'imap_secure'=>,
             'label'=>,
             'imap_user'=>,
             'imap_pass'=>,
             'from_emails'=>,
        ]
     * @return 0 or 1
     */
    function setData($data) {
        foreach ($data as $key => $val) {
           $this->{$key} = $val;
        }
    }
    /*
     * connects to the imap after username and password have been set
     * return false if fails
     */
    function connect() {
        if (empty($this->imap_user) || empty($this->imap_pass)) {
            $this->errors[] = 'No username or password defined! Use Set Data To set Imap_user && pass!';
            return false;
        }
        $imapaddress = "{".
                $this->imap_host.":" .
                $this->imap_port . "/" .
                $this->imap_secure . "/novalidate-cert}";
        $this->c = @imap_open(
                            $imapaddress.$this->label,
                            $this->imap_user,
                            $this->imap_pass,
                            NULL, 1);
        if ($this->c) {
            return $this->c;
        }
        $this->errors = imap_errors();
        return false;
    }

    /*
     * Fetch any errors that could have occured
     */

    function getErrors() {
        return $this->errors;
    }

    /*
     * returns a list of all emails that bounced
     */

    function getEmailsThatBounced() {
        if (!$this->c) {
            return false;
        }
        $response = array();
        $headers = @imap_headers($this->c);
        $max_message_count = sizeof($headers);

        $count = 1;
        while ($count <= $max_message_count) {
            $headerinfo = @imap_headerinfo($this->c, $count);
            $from = $headerinfo->fromaddress;
            if (in_array($from, $this->from_emails)) {
                $body = @imap_body($this->c, $count);
                $user_id = $this->getUserId($body);
                $campaign_id = $this->getCampaignId($body);
                if ($user_id && $campaign_id) {
                    $this->messagesFound[] = $count;
                    $response[] = array(
                        'user_id' => $user_id,
                        'campaign_id' => $campaign_id
                    );
                }
            }

            $count++;
        }
        return $response;
    }
    function getCampaignId($body) {
        $lines = explode("\r\n", $body);
        foreach ($lines as $line) {
            $lookfor = 'campaignID:';
            if (substr($line, 0, strlen($lookfor)) == $lookfor) {
                $campaign_id = substr($line, strlen($lookfor));
                return $campaign_id;
            }
        }
        return null;
    }

    function getUserId($body) {
        $lines = explode("\r\n", $body);
        foreach ($lines as $line) {
            $lookfor = 'userID:';
            if (substr($line, 0, strlen($lookfor)) == $lookfor) {
                $user_id = substr($line, strlen($lookfor));
                return $user_id;
            }
        }
        return null;
    }
    /*
     * deletes all emails that have been previously found
     */

    function deleteEmailsFound() {
        if (!$this->c) {
            return false;
        }
        foreach ($this->messagesFound as $message_id) {
            $this->deleteEmail($message_id);
        }
    }

    function deleteEmail($message_id) {
        if (!$this->c) {
            return false;
        }
        @imap_delete($this->c, $message_id);
    }
    /*
     * call required to save all the changes
     * expunges messages and closes the connection
     */
    function end() {
        @imap_expunge($this->c);
        imap_close($this->c);
    }

}
