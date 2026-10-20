<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use SoftDeletes;

    protected $fillable = ['type', 'name', 'email', 'organisation', 'subject', 'message', 'status', 'admin_notes', 'replied_at', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['type' => EnquiryType::class, 'status' => EnquiryStatus::class, 'replied_at' => 'datetime'];
    }
}
