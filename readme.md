# PitStop Manager – Dokumentacja Aplikacji

**Autorzy:** Kacper Papuga, Tymon Korpiela  
**Przedmiot:** Otwarte bazy danych  
**Wersja aplikacji:** 1.0

---

## 1. Opis przeznaczenia aplikacji

PitStop Manager to system zarządzania zespołem wyścigowym, który łączy funkcje operacyjne dla pracowników zespołu z publicznym widokiem dla kibiców i sympatyków.

System realizuje dwa główne cele:

**Panel operacyjny** – narzędzie pracy dla mechaników i administratora zespołu. Umożliwia prowadzenie magazynu części eksploatacyjnych, dokumentowanie napraw pojazdów przez logi serwisowe, zarządzanie flotą pojazdów, kierowcami oraz kalendarzem wydarzeń wyścigowych.

**Portal kibica** – publiczna część aplikacji dostępna bez logowania. Kibice mogą śledzić kalendarz wyścigów z wynikami, przeglądać profile pojazdów zespołu wraz z ich specyfikacją techniczną oraz zapoznać się z sylwetkami kierowców startujących dla zespołu.

---

## 2. Wymagania sprzętowe, programowe i bezpieczeństwa

### Wymagania sprzętowe (serwer)

| Parametr | Minimum |
|---|---|
| RAM | 64 MB |
| Miejsce na dysku | 750 MB (środowisko XAMPP) + miejsce na pliki aplikacji i przesyłane zdjęcia |
| System operacyjny | Windows 7 / 8 / 10 / 11 (64-bit) |

### Wymagania programowe

| Oprogramowanie | Wersja |
|---|---|
| XAMPP | 8.2.12 |
| Apache | 2.4.58 |
| PHP | 8.2.12 |
| MariaDB | 10.4.32 |
| phpMyAdmin | 5.2.1 |

> **Uwaga:** PHP 8.2.12 wymaga zainstalowanego pakietu Microsoft Visual C++ 2019 Redistributable (VS16 X86 64bit). Pobierz z: https://www.microsoft.com/en-us/download/

### Wymagania po stronie klienta (przeglądarka)

Aplikacja działa poprawnie w każdej nowoczesnej przeglądarce internetowej obsługującej HTML5 i CSS3:

- Google Chrome 90+
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+

### Bezpieczeństwo

- Hasła użytkowników przechowywane są w bazie jako hasze bcrypt (funkcja `password_hash()` z algorytmem `PASSWORD_BCRYPT`)
- Wszystkie dane wyświetlane ze zmiennych PHP filtrowane są przez `htmlspecialchars()` zapobiegając atakom XSS
- Dostęp do paneli administracyjnego i użytkownika chroniony jest weryfikacją sesji i roli na początku każdego pliku PHP — nieautoryzowane próby dostępu przekierowują do strony logowania
- Pliki konfiguracyjne (`config/db.php`) znajdują się poza folderem `public`
- Przesyłane pliki graficzne weryfikowane są pod kątem typu MIME i rozmiaru (maksymalnie 5 MB, dozwolone formaty: JPG, PNG, WEBP)
- Zapytania do bazy danych wykonywane są wyłącznie przez PDO z parametrami bindowanymi (prepared statements), co zabezpiecza przed SQL Injection

---

## 3. Opis instalacji

### Krok 1 – Instalacja XAMPP

Pobierz i zainstaluj XAMPP w wersji 8.2.12 ze strony https://www.apachefriends.org.  
Upewnij się że zainstalowany jest pakiet Microsoft Visual C++ 2019 Redistributable.

### Krok 2 – Skopiowanie plików aplikacji

Skopiuj folder `PitStop_Manager` do katalogu:

```
C:\xampp\htdocs\
```

Docelowa struktura katalogów powinna wyglądać następująco:

```
C:\xampp\htdocs\PitStop_Manager\
├── admin\          (panel administratora)
├── assets\         (zasoby statyczne)
│   ├── img\        (obrazy dekoracyjne strony)
│   ├── uploads\    (zdjęcia przesyłane przez użytkowników)
│   │   ├── drivers\
│   │   ├── events\
│   │   └── vehicles\
│   ├── style.css
│   └── script.js
├── config\         (konfiguracja bazy danych)
├── includes\       (współdzielone pliki nagłówka i stopki)
├── public\         (strony dostępne publicznie)
├── sql\            (skrypt inicjalizujący bazę danych)
└── user\           (panel mechanika)
```

