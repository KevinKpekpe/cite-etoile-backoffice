<?php

test('the application requires authentication', function () {
    $this->get('/')->assertRedirect(route('login'));
});
