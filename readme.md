# PitStop Manager

System zarządzania zespołem wyścigowym łączący panel operacyjny dla pracowników z publicznym portalem dla kibiców. Umożliwia prowadzenie magazynu części eksploatacyjnych, dokumentowanie napraw pojazdów przez logi serwisowe, zarządzanie flotą pojazdów, kierowcami oraz kalendarzem wydarzeń wyścigowych. Publiczna część aplikacji pozwala kibicom śledzić kalendarz wyścigów, przeglądać profile pojazdów oraz sylwetki kierowców zespołu.

## Wymagania systemowe

* Apache 2.4.58
* PHP 8.2.12
* MariaDB 10.4.32
* Microsoft Visual C++ 2019 Redistributable (VS16 X86 64bit) — wymagany przez PHP 8.2

## Instalacja

1. Zainstaluj XAMPP 8.2.12 ze strony https://www.apachefriends.org
2. Skopiuj folder `PitStop_Manager` do katalogu `C:\xampp\htdocs\`
3. Uruchom XAMPP Control Panel i wystartuj moduły **Apache** oraz **MySQL**
4. Otwórz phpMyAdmin pod adresem `http://localhost/phpmyadmin`
5. Utwórz bazę danych o nazwie `pitstop_db` z kodowaniem `utf8mb4_general_ci`
6. Zaimportuj plik `PitStop_Manager/sql/init.sql` (zakładka Import w phpMyAdmin)
7. Sprawdź plik `config/db.php` — domyślne ustawienia dla XAMPP to host `localhost`, użytkownik `root`, puste hasło
8. Otwórz aplikację pod adresem `http://localhost/PitStop_Manager/public/index.php`

**Uprawnienia do zapisu** — foldery na przesyłane zdjęcia muszą być dostępne do zapisu przez Apache:
* `assets/uploads/drivers/`
* `assets/uploads/events/`
* `assets/uploads/vehicles/`

**Domyślne konta po instalacji:**

| Login | Hasło | Rola |
|---|---|---|
| admin | password | Administrator |
| mechanik | password | Mechanik |

## Autorzy

* **Kacper Papuga**
* *nr albumu: 420514*
* *login: papugak*

---

* **Tymon Korpiela**
* *nr albumu: 424769*
* *login: tymonk*

## Wykorzystane zewnętrzne biblioteki

* Font Awesome 6.0.0 — ikony interfejsu (https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css)

## Narzędzia AI

Adres: https://claude.ai

Prompty użyte przy tworzeniu projektu:

Przeanalizuj zarówno projekt wstępny jak i wymagania projektu. Określ czy projekt jest wystarczająco rozbudowany jak na zespół dwuosobowy. Jeśli nie to podaj propozycje rozwinięcia projektu. Oto wymagania projektu: Wytyczne do Projektu:
•	aplikacja powinna wykorzystywać środowisko: Linux, Apache, PHP, MySQL.
•	aplikacje powinna realizować 4 podstawowe operacje bazodanowe: CRUD
•	w aplikacji powinny być poprawnie zaimplementowane trzy poziomy uwierzytelniania

o	a tym samym aplikacja powinna obsługiwać trzy różne typy użytkowników aplikacyjnych (admin, user, guest). [12%]
•	User powinien mieć dostępne co najmniej 3 różne funkcjonalności, np.:
o	Obsługę własnych postów (tworzenie, aktualizowanie przez edycję, kasowanie z potwierdzeniem) [12%]
o	Zarządzanie profilem użytkownika (edycja danych (imię, nazwisko, mail), edycja hasła) [12%]
o	Obsługa towarów w magazynie (tworzenie, aktualizowanie przez edycję, kasowanie z potwierdzeniem) [12%]
•	Administrator powinien mieć dostępne co najmniej 3 różne funkcjonalności, np.:
o	Obsługę wszystkich postów (tworzenie, aktualizowanie przez edycję, kasowanie z potwierdzeniem) [12%]
o	Zarządzanie użytkownikami (edycja danych (imię, nazwisko, mail), edycja hasła, aktywacja/deaktywacja konta) [12%]
o	Obsługa kategoriami towarów w magazynie (tworzenie, aktualizowanie przez edycję, kasowanie z potwierdzeniem) [12%]

