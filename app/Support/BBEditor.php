<?php

namespace App\Support;

class BBEditor
{
    public function render(?string $content): string
    {
        $content = e($content ?? '');
        $tags = [
            '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
            '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
            '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
            '/\[h2\](.*?)\[\/h2\]/is' => '<h2>$1</h2>',
            '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote>$1</blockquote>',
            '/\[list\](.*?)\[\/list\]/is' => '<ul>$1</ul>',
            '/\[olist\](.*?)\[\/olist\]/is' => '<ol>$1</ol>',
            '/\[li\](.*?)\[\/li\]/is' => '<li>$1</li>',
        ];

        return nl2br(preg_replace(array_keys($tags), array_values($tags), $content), false);
    }
}
