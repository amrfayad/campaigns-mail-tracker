<?php

namespace amrfayad\CampaignMailTracker;

class Bounces {

    /**
     * ImapClient Object
     * @var Object
     */
    private $imapClient;

    /**
     * @Usage for set Imap Account Data
     * @author Amr Fayad <amr.fci2007@gmail.com>
     * @param array $config
      [
      'port'=>,
      'host'=>,
      'secure'=>,
      'username'=>,
      'password'=>,
      'encryption'=>,
      ]
     * @return void
     */
    function setImapClinet($config) {
        $this->imapClient = new ImapClient($config);
    }
    /**
     * Connect To Imap Client
     */
    function connect() {
        $this->imapClient->connect();
    }
    /**
     * end Imap Client Connection
     * 
     */
    function end() {
        $this->imapClient->disconnect();
    }
    /**
     * Get Imap Client Object 
     * @return Object ImapClient
     */
    function getImapClient() {
        return $this->imapClient;
    }
    function getMessages($criteria = 'SUBJECT "*delivery*"') {
        return $this->imapClient->getResult($criteria);
        
    }
    function deleteEmailsFound(){
        $this->imapClient->deleteEmailsFound();
    }
    function getConnectionStatus(){
        return $this->imapClient->getConnectionStatus();
    }
}
