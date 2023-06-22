<?php

return [
    //You should enter connection name
    'from' => 'production',

    //array of tables which we don't have to copy
    'except' => [

    ],

    //count of rows for each job
    'batch-size' => 500000
];
