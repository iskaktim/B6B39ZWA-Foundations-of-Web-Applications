# **Zadání semestrální práce**

## **Zadání**

**Název:** Diskusní fórum\
**Cíl:** Poskytnout platformu, kde může komunita uživatelů vytvářet příspěvky, sdílet obsah a komunikovat prostřednictvím komentářů.

## **Uživatelské role**

* Nepřihlášení uživatelé (Guests)
* Registrovaní uživatelé
  * Běžní uživatelé
  * Administrátor
  * Owner

## **Popis**

### **Hlavní funkcionalita**

Příspěvek obsahuje titulek, obsah, informace o autorovi, datum vytvoření, volitelný obrázek a čas poslední úpravy.\
Uživatelé mohou komentovat příspěvky; každý komentář obsahuje autora, text a čas vytvoření.\
Hlavní stránka zobrazuje nejnovější příspěvky seřazené podle času a obsahuje stránkování.\
Každý příspěvek má vlastní detailní stránku s úplným obsahem a sekcí komentářů.

## **Funkce podle rolí**

### **Nepřihlášení uživatelé**

* Mohou procházet všechny příspěvky a komentáře.
* Mohou se registrovat nebo přihlásit.
* Nemohou vytvářet příspěvky ani komentáře.

### **Běžní uživatelé**

* Mohou vytvářet, upravovat a mazat své vlastní příspěvky.
* Mohou přidávat komentáře a spravovat své vlastní komentáře.
* Mohou upravovat informace ve svém profilu a avatar.

### **Administrátor**

* Může smazat jakýkoli příspěvek nebo komentář.
* Může povyšovat uživatele na administrátory.
* Má přístup do Admin Panelu.

### **Owner**

* Má plnou administrativní kontrolu.
* Může smazat jakéhokoli uživatele.
* Může přidělovat nebo odebírat admin roli.
