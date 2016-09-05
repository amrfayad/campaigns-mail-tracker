<?php
namespace amrfayad\CampaignMailTracker;
use amrfayad\CampaignMailTracker\Exceptions\ConnectionFailedException;
use ErrorException;
Class ImapClient{
    
    /**
     * @var bool|resource
     */
    protected $connection = false;
    /**
     * Server hostname.
     *
     * @var string
     */
    public $host = 'imap.gmail.com';
    /**
     * Server port.
     *
     * @var int
     */
    public $port = 993;
    /**
     * Server encryption.
     * Supported: none, ssl or tls.
     *
     * @var string
     */
    public $encryption = 'ssl';
    /**
     * If server has to validate cert.
     *
     * @var mixed
     */
    public $validate_cert;
    /**
     * Account username/
     *
     * @var mixed
     */
    public $username;
    /**
     * Account password.
     *
     * @var string
     */
    public $password;
    /**
     *  Imap Connection Error
     *  @var array 
     */
    public $errors ;
    /**
     * Read only parameter.
     *
     * @var bool
     */
    protected $read_only = false;
    /**
     * Messages 
     * @var array
     */
    protected $messages ; 
   
    /**
     * ImapClient constructor.
     *
     * @param array $config
     */
    public function __construct($config = []) {
        foreach ($config as $key => $value) {
            if(property_exists($this, $key))
            {
                $this->{$key} = $value ; 
            }
        }
    }
    /**
     * Set read only property and reconnect if it's necessary.
     *
     * @param bool $readOnly
     */
    public function setReadOnly($readOnly = true)
    {
        $this->read_only = $readOnly;
    }

    /**
     * Determine if connection was established.
     *
     * @return bool
     */
    public function isConnected()
    {
        return ($this->connection) ? true : false;
    }

    /**
     * Determine if connection is in read only mode.
     *
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->read_only;
    }

    /**
     * Determine if connection was established and connect if not.
     */
    public function checkConnection()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
    }
    /**
     * Connect to server.
     *
     * @param int $attempts
     *
     * @return $this
     * @throws ConnectionFailedException
     */
    public function connect($attempts = 3)
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }
        try {
            $this->connection = imap_open(
                $this->getAddress(),
                $this->username,
                $this->password,
                $this->getOptions(),
                $attempts
            );
        } catch (ErrorException $e) {
            $message = $e->getMessage().'. '.implode("; ", imap_errors());
            $this->errors = $message ; 
        }
        return $this;
    }
    /**
     * Disconnect from server.
     *
     * @return $this
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            imap_expunge($this->connection);
            imap_close($this->connection);
        }
    }
    /**
     * Get option for imap_open and imap_reopen.
     * It supports only isReadOnly feature.
     *
     * @return int
     */
    protected function getOptions()
    {
        return ($this->isReadOnly()) ? OP_READONLY : 0;
    }
    /**
     * Get full address of mailbox.
     *
     * @return string
     */
    protected function getAddress()
    {
        $address = "{".$this->host.":".$this->port."/imap";
        if (!$this->validate_cert) {
            $address .= '/novalidate-cert';
        }
        if ($this->encryption == 'ssl') {
            $address .= '/ssl';
        }
        $address .= '}';
        return $address;
    }
    public function getMessages($criteria = 'SUBJECT "*delivery*"') {
        $this->checkConnection();
        try {
            $this->messages = imap_search($this->connection, $criteria);
        } catch (ErrorException $e) {
            $message = $e->getMessage();
            throw new GetMessagesFailedException($message);
        }
    }
    /**
     * 
     */
    public function getHeaders($messages){
        if($messages)
        {
            $headers = array();
            foreach ($messages as $message)
            {
                $headers[] = imap_fetchheader($this->connection, $message );
            }
            return $headers;
        }
        return null;
    }
    public function getHeader($messageId){
        return imap_fetchheader($this->connection, $messageId);
    }
    public function deleteMessage($messageId){
        //return imap_delete($this->connection, $messageId);
        return $messageId;
    }
    public function getBody($messageId) {
        return imap_body($this->connection, $messageId);
    }
    function getResult($criteria) {
        if(!$this->messages)
        {
             $this->getMessages($criteria);
        }
        $result = array();
        $matches = array();
        foreach ($this->messages as $messageId){
            $header = $this->getHeader($messageId);
            if (empty($header)) {
                $this->deleteMessage($messageId);
                continue;
            }
            if(preg_match("/Content-Type:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/is",
                    $header, $matches))
            {
                if (preg_match("/multipart\/report/is", $matches[1]) &&
                    preg_match("/report-type=[\"']?delivery-status[\"']?/is", $matches[1]))
                        {
                            echo "DNS Process\n";
                            $result[] = $this->processDsn($messageId);
                        }
                else{
                    
                            echo "Body Process\n";
                        $result[] = $this->processBody($messageId);
                    }
           }else
            {
               echo "Process body\n";
               $result[] = $this->processBody($messageId);
            }
        }
        return $result;
        
    }
    
    
    
    protected function processDsn($messageId)
    {
        $result = array(
            'user_id'       => null,
            'campaign_id'   => null,
            'email'         => null,
            'bounceType'    => null,
            'action'        => null,
            'statusCode'    => null,
            'diagnosticCode'=> null,
        );
        $action = $statusCode = $diagnosticCode = null;
        // first part of DSN (Delivery Status Notification), human-readable explanation
        $dsnMessage = imap_fetchbody($this->connection, $messageId, "1");
        $dsnMessageStructure = imap_bodystruct($this->connection, $messageId, "1");;
        if ($dsnMessageStructure->encoding == 4) {
            $dsnMessage = quoted_printable_decode($dsnMessage);
        } elseif ($dsnMessageStructure->encoding == 3) {
            $dsnMessage = base64_decode($dsnMessage);
        }
        // second part of DSN (Delivery Status Notification), delivery-status
        $dsnReport = imap_fetchbody($this->connection, $messageId, "2");
        if (preg_match("/Original-Recipient: rfc822;(.*)/i", $dsnReport, $matches)) {
            $emailArr = imap_rfc822_parse_adrlist($matches[1], 'default.domain.name');
            if (isset($emailArr[0]->host) && $emailArr[0]->host != '.SYNTAX-ERROR.' && $emailArr[0]->host != 'default.domain.name' ) {
                $result['email'] = $emailArr[0]->mailbox.'@'.$emailArr[0]->host;
            }
        } else if (preg_match("/Final-Recipient: rfc822;(.*)/i", $dsnReport, $matches)) {
            $emailArr = imap_rfc822_parse_adrlist($matches[1], 'default.domain.name');
            if (isset($emailArr[0]->host) && $emailArr[0]->host != '.SYNTAX-ERROR.' && $emailArr[0]->host != 'default.domain.name' ) {
                $result['email'] = $emailArr[0]->mailbox.'@'.$emailArr[0]->host;
            }
        }
        if (preg_match ("/Action: (.+)/i", $dsnReport, $matches)) {
            $action = strtolower(trim($matches[1]));
        }
        if (preg_match ("/Status: ([0-9\.]+)/i", $dsnReport, $matches)) {
            $statusCode = $matches[1];
        }
        // Could be multi-line , if the new line is beginning with SPACE or HTAB
        if (preg_match ("/Diagnostic-Code:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/is", $dsnReport, $matches)) {
            $diagnosticCode = $matches[1];
        }
        if (empty($result['email'])) {
            if (preg_match ("/quota exceed.*<(\S+@\S+\w)>/is", $dsnMessage, $matches)) {
                $result['email'] = $matches[1];
                $result['bounceType'] = 'soft';
            }
        } else {
            // "failed" / "delayed" / "delivered" / "relayed" / "expanded"
            if ($action == 'failed') {
                $rules = $this->getRules();
                $foundMatch = false;
                foreach ($rules['DIAGNOSTIC_CODE_RULES'] as $rule) {
                    if (preg_match($rule['regex'], $diagnosticCode, $matches)) {
                        $foundMatch = true;
                        $result['bounceType'] = $rule['bounceType'];
                        break;
                    }
                }
                if (!$foundMatch) {
                    foreach ($rules['DSN_MESSAGE_RULES'] as $rule) {
                        if (preg_match($rule['regex'], $dsnMessage, $matches)) {
                            $foundMatch = true;
                            $result['bounceType'] = $rule['bounceType'];
                            break;
                        }
                    }    
                }
                if (!$foundMatch) {
                    foreach ($rules['COMMON_RULES'] as $rule) {
                        if (preg_match($rule['regex'], $dsnMessage, $matches)) {
                            $foundMatch = true;
                            $result['bounceType'] = $rule['bounceType'];
                            break;
                        }
                    }    
                }
            } else {
                $result['bounceType'] = 'soft';
            }    
        }
        $body = $this->getBody($messageId);
        $result['user_id'] = $this->getUserId($body);
        $result['campaign_id'] = $this->getCampaignId($body);
        $result['action'] = $action;
        $result['statusCode'] = $statusCode;
        $result['diagnosticCode'] = $diagnosticCode;
        return $result;
    }
    protected function processBody($messageId)
    {
        $result    = array(
            'user_id'       => null,
            'campaign_id'   => null,
            'email'         => null,
            'bounceType'    => null,
            'action'        => null,
            'statusCode'    => null,
            'diagnosticCode'=> null,
        );
        
        $body = null;
        $structure = imap_fetchstructure($this->connection, $messageId);
        if (in_array($structure->type, array(0, 1))) {
            $body = imap_fetchbody($this->connection, $messageId, "1");
            // Detect encoding and decode - only base64
            if (isset($structure->parts) && isset($structure->parts[0]) && $structure->parts[0]->encoding == 4) {
                $body = quoted_printable_decode($body);
            } elseif (isset($structure->parts) && $structure->parts[0] && $structure->parts[0]->encoding == 3) {
                $body = base64_decode($body);
            }
        }
        elseif ($structure->type == 2) {
            $body = $this->getBody($messageId);
            if ($structure->encoding == 4) {
                $body = quoted_printable_decode($body);
            } elseif ($structure->encoding == 3) {
                $body = base64_decode($body);
            }
        }
        if (!$body) {
            $result['bounceType'] = 'hard';
            return $result;
        }
        $rules = $this->getRules();
        $foundMatch = false;
        foreach ($rules['BODY_RULES'] as $rule) {
            if (preg_match($rule['regex'], $body, $matches)) {
                $foundMatch = true;
                $result['bounceType'] = $rule['bounceType'];
                if (isset($rule['regexEmailIndex']) && isset($matches[$rule['regexEmailIndex']])) {
                    $result['email'] = $matches[$rule['regexEmailIndex']];
                }
                break;
            }
        }
        if (!$foundMatch) {
            foreach ($rules['COMMON_RULES'] as $rule) {
                if (preg_match($rule['regex'], $body, $matches)) {
                    $foundMatch = true;
                    $result['bounceType'] = $rule['bounceType'];
                    break;
                }
            }    
        }
        var_dump($body);
        $result['user_id'] = $this->getUserId($body);
        $result['campaign_id'] = $this->getCampaignId($body);
        return $result;
    }
    function getCampaignId($body) {
        $lines = explode("\r\n", $body);
        foreach ($lines as $line) {
            $lookfor = 'campaignID: ';
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
            $lookfor = 'userID: ';
            if (substr($line, 0, strlen($lookfor)) == $lookfor) {
                $user_id = substr($line, strlen($lookfor));
                return $user_id;
            }
        }
        return null;
    }
    public function  getRules()
    {
        $rules = config('campaigns-mail-tracker.bounce-types');
        return $rules;
    }
    
}