### Krok 3 – Uruchomienie XAMPP

Otwórz XAMPP Control Panel i uruchom moduły **Apache** oraz **MySQL**.

### Krok 4 – Utworzenie bazy danych

1. Otwórz przeglądarkę i przejdź pod adres: `http://localhost/phpmyadmin`
2. Utwórz nową bazę danych o nazwie `pitstop_db` z kodowaniem `utf8mb4_general_ci`
3. Wybierz bazę `pitstop_db`, przejdź do zakładki **Import**
4. Wybierz plik `PitStop_Manager/sql/init.sql` i kliknij **Importuj**

Plik `init.sql` tworzy wszystkie tabele oraz wstawia domyślne konta użytkowników (administratora i mechanika).

### Krok 5 – Konfiguracja połączenia z bazą

Otwórz plik `config/db.php` i sprawdź czy dane połączenia są poprawne:

```php
$host = 'localhost';
$db   = 'pitstop_db';
$user = 'root';
$pass = '';  // domyślnie puste w XAMPP
```

### Krok 6 – Uruchomienie aplikacji

Otwórz przeglądarkę i przejdź pod adres:

```
http://localhost/PitStop_Manager/public/index.php
```

---

## 4. Uwierzytelnienie w aplikacji – opis użytkowników i zakres możliwości

Aplikacja obsługuje trzy poziomy dostępu. Po zalogowaniu system automatycznie przekierowuje użytkownika do odpowiedniego panelu na podstawie przypisanej roli. Wylogowanie dostępne jest przez przycisk w prawym górnym rogu nawigacji i niszczy sesję użytkownika.

### Domyślne dane logowania (po instalacji)

| Login | Hasło | Rola |
|---|---|---|
| admin | password | Administrator |
| mechanik | password | Mechanik |

> **Zalecenie bezpieczeństwa:** Zmień hasła domyślnych kont bezpośrednio po pierwszym uruchomieniu aplikacji.

---

### A. Gość (Kibic)

Rola publiczna, niewymagająca logowania. Gość ma dostęp wyłącznie do informacji przeznaczonych dla kibiców i sympatyków zespołu.

**Dostępne funkcje:**

- **Strona główna** (`public/index.php`) – widok aktualności zespołu: trzy najbliższe zaplanowane wyścigi z datami i torami, tabela ostatnich pięciu wyników, statystyki sezonu (łączna liczba wydarzeń, ukończonych wyścigów, aktywnych pojazdów). Dla niezalogowanych gości wyświetlana jest sekcja zachęcająca do logowania.

- **Kalendarz wydarzeń** (`public/events.php`) – pełna lista wydarzeń wyścigowych z filtrowaniem po statusie (zaplanowane / zakończone / anulowane). Każda karta wydarzenia pokazuje zdjęcie, datę, tor, status i skrót opisu. Szczegóły wydarzenia (`public/event_detail.php`) zawierają: zdjęcie w tle z tytułem, datę, tor, status, wynik (jeśli zakończone), opis, miniaturki pojazdów z linkami do ich profili oraz zdjęcia kierowców z linkami do ich profili.

- **Pojazdy zespołu** (`public/vehicles.php`) – lista pojazdów z filtrowaniem po statusie (aktywny / w naprawie / wycofany). Profil pojazdu (`public/vehicle_detail.php`) zawiera: duże zdjęcie z nakładką tytułową, pełną specyfikację techniczną (silnik, napęd, masa, kategoria wyścigowa, rok produkcji i debiutu) oraz historię startów w wydarzeniach klikalną do ich szczegółów.

- **Kierowcy zespołu** (`public/drivers.php`) – lista kierowców z filtrowaniem (aktywni / byli kierowcy). Profil kierowcy (`public/driver_detail.php`) zawiera: duże zdjęcie, dane osobowe (narodowość, wiek wyliczany z daty urodzenia, rok dołączenia), biografię, statystyki (liczba startów, ukończonych wyścigów, lat w zespole) oraz historię startów z wynikami.

- **Logowanie** (`public/login.php`) – formularz logowania dla członków zespołu. Konta tworzone są wyłącznie przez administratora.

