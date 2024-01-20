-QuickNote: Bohužel framework má moc souborů, takže když stahujete z githubu, tak se musíte proklikat několika zipama.
# ProjektWAPololetni
programy potřebné ke spuštění:
Aplikace byla vytvořena pomocí ratchet a slim framework v PHP s xampp. Využívá se také apache a mysql.

----------------------------
programy použité při vytváření: 

https://www.jetbrains.com/phpstorm/ (popřípadě vaše volba jiného kódovacího programu) https://www.apachefriends.org/index.html
http://socketo.me/
https://getcomposer.org/
https://www.slimframework.com/

----------------------------
spuštění na serveru localhost:

1. staženýrozbalený soubor SkolaRestApi vložíte do složky xampp\htdocs
2. druhý soubor register vložíte do složky xampp\mysql\data
3. SkolaRestApi si otevřete v kódovacím programu
4. Zapněte si xampp control panel a spusťte apache a mysql
5. do terminálu v kódovacím programu vložte tento příkaz: php server.php
6. Poté otevřete Register.php a zaregistrujte se a poté pro ověření přihlašte
7. Vytvořte místnost a můžete v ní komunikovat

spuštění RestAPI na localhost:

1. stažený rozbalený soubor SkolaRestApi vložíte do složky xampp\htdocs
2. druhý soubor register vložíte do složky xampp\mysql\data
3. SkolaRestApi si otevřete v kódovacím programu
4. Zapněte si xampp control panel a spusťte apache a mysql
5. do terminálu v kódovacím programu vložte tento příkaz: php -S localhost:8080 -t public public/index.php
6. Poté zkopíčujte odkaz, který vám to vytvoří a vložte jeho url do prohlížeče s koncovkou:  /blog/all       např: http://localhost:8080/blog/all
   to vám ukáže veškerá data


-----------------------
Popis Aplikace:

Aplikace je online chat, kde můžete vytvářet místnosti a komunikovat s ostatními. Lze zamítnout přístup osobám. Tuto možnost máte při vytvoření místnosti. Mezi místnostmi lze přecházet a napsané zprávy se ukládají do RestAPI, které je přistupné pomocí endpointů.


------------------------------------------------

RestApi endpointy:

GET /messages

- vypíše všechny zprávy

GET /users/{userId}/messages 

-userId je email daného uživatele
-vypíše všechny zprávy zadaného uživatele


GET /rooms/{room}/messages

-room je název místnosti
-vypíše všechny zprávy v dané místnosti

GET /messages/search/{word}

-word je slovo, které chcete vyhledat
-vypíše všechny zprávy, které obsahují dané slovo

------------------------
InfoLog:

-zapisují se do něj neautorizované pokusy o přistup do aplikace

ErrorLog:

-zapisují se do něj errory aplikace



--------------------------

Websocket se zde používá pro implementaci chatovacího serveru. Umožňuje chatování v reálném čase a okamžité přenosy informací. Vytváří připojení k serveru, který běží na portu 8080. Poté otevře spojení a komunikuje se serverem a posílá si informace. Informace ze serveru přijímá data v JSON a zpracuje je. 

