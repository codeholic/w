<?php

/*
 * w - A Wiki Software
 * 
 * Copyright (c) 2009, 2010 Ivan Fomichev
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 */

define('CAPTCHA_CHARACTERS', '0123456789');
define('CAPTCHA_LENGTH', 4);

session_start();

$_SESSION['captcha_text'] = '';
for ($i = 0; $i < CAPTCHA_LENGTH; $i++) {
    $_SESSION['captcha_text'] .= substr(CAPTCHA_CHARACTERS, rand(0, strlen(CAPTCHA_CHARACTERS) - 1), 1);
}

header("Content-type: image/png");

$width  = CAPTCHA_LENGTH * 7 + 3;
$height = 14;

$im     = imagecreate($width, $height);
$white  = imagecolorallocate($im, 255, 255, 255); # background color
$black  = imagecolorallocate($im, 0, 0, 0);
$px     = (imagesx($im) - 7.5 * CAPTCHA_LENGTH) / 2;
imagestring($im, 3, 2, 0, $_SESSION['captcha_text'], $black);
imagepng($im);
imagedestroy($im);

?>