---

### B. Użytkownik (Mechanik)

Pracownik operacyjny z kontem w systemie. Posiada dostęp do panelu użytkownika oraz wszystkich funkcji dostępnych dla gościa.

**Dostępne funkcje:**

- **Panel główny** (`user/dashboard.php`) – widok powitalny z imieniem mechanika. Wyświetla: liczbę własnych logów serwisowych, liczbę części poniżej stanu minimalnego (z alertem wizualnym), aktualny czas, tabelę ostatnich pięciu własnych wpisów serwisowych oraz tabelę części wymagających uzupełnienia.

- **Magazyn części** (`user/parts_inventory.php`) – pełna obsługa magazynu:
  - Lista wszystkich części z filtrowaniem po kategoriach zdefiniowanych przez administratora
  - Wizualny alert: ilość części poniżej minimum wyświetlana czerwonym kolorem jako badge
  - Dodawanie części (`user/add_part.php`): nazwa, kategoria, numer seryjny, status (nowy / używany / uszkodzony), cena, ilość, minimalny stan magazynowy
  - Edycja (`user/edit_part.php`) i usuwanie części z potwierdzeniem w oknie dialogowym

- **Logi serwisowe** (`user/logs.php`) – dokumentacja napraw pojazdów:
  - Lista własnych logów z możliwością filtrowania po pojazdach
  - Tworzenie wpisu (`user/add_log.php`): tytuł, pojazd, użyte części z magazynu (ilość automatycznie odejmowana po zapisaniu), opis prac, data
  - Edycja (`user/edit_log.php`) wyłącznie własnych wpisów

- **Profil użytkownika** (`user/profile.php`) – edycja własnych danych: imię, nazwisko, adres e-mail. Zmiana hasła dostępna przez oddzielną stronę (`user/change_password.php`).

---

### C. Administrator (Team Principal)

Najwyższy poziom uprawnień. Dostęp do pełnego panelu administracyjnego oraz wszystkich funkcji niższych ról.

**Dostępne funkcje:**

- **Panel główny** (`admin/dashboard.php`) – widok statystyk systemu: liczba aktywnych pracowników, liczba pojazdów w flocie, liczba części z niskim stanem (z czerwonym alertem jeśli dotyczy). Tabela ostatnich pięciu logów serwisowych ze wszystkich kont z linkiem do pełnej listy.

- **Zarządzanie pracownikami** (`admin/users.php`) – pełna lista kont z datą utworzenia, rolą i statusem. Tworzenie konta (`admin/add_user.php`): login, imię, nazwisko, e-mail, hasło, rola. Edycja danych i hasła (`admin/edit_user.php`). Aktywacja i deaktywacja kont przyciskiem w tabeli (z potwierdzeniem). Administrator nie może deaktywować własnego konta ani zmienić własnej roli.

- **Zarządzanie kierowcami** (`admin/drivers.php`) – lista profili kierowców z liczbą przypisanych wydarzeń i statusem. Tworzenie (`admin/add_driver.php`) i edycja (`admin/edit_driver.php`): imię, nazwisko, numer startowy, narodowość, data urodzenia, rok dołączenia, zdjęcie (z podglądem i opcją usunięcia), biografia, status aktywności. Usunięcie możliwe tylko jeśli kierowca nie ma przypisanych wydarzeń.

- **Zarządzanie flotą** (`admin/vehicles.php`) – lista pojazdów z liczbą przypisanych logów i statusem. Tworzenie (`admin/add_vehicle.php`) i edycja (`admin/edit_vehicle.php`): marka, model, numer startowy, rok produkcji, VIN, status (aktywny / w naprawie / wycofany), silnik, napęd, masa, kategoria wyścigowa, rok debiutu, zdjęcie, opis publiczny. Pojazdu nie można usunąć jeśli posiada przypisane logi serwisowe.

- **Zarządzanie kategoriami magazynu** (`admin/categories.php`) – lista kategorii z liczbą przypisanych części. Tworzenie (`admin/add_category.php`) i edycja (`admin/edit_category.php`): nazwa i opis. Kategoria z przypisanymi częściami ma zablokowany przycisk usuwania.

