<?php

namespace App\Constants;

class Enquiries
{
    public const TYPES = [
        'General',
        'Sales',
        'Vehicle Information',
        'Test Drive',
        'Price Enquiry',
        'Financing',
        'Insurance',
        'Trade-In',
        'Availability',
        'Service',
        'Parts',
        'Complaint',
        'Feedback',
        'Other',
    ];

    public const STATUSES = [
        'New',
        'In Progress',
        'Awaiting Customer',
        'Responded',
        'Closed',
        'Converted to Sale',
        'Cancelled',
    ];

    public const SOURCES = [
        'Website',
        'Phone Call',
        'Email',
        'Walk-in',
        'Social Media',
        'Referral',
        'Online Marketplace',
        'Campaign',
        'Event',
        'Other',
    ];
}


