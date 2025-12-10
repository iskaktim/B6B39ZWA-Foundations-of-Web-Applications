# B6B39ZWA - Základy webových aplikací

Zimní Semestr 2025 - Finální Projekt pro B6B39ZWA Základy webových aplikací

## **Diskusní Fórum**

Toto je webová diskusní aplikace, která umožňuje uživatelům registrovat se, přihlašovat se, vytvářet příspěvky, nahrávat obrázky, přidávat komentáře a spravovat svůj profil.\
Systém podporuje běžné uživatele, administrátory a roli owner s rozšířenými oprávněními.

https://zwa.toad.cz/~iskaktim/

## **Features**

### **Authentication & Authorization**

* Registrace: Uživatel se může zaregistrovat pomocí unikátního uživatelského jména a e-mailu. Potvrzení hesla je povinné.
* Přihlášení: Uživatel se autentizuje pomocí uživatelského jména a hesla.
* **Role:**
  * **Guest:** Může prohlížet příspěvky a komentáře, ale nemůže vytvářet obsah ani přistupovat na omezené stránky.
  * **User:** Může vytvářet, upravovat a mazat své příspěvky a komentáře a spravovat informace ve svém profilu.
  * **Admin:** Může mazat jakýkoli příspěvek nebo komentář a povyšovat uživatele na admina.
  * **Owner:** Má úplný přístup k systému, může mazat uživatele a přidělovat či odebírat admin roli.

### **Navigation**

* Přihlášení uživatelé mají přístup k: Profile, Forum, My Posts, Add a Post a Logout. (Admin Panel je dostupný pouze pro admin a owner role.)
* Nepřihlášení uživatelé mají přístup k: Forum, Login a Registration.

Navigace se dynamicky mění podle toho, zda je uživatel přihlášen a jakou má roli.

### **User Profile**

* Uživatel může zobrazit své základní informace, jako je uživatelské jméno, e-mail, role, avatar a datum vytvoření účtu.
* Profil umožňuje upravit uživatelské jméno, e-mail, heslo i avatar.
* Avatar lze nahrát nebo odstranit; pokud není nastaven, použije se výchozí obrázek.


### **Post Management**

* Přihlášený uživatel může vytvořit nový příspěvek s názvem, obsahem a volitelným obrázkem.
* Autor může upravit svůj příspěvek, včetně změny obrázku nebo jeho odstranění.
* Autor může smazat svůj vlastní příspěvek; admin a owner mohou smazat jakýkoli příspěvek.
* Příspěvky zobrazují název, obsah, autora, obrázek (pokud je přiložen), datum vytvoření a datum poslední úpravy.

### **Comments**

* Komentáře jsou viditelné pro všechny uživatele.
* Přihlášení uživatelé mohou přidávat komentáře.
* Autoři mohou upravovat a mazat své komentáře.
* Admin a owner mohou smazat jakýkoli komentář.

### **Admin Panel**

* Admin Panel je dostupný pouze pro role admin a owner.
* Panel zobrazuje všechny registrované uživatele včetně jejich uživatelského jména, e-mailu, role a počtu příspěvků.
* Owner může povyšovat nebo ponižovat adminy a mazat jakéhokoli uživatele.
* Admin může povyšovat uživatele na admina, ale nemůže admin roli nikomu odebrat.
* Obě role mohou mazat uživatele (nikdo nemůže mazat ownera).

### **Pagination**

* Paginace se používá na stránkách Forum, My Posts a Comments.
* Nová stránka se vytvoří automaticky v případě, že aktuální obsah překročí pět položek (příspěvků nebo komentářů).

### **File Uploads**

* Uživatelé mohou nahrávat avatary a obrázky k příspěvkům.
* Server kontroluje typ souboru a ukládá nahrané soubory do vyhrazených složek.
* K dispozici je výchozí avatar.


### **Access Control**

* Nepřihlášení uživatelé jsou při pokusu o přístup na chráněné stránky přesměrováni na login.html.
* UI prvky (např. akční tlačítka) jsou skryty, pokud uživatel nemá oprávnění danou akci provést.
* Middleware zajišťuje kontrolu oprávnění na straně serveru.

## **Files Overview**

### **Architecture**

* Projekt využívá architekturu MVC (Model–View–Controller).
* Modely zpracovávají databázové operace.
* View část tvoří HTML, CSS a JavaScript soubory na straně klienta.
* Controllery zpracovávají požadavky a vracejí odpovědi ve formátu JSON.

### **Project Structure**

### **index.html**

Hlavní vstupní stránka aplikace, která načítá fórum, spouští JavaScript logiku a zobrazuje nejnovější příspěvky.

### **client/**

Klientská část aplikace, která vykresluje uživatelské rozhraní, odesílá API požadavky a dynamicky aktualizuje obsah.

Obsah:

* **html pages**
  * **login.html** – stránka pro přihlášení uživatele.
  * **register.html** – stránka pro vytvoření nového účtu.
  * **profile.html** – stránka pro zobrazení a úpravu profilu.
  * **my_posts.html** – stránka s příspěvky přihlášeného uživatele.
  * **admin.html** – panel administrátora se seznamem uživatelů.
  * **detail_post.html** – detailní zobrazení jednoho příspěvku a komentářů.
  * **add_post.html** – stránka pro vytvoření nového příspěvku.
  * **dashboard.html** – přesměrovává nepřihlášené uživatele na Login/Register.
* **css/** – globální a specifické styly.
* **js/** – skripty pro autentizaci, příspěvky, komentáře, paginaci a další UI funkce.
* **uploads/** – nahrané avatary a obrázky příspěvků.
* **images/** – statické obrázky, například výchozí avatar.

### **server/**

Serverová část aplikace, která zpracovává API požadavky, autentizaci, autorizaci a práci s databází.

* **controllers/**
  * **auth_middleware.php** – kontrola přihlášení, admin a owner oprávnění.
  * **comment_controller.php** – operace s komentáři a paginace.
  * **post_controller.php** – operace s příspěvky a paginace.
  * **user_controller.php** – registrace, přihlášení, úprava profilu a avatarů.
* **models/**
  * **comment_model.php** – databázové operace pro komentáře.
  * **post_model.php** – databázové operace pro příspěvky.
  * **user_model.php** – úlohy související s uživateli a autentizací.
* **config.php** – inicializuje backend, spouští session a nastavuje připojení k databázi.
* **avatar.php** – vrací avatar uživatele nebo výchozí obrázek, pokud avatar není nastaven.