- **Zarządzanie kalendarzem wyścigowym** (`admin/events_manage.php`) – lista wszystkich wydarzeń z podglądem przypisanych pojazdów i aktualnym statusem. Możliwość szybkiej zmiany statusu bezpośrednio w tabeli. Tworzenie (`admin/add_event.php`) i edycja (`admin/edit_event.php`): tytuł, tor, data, status, zdjęcie (drag & drop z podglądem), przypisane pojazdy i kierowcy (dropdown z checkboxami), opis, wynik (pole dostępne tylko dla statusu "zakończone"). Usuwanie z potwierdzeniem.

- **Nadzór nad logami serwisowymi** (`admin/logs.php`) – widok wszystkich logów ze wszystkich kont z filtrowaniem po pojazdach. Podgląd szczegółów naprawy, edycja każdego wpisu (`admin/edit_log.php`) i usuwanie z potwierdzeniem. Administrator nie tworzy logów — jest to wyłączna funkcja mechaników.

---

## 5. Schemat stron

```
PitStop Manager
│
├── public/                          [dostęp: wszyscy]
│   ├── index.php                    Strona główna (wyniki, nadchodzące wyścigi, statystyki)
│   ├── login.php                    Logowanie
│   ├── logout.php                   Wylogowanie (niszczy sesję i przekierowuje)
│   ├── events.php                   Kalendarz wydarzeń z filtrowaniem
│   ├── event_detail.php             Szczegóły wydarzenia (pojazdy, kierowcy, wynik)
│   ├── vehicles.php                 Lista pojazdów z filtrowaniem
│   ├── vehicle_detail.php           Profil pojazdu (specyfikacja, historia startów)
│   ├── drivers.php                  Lista kierowców z filtrowaniem
│   └── driver_detail.php            Profil kierowcy (biografia, statystyki, starty)
│
├── user/                            [dostęp: zalogowany mechanik]
│   ├── dashboard.php                Panel główny (statystyki, ostatnie logi, alerty)
│   ├── profile.php                  Edycja danych osobowych
│   ├── change_password.php          Zmiana hasła
│   ├── parts_inventory.php          Lista części z filtrowaniem i alertami
│   ├── add_part.php                 Dodaj część do magazynu
│   ├── edit_part.php                Edytuj część
│   ├── logs.php                     Lista własnych logów serwisowych
│   ├── add_log.php                  Utwórz log serwisowy
│   └── edit_log.php                 Edytuj własny log
│
└── admin/                           [dostęp: zalogowany administrator]
    ├── dashboard.php                Panel główny (statystyki systemu, ostatnie logi)
    ├── users.php                    Lista pracowników
    ├── add_user.php                 Dodaj pracownika
    ├── edit_user.php                Edytuj dane i hasło pracownika
    ├── drivers.php                  Lista kierowców
    ├── add_driver.php               Dodaj profil kierowcy
    ├── edit_driver.php              Edytuj profil kierowcy
    ├── vehicles.php                 Lista pojazdów
    ├── add_vehicle.php              Dodaj pojazd
    ├── edit_vehicle.php             Edytuj pojazd
    ├── categories.php               Lista kategorii magazynu
    ├── add_category.php             Dodaj kategorię
    ├── edit_category.php            Edytuj kategorię
    ├── events_manage.php            Lista wydarzeń (z szybką zmianą statusu)
    ├── add_event.php                Dodaj wydarzenie
    ├── edit_event.php               Edytuj wydarzenie
    ├── logs.php                     Lista wszystkich logów serwisowych
    └── edit_log.php                 Edytuj log serwisowy
```

---

## 6. Schemat bazy danych

Baza danych: `pitstop_db` | Kodowanie: `utf8mb4_general_ci`

### Tabela: `users`
Konta użytkowników systemu.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator użytkownika |
| username | VARCHAR(50) UNIQUE NOT NULL | Login użytkownika |
| password | VARCHAR(255) NOT NULL | Hash hasła (bcrypt) |
| email | VARCHAR(100) NOT NULL | Adres e-mail |
| first_name | VARCHAR(50) | Imię |
| last_name | VARCHAR(50) | Nazwisko |
| role | ENUM('admin','user') | Rola w systemie |
| is_active | TINYINT(1) | Status konta (1=aktywne, 0=nieaktywne) |
| created_date | TIMESTAMP | Data utworzenia konta |