Stwórz wstępną bazę danych dla tego projektu. Ma zawierać encje użytkownicy, pojazdy, kategorie, części, logi, wydarzenia, kierowcy oraz jeśli to konieczne encje łączące. 
Stwórz początkowe strony dla tego projektu. Kieruj się wymaganiami projektowymi. W tym jak i w kolejnych poleceniach przy tworzeniu strony uwzględniaj sekcję php, html oraz oddzielnie css.
Stwórz stronę początkową dla admina. Ma ona uwzględniać dane zespołu np. liczbę aktywnych pracowników, liczbę pojazdów oraz informacje o częściach. Dodaj też sekcję z ostatnio stworzonymi logami serwisowymi.
Zaprojektuj wizualny motyw strony, kolory przewodnie oraz ogólny styl strony. Ten styl będziesz wykorzystywał przy kolejnych poleceniach. 
Stwórz panel służący do obsługi pracowników. Na tej stronie ma być widoczna lista użytkowników wraz z podstawowymi informacjami na ich temat, status oraz przyciski akcji: edycja oraz dezaktywacja/aktywacja. Umieść tu również przycisk który przenosi do strony dodawania użytkowników. Stwórz również stronę  odpowiedzialną za dodawanie użytkowników. Uwzględnij dane: login, email, imię, nazwisko, hasło oraz wybór roli. Na podobnej zasadzie stwórz stronę edycji użytkownika.
Stwórz moduł logowania. Uwzględnij mechanizm sesji użytkownika.
Stwórz panel admina do zarządzania wydarzenia. Ma to wyglądać na podobnej zasadzie co strona z użytkownikami. Uwzględnij stronę główną, stronę do tworzenia wydarzenia oraz stronę do edycji wydarzenia. Uwzględnij dane które występują w encji events w bazie danych. Na stronie głównej z listą wydarzeń, admin ma mieć możliwość ustawienia wyniku wydarzenia, jeśli ma ono status zakończone. Status powinno dać się zmienić z pozycji listy wydarzeń bez wchodzenia w edycję. Natomiast wynik ustawia się w edycji po wcześniejszej zmianie statusu. Jeśli status jest inny niż zakończony to pole wynik jest niewidoczne.
Popraw wizualnie sekcję status w kolumnie z wydarzeniami. Dodaj listę rozwijaną z przyciskiem zatwierdzającym wybór obok. Dodaj też do tabeli kolumnę z której widoczne będą pojazdy przypisane do konkretnego wydarzenia.
Popraw wizualnie obszar odpowiedni za upload zdjęcia na stronie tworzenia wydarzeń. 
Na podobnej zasadzie co wcześniej stwórz moduł magazyn. Tutaj admin ma możliwość tworzyć, usuwać i edytować kategorie.
Stwórz moduł pojazdów w sekcji admina. Do bazy dodaj więcej atrybutów, odpowiadających specyfikacji samochodu sportowego. Uwzględnij tą nową wersje bazy w tworzeniu stron z pojazdami. Znowu stwórz stronę z listą pojazdów, uwzględniając podstawowe dane oraz akcje. Stronę z dodawaniem pojazdu oraz stronę z edycją pojazdy.
Na stronie dodawania i edycji pojazdu użyj tego samego elementu do dodawania zdjęć co w module wydarzeń.
Stwórz moduł kierowców. Kierowcy nie mają własnego konta. To sztywne profile, które służą tylko do wglądu dla kibiców. Stwórz stronę z listą kierowców, z dodawaniem kierowców i edycją kierowców. 
Teraz zajmij się stroną z perspektywy gościa. Stwórz stronę główną z podstawowymi i informacjami/aktualnościami. Ma ona prezentować najświeższe statystyki zespołu. Najbliższe zaplanowane wydarzenia. Przyciski które przenoszą do stron z kalendarzem oraz listą pojazdów we flocie. Dodaj również na dole strony sekcję z przyciskiem logowania dedykowaną mechanikom.
Stwórz stronę z listą wydarzeń. Ma ona zawierać wszystkie wydarzenia, niezależnie od ich statusu. Co ważne w porównaniu do strony admina, tutaj należy zachować estetykę. Wydarzenia mają być przedstawione w formie prostokątnych kafelków ze zdjęciami i podstawowymi informacjami obok. Dodaj możliwość filtrowania wydarzeń po statusie. Stwórz stronę ze szczegółami wydarzeń gdzie widoczne będą wszystkie informacje o wydarzeniach łącznie z przypisanymi pojazdami i biorącymi w nim udział kierowcami. Pojazdy i kierowcy mają mieć widoczne zdjęcia. Po kliknięciu na dany pojazd lub kierowcę użytkownik zostaje przekierowany na stronę ze szczegółami danego kierowcy lub pojazdu. (te strony stworzysz zaraz).
Popraw wizualny rozkład strony ze szczegółami wydarzenia. Lista pojazdów ma być większa i widoczna po lewej stronie. Opis powinien rozciągać się na całą szerokość sekcji. Na górze, pod zdjęciem powinny być widoczne szczegóły wydarzenia – najważniejsze informacje.
Stwórz stronę z listą pojazdów oraz stronę ze szczegółami pojazdów. Pojazdy mają być widoczne w formie kwadratowych kafelków ze zdjęciami oraz podstawowymi informacjami. Lista ma mieć możliwość filtrowania po statusie pojazdu. Strona ze szczegółami ma zawierać wszystkie informacje o pojeździe w tym specyfikacja oraz historia startów pojazdu – wydarzeń w których pojazd brał udział.
Na podobnej zasadzie co moduł pojazdów, stwórz moduł kierowców. Tutaj też zastosuj filtry po statusie. Lista również w formie kafelków i również po kliknięciu użytkownik ma być przeniesiony do strony ze szczegółami. Strona szczegółów kierowcy ma zawierać jego biografię, historie startów i wszystkie inne dane kierowcy.
Popraw strony ze szczegółami pojazdów i kierowców. Przycisk powrotu do poprzedniej strony powinien przenosić do listy pojazdów/kierowców lub do strony ze szczegółami wydarzenia, w zależności od tego na jakiej stronie użytkownik był wcześniej.
W panelu admina stwórz stronę z wydarzeniami. Ma ona działać tak jak w przypadku gościa. Ma ona tylko charakter informacyjny.
Dokonaj zmian w module magazynu, popraw wizualnie kolumnę ilość. Dodaj możliwość filtrowania części po kategoriach.
Stwórz stronę z pojazdami w sekcji mechanika. Strona ta ma wyświetlać informacje o wszystkich pojazdach, ilości aktywnych pojazdów, ilość pojazdów w naprawie oraz wycofanych. Pod tymi statystykami widoczna ma być lista pojazdów z widocznymi najważniejszymi informacjami w tym status oraz liczba logów stworzonych dla danego pojazdu. Nie ma tu możliwości przejścia do szczegółów pojazdów. Strona ta ma charakter informacyjny dla pracownika.
Stworzyłem opis funkcji które posiada ta strona. Popraw ten opis tak aby brzmiał lepiej i zawierał wszystkie funkcje strony. Na koniec przeanalizuj te funkcje pod kątem wymagań prowadzącego. 
Jakie jeszcze informacje potrzebujesz do stworzenia pełnej dokumentacji projektu zgodniej z tymi wymaganiami: Dokumentacja końcowa (na bazie wykonanej aplikacji) w postaci pliku: readme.md (wzór w załączeniu:   readme.md) [10%]
•	opis przeznaczenia aplikacji
•	wymagania sprzętowe, programowe i bezpieczeństwa dla poprawnego działania aplikacji
•	opis instalacji
•	uwierzytelnienie w aplikacji, opis użytkowników, zakres możliwości
•	schemat stron
•	schemat bazy danych
•	opis interfejsu, instrukcja użytkownika
Korzystając ze wszystkich danych które posiadasz, stwórz pełną dokumentację projektu. 
Stwórz pełną instrukcję użytkownika.

