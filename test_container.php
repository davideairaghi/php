<?php

require_once(__DIR__ . '/library/Airaghi/Tools/Container.php');

$a = 'A';
$b = 'B';
$c = new \stdClass();
$c->n = 1;

\Airaghi\Tools\Container::removeAll();

\Airaghi\Tools\Container::store('a',$a);
$x = \Airaghi\Tools\Container::getStored('a');
echo 'X = '.$x."\n";
$x = 1;
echo 'A = '.$a.' ; X = '.$x."\n\n";

\Airaghi\Tools\Container::share('b',$b);
$x = &\Airaghi\Tools\Container::getShared('b');
echo 'X = '.$x."\n";
$b = 1;
echo 'B = '.$b.' ; X = '.$x."\n\n";

\Airaghi\Tools\Container::share('c',$c);
$y = \Airaghi\Tools\Container::getShared('c');
echo 'Y = '.print_r($y,true)."\n";
$y->n = 2;
echo 'C = '.print_r($c,true).' ; Y = '.print_r($y,true)."\n";


?>