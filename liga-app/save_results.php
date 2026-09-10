<?php
// Zastaralý hromadný zápis je záměrně vypnutý. Neznal pravidla jednotlivých
// lig a dovoloval uložit remízu. Výsledky se ukládají pouze přes save_stats.php.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
exit('Hromadné ukládání bylo zrušeno. Výsledek zadejte z rozpisu konkrétního zápasu.');