Stwórz stronę o logach służącą do obsługi logów serwisowych z perspektywy mechanika. Wymagania projektowe: dostęp do strony ma mieć tylko zalogowany użytkownik z rolą user. Na stronie ma być widoczny formularz dodawania nowego logu oraz lista dotychczasowych wpisów. W formularzu uwzględnij listę rozwijaną z pojazdami pobranymi z bazy oraz pole tekstowe na opis naprawy. ID mechanika ma podstawiać się automatycznie z sesji. Lista logów ma wyświetlać markę i model pojazdu, login mechanika, treść wpisu oraz datę. Przy tworzeniu strony uwzględniaj sekcję php, html oraz oddzielnie css. 






Na podobnej zasadzie stwórz stronę zarządzenie kątem  służącą do zarządzania profilem użytkownika. Strona ma być dostępna dla zalogowanych pracowników (admin oraz user). Ma zawierać formularz z edycją danych takich jak: imię, nazwisko oraz email (pola mają być domyślnie wypełnione aktualnymi danymi z bazy). Dodaj również osobną sekcję odpowiedzialną za edycję hasła, zawierającą pola: aktualne hasło, nowe hasło oraz powtórz nowe hasło. 


Stwórz stronę logi admin  służącą do nadzoru nad raportami z perspektywy administratora, bazując na dostarczonym designie (ciemne tło, pomarańczowo-czerwone akcenty, zaokrąglona karta główna). Wymagania projektowe: dostęp do strony ma mieć tylko zalogowany użytkownik z rolą admin. Administrator również ma mieć opcję edycji, przeglądania oraz usuwania logów. Lista ma mieć opcję filtrowania logów względem wybranego pojazdu. 
Na podobnej zasadzie stwórz stronę edycje l0gów przez mechanika służącą do edycji logu serwisowego z perspektywy mechanika. Wymagania projektowe: dostęp do strony ma mieć tylko zalogowany użytkownik z rolą user. System ma pozwalać na edycję wyłącznie tych logów, których autorem jest zalogowany mechanik. Również w opcji edycji powinna być opcja dodanie i usuwanie kolejnych części. 