### Tabela: `vehicles`
Pojazdy należące do zespołu.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator pojazdu |
| number | VARCHAR(10) | Numer startowy |
| brand | VARCHAR(50) NOT NULL | Marka |
| model | VARCHAR(50) NOT NULL | Model |
| vin | VARCHAR(50) UNIQUE | Numer VIN |
| year | INT | Rok produkcji |
| status | ENUM('aktywny','w_naprawie','wycofany') | Status pojazdu |
| photo | VARCHAR(255) | Ścieżka do zdjęcia |
| engine | VARCHAR(100) | Opis silnika |
| weight | INT | Masa w kg |
| drive_type | VARCHAR(50) | Typ napędu (AWD/RWD/FWD) |
| category | VARCHAR(100) | Kategoria wyścigowa |
| debut_year | INT | Rok debiutu w zespole |
| description | TEXT | Opis publiczny dla kibiców |

### Tabela: `categories`
Kategorie części magazynowych.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator kategorii |
| name | VARCHAR(100) NOT NULL | Nazwa kategorii |
| description | TEXT | Opis kategorii |

### Tabela: `parts`
Części w magazynie.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator części |
| category_id | INT FK → categories.id | Kategoria (SET NULL przy usunięciu kategorii) |
| name | VARCHAR(100) NOT NULL | Nazwa części |
| serial_number | VARCHAR(50) | Numer seryjny |
| price | DECIMAL(10,2) NOT NULL | Cena jednostkowa |
| quantity | INT | Aktualna ilość w magazynie |
| min_quantity | INT | Minimalny stan (próg alertu) |
| status | ENUM('nowy','używany','uszkodzony') | Stan części |

### Tabela: `logs`
Logi serwisowe – dokumentacja napraw pojazdów.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator logu |
| user_id | INT FK → users.id | Autor logu (SET NULL przy usunięciu użytkownika) |
| vehicle_id | INT FK → vehicles.id | Naprawiany pojazd (CASCADE przy usunięciu pojazdu) |
| title | VARCHAR(200) NOT NULL | Tytuł prac |
| content | TEXT NOT NULL | Opis wykonanych prac |
| created_at | TIMESTAMP | Data utworzenia |
| updated_at | TIMESTAMP | Data ostatniej modyfikacji |

### Tabela: `log_parts`
Powiązanie logów z użytymi częściami (relacja wiele-do-wielu).

| Kolumna | Typ | Opis |
|---|---|---|
| log_id | INT FK → logs.id | Identyfikator logu (CASCADE) |
| part_id | INT FK → parts.id | Identyfikator części (CASCADE) |
| quantity_used | INT | Ilość użyta podczas naprawy |

### Tabela: `events`
Wydarzenia wyścigowe.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator wydarzenia |
| title | VARCHAR(200) NOT NULL | Tytuł wydarzenia |
| event_date | DATE NOT NULL | Data wydarzenia |
| track_name | VARCHAR(100) | Nazwa toru |
| status | ENUM('zaplanowane','zakończone','anulowane') | Status |
| result | VARCHAR(100) | Wynik zespołu |
| description | TEXT | Opis wydarzenia |
| photo | VARCHAR(255) | Ścieżka do zdjęcia |

### Tabela: `event_vehicles`
Powiązanie wydarzeń z pojazdami (relacja wiele-do-wielu).

| Kolumna | Typ | Opis |
|---|---|---|
| event_id | INT FK → events.id | Identyfikator wydarzenia (CASCADE) |
| vehicle_id | INT FK → vehicles.id | Identyfikator pojazdu (CASCADE) |

### Tabela: `drivers`
Profile kierowców wyścigowych.

| Kolumna | Typ | Opis |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identyfikator kierowcy |
| first_name | VARCHAR(50) NOT NULL | Imię |
| last_name | VARCHAR(50) NOT NULL | Nazwisko |
| number | INT | Numer startowy kierowcy |
| nationality | VARCHAR(50) | Narodowość |
| date_of_birth | DATE | Data urodzenia |
| photo | VARCHAR(255) | Ścieżka do zdjęcia |
| bio | TEXT | Biografia |
| is_active | TINYINT(1) | Status w zespole (1=aktywny) |
| joined_year | INT | Rok dołączenia do zespołu |
| created_at | TIMESTAMP | Data dodania profilu |

