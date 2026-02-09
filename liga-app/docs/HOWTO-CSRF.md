# Jak přidat CSRF validaci do vlastního POST handleru

Pokud máte samostatný soubor, který ZPRACOVÁVÁ POST a **ne**používá `header.php`, 
dejte úplně na jeho začátek:

```php
<?php
require __DIR__.'/security/guard-post.php';
// ... zbytek kódu
```
Tím se u všech POST požadavků ověří platnost CSRF.

Pokud handler vykresluje formulář, můžete do `<form>` ručně vložit:

```php
<?php csrf_input(); ?>
```
(již je dostupné díky `security/csrf.php` načtenému v `header.php`)

**Poznámka:** `assets/csrf-autoinject.js` umí token vložit automaticky do všech
`<form method="post">`, takže ruční vkládání nemusí být potřeba.
