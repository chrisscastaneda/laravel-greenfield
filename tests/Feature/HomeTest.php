<?php

test('the home page is publicly accessible', function () {
    $this->get(route('home'))->assertOk();
});

test('the health check endpoint responds', function () {
    $this->get('/up')->assertOk();
});