### Tabela: `event_drivers`
Powiązanie wydarzeń z kierowcami (relacja wiele-do-wielu).

| Kolumna | Typ | Opis |
|---|---|---|
| event_id | INT FK → events.id | Identyfikator wydarzenia (CASCADE) |
| driver_id | INT FK → drivers.id | Identyfikator kierowcy (CASCADE) |

### Diagram relacji

![Diagram ERD](sql/Diagram%20ERD%20bazy%20danych.png)

## 7. Opis interfejsu i instrukcja użytkownika

### Ogólne zasady interfejsu

Aplikacja utrzymana jest w ciemnej kolorystyce (dark mode) z pomarańczowym kolorem akcentującym (#ff5e00). Górna nawigacja dostosowuje się automatycznie do roli zalogowanego użytkownika:

- **Gość:** Strona główna, Kalendarz, Pojazdy, Kierowcy, przycisk Logowanie
- **Mechanik:** Strona główna, Kalendarz, Pojazdy, Kierowcy, Magazyn, Logi, Profil, przycisk Wyloguj
- **Administrator:** Dashboard, Pracownicy, Kierowcy, Pojazdy, Magazyn, Kalendarz, Logi, przycisk Wyloguj

Komunikaty o powodzeniu operacji wyświetlane są w zielonej ramce, błędy w czerwonej — zawsze na górze strony bezpośrednio po wykonaniu akcji. Operacje usuwania wymagają potwierdzenia w oknie dialogowym przeglądarki.

Przyciski akcji w tabelach:
- **Pomarańczowy ołówek** – edycja rekordu
- **Czerwony kosz** – usunięcie rekordu
- **Zielona/czerwona ikona człowieka** – aktywacja/deaktywacja
- **Szara kłódka** – operacja niedostępna (np. usunięcie rekordu z powiązaniami)

---

### Instrukcja dla gościa (kibica)

**Przeglądanie kalendarza:**
1. Kliknij zakładkę **Kalendarz** w górnej nawigacji
2. Użyj przycisków filtrowania aby zawęzić listę do wybranego statusu
3. Kliknij kartę wydarzenia aby zobaczyć szczegóły — zdjęcie, opis, pojazdy i kierowców
4. W szczegółach wydarzenia kliknij miniaturę pojazdu lub zdjęcie kierowcy aby przejść do ich profilu
5. Przycisk **Powrót do kalendarza** wraca do listy wydarzeń

**Przeglądanie pojazdów:**
1. Kliknij zakładkę **Pojazdy** w górnej nawigacji
2. Filtruj po statusie używając przycisków nad listą
3. Kliknij kartę pojazdu aby zobaczyć pełną specyfikację techniczną i historię startów
4. Wiersze w historii startów są klikalne i prowadzą do szczegółów danego wydarzenia

**Przeglądanie kierowców:**
1. Kliknij zakładkę **Kierowcy** w górnej nawigacji
2. Przełączaj między aktywnymi i byłymi kierowcami przyciskami filtrowania
3. Kliknij kartę kierowcy aby zobaczyć pełny profil z biografią, statystykami i historią wyścigów

---

### Instrukcja dla mechanika (użytkownika)

**Logowanie:**
1. Kliknij **Logowanie** w prawym górnym rogu
2. Wpisz login i hasło otrzymane od administratora
3. Po zalogowaniu system przekieruje do panelu mechanika

**Zarządzanie magazynem:**
1. Kliknij zakładkę **Magazyn** w nawigacji
2. Użyj przycisków kategorii nad tabelą aby filtrować części
3. Części wyświetlone czerwonym kolorem ilości osiągnęły minimalny stan — należy je uzupełnić
4. Kliknij **Dodaj część** (przycisk na dole listy) aby dodać nową pozycję do magazynu
5. Przy każdej części dostępne są przyciski edycji i usunięcia
6. Usunięcie wymaga potwierdzenia w oknie dialogowym

**Tworzenie logu serwisowego:**
1. Kliknij zakładkę **Logi** w nawigacji
2. Kliknij **Dodaj log** na dole listy
3. Podaj tytuł prac i wybierz pojazd z listy rozwijanej
4. Opisz wykonane prace w polu opisu
5. Wybierz z listy części użyte podczas naprawy — ich ilość zostanie automatycznie odjęta z magazynu po zapisaniu
6. Kliknij **Zapisz log**

**Edycja logu:**
1. Na liście logów kliknij ikonę ołówka przy wybranym wpisie
2. Edytować można wyłącznie własne wpisy — przy cudzych logach przycisk edycji jest niedostępny
3. Po zmianach kliknij **Zapisz zmiany**

**Edycja profilu:**
1. Kliknij zakładkę **Profil** w nawigacji
2. Zmień imię, nazwisko lub adres e-mail i kliknij **Zapisz dane**
3. Aby zmienić hasło kliknij przycisk **Zmień hasło** który przenosi do osobnego formularza

---

### Instrukcja dla administratora

**Dodawanie nowego pracownika:**
1. Przejdź do zakładki **Pracownicy** i kliknij **Dodaj pracownika**
2. Wypełnij formularz: login (musi być unikalny), imię, nazwisko, e-mail, hasło tymczasowe, rola
3. Kliknij **Utwórz pracownika** — konto jest od razu aktywne
4. Poinformuj pracownika o danych logowania i poproś o zmianę hasła przy pierwszym logowaniu

**Dezaktywacja konta:**
1. Na liście pracowników kliknij ikonę dezaktywacji przy wybranej osobie
2. Potwierdź operację w oknie dialogowym
3. Konto zostaje dezaktywowane — użytkownik nie może się zalogować, ale jego dane i logi pozostają w systemie
4. Ponowna aktywacja działa tym samym przyciskiem

**Dodawanie wydarzenia wyścigowego:**
1. Przejdź do zakładki **Kalendarz** i kliknij **Dodaj wydarzenie**
2. Wypełnij tytuł, datę i nazwę toru
3. Wybierz status — po wyborze "Zakończone" pojawia się pole na wynik
4. Opcjonalnie dodaj zdjęcie — przeciągnij plik na obszar uploadu lub kliknij go aby wybrać z dysku
5. Z rozwijanej listy z checkboxami wybierz pojazdy i kierowców przypisanych do tego wydarzenia
6. Kliknij **Dodaj wydarzenie**

**Szybka zmiana statusu wydarzenia:**
1. Na liście wydarzeń (`events_manage.php`) każdy wiersz zawiera rozwijane menu ze statusem
2. Zmień status bezpośrednio w tabeli i kliknij zielony przycisk z ptaszkiem obok
3. Nie ma potrzeby wchodzenia do pełnego formularza edycji

**Zarządzanie logami serwisowymi:**
1. Przejdź do zakładki **Logi**
2. Filtruj logi po pojazdach używając przycisków nad tabelą
3. Kliknij ikonę edycji aby poprawić dowolny wpis — admin może edytować logi wszystkich mechaników
4. Kliknij ikonę kosza aby usunąć wpis (wymagane potwierdzenie)

**Zarządzanie kategoriami magazynu:**
1. Przejdź do zakładki **Magazyn** w nawigacji admina
2. Lista pokazuje kategorie wraz z liczbą przypisanych części w każdej
3. Kategorie z przypisanymi częściami mają zablokowany przycisk usuwania (ikona kłódki z podpowiedzią)
4. Kliknij **Dodaj kategorię** aby utworzyć nową — wymagana jest tylko nazwa, opis jest opcjonalny

---

## 8. Znane ograniczenia

- Aplikacja zaprojektowana jest do działania w środowisku lokalnym (XAMPP). Wdrożenie na serwer produkcyjny wymaga dodatkowej konfiguracji uprawnień do folderów `assets/uploads/`
- Folder `assets/uploads/` oraz jego podfoldery (`drivers/`, `events/`, `vehicles/`) muszą mieć uprawnienia do zapisu przez proces serwera Apache
- Po świeżej instalacji baza danych zawiera tylko dwa domyślne konta — wszystkie pozostałe dane (pojazdy, kierowcy, wydarzenia, części) należy wprowadzić ręcznie przez panel administratora
- Folder `assets/img/` przeznaczony jest na statyczne zasoby graficzne strony (ikony, grafiki dekoracyjne) — w obecnej wersji aplikacji nie zawiera plików
