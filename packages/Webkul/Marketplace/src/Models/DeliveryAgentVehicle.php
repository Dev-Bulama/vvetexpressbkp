<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryAgentVehicle extends Model
{
    protected $table = 'delivery_agent_vehicles';

    protected $fillable = [
        'type',
        'plate_number',
        'model',
        'color',
    ];
}
