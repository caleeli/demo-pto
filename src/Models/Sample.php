<?php

namespace ProcessMaker\Package\DemoPto\Models;

use ProcessMaker\Models\ProcessMakerModel;

class Sample extends ProcessMakerModel
{
    protected $table = 'sample_skeleton';

    protected $fillable = [
        'id', 'name', 'status',
    ];
}
