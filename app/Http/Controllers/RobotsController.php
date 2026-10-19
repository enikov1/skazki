<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        if (! config('site.indexable')) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password/',
            'Disallow: /vhod',
            'Disallow: /registratsiya',
            'Disallow: /zabyl-parol',
            'Disallow: /sbros-parol/',
            'Disallow: /demo/',
            'Disallow: /*?*',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ]);
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
