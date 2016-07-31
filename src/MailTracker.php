<?php 

namespace amrfayad\MailTracker;

class MailTracker implements \Swift_Events_SendListener {

	protected $hash;
        protected $user_id;
        protected $campaign_id;
        
        
  

        public function __construct($user_id ,$campaign_id ) {
            $this->user_id = $user_id ;
            $this->campaign_id = $campaign_id ; 
        }
	/**
	 * Inject the tracking code into the message
	 */
	public function beforeSendPerformed(\Swift_Events_SendEvent $event)
	{
		$message = $event->getMessage();
    	$headers = $message->getHeaders();
    	$hash = str_random(32);

    	$original_content = $message->getBody();

        if ($message->getContentType() === 'text/html' ||
            ($message->getContentType() === 'multipart/alternative' && $message->getBody())
        ) {
        	$message->setBody($this->addTrackers($message->getBody(), $hash));
        }

        foreach ($message->getChildren() as $part) {
            if (strpos($part->getContentType(), 'text/html') === 0) {
                $converter->setHTML($part->getBody());
                $part->setBody($this->addTrackers($message->getBody(), $hash));
            }
        }    	

    	Model\SentEmail::create([
                        'user_id'=>  $this->user_id,
                        'campaign_id'=>  $this->campaign_id,
            		'hash'=>$hash,
    			'headers'=>$headers->toString(),
    			'sender'=>$headers->get('from')->getFieldBody(),
    			'recipient'=>$headers->get('to')->getFieldBody(),
    			'subject'=>$headers->get('subject')->getFieldBody(),
    			'content'=>$original_content,
    		]);

    	// Purge old records
    	if(config('mail-tracker.expire-days') > 0) {
    		Model\SentEmail::where('created_at','<',\Carbon\Carbon::now()->subDays(config('mail-tracker.expire-days')))->delete();
    	}
	}

    public function sendPerformed(\Swift_Events_SendEvent $event)
    {
    	//
    }

    protected function addTrackers($html, $hash)
    {
    	if(config('mail-tracker.inject-pixel')) {
	    	$html = $this->injectTrackingPixel($html, $hash);
    	}
    	if(config('mail-tracker.track-links')) {
    		$html = $this->injectLinkTracker($html, $hash);
    	}

    	return $html;
    }

    protected function injectTrackingPixel($html, $hash)
    {
    	// Append the tracking url
    	$tracking_pixel = '<img src="'.action('\amrfayad\MailTracker\MailTrackerController@getT',[$hash]).'" />';

    	$linebreak = str_random(32);
    	$html = str_replace("\n",$linebreak,$html);

    	if(preg_match("/^(.*<body[^>]*>)(.*)$/", $html, $matches)) {
    		$html = $matches[1].$tracking_pixel.$matches[2];
    	} else {
    		$html = $tracking_pixel . $html;
    	}
    	$html = str_replace($linebreak,"\n",$html);

    	return $html;
    }

    protected function injectLinkTracker($html, $hash)
    {
    	$this->hash = $hash;

    	$html = preg_replace_callback("/(<a[^>]*href=['\"])([^'\"]*)/",
    			array($this, 'inject_link_callback'),
    			$html);

    	return $html;
    }

    protected function inject_link_callback($matches)
    {
        if (empty($matches[2])) {
            $url = app()->make('url')->to('/');
        } else {
            $url = $matches[2];
        }
        
    	return $matches[1].action('\amrfayad\MailTracker\MailTrackerController@getL',
    		[
    			MailTracker::hash_url($url),
    			$this->hash
    		]);
    }

    static public function hash_url($url)
    {
        // Replace "/" with "$"
        return str_replace("/","$",base64_encode($url));
    }
    
    static public function cheakIfCampaigSendedbefore($user_id , $campaign_id){
        return Model\SentEmail::where(
                        [
                            ['user_id',$user_id],
                            ['campaign_id',$campaign_id],
                        ])
			->first();
        
    }
}