Stwórz również podobną stronę do edycj logów dla mechanika.

Popraw struktóre wyświetlanej listy w module logi mechanika. Lista powinna tylko wyświetlać date, mechanika, tytuł, pojazd , akcję. 

Tu maszy podane wysztskie poprawki które potrzebuję dla logów:
1. (User) Poprawa listy logów - Mechanik może widzieć wszystkie logi (nie tylko utworzone przez niego)
2. (User) Dodanie strony ze szczegółami logów (tak żeby był wgląd do opisu)
3. (admin) Dodanie możliwości edycji logów
4. (admin) Dodanie strony ze szczegółami logów (tak jak u usera)
5. (admin) Wizualna poprawa tabeli ze wszystkimi logami. Usunąć z tabeli kolumnę z użytymi częściami. Kolumnę "Pojazd / Tytuł prac" rozdzielić na dwie oddzielne kolumny.
Właściwa struktura tabeli z logami w widoku admin: Data | Mechanik | Tytuł | Pojazd | Akcje
6. (user/admin) Dodatkowy pomysł aby admin i user miał możliwość zobaczyć listę logów dla konkretnego pojazdu. Albo na stronie pojazdy wchodząc w pojazd albo filtrami na liście logów. zrób tak jak jest w ty opisie 


Rozdziel zmianę hasła od edycji uztykownika. W panelu edycji zrób przycisk przekierowywyujący do strony z opcja zmiany hasła. W podstronie zmiany hasła powinny znaleźć się następujące pola tekstowe: obecne hasło, nowe hasło i potwierdź nowe hasło. 
