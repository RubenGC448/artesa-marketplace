<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('devuelve la portada pública correctamente', function () {
    $this->get('/')->assertOk();
});
