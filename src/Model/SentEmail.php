<?php

namespace amrfayad\CampaignMailTracker\Model;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    protected $fillable = [
		'user_id',
        'campaign_id',
    	'hash',
    	'headers',
    	'sender',
    	'recipient',
    	'subject',
    	'content',
    	'opens',
    	'clicks',
		'bounces',
		'bounce_type'
    ];
}
