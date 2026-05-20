<?php

it('returns a redirect', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
});